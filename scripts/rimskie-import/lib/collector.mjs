import { join } from 'node:path';

import { parseCategoryPage } from './category-parser.mjs';
import { parseProductPage } from './product-parser.mjs';
import { validateImageFile } from './playwright-transport.mjs';

const terminalStatuses = new Set(['completed', 'stopped']);
const retryableFailureKinds = new Set(['network', 'timeout', 'http_403', 'http_429']);

function assertSafeId(value, label) {
    if (typeof value !== 'string' || !value || value === '.' || value === '..'
        || value.includes('/') || value.includes('\\') || value.includes('..')) {
        throw new Error(`${label} path traversal is not allowed`);
    }
}

function sourceRecord(source, status = source.completed ? 'completed' : 'running') {
    return {
        label: source.label,
        source_url: source.sourceUrl,
        target_slug: source.sourceSlug,
        enabled: source.enabled,
        sort_order: source.sortOrder,
        status,
        pages: source.pages,
        next_page_url: source.nextPageUrl,
    };
}

function snapshot(state) {
    return {
        ...state,
        uniqueProducts: state.completedProductIds.length,
    };
}

async function readControl(control) {
    if (!control) return { pause: false, stop: false };
    if (typeof control.read === 'function') return control.read();

    return {
        pause: Boolean(await control.shouldPause?.()),
        stop: Boolean(await control.shouldStop?.()),
    };
}

export class Collector {
    async run({
        store,
        transport,
        policy,
        control,
        maxRequests = Number.POSITIVE_INFINITY,
        maxProducts = Number.POSITIVE_INFINITY,
        acknowledgeFailurePause = false,
    }) {
        const state = await store.readState();
        if (!state) throw new Error('Run state does not exist');
        if (terminalStatuses.has(state.status)) return snapshot(state);

        const context = {
            store,
            transport,
            policy,
            control,
            state,
            maxRequests,
            maxProducts,
            requestsThisRun: 0,
            productsThisRun: 0,
        };
        policy.hydrate(state.requestPolicy);
        state.requestPolicy = policy.snapshot();
        policy.connect({
            beforeReserve: async () => !await this.#mustHalt(context),
            persistState: async (policyState, reason) => {
                state.requestPolicy = policyState;
                if (reason === 'reservation') {
                    context.requestsThisRun += 1;
                    state.requestCount += 1;
                }
                await store.checkpoint(state);
            },
        });
        if (policy.requiresFailurePause()) {
            if (!acknowledgeFailurePause) {
                const pauseReason = policy.snapshot().lastFailureKind || 'failure-limit';
                const isNewPause = state.status !== 'paused' || state.pauseReason !== pauseReason;
                state.status = 'paused';
                state.pauseReason = pauseReason;
                if (isNewPause) {
                    await store.appendEvent({ type: 'pause', reason: pauseReason, recovered: true });
                }
                await store.checkpoint(state);
                return snapshot(state);
            }
            await policy.resumePendingBackoff();
            await policy.acknowledgeFailurePause();
        } else {
            await policy.resumePendingBackoff();
        }
        const previousStatus = state.status;
        state.status = 'running';
        delete state.pauseReason;
        if (previousStatus === 'paused' || previousStatus === 'limited') {
            await store.appendEvent({ type: 'resume', previousStatus });
        }
        await store.checkpoint(state);

        try {
            for (const source of state.sources) {
                if (await this.#mustHalt(context)) break;
                await this.#processSource(context, source);
            }

            if (state.status === 'running') {
                state.status = state.sources.every((source) => source.completed) ? 'completed' : 'limited';
                await store.appendEvent({
                    type: state.status === 'completed' ? 'completion' : 'pause',
                    reason: state.status === 'limited' ? 'limit' : undefined,
                });
                await store.checkpoint(state);
            }
        } catch (error) {
            state.status = 'error';
            delete state.pauseReason;
            await store.appendEvent({
                type: 'error',
                kind: 'collector',
                message: error?.message || String(error),
            });
            await store.checkpoint(state);
        }

        return snapshot(state);
    }

    async #processSource(context, source) {
        while (context.state.status === 'running' && !source.completed) {
            await this.#processPendingProducts(context, source);
            if (context.state.status !== 'running') return;

            if (!source.nextPageUrl) {
                source.completed = true;
                await context.store.saveSource(source.sourceSlug, sourceRecord(source, 'completed'));
                await context.store.checkpoint(context.state);
                return;
            }

            const pageUrl = source.nextPageUrl;
            const html = await this.#request(context, 'html', pageUrl, () => context.transport.getHtml(pageUrl));
            if (html === null) return;

            const page = parseCategoryPage(html, pageUrl);
            for (const product of page.products) {
                assertSafeId(product.externalId, 'product external ID');
                await context.store.appendMembership({
                    sourceSlug: source.sourceSlug,
                    externalId: product.externalId,
                });
                const isPending = source.pendingProducts.some(({ externalId }) => externalId === product.externalId);
                if (!isPending) source.pendingProducts.push(product);
            }
            source.pages += 1;
            source.nextPageUrl = page.nextPageUrl;
            await context.store.saveSource(source.sourceSlug, sourceRecord(source));
            await context.store.checkpoint(context.state);
        }
    }

    async #processPendingProducts(context, source) {
        while (source.pendingProducts.length > 0 && context.state.status === 'running') {
            if (await this.#mustHalt(context)) return;

            const card = source.pendingProducts[0];
            if (context.state.completedProductIds.includes(card.externalId)) {
                source.pendingProducts.shift();
                await context.store.checkpoint(context.state);
                continue;
            }
            if (context.productsThisRun >= context.maxProducts) {
                await this.#setLimited(context, 'max-products');
                return;
            }

            if (card.stage !== 'html-complete' && card.stage !== 'image-complete') {
                const savedDraft = card.draft?.collectionStage === 'html-complete'
                    ? card.draft
                    : await context.store.readProduct?.(card.externalId);
                if (savedDraft?.collectionStage === 'html-complete'
                    && savedDraft.externalId === card.externalId
                    && savedDraft.sourceUrl === card.sourceUrl) {
                    card.draft = savedDraft;
                    card.stage = 'html-complete';
                    await context.store.checkpoint(context.state);
                }
            }

            if (card.stage !== 'html-complete' && card.stage !== 'image-complete') {
                const html = await this.#request(
                    context,
                    'html',
                    card.sourceUrl,
                    () => context.transport.getHtml(card.sourceUrl),
                );
                if (html === null) return;

                const parsedProduct = parseProductPage(html, card.sourceUrl);
                const parsedExternalId = parsedProduct.externalId || card.externalId;
                assertSafeId(parsedExternalId, 'product external ID');
                card.draft = {
                    ...parsedProduct,
                    externalId: parsedExternalId,
                    firstImagePath: `images/${parsedExternalId}.webp`,
                    collectionStage: 'html-complete',
                };
                await context.store.saveProduct(parsedExternalId, card.draft);
                card.stage = 'html-complete';
                await context.store.checkpoint(context.state);
            }

            const product = card.draft;
            const externalId = product.externalId;
            assertSafeId(externalId, 'product external ID');
            if (!product.firstImageUrl) {
                await this.#setError(context, 'missing_first_image', card.sourceUrl);
                return;
            }

            const firstImagePath = `images/${externalId}.webp`;
            const destination = join(context.store.imagesDir, `${externalId}.webp`);
            const imageIsValid = await validateImageFile(destination, 'webp');
            if (card.stage === 'image-complete' && !imageIsValid) {
                card.stage = 'html-complete';
                await context.store.checkpoint(context.state);
            }
            if (card.stage !== 'image-complete') {
                if (!imageIsValid) {
                    const image = await this.#request(
                        context,
                        'image',
                        product.firstImageUrl,
                        () => context.transport.downloadFirstImage(product.firstImageUrl, destination),
                    );
                    if (image === null) return;
                }
                card.stage = 'image-complete';
                await context.store.checkpoint(context.state);
            }

            const { collectionStage, ...completedProduct } = product;
            await context.store.saveProduct(externalId, {
                ...completedProduct,
                externalId,
                firstImagePath,
            });
            context.state.completedProductIds.push(externalId);
            context.productsThisRun += 1;
            source.pendingProducts.shift();
            await context.store.checkpoint(context.state);
        }
    }

    async #request(context, kind, url, operation) {
        while (context.state.status === 'running') {
            if (await this.#mustHalt(context)) return null;
            if (context.requestsThisRun >= context.maxRequests) {
                await this.#setLimited(context, 'max-requests');
                return null;
            }

            try {
                await context.policy.beforeRequest(kind);
            } catch (error) {
                if (error?.code === 'request_cancelled') return null;
                const isHourlyBudget = error?.code === 'hourly_budget_exhausted';
                context.state.status = isHourlyBudget ? 'paused' : 'error';
                context.state.pauseReason = isHourlyBudget ? 'hourly-budget' : undefined;
                await context.store.appendEvent({
                    type: 'error',
                    kind: isHourlyBudget ? 'hourly_budget' : 'policy',
                    url,
                    message: error?.message || String(error),
                });
                if (isHourlyBudget) {
                    await context.store.appendEvent({ type: 'pause', reason: 'hourly-budget' });
                }
                await context.store.checkpoint(context.state);
                return null;
            }
            if (await this.#mustHalt(context)) return null;
            try {
                const result = await operation();
                await context.policy.recordSuccess();
                return result;
            } catch (error) {
                const failureKind = error?.kind || 'error';
                const errorEvent = {
                    type: failureKind === 'challenge' ? 'challenge' : 'error',
                    kind: failureKind,
                    url,
                    message: error?.message || String(error),
                };
                if (failureKind === 'challenge') {
                    await context.store.appendEvent(errorEvent);
                    context.state.status = 'paused';
                    context.state.pauseReason = 'challenge';
                    await context.store.appendEvent({ type: 'pause', reason: 'challenge' });
                    await context.store.checkpoint(context.state);
                    return null;
                }
                if (!retryableFailureKinds.has(failureKind)) {
                    await context.store.appendEvent(errorEvent);
                    context.state.status = 'error';
                    await context.store.checkpoint(context.state);
                    return null;
                }

                const action = await context.policy.recordFailure(failureKind, {
                    afterPersist: () => context.store.appendEvent(errorEvent),
                });
                if (action === 'pause') {
                    context.state.status = 'paused';
                    context.state.pauseReason = failureKind;
                    await context.store.appendEvent({ type: 'pause', reason: failureKind });
                    await context.store.checkpoint(context.state);
                    return null;
                }
            }
        }

        return null;
    }

    async #mustHalt(context) {
        if (context.state.status !== 'running') return true;
        const flags = await readControl(context.control);
        if (flags.stop) {
            context.state.status = 'stopped';
            delete context.state.pauseReason;
            await context.store.appendEvent({ type: 'stop', reason: 'operator' });
            await context.store.checkpoint(context.state);
            return true;
        }
        if (flags.pause) {
            context.state.status = 'paused';
            context.state.pauseReason = 'operator';
            await context.store.appendEvent({ type: 'pause', reason: 'operator' });
            await context.store.checkpoint(context.state);
            return true;
        }

        return false;
    }

    async #setLimited(context, reason) {
        context.state.status = 'limited';
        context.state.pauseReason = reason;
        await context.store.appendEvent({ type: 'pause', reason });
        await context.store.checkpoint(context.state);
    }

    async #setError(context, kind, url) {
        context.state.status = 'error';
        delete context.state.pauseReason;
        await context.store.appendEvent({ type: 'error', kind, url });
        await context.store.checkpoint(context.state);
    }
}

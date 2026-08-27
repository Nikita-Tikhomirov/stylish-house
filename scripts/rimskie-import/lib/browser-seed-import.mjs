import { lstat, readdir } from 'node:fs/promises';
import { join } from 'node:path';

import { resolveDonorUrl } from './donor-url-policy.mjs';
import {
    assertNumericExternalId,
    assertSafeDirectoryPath,
    readSafeFile,
    writeFileAtomically,
} from './safe-filesystem.mjs';
import { validateImageFile } from './webp.mjs';

const normalizedAttributeKeyPattern = /^[a-z0-9]+(?:_[a-z0-9]+)*$/;

function stableJson(value) {
    return JSON.stringify(value, null, 2);
}

function validateProgress(progress, store) {
    if (!progress || progress.schemaVersion !== 1 || progress.runId !== store.runId) {
        throw new Error('Browser seed progress does not match this run');
    }
    if (progress.status === 'running') {
        throw new Error('Cannot import browser seed while its writer is running');
    }
    if (!Array.isArray(progress.requestTimes)
        || progress.requestTimes.some((timestamp) => !Number.isSafeInteger(timestamp) || timestamp < 0)) {
        throw new Error('Browser seed progress has an invalid request ledger');
    }

    return [...new Set(progress.requestTimes)].sort((left, right) => left - right);
}

async function mergeRequestLedger(store, state, seedRequestTimes) {
    const importedRequestTimes = new Set(
        Array.isArray(state.browserSeedImportedRequestTimes)
            ? state.browserSeedImportedRequestTimes.filter(Number.isSafeInteger)
            : [],
    );
    const requestPolicy = state.requestPolicy && typeof state.requestPolicy === 'object'
        ? state.requestPolicy
        : {};
    const rollingRequestTimes = new Set(
        Array.isArray(requestPolicy.requestTimes)
            ? requestPolicy.requestTimes.filter(Number.isSafeInteger)
            : [],
    );
    let newlyImportedRequests = 0;

    for (const timestamp of seedRequestTimes) {
        rollingRequestTimes.add(timestamp);
        if (importedRequestTimes.has(timestamp)) continue;
        importedRequestTimes.add(timestamp);
        newlyImportedRequests += 1;
    }

    state.requestCount = (Number.isSafeInteger(state.requestCount) ? state.requestCount : 0)
        + newlyImportedRequests;
    state.requestPolicy = {
        ...requestPolicy,
        requestTimes: [...rollingRequestTimes].sort((left, right) => left - right),
    };
    state.browserSeedImportedRequestTimes = [...importedRequestTimes]
        .sort((left, right) => left - right);
    await store.checkpoint(state);
}

function validateAttributes(attributes, externalId) {
    if (!attributes || typeof attributes !== 'object' || Array.isArray(attributes)) {
        throw new Error(`Browser seed product ${externalId} requires normalized attributes`);
    }
    for (const [key, values] of Object.entries(attributes)) {
        if (!normalizedAttributeKeyPattern.test(key)
            || !Array.isArray(values) || values.length === 0
            || values.some((value) => typeof value !== 'string' || !value.trim())) {
            throw new Error(`Browser seed product ${externalId} has invalid attributes`);
        }
    }
}

function validateProduct(product, externalId, allowedSourceSlugs) {
    if (!product || product.externalId !== externalId) {
        throw new Error(`Browser seed product ${externalId} has a mismatched external ID`);
    }
    const requiredTextFields = [
        'sourceUrl', 'sourceTitle', 'sourceDescription', 'sourcePrice',
        'firstImageUrl', 'firstImagePath',
    ];
    for (const field of requiredTextFields) {
        if (typeof product[field] !== 'string' || !product[field].trim()) {
            throw new Error(`Browser seed product ${externalId} requires ${field}`);
        }
    }
    if (product.sourceDescription.length < 100) {
        throw new Error(`Browser seed product ${externalId} source description is incomplete`);
    }
    if (!/^\d+(?:\.\d{2})$/.test(product.sourcePrice)) {
        throw new Error(`Browser seed product ${externalId} has an invalid source price`);
    }
    if (product.firstImagePath !== `images/${externalId}.webp`) {
        throw new Error(`Browser seed product ${externalId} has an invalid first image path`);
    }
    const approvedProductUrl = resolveDonorUrl(product.sourceUrl, {
        kind: 'product', label: `Browser seed product ${externalId} URL`,
    });
    const approvedImageUrl = resolveDonorUrl(product.firstImageUrl, {
        kind: 'image', label: `Browser seed product ${externalId} image URL`,
    });
    if (approvedProductUrl !== product.sourceUrl || approvedImageUrl !== product.firstImageUrl
        || new URL(approvedProductUrl).pathname.match(/^\/products\/(\d+)/)?.[1] !== externalId) {
        throw new Error(`Browser seed product ${externalId} has an invalid donor URL`);
    }
    if (!Array.isArray(product.sourceSlugs) || product.sourceSlugs.length === 0
        || new Set(product.sourceSlugs).size !== product.sourceSlugs.length
        || product.sourceSlugs.some((sourceSlug) => !allowedSourceSlugs.has(sourceSlug))) {
        throw new Error(`Browser seed product ${externalId} references an unknown source`);
    }
    validateAttributes(product.attributes, externalId);
    return product;
}

async function requireRegularDirectory(runDir, path, label) {
    let stats;
    try {
        stats = await lstat(path);
    } catch (error) {
        if (error?.code === 'ENOENT') throw new Error(`Missing ${label} directory`);
        throw error;
    }
    if (!stats.isDirectory() || stats.isSymbolicLink()) {
        throw new Error(`${label} must be a regular directory`);
    }
    await assertSafeDirectoryPath(runDir, path);
}

export async function importBrowserSeed({ store }) {
    const { config, state } = await store.requireInitialized();
    if (state.status === 'running') {
        throw new Error('Cannot import browser seed while the collector is running');
    }
    const seedRoot = join(store.runDir, 'browser-seed');
    const seedProductsDir = join(seedRoot, 'products');
    const seedImagesDir = join(seedRoot, 'images');
    await requireRegularDirectory(store.runDir, seedRoot, 'browser seed');
    const progress = JSON.parse(await readSafeFile(
        store.runDir,
        join(seedRoot, 'progress.json'),
        'utf8',
    ));
    const seedRequestTimes = validateProgress(progress, store);
    await mergeRequestLedger(store, state, seedRequestTimes);
    await requireRegularDirectory(store.runDir, seedProductsDir, 'browser seed products');
    await requireRegularDirectory(store.runDir, seedImagesDir, 'browser seed images');

    const allowedSourceSlugs = new Set(config.sources.map((source) => source.sourceSlug));
    const productFileNames = (await readdir(seedProductsDir, { withFileTypes: true }))
        .filter((entry) => entry.isFile() && /^\d+\.json$/.test(entry.name))
        .map((entry) => entry.name)
        .sort((left, right) => Number(left.slice(0, -5)) - Number(right.slice(0, -5)));
    const completedIds = new Set(state.completedProductIds);
    let imported = 0;
    let skipped = 0;

    for (const fileName of productFileNames) {
        const externalId = assertNumericExternalId(fileName.slice(0, -5));
        const productPath = join(seedProductsDir, fileName);
        const imagePath = join(seedImagesDir, `${externalId}.webp`);
        const product = validateProduct(
            JSON.parse(await readSafeFile(store.runDir, productPath, 'utf8')),
            externalId,
            allowedSourceSlugs,
        );
        try {
            const imageStats = await lstat(imagePath);
            if (!imageStats.isFile() || imageStats.isSymbolicLink()) {
                throw new Error(`Browser seed image ${externalId} must be a regular file`);
            }
        } catch (error) {
            if (error?.code === 'ENOENT') {
                throw new Error(`Missing browser seed image for product ${externalId}`);
            }
            throw error;
        }
        if (!await validateImageFile(imagePath, 'webp')) {
            throw new Error(`Browser seed image ${externalId} is not a valid WebP file`);
        }

        const existingProduct = await store.readProduct(externalId);
        const destinationImagePath = join(store.imagesDir, `${externalId}.webp`);
        if (completedIds.has(externalId)) {
            if (!existingProduct || stableJson(existingProduct) !== stableJson(product)
                || !await validateImageFile(destinationImagePath, 'webp')) {
                throw new Error(`Completed product ${externalId} conflicts with browser seed data`);
            }
            skipped += 1;
            continue;
        }
        if (existingProduct && stableJson(existingProduct) !== stableJson(product)) {
            throw new Error(`Existing product ${externalId} conflicts with browser seed data`);
        }

        const imageBytes = await readSafeFile(store.runDir, imagePath);
        await writeFileAtomically(store.runDir, destinationImagePath, imageBytes);
        await store.saveProduct(externalId, product);
        for (const sourceSlug of product.sourceSlugs) {
            await store.appendMembership({ sourceSlug, externalId });
        }
        state.completedProductIds.push(externalId);
        completedIds.add(externalId);
        await store.checkpoint(state);
        await store.appendEvent({
            at: new Date().toISOString(),
            type: 'browser_seed_import',
            externalId,
        });
        imported += 1;
    }

    return { imported, skipped, completedProducts: completedIds.size };
}

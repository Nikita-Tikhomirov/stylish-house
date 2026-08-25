import { createHash } from 'node:crypto';
import { lstat, mkdir, readFile, readdir } from 'node:fs/promises';
import { isAbsolute, join, relative, sep } from 'node:path';

import {
    appendFileAtomically,
    assertContainedPath,
    assertNumericExternalId,
    assertSafeDirectoryPath,
    assertSafeFileTarget,
    assertSafeIdentifier,
    createFileAtomically,
    readSafeFile,
    writeFileAtomically,
} from './safe-filesystem.mjs';
import { validateImageFile } from './webp.mjs';
import { resolveDonorUrl } from './donor-url-policy.mjs';

const manifestSchemaVersion = 'stylish-house.catalog-import/v1';
const configSchemaVersion = 'stylish-house.catalog-import-run/v1';
const normalizedAttributeKeyPattern = /^[a-z0-9]+(?:_[a-z0-9]+)*$/;

function isMissingFile(error) {
    return error?.code === 'ENOENT';
}

function stableJson(value) {
    return `${JSON.stringify(value, null, 2)}\n`;
}

function validateProductAttributes(attributes, externalId) {
    if (!attributes || typeof attributes !== 'object' || Array.isArray(attributes)) {
        throw new Error(`Product ${externalId} attributes must be a record`);
    }

    for (const [key, values] of Object.entries(attributes)) {
        if (!normalizedAttributeKeyPattern.test(key)) {
            throw new Error(`Product ${externalId} attributes contain a non-normalized key`);
        }
        if (!Array.isArray(values) || values.length === 0) {
            throw new Error(`Product ${externalId} attributes must contain non-empty value arrays`);
        }
        const uniqueValues = new Set();
        for (const value of values) {
            if (typeof value !== 'string' || !value.trim() || value !== value.trim()) {
                throw new Error(`Product ${externalId} attributes contain an invalid value`);
            }
            if (uniqueValues.has(value)) {
                throw new Error(`Product ${externalId} attributes contain duplicate values`);
            }
            uniqueValues.add(value);
        }
    }
}

function canonicalize(value) {
    if (Array.isArray(value)) return value.map(canonicalize);
    if (!value || typeof value !== 'object') return value;
    return Object.fromEntries(
        Object.keys(value).sort().map((key) => [key, canonicalize(value[key])]),
    );
}

export function configDigest(config) {
    return createHash('sha256').update(JSON.stringify(canonicalize(config))).digest('hex');
}

function validateConfig(config) {
    if (!config || config.schema_version !== configSchemaVersion
        || !Array.isArray(config.sources) || config.sources.length === 0 || !config.limits) {
        throw new Error('Run config must include the supported schema_version, sources, and limits');
    }
    const { limits } = config;
    const validRange = (value) => Array.isArray(value) && value.length === 2
        && value.every((entry) => Number.isInteger(entry) && entry >= 0)
        && value[0] <= value[1];
    const validBackoff = Array.isArray(limits.backoff_ms) && limits.backoff_ms.length === 3
        && limits.backoff_ms.every((entry) => Number.isInteger(entry) && entry >= 0);
    const validCap = (value) => value === null || (Number.isInteger(value) && value > 0);
    if (!validRange(limits.html_delay_ms) || !validRange(limits.image_delay_ms)
        || !Number.isInteger(limits.hourly_requests) || limits.hourly_requests <= 0
        || !validBackoff || limits.concurrency !== 1
        || !validCap(limits.max_requests) || !validCap(limits.max_products)) {
        throw new Error('Run config limits do not match the required request-policy schema');
    }
    const sourceSlugs = new Set();
    const sourceOrders = new Set();
    for (const source of config.sources) {
        let approvedSourceUrl;
        try {
            assertSafeIdentifier(source?.sourceSlug, 'source slug', {
                pattern: /^[a-z0-9][a-z0-9-]{0,127}$/,
            });
            approvedSourceUrl = resolveDonorUrl(source.sourceUrl, {
                kind: 'category', label: 'configured source URL',
            });
        } catch (error) {
            throw new Error('Run config source schema is invalid', { cause: error });
        }
        if (typeof source.label !== 'string' || !source.label.trim()
            || approvedSourceUrl !== source.sourceUrl
            || source.enabled !== true
            || !Number.isInteger(source.sortOrder) || source.sortOrder <= 0
            || source.nextPageUrl !== source.sourceUrl
            || !Array.isArray(source.pendingProducts) || source.pendingProducts.length !== 0
            || source.completed !== false || source.pages !== 0
            || sourceSlugs.has(source.sourceSlug) || sourceOrders.has(source.sortOrder)) {
            throw new Error('Run config source schema is invalid');
        }
        sourceSlugs.add(source.sourceSlug);
        sourceOrders.add(source.sortOrder);
    }
    if (config.sources.some((source, index) => index > 0
        && source.sortOrder <= config.sources[index - 1].sortOrder)) {
        throw new Error('Run config source schema order is invalid');
    }
    return config;
}

async function readNdjson(path) {
    try {
        const text = await readFile(path, 'utf8');
        return text.split('\n').filter(Boolean).map(JSON.parse);
    } catch (error) {
        if (isMissingFile(error)) return [];
        throw error;
    }
}

function resolveFirstImagePath(runDir, imagesDir, imagePath, externalId) {
    const requestedPath = imagePath || `images/${externalId}.webp`;
    const pathParts = typeof requestedPath === 'string' ? requestedPath.split(/[\\/]+/) : [];
    if (!requestedPath || isAbsolute(requestedPath) || pathParts.includes('..')) {
        throw new Error(`Product ${externalId} first image path traversal is not allowed`);
    }

    const resolvedPath = join(runDir, requestedPath);
    const imageRelativePath = relative(imagesDir, resolvedPath);
    if (isAbsolute(imageRelativePath) || imageRelativePath === '..'
        || imageRelativePath.startsWith(`..${sep}`)) {
        throw new Error(`Product ${externalId} first image path must be inside images directory`);
    }
    if (requestedPath.replaceAll('\\', '/') !== `images/${externalId}.webp`) {
        throw new Error(`Product ${externalId} first image path must match its external ID`);
    }

    return resolvedPath;
}

async function directoryExists(path) {
    try {
        return (await lstat(path)).isDirectory();
    } catch (error) {
        if (isMissingFile(error)) return false;
        throw error;
    }
}

export class RunStore {
    static async open({ rootDir, runId }) {
        assertSafeIdentifier(runId, 'run ID');
        await mkdir(rootDir, { recursive: true });
        const runDir = assertContainedPath(rootDir, join(rootDir, runId), 'run directory');
        await mkdir(runDir, { recursive: true });
        const store = new RunStore(runDir, runId);
        await assertSafeDirectoryPath(store.runDir, store.runDir);
        for (const directory of [store.sourcesDir, store.productsDir, store.imagesDir]) {
            await mkdir(directory, { recursive: true });
            await assertSafeDirectoryPath(store.runDir, directory);
        }
        await store.#loadMembershipKeys();

        return store;
    }

    static async openExisting({ rootDir, runId }) {
        assertSafeIdentifier(runId, 'run ID');
        const runDir = assertContainedPath(rootDir, join(rootDir, runId), 'run directory');
        if (!await directoryExists(runDir)) throw new Error(`Import run does not exist: ${runId}`);
        const store = new RunStore(runDir, runId);
        await assertSafeDirectoryPath(store.runDir, store.runDir);
        for (const directory of [store.sourcesDir, store.productsDir, store.imagesDir]) {
            await assertSafeDirectoryPath(store.runDir, directory);
        }
        await store.#loadMembershipKeys();

        return store;
    }

    constructor(runDir, runId) {
        this.runDir = runDir;
        this.runId = runId;
        this.configPath = join(runDir, 'config.json');
        this.statePath = join(runDir, 'state.json');
        this.sourcesDir = join(runDir, 'sources');
        this.productsDir = join(runDir, 'products');
        this.imagesDir = join(runDir, 'images');
        this.membershipsPath = join(runDir, 'memberships.ndjson');
        this.eventsPath = join(runDir, 'events.ndjson');
        this.exportPath = join(runDir, 'export.json');
        this.membershipKeys = new Set();
    }

    async #loadMembershipKeys() {
        this.membershipKeys = new Set(
            (await this.readMemberships()).map(({ sourceSlug, externalId }) => `${sourceSlug}\u0000${externalId}`),
        );
    }

    async initializeConfig(config) {
        validateConfig(config);
        try {
            const existing = await this.readConfig();
            if (stableJson(existing) !== stableJson(config)) {
                throw new Error('Run config is immutable and does not match the existing config.json');
            }
            return existing;
        } catch (error) {
            if (!isMissingFile(error)) throw error;
        }

        if (await createFileAtomically(this.runDir, this.configPath, stableJson(config), 'utf8')) {
            return config;
        }
        const existing = await this.readConfig();
        if (stableJson(existing) !== stableJson(config)) {
            throw new Error('Run config is immutable and does not match the existing config.json');
        }
        return existing;
    }

    async readConfig() {
        return validateConfig(JSON.parse(await readSafeFile(this.runDir, this.configPath, 'utf8')));
    }

    async readState() {
        try {
            return JSON.parse(await readSafeFile(this.runDir, this.statePath, 'utf8'));
        } catch (error) {
            if (isMissingFile(error)) return null;
            throw error;
        }
    }

    async requireInitialized() {
        let config;
        try {
            config = await this.readConfig();
        } catch (error) {
            if (isMissingFile(error)) {
                throw new Error('Run requires initialized config.json and state.json');
            }
            throw error;
        }
        const state = await this.readState();
        if (!state || typeof state.status !== 'string' || !Array.isArray(state.sources)
            || !Array.isArray(state.completedProductIds)
            || !Number.isInteger(state.requestCount) || state.requestCount < 0) {
            throw new Error('Run requires a valid initialized state schema');
        }
        if (state.configDigest !== configDigest(config)) {
            throw new Error('Run state config digest does not match immutable config.json');
        }
        return { config, state };
    }

    async checkpoint(state) {
        await writeFileAtomically(this.runDir, this.statePath, stableJson(state), 'utf8');
    }

    async saveSource(slug, data) {
        assertSafeIdentifier(slug, 'source slug', { pattern: /^[a-z0-9][a-z0-9-]{0,127}$/ });
        await writeFileAtomically(
            this.runDir,
            join(this.sourcesDir, `${slug}.json`),
            stableJson(data),
            'utf8',
        );
    }

    async saveProduct(externalId, data) {
        assertNumericExternalId(externalId);
        await writeFileAtomically(
            this.runDir,
            join(this.productsDir, `${externalId}.json`),
            stableJson(data),
            'utf8',
        );
    }

    async readProduct(externalId) {
        assertNumericExternalId(externalId);
        const path = join(this.productsDir, `${externalId}.json`);
        try {
            return JSON.parse(await readSafeFile(this.runDir, path, 'utf8'));
        } catch (error) {
            if (isMissingFile(error)) return null;
            throw error;
        }
    }

    async readMemberships() {
        try {
            await assertSafeFileTarget(this.runDir, this.membershipsPath);
            return await readNdjson(this.membershipsPath);
        } catch (error) {
            if (isMissingFile(error)) return [];
            throw error;
        }
    }

    async appendMembership(record) {
        assertSafeIdentifier(record?.sourceSlug, 'source slug', {
            pattern: /^[a-z0-9][a-z0-9-]{0,127}$/,
        });
        assertNumericExternalId(record?.externalId);
        const key = `${record.sourceSlug}\u0000${record.externalId}`;
        if (this.membershipKeys.has(key)) return false;

        await appendFileAtomically(this.runDir, this.membershipsPath, `${JSON.stringify(record)}\n`);
        this.membershipKeys.add(key);
        return true;
    }

    async appendEvent(record) {
        await appendFileAtomically(this.runDir, this.eventsPath, `${JSON.stringify(record)}\n`);
    }

    async #readJsonDirectory(directory) {
        await assertSafeDirectoryPath(this.runDir, directory);
        const fileNames = (await readdir(directory))
            .filter((fileName) => fileName.endsWith('.json'))
            .sort();
        const records = [];
        for (const fileName of fileNames) {
            assertSafeIdentifier(fileName.slice(0, -5), 'JSON record ID');
            const path = join(directory, fileName);
            records.push(JSON.parse(await readSafeFile(this.runDir, path, 'utf8')));
        }
        return records;
    }

    async exportManifest() {
        const [config, state, memberships, sources] = await Promise.all([
            this.readConfig(),
            this.readState(),
            this.readMemberships(),
            this.#readJsonDirectory(this.sourcesDir),
        ]);
        if (!state) throw new Error('Cannot export a run without state.json');
        if (config.schema_version !== configSchemaVersion) {
            throw new Error(`Unsupported run config schema: ${config.schema_version || 'missing'}`);
        }
        if (state.status !== 'completed') {
            throw new Error(`Cannot export a run in ${state.status || 'unknown'} state; completed is required`);
        }
        const digest = configDigest(config);
        if (state.configDigest !== digest) {
            throw new Error('State config digest does not match immutable config.json');
        }
        if (!Array.isArray(state.sources) || state.sources.length === 0
            || state.sources.some((source) => !source.completed)) {
            throw new Error('Cannot export an incomplete source set');
        }
        if (state.sources.some((source) => source.nextPageUrl !== null
            || !Array.isArray(source.pendingProducts) || source.pendingProducts.length !== 0
            || !Number.isInteger(source.pages) || source.pages < 1)) {
            throw new Error('Completed source progress is contradictory or has pending products');
        }
        if (sources.length !== state.sources.length || sources.some((source) => source.status !== 'completed')) {
            throw new Error('Persisted source records are incomplete or inconsistent');
        }
        if (memberships.length === 0) throw new Error('Cannot export an empty membership set');

        const sourceSlugs = new Set(state.sources.map(({ sourceSlug }) => sourceSlug));
        const immutableSourceFields = ['label', 'sourceSlug', 'sourceUrl', 'enabled', 'sortOrder'];
        if (config.sources.length !== state.sources.length || state.sources.some((source, index) => {
            const configured = config.sources[index];
            return !configured
                || immutableSourceFields.some((field) => configured[field] !== source[field])
                || !Array.isArray(source.pendingProducts)
                || typeof source.completed !== 'boolean'
                || !Number.isInteger(source.pages) || source.pages < 0
                || (source.nextPageUrl !== null && typeof source.nextPageUrl !== 'string');
        })) {
            throw new Error('State sources differ from immutable config.json');
        }
        const persistedSources = new Map(sources.map((source) => [source.target_slug, source]));
        if (persistedSources.size !== sourceSlugs.size || state.sources.some((source) => {
            const persisted = persistedSources.get(source.sourceSlug);
            return !persisted
                || persisted.label !== source.label
                || persisted.source_url !== source.sourceUrl
                || persisted.target_slug !== source.sourceSlug
                || persisted.enabled !== source.enabled
                || persisted.sort_order !== source.sortOrder
                || persisted.status !== (source.completed ? 'completed' : 'running')
                || persisted.pages !== source.pages
                || persisted.next_page_url !== source.nextPageUrl;
        })) {
            throw new Error('Persisted source records contradict immutable config or state progress');
        }
        const orderedSources = state.sources.map((source) => persistedSources.get(source.sourceSlug));
        const membershipKeys = memberships.map(({ sourceSlug, externalId }) => `${sourceSlug}\u0000${externalId}`);
        if (new Set(membershipKeys).size !== membershipKeys.length) {
            throw new Error('Membership file contains duplicate records');
        }
        const externalIds = [...new Set(memberships.map(({ sourceSlug, externalId }) => {
            assertSafeIdentifier(sourceSlug, 'source slug', { pattern: /^[a-z0-9][a-z0-9-]{0,127}$/ });
            assertNumericExternalId(externalId);
            if (!sourceSlugs.has(sourceSlug)) {
                throw new Error(`Membership references unknown source ${sourceSlug}`);
            }
            return externalId;
        }))].sort((left, right) => Number(left) - Number(right));
        if (new Set(state.completedProductIds || []).size !== (state.completedProductIds || []).length) {
            throw new Error('Completed product IDs contain duplicates');
        }
        const completedIds = [...new Set(state.completedProductIds || [])]
            .map((externalId) => assertNumericExternalId(externalId))
            .sort((left, right) => Number(left) - Number(right));
        if (JSON.stringify(externalIds) !== JSON.stringify(completedIds)) {
            throw new Error('Completed products and memberships are inconsistent');
        }
        const productFileNames = (await readdir(this.productsDir))
            .filter((fileName) => fileName.endsWith('.json'))
            .sort();
        const expectedProductFileNames = externalIds.map((externalId) => `${externalId}.json`).sort();
        if (JSON.stringify(productFileNames) !== JSON.stringify(expectedProductFileNames)) {
            throw new Error('Product JSON files are incomplete or contain unreferenced records');
        }

        const products = [];
        const images = [];
        for (const externalId of externalIds) {
            const product = await this.readProduct(externalId);
            if (!product) throw new Error(`Missing product JSON for ${externalId}`);
            if (product.collectionStage || product.stage || product.draft) {
                throw new Error(`Product ${externalId} is a draft-stage or incomplete product record`);
            }
            if (product.externalId !== externalId) {
                throw new Error(`Product JSON external ID mismatch for ${externalId}`);
            }
            const requiredTextFields = [
                'sourceUrl', 'sourceTitle', 'sourceDescription', 'sourcePrice',
                'firstImageUrl', 'firstImagePath',
            ];
            for (const field of requiredTextFields) {
                if (typeof product[field] !== 'string' || !product[field].trim()) {
                    throw new Error(`Product ${externalId} requires ${field}`);
                }
            }
            validateProductAttributes(product.attributes, externalId);
            if (!/^\d+(?:\.\d{2})$/.test(product.sourcePrice)) {
                throw new Error(`Product ${externalId} sourcePrice must be an exact donor amount`);
            }
            let approvedProductUrl;
            let approvedImageUrl;
            try {
                approvedProductUrl = resolveDonorUrl(product.sourceUrl, {
                    kind: 'product', label: `Product ${externalId} sourceUrl`,
                });
                approvedImageUrl = resolveDonorUrl(product.firstImageUrl, {
                    kind: 'image', label: `Product ${externalId} firstImageUrl`,
                });
            } catch (error) {
                throw new Error(`Product ${externalId} requires approved product and image URLs`, {
                    cause: error,
                });
            }
            if (approvedProductUrl !== product.sourceUrl || approvedImageUrl !== product.firstImageUrl
                || new URL(approvedProductUrl).pathname.match(/^\/products\/(\d+)/)?.[1] !== externalId) {
                throw new Error(`Product ${externalId} requires approved exact product and image URLs`);
            }
            const imagePath = product.firstImagePath || product.first_image_path;
            const resolvedImagePath = resolveFirstImagePath(
                this.runDir,
                this.imagesDir,
                imagePath,
                externalId,
            );
            try {
                await assertSafeFileTarget(this.runDir, resolvedImagePath);
                const imageStats = await lstat(resolvedImagePath);
                if (!imageStats.isFile() || imageStats.isSymbolicLink() || imageStats.nlink !== 1) {
                    throw new Error(`Product ${externalId} first image must be a regular private file`);
                }
            } catch (error) {
                if (isMissingFile(error)) throw new Error(`Missing first image for ${externalId}`);
                throw error;
            }
            if (!await validateImageFile(resolvedImagePath, 'webp')) {
                throw new Error(`Product ${externalId} first image is not a structurally valid WebP file`);
            }
            const imageBytes = await readSafeFile(this.runDir, resolvedImagePath);
            images.push({
                external_id: externalId,
                path: imagePath,
                byte_length: imageBytes.length,
                sha256: createHash('sha256').update(imageBytes).digest('hex'),
            });
            products.push(product);
        }

        const manifest = {
            schema_version: manifestSchemaVersion,
            run_id: this.runId,
            config_digest: digest,
            config,
            state,
            sources: orderedSources,
            products,
            images,
            memberships,
            counts: {
                sources: orderedSources.length,
                products: products.length,
                memberships: memberships.length,
                images: images.length,
            },
        };
        await writeFileAtomically(this.runDir, this.exportPath, stableJson(manifest), 'utf8');

        return manifest;
    }
}

export { configSchemaVersion, manifestSchemaVersion };

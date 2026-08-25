import { lstat, mkdir, readFile, readdir } from 'node:fs/promises';
import { isAbsolute, join, relative, sep } from 'node:path';

import {
    appendFileAtomically,
    assertContainedPath,
    assertNumericExternalId,
    assertSafeDirectoryPath,
    assertSafeFileTarget,
    assertSafeIdentifier,
    readSafeFile,
    writeFileAtomically,
} from './safe-filesystem.mjs';
import { validateImageFile } from './webp.mjs';

const manifestSchemaVersion = 'stylish-house.catalog-import/v1';
const configSchemaVersion = 'stylish-house.catalog-import-run/v1';

function isMissingFile(error) {
    return error?.code === 'ENOENT';
}

function stableJson(value) {
    return `${JSON.stringify(value, null, 2)}\n`;
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
        if (!await directoryExists(runDir)) await mkdir(runDir);
        const store = new RunStore(runDir, runId);
        await assertSafeDirectoryPath(store.runDir, store.runDir);
        for (const directory of [store.sourcesDir, store.productsDir, store.imagesDir]) {
            if (!await directoryExists(directory)) await mkdir(directory);
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
        if (!config || config.schema_version !== configSchemaVersion
            || !Array.isArray(config.sources) || config.sources.length === 0 || !config.limits) {
            throw new Error('Run config must include schema_version, sources, and limits');
        }
        try {
            const existing = await this.readConfig();
            if (stableJson(existing) !== stableJson(config)) {
                throw new Error('Run config is immutable and does not match the existing config.json');
            }
            return existing;
        } catch (error) {
            if (!isMissingFile(error)) throw error;
        }

        await writeFileAtomically(this.runDir, this.configPath, stableJson(config), 'utf8');
        return config;
    }

    async readConfig() {
        return JSON.parse(await readSafeFile(this.runDir, this.configPath, 'utf8'));
    }

    async readState() {
        try {
            return JSON.parse(await readSafeFile(this.runDir, this.statePath, 'utf8'));
        } catch (error) {
            if (isMissingFile(error)) return null;
            throw error;
        }
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
        if (!Array.isArray(state.sources) || state.sources.length === 0
            || state.sources.some((source) => !source.completed)) {
            throw new Error('Cannot export an incomplete source set');
        }
        if (sources.length !== state.sources.length || sources.some((source) => source.status !== 'completed')) {
            throw new Error('Persisted source records are incomplete or inconsistent');
        }
        if (memberships.length === 0) throw new Error('Cannot export an empty membership set');

        const sourceSlugs = new Set(state.sources.map(({ sourceSlug }) => sourceSlug));
        const configuredSources = new Map(config.sources.map((source) => [source.sourceSlug, source]));
        if (configuredSources.size !== state.sources.length || state.sources.some((source) => {
            const configured = configuredSources.get(source.sourceSlug);
            return !configured || configured.sourceUrl !== source.sourceUrl;
        })) {
            throw new Error('State sources differ from immutable config.json');
        }
        const persistedSourceSlugs = new Set(sources.map((source) => source.target_slug));
        if (persistedSourceSlugs.size !== sourceSlugs.size
            || [...sourceSlugs].some((slug) => !persistedSourceSlugs.has(slug))) {
            throw new Error('Persisted source records do not match state sources');
        }
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
        for (const externalId of externalIds) {
            const product = await this.readProduct(externalId);
            if (!product) throw new Error(`Missing product JSON for ${externalId}`);
            if (product.externalId !== externalId) {
                throw new Error(`Product JSON external ID mismatch for ${externalId}`);
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
            products.push(product);
        }

        const manifest = {
            schema_version: manifestSchemaVersion,
            run_id: this.runId,
            config,
            state,
            sources,
            products,
            memberships,
        };
        await writeFileAtomically(this.runDir, this.exportPath, stableJson(manifest), 'utf8');

        return manifest;
    }
}

export { configSchemaVersion, manifestSchemaVersion };

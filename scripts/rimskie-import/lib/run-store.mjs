import { mkdir, readFile, readdir, rename, writeFile } from 'node:fs/promises';
import { join, relative, sep } from 'node:path';

const manifestSchemaVersion = 'stylish-house.catalog-import/v1';

function assertSafePathSegment(value, label) {
    if (typeof value !== 'string' || !value || value === '.' || value === '..'
        || value.includes('/') || value.includes('\\') || value.includes('..')) {
        throw new Error(`${label} path traversal is not allowed`);
    }
}

function isMissingFile(error) {
    return error?.code === 'ENOENT';
}

async function readJson(path) {
    return JSON.parse(await readFile(path, 'utf8'));
}

async function writeJsonAtomically(path, data) {
    const tempPath = `${path}.${process.pid}.tmp`;
    await writeFile(tempPath, `${JSON.stringify(data, null, 2)}\n`, 'utf8');
    await rename(tempPath, path);
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

async function readJsonDirectory(directory) {
    const fileNames = (await readdir(directory))
        .filter((fileName) => fileName.endsWith('.json'))
        .sort();

    return Promise.all(fileNames.map((fileName) => readJson(join(directory, fileName))));
}

export class RunStore {
    static async open({ rootDir, runId }) {
        assertSafePathSegment(runId, 'run ID');
        const runDir = join(rootDir, runId);
        const store = new RunStore(runDir, runId);

        await Promise.all([
            mkdir(store.sourcesDir, { recursive: true }),
            mkdir(store.productsDir, { recursive: true }),
            mkdir(store.imagesDir, { recursive: true }),
        ]);
        store.membershipKeys = new Set(
            (await store.readMemberships()).map(({ sourceSlug, externalId }) => `${sourceSlug}\u0000${externalId}`),
        );

        return store;
    }

    constructor(runDir, runId) {
        this.runDir = runDir;
        this.runId = runId;
        this.statePath = join(runDir, 'state.json');
        this.sourcesDir = join(runDir, 'sources');
        this.productsDir = join(runDir, 'products');
        this.imagesDir = join(runDir, 'images');
        this.membershipsPath = join(runDir, 'memberships.ndjson');
        this.eventsPath = join(runDir, 'events.ndjson');
        this.exportPath = join(runDir, 'export.json');
        this.membershipKeys = new Set();
    }

    async readState() {
        try {
            return await readJson(this.statePath);
        } catch (error) {
            if (isMissingFile(error)) return null;
            throw error;
        }
    }

    async checkpoint(state) {
        await writeJsonAtomically(this.statePath, state);
    }

    async saveSource(slug, data) {
        assertSafePathSegment(slug, 'source slug');
        await writeJsonAtomically(join(this.sourcesDir, `${slug}.json`), data);
    }

    async saveProduct(externalId, data) {
        assertSafePathSegment(externalId, 'product external ID');
        await writeJsonAtomically(join(this.productsDir, `${externalId}.json`), data);
    }

    async readMemberships() {
        return readNdjson(this.membershipsPath);
    }

    async appendMembership(record) {
        assertSafePathSegment(record?.sourceSlug, 'source slug');
        assertSafePathSegment(record?.externalId, 'product external ID');
        const key = `${record.sourceSlug}\u0000${record.externalId}`;
        if (this.membershipKeys.has(key)) return false;

        await writeFile(this.membershipsPath, `${JSON.stringify(record)}\n`, { encoding: 'utf8', flag: 'a' });
        this.membershipKeys.add(key);
        return true;
    }

    async appendEvent(record) {
        await writeFile(this.eventsPath, `${JSON.stringify(record)}\n`, { encoding: 'utf8', flag: 'a' });
    }

    async exportManifest() {
        const memberships = await this.readMemberships();
        const externalIds = [...new Set(memberships.map(({ externalId }) => externalId))];
        const products = [];

        for (const externalId of externalIds) {
            assertSafePathSegment(externalId, 'product external ID');
            const productPath = join(this.productsDir, `${externalId}.json`);
            let product;
            try {
                product = await readJson(productPath);
            } catch (error) {
                if (isMissingFile(error)) {
                    throw new Error(`Missing product JSON for ${externalId}`);
                }
                throw error;
            }

            const imagePath = product.firstImagePath || product.first_image_path || `images/${externalId}.webp`;
            const resolvedImagePath = join(this.runDir, imagePath);
            const imageRelativePath = relative(this.runDir, resolvedImagePath);
            if (imageRelativePath.startsWith(`..${sep}`) || imageRelativePath === '..') {
                throw new Error(`Product ${externalId} first image path traversal is not allowed`);
            }

            try {
                await readFile(resolvedImagePath);
            } catch (error) {
                if (isMissingFile(error)) {
                    throw new Error(`Missing first image for ${externalId}`);
                }
                throw error;
            }
            products.push(product);
        }

        const manifest = {
            schema_version: manifestSchemaVersion,
            run_id: this.runId,
            state: await this.readState(),
            sources: await readJsonDirectory(this.sourcesDir),
            products,
            memberships,
        };
        await writeJsonAtomically(this.exportPath, manifest);

        return manifest;
    }
}

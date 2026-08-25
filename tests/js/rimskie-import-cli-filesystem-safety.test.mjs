import assert from 'node:assert/strict';
import { execFile } from 'node:child_process';
import { link, mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';
import { promisify } from 'node:util';
import { fileURLToPath } from 'node:url';

import {
    assertRunDirectorySafe,
    ControlFile,
    parseArguments,
} from '../../scripts/rimskie-import/cli.mjs';
import { configSchemaVersion, RunStore } from '../../scripts/rimskie-import/lib/run-store.mjs';

const execFileAsync = promisify(execFile);

test('CLI rejects Windows reserved names and ADS syntax in run IDs', () => {
    for (const runId of ['CON', 'nul.json', 'run:stream', '..hidden', 'trailing.']) {
        assert.throws(() => parseArguments([
            'status', '--run', runId, '--data-root', 'G:\\stylish-house-data\\rimskie-imports',
        ]), /run ID.*safe/i);
    }
});

test('openExisting rejects a missing run without creating it', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-existing-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const runDir = join(rootDir, 'missing-run');

    await assert.rejects(RunStore.openExisting({ rootDir, runId: 'missing-run' }), /does not exist/i);
    await assert.rejects(readFile(join(runDir, 'state.json')), /ENOENT/);
});

test('non-start CLI commands fail on a missing run without creating it', async (t) => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runId = `missing-cli-${process.pid}-${Date.now()}`;
    const runDir = join(dataRoot, runId);
    t.after(() => rm(runDir, { recursive: true, force: true }));
    const cliPath = fileURLToPath(new URL('../../scripts/rimskie-import/cli.mjs', import.meta.url));

    for (const command of ['status', 'pause', 'stop', 'export', 'resume']) {
        await assert.rejects(execFileAsync(process.execPath, [
            cliPath, command, '--run', runId, '--data-root', dataRoot,
        ]), /does not exist/i);
    }
    await assert.rejects(readFile(join(runDir, 'state.json')), /ENOENT/);
});

test('run config is create-once and immutable', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-config-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });
    const config = {
        schema_version: configSchemaVersion,
        sources: [{ target_slug: 'white', source_url: 'https://rimskie.com/catalog/rimskie-shtory/white' }],
        limits: { hourly_requests: 120, max_requests: 3, max_products: 1 },
    };

    await store.initializeConfig(config);
    assert.deepEqual(await store.readConfig(), config);
    await assert.rejects(
        store.initializeConfig({ ...config, limits: { ...config.limits, hourly_requests: 121 } }),
        /immutable/i,
    );
});

test('stale-lock EEXIST crash window is recovered only from the same hard link', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-lock-crash-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const controlPath = join(rootDir, 'control.json');
    const lockPath = `${controlPath}.lock`;
    const stalePath = `${lockPath}.stale.dead-token`;
    await writeFile(lockPath, JSON.stringify({ pid: 999999, token: 'dead-token' }), 'utf8');
    await link(lockPath, stalePath);
    const control = new ControlFile(controlPath, { isLiveProcess: () => false });

    await control.update({ pause: true });

    assert.deepEqual(await control.read(), { pause: true, stop: false });
    assert.equal(JSON.parse(await readFile(stalePath, 'utf8')).token, 'dead-token');
});

test('recursive run safety rejects a nested profile junction before writes', async () => {
    const dataRoot = 'G:\\stylish-house-data\\rimskie-imports';
    const runDir = `${dataRoot}\\run-001`;
    const profileDir = `${runDir}\\profile`;
    const nestedDir = `${profileDir}\\Cache`;
    const directories = new Map([
        [runDir, ['profile']],
        [profileDir, ['Cache']],
        [nestedDir, []],
    ]);
    const fakeDirectoryStats = { isDirectory: () => true, isSymbolicLink: () => false, isFile: () => false };

    await assert.rejects(assertRunDirectorySafe(dataRoot, 'run-001', {
        platform: 'win32',
        realpath: async (path) => path === nestedDir ? 'C:\\escaped-profile-cache' : path,
        lstat: async () => fakeDirectoryStats,
        readdir: async (path) => directories.get(path) || [],
    }), /nested run path|junction|drive C:/i);
});

test('numeric product IDs and safe source slugs are mandatory for persisted paths', async (t) => {
    const rootDir = await mkdtemp(join(tmpdir(), 'rimskie-identifiers-'));
    t.after(() => rm(rootDir, { recursive: true, force: true }));
    const store = await RunStore.open({ rootDir, runId: 'run-001' });

    for (const externalId of ['abc', '11889:stream', 'CON', '../11889']) {
        await assert.rejects(store.saveProduct(externalId, {}), /product external ID.*safe/i);
    }
    for (const slug of ['White_With_Underscore', 'nul', 'bad:stream', '../white']) {
        await assert.rejects(store.saveSource(slug, {}), /source slug.*safe/i);
    }
});


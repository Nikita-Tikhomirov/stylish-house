#!/usr/bin/env node

import { execFile as execFileCallback } from 'node:child_process';
import { randomUUID } from 'node:crypto';
import { constants } from 'node:fs';
import {
    access as fsAccess,
    link as fsLink,
    lstat as fsLstat,
    mkdir as fsMkdir,
    readFile,
    readdir as fsReaddir,
    realpath as fsRealpath,
    rename,
    unlink,
    writeFile,
} from 'node:fs/promises';
import { basename, dirname, join, posix, win32 } from 'node:path';
import { fileURLToPath } from 'node:url';
import { setTimeout as sleep } from 'node:timers/promises';
import { promisify } from 'node:util';

import { DEFAULT_LIMITS } from './lib/request-policy.mjs';
import { configDigest, configSchemaVersion, RunStore } from './lib/run-store.mjs';
import {
    assertSafeDirectoryPath,
    assertSafeIdentifier,
    readSafeFile,
    writeFileAtomically,
} from './lib/safe-filesystem.mjs';

const commands = new Set(['start', 'status', 'pause', 'resume', 'stop', 'export', 'dry-run']);
const valueOptions = new Set(['--run', '--data-root', '--chrome', '--max-requests', '--max-products']);
const execFileAsync = promisify(execFileCallback);

function positiveInteger(value, option) {
    const parsed = Number(value);
    if (!Number.isInteger(parsed) || parsed <= 0) throw new Error(`${option} must be a positive integer`);

    return parsed;
}

export function parseArguments(argv, environment = process.env) {
    const [command, ...tokens] = argv;
    if (!commands.has(command)) {
        throw new Error('Command must be start, status, pause, resume, stop, export, or dry-run');
    }

    const values = new Map();
    let json = false;
    for (let index = 0; index < tokens.length; index += 1) {
        const token = tokens[index];
        if (token === '--json') {
            json = true;
            continue;
        }
        if (!valueOptions.has(token)) throw new Error(`Unknown option: ${token}`);
        const value = tokens[index + 1];
        if (!value || value.startsWith('--')) throw new Error(`${token} requires a value`);
        values.set(token, value);
        index += 1;
    }

    const runId = values.get('--run');
    const dataRoot = values.get('--data-root') || environment.RIMSKIE_IMPORT_DATA_ROOT;
    if (!runId) throw new Error('--run is required');
    if (!dataRoot) throw new Error('--data-root or RIMSKIE_IMPORT_DATA_ROOT is required');
    assertSafeIdentifier(runId, 'run ID');

    return {
        command,
        runId,
        dataRoot,
        chrome: values.get('--chrome'),
        json,
        maxRequests: values.has('--max-requests')
            ? positiveInteger(values.get('--max-requests'), '--max-requests')
            : command === 'dry-run' ? 3 : Number.POSITIVE_INFINITY,
        maxProducts: values.has('--max-products')
            ? positiveInteger(values.get('--max-products'), '--max-products')
            : command === 'dry-run' ? 1 : Number.POSITIVE_INFINITY,
        maxRequestsExplicit: values.has('--max-requests'),
        maxProductsExplicit: values.has('--max-products'),
    };
}

function pathApi(platform) {
    return platform === 'win32' ? win32 : posix;
}

function assertAllowedRoot(value, platform) {
    const paths = pathApi(platform);
    if (!paths.isAbsolute(value)) throw new Error('Data root must be an absolute path');
    if (platform !== 'win32') return;

    const normalized = value.replaceAll('/', '\\');
    const namespacePatterns = [/^\\\\[?.]\\/, /^\\\\\?\?\\/, /^\\\?\?\\/];
    let ordinaryPath = normalized;
    let namespaced = false;
    for (const pattern of namespacePatterns) {
        if (!pattern.test(ordinaryPath)) continue;
        ordinaryPath = ordinaryPath.replace(pattern, '');
        namespaced = true;
        break;
    }
    const drive = ordinaryPath.match(/^([a-z]):\\/i)?.[1]?.toUpperCase();
    if (drive === 'C') throw new Error('Data root must not use Windows drive C:');
    if (namespaced || !drive) {
        throw new Error('Data root must use a regular Windows drive-letter path');
    }
}

async function resolveThroughExistingAncestor(value, platform, realpath) {
    const paths = pathApi(platform);
    let candidate = value;

    while (true) {
        try {
            const resolvedAncestor = await realpath(candidate);
            assertAllowedRoot(resolvedAncestor, platform);
            const resolvedTarget = paths.resolve(resolvedAncestor, paths.relative(candidate, value));
            assertAllowedRoot(resolvedTarget, platform);
            return resolvedTarget;
        } catch (error) {
            if (error?.code !== 'ENOENT') throw error;
            const parent = paths.dirname(candidate);
            if (parent === candidate) throw error;
            candidate = parent;
        }
    }
}

export async function resolveDataRoot(value, {
    platform = process.platform,
    create = true,
    mkdir = fsMkdir,
    access = fsAccess,
    realpath = fsRealpath,
} = {}) {
    assertAllowedRoot(value, platform);
    const safeTarget = await resolveThroughExistingAncestor(value, platform, realpath);
    if (create) await mkdir(safeTarget, { recursive: true });
    await access(safeTarget, constants.W_OK);
    const resolved = await realpath(safeTarget);
    assertAllowedRoot(resolved, platform);

    return resolved;
}

export async function assertRunDirectorySafe(dataRoot, runId, {
    platform = process.platform,
    realpath = fsRealpath,
    lstat = fsLstat,
    readdir = fsReaddir,
    unlink: unlinkFile = unlink,
} = {}) {
    assertSafeIdentifier(runId, 'run ID');

    const paths = pathApi(platform);
    const requestedRunDir = paths.join(dataRoot, runId);
    const resolvedRunDir = await resolveThroughExistingAncestor(requestedRunDir, platform, realpath);
    assertAllowedRoot(resolvedRunDir, platform);

    const comparableRequested = paths.resolve(requestedRunDir);
    const comparableResolved = paths.resolve(resolvedRunDir);
    const sameLocation = platform === 'win32'
        ? comparableRequested.toLowerCase() === comparableResolved.toLowerCase()
        : comparableRequested === comparableResolved;
    if (!sameLocation) {
        throw new Error('Run directory must stay inside the validated data root without junctions');
    }

    const childNames = [
        'sources',
        'products',
        'images',
        'profile',
        'config.json',
        'state.json',
        'control.json',
        'control.json.lock',
        'memberships.ndjson',
        'events.ndjson',
        'export.json',
    ];
    for (const childName of childNames) {
        const childPath = paths.join(requestedRunDir, childName);
        let resolvedChild;
        try {
            resolvedChild = await realpath(childPath);
        } catch (error) {
            if (error?.code === 'ENOENT') continue;
            throw error;
        }
        assertAllowedRoot(resolvedChild, platform);
        const requestedChild = paths.resolve(childPath);
        const actualChild = paths.resolve(resolvedChild);
        const sameChild = platform === 'win32'
            ? requestedChild.toLowerCase() === actualChild.toLowerCase()
            : requestedChild === actualChild;
        if (!sameChild) {
            throw new Error(`Run child ${childName} must not use a junction or symbolic link`);
        }
    }

    function assertSafeLeafName(name) {
        if (typeof name !== 'string' || !name || name === '.' || name === '..'
            || name.includes('/') || name.includes('\\') || name.includes(':')
            || /[. ]$/.test(name)
            || /^(?:con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\..*)?$/i.test(name)) {
            throw new Error(`Unsafe nested run path name: ${name}`);
        }
    }

    const sameFileIdentity = (left, right) => left.dev !== undefined && left.ino !== undefined
        && left.dev === right.dev && left.ino === right.ino;
    const uuidPattern = '[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';
    const createAliasPattern = new RegExp(`^config\\.json\\.${uuidPattern}\\.create$`, 'i');
    const claimAliasPattern = new RegExp(`^control\\.json\\.lock\\.claim\\.([1-9][0-9]*)\\.(${uuidPattern})$`, 'i');
    const staleLockPattern = new RegExp(`^control\\.json\\.lock\\.stale\\.(${uuidPattern})$`, 'i');
    const duplicateStaleLockPattern = new RegExp(
        `^control\\.json\\.lock\\.stale\\.(${uuidPattern})\\.(${uuidPattern})$`,
        'i',
    );
    const verifiedHardLinkPaths = new Set();

    async function removeRedundantAlias(aliasPath) {
        try {
            await unlinkFile(aliasPath);
        } catch (error) {
            if (error?.code !== 'ENOENT') throw error;
        }
    }

    async function recoverPublishedConfigAlias(name) {
        if (!createAliasPattern.test(name)) return;
        const aliasPath = paths.join(requestedRunDir, name);
        const targetPath = paths.join(requestedRunDir, 'config.json');
        let aliasStats;
        let targetStats;
        try {
            [aliasStats, targetStats] = await Promise.all([lstat(aliasPath), lstat(targetPath)]);
        } catch (error) {
            if (error?.code === 'ENOENT') return;
            throw error;
        }
        if (!aliasStats.isFile?.() || aliasStats.isSymbolicLink?.() || aliasStats.nlink <= 1
            || !targetStats.isFile?.() || targetStats.isSymbolicLink?.()
            || !sameFileIdentity(aliasStats, targetStats)) return;
        await removeRedundantAlias(aliasPath);
    }

    async function recoverPublishedClaimAlias(name) {
        const match = claimAliasPattern.exec(name);
        if (!match) return;
        const aliasPath = paths.join(requestedRunDir, name);
        let aliasStats;
        try {
            aliasStats = await lstat(aliasPath);
        } catch (error) {
            if (error?.code === 'ENOENT') return;
            throw error;
        }
        if (!aliasStats.isFile?.() || aliasStats.isSymbolicLink?.() || aliasStats.nlink <= 1) return;

        let metadata;
        try {
            metadata = JSON.parse(await readFile(aliasPath, 'utf8'));
        } catch (error) {
            if (error?.code === 'ENOENT' || error instanceof SyntaxError) return;
            throw error;
        }
        if (metadata?.pid !== Number(match[1]) || metadata?.token !== match[2]) return;

        const lockPath = paths.join(requestedRunDir, 'control.json.lock');
        const targetPaths = [lockPath, `${lockPath}.stale.${match[2]}`];
        for (const targetPath of targetPaths) {
            let targetStats;
            try {
                targetStats = await lstat(targetPath);
            } catch (error) {
                if (error?.code === 'ENOENT') continue;
                throw error;
            }
            if (targetStats.isFile?.() && !targetStats.isSymbolicLink?.()
                && sameFileIdentity(aliasStats, targetStats)) {
                await removeRedundantAlias(aliasPath);
                return;
            }
        }
    }

    async function verifyStaleLockPair(name) {
        const match = staleLockPattern.exec(name);
        if (!match) return;
        const lockPath = paths.join(requestedRunDir, 'control.json.lock');
        const stalePath = paths.join(requestedRunDir, name);
        let lockStats;
        let staleStats;
        try {
            [lockStats, staleStats] = await Promise.all([lstat(lockPath), lstat(stalePath)]);
        } catch (error) {
            if (error?.code === 'ENOENT') return;
            throw error;
        }
        if (!lockStats.isFile?.() || lockStats.isSymbolicLink?.() || lockStats.nlink !== 2
            || !staleStats.isFile?.() || staleStats.isSymbolicLink?.() || staleStats.nlink !== 2
            || !sameFileIdentity(lockStats, staleStats)) return;

        let metadata;
        try {
            metadata = JSON.parse(await readFile(lockPath, 'utf8'));
        } catch (error) {
            if (error?.code === 'ENOENT' || error instanceof SyntaxError) return;
            throw error;
        }
        if (metadata?.token !== match[1]) return;
        verifiedHardLinkPaths.add(paths.resolve(lockPath));
        verifiedHardLinkPaths.add(paths.resolve(stalePath));
    }

    async function recoverDuplicateStaleAlias(name) {
        const match = duplicateStaleLockPattern.exec(name);
        if (!match) return;
        const redundantPath = paths.join(requestedRunDir, name);
        const canonicalStalePath = paths.join(
            requestedRunDir,
            `control.json.lock.stale.${match[1]}`,
        );
        let redundantStats;
        let canonicalStats;
        try {
            [redundantStats, canonicalStats] = await Promise.all([
                lstat(redundantPath), lstat(canonicalStalePath),
            ]);
        } catch (error) {
            if (error?.code === 'ENOENT') return;
            throw error;
        }
        if (!redundantStats.isFile?.() || redundantStats.isSymbolicLink?.()
            || redundantStats.nlink <= 1
            || !canonicalStats.isFile?.() || canonicalStats.isSymbolicLink?.()
            || !sameFileIdentity(redundantStats, canonicalStats)) return;

        let metadata;
        try {
            metadata = JSON.parse(await readFile(canonicalStalePath, 'utf8'));
        } catch (error) {
            if (error?.code === 'ENOENT' || error instanceof SyntaxError) return;
            throw error;
        }
        if (metadata?.token !== match[1]) return;
        await removeRedundantAlias(redundantPath);
    }

    let topLevelEntries;
    try {
        topLevelEntries = await readdir(requestedRunDir, { withFileTypes: true });
    } catch (error) {
        if (error?.code === 'ENOENT') return requestedRunDir;
        throw error;
    }
    for (const entry of topLevelEntries) {
        const name = typeof entry === 'string' ? entry : entry.name;
        await recoverPublishedConfigAlias(name);
        await recoverPublishedClaimAlias(name);
        await recoverDuplicateStaleAlias(name);
    }
    for (const entry of topLevelEntries) {
        const name = typeof entry === 'string' ? entry : entry.name;
        await verifyStaleLockPair(name);
    }

    async function inspectRecursively(path) {
        let stats;
        try {
            stats = await lstat(path);
        } catch (error) {
            if (error?.code === 'ENOENT') return;
            throw error;
        }
        if (stats.isSymbolicLink?.()) {
            throw new Error(`Nested run path must not use a junction or symbolic link: ${path}`);
        }
        const knownLockRecoveryArtifact = verifiedHardLinkPaths.has(paths.resolve(path));
        if (stats.isFile?.() && stats.nlink > 1 && !knownLockRecoveryArtifact) {
            throw new Error(`Nested run path contains an unsafe hard-linked file: ${path}`);
        }
        let actual;
        try {
            actual = await realpath(path);
        } catch (error) {
            if (error?.code === 'ENOENT') return;
            throw error;
        }
        assertAllowedRoot(actual, platform);
        const requested = paths.resolve(path);
        const resolved = paths.resolve(actual);
        const same = platform === 'win32'
            ? requested.toLowerCase() === resolved.toLowerCase()
            : requested === resolved;
        if (!same) throw new Error(`Nested run path resolves through a junction: ${path}`);

        if (!stats.isDirectory?.()) return;
        for (const entry of await readdir(path, { withFileTypes: true })) {
            const name = typeof entry === 'string' ? entry : entry.name;
            assertSafeLeafName(name);
            await inspectRecursively(paths.join(path, name));
        }
    }

    await inspectRecursively(requestedRunDir);

    return requestedRunDir;
}

// Returns an OS-issued process start identity without invoking a command shell.
export async function lookupProcessFingerprint(processId, {
    platform = process.platform,
    execFile = execFileAsync,
    readProcessFile = readFile,
} = {}) {
    if (!Number.isInteger(processId) || processId <= 0) return null;

    try {
        if (platform === 'win32') {
            const command = `(Get-Process -Id ${processId} -ErrorAction Stop)`
                + '.StartTime.ToUniversalTime().Ticks';
            const { stdout } = await execFile('powershell.exe', [
                '-NoProfile',
                '-NonInteractive',
                '-WindowStyle',
                'Hidden',
                '-Command',
                command,
            ], { encoding: 'utf8', windowsHide: true });
            const startTicks = stdout.trim();
            return /^\d+$/.test(startTicks) ? `win32:${startTicks}` : null;
        }

        if (platform === 'linux') {
            const stat = await readProcessFile(`/proc/${processId}/stat`, 'utf8');
            const commandEnd = stat.lastIndexOf(')');
            const fieldsAfterCommand = stat.slice(commandEnd + 2).trim().split(/\s+/);
            const startTicks = fieldsAfterCommand[19];
            return /^\d+$/.test(startTicks) ? `linux:${startTicks}` : null;
        }

        const { stdout } = await execFile('ps', [
            '-o', 'lstart=', '-p', String(processId),
        ], { encoding: 'utf8', windowsHide: true });
        const startIdentity = stdout.trim();
        return startIdentity ? `${platform}:${startIdentity}` : null;
    } catch {
        return null;
    }
}

export class ControlFile {
    constructor(path, {
        isLiveProcess: liveProcessCheck = isLiveProcess,
        processIdentity = randomUUID(),
        processFingerprintLookup = lookupProcessFingerprint,
    } = {}) {
        this.path = path;
        this.rootDir = dirname(path);
        this.lockPath = `${path}.lock`;
        this.isLiveProcess = liveProcessCheck;
        this.processIdentity = processIdentity;
        this.processFingerprintLookup = processFingerprintLookup;
        this.ownProcessFingerprint = null;
    }

    async read() {
        try {
            return JSON.parse(await readSafeFile(this.rootDir, this.path, 'utf8'));
        } catch (error) {
            if (error?.code === 'ENOENT') return { pause: false, stop: false };
            throw error;
        }
    }

    async write(value) {
        await this.#withLock(async () => {
            const current = await this.read();
            await this.#writeUnlocked(this.#merge(current, value));
        });
    }

    async update(changes) {
        await this.write(changes);
    }

    async exclusive(operation) {
        return this.#withLock(async () => operation(await this.read()));
    }

    // Distinguishes the recorded process instance from a later process that reused its PID.
    async ownerStatus(value = null) {
        const current = value || await this.read();
        if (!current.ownerPid) return 'none';
        return this.#processRecordStatus(current.ownerPid, current.ownerProcessFingerprint);
    }

    async claim(ownerPid, { resume = false } = {}) {
        const ownerProcessFingerprint = await this.#requiredProcessFingerprint(
            ownerPid,
            'collector owner',
        );
        await this.#withLock(async () => {
            const current = await this.read();
            if (current.stop) throw new Error('A stopped run cannot start or resume');
            const sameOwner = current.ownerPid === ownerPid
                && current.ownerIdentity === this.processIdentity
                && current.ownerProcessFingerprint === ownerProcessFingerprint;
            if (current.ownerPid && !sameOwner) {
                const status = await this.ownerStatus(current);
                if (status === 'live') {
                    throw new Error(`Collector is already running with PID ${current.ownerPid}`);
                }
                if (status === 'unverified') {
                    throw new Error(
                        `Cannot verify collector process instance for PID ${current.ownerPid}; refusing ownership change`,
                    );
                }
            }
            if (current.pause && !resume) throw new Error('Run is paused; use the resume command');
            await this.#writeUnlocked({
                ...current,
                pause: resume ? false : Boolean(current.pause),
                stop: false,
                ownerPid,
                ownerIdentity: this.processIdentity,
                ownerProcessFingerprint,
            });
        });
    }

    async release(ownerPid) {
        await this.#withLock(async () => {
            const current = await this.read();
            if (current.ownerPid !== ownerPid || current.ownerIdentity !== this.processIdentity) return;
            const ownerProcessFingerprint = await this.#lookupProcessFingerprint(ownerPid);
            if (!ownerProcessFingerprint
                || current.ownerProcessFingerprint !== ownerProcessFingerprint) return;
            await this.#writeUnlocked({
                ...current,
                ownerPid: null,
                ownerIdentity: null,
                ownerProcessFingerprint: null,
            });
        });
    }

    async #lookupProcessFingerprint(processId) {
        try {
            const fingerprint = await this.processFingerprintLookup(processId);
            return typeof fingerprint === 'string' && fingerprint ? fingerprint : null;
        } catch {
            return null;
        }
    }

    async #requiredProcessFingerprint(processId, label) {
        const fingerprint = processId === process.pid
            ? await this.#ownProcessFingerprint()
            : await this.#lookupProcessFingerprint(processId);
        if (!fingerprint) {
            throw new Error(`Cannot verify ${label} process instance for PID ${processId}`);
        }
        return fingerprint;
    }

    async #ownProcessFingerprint() {
        if (!this.ownProcessFingerprint) {
            this.ownProcessFingerprint = this.#lookupProcessFingerprint(process.pid);
        }
        return this.ownProcessFingerprint;
    }

    async #processRecordStatus(processId, recordedFingerprint) {
        if (!this.isLiveProcess(processId)) return 'stale';
        if (typeof recordedFingerprint !== 'string' || !recordedFingerprint) return 'unverified';
        const currentFingerprint = await this.#lookupProcessFingerprint(processId);
        if (!currentFingerprint) return 'unverified';
        return currentFingerprint === recordedFingerprint ? 'live' : 'stale';
    }

    #merge(current, changes) {
        const stop = Boolean(current.stop || changes.stop);
        return {
            ...current,
            ...changes,
            pause: stop ? false : Boolean(changes.pause ?? current.pause),
            stop,
        };
    }

    async #writeUnlocked(value) {
        await writeFileAtomically(
            this.rootDir,
            this.path,
            `${JSON.stringify(value, null, 2)}\n`,
            'utf8',
        );
    }

    async #withLock(operation) {
        for (let attempt = 0; attempt < 200; attempt += 1) {
            const token = await this.#tryClaimLock();
            if (token) {
                try {
                    return await operation();
                } finally {
                    await this.#releaseLock(token);
                }
            }

            const owner = await this.#readLockOwner();
            if (!owner) continue;
            const ownerStatus = await this.#processRecordStatus(owner.pid, owner.processFingerprint);
            if (ownerStatus === 'stale') {
                await this.#quarantineStaleLock(owner);
                continue;
            }
            if (ownerStatus === 'unverified') {
                throw new Error(
                    `Cannot verify control lock process instance for PID ${owner.pid}; refusing lock recovery`,
                );
            }
            await sleep(5);
        }

        throw new Error('Timed out waiting for control file lock');
    }

    async #tryClaimLock() {
        await assertSafeDirectoryPath(this.rootDir, this.rootDir);
        const token = randomUUID();
        const processFingerprint = await this.#requiredProcessFingerprint(
            process.pid,
            'control lock owner',
        );
        const claimPath = `${this.lockPath}.claim.${process.pid}.${token}`;
        await writeFile(claimPath, `${JSON.stringify({
            pid: process.pid,
            token,
            processFingerprint,
        })}\n`, { encoding: 'utf8', flag: 'wx' });
        try {
            try {
                await fsLink(claimPath, this.lockPath);
                return token;
            } catch (error) {
                if (error?.code !== 'EEXIST') throw error;
                return null;
            }
        } finally {
            await unlink(claimPath).catch((error) => {
                if (error?.code !== 'ENOENT') throw error;
            });
        }
    }

    async #readLockOwner() {
        try {
            await assertSafeDirectoryPath(this.rootDir, this.rootDir);
            const lockStats = await fsLstat(this.lockPath);
            if (!lockStats.isFile() || lockStats.isSymbolicLink()) {
                throw new Error('lock is not a regular file');
            }
            const owner = JSON.parse(await readFile(this.lockPath, 'utf8'));
            if (!Number.isInteger(owner?.pid) || owner.pid <= 0
                || typeof owner?.token !== 'string' || !/^[a-z0-9-]+$/i.test(owner.token)
                || typeof owner?.processFingerprint !== 'string' || !owner.processFingerprint) {
                throw new Error('invalid owner metadata');
            }
            return owner;
        } catch (error) {
            if (error?.code === 'ENOENT') return null;
            throw new Error(
                `Incomplete control lock at ${this.lockPath}; verify no collector is running and remove manually`,
                { cause: error },
            );
        }
    }

    async #quarantineStaleLock(owner) {
        const reclaimPath = `${this.lockPath}.reclaim`;
        const reclaimToken = randomUUID();
        const processFingerprint = await this.#requiredProcessFingerprint(
            process.pid,
            'stale-lock reclaimer',
        );
        try {
            await writeFile(reclaimPath, `${JSON.stringify({
                pid: process.pid,
                token: reclaimToken,
                identity: this.processIdentity,
                processFingerprint,
            })}\n`, { encoding: 'utf8', flag: 'wx' });
        } catch (error) {
            if (error?.code !== 'EEXIST') throw error;
            let recoveryOwner;
            try {
                recoveryOwner = JSON.parse(await readFile(reclaimPath, 'utf8'));
            } catch (readError) {
                throw new Error(
                    `Incomplete stale-lock recovery at ${reclaimPath}; remove it manually after verifying no collector is running`,
                    { cause: readError },
                );
            }
            if (!Number.isInteger(recoveryOwner?.pid) || recoveryOwner.pid <= 0
                || typeof recoveryOwner?.token !== 'string'
                || !/^[a-z0-9-]+$/i.test(recoveryOwner.token)
                || typeof recoveryOwner?.identity !== 'string' || !recoveryOwner.identity
                || typeof recoveryOwner?.processFingerprint !== 'string'
                || !recoveryOwner.processFingerprint) {
                throw new Error(
                    `Incomplete stale-lock recovery at ${reclaimPath}; remove it manually after verifying no collector is running`,
                );
            }
            const recoveryStatus = await this.#processRecordStatus(
                recoveryOwner.pid,
                recoveryOwner.processFingerprint,
            );
            if (recoveryStatus === 'stale') {
                const currentText = await readFile(reclaimPath, 'utf8');
                const currentOwner = JSON.parse(currentText);
                if (currentOwner.pid !== recoveryOwner.pid
                    || currentOwner.token !== recoveryOwner.token
                    || currentOwner.identity !== recoveryOwner.identity
                    || currentOwner.processFingerprint !== recoveryOwner.processFingerprint) {
                    return false;
                }
                let abandonedPath = `${reclaimPath}.stale.${recoveryOwner.token}`;
                try {
                    await fsLstat(abandonedPath);
                    abandonedPath = `${abandonedPath}.${randomUUID()}`;
                } catch (statError) {
                    if (statError?.code !== 'ENOENT') throw statError;
                }
                await rename(reclaimPath, abandonedPath);
                return false;
            }
            if (recoveryStatus === 'unverified') {
                throw new Error(
                    `Cannot verify stale-lock reclaimer process instance for PID ${recoveryOwner.pid}`,
                );
            }
            return false;
        }

        try {
            const currentOwner = await this.#readLockOwner();
            if (!currentOwner || currentOwner.pid !== owner.pid || currentOwner.token !== owner.token
                || currentOwner.processFingerprint !== owner.processFingerprint) {
                return false;
            }
            const staleBase = `${this.lockPath}.stale.${owner.token}`;
            let stalePath = staleBase;
            try {
                await fsLstat(staleBase);
                stalePath = `${staleBase}.${randomUUID()}`;
            } catch (error) {
                if (error?.code !== 'ENOENT') throw error;
            }
            await rename(this.lockPath, stalePath);
            return true;
        } catch (error) {
            if (error?.code === 'ENOENT') return false;
            throw error;
        } finally {
            await unlink(reclaimPath).catch((error) => {
                if (error?.code !== 'ENOENT') throw error;
            });
        }
    }

    async #releaseLock(token) {
        const owner = await this.#readLockOwner();
        const processFingerprint = await this.#requiredProcessFingerprint(
            process.pid,
            'control lock owner',
        );
        if (!owner || owner.pid !== process.pid || owner.token !== token
            || owner.processFingerprint !== processFingerprint) {
            throw new Error('Control lock ownership changed before release');
        }
        await unlink(this.lockPath);
    }
}

async function loadSources() {
    const sources = JSON.parse(await readFile(
        new URL('../../config/rimskie-import-sources.json', import.meta.url),
        'utf8',
    ));

    return sources
        .filter(({ enabled }) => enabled)
        .sort((left, right) => left.sort_order - right.sort_order)
        .map((source) => ({
            label: source.label,
            sourceSlug: source.target_slug,
            sourceUrl: source.source_url,
            nextPageUrl: source.source_url,
            enabled: source.enabled,
            sortOrder: source.sort_order,
            pendingProducts: [],
            completed: false,
            pages: 0,
        }));
}

function serializedLimit(value) {
    return Number.isFinite(value) ? value : null;
}

function runtimeLimit(value) {
    return value === null ? Number.POSITIVE_INFINITY : value;
}

function stateSourcesMatchConfig(stateSources, configSources) {
    const immutableFields = ['sourceUrl', 'sourceSlug', 'label', 'enabled', 'sortOrder'];
    return Array.isArray(stateSources) && stateSources.length === configSources.length
        && stateSources.every((source, index) => immutableFields.every(
            (field) => source?.[field] === configSources[index][field],
        ));
}

function usesCurrentSafeRequestProfile(config) {
    const limits = config?.limits;
    return Array.isArray(limits?.html_delay_ms)
        && limits.html_delay_ms[0] >= DEFAULT_LIMITS.htmlDelayMs[0]
        && Array.isArray(limits.image_delay_ms)
        && limits.image_delay_ms[0] >= DEFAULT_LIMITS.imageDelayMs[0]
        && Array.isArray(limits.challenge_delay_ms)
        && limits.challenge_delay_ms[0] >= DEFAULT_LIMITS.challengeDelayMs[0]
        && Number.isInteger(limits.hourly_requests)
        && limits.hourly_requests <= DEFAULT_LIMITS.hourlyLimit
        && limits.concurrency === DEFAULT_LIMITS.concurrency;
}

export function savedPolicyOptions(config) {
    return {
        htmlDelayMs: config.limits.html_delay_ms,
        imageDelayMs: config.limits.image_delay_ms,
        challengeDelayMs: config.limits.challenge_delay_ms,
        hourlyLimit: config.limits.hourly_requests,
        backoffMs: config.limits.backoff_ms,
    };
}

export async function initializeRun(store, options) {
    const existingState = await store.readState();
    let config;
    try {
        config = await store.readConfig();
    } catch (error) {
        if (error?.code !== 'ENOENT') throw error;
        if (existingState) {
            throw new Error('Existing run is missing immutable config.json and cannot be resumed safely');
        }
        const sources = await loadSources();
        config = {
            schema_version: configSchemaVersion,
            sources,
            limits: {
                html_delay_ms: DEFAULT_LIMITS.htmlDelayMs,
                image_delay_ms: DEFAULT_LIMITS.imageDelayMs,
                challenge_delay_ms: DEFAULT_LIMITS.challengeDelayMs,
                hourly_requests: DEFAULT_LIMITS.hourlyLimit,
                backoff_ms: DEFAULT_LIMITS.backoffMs,
                concurrency: DEFAULT_LIMITS.concurrency,
                max_requests: serializedLimit(options.maxRequests),
                max_products: serializedLimit(options.maxProducts),
            },
        };
        await store.initializeConfig(config);
    }

    const configuredMaxRequests = runtimeLimit(config.limits.max_requests);
    const configuredMaxProducts = runtimeLimit(config.limits.max_products);
    const digest = configDigest(config);
    if (options.maxRequestsExplicit && options.maxRequests !== configuredMaxRequests) {
        throw new Error('Run max-request limit is immutable and differs from config.json');
    }
    if (options.maxProductsExplicit && options.maxProducts !== configuredMaxProducts) {
        throw new Error('Run max-product limit is immutable and differs from config.json');
    }

    if (existingState) {
        if (existingState.configDigest !== digest) {
            throw new Error('Existing run state config digest does not match immutable config.json');
        }
        if (!stateSourcesMatchConfig(existingState.sources, config.sources)) {
            throw new Error('State sources differ from immutable config.json');
        }
        if (!usesCurrentSafeRequestProfile(config)) {
            throw new Error(
                'Existing run uses an obsolete aggressive request profile; create a new run ID',
            );
        }
        return {
            state: existingState,
            config,
            configDigest: digest,
            maxRequests: configuredMaxRequests,
            maxProducts: configuredMaxProducts,
        };
    }
    if (!usesCurrentSafeRequestProfile(config)) {
        throw new Error(
            'Existing run uses an obsolete aggressive request profile; create a new run ID',
        );
    }
    const state = {
        status: 'ready',
        requestCount: 0,
        completedProductIds: [],
        sources: structuredClone(config.sources),
        configDigest: digest,
    };
    await store.checkpoint(state);

    return {
        state,
        config,
        configDigest: digest,
        maxRequests: configuredMaxRequests,
        maxProducts: configuredMaxProducts,
    };
}

export function isLiveProcess(processId, kill = process.kill) {
    if (!Number.isInteger(processId) || processId <= 0) return false;
    try {
        kill(processId, 0);
        return true;
    } catch (error) {
        return error?.code === 'EPERM';
    }
}

function printableSnapshot(state, control) {
    return {
        ...state,
        uniqueProducts: state?.completedProductIds?.length || 0,
        control,
    };
}

async function print(value, json = false) {
    if (json || typeof value !== 'string') {
        process.stdout.write(`${JSON.stringify(value, null, 2)}\n`);
        return;
    }
    process.stdout.write(`${value}\n`);
}

export async function runCollector(options, store, control, dependencies = {}) {
    const [
        collectorModule,
        transportModule,
        policyModule,
    ] = await Promise.all([
        import('./lib/collector.mjs'),
        import('./lib/playwright-transport.mjs'),
        import('./lib/request-policy.mjs'),
    ]);
    const Collector = dependencies.Collector || collectorModule.Collector;
    const PlaywrightTransport = dependencies.PlaywrightTransport || transportModule.PlaywrightTransport;
    const RequestPolicy = dependencies.RequestPolicy || policyModule.RequestPolicy;
    const currentControl = await control.read();
    if (options.command === 'resume' && currentControl.stop) {
        throw new Error('A stopped run cannot be resumed');
    }
    if (options.command === 'resume' && currentControl.ownerPid !== process.pid) {
        const ownerStatus = await control.ownerStatus(currentControl);
        if (ownerStatus === 'live') {
            await control.update({ pause: false });
            return { status: 'resume-signaled', ownerPid: currentControl.ownerPid };
        }
        if (ownerStatus === 'unverified') {
            throw new Error(
                `Cannot verify collector process instance for PID ${currentControl.ownerPid}; refusing resume signal`,
            );
        }
    }

    await control.claim(process.pid, { resume: options.command === 'resume' });
    let transport;
    let result;

    try {
        const initialized = await initializeRun(store, options);
        const { state } = initialized;
        if (state.status === 'stopped') throw new Error('A stopped run cannot be resumed');
        if (state.status === 'completed') {
            return printableSnapshot(state, await control.read());
        }
        transport = await PlaywrightTransport.open({
            profileDir: join(store.runDir, 'profile'),
            headed: true,
            executablePath: options.chrome,
        });
        const policy = new RequestPolicy({
            ...savedPolicyOptions(initialized.config),
            onEvent: (event) => store.appendEvent(event),
        });
        const collector = new Collector();
        result = await collector.run({
            store,
            transport,
            policy,
            control,
            maxRequests: initialized.maxRequests,
            maxProducts: initialized.maxProducts,
            acknowledgeFailurePause: options.command === 'resume',
            acknowledgeChallenge: options.command === 'resume',
            acknowledgeError: options.command === 'resume',
        });
    } finally {
        try {
            await transport?.close();
        } finally {
            await control.release(process.pid);
        }
    }

    return result;
}

export async function main(argv = process.argv.slice(2)) {
    const options = parseArguments(argv);
    const mayCreateRun = options.command === 'start' || options.command === 'dry-run';
    const dataRoot = await resolveDataRoot(options.dataRoot, { create: mayCreateRun });
    await assertRunDirectorySafe(dataRoot, options.runId);
    const store = mayCreateRun
        ? await RunStore.open({ rootDir: dataRoot, runId: options.runId })
        : await RunStore.openExisting({ rootDir: dataRoot, runId: options.runId });
    if (!mayCreateRun) await store.requireInitialized();
    await assertRunDirectorySafe(dataRoot, options.runId);
    const control = new ControlFile(join(store.runDir, 'control.json'));

    if (options.command === 'status') {
        await print(printableSnapshot(await store.readState(), await control.read()), options.json);
        return;
    }
    if (options.command === 'pause') {
        await control.update({ pause: true });
        await print(`Pause requested for ${options.runId}`);
        return;
    }
    if (options.command === 'stop') {
        await control.update({ pause: false, stop: true });
        await print(`Stop requested for ${options.runId}`);
        return;
    }
    if (options.command === 'export') {
        await control.exclusive(async (currentControl) => {
            const ownerStatus = await control.ownerStatus(currentControl);
            if (ownerStatus === 'live') {
                throw new Error(`Cannot export while collector PID ${currentControl.ownerPid} is running`);
            }
            if (ownerStatus === 'unverified') {
                throw new Error(
                    `Cannot verify collector process instance for PID ${currentControl.ownerPid}; refusing export`,
                );
            }
            await store.exportManifest();
        });
        await print(store.exportPath);
        return;
    }

    const result = await runCollector(options, store, control);
    await print(result, options.json);
}

const entryPath = process.argv[1] ? fileURLToPath(import.meta.url) : null;
if (entryPath && basename(process.argv[1]) === basename(entryPath)
    && process.argv[1].replaceAll('\\', '/').toLowerCase() === entryPath.replaceAll('\\', '/').toLowerCase()) {
    main().catch((error) => {
        process.stderr.write(`${error.message}\n`);
        process.exitCode = 1;
    });
}

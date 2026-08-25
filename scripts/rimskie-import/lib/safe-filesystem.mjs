import { randomUUID } from 'node:crypto';
import {
    lstat,
    readFile,
    realpath,
    rename,
    rm,
    writeFile,
} from 'node:fs/promises';
import { dirname, isAbsolute, relative, resolve, sep } from 'node:path';

const windowsReservedName = /^(?:con|prn|aux|nul|com[1-9]|lpt[1-9])(?:\..*)?$/i;

export function assertSafeIdentifier(value, label, {
    pattern = /^[a-z0-9][a-z0-9._-]{0,127}$/i,
} = {}) {
    if (typeof value !== 'string' || !pattern.test(value)
        || value === '.' || value === '..' || value.includes('..')
        || value.includes(':') || /[. ]$/.test(value) || windowsReservedName.test(value)) {
        throw new Error(`${label} is not a safe path identifier`);
    }

    return value;
}

export function assertNumericExternalId(value) {
    return assertSafeIdentifier(value, 'product external ID', { pattern: /^\d{1,32}$/ });
}

export function assertContainedPath(rootDir, targetPath, label = 'path') {
    const relativePath = relative(resolve(rootDir), resolve(targetPath));
    if (isAbsolute(relativePath) || relativePath === '..' || relativePath.startsWith(`..${sep}`)) {
        throw new Error(`${label} must stay inside the run directory`);
    }

    return resolve(targetPath);
}

async function readStatsIfPresent(path) {
    try {
        return await lstat(path);
    } catch (error) {
        if (error?.code === 'ENOENT') return null;
        throw error;
    }
}

async function assertExistingNodeSafe(path, { type, rootDir }) {
    const stats = await readStatsIfPresent(path);
    if (!stats) return null;
    if (stats.isSymbolicLink()) throw new Error(`Unsafe symbolic link or junction at ${path}`);
    if (stats.nlink > 1 && stats.isFile()) throw new Error(`Unsafe hard-linked file at ${path}`);
    if (type === 'directory' && !stats.isDirectory()) throw new Error(`Expected directory at ${path}`);
    if (type === 'file' && !stats.isFile()) throw new Error(`Expected regular file at ${path}`);

    const resolvedPath = await realpath(path);
    assertContainedPath(rootDir, resolvedPath, 'resolved path');
    return stats;
}

export async function assertSafeFileTarget(rootDir, targetPath) {
    const target = assertContainedPath(rootDir, targetPath, 'file path');
    const root = resolve(rootDir);
    const parts = relative(root, dirname(target)).split(sep).filter(Boolean);
    let current = root;

    await assertExistingNodeSafe(current, { type: 'directory', rootDir: root });
    for (const part of parts) {
        assertSafeIdentifier(part, 'directory name');
        current = resolve(current, part);
        const stats = await assertExistingNodeSafe(current, { type: 'directory', rootDir: root });
        if (!stats) throw new Error(`Missing writable directory: ${current}`);
    }
    await assertExistingNodeSafe(target, { type: 'file', rootDir: root });

    return target;
}

export async function assertSafeDirectoryPath(rootDir, directoryPath) {
    const directory = assertContainedPath(rootDir, directoryPath, 'directory path');
    const root = resolve(rootDir);
    const parts = relative(root, directory).split(sep).filter(Boolean);
    let current = root;

    const rootStats = await assertExistingNodeSafe(current, { type: 'directory', rootDir: root });
    if (!rootStats) throw new Error(`Missing run directory: ${root}`);
    for (const part of parts) {
        assertSafeIdentifier(part, 'directory name');
        current = resolve(current, part);
        const stats = await assertExistingNodeSafe(current, { type: 'directory', rootDir: root });
        if (!stats) throw new Error(`Missing run directory: ${current}`);
    }

    return directory;
}

export async function readSafeFile(rootDir, path, encoding) {
    await assertSafeFileTarget(rootDir, path);
    return readFile(path, encoding);
}

export async function writeFileAtomically(rootDir, path, data, encoding) {
    const target = await assertSafeFileTarget(rootDir, path);
    const temporaryPath = `${target}.${randomUUID()}.tmp`;
    assertContainedPath(rootDir, temporaryPath, 'temporary file path');
    let created = false;
    try {
        await writeFile(temporaryPath, data, { encoding, flag: 'wx' });
        created = true;
        const temporaryStats = await lstat(temporaryPath);
        if (!temporaryStats.isFile() || temporaryStats.isSymbolicLink() || temporaryStats.nlink !== 1) {
            throw new Error(`Unsafe temporary file at ${temporaryPath}`);
        }
        await assertSafeFileTarget(rootDir, target);
        await rename(temporaryPath, target);
        created = false;
    } finally {
        if (created) await rm(temporaryPath, { force: true }).catch(() => {});
    }
}

export async function appendFileAtomically(rootDir, path, line) {
    let current = '';
    try {
        current = await readSafeFile(rootDir, path, 'utf8');
    } catch (error) {
        if (error?.code !== 'ENOENT') throw error;
    }
    await writeFileAtomically(rootDir, path, `${current}${line}`, 'utf8');
}

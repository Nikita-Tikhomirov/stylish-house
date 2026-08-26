import { spawn } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { join } from 'node:path';

import { assertSafeIdentifier } from '../lib/safe-filesystem.mjs';

function runIdTimestamp(date) {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Europe/Moscow',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hourCycle: 'h23',
    }).formatToParts(date);
    const value = Object.fromEntries(parts.map((part) => [part.type, part.value]));
    return `${value.year}${value.month}${value.day}-${value.hour}${value.minute}${value.second}`;
}

function validateRunId(runId) {
    assertSafeIdentifier(runId, 'run ID', { pattern: /^[a-z0-9][a-z0-9-]{0,127}$/ });
    return runId;
}

export class CollectorSupervisor {
    constructor({
        cliPath,
        dataRoot,
        nodeExecutable = process.execPath,
        spawnProcess = spawn,
        now = () => new Date(),
        randomId = () => randomBytes(4).toString('hex'),
    }) {
        this.cliPath = cliPath;
        this.dataRoot = dataRoot;
        this.nodeExecutable = nodeExecutable;
        this.spawnProcess = spawnProcess;
        this.now = now;
        this.randomId = randomId;
        this.processes = new Map();
    }

    async start() {
        const runId = validateRunId(`run-${runIdTimestamp(this.now())}-${this.randomId()}`);
        this.#spawnCollector('start', runId);
        return { runId, command: 'start' };
    }

    async resume(runId) {
        return this.#dispatch('resume', runId);
    }

    async pause(runId) {
        return this.#dispatch('pause', runId);
    }

    async stop(runId) {
        return this.#dispatch('stop', runId);
    }

    async exportRun(runId) {
        return this.#dispatch('export', runId);
    }

    async openFolder(runId) {
        validateRunId(runId);
        const child = this.spawnProcess('explorer.exe', [join(this.dataRoot, runId)], {
            shell: false,
            windowsHide: false,
            stdio: 'ignore',
        });
        child.unref?.();
        return { runId, command: 'open-folder' };
    }

    #dispatch(command, runId) {
        validateRunId(runId);
        this.#spawnCollector(command, runId);
        return { runId, command };
    }

    #spawnCollector(command, runId) {
        const args = [
            this.cliPath,
            command,
            '--run', runId,
            '--data-root', this.dataRoot,
            '--json',
        ];
        const child = this.spawnProcess(this.nodeExecutable, args, {
            shell: false,
            windowsHide: true,
            stdio: ['ignore', 'pipe', 'pipe'],
        });
        const record = { child, command, runId, stdout: '', stderr: '', exitCode: null };
        this.processes.set(`${runId}:${command}`, record);
        child.stdout?.on?.('data', (chunk) => { record.stdout = `${record.stdout}${chunk}`.slice(-20_000); });
        child.stderr?.on?.('data', (chunk) => { record.stderr = `${record.stderr}${chunk}`.slice(-20_000); });
        child.on?.('exit', (exitCode) => { record.exitCode = exitCode; });
        child.on?.('error', (error) => { record.stderr = error.message; record.exitCode = -1; });
        return record;
    }
}

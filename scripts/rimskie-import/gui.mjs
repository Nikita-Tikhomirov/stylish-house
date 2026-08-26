#!/usr/bin/env node

import { execFile } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { fileURLToPath } from 'node:url';

import { CollectorSupervisor } from './gui/process-supervisor.mjs';
import { createGuiServer } from './gui/server.mjs';
import { createStatusService } from './gui/status-service.mjs';

const dataRoot = process.env.RIMSKIE_IMPORT_DATA_ROOT || 'G:\\stylish-house-data\\rimskie-imports';
const cliPath = fileURLToPath(new URL('./cli.mjs', import.meta.url));

async function main() {
    const statusService = await createStatusService({ dataRoot });
    const supervisor = new CollectorSupervisor({ cliPath, dataRoot: statusService.dataRoot });
    const gui = createGuiServer({
        host: '127.0.0.1', port: 43127, token: randomBytes(32).toString('hex'),
        statusService, supervisor,
    });
    const address = await gui.listen();
    process.stdout.write(`Локальная панель запущена: ${address.url}\n`);
    process.stdout.write(`Данные: ${statusService.dataRoot}\n`);
    execFile('rundll32.exe', ['url.dll,FileProtocolHandler', address.url], { windowsHide: true }, () => {});

    const close = async () => { await gui.close(); process.exit(0); };
    process.once('SIGINT', close);
    process.once('SIGTERM', close);
}

main().catch((error) => {
    const message = error?.code === 'EADDRINUSE'
        ? 'Порт 43127 уже занят. Закройте предыдущую панель и запустите снова.'
        : error.message;
    process.stderr.write(`${message}\n`);
    process.exitCode = 1;
});

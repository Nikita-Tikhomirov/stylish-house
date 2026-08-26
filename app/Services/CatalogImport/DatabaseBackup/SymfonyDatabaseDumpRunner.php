<?php

namespace App\Services\CatalogImport\DatabaseBackup;

use App\Data\CatalogImport\DatabaseBackupInvocation;
use Symfony\Component\Process\Process;
use Throwable;

final class SymfonyDatabaseDumpRunner implements DatabaseDumpRunner
{
    private readonly DatabaseDumpProcessFactory $processFactory;

    public function __construct(?DatabaseDumpProcessFactory $processFactory = null)
    {
        $this->processFactory = $processFactory ?? new SymfonyDatabaseDumpProcessFactory;
    }

    public function run(DatabaseBackupInvocation $invocation): void
    {
        if ($invocation->command === [] || $invocation->timeoutSeconds < 1) {
            throw new DatabaseBackupException('The database dump process configuration is invalid.');
        }

        foreach ($invocation->command as $argument) {
            if (! is_string($argument) || str_contains($argument, "\0")) {
                throw new DatabaseBackupException('The database dump process arguments are invalid.');
            }
        }
        foreach ($invocation->environment as $name => $value) {
            if (! is_string($name) || ! is_string($value) || str_contains($name.$value, "\0")) {
                throw new DatabaseBackupException('The database dump process environment is invalid.');
            }
        }

        $expectedIdentity = $this->regularSingleLinkIdentity($invocation->outputPath);
        if ($expectedIdentity === null
            || $invocation->outputDevice === null
            || $invocation->outputInode === null
            || $expectedIdentity['dev'] !== $invocation->outputDevice
            || $expectedIdentity['ino'] !== $invocation->outputInode) {
            throw new DatabaseBackupException('The database dump output was not exclusively preclaimed.');
        }

        $output = @fopen($invocation->outputPath, 'r+b');
        if ($output === false) {
            throw new DatabaseBackupException('The preclaimed database dump output cannot be opened.');
        }

        try {
            if (! $this->handleMatchesIdentity($output, $expectedIdentity)
                || $this->regularSingleLinkIdentity($invocation->outputPath) !== $expectedIdentity) {
                throw new DatabaseBackupException('The preclaimed database dump output identity changed.');
            }
            if (! ftruncate($output, 0) || fseek($output, 0) !== 0) {
                throw new DatabaseBackupException('The preclaimed database dump output cannot be initialized.');
            }

            $process = $this->processFactory->create($invocation);
            $exitCode = $process->run(function (string $type, string $buffer) use ($output, $process): void {
                // Symfony stores each callback chunk before invoking us. Clear both
                // buffers immediately so large dumps and secret-bearing stderr stay bounded.
                $process->clearOutput();
                $process->clearErrorOutput();

                if ($type !== Process::OUT) {
                    return;
                }

                $offset = 0;
                $length = strlen($buffer);
                while ($offset < $length) {
                    $written = fwrite($output, substr($buffer, $offset));
                    if ($written === false || $written === 0) {
                        throw new DatabaseBackupException('The database dump output cannot be written.');
                    }
                    $offset += $written;
                }
            });
            $process->clearOutput();
            $process->clearErrorOutput();

            if (! fflush($output)) {
                throw new DatabaseBackupException('The database dump output cannot be flushed.');
            }
            if (! $this->handleMatchesIdentity($output, $expectedIdentity)
                || $this->regularSingleLinkIdentity($invocation->outputPath) !== $expectedIdentity) {
                throw new DatabaseBackupException('The database dump output identity changed during execution.');
            }
        } catch (Throwable) {
            throw new DatabaseBackupException('The database dump process could not be completed.');
        } finally {
            fclose($output);
        }

        if ($exitCode !== 0) {
            throw new DatabaseBackupException('The database dump process failed with exit code '.(int) $exitCode.'.');
        }
    }

    /** @return array{dev: int, ino: int}|null */
    private function regularSingleLinkIdentity(string $path): ?array
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        if (! is_array($stat)
            || is_link($path)
            || ! isset($stat['mode'], $stat['dev'], $stat['ino'], $stat['nlink'])
            || ($stat['mode'] & 0170000) !== 0100000
            || $stat['nlink'] !== 1
            || ! is_int($stat['dev'])
            || ! is_int($stat['ino'])) {
            return null;
        }

        return ['dev' => $stat['dev'], 'ino' => $stat['ino']];
    }

    /**
     * @param  resource  $handle
     * @param  array{dev: int, ino: int}  $identity
     */
    private function handleMatchesIdentity($handle, array $identity): bool
    {
        $stat = @fstat($handle);

        return is_array($stat)
            && isset($stat['mode'], $stat['dev'], $stat['ino'], $stat['nlink'])
            && ($stat['mode'] & 0170000) === 0100000
            && $stat['nlink'] === 1
            && $stat['dev'] === $identity['dev']
            && $stat['ino'] === $identity['ino'];
    }
}

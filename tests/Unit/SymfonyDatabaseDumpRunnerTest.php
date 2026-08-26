<?php

namespace Tests\Unit;

use App\Data\CatalogImport\DatabaseBackupInvocation;
use App\Services\CatalogImport\DatabaseBackup\DatabaseBackupException;
use App\Services\CatalogImport\DatabaseBackup\DatabaseDumpProcessFactory;
use App\Services\CatalogImport\DatabaseBackup\SymfonyDatabaseDumpRunner;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class SymfonyDatabaseDumpRunnerTest extends TestCase
{
    private string $sandbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sandbox = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dump-runner-test-'.bin2hex(random_bytes(8));
        mkdir($this->sandbox, 0700, true);
    }

    protected function tearDown(): void
    {
        if (str_starts_with(basename($this->sandbox), 'dump-runner-test-')) {
            (new Filesystem)->deleteDirectory($this->sandbox);
        }

        parent::tearDown();
    }

    public function test_it_executes_an_argument_array_with_child_only_password_environment_and_no_shell_interpolation(): void
    {
        $capturePath = $this->sandbox.DIRECTORY_SEPARATOR.'capture.json';
        $shellSentinel = $this->sandbox.DIRECTORY_SEPARATOR.'must-not-exist.txt';
        $argument = '; file_put_contents('.var_export($shellSentinel, true).', "executed");';
        $script = <<<'PHP'
$capture = [
    'password' => getenv('MYSQL_PWD'),
    'argument' => $argv[2],
];
file_put_contents($argv[1], json_encode($capture, JSON_THROW_ON_ERROR));
fwrite(STDOUT, "CREATE TABLE `streamed` (`id` int);\n");
PHP;

        $outputPath = $this->sandbox.DIRECTORY_SEPARATOR.'preclaimed.sql.tmp';
        touch($outputPath);
        $outputStat = stat($outputPath);
        $this->assertIsArray($outputStat);

        (new SymfonyDatabaseDumpRunner)->run(new DatabaseBackupInvocation(
            command: [PHP_BINARY, '-r', $script, $capturePath, $argument],
            environment: ['MYSQL_PWD' => 'runner-secret'],
            outputPath: $outputPath,
            timeoutSeconds: 10,
            outputDevice: $outputStat['dev'],
            outputInode: $outputStat['ino'],
        ));

        $capture = json_decode((string) file_get_contents($capturePath), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('runner-secret', $capture['password']);
        $this->assertSame($argument, $capture['argument']);
        $this->assertFileDoesNotExist($shellSentinel);
        $this->assertSame("CREATE TABLE `streamed` (`id` int);\n", file_get_contents($outputPath));
    }

    public function test_process_failure_never_exposes_child_output_command_or_password(): void
    {
        $secret = 'runner-super-secret';
        $username = 'backup_operator';
        $script = 'fwrite(STDERR, getenv("MYSQL_PWD")." '.$username.'"); exit(7);';
        $outputPath = $this->sandbox.DIRECTORY_SEPARATOR.'preclaimed-failure.sql.tmp';
        touch($outputPath);
        $outputStat = stat($outputPath);
        $this->assertIsArray($outputStat);

        try {
            (new SymfonyDatabaseDumpRunner)->run(new DatabaseBackupInvocation(
                command: [PHP_BINARY, '-r', $script],
                environment: ['MYSQL_PWD' => $secret],
                outputPath: $outputPath,
                timeoutSeconds: 10,
                outputDevice: $outputStat['dev'],
                outputInode: $outputStat['ino'],
            ));
            $this->fail('A non-zero dump process exit must fail the backup.');
        } catch (DatabaseBackupException $exception) {
            $this->assertStringNotContainsString($secret, $exception->getMessage());
            $this->assertStringNotContainsString($username, $exception->getMessage());
            $this->assertStringNotContainsString($script, $exception->getMessage());
            $this->assertStringContainsString('7', $exception->getMessage());
        }
    }

    public function test_it_rejects_a_preclaimed_output_replaced_by_an_external_hardlink_before_open(): void
    {
        $outputPath = $this->sandbox.DIRECTORY_SEPARATOR.'claimed.sql.tmp';
        $sentinel = $this->sandbox.DIRECTORY_SEPARATOR.'external-sentinel.sql';
        touch($outputPath);
        file_put_contents($sentinel, "CREATE TABLE `foreign` (`id` int);\n");
        $claimedStat = stat($outputPath);
        $this->assertIsArray($claimedStat);
        unlink($outputPath);
        $this->assertTrue(link($sentinel, $outputPath));

        try {
            (new SymfonyDatabaseDumpRunner)->run(new DatabaseBackupInvocation(
                command: [PHP_BINARY, '-r', 'fwrite(STDOUT, "CREATE TABLE `owned` (`id` int);\\n");'],
                environment: ['MYSQL_PWD' => 'runner-secret'],
                outputPath: $outputPath,
                timeoutSeconds: 10,
                outputDevice: $claimedStat['dev'],
                outputInode: $claimedStat['ino'],
            ));
            $this->fail('A replaced preclaimed output must be rejected before the child process starts.');
        } catch (DatabaseBackupException) {
            $this->assertSame("CREATE TABLE `foreign` (`id` int);\n", file_get_contents($sentinel));
            $this->assertSame("CREATE TABLE `foreign` (`id` int);\n", file_get_contents($outputPath));
        }
    }

    public function test_many_stdout_and_stderr_chunks_are_streamed_without_retaining_process_buffers(): void
    {
        $outputPath = $this->sandbox.DIRECTORY_SEPARATOR.'many-chunks.sql.tmp';
        touch($outputPath);
        $outputStat = stat($outputPath);
        $this->assertIsArray($outputStat);
        $chunkSize = 8192;
        $iterations = 256;
        $script = '$out = str_repeat("A", '.$chunkSize.'); '
            .'$err = str_repeat("runner-secret", 700); '
            .'for ($i = 0; $i < '.$iterations.'; $i++) { fwrite(STDOUT, $out); fwrite(STDERR, $err); }';
        $factory = new InspectableDatabaseDumpProcessFactory;

        (new SymfonyDatabaseDumpRunner($factory))->run(new DatabaseBackupInvocation(
            command: [PHP_BINARY, '-r', $script],
            environment: ['MYSQL_PWD' => 'runner-secret'],
            outputPath: $outputPath,
            timeoutSeconds: 20,
            outputDevice: $outputStat['dev'],
            outputInode: $outputStat['ino'],
        ));

        $this->assertSame($chunkSize * $iterations, filesize($outputPath));
        $this->assertNotNull($factory->process);
        $this->assertSame('', $factory->process->getOutput());
        $this->assertSame('', $factory->process->getErrorOutput());
        $this->assertGreaterThan(1, $factory->process->clearOutputCalls);
        $this->assertGreaterThan(1, $factory->process->clearErrorOutputCalls);
    }
}

final class InspectableDatabaseDumpProcessFactory implements DatabaseDumpProcessFactory
{
    public ?InspectableDatabaseDumpProcess $process = null;

    public function create(DatabaseBackupInvocation $invocation): Process
    {
        return $this->process = new InspectableDatabaseDumpProcess(
            command: $invocation->command,
            cwd: null,
            env: $invocation->environment,
            input: null,
            timeout: $invocation->timeoutSeconds,
        );
    }
}

final class InspectableDatabaseDumpProcess extends Process
{
    public int $clearOutputCalls = 0;

    public int $clearErrorOutputCalls = 0;

    public function clearOutput(): static
    {
        $this->clearOutputCalls++;

        return parent::clearOutput();
    }

    public function clearErrorOutput(): static
    {
        $this->clearErrorOutputCalls++;

        return parent::clearErrorOutput();
    }
}

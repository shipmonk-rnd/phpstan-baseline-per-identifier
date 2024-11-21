<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use Nette\Neon\Neon;
use PHPUnit\Framework\TestCase;
use function fclose;
use function file_put_contents;
use function mkdir;
use function proc_close;
use function proc_open;
use function stream_get_contents;
use function sys_get_temp_dir;
use function uniqid;

class BinTest extends TestCase
{

    public function testFormat(): void
    {
        $fakeRoot = sys_get_temp_dir() . '/' . uniqid('split');
        @mkdir($fakeRoot . '/baselines', 0777, true);

        $squashed = [
            'parameters' => [
                'ignoreErrors' => [
                    [
                        'message' => '#^Error simple$#',
                        'count' => 1,
                        'path' => '../app/file.php',
                        'identifier' => 'sample.identifier',
                    ],
                    [
                        'message' => '#^Error to escape \'\#$#',
                        'count' => 1,
                        'path' => '../app/config.php',
                        'identifier' => 'another.identifier',
                    ],
                    [
                        'message' => '#^Error 3$#',
                        'count' => 1,
                        'path' => '../app/index.php',
                    ],
                ],
            ],
        ];

        file_put_contents($fakeRoot . '/baselines/loader.neon', Neon::encode($squashed));

        $this->runCommand('php bin/split-phpstan-baseline ' . $fakeRoot . '/baselines/loader.neon', __DIR__ . '/..', 0);

        self::assertFileEquals(__DIR__ . '/Rule/data/baselines/loader.neon', $fakeRoot . '/baselines/loader.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines/sample.identifier.neon', $fakeRoot . '/baselines/sample.identifier.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines/another.identifier.neon', $fakeRoot . '/baselines/another.identifier.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines/missing-identifier.neon', $fakeRoot . '/baselines/missing-identifier.neon');
    }

    private function runCommand(
        string $command,
        string $cwd,
        int $expectedExitCode,
        ?string $expectedOutputContains = null,
        ?string $expectedErrorContains = null
    ): void
    {
        $desc = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];

        $procHandle = proc_open($command, $desc, $pipes, $cwd);
        self::assertNotFalse($procHandle);

        /** @var list<resource> $pipes */
        $output = stream_get_contents($pipes[1]); // @phpstan-ignore offsetAccess.notFound
        $errorOutput = stream_get_contents($pipes[2]); // @phpstan-ignore offsetAccess.notFound
        self::assertNotFalse($output);
        self::assertNotFalse($errorOutput);

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        $extraInfo = "Output was:\n" . $output . "\nError was:\n" . $errorOutput . "\n";

        $exitCode = proc_close($procHandle);
        self::assertSame(
            $expectedExitCode,
            $exitCode,
            $extraInfo,
        );

        if ($expectedOutputContains !== null) {
            self::assertStringContainsString(
                $expectedOutputContains,
                $output,
                $extraInfo,
            );
        }

        if ($expectedErrorContains !== null) {
            self::assertStringContainsString(
                $expectedErrorContains,
                $errorOutput,
                $extraInfo,
            );
        }
    }

}

<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use PHPUnit\Framework\TestCase;
use function fclose;
use function proc_close;
use function proc_open;
use function stream_get_contents;

abstract class BinTestCase extends TestCase
{

    protected function runCommand(
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

        $exitCode = proc_close($procHandle);
        self::assertSame(
            $expectedExitCode,
            $exitCode,
            $extraInfo,
        );
    }

}

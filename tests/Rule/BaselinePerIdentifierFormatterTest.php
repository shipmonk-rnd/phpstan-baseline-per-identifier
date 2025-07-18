<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;
use PHPStan\Testing\PHPStanTestCase;
use function mkdir;
use function realpath;
use function sys_get_temp_dir;
use function uniqid;

final class BaselinePerIdentifierFormatterTest extends PHPStanTestCase
{

    public function testFormat(): void
    {
        $fakeRoot = realpath(sys_get_temp_dir());
        self::assertNotFalse($fakeRoot);

        $fakeRoot = $fakeRoot . '/' . uniqid('root');
        @mkdir($fakeRoot . '/baselines', 0777, true);

        $formatter = new BaselinePerIdentifierFormatter($fakeRoot . '/baselines', '    ');

        $formatter->formatErrors(
            $this->createAnalysisResult(
                [
                    (new Error('Error simple', $fakeRoot . '/app/file.php', 1))->withIdentifier('sample.identifier'), // @phpstan-ignore phpstanApi.constructor
                    (new Error('Error simple', $fakeRoot . '/app/file.php', 5))->withIdentifier('sample.identifier'), // @phpstan-ignore phpstanApi.constructor
                    (new Error('Error to escape \'#', $fakeRoot . '/app/config.php', 2))->withIdentifier('another.identifier'), // @phpstan-ignore phpstanApi.constructor
                    (new Error('Error 3', $fakeRoot . '/app/index.php', 3)), // @phpstan-ignore phpstanApi.constructor
                ],
            ),
            $this->createOutput(),
        );

        self::assertFileEquals(__DIR__ . '/data/baselines-neon/loader.neon', $fakeRoot . '/baselines/loader.neon');
        self::assertFileEquals(__DIR__ . '/data/baselines-neon/sample.identifier.neon', $fakeRoot . '/baselines/sample.identifier.neon');
        self::assertFileEquals(__DIR__ . '/data/baselines-neon/another.identifier.neon', $fakeRoot . '/baselines/another.identifier.neon');
        self::assertFileEquals(__DIR__ . '/data/baselines-neon/missing-identifier.neon', $fakeRoot . '/baselines/missing-identifier.neon');
    }

    /**
     * @param list<Error> $errors
     */
    private function createAnalysisResult(array $errors): AnalysisResult
    {
        return new AnalysisResult($errors, [], [], [], [], false, null, false, 0, false, []); // @phpstan-ignore phpstanApi.constructor
    }

    private function createOutput(): Output
    {
        return $this->createMock(Output::class);
    }

}

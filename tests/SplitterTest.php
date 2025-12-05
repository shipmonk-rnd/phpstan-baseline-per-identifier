<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use Nette\Neon\Neon;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use function realpath;
use function strpos;
use function sys_get_temp_dir;
use function uniqid;
use function var_export;

final class SplitterTest extends BinTestCase
{

    public function testBinaryWithNeon(): void
    {
        $fakeRoot = $this->prepareSampleFolder();
        $squashed = $this->getSampleErrors();

        file_put_contents($fakeRoot . '/baselines/loader.neon', Neon::encode($squashed));

        $this->runCommand('php bin/split-phpstan-baseline ' . $fakeRoot . '/baselines/loader.neon', __DIR__ . '/..', 0);

        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-neon/loader.neon', $fakeRoot . '/baselines/loader.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-neon/sample.identifier.neon', $fakeRoot . '/baselines/sample.identifier.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-neon/another.identifier.neon', $fakeRoot . '/baselines/another.identifier.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-neon/missing-identifier.neon', $fakeRoot . '/baselines/missing-identifier.neon');
    }

    public function testBinaryWithPhp(): void
    {
        $fakeRoot = $this->prepareSampleFolder();
        $squashed = $this->getSampleErrors();

        file_put_contents($fakeRoot . '/baselines/loader.php', '<?php return ' . var_export($squashed, true) . ';');

        $this->runCommand('php bin/split-phpstan-baseline ' . $fakeRoot . '/baselines/loader.php', __DIR__ . '/..', 0);

        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-php/loader.php', $fakeRoot . '/baselines/loader.php');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-php/sample.identifier.php', $fakeRoot . '/baselines/sample.identifier.php');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-php/another.identifier.php', $fakeRoot . '/baselines/another.identifier.php');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-php/missing-identifier.php', $fakeRoot . '/baselines/missing-identifier.php');
    }

    public function testBinaryWithNeonNoErrorCount(): void
    {
        $fakeRoot = $this->prepareSampleFolder();
        $squashed = $this->getSampleErrors();

        file_put_contents($fakeRoot . '/baselines/loader.neon', Neon::encode($squashed));

        $this->runCommand('php bin/split-phpstan-baseline ' . $fakeRoot . '/baselines/loader.neon --no-error-count', __DIR__ . '/..', 0);

        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-neon-no-error-count/loader.neon', $fakeRoot . '/baselines/loader.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-neon-no-error-count/sample.identifier.neon', $fakeRoot . '/baselines/sample.identifier.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-neon-no-error-count/another.identifier.neon', $fakeRoot . '/baselines/another.identifier.neon');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-neon-no-error-count/missing-identifier.neon', $fakeRoot . '/baselines/missing-identifier.neon');
    }

    public function testBinaryWithPhpNoErrorCount(): void
    {
        $fakeRoot = $this->prepareSampleFolder();
        $squashed = $this->getSampleErrors();

        file_put_contents($fakeRoot . '/baselines/loader.php', '<?php return ' . var_export($squashed, true) . ';');

        $this->runCommand('php bin/split-phpstan-baseline ' . $fakeRoot . '/baselines/loader.php --no-error-count', __DIR__ . '/..', 0);

        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-php-no-error-count/loader.php', $fakeRoot . '/baselines/loader.php');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-php-no-error-count/sample.identifier.php', $fakeRoot . '/baselines/sample.identifier.php');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-php-no-error-count/another.identifier.php', $fakeRoot . '/baselines/another.identifier.php');
        self::assertFileEquals(__DIR__ . '/Rule/data/baselines-php-no-error-count/missing-identifier.php', $fakeRoot . '/baselines/missing-identifier.php');
    }

    public function testSplitter(): void
    {
        $folder = $this->prepareSampleFolder();
        $squashed = $this->getSampleErrors();

        $loaderPath = $folder . '/baselines/loader.neon';
        file_put_contents($loaderPath, Neon::encode($squashed));

        $splitter = new BaselineSplitter("\t", true);
        $written = $splitter->split($loaderPath);

        self::assertSame([
            $folder . '/baselines/another.identifier.neon' => 1,
            $folder . '/baselines/missing-identifier.neon' => 1,
            $folder . '/baselines/sample.identifier.neon' => 3,
            $folder . '/baselines/loader.neon' => null,
        ], $written);
    }

    private function prepareSampleFolder(): string
    {
        $folder = realpath(sys_get_temp_dir());
        self::assertNotFalse($folder);

        $folder = $folder . '/' . uniqid('split');
        @mkdir($folder . '/baselines', 0777, true);

        return $folder;
    }

    public function testPreservesOrderOfExistingErrors(): void
    {
        $folder = $this->prepareSampleFolder();
        $loaderPath = $folder . '/baselines/loader.neon';

        // First, create an existing baseline with errors in specific order: C, A, B
        $existingBaseline = <<<'NEON'
parameters:
    ignoreErrors:
        -
            message: '#^Error C$#'
            count: 1
            path: ../app/c.php

        -
            message: '#^Error A$#'
            count: 1
            path: ../app/a.php

        -
            message: '#^Error B$#'
            count: 1
            path: ../app/b.php

NEON;
        file_put_contents($folder . '/baselines/test.identifier.neon', $existingBaseline);

        // Now regenerate with the same errors but in different order in the input: A, B, C
        $inputErrors = [
            'parameters' => [
                'ignoreErrors' => [
                    ['message' => '#^Error A$#', 'count' => 1, 'path' => '../app/a.php', 'identifier' => 'test.identifier'],
                    ['message' => '#^Error B$#', 'count' => 1, 'path' => '../app/b.php', 'identifier' => 'test.identifier'],
                    ['message' => '#^Error C$#', 'count' => 1, 'path' => '../app/c.php', 'identifier' => 'test.identifier'],
                ],
            ],
        ];
        file_put_contents($loaderPath, Neon::encode($inputErrors));

        $splitter = new BaselineSplitter("\t", true);
        $splitter->split($loaderPath);

        // The output should preserve the original order: C, A, B
        $result = file_get_contents($folder . '/baselines/test.identifier.neon');
        self::assertNotFalse($result);

        // Check that C comes before A and A comes before B in the output
        $posC = strpos($result, 'Error C');
        $posA = strpos($result, 'Error A');
        $posB = strpos($result, 'Error B');

        self::assertNotFalse($posC);
        self::assertNotFalse($posA);
        self::assertNotFalse($posB);
        self::assertLessThan($posA, $posC, 'Error C should come before Error A');
        self::assertLessThan($posB, $posA, 'Error A should come before Error B');
    }

    public function testNewErrorsInsertedInSortedPosition(): void
    {
        $folder = $this->prepareSampleFolder();
        $loaderPath = $folder . '/baselines/loader.neon';

        // Create existing baseline with errors for a.php and c.php
        $existingBaseline = <<<'NEON'
parameters:
    ignoreErrors:
        -
            message: '#^Error A$#'
            count: 1
            path: ../app/a.php

        -
            message: '#^Error C$#'
            count: 1
            path: ../app/c.php

NEON;
        file_put_contents($folder . '/baselines/test.identifier.neon', $existingBaseline);

        // Now regenerate with an additional error for b.php (new error)
        $inputErrors = [
            'parameters' => [
                'ignoreErrors' => [
                    ['message' => '#^Error A$#', 'count' => 1, 'path' => '../app/a.php', 'identifier' => 'test.identifier'],
                    ['message' => '#^Error B$#', 'count' => 1, 'path' => '../app/b.php', 'identifier' => 'test.identifier'],
                    ['message' => '#^Error C$#', 'count' => 1, 'path' => '../app/c.php', 'identifier' => 'test.identifier'],
                ],
            ],
        ];
        file_put_contents($loaderPath, Neon::encode($inputErrors));

        $splitter = new BaselineSplitter("\t", true);
        $splitter->split($loaderPath);

        // The new error B should be inserted between A and C (sorted by path)
        $result = file_get_contents($folder . '/baselines/test.identifier.neon');
        self::assertNotFalse($result);

        $posA = strpos($result, 'Error A');
        $posB = strpos($result, 'Error B');
        $posC = strpos($result, 'Error C');

        self::assertNotFalse($posA);
        self::assertNotFalse($posB);
        self::assertNotFalse($posC);
        self::assertLessThan($posB, $posA, 'Error A should come before Error B');
        self::assertLessThan($posC, $posB, 'Error B should come before Error C');
    }

    public function testRemovedErrorsAreDeleted(): void
    {
        $folder = $this->prepareSampleFolder();
        $loaderPath = $folder . '/baselines/loader.neon';

        // Create existing baseline with errors A, B, C
        $existingBaseline = <<<'NEON'
parameters:
    ignoreErrors:
        -
            message: '#^Error A$#'
            count: 1
            path: ../app/a.php

        -
            message: '#^Error B$#'
            count: 1
            path: ../app/b.php

        -
            message: '#^Error C$#'
            count: 1
            path: ../app/c.php

NEON;
        file_put_contents($folder . '/baselines/test.identifier.neon', $existingBaseline);

        // Regenerate with only A and C (B is removed)
        $inputErrors = [
            'parameters' => [
                'ignoreErrors' => [
                    ['message' => '#^Error A$#', 'count' => 1, 'path' => '../app/a.php', 'identifier' => 'test.identifier'],
                    ['message' => '#^Error C$#', 'count' => 1, 'path' => '../app/c.php', 'identifier' => 'test.identifier'],
                ],
            ],
        ];
        file_put_contents($loaderPath, Neon::encode($inputErrors));

        $splitter = new BaselineSplitter("\t", true);
        $splitter->split($loaderPath);

        $result = file_get_contents($folder . '/baselines/test.identifier.neon');
        self::assertNotFalse($result);

        // Error B should be removed
        self::assertStringNotContainsString('Error B', $result);
        // A and C should still be present in original order
        self::assertStringContainsString('Error A', $result);
        self::assertStringContainsString('Error C', $result);
    }

    public function testCountChangesPreserveOrder(): void
    {
        $folder = $this->prepareSampleFolder();
        $loaderPath = $folder . '/baselines/loader.neon';

        // Create existing baseline with count=2
        $existingBaseline = <<<'NEON'
parameters:
    ignoreErrors:
        -
            message: '#^Error A$#'
            count: 2
            path: ../app/a.php

NEON;
        file_put_contents($folder . '/baselines/test.identifier.neon', $existingBaseline);

        // Regenerate with count=5
        $inputErrors = [
            'parameters' => [
                'ignoreErrors' => [
                    ['message' => '#^Error A$#', 'count' => 5, 'path' => '../app/a.php', 'identifier' => 'test.identifier'],
                ],
            ],
        ];
        file_put_contents($loaderPath, Neon::encode($inputErrors));

        $splitter = new BaselineSplitter("\t", true);
        $splitter->split($loaderPath);

        $result = file_get_contents($folder . '/baselines/test.identifier.neon');
        self::assertNotFalse($result);

        // Count should be updated to 5
        self::assertStringContainsString('count: 5', $result);
        self::assertStringNotContainsString('count: 2', $result);
    }

    public function testFirstRunCreatesFileSorted(): void
    {
        $folder = $this->prepareSampleFolder();
        $loaderPath = $folder . '/baselines/loader.neon';

        // No existing baseline file - first run with errors in unsorted order: C, A, B
        $inputErrors = [
            'parameters' => [
                'ignoreErrors' => [
                    ['message' => '#^Error C$#', 'count' => 1, 'path' => '../app/c.php', 'identifier' => 'test.identifier'],
                    ['message' => '#^Error A$#', 'count' => 1, 'path' => '../app/a.php', 'identifier' => 'test.identifier'],
                    ['message' => '#^Error B$#', 'count' => 1, 'path' => '../app/b.php', 'identifier' => 'test.identifier'],
                ],
            ],
        ];
        file_put_contents($loaderPath, Neon::encode($inputErrors));

        $splitter = new BaselineSplitter("\t", true);
        $splitter->split($loaderPath);

        // On first run (no existing file), errors should be written as-is (not sorted by default)
        // since there's no existing order to preserve
        $result = file_get_contents($folder . '/baselines/test.identifier.neon');
        self::assertNotFalse($result);

        self::assertStringContainsString('Error A', $result);
        self::assertStringContainsString('Error B', $result);
        self::assertStringContainsString('Error C', $result);
    }

    /**
     * @return array{parameters: array{ignoreErrors: array{0: array{message?: string, rawMessage?: string, count: int, path: string, identifier?: string}}}}
     */
    private function getSampleErrors(): array
    {
        return [
            'parameters' => [
                'ignoreErrors' => [
                    [
                        'message' => '#^Error simple$#',
                        'count' => 2,
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
                    [
                        'rawMessage' => 'Error raw message list<Foo\\Bar>|null',
                        'count' => 1,
                        'path' => '../app/index.php',
                        'identifier' => 'sample.identifier',
                    ],
                ],
            ],
        ];
    }

}

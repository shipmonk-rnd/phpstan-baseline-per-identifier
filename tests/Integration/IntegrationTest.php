<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Integration;

use ShipMonk\PHPStan\Baseline\BinTestCase;
use function array_map;
use function file_put_contents;
use function glob;
use function is_dir;
use function mkdir;

final class IntegrationTest extends BinTestCase
{

    /**
     * @dataProvider provideExtension
     */
    public function testResultCache(string $extension): void
    {
        $emptyConfig = $extension === 'php' ? '<?php return [];' : '';
        $baselinesDir = 'cache/integration-test/baselines';
        $baselinesDirAbs = __DIR__ . '/../../' . $baselinesDir;

        if (!is_dir($baselinesDirAbs)) {
            mkdir($baselinesDirAbs, 0777, true);
        }

        array_map('unlink', glob($baselinesDirAbs . '/*')); // @phpstan-ignore argument.type

        // ensure dummy loader is present
        file_put_contents($baselinesDirAbs . "/_loader.$extension", $emptyConfig);

        $cwd = __DIR__;
        $phpstan = '../../vendor/bin/phpstan';
        $split = '../../bin/split-phpstan-baseline';

        $this->runCommand("$phpstan clear-result-cache -c $extension.neon", $cwd, 0);
        $this->runCommand("$phpstan analyse -vv -c $extension.neon --generate-baseline=../../$baselinesDir/_loader.$extension", $cwd, 0, null, 'Result cache is saved.');
        $this->runCommand("php $split ../../$baselinesDir/_loader.$extension", $cwd, 0, 'Writing baseline file');
        $this->runCommand("$phpstan analyse -vv -c $extension.neon", $cwd, 0, null, 'Result cache restored. 0 files will be reanalysed.');

        // cache should invalidate by editing the baseline
        file_put_contents($baselinesDirAbs . "/method.notFound.$extension", $emptyConfig);

        $this->runCommand("$phpstan analyse -vv -c $extension.neon", $cwd, 1, 'Call to an undefined method DateTime::invalid()');
    }

    /**
     * @return iterable<array{string}>
     */
    public function provideExtension(): iterable
    {
        yield 'Neon' => ['neon'];
        yield 'PHP' => ['php'];
    }

}

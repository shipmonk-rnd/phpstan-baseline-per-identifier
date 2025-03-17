<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use LogicException;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use function array_shift;
use function count;
use function explode;
use function file_put_contents;
use function implode;
use function ksort;
use function preg_quote;
use function realpath;
use function sprintf;
use function str_repeat;
use const DIRECTORY_SEPARATOR;
use const SORT_STRING;

/**
 * @deprecated Use new approach, see readme
 */
class BaselinePerIdentifierFormatter implements ErrorFormatter
{

    private string $baselinesDir;

    private string $indent;

    public function __construct(?string $baselinesDir, string $indent)
    {
        if ($baselinesDir === null) {
            throw new LogicException('Baselines directory must be set, please set up \'parameters.shipmonkBaselinePerIdentifier.directory\' in your phpstan configuration file');
        }

        $baselinesRealDir = realpath($baselinesDir);

        if ($baselinesRealDir === false) {
            throw new LogicException('Baselines directory \'' . $baselinesDir . '\' does not exist');
        }

        $this->baselinesDir = $baselinesRealDir;
        $this->indent = $indent;
    }

    public function formatErrors(
        AnalysisResult $analysisResult,
        Output $output
    ): int
    {
        foreach ($analysisResult->getInternalErrorObjects() as $internalError) {
            $output->writeLineFormatted('<error>' . $internalError->getMessage() . '</error>');
        }

        $fileErrors = [];

        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            if (!$fileSpecificError->canBeIgnored()) {
                continue;
            }

            $usedIdentifier = $fileSpecificError->getIdentifier() ?? 'missing-identifier';

            $relativeFilePath = $this->getPathDifference($this->baselinesDir, $fileSpecificError->getFilePath());
            $fileErrors[$usedIdentifier][$relativeFilePath][] = $fileSpecificError->getMessage();
        }

        ksort($fileErrors, SORT_STRING);

        $includes = [];
        $totalErrorsCount = 0;

        foreach ($fileErrors as $identifier => $errors) {
            $errorsToOutput = [];
            $errorsCount = 0;

            foreach ($errors as $file => $errorMessages) {
                $fileErrorsCounts = [];

                foreach ($errorMessages as $errorMessage) {
                    if (!isset($fileErrorsCounts[$errorMessage])) {
                        $fileErrorsCounts[$errorMessage] = 1;
                        continue;
                    }

                    $fileErrorsCounts[$errorMessage]++;
                }

                ksort($fileErrorsCounts, SORT_STRING);

                foreach ($fileErrorsCounts as $message => $count) {
                    $errorsToOutput[] = [
                        'message' => NeonHelper::escape('#^' . preg_quote($message, '#') . '$#'),
                        'count' => $count,
                        'path' => NeonHelper::escape($file),
                    ];
                    $errorsCount += $count;
                }
            }

            $includes[] = $identifier . '.neon';
            $baselineFilePath = $this->baselinesDir . '/' . $identifier . '.neon';

            $totalErrorsCount += $errorsCount;
            $output->writeLineFormatted(sprintf('Writing baseline file %s with %d errors', $baselineFilePath, $errorsCount));

            $plurality = $errorsCount === 1 ? '' : 's';
            $prefix = "# total $errorsCount error$plurality\n\n";
            $contents = $prefix . NeonHelper::encode(['parameters' => ['ignoreErrors' => $errorsToOutput]], $this->indent);
            $written = file_put_contents($baselineFilePath, $contents);

            if ($written === false) {
                throw new LogicException('Error while writing to ' . $baselineFilePath);
            }
        }

        $plurality = $totalErrorsCount === 1 ? '' : 's';
        $prefix = "# total $totalErrorsCount error$plurality\n";
        $writtenLoader = file_put_contents($this->baselinesDir . '/loader.neon', $prefix . NeonHelper::encode(['includes' => $includes], $this->indent));

        if ($writtenLoader === false) {
            throw new LogicException('Error while writing to ' . $this->baselinesDir . '/loader.neon');
        }

        $output->writeLineFormatted('');
        $output->writeLineFormatted('⚠️  <comment>You are using deprecated approach to split baselines which cannot utilize PHPStan result cache</comment> ⚠️');
        $output->writeLineFormatted('    Consider switching to new approach via:');
        $output->writeLineFormatted("    vendor/bin/phpstan --generate-baseline=$this->baselinesDir/loader.neon && vendor/bin/split-phpstan-baseline $this->baselinesDir/loader.neon");
        $output->writeLineFormatted('');

        return 0;
    }

    private function getPathDifference(string $from, string $to): string
    {
        $fromParts = explode(DIRECTORY_SEPARATOR, $from);
        $toParts = explode(DIRECTORY_SEPARATOR, $to);

        // Find the common base path
        while ($fromParts !== [] && $toParts !== [] && ($fromParts[0] === $toParts[0])) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        // Add '..' for each remaining part in $fromParts
        $relativePath = str_repeat('..' . DIRECTORY_SEPARATOR, count($fromParts));

        // Append the remaining parts from $toParts
        $relativePath .= implode(DIRECTORY_SEPARATOR, $toParts);

        return $relativePath;
    }

}

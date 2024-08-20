<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use LogicException;
use Nette\Neon\Neon;
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
use function preg_replace;
use function realpath;
use function sprintf;
use function str_repeat;
use function trim;
use const DIRECTORY_SEPARATOR;
use const SORT_STRING;

class BaselinePerIdentifierFormatter implements ErrorFormatter
{

    private string $baselinesDir;

    private string $indent;

    public function __construct(string $baselinesDir, string $indent)
    {
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

        foreach ($fileErrors as $identifier => $errors) {
            $errorsToOutput = [];

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
                        'message' => $this->escape('#^' . preg_quote($message, '#') . '$#'),
                        'count' => $count,
                        'path' => $this->escape($file),
                    ];
                }
            }

            $includes[] = $identifier . '.neon';
            $baselineFilePath = $this->baselinesDir . '/' . $identifier . '.neon';
            $errorsCount = count($errorsToOutput);

            $output->writeLineFormatted(sprintf('Writing baseline file %s with %d errors', $baselineFilePath, $errorsCount));

            $prefix = "# total $errorsCount errors\n\n";
            $contents = $prefix . $this->getNeon(['parameters' => ['ignoreErrors' => $errorsToOutput]]);
            $written = file_put_contents($baselineFilePath, $contents);

            if ($written === false) {
                throw new LogicException('Error while writing to ' . $baselineFilePath);
            }
        }

        $writtenLoader = file_put_contents($this->baselinesDir . '/loader.neon', $this->getNeon(['includes' => $includes]));

        if ($writtenLoader === false) {
            throw new LogicException('Error while writing to ' . $this->baselinesDir . '/loader.neon');
        }

        return 0;
    }

    private function getNeon(mixed $data): string
    {
        return trim(Neon::encode($data, blockMode: true, indentation: $this->indent)) . "\n";
    }

    private function escape(string $value): string
    {
        $return = preg_replace('#^@|%#', '$0$0', $value);

        if ($return === null) {
            throw new LogicException('Error while escaping ' . $value);
        }

        return $return;
    }

    private function getPathDifference(string $from, string $to): string
    {
        $fromParts = explode(DIRECTORY_SEPARATOR, $from);
        $toParts = explode(DIRECTORY_SEPARATOR, $to);

        // Find the common base path
        while (count($fromParts) > 0 && count($toParts) > 0 && ($fromParts[0] === $toParts[0])) {
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

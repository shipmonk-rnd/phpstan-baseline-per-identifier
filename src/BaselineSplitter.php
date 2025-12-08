<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use ArrayIterator;
use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use ShipMonk\PHPStan\Baseline\Handler\BaselineHandler;
use ShipMonk\PHPStan\Baseline\Handler\HandlerFactory;
use SplFileInfo;
use function array_reduce;
use function basename;
use function dirname;
use function file_put_contents;
use function glob;
use function is_file;
use function ksort;
use function str_replace;
use function unlink;

class BaselineSplitter
{

    private string $indent;

    private bool $includeCount;

    public function __construct(
        string $indent,
        bool $includeCount
    )
    {
        $this->indent = $indent;
        $this->includeCount = $includeCount;
    }

    /**
     * @return array<string, int|null> file path => error count (null for loader, 0 for deleted)
     *
     * @throws ErrorException
     */
    public function split(string $loaderFilePath): array
    {
        $splFile = new SplFileInfo($loaderFilePath);
        $realPath = $splFile->getRealPath();

        if ($realPath === false) {
            throw new ErrorException("Unable to realpath '$loaderFilePath'");
        }

        $folder = dirname($realPath);
        $loaderFileName = $splFile->getFilename();
        $extension = $splFile->getExtension();

        $handler = HandlerFactory::create($extension);
        $ignoredErrors = $handler->decodeBaseline($realPath);
        $groupedErrors = $this->groupErrorsByIdentifier($ignoredErrors, $folder);

        $outputInfo = [];
        $baselineFiles = [];
        $writtenFiles = [];
        $totalErrorCount = 0;

        foreach ($groupedErrors as $identifier => $newErrors) {
            $fileName = $identifier . '.' . $extension;
            $filePath = $folder . '/' . $fileName;

            $oldErrors = $this->readExistingErrors($filePath, $handler) ?? [];
            $sortedErrors = $this->sortErrors($oldErrors, $newErrors);

            $errorsCount = array_reduce($sortedErrors, static fn (int $carry, array $item): int => $carry + $item['count'], 0);
            $totalErrorCount += $errorsCount;

            $outputInfo[$filePath] = $errorsCount;
            $baselineFiles[] = $fileName;
            $writtenFiles[$filePath] = true;

            $plural = $errorsCount === 1 ? '' : 's';
            $prefix = $this->includeCount ? "total $errorsCount error$plural" : null;

            $encodedData = $handler->encodeBaseline($prefix, $sortedErrors, $this->indent);
            $this->writeFile($filePath, $encodedData);
        }

        $plural = $totalErrorCount === 1 ? '' : 's';
        $prefix = $this->includeCount ? "total $totalErrorCount error$plural" : null;
        $baselineLoaderData = $handler->encodeBaselineLoader($prefix, $baselineFiles, $this->indent);
        $this->writeFile($realPath, $baselineLoaderData);

        $outputInfo[$realPath] = null;

        // Delete orphaned baseline files
        $deletedFiles = $this->deleteOrphanedFiles($folder, $extension, $loaderFileName, $writtenFiles);

        foreach ($deletedFiles as $deletedFile) {
            $outputInfo[$deletedFile] = 0;
        }

        return $outputInfo;
    }

    /**
     * @param list<array{message: string, count: int, path: string, identifier: string|null}|array{rawMessage: string, count: int, path: string, identifier: string|null}> $errors
     * @return array<string, list<array{message: string, count: int, path: string}|array{rawMessage: string, count: int, path: string}>>
     *
     * @throws ErrorException
     */
    private function groupErrorsByIdentifier(
        array $errors,
        string $folder
    ): array
    {
        $groupedErrors = [];

        foreach ($errors as $error) {
            $identifier = $error['identifier'] ?? 'missing-identifier';
            $normalizedPath = str_replace($folder . '/', '', $error['path']);

            if (isset($error['rawMessage'])) {
                $groupedErrors[$identifier][] = [
                    'rawMessage' => $error['rawMessage'],
                    'count' => $error['count'],
                    'path' => $normalizedPath,
                ];

            } elseif (isset($error['message'])) {
                $groupedErrors[$identifier][] = [
                    'message' => $error['message'],
                    'count' => $error['count'],
                    'path' => $normalizedPath,
                ];

            } else {
                throw new ErrorException('Error is missing message or rawMessage');
            }
        }

        ksort($groupedErrors);

        return $groupedErrors;
    }

    /**
     * @throws ErrorException
     */
    private function writeFile(
        string $filePath,
        string $contents
    ): void
    {
        $written = file_put_contents($filePath, $contents);

        if ($written === false) {
            throw new ErrorException('Error while writing to ' . $filePath);
        }
    }

    /**
     * @param array{message?: string, rawMessage?: string, count: int, path: string} $error
     */
    private function getErrorKey(array $error): string
    {
        return $error['path'] . "\x00" . ($error['rawMessage'] ?? $error['message'] ?? '');
    }

    /**
     * @return list<array{message: string, count: int, path: string}|array{rawMessage: string, count: int, path: string}>|null
     */
    private function readExistingErrors(
        string $filePath,
        BaselineHandler $handler
    ): ?array
    {
        if (!is_file($filePath)) {
            return null;
        }

        try {
            return $handler->decodeBaseline($filePath);

        } catch (ErrorException $e) {
            return null;
        }
    }

    /**
     * @param list<array{message: string, count: int, path: string}|array{rawMessage: string, count: int, path: string}> $oldErrors
     * @param list<array{message: string, count: int, path: string}|array{rawMessage: string, count: int, path: string}> $newErrors
     * @return list<array{message: string, count: int, path: string}|array{rawMessage: string, count: int, path: string}>
     */
    private function sortErrors(
        array $oldErrors,
        array $newErrors
    ): array
    {
        $newErrorsByKey = [];

        foreach ($newErrors as $newError) {
            $key = $this->getErrorKey($newError);
            $newErrorsByKey[$key] = $newError;
        }

        // collect errors that existed before
        $existingByKey = [];

        foreach ($oldErrors as $oldError) {
            $key = $this->getErrorKey($oldError);

            if (isset($newErrorsByKey[$key])) {
                $existingByKey[$key] = $newErrorsByKey[$key];
                unset($newErrorsByKey[$key]);
            }
        }

        // insert new errors at their sorted positions among existing errors
        ksort($newErrorsByKey);
        $newErrorsIterator = new ArrayIterator($newErrorsByKey);
        $result = [];

        foreach ($existingByKey as $existingKey => $existingError) {
            while ($newErrorsIterator->valid() && $newErrorsIterator->key() < $existingKey) {
                $result[] = $newErrorsIterator->current();
                $newErrorsIterator->next();
            }

            $result[] = $existingError;
        }

        while ($newErrorsIterator->valid()) {
            $result[] = $newErrorsIterator->current();
            $newErrorsIterator->next();
        }

        return $result;
    }

    /**
     * @param array<string, true> $writtenFiles
     * @return list<string>
     */
    private function deleteOrphanedFiles(
        string $folder,
        string $extension,
        string $loaderFileName,
        array $writtenFiles
    ): array
    {
        $deletedFiles = [];
        $existingFiles = glob($folder . '/*.' . $extension);

        if ($existingFiles === false) {
            return [];
        }

        foreach ($existingFiles as $existingFile) {
            $fileName = basename($existingFile);

            // Skip the loader file
            if ($fileName === $loaderFileName) {
                continue;
            }

            // Skip files that were written in this run
            if (isset($writtenFiles[$existingFile])) {
                continue;
            }

            // Delete orphaned file
            if (unlink($existingFile)) {
                $deletedFiles[] = $existingFile;
            }
        }

        return $deletedFiles;
    }

}

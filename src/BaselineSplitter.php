<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use ShipMonk\PHPStan\Baseline\Handler\HandlerFactory;
use SplFileInfo;
use function array_keys;
use function assert;
use function count;
use function dirname;
use function file_put_contents;
use function is_array;
use function is_int;
use function is_string;
use function ksort;
use function str_replace;

class BaselineSplitter
{

    public function __construct(
        private string $indent
    )
    {
    }

    /**
     * @return array<string, int|null>
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
        $extension = $splFile->getExtension();

        $handler = HandlerFactory::create($extension);
        $data = $handler->decodeBaseline($realPath);

        $ignoredErrors = $data['parameters']['ignoreErrors'] ?? null; // @phpstan-ignore offsetAccess.nonOffsetAccessible

        if (!is_array($ignoredErrors)) {
            throw new ErrorException(
                "Invalid argument, expected $extension file with 'parameters.ignoreErrors' key in '$loaderFilePath'." .
                "\n - Did you run native baseline generation first?" .
                "\n - You can so via vendor/bin/phpstan --generate-baseline=$loaderFilePath",
            );
        }

        $groupedErrors = $this->groupErrorsByIdentifier($ignoredErrors, $folder);

        $createdBaselines = [];

        foreach ($groupedErrors as $identifier => $errors) {
            $fileName = $identifier . '.' . $extension;
            $filePath = $folder . '/' . $fileName;
            $errorsCount = count($errors);

            $createdBaselines[$fileName] = $errorsCount;

            $plural = $errorsCount === 1 ? '' : 's';
            $prefix = "total $errorsCount error$plural";

            $encodedData = $handler->encodeBaseline($prefix, $errors, $this->indent);
            $this->writeFile($filePath, $encodedData);
        }

        $baselineLoaderData = $handler->encodeBaselineLoader(array_keys($createdBaselines), $this->indent);
        $this->writeFile($loaderFilePath, $baselineLoaderData);

        $createdBaselines[$loaderFilePath] = null;

        return $createdBaselines;
    }

    /**
     * @param array<mixed> $errors
     * @return array<string, list<array{message: string, count: int, path: string}>>
     * @throws ErrorException
     */
    private function groupErrorsByIdentifier(array $errors, string $folder): array
    {
        $groupedErrors = [];

        foreach ($errors as $index => $error) {
            if (!is_array($error)) {
                throw new ErrorException("Ignored error #$index is not an array");
            }

            $identifier = $error['identifier'] ?? 'missing-identifier';

            $message = $error['message'] ?? throw new ErrorException("Ignored error #$index is missing 'message'");
            $count = $error['count'] ?? throw new ErrorException("Ignored error #$index is missing 'count'");
            $path = $error['path'] ?? throw new ErrorException("Ignored error #$index is missing 'path'");

            assert(is_string($identifier));
            assert(is_string($message));
            assert(is_int($count));
            assert(is_string($path));

            $normalizedPath = str_replace($folder . '/', '', $path);

            unset($error['identifier']);

            $groupedErrors[$identifier][] = [
                'message' => $message,
                'count' => $count,
                'path' => $normalizedPath,
            ];
        }

        ksort($groupedErrors);

        return $groupedErrors;
    }

    /**
     * @throws ErrorException
     */
    private function writeFile(string $filePath, string $contents): void
    {
        $written = file_put_contents($filePath, $contents);

        if ($written === false) {
            throw new ErrorException('Error while writing to ' . $filePath);
        }
    }

}

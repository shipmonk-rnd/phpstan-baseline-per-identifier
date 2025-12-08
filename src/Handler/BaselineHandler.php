<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use function is_array;
use function is_int;
use function is_string;

abstract class BaselineHandler
{

    /**
     * @return list<array{message: string, count: int, path: string, identifier: string|null}|array{rawMessage: string, count: int, path: string, identifier: string|null}>
     *
     * @throws ErrorException
     */
    public function decodeBaseline(string $filepath): array
    {
        $decoded = $this->decodeBaselineFile($filepath);

        if (!isset($decoded['parameters']) || !is_array($decoded['parameters'])) {
            throw new ErrorException("File '$filepath' must contain 'parameters' array");
        }

        if (!isset($decoded['parameters']['ignoreErrors']) || !is_array($decoded['parameters']['ignoreErrors'])) {
            throw new ErrorException("File '$filepath' must contain 'parameters.ignoreErrors' array");
        }

        $errors = $decoded['parameters']['ignoreErrors'];
        $result = [];

        foreach ($errors as $index => $error) {
            if (!is_array($error)) {
                throw new ErrorException("Ignored error #$index in '$filepath' is not an array");
            }

            if (!isset($error['path']) || !is_string($error['path'])) {
                throw new ErrorException("Ignored error #$index in '$filepath' is missing 'path'");
            }

            if (!isset($error['count']) || !is_int($error['count'])) {
                throw new ErrorException("Ignored error #$index in '$filepath' is missing 'count'");
            }

            $error['identifier'] ??= null;

            if ($error['identifier'] !== null && !is_string($error['identifier'])) {
                throw new ErrorException("Ignored error #$index in '$filepath' has invalid 'identifier'");
            }

            if (isset($error['rawMessage'])) {
                if (!is_string($error['rawMessage'])) {
                    throw new ErrorException("Ignored error #$index in '$filepath' has invalid 'rawMessage'");
                }

                $result[] = [
                    'rawMessage' => $error['rawMessage'],
                    'count' => $error['count'],
                    'path' => $error['path'],
                    'identifier' => $error['identifier'],
                ];

            } elseif (isset($error['message'])) {
                if (!is_string($error['message'])) {
                    throw new ErrorException("Ignored error #$index in '$filepath' has invalid 'message'");
                }

                $result[] = [
                    'message' => $error['message'],
                    'count' => $error['count'],
                    'path' => $error['path'],
                    'identifier' => $error['identifier'],
                ];

            } else {
                throw new ErrorException("Ignored error #$index in '$filepath' is missing 'message' or 'rawMessage'");
            }
        }

        return $result;
    }

    /**
     * @return array<mixed>
     *
     * @throws ErrorException
     */
    abstract protected function decodeBaselineFile(string $filepath): array;

    /**
     * @param list<array{message: string, count: int, path: string}|array{rawMessage: string, count: int, path: string}> $errors
     */
    abstract public function encodeBaseline(
        ?string $comment,
        array $errors,
        string $indent
    ): string;

    /**
     * @param list<string> $filePaths
     */
    abstract public function encodeBaselineLoader(
        ?string $comment,
        array $filePaths,
        string $indent
    ): string;

}

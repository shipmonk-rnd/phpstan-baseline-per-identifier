<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use ShipMonk\PHPStan\Baseline\Exception\ErrorException;

interface BaselineHandler
{

    /**
     * @throws ErrorException
     */
    public function decodeBaseline(string $filepath): mixed;

    /**
     * @param list<array{message: string, count: int, path: string}> $errors
     */
    public function encodeBaseline(string $comment, array $errors, string $indent): string;

    /**
     * @param list<string> $filePaths
     */
    public function encodeBaselineLoader(array $filePaths, string $indent): string;

}

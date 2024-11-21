<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use Nette\Neon\Exception as NeonException;
use Nette\Neon\Neon;
use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use ShipMonk\PHPStan\Baseline\NeonHelper;

class NeonBaselineHandler implements BaselineHandler
{

    public function decodeBaseline(string $filepath): mixed
    {
        try {
            /** @throws NeonException */
            return Neon::decodeFile($filepath);
        } catch (NeonException $e) {
            throw new ErrorException('Invalid neon file: ' . $e->getMessage(), $e);
        }
    }

    public function encodeBaseline(string $comment, array $errors, string $indent): string
    {
        $prefix = "# $comment\n\n";
        return $prefix . NeonHelper::encode(['parameters' => ['ignoreErrors' => $errors]], $indent);
    }

    public function encodeBaselineLoader(array $filePaths, string $indent): string
    {
        return NeonHelper::encode(['includes' => $filePaths], $indent);
    }

}

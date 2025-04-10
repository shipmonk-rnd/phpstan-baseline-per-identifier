<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use Nette\Neon\Exception as NeonException;
use Nette\Neon\Neon;
use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use ShipMonk\PHPStan\Baseline\NeonHelper;
use function gettype;
use function is_array;

class NeonBaselineHandler implements BaselineHandler
{

    public function decodeBaseline(string $filepath): array
    {
        try {
            /** @throws NeonException */
            $decoded = Neon::decodeFile($filepath);

            if (!is_array($decoded)) {
                throw new ErrorException('Invalid neon file: root must be an array, ' . gettype($decoded) . ' given');
            }

            return $decoded;

        } catch (NeonException $e) {
            throw new ErrorException('Invalid neon file: ' . $e->getMessage(), $e);
        }
    }

    public function encodeBaseline(?string $comment, array $errors, string $indent): string
    {
        $prefix = $comment !== null ? "# $comment\n\n" : '';
        return $prefix . NeonHelper::encode(['parameters' => ['ignoreErrors' => $errors]], $indent);
    }

    public function encodeBaselineLoader(?string $comment, array $filePaths, string $indent): string
    {
        $prefix = $comment !== null ? "# $comment\n" : '';
        return $prefix . NeonHelper::encode(['includes' => $filePaths], $indent);
    }

}

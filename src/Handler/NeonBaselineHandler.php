<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use Nette\Neon\Exception as NeonException;
use Nette\Neon\Neon;
use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use ShipMonk\PHPStan\Baseline\NeonHelper;
use function get_debug_type;
use function is_array;

class NeonBaselineHandler implements BaselineHandler
{

    public function decodeBaseline(string $filepath): array
    {
        try {
            /** @throws NeonException */
            $decoded = Neon::decodeFile($filepath);

            if (!is_array($decoded)) {
                throw new ErrorException('Invalid neon file: root must be an array, ' . get_debug_type($decoded) . ' given');
            }

            return $decoded;

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

<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use LogicException;
use Nette\Neon\Exception as NeonException;
use Nette\Neon\Neon;
use function preg_replace;
use function trim;

class NeonHelper
{

    public static function encode(
        mixed $data,
        string $indent,
    ): string
    {
        try {
            return trim(Neon::encode($data, true, $indent)) . "\n";
        } catch (NeonException $e) {
            throw new LogicException('Error while encoding NEON: ' . $e->getMessage(), 0, $e);
        }
    }

    public static function escape(string $value): string
    {
        $return = preg_replace('#^@|%#', '$0$0', $value);

        if ($return === null) {
            throw new LogicException('Error while escaping ' . $value);
        }

        return $return;
    }

}

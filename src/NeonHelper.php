<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline;

use LogicException;
use Nette\Neon\Neon;
use function preg_replace;
use function trim;

class NeonHelper
{

    public static function encode(mixed $data, string $indent): string
    {
        return trim(Neon::encode($data, blockMode: true, indentation: $indent)) . "\n";
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

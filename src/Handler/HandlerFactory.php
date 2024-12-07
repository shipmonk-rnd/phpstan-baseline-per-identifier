<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use ShipMonk\PHPStan\Baseline\Exception\ErrorException;

class HandlerFactory
{

    /**
     * @throws ErrorException
     */
    public static function create(string $extension): BaselineHandler
    {
        switch ($extension) {
            case 'neon':
                return new NeonBaselineHandler();

            case 'php':
                return new PhpBaselineHandler();

            default:
                throw new ErrorException("Invalid baseline file extension '$extension', expected neon or php file");
        }
    }

}

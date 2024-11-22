<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Exception;

use RuntimeException;
use Throwable;

class ErrorException extends RuntimeException
{

    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

}

<?php declare(strict_types = 1);

return array_merge(
    require __DIR__ . '/another.identifier.php',
    require __DIR__ . '/missing-identifier.php',
    require __DIR__ . '/sample.identifier.php',
);
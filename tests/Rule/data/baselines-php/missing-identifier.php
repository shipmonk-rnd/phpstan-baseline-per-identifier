<?php declare(strict_types = 1);

// total 1 error

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Error 3$#',
    'count' => 1,
    'path' => __DIR__ . '/../app/index.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

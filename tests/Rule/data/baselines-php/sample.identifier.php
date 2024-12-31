<?php declare(strict_types = 1);

// total 1 error

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Error simple$#',
    'count' => 1,
    'path' => __DIR__ . '/../app/file.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

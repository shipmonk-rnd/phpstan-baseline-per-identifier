<?php declare(strict_types = 1);

// total 2 errors

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Error simple$#',
    'count' => 2,
    'path' => __DIR__ . '/../app/file.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

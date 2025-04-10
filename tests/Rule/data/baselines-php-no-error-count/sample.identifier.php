<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Error simple$#',
    'count' => 2,
    'path' => __DIR__ . '/../app/file.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Error 3$#',
    'count' => 1,
    'path' => __DIR__ . '/../app/index.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

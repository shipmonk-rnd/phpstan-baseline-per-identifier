<?php declare(strict_types = 1);

// total 1 error

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Error to escape \'\\#$#',
    'count' => 1,
    'path' => __DIR__ . '/../app/config.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

<?php declare(strict_types = 1);

// total 3 errors

$ignoreErrors = [];
$ignoreErrors[] = [
    'message' => '#^Error simple$#',
    'count' => 2,
    'path' => __DIR__ . '/../app/file.php',
];
$ignoreErrors[] = [
    'rawMessage' => 'Error raw message list<Foo\\Bar>|null',
    'count' => 1,
    'path' => __DIR__ . '/../app/index.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

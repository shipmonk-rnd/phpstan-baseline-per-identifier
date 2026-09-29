<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhpBaselineHandlerTest extends TestCase
{

    #[DataProvider('providePaths')]
    public function testEncodePath(
        string $path,
        string $expectedPathLine,
    ): void
    {
        $encoded = (new PhpBaselineHandler())->encodeBaseline(null, [['message' => '#^Error$#', 'count' => 1, 'path' => $path]], '    ');

        self::assertStringContainsString("\n    'path' => $expectedPathLine,\n", $encoded);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function providePaths(): iterable
    {
        yield 'relative' => ['../app/a.php', "__DIR__ . '/../app/a.php'"];
        yield 'relative without dots' => ['app/a.php', "__DIR__ . '/app/a.php'"];
        yield 'unix absolute' => ['/app/a.php', "'/app/a.php'"];
        yield 'windows absolute' => ['C:\\app\\a.php', "'C:\\\\app\\\\a.php'"];
        yield 'windows absolute with slash' => ['c:/app/a.php', "'c:/app/a.php'"];
        yield 'windows drive relative' => ['C:app\\a.php', "__DIR__ . '/C:app\\\\a.php'"];
        yield 'windows unc' => ['\\\\server\\app\\a.php', "'\\\\\\\\server\\\\app\\\\a.php'"];
        yield 'stream wrapper' => ['phar://app.phar/a.php', "'phar://app.phar/a.php'"];
        yield 'uppercase stream wrapper' => ['PHAR://app.phar/a.php', "'PHAR://app.phar/a.php'"];
    }

}

<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use Throwable;
use function get_debug_type;
use function is_array;
use function sprintf;
use function var_export;

class PhpBaselineHandler implements BaselineHandler
{

    public function decodeBaseline(string $filepath): array
    {
        try {
            $decoded = (static function () use ($filepath): mixed {
                return require $filepath;
            })();

            if (!is_array($decoded)) {
                throw new ErrorException("File '$filepath' must return array, " . get_debug_type($decoded) . ' given');
            }

            return $decoded;

        } catch (Throwable $e) {
            throw new ErrorException("Error while loading baseline file '$filepath':" . $e->getMessage(), $e);
        }
    }

    public function encodeBaseline(string $comment, array $errors, string $indent): string
    {
        $php = '<?php declare(strict_types = 1);';
        $php .= "\n\n";
        $php .= "// $comment";
        $php .= "\n\n";
        $php .= "\$ignoreErrors = [];\n";

        foreach ($errors as $error) {
            $php .= sprintf(
                "\$ignoreErrors[] = [\n{$indent}'message' => %s,\n{$indent}'count' => %d,\n{$indent}'path' => __DIR__ . %s,\n];\n",
                var_export($error['message'], true),
                var_export($error['count'], true),
                var_export($error['path'], true),
            );
        }

        $php .= "\n";
        $php .= 'return [\'parameters\' => [\'ignoreErrors\' => $ignoreErrors]];';
        $php .= "\n";

        return $php;
    }

    public function encodeBaselineLoader(array $filePaths, string $indent): string
    {
        $php = "<?php declare(strict_types = 1);\n\n";
        $php .= "return array_merge_recursive(\n";

        foreach ($filePaths as $filePath) {
            $php .= "{$indent}require __DIR__ . '/$filePath',\n";
        }

        $php .= ");\n";

        return $php;
    }

}

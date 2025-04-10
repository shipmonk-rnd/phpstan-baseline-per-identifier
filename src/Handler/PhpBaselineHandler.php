<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use Throwable;
use function gettype;
use function is_array;
use function sprintf;
use function var_export;

class PhpBaselineHandler implements BaselineHandler
{

    public function decodeBaseline(string $filepath): array
    {
        try {
            $decoded = (static fn() => require $filepath)();

            if (!is_array($decoded)) {
                throw new ErrorException("File '$filepath' must return array, " . gettype($decoded) . ' given');
            }

            return $decoded;

        } catch (Throwable $e) {
            throw new ErrorException("Error while loading baseline file '$filepath':" . $e->getMessage(), $e);
        }
    }

    public function encodeBaseline(?string $comment, array $errors, string $indent): string
    {
        $php = '<?php declare(strict_types = 1);';

        if ($comment !== null) {
            $php .= "\n\n";
            $php .= "// $comment";
        }

        $php .= "\n\n";
        $php .= "\$ignoreErrors = [];\n";

        foreach ($errors as $error) {
            $php .= sprintf(
                "\$ignoreErrors[] = [\n{$indent}'message' => %s,\n{$indent}'count' => %d,\n{$indent}'path' => __DIR__ . %s,\n];\n",
                var_export($error['message'], true),
                var_export($error['count'], true),
                var_export('/' . $error['path'], true),
            );
        }

        $php .= "\n";
        $php .= 'return [\'parameters\' => [\'ignoreErrors\' => $ignoreErrors]];';
        $php .= "\n";

        return $php;
    }

    public function encodeBaselineLoader(?string $comment, array $filePaths, string $indent): string
    {
        $php = "<?php declare(strict_types = 1);\n\n";

        if ($comment !== null) {
            $php .= "// $comment\n";
        }

        $php .= "return ['includes' => [\n";

        foreach ($filePaths as $filePath) {
            $php .= "{$indent}__DIR__ . '/$filePath',\n";
        }

        $php .= "]];\n";

        return $php;
    }

}

<?php declare(strict_types = 1);

namespace ShipMonk\PHPStan\Baseline\Handler;

use ShipMonk\PHPStan\Baseline\Exception\ErrorException;
use Throwable;
use function gettype;
use function is_array;
use function preg_match;
use function sprintf;
use function var_export;

class PhpBaselineHandler extends BaselineHandler
{

    protected function decodeBaselineFile(string $filepath): array
    {
        try {
            $decoded = (static fn () => require $filepath)();

            if (!is_array($decoded)) {
                throw new ErrorException("File '$filepath' must return array, " . gettype($decoded) . ' given');
            }

            return $decoded;

        } catch (ErrorException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ErrorException("Error while loading baseline file '$filepath': " . $e->getMessage(), $e);
        }
    }

    public function encodeBaseline(
        ?string $comment,
        array $errors,
        string $indent,
    ): string
    {
        $php = '<?php declare(strict_types = 1);';

        if ($comment !== null) {
            $php .= "\n\n";
            $php .= "// $comment";
        }

        $php .= "\n\n";
        $php .= "\$ignoreErrors = [];\n";

        foreach ($errors as $error) {
            if (isset($error['rawMessage'])) {
                $message = $error['rawMessage'];
                $messageKey = 'rawMessage';
            } else {
                $message = $error['message'];
                $messageKey = 'message';
            }

            $path = $this->isAbsolutePath($error['path'])
                ? var_export($error['path'], true)
                : '__DIR__ . ' . var_export('/' . $error['path'], true);

            $php .= sprintf(
                "\$ignoreErrors[] = [\n{$indent}%s => %s,\n{$indent}'count' => %d,\n{$indent}'path' => %s,\n];\n",
                var_export($messageKey, true),
                var_export($message, true),
                var_export($error['count'], true),
                $path,
            );
        }

        $php .= "\n";
        $php .= 'return [\'parameters\' => [\'ignoreErrors\' => $ignoreErrors]];';
        $php .= "\n";

        return $php;
    }

    public function encodeBaselineLoader(
        ?string $comment,
        array $filePaths,
        string $indent,
    ): string
    {
        $php = "<?php declare(strict_types = 1);\n\n";

        if ($comment !== null) {
            $php .= "// $comment\n\n";
        }

        $php .= "return ['includes' => [\n";

        foreach ($filePaths as $filePath) {
            $php .= "{$indent}__DIR__ . '/$filePath',\n";
        }

        $php .= "]];\n";

        return $php;
    }

    private function isAbsolutePath(string $path): bool
    {
        return preg_match('~^(?:[/\\\\]|[a-zA-Z]:[/\\\\]|[a-z][a-z0-9+.-]*://)~', $path) === 1;
    }

}

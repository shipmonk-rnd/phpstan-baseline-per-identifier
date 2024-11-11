# PHPStan baseline per error identifier

Split your [PHPStan baseline](https://phpstan.org/user-guide/baseline) into multiple files, one per error identifier:

```txt
baselines/
 ├─ loader.neon
 ├─ empty.notAllowed.neon
 ├─ foreach.nonIterable.neon
 ├─ identical.alwaysFalse.neon
 └─ if.condNotBoolean.neon
```

Each file looks like this:

```neon
# total 1 error

parameters:
	ignoreErrors:
		-
			message: '#^Construct empty\(\) is not allowed\. Use more strict comparison\.$#'
			path: ../app/index.php
			count: 1
```

## Installation:

```sh
composer require --dev shipmonk/phpstan-baseline-per-identifier
```

Use [official extension-installer](https://phpstan.org/user-guide/extension-library#installing-extensions) or just load the extension:

```neon
includes:
    - vendor/shipmonk/phpstan-baseline-per-identifier/extension.neon
```


## Usage:

Setup where your baseline files should be stored and include its loader:
```neon
# phpstan.neon.dist
includes:
    - baselines/loader.neon

parameters:
    shipmonkBaselinePerIdentifier:
        directory: %currentWorkingDirectory%/baselines
        indent: '    '
```

Prepare composer script to simplify generation:

```json
{
    "scripts": {
        "generate:baseline:phpstan": [
            "rm baselines/*.neon",
            "touch baselines/loader.neon",
            "@phpstan analyse --error-format baselinePerIdentifier"
        ]
    }
}
```

Regenerate the baselines:

```sh
composer generate:baseline:phpstan
```

## Migration from single baseline

1. `rm phpstan-baseline.neon` (and remove its include from `phpstan.neon.dist`)
2. `mkdir baselines`
3. `composer generate:baseline:phpstan`

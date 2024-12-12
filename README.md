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

## Usage

> [!IMPORTANT]
> _This usage is available since version 2.0. See legacy usage below if you are still using PHPStan 1.x_

Setup baselines loader, other files will be placed beside that file:
```neon
# phpstan.neon.dist
includes:
    - baselines/loader.neon # instead of traditional phpstan-baseline.neon
```

Run native baseline generation and split it into multiple files via our script:
```sh
vendor/bin/phpstan --generate-baseline=baselines/loader.neon && vendor/bin/split-phpstan-baseline baselines/loader.neon
```

_(optional)_ You can simplify generation with e.g. composer script:
```json
{
    "scripts": {
        "generate:baseline:phpstan": [
            "phpstan --generate-baseline=baselines/loader.neon",
            "split-phpstan-baseline baselines/loader.neon"
        ]
    }
}
```

<details>
<summary><h3>Legacy usage</h3></summary>

> _This usage is deprecated since 2.0, but it works in all versions. Downside is that it cannot utilize result cache_

Setup where your baseline files should be stored and include its loader:
```neon
# phpstan.neon.dist
includes:
    - vendor/shipmonk/phpstan-baseline-per-identifier/extension.neon # or use extension-installer
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
            "phpstan analyse --error-format baselinePerIdentifier"
        ]
    }
}
```

</details>

## Cli options
- ``--tabs`` to use tabs as indents in generated neon files

## PHP Baseline
- If the loader file extension is php, the generated files will be php files as well

## Migrating from single baseline

1. `rm phpstan-baseline.neon` (and remove its include from `phpstan.neon.dist`)
2. `mkdir baselines`
3. `touch baselines/loader.neon`  (and include it in `phpstan.neon.dist`)
4. Run the split script from above


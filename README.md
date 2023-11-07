# drupal-check [![Build](https://github.com/mglaman/drupal-check/actions/workflows/php.yml/badge.svg)](https://github.com/mglaman/drupal-check/actions/workflows/php.yml) [![CircleCI](https://circleci.com/gh/mglaman/drupal-check.svg?style=svg)](https://circleci.com/gh/mglaman/drupal-check) [![Latest release](https://img.shields.io/github/release/mglaman/drupal-check.svg)](https://github.com/mglaman/drupal-check/releases/latest)

Built on [PHPStan](https://github.com/phpstan/phpstan), this static analysis tool will check for correctness (e.g. using a class that doesn't exist), deprecation errors, and more.

Why? While there are many static analysis tools out there, none of them run with the Drupal context in mind. This allows checking contrib modules for deprecation errors thrown by core.

Are you ready for Drupal 9? Check out our [Drupal 9 Readiness](https://github.com/mglaman/drupal-check/wiki/Drupal-9-Readiness) instructions for details on how this tool can help.

## Sponsors

<a href="https://www.undpaul.de/"><img src="https://www.undpaul.de/themes/custom/undpaul3/logo.svg" alt="undpaul" width="250" /></a> <a href="https://www.optasy.com/"><img src="https://www.optasy.com/images/logo.svg" alt="undpaul" width="200" /></a>

[Would you like to sponsor?](https://github.com/sponsors/mglaman)

## Requirements

* PHP >=7.2

## Installation

You can install this in your project using Composer as a development dependency like so:

```
composer require mglaman/drupal-check --dev
```

You can also install this globally using Composer like so:

```
composer global require mglaman/drupal-check
```

Refer to Composer's documentation on how to ensure global binaries are in your PATH: https://getcomposer.org/doc/00-intro.md#manual-installation.

## Usage

```
php vendor/bin/drupal-check [options] [--] <path>...
```
Arguments:
* `path` - The Drupal code path(s) to inspect

Options:
* `--drupal-root[=DRUPAL-ROOT]` - Path to Drupal root.
* `--format[=FORMAT]` - Formatter to use: raw, table, checkstyle, json, or junit [default: "table"]
* `-d, --deprecations` - Check for deprecations
* `-a, --analysis` - Check code analysis
* `-s, --style` - Check code style
* `--php8` - Set PHPStan phpVersion for 8.1 (Drupal 10 requirement)
* `--memory-limit[=MEMORY-LIMIT]` - Memory limit for analysis
* `-e, --exclude-dir[=EXCLUDE-DIR]` - Directories to exclude. Separate multiple directories with a comma, no spaces.
* `--no-progress` - Do not show progress bar, only results
* `-h, --help` - Display this help message
* `-q, --quiet` - Do not output any message
* `-V, --version` - Display this application version
* `--ansi` - Force ANSI output
* `--no-ansi` - Disable ANSI output
* `-n, --no-interaction` - Do not ask any interactive question
* `-v|vv|vvv, --verbose` - Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Examples:

* Check the address contrib module:

```
php vendor/bin/drupal-check web/modules/contrib/address
```

* Check the address contrib module for deprecations:

```
php vendor/bin/drupal-check -d web/modules/contrib/address
```

* Check the address contrib module for analysis:

```
php vendor/bin/drupal-check -a web/modules/contrib/address
```

## Rollback update to PHPStan level 2 for deprecation analysis

drupal-check:1.4.0 set PHPStan's analysis level to 2 for deprecations and 6 for analysis. This ensures basic analysis
errors are fixed to provide the best deprecated code detection experience. You can read more about PHPStan's rule
levels here: https://phpstan.org/user-guide/rule-levels

If you do not want to run PHPStan at level 2 and only report deprecation messages, use the following instructions

```shell
composer remove mglaman/drupal-check
composer require  --dev phpstan/phpstan \
  phpstan/extension-installer \
  mglaman/phpstan-drupal \
  phpstan/phpstan-deprecation-rules
```

Create a `phpstan.neon` file with the following:

```neon
parameters:
	customRulesetUsed: true
	ignoreErrors:
		- '#\Drupal calls should be avoided in classes, use dependency injection instead#'
		- '#Plugin definitions cannot be altered.#'
		- '#Missing cache backend declaration for performance.#'
		- '#Plugin manager has cache backend specified but does not declare cache tags.#'

	# FROM mglaman/drupal-check/phpstan/base_config.neon
	reportUnmatchedIgnoredErrors: false
	excludePaths:
		- */tests/Drupal/Tests/Listeners/Legacy/*
		- */tests/fixtures/*.php
		- */settings*.php
		- */bower_components/*
		- */node_modules/*
```

You can copy this from the Upgrade Status module directly https://git.drupalcode.org/project/upgrade_status/-/blob/8.x-3.x/deprecation_testing_template.neon

## Drupal Check - VS Code Extension

You can run Drupal Check from VSCode using this extension: https://marketplace.visualstudio.com/items?itemName=bbeversdorf.drupal-check

The code can be found at: https://github.com/bbeversdorf/vscode-drupal-check

## License

[GPL v2](LICENSE.txt)

## Issues

Submit issues and feature requests here: https://github.com/mglaman/drupal-check/issues.

### Known Issues

There are conflicts with dependencies shared with other libraries that might be installed on a Drupal project:

* This tool does not work with BLT 9: https://github.com/mglaman/drupal-check/issues/9
* If you run into issues with other libraries, please submit an issue to this project.

## Contributing

See the [CONTRIBUTING.md](CONTRIBUTING.md).

## References

* [Writing better Drupal code with static analysis using PHPStan](https://glamanate.com/blog/writing-better-drupal-code-static-analysis-using-phpstan)
* [PHPStan: Find Bugs In Your Code Without Writing Tests!](https://medium.com/@ondrejmirtes/phpstan-2939cd0ad0e3)
* [Online PHPStan Analyzer](https://phpstan.org/)


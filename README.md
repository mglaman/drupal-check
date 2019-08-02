# drupal-check [![Build Status](https://travis-ci.com/mglaman/drupal-check.svg?branch=master)](https://travis-ci.com/mglaman/drupal-check) [![CircleCI](https://circleci.com/gh/mglaman/drupal-check.svg?style=svg)](https://circleci.com/gh/mglaman/drupal-check) [![Latest release](https://img.shields.io/github/release/mglaman/drupal-check.svg)](https://github.com/mglaman/drupal-check/releases/latest)

Built on [PHPStan](https://github.com/phpstan/phpstan), this static analysis tool will check for correctness (e.g. using a class that doesn't exist), deprecation errors, and more.

Why? While there are many static analysis tools out there, none of them run with the Drupal context in mind. This allows checking contrib modules for deprecation errors thrown by core.

Are you ready for Drupal 9? Check out our [Drupal 9 Readiness](https://github.com/mglaman/drupal-check/wiki/Drupal-9-Readiness) instructions for details on how this tool can help.

Don't want to run Drupal Check on your own? We offer a hosted version in [Centarro Toolbox](https://www.centarro.io/products/centarro-toolbox) for folks who want it done for them.

## Requirements

* PHP >=7.1

## Installation

The easiest way to install is by downloading the latest PHAR and putting it into your path. For example:

```
curl -O -L https://github.com/mglaman/drupal-check/releases/latest/download/drupal-check.phar
mv drupal-check.phar /usr/local/bin/drupal-check
chmod +x /usr/local/bin/drupal-check
```

### Composer

You can install this in your project using Composer as a development dependency like so:

```
composer require mglaman/drupal-check --dev
```

You can also install this globally using Composer like so:

```
composer global require mglaman/drupal-check
```

Refer to Composer's documentation on how to ensure global binaries are in your PATH: https://getcomposer.org/doc/00-intro.md#manual-installation.

Note: you can also install this locally to your project and run it from that project's Composer bin directory.

### Build From Source

Follow these instructions to build from source: https://github.com/mglaman/drupal-check/wiki/Build-From-Source.

## Usage

This tool works on all Drupal code, but must be executed within the root directory of a Drupal project..

### 1. cd into a Drupal Directory

You can run this tool within any Drupal project. But, for best results, create a fresh Drupal directory on the latest Drupal:

```
composer create-project drupal-composer/drupal-project:8.x-dev drupal --no-interaction --stability=dev
cd drupal
```

### 2. Run drupal-check

Usage:

  ```
  drupal-check [OPTIONS] [DIRS]
  ```

Arguments:

* `OPTIONS` - See "Options" for allowed values. Specify multiples in sequence, e.g. `-ad`.
* `DIRS` - One or more directories within the root of a Drupal project.

Options:

* `-a` Check analysis
* `-d` Check deprecations (default)
* `--drupal-root` Path to Drupal root. Fallback option if drupal-check could not identify Drupal root from the provided path(s).

Examples:

* Check the address contrib module:

  ```
  drupal-check web/modules/contrib/address
  ```

* Check the address contrib module for deprecations:

  ```
  drupal-check -d web/modules/contrib/address
  ```

* Check the address contrib module for analysis:

  ```
  drupal-check -a web/modules/contrib/address
  ```

* Check the address contrib module for both deprecations and analysis:

  ```
  drupal-check -ad web/modules/contrib/address
  ```
  
## Drupal Check - VS Code Extension

You can run Drupal Check from VSCode using this extension: https://marketplace.visualstudio.com/items?itemName=bbeversdorf.drupal-check

The code can be found at: https://github.com/bbeversdorf/vscode-drupal-check

## License

[GPL v2](LICENSE.txt)

## Roadmap

See what feature requests are most popular in the Issue queue: https://github.com/mglaman/drupal-check/issues.

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


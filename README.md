# drupal-check

Built on [phpstan](https://github.com/phpstan/phpstan), this static analysis tool will check for correctness (e.g. using a class that doesn't exist), deprecation errors, and more.

Why? While there are many static analysis tools out there, none of them run with the Drupal context in mind. This allows checking contrib modules for deprecation errors thrown by core.

Are you ready for Drupal 9? Check out our [Drupal 9 Readiness](https://github.com/mglaman/drupal-check/wiki/Drupal-9-Readiness) instructions for details on how this tool can help.

## Requirements

* PHP >=7.1

## Installation

The easiest way to install is by downloading the latest PHAR and putting it into your path. For example:

```
curl -O -L https://github.com/mglaman/drupal-check/releases/download/1.0.3/drupal-check.phar
mv drupal-check.phar /usr/local/bin/drupal-check
```

### Composer

You can also install this globally using composer like so:

```
composer global require mglaman/drupal-check
```

Refer to Composer's documentation on how to ensure global binaries are in your PATH: https://getcomposer.org/doc/00-intro.md#manual-installation.

Note: you can also install this locally to your project and run it from that project's composer bin directory.

### Build From Source

You can also clone this repository and build the PHAR from source:

First, install humbug/box:

```
composer global require humbug/box
```

Next, clone the repo and build:

```
git clone https://github.com/mglaman/drupal-check.git
cd drupal-check
composer install --prefer-dist
box -v
```

## Usage

This tool works on contrib modules within a Drupal repository.

### 1. cd into a Drupal Directory

You can run this tool within any Drupal project. But, for best results, create a fresh Drupal directory on the latest Drupal:

```
composer create-project drupal-composer/drupal-project:8.x-dev drupal --no-interaction --stability=dev
cd drupal
```

### 2. Run drupal-check

Usage:

  ```
  drupal-check [OPTIONS] [MODULE]
  ```

Options:

* `-a` Check analysis
* `-d` Check deprecations

Examples:

* Check the address contrib module: `drupal-check web/modules/contrib/address`
* Check the address contrib module for deprecations: `drupal-check -d web/modules/contrib/address`
* Check the address contrib module for analysis: `drupal-check -a web/modules/contrib/address`
* Check the address contrib module for both deprecations and analysis: `drupal-check -ad web/modules/contrib/address`

## License

[GPL v2](LICENSE.txt)

## Roadmap

See what feature requests are most popular in the Issue queue: https://github.com/mglaman/drupal-check/issues.

## Issues

Submit issues and feature requests here: https://github.com/mglaman/drupal-check/issues.

### Known Issues

* This tool does not work with BLT 9: https://github.com/mglaman/drupal-check/issues/9

## Contributing

See the [CONTRIBUTING.md](CONTRIBUTING.md).

## References

* [Writing better Drupal code with static analysis using PHPStan](https://glamanate.com/blog/writing-better-drupal-code-static-analysis-using-phpstan)
* [PHPStan: Find Bugs In Your Code Without Writing Tests!](https://medium.com/@ondrejmirtes/phpstan-2939cd0ad0e3)
* [Online PHPStan Analyzer](https://phpstan.org/)


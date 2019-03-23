# drupal-check [![Build Status](https://travis-ci.com/mglaman/drupal-check.svg?branch=master)](https://travis-ci.com/mglaman/drupal-check) [![Latest release](https://img.shields.io/github/release/mglaman/drupal-check.svg)](https://github.com/mglaman/drupal-check/releases/latest)

## Requirements

You must have PHP 7.1 or greater, which is a requirement of PHPStan, the static analyzer backing this tool.

## Usage

For the best experience with this tool is best used against a vanilla Drupal project. It will work against custom Drupal 
projects, but there can be conflicts against other development tools added to projects.

Check deprecations

```
# Example: Against address contrib
drupal-check /path/to/drupal8/modules/contrib/address
drupal-check -d /path/to/drupal8/modules/contrib/address
```

Check static analysis

```
# Example: Against address contrib
drupal-check -a /path/to/drupal8/modules/contrib/address
```

Check static analysis and deprecations

```
# Example: Against address contrib
drupal-check -ad /path/to/drupal8/modules/contrib/address
```

Coming soon: code style w/ phpcs integration.

## Install

Download the latest Phar from https://github.com/mglaman/drupal-check/releases/latest, move into your path, profit!

Or, something like:

```
curl -sO $(curl -s https://api.github.com/repos/mglaman/drupal-check/releases/latest | grep "browser_download_url.*phar" | cut -d : -f 2,3 | tr -d \") \
&& chmod 755 drupal-check.phar \
&& sudo mv drupal-check.phar /usr/local/bin/drupal-check
```

## Building

The phar is built using [humbug/box](https://github.com/humbug/box)

macOS with bew:

```
brew tap humbug/box
brew install box
box -v
```

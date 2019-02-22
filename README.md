# drupal-check

## Usage

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
curl -O -L https://github.com/mglaman/drupal-check/releases/download/1.0.0/drupal-check.phar
ln -s $(pwd)/drupal-check.phar /usr/local/bin/drupal-check
drupal-check /path/to/drupal/code_to_analyze
```

## Building

The phar is built using humbug/box
```
brew tap humbug/box
brew install box
box -v
```

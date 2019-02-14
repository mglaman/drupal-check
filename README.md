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

```
git@github.com:mglaman/drupal-check.git
cd drupal-check
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

# Changelog

## 2.0.0 (2026-03-23)

### Added
- Added `.lando.yml` for local development and verification with PHP 8.4.
- Added `--php-version` CLI option, with `--php8` kept as a deprecated alias to `--php-version=80400`.

### Changed
- Raised platform and dependency requirements to modern stack:
  - PHP `^8.4`
  - PHPStan `^2.1`
  - phpstan-drupal `^2.0`
  - phpstan-deprecation-rules `^2.0`
  - Symfony Console/Process `^7.0`
- Updated GitHub Actions matrix to PHP 8.4/8.5 and Drupal 11.3.0.
- Updated docs and issue template to reflect Drupal 11.3 and PHP 8.4 baseline.
- Updated test fixture module metadata to `core_version_requirement: ^10 || ^11`.

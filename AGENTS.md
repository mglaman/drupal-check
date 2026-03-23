# Agents Guide

This repository is maintained with an automation-friendly workflow.

## Runtime Baseline
- PHP: `8.4+`
- Drupal target: `11.3`
- PHPStan: `2.x`

## Local Environment
- Preferred local runtime: Lando (`.lando.yml`).
- Start environment: `lando start`
- Composer in container: `lando composer <command>`
- PHP in container: `lando php <command>`

## Verification Commands
- `lando composer validate --strict`
- `lando composer update --with-all-dependencies`
- `lando phpcs src`
- `lando phpstan analyse src`

## Notes
- Keep `composer.json` and `composer.lock` in sync.
- Prefer forward-compatible changes; avoid reintroducing legacy PHP 7/Drupal 8 assumptions.

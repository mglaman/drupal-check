#!/usr/bin/env bash
# Note: must be run from the root of the project.
circleci config process .circleci/config.yml > .circleci/config_local.yml

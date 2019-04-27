#!/usr/bin/env bash
# Note: must be run from the root of the project.
source .circleci/generate-local-config.sh
circleci local execute --job ${1:-build} --config .circleci/config_local.yml

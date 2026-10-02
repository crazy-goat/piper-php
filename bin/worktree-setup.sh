#!/usr/bin/env bash
# Prepare a fresh worktree: git submodule (piper1-gpl) and Composer dependencies.
# Called by bin/worktree.sh after a new worktree is created.
# No test containers are started: the suites run on the host. Unit tests need only Composer
# dependencies; integration tests also need libs/ (make build-libs) and a voice model (make test-model).
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

git submodule update --init --recursive
composer install --no-interaction --prefer-dist

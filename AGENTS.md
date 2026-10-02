# AGENTS.md

Project commands and specifics for piper-php, a PHP text-to-speech library that drives
[Piper](https://github.com/OHF-Voice/piper1-gpl) (libpiper) through FFI.
The development process (issue, worktree, review, PR, merge) is in
[docs/workflow.md](docs/workflow.md), the release process in
[docs/release-workflow.md](docs/release-workflow.md). The default branch is `master`.

Everything is written in English (code, comments, docs, commits, issues). The Polish sample
input for the `pl_PL-gosia-medium` voice in `examples/*.php` is deliberate.

## Layout

| Path | Content |
|---|---|
| `src/` | Library, namespace `CrazyGoat\PiperTTS\` (`PiperTTS`, `LoadedModel`, `AudioChunk`, `VoiceInfo`, `Exception/`) |
| `bin/piper-tts` | CLI: `install-deps`, `list`, `download`, `installed` |
| `stubs/` | PHPStan stubs for `FFI` and `FFI\CData` |
| `tests/Unit/` | Unit suite, needs no libraries |
| `tests/Integration/` | Integration suite (`@group integration`), needs `libs/` and a voice model |
| `examples/` | Runnable examples |
| `piper1-gpl/` | Git submodule (GPL-3.0): libpiper sources. Never edited here |
| `libs/`, `models/` | Built libraries and voice models (gitignored) |
| `bin/*.sh` | `lint.sh`, `pick-issue.sh`, `worktree*.sh` |

## Commands

PHP 8.2+ (CI runs 8.2 to 8.5) with `ext-ffi`.

```bash
git submodule update --init --recursive   # piper1-gpl, needed only to build libpiper
composer install

# Lint: composer validate/audit + php-cs-fixer + PHPStan + Rector (dry run) + shellcheck
bin/lint.sh                # check only; runs every step; `composer lint` / `make lint` call it
bin/lint.sh --fix          # fixers first, then the checks; `composer lint:fix`
composer cs         # PHP-CS-Fixer, check only
composer phpstan    # PHPStan
composer rector     # Rector, dry run

# Tests
composer test              # unit suite (vendor/bin/phpunit --testsuite unit)
make build-libs            # build libpiper with cmake and copy it to libs/ (long; needs cmake >= 3.26)
make test-model            # download the small test voice into models/
make test-integration      # integration suite (needs libs/ and the test model)
make test                  # unit + integration
```

`bin/lint.sh` needs `shellcheck` installed (CI installs a pinned release); a missing tool is a
failure. There is no Dockerfile and no first-party C/C++ code, so hadolint and clang-format
are not part of it.

## Conventions

- `declare(strict_types=1)` in every PHP file. PHP-CS-Fixer (`@PER-CS2.0`), PHPStan level 9
  without a baseline, Rector for PHP 8.2 (config in `.php-cs-fixer.dist.php`, `phpstan.neon`,
  `rector.php`). Raise levels, never lower them.
- Dev dependencies are pinned in `composer.json` and locked in `composer.lock` (committed).
  CI uses `composer install`, never `composer update`. `config.platform.php` is `8.2.99`, so the
  lock stays installable on the oldest supported PHP.
- `LoadedModel` owns native memory; a change there needs a test for double free and for use
  after `free()`.
- Commit style: Conventional Commits. Branches and worktrees follow `docs/workflow.md`.
- Update `CHANGELOG.md` under `## [Unreleased]` for every user-visible change.
- No Docker Compose in this repository. `bin/worktree-setup.sh` initializes the submodule and
  installs Composer dependencies; it does not build libpiper (run `make build-libs`).

## CI

`.github/workflows/ci.yml` runs on pull requests to `master` and on pushes to `master`. The
`changes` job detects documentation-only changes and the `docs` job checks them fast. `lint`
(only `bin/lint.sh`), `test-unit` (PHP 8.2 to 8.5), `build-piper-glibc`, `build-piper-musl` and
`test-integration` run only for code changes. `ci-ok` aggregates the results and is the check to require.

Tag pushes (`v*`) run `.github/workflows/release.yml`: it builds libpiper for glibc and musl,
packages the five archives that `piper-tts install-deps` downloads, and creates the GitHub
Release from the `CHANGELOG.md` section.

## License

The PHP code is MIT. libpiper (piper1-gpl) and the espeak-ng data are GPL-3.0 and are only built
or loaded, never copied into `src/`. Do not copy code from `piper1-gpl/` into the PHP sources.

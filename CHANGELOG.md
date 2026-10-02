# Changelog

All notable changes to this project are documented here, following
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/). This package uses
[Semantic Versioning](https://semver.org/); before 1.0.0, minor versions may change the API.

## [Unreleased]

### Changed

- libpiper (the `piper1-gpl` submodule) is upgraded from v1.4.1 to v1.8.0 (ONNX Runtime stays at
  1.22.0 for glibc). The `piper.h` changes are additive (`piper_create_with_options`,
  `piper_version`, `EXPORT_SYMBOL`), so the FFI definitions and the public PHP API are
  unchanged. Closes #21; replaces Dependabot PR #3.
- The musl build (CI and release) runs in `alpine:3.23` and links the Alpine `onnxruntime` package
  (1.23.0) instead of the prebuilt glibc-only ONNX Runtime, which no longer links on musl since
  libpiper builds the `piper` executable too. `libpiper-linux-musl-x86_64.tar.gz` and
  `libonnxruntime-linux-musl-x86_64.tar.gz` now work together on Alpine: extract both into `libs/`.
  The ONNX Runtime archive contains the libraries it needs (abseil, protobuf, ICU and others), and all
  libraries have an `$ORIGIN` rpath. The glibc assets are unchanged.
- libpiper now installs the espeak-ng data to `share/espeak-ng-data` instead of `espeak-ng-data`;
  `make build-libs`, CI and the release workflow use the new path.

## [0.2.0] - 2026-10-02

### Added

- `LICENSE` (MIT) and a README License section explaining that libpiper and the espeak-ng data
  (piper1-gpl) are GPL-3.0.
- `bin/lint.sh`, the single entry point for every static check: composer validate and audit,
  PHP-CS-Fixer, PHPStan, Rector and shellcheck. `--fix` applies the fixers first. `composer lint`,
  `composer lint:fix` and `make lint` call it. Closes #1.
- The shared development process: `docs/workflow.md`, `docs/release-workflow.md`, `AGENTS.md`,
  `bin/pick-issue.sh`, `bin/worktree*.sh`, a pull request template and Dependabot.
- `release.yml` creates the GitHub Release from the `CHANGELOG.md` section and attaches the
  libpiper archives.

### Changed

- PHP-CS-Fixer (PER-CS 2.0) replaces PHP_CodeSniffer; `phpcs.xml.dist` is gone.
- Dev dependencies are locked: `composer.lock` is committed and CI runs `composer install`.
- CI: the `lint` job runs only `bin/lint.sh`; jobs run only for code changes; unit tests run on
  PHP 8.2 to 8.5; an aggregate `ci-ok` check is the one to require. CI no longer runs on tag
  pushes.
- Releases are built by `release.yml` instead of `ci.yml`. The GitHub Release notes now come from
  `CHANGELOG.md`; the five archives are the same as in 0.1.8.
- The PHP sources and `bin/piper-tts` are reformatted to PER-CS 2.0. There is no change in
  behaviour or in the public API.

## [0.1.8] - 2026-03-31

### Fixed

- Release archives keep the symlinks of the `libonnxruntime` shared libraries.
- Locating the `libonnxruntime` files when packaging (glibc and musl builds).

## [0.1.7] - 2026-03-31

### Added

- Pre-built libpiper and ONNX Runtime archives for musl (Alpine), next to the glibc ones.
- `piper-tts install-deps` detects the package version from `composer.lock` and downloads the
  matching release.

### Fixed

- `CMAKE_INSTALL_PREFIX` is an absolute path, which fixes duplicated paths in the libpiper build.
- The `libonnxruntime` archive layout is flat, and CI installs cmake 3.26 or newer.

## [0.1.6] - 2026-03-31

Tag only, no GitHub Release was published.

### Added

- Unit and integration test suites (PHPUnit), with `make test`, `make test-unit`,
  `make test-integration` and composer test scripts.
- CI builds libpiper once and shares the artifacts between jobs; the release is created on a tag.

### Fixed

- Double free in `LoadedModel` when a model was freed explicitly and again by the destructor.
- Warning from `file_get_contents` in `VoiceInfo`.
- Constructor properties are `readonly`; lint issues in `tests/`.

### Changed

- README: PHP version badge, `install-deps` usage and the current Piper repository
  (OHF-Voice/piper1-gpl).

## [0.1.5] - 2026-03-30

### Changed

- The `piper-install` script is merged into the `piper-tts` CLI, and the command is renamed to
  `piper-tts install-deps`.
- `require-dev` restored in `composer.json`; CI uses `composer update` because no lock file is
  committed.

## [0.1.4] - 2026-03-30

### Changed

- The library download script is renamed to `vendor/bin/piper-install`; the composer
  post-install scripts are removed.
- `composer.lock` is no longer committed; README describes `piper-install`.

## [0.1.3] - 2026-03-30

### Added

- `post-install.php` is registered as a composer binary, so it runs as `vendor/bin/piper-install`.

## [0.1.2] - 2026-03-30

### Added

- `make build-libs` and `make examples` for local development, with automatic voice model
  download.

### Changed

- Examples save their output next to the script, with a matching name.
- `libs/` is gitignored.

## [0.1.1] - 2026-03-30

Tag only, no GitHub Release was published.

### Added

- First version: `PiperTTS`, `LoadedModel` (`speak`, `speakStreaming`, `warmUp`), `AudioChunk`,
  `VoiceInfo`, the exception hierarchy, the `piper-tts` CLI for voice models, examples and README.
- The `piper1-gpl` submodule, built into libpiper with cmake.
- Linting with PHPStan, PHP_CodeSniffer and Rector, and a CI workflow.
- Release archives built on tags: `libpiper-linux-x86_64.tar.gz`,
  `libonnxruntime-linux-x86_64.tar.gz` (all versioned files, debug symbols stripped) and
  `espeak-ng-data.tar.gz`.
- A post-install script that downloads the pre-built libraries for the detected architecture.

[Unreleased]: https://github.com/crazy-goat/piper-php/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/crazy-goat/piper-php/compare/v0.1.8...v0.2.0
[0.1.8]: https://github.com/crazy-goat/piper-php/compare/v0.1.7...v0.1.8
[0.1.7]: https://github.com/crazy-goat/piper-php/compare/v0.1.6...v0.1.7
[0.1.6]: https://github.com/crazy-goat/piper-php/compare/v0.1.5...v0.1.6
[0.1.5]: https://github.com/crazy-goat/piper-php/compare/v0.1.4...v0.1.5
[0.1.4]: https://github.com/crazy-goat/piper-php/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/crazy-goat/piper-php/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/crazy-goat/piper-php/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/crazy-goat/piper-php/releases/tag/v0.1.1

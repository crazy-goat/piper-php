# Changelog

All notable changes to this project are documented here, following
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/). This package uses
[Semantic Versioning](https://semver.org/); before 1.0.0, minor versions may change the API.

## [Unreleased]

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

## [0.1.8] - 2026-03-31

### Fixed

- Release archives keep the symlinks of the `libonnxruntime` shared libraries.
- Locating the `libonnxruntime` files when packaging (glibc and musl builds).

## [0.1.7] - 2026-03-31

### Added

- Pre-built libpiper and ONNX Runtime archives for musl (Alpine), next to the glibc ones.
- `piper-tts install-deps` detects the package version from `composer.lock` and downloads the
  matching release.
- Unit and integration test suites (PHPUnit), with `make test`, `make test-unit` and
  `make test-integration`.

### Fixed

- Double free in `LoadedModel` when a model was freed explicitly and again by the destructor.
- `CMAKE_INSTALL_PREFIX` is an absolute path, which fixes duplicated paths in the libpiper build.
- Warning from `file_get_contents` in `VoiceInfo`.

## [0.1.6] - 2026-03-31

Tag only, no GitHub Release was published.

### Changed

- CI builds libpiper once and shares the artifacts between jobs; the release is created on a tag.

## [0.1.5] - 2026-03-30

### Changed

- Documentation: the README describes `piper-tts install-deps` and the current Piper repository
  (OHF-Voice/piper1-gpl).
- `composer.lock` is no longer committed.

## [0.1.4] - 2026-03-30

### Added

- `vendor/bin/piper-tts install-deps` downloads the pre-built libraries from the GitHub Release.

## [0.1.3] - 2026-03-30

### Fixed

- Release archives contain all versioned `libonnxruntime` files.

## [0.1.2] - 2026-03-30

### Added

- Release assets are split into `libpiper-linux-x86_64.tar.gz`, `libonnxruntime-linux-x86_64.tar.gz`
  and `espeak-ng-data.tar.gz`, with debug symbols stripped.
- `make build-libs` and `make examples` for local development.

## [0.1.1] - 2026-03-30

Tag only, no GitHub Release was published.

### Added

- First version: `PiperTTS`, `LoadedModel` (`speak`, `speakStreaming`, `warmUp`), `AudioChunk`,
  `VoiceInfo`, the exception hierarchy, the `piper-tts` CLI for voice models, and examples.
- CI with PHP_CodeSniffer, PHPStan and Rector.

[Unreleased]: https://github.com/crazy-goat/piper-php/compare/v0.1.8...HEAD
[0.1.8]: https://github.com/crazy-goat/piper-php/compare/v0.1.7...v0.1.8
[0.1.7]: https://github.com/crazy-goat/piper-php/compare/v0.1.6...v0.1.7
[0.1.6]: https://github.com/crazy-goat/piper-php/compare/v0.1.5...v0.1.6
[0.1.5]: https://github.com/crazy-goat/piper-php/compare/v0.1.4...v0.1.5
[0.1.4]: https://github.com/crazy-goat/piper-php/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/crazy-goat/piper-php/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/crazy-goat/piper-php/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/crazy-goat/piper-php/releases/tag/v0.1.1

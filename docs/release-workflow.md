# Release workflow

One milestone = one release. Versions follow
[Semantic Versioning](https://semver.org/) and the changelog follows
[Keep a Changelog](https://keepachangelog.com/). Tags are `vX.Y.Z`.

Everything is written in **English**, including release notes.

## 1. Release gate

A release is ready when the milestone has **no open issues**:

```bash
gh api repos/{owner}/{repo}/milestones --jq '.[] | select(.title=="vX.Y.Z") | {title, open_issues, closed_issues}'
```

- Open issues that will not make it: move them to the next milestone
  (`gh issue edit <N> --milestone vX.Y.(Z+1)`).
- The default branch must have a green `ci-ok`.

## 2. Choose the version

| Change | Bump |
|---|---|
| Bug fixes only | patch (`1.2.3` → `1.2.4`) |
| New, backward compatible features | minor (`1.2.3` → `1.3.0`) |
| Breaking changes | major (`1.2.3` → `2.0.0`) |

Before `1.0.0`, breaking changes bump the minor version. The milestone title
already holds the planned version. Change the milestone title if the plan changed.

## 3. Prepare the CHANGELOG (pull request)

```bash
git switch -c chore/release-vX.Y.Z
```

In `CHANGELOG.md`:

- Rename `## [Unreleased]` to `## [X.Y.Z] - YYYY-MM-DD`.
- Add a fresh empty `## [Unreleased]` above it.
- Group entries under Added, Changed, Deprecated, Removed, Fixed, Security.
- Update the compare links at the bottom, if the file has them.
- No version is stored in any file (`composer.json` has no `version` field; the tag is
  the version).

Open a PR titled `chore: release vX.Y.Z`, wait for `ci-ok`, squash merge.

## 4. Tag

Tag the merge commit on the default branch with an **annotated** tag:

```bash
git switch <default-branch> && git pull --ff-only
git tag -a vX.Y.Z -m "Release vX.Y.Z"
git push origin vX.Y.Z
```

## 5. GitHub Release

Pushing the tag starts `.github/workflows/release.yml`. GitHub runs the workflow
file from the **tagged commit**, so the release PR with the `## [X.Y.Z]` section
must be **merged before** you tag.

The workflow builds libpiper from the `piper1-gpl` submodule on Linux (`ubuntu-latest`, glibc)
and packages three archives:

| Asset | Content |
|---|---|
| `libpiper-linux-x86_64.tar.gz` | `libpiper.so` (glibc) |
| `libonnxruntime-linux-x86_64.tar.gz` | ONNX Runtime shared libraries (glibc) |
| `espeak-ng-data.tar.gz` | espeak-ng phoneme data |

There is no musl (Alpine) build. Microsoft publishes no musl build of ONNX Runtime, which
libpiper links, so there is nothing to build a musl `libpiper.so` against. See the `Removed`
entry in `CHANGELOG.md`.

It then creates the GitHub Release with `gh release create --verify-tag`, using the
notes from the matching `CHANGELOG.md` section, and attaches the archives. It fails
when the section is missing or empty. The assets are what `vendor/bin/piper-tts
install-deps` downloads for the installed package version, so a release without them
breaks installation.

libpiper and espeak-ng data are GPL-3.0 (see the License section of the README). The
archives are binary distributions of GPL code built from the public `piper1-gpl`
submodule, which is the corresponding source.

GitHub rejects release notes longer than 125000 characters. The workflow cuts the notes
at 120000 bytes, at a line boundary, and adds a link to `CHANGELOG.md` at the tag.

Tags with a `-` (for example `v0.2.0-rc.1`) are published as pre-releases. If the release
already exists (for example after a failed upload), re-running the workflow uploads the
archives to it with `--clobber`, publishes it if an interrupted run left it as a draft, and
leaves its notes unchanged.

```bash
gh run watch
gh release view vX.Y.Z
```

## 6. Close the milestone

```bash
gh api -X PATCH repos/{owner}/{repo}/milestones/<number> -f state=closed
```

Make sure the next milestone `vX.Y.(Z+1)` (or the next minor) exists.

## 7. After the release

- Check that the install instructions work with the new version: `composer require
  crazy-goat/piper-php:^X.Y` followed by `vendor/bin/piper-tts install-deps` downloads all assets.
- If something is wrong, do not move the tag. Fix forward with a patch release.

## Checklist

- [ ] Milestone has no open issues, CI is green
- [ ] CHANGELOG section `[X.Y.Z] - date` written, `[Unreleased]` is empty
- [ ] Release PR merged
- [ ] Annotated tag `vX.Y.Z` pushed
- [ ] GitHub Release exists with the CHANGELOG notes and the three libpiper/ONNX Runtime/espeak-ng archives
- [ ] Milestone closed, next milestone exists

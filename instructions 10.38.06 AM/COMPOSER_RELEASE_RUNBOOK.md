# Composer Release Runbook (Packagist-Safe)

Use this in any PHP package repo to avoid version/tag mismatches like `v0.1.2` vs `0.1.2`.

## Why Mismatches Happen

- `composer.json` has a hardcoded `version` that conflicts with git tags.
- A repo has both tag styles for the same version (for example both `0.1.2` and `v0.1.2`).

## Rules

1. Do not set `version` in `composer.json` for VCS/Packagist libraries.
2. Use exactly one git tag style per repository.
3. Never create both `X.Y.Z` and `vX.Y.Z` for the same release.

## One-Time Cleanup (Existing Repo)

### 1) Remove composer version field

Delete this key from `composer.json` if present:

```json
"version": "0.1.3"
```

### 2) Detect current tag style and duplicates

```bash
git tag --list | sort -V
git ls-remote --tags origin | awk '{print $2}' | sed -E 's#refs/tags/##; s#\^\{\}##' | sort -V | uniq
```

### 3) Remove duplicate-style tags (choose one style and keep it)

If you keep plain tags (`X.Y.Z`):

```bash
# Example duplicate: v0.1.2

git tag -d v0.1.2
git push origin :refs/tags/v0.1.2
```

If you keep prefixed tags (`vX.Y.Z`):

```bash
# Example duplicate: 0.1.2

git tag -d 0.1.2
git push origin :refs/tags/0.1.2
```

## Repeatable Release Flow

Set version once:

```bash
VERSION="0.1.3"
```

Build/package steps used in this project:

```bash
npm run build
composer install --no-dev
npm run plugin-zip
```

Commit and tag (plain-tag example):

```bash
git add -A
git commit -m "Release ${VERSION}"
git tag -a "${VERSION}" -m "Release ${VERSION}"
git push origin HEAD
git push origin "${VERSION}"
```

Commit and tag (v-tag example):

```bash
git add -A
git commit -m "Release ${VERSION}"
git tag -a "v${VERSION}" -m "Release v${VERSION}"
git push origin HEAD
git push origin "v${VERSION}"
```

Create GitHub release with attached zip (plain-tag example):

```bash
gh release create "${VERSION}" \
  --title "${VERSION}" \
  --notes "Release ${VERSION}" \
  navigation-block-enhancements.zip
```

Create GitHub release with attached zip (v-tag example):

```bash
gh release create "v${VERSION}" \
  --title "v${VERSION}" \
  --notes "Release v${VERSION}" \
  your-plugin.zip
```

## Verification Checklist

Run after tagging:

```bash
git tag --list | sort -V
git ls-remote --tags origin | awk '{print $2}' | sed -E 's#refs/tags/##; s#\^\{\}##' | sort -V | uniq
```

Check that:

- Only one tag style exists.
- New tag exists on origin.
- `composer.json` does not contain `"version"`.

## Notes For This Repository

Current convention in this repo is plain tags (`X.Y.Z`), not `vX.Y.Z`.

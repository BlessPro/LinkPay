# Versioning Guide

## Standard
- This project uses Semantic Versioning: `MAJOR.MINOR.PATCH`.
- Canonical version lives in the root `VERSION` file.

## Bump Rules
- `PATCH`: bug fixes, small safe improvements.
- `MINOR`: new features, backward-compatible behavior changes.
- `MAJOR`: breaking changes.

## Release Workflow
1. Update `VERSION` (example: `0.1.0` -> `0.2.0`).
2. Move relevant `Unreleased` notes in `CHANGELOG.md` into a new version heading.
3. Commit changes.
4. Create git tag:
   - `git tag -a v0.2.0 -m "Release v0.2.0"`
5. Push code + tags:
   - `git push origin main`
   - `git push origin --tags`

## Runtime Version
- `config('app.version')` reads from:
  1. `APP_VERSION` env variable
  2. `VERSION` file fallback
- `/version.json` exposes the active version for clients and diagnostics.


# Changelog

All notable changes to this project will be documented in this file.

## 1.0.0 - 2026-02-11

- Initial release.

## 1.0.1 - 2026-02-12

- Add addon logo + README badges.
- Use a custom Statamic Utility icon.

## 1.0.2 - 2026-02-12

- Fix CP Utility icon registration/loading.
- Update the README logo to match the provided artwork.

## 1.0.3 - 2026-02-12

- Replace the logo with a noindex + redirect design (SVG + 600×600 PNG thumbnail).
- Use the same logo SVG for the CP Utility icon.

## 1.0.4 - 2026-02-15

- Register the middleware globally so redirect/noindex still works when Statamic frontend routes are disabled.

## 1.0.5 - 2026-02-15

- Apply noindex headers/meta even for error responses (404/403/500) when frontend routes are disabled.

## 1.0.6 - 2026-02-15

- Ensure the middleware is registered even if the HTTP kernel is resolved before providers.
- Exclude Statamic actions routes (`/!/*`, including `!/forms`) from redirect/noindex.
- When Statamic frontend routes are disabled, redirect all frontend requests (GET/HEAD) instead of rendering 404.

## 1.0.7 - 2026-02-15

- Save the redirect URL even when redirect is toggled off in the CP Utility.
- Redirect all frontend GET/HEAD requests when enabled (CP, `!/` actions, and GraphQL excluded).
- Add a route fallback so redirect/noindex still applies when Statamic frontend routes are disabled.

## 1.0.8 - 2026-02-16

- Refactor middleware registration to resolve/register on boot for deterministic global coverage.
- Keep `web`/`statamic.web` middleware group registration as a deduped fallback.
- Fix regression where frontend 404/unmatched responses could miss noindex behavior.

## 1.0.9 - 2026-02-16

- Add explicit package version metadata in `composer.json` for release consistency.

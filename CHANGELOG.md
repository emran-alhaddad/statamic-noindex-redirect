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

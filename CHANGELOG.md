# Changelog

All notable changes to this project will be documented in this file.

## 1.1.2 - 2026-06-08

- Fix the CP Utility being unstyled and non-interactive on Statamic 6. Statamic 6 renders a utility view by compiling its HTML as a Vue component template (`DynamicHtmlRenderer`), which strips `<script>` tags, and its redesigned control panel no longer ships the legacy CP CSS classes (`card`, `btn-primary`, `input-text`, …). The utility view is now fully self-contained: inline styles instead of CP classes, and no JavaScript. The Redirect URL field is always visible, so the form works without the previous toggle script. Renders and functions identically on Statamic 4, 5, and 6.

## 1.1.1 - 2026-06-08

- Fix CP Utility crash on Statamic 6 (`View [partials.breadcrumb] not found`): the `statamic::partials.breadcrumb` and `statamic::partials.flash` partials were removed in Statamic 6. They are now inlined in the utility view so it renders on Statamic 4, 5, and 6.
- Show the Redirect URL field by default. On Statamic 6 the utility HTML is injected via Inertia/`v-html`, so the inline toggle script does not run; the field is now always reachable (the script still hides it when redirect is off on Statamic 4/5).

## 1.1.0 - 2026-06-08

- Add support for Statamic 4, 5, and 6 (incl. 6.20). Constraint widened to `statamic/cms: ^4.0|^5.0|^6.0`.
- Lower minimum PHP to `^8.1` so Statamic 4 installs (Laravel 9/10) are supported.
- Remove redundant manual settings-blueprint registration: Statamic 6 auto-registers it from `resources/blueprints/settings.yaml` during boot, while Statamic 4/5 have no settings-blueprint concept (avoids a double bind on v6).
- Quote `instructions` strings in `settings.yaml` so the colon (`X-Robots-Tag:`) parses correctly under Statamic 6's automatic blueprint parsing.

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

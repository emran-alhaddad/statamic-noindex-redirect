<p align="center">
  <img src="assets/thumbnail.png" width="160" alt="Noindex Redirect" />
</p>

<p align="center">
  <a href="https://statamic.com/addons/emran-alhaddad/statamic-noindex-redirect">
    <img alt="Statamic Marketplace" src="https://img.shields.io/badge/Statamic-Marketplace-4E5DFF?logo=statamic&logoColor=white" />
  </a>
  <a href="https://packagist.org/packages/emran-alhaddad/noindex-redirect">
    <img alt="Latest Version on Packagist" src="https://img.shields.io/packagist/v/emran-alhaddad/noindex-redirect" />
  </a>
  <a href="https://packagist.org/packages/emran-alhaddad/noindex-redirect">
    <img alt="Total Downloads" src="https://img.shields.io/packagist/dt/emran-alhaddad/noindex-redirect" />
  </a>
  <a href="https://packagist.org/packages/emran-alhaddad/noindex-redirect">
    <img alt="Monthly Downloads" src="https://img.shields.io/packagist/dm/emran-alhaddad/noindex-redirect" />
  </a>
  <a href="https://packagist.org/packages/emran-alhaddad/noindex-redirect">
    <img alt="License" src="https://img.shields.io/packagist/l/emran-alhaddad/noindex-redirect" />
  </a>
</p>

# Noindex Redirect (Statamic Addon)

Disable indexing (`noindex, nofollow`) for your Statamic site and optionally redirect the root URL (`/`) to another domain — managed from a **Control Panel Utility**.

## Requirements

- PHP `^8.2`
- Statamic `^5.0` (Statamic `^6.0` supported)

## Installation

```bash
composer require emran-alhaddad/noindex-redirect
```

## Control Panel Utility

Go to **Control Panel → Utilities → Noindex Redirect**.

Settings are stored in `storage/app/noindex-redirect/settings.json` and will override config/env values until you click **Reset to config**.

## Configuration (optional)

Publish the config file:

```bash
php artisan vendor:publish --tag=noindex-redirect-config
```

Config path:

- `config/noindex-redirect.php`

Environment variables:

```env
NOINDEX_REDIRECT_DISABLE_INDEXING=true
NOINDEX_REDIRECT_ENABLE_REDIRECT=false
NOINDEX_REDIRECT_REDIRECT_URL=https://example.com
NOINDEX_REDIRECT_MANAGE_PUBLIC_ROBOTS_TXT=true
```

## What it does

### Disable Indexing

When enabled, the addon:

- Adds `X-Robots-Tag: noindex, nofollow` to frontend responses (excludes CP + GraphQL routes).
- Injects `<meta name="robots" content="noindex, nofollow">` into the HTML `<head>` for `text/html` responses.
- Optionally manages `public/robots.txt` (see below).

### robots.txt behavior

Some servers (including local dev setups) will serve `public/robots.txt` directly and bypass Laravel routes.

If `NOINDEX_REDIRECT_MANAGE_PUBLIC_ROBOTS_TXT=true`, the addon will:

- Write a managed `public/robots.txt` when indexing is disabled (`Disallow: /`).
- Back up any existing `public/robots.txt` to `storage/app/noindex-redirect/robots.backup.txt` before overriding it.
- Restore the backup when indexing is re-enabled.

If the `public` directory is not writable, robots.txt syncing is skipped.

### Root redirect

When **Enable Redirect** is enabled and a `redirect_url` is set, requests to `/` will be redirected (301) to the configured URL.

## License

MIT. See `LICENSE`.

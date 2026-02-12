<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Noindex Redirect Configuration
    |--------------------------------------------------------------------------
    |
    | Default values for the Noindex Redirect addon.
    |
    | In Statamic 5, these values can be configured via this config file or
    | environment variables. A Control Panel Utility is also provided by this
    | addon to manage these settings without editing files.
    |
    */

    /*
    | Disable Indexing
    |
    | When set to true, the addon will append an `X-Robots-Tag: noindex, nofollow`
    | header to all frontend responses (excluding the control panel and API
    | endpoints). Set to false to allow indexing.
    */
    'disable_indexing' => env('NOINDEX_REDIRECT_DISABLE_INDEXING', true),

    /*
    | Enable Redirect
    |
    | When true, requests to the root of the CMS subdomain will be redirected
    | to the URL specified below. This should only redirect the root path and
    | leave control panel and API routes unaffected.
    */
    'enable_redirect' => env('NOINDEX_REDIRECT_ENABLE_REDIRECT', false),

    /*
    | Redirect URL
    |
    | The absolute URL to redirect root requests to (e.g. `https://example.com`).
    | Leave null or empty to disable the redirect.
    */
    // Support both env var names (the project previously used `NOINDEX_REDIRECT_REDIRECT_URL`).
    'redirect_url' => env('NOINDEX_REDIRECT_REDIRECT_URL', env('NOINDEX_REDIRECT_URL', null)),

    /*
    | Manage public/robots.txt
    |
    | If enabled, the addon will keep `public/robots.txt` in sync with the
    | "Disable Indexing" setting (and back up any existing file while disabled).
    */
    'manage_public_robots_txt' => env('NOINDEX_REDIRECT_MANAGE_PUBLIC_ROBOTS_TXT', true),
];

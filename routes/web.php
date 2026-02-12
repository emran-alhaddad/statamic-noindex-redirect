<?php

use Illuminate\Support\Facades\Route;
use Emran\NoindexRedirect\NoindexRedirectSettings;

/*
|--------------------------------------------------------------------------
| Addon Web Routes
|--------------------------------------------------------------------------
|
| Here we register any routes for the addon that need to be accessible
| outside of the Statamic Control Panel. We only define a robots.txt
| endpoint here because the redirect logic is handled in middleware.
*/

// Provide a custom robots.txt when indexing is disabled. This route will
// override any other robots.txt route in the application.
Route::get('robots.txt', function () {
    return response(NoindexRedirectSettings::robotsTxt(), 200)->header('Content-Type', 'text/plain');
});

<?php

namespace Emran\NoindexRedirect\Http\Controllers;

use Emran\NoindexRedirect\NoindexRedirectSettings;
use Illuminate\Http\Request;
use Statamic\Http\Controllers\CP\CpController;

class NoindexRedirectUtilityController extends CpController
{
    public function update(Request $request)
    {
        $enableRedirect = $request->boolean('enable_redirect');

        $validated = $request->validate([
            'redirect_url' => $enableRedirect ? ['required', 'url'] : ['nullable', 'url'],
        ]);

        try {
            NoindexRedirectSettings::save([
                'disable_indexing' => $request->boolean('disable_indexing'),
                'enable_redirect' => $enableRedirect,
                'redirect_url' => $enableRedirect ? ($validated['redirect_url'] ?? null) : null,
            ]);

            NoindexRedirectSettings::applyToConfig();
        } catch (\Throwable $e) {
            return back()
                ->withErrors([__('Unable to save settings. Please ensure the storage directory is writable.')])
                ->withInput();
        }

        return back()->with('success', __('Noindex Redirect settings saved.'));
    }

    public function reset()
    {
        try {
            NoindexRedirectSettings::clear();

            NoindexRedirectSettings::applyToConfig();
        } catch (\Throwable $e) {
            return back()
                ->withErrors([__('Unable to reset settings. Please ensure the storage directory is writable.')]);
        }

        return back()->with('success', __('Noindex Redirect settings reset to config defaults.'));
    }
}

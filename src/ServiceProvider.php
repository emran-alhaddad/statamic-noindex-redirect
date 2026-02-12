<?php

namespace Emran\NoindexRedirect;

use Statamic\Providers\AddonServiceProvider;
use Statamic\Facades\Utility;
use Statamic\Facades\YAML;
use Emran\NoindexRedirect\Http\Controllers\NoindexRedirectUtilityController;

/**
 * Service provider for the Noindex Redirect addon.
 *
 * This class registers a Control Panel Utility to manage settings and pushes a
 * middleware onto the web middleware group. The middleware handles both the
 * noindex header and optional root-path redirect.
 */
class ServiceProvider extends AddonServiceProvider
{
    /**
     * The addon's route definitions. Only the web routes are needed here.
     * @var array
     */
    protected $routes = [
        'web' => __DIR__ . '/../routes/web.php',
    ];

    private function utilityIcon(): string
    {
        $iconName = 'noindex-redirect';
        $iconPath = $this->getAddon()->directory().'resources/svg/icons/noindex-redirect.svg';

        if (is_file($iconPath) && class_exists(\Statamic\Facades\CP\SVG::class) && is_callable([\Statamic\Facades\CP\SVG::class, 'extend'])) {
            try {
                \Statamic\Facades\CP\SVG::extend(function ($svg) use ($iconName, $iconPath) {
                    if (! is_object($svg)) {
                        return;
                    }

                    if (method_exists($svg, 'add')) {
                        $svg->add($iconName, $iconPath);
                        return;
                    }

                    if (method_exists($svg, 'register')) {
                        $svg->register($iconName, $iconPath);
                    }
                });

                return $iconName;
            } catch (\Throwable $e) {
                // Fallbacks below.
            }
        }

        if (is_file($iconPath) && class_exists(\Statamic\Facades\CP\Icon::class) && is_callable([\Statamic\Facades\CP\Icon::class, 'register'])) {
            try {
                \Statamic\Facades\CP\Icon::register($iconName, $iconPath);
                return $iconName;
            } catch (\Throwable $e) {
                // Fallbacks below.
            }
        }

        return 'eye';
    }

    /**
     * Boot the addon. Registers the settings blueprint and middleware.
     *
     * @return void
     */
    public function bootAddon()
    {
        parent::bootAddon();

        NoindexRedirectSettings::applyToConfig();

        // Register the settings blueprint if supported (Statamic 6+).
        if (method_exists($this, 'registerSettingsBlueprint')) {
            $path = $this->getAddon()->directory().'resources/blueprints/settings.yaml';
            $this->registerSettingsBlueprint(YAML::file($path)->parse());
        }

        $utilityIcon = $this->utilityIcon();

        Utility::register('noindex_redirect')
            ->icon($utilityIcon)
            ->title(__('Noindex Redirect'))
            ->description(__('Disable indexing and configure root redirect.'))
            ->view('noindex-redirect::utility', function ($request) {
                return [
                    'settings' => NoindexRedirectSettings::all(),
                    'has_stored_settings' => NoindexRedirectSettings::hasStoredSettings(),
                    'storage_relative_path' => NoindexRedirectSettings::storageRelativePath(),
                ];
            })
            ->routes(function ($router) {
                $router->post('/', [NoindexRedirectUtilityController::class, 'update'])->name('update');
                $router->post('reset', [NoindexRedirectUtilityController::class, 'reset'])->name('reset');
            });

        // Register our middleware early so it wraps other middleware (eg. static caching)
        // and can still apply headers/meta tags even when a cache returns early.
        $middleware = \Emran\NoindexRedirect\Http\Middleware\NoIndexMiddleware::class;
        $router = $this->app['router'];

        // Statamic's frontend uses the `statamic.web` group.
        $router->prependMiddlewareToGroup('statamic.web', $middleware);

        // Keep this on the default `web` group as well for any non-Statamic routes.
        $router->prependMiddlewareToGroup('web', $middleware);
    }
}

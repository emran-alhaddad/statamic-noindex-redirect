<?php

namespace Emran\NoindexRedirect;

use Illuminate\Contracts\Http\Kernel;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Facades\Utility;
use Emran\NoindexRedirect\Http\Controllers\NoindexRedirectUtilityController;

/**
 * Service provider for the Noindex Redirect addon.
 *
 * This class registers a Control Panel Utility to manage settings and
 * registers middleware globally (with route-group fallback). The middleware
 * handles both the noindex header/meta behavior and optional redirect.
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

    private function svgIcon(string $name): string
    {
        $path = __DIR__ . '/../resources/svg/' . $name . '.svg';
        return file_exists($path) ? (file_get_contents($path) ?: '') : '';
    }

    /**
     * Boot the addon. Registers the CP Utility and middleware.
     *
     * @return void
     */
    public function bootAddon()
    {
        parent::bootAddon();

        NoindexRedirectSettings::applyToConfig();

        $middleware = \Emran\NoindexRedirect\Http\Middleware\NoIndexMiddleware::class;
        $this->registerGlobalMiddleware($middleware);
        $this->registerGroupMiddlewareFallback($middleware);

        // Note: on Statamic 6+ the parent service provider auto-registers the
        // settings blueprint from resources/blueprints/settings.yaml during boot.
        // Statamic 4/5 have no settings-blueprint concept, so there's nothing to
        // register here. (Registering it manually would double-bind on v6.)

        Utility::extend(function () {
            Utility::register('noindex_redirect')
                ->icon($this->svgIcon('noindex-redirect'))
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
        });
    }

    private function registerGlobalMiddleware(string $middleware): void
    {
        try {
            $kernel = $this->app->make(Kernel::class);

            if (method_exists($kernel, 'getMiddleware')) {
                $current = $kernel->getMiddleware();
                if (is_array($current) && in_array($middleware, $current, true)) {
                    return;
                }
            }

            if (method_exists($kernel, 'prependMiddleware')) {
                $kernel->prependMiddleware($middleware);

                return;
            }

            if (method_exists($kernel, 'pushMiddleware')) {
                $kernel->pushMiddleware($middleware);
            }
        } catch (\Throwable $e) {
            //
        }
    }

    private function registerGroupMiddlewareFallback(string $middleware): void
    {
        try {
            $router = $this->app['router'];
            $this->prependMiddlewareToGroupIfMissing($router, 'statamic.web', $middleware);
            $this->prependMiddlewareToGroupIfMissing($router, 'web', $middleware);
        } catch (\Throwable $e) {
            //
        }
    }

    private function prependMiddlewareToGroupIfMissing($router, string $group, string $middleware): void
    {
        try {
            if (method_exists($router, 'getMiddlewareGroups')) {
                $groups = $router->getMiddlewareGroups();

                if (is_array($groups)
                    && array_key_exists($group, $groups)
                    && is_array($groups[$group])
                    && in_array($middleware, $groups[$group], true)
                ) {
                    return;
                }
            }

            if (method_exists($router, 'prependMiddlewareToGroup')) {
                $router->prependMiddlewareToGroup($group, $middleware);
            }
        } catch (\Throwable $e) {
            //
        }
    }
}

<?php

namespace Emran\NoindexRedirect;

use Illuminate\Contracts\Http\Kernel;
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

    private function svgIcon(string $name): string
    {
        $path = __DIR__ . '/../resources/svg/' . $name . '.svg';
        return file_exists($path) ? (file_get_contents($path) ?: '') : '';
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

        // Register our middleware globally so it also runs when Statamic frontend
        // routes are disabled (no matched routes => no route middleware groups).
        $middleware = \Emran\NoindexRedirect\Http\Middleware\NoIndexMiddleware::class;

        try {
            $kernel = $this->app->make(Kernel::class);
            if (method_exists($kernel, 'prependMiddleware')) {
                $kernel->prependMiddleware($middleware);
            } else {
                $kernel->pushMiddleware($middleware);
            }
        } catch (\Throwable $e) {
            // If the HTTP kernel isn't available for some reason, fall back to route groups.
            $router = $this->app['router'];
            $router->prependMiddlewareToGroup('statamic.web', $middleware);
            $router->prependMiddlewareToGroup('web', $middleware);
        }
    }
}

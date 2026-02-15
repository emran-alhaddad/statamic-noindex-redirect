<?php

namespace Emran\NoindexRedirect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Emran\NoindexRedirect\NoindexRedirectSettings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Middleware that applies indexing and redirect rules based on addon settings.
 *
 * This middleware performs two main functions:
 *   1. It attaches an X-Robots-Tag header with `noindex, nofollow` to
 *      responses when indexing is disabled. It skips control panel and
 *      GraphQL routes to avoid interfering with the CMS.
 *   2. It performs a root-path redirect to a configured URL when enabled.
 */
class NoIndexMiddleware
{
    private const ROBOTS_META_TAG = '<meta name="robots" content="noindex, nofollow">';

    private function shouldSkip(Request $request): bool
    {
        $firstSegment = $request->segment(1);
        $cpRoute = Config::get('statamic.cp.route', 'cp');

        if ($firstSegment === $cpRoute) {
            return true;
        }

        if (in_array($firstSegment, ['graphql', 'graphql-playground'], true)) {
            return true;
        }

        // Statamic "actions" endpoints live under "/!/*" (eg. "/!/forms/*").
        if ($firstSegment === '!') {
            return true;
        }

        return false;
    }

    private function redirectTarget(string $baseUrl, Request $request): string
    {
        $base = rtrim($baseUrl, '/');
        $uri = $request->getRequestUri(); // includes path + query string

        // Ensure the URI always starts with a slash.
        if ($uri === '' || $uri[0] !== '/') {
            $uri = '/'.$uri;
        }

        return $base.$uri;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $settings = NoindexRedirectSettings::all();

        $enableRedirect = $settings['enable_redirect'];
        $redirectUrl = $settings['redirect_url'];

        // Perform redirect if enabled for frontend requests only.
        // If Statamic frontend routes are disabled, redirect ALL frontend
        // requests to avoid rendering 404 pages.
        if ($enableRedirect && $redirectUrl && ! $this->shouldSkip($request)) {
            if (in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
                $frontendEnabled = (bool) Config::get('statamic.routes.frontend', true);

                if (! $frontendEnabled) {
                    return Redirect::away($this->redirectTarget($redirectUrl, $request), 301);
                }

                // Default behavior: redirect only the site root ("/").
                if (! $request->segment(1)) {
                    return Redirect::away($redirectUrl, 301);
                }
            }
        }

        // Let the request continue and capture the response. If a request ends up
        // as an exception (404/403/500), still render it so we can attach noindex
        // headers/meta to the final response.
        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $handler = app(ExceptionHandler::class);

            try {
                $handler->report($e);
            } catch (\Throwable $ignored) {
            }

            $response = $handler->render($request, $e);
        }

        // Check if indexing is disabled. Use config default if no setting exists.
        $disableIndexing = $settings['disable_indexing'];
        if ($disableIndexing && ! $this->shouldSkip($request)) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow', true);
            $this->injectRobotsMetaTag($response);
        }

        return $response;
    }

    private function injectRobotsMetaTag($response): void
    {
        if (! $response instanceof \Symfony\Component\HttpFoundation\Response) {
            return;
        }

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType && ! str_contains($contentType, 'text/html')) {
            return;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '') {
            return;
        }

        if (stripos($html, '</head') === false) {
            return;
        }

        if (preg_match('/<meta\b[^>]*name=[\'"]robots[\'"][^>]*>/i', $html)) {
            $updated = preg_replace(
                '/<meta\b[^>]*name=[\'"]robots[\'"][^>]*>/i',
                self::ROBOTS_META_TAG,
                $html,
                1
            );

            if (is_string($updated)) {
                $response->setContent($updated);
            }

            return;
        }

        $meta = self::ROBOTS_META_TAG."\n";
        $updated = preg_replace('/<head\b[^>]*>/i', '$0'.$meta, $html, 1, $count);

        if (! is_string($updated)) {
            return;
        }

        if ($count === 0) {
            $updated = preg_replace('/<\/head\s*>/i', $meta.'</head>', $updated, 1);
            if (! is_string($updated)) {
                return;
            }
        }

        $response->setContent($updated);
    }
}

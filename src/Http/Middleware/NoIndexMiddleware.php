<?php

namespace Emran\NoindexRedirect\Http\Middleware;

use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Emran\NoindexRedirect\NoindexRedirectSettings;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Middleware that applies indexing and redirect rules based on addon settings.
 *
 * This middleware performs two main functions:
 *   1. It attaches an X-Robots-Tag header with `noindex, nofollow` to
 *      responses when indexing is disabled. It skips control panel and
 *      GraphQL routes to avoid interfering with the CMS.
 *   2. It redirects all frontend GET/HEAD requests to a configured URL when enabled.
 */
class NoIndexMiddleware
{
    private const ROBOTS_META_TAG = '<meta name="robots" content="noindex, nofollow">';

    private function shouldSkip(Request $request): bool
    {
        $cpRoute = trim((string) Config::get('statamic.cp.route', 'cp'), '/');

        if ($cpRoute !== '' && ($request->is($cpRoute) || $request->is($cpRoute.'/*'))) {
            return true;
        }

        if ($request->is('graphql') || $request->is('graphql/*') || $request->is('graphql-playground') || $request->is('graphql-playground/*')) {
            return true;
        }

        // Statamic "actions" endpoints live under "/!/*" (eg. "/!/forms/*").
        if ($request->segment(1) === '!') {
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
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $settings = NoindexRedirectSettings::all();

        $enableRedirect = $settings['enable_redirect'];
        $redirectUrl = $settings['redirect_url'];

        // Redirect all frontend GET/HEAD requests when enabled.
        if ($enableRedirect && $redirectUrl) {
            if (in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
                $response = Redirect::away($this->redirectTarget($redirectUrl, $request), 301);

                if ((bool) $settings['disable_indexing']) {
                    $response->headers->set('X-Robots-Tag', 'noindex, nofollow', true);
                }

                return $response;
            }
        }

        try {
            $response = $next($request);
        } catch (\Throwable $exception) {
            $response = $this->renderExceptionResponse($request, $exception);
        }

        // Check if indexing is disabled. Use config default if no setting exists.
        $disableIndexing = $settings['disable_indexing'];
        if ($disableIndexing) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow', true);
            $this->injectRobotsMetaTag($response);
        }

        return $response;
    }

    private function renderExceptionResponse(Request $request, \Throwable $exception): HttpResponse
    {
        $handler = app(ExceptionHandler::class);

        try {
            $handler->report($exception);
        } catch (\Throwable $ignored) {
        }

        return $handler->render($request, $exception);
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

        if (preg_match('/<head\b[^>]*>/i', $html)) {
            $updated = preg_replace('/<head\b[^>]*>/i', '$0'.$meta, $html, 1);
            if (is_string($updated)) {
                $response->setContent($updated);
            }

            return;
        }

        if (preg_match('/<html\b[^>]*>/i', $html)) {
            $updated = preg_replace('/<html\b[^>]*>/i', '$0<head>'.$meta.'</head>', $html, 1);
            if (is_string($updated)) {
                $response->setContent($updated);
            }

            return;
        }

        $response->setContent('<!DOCTYPE html><html><head>'.$meta.'</head><body>'.$html.'</body></html>');
    }
}

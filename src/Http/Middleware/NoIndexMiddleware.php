<?php

namespace Emran\NoindexRedirect\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
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

        // Perform redirect if enabled and request is for the root of the CMS
        // subdomain. We avoid redirecting CP or API routes. An empty segment
        // indicates the root (no segments).
        if ($enableRedirect && $redirectUrl) {
            $firstSegment = $request->segment(1);

            if (!$firstSegment) {
                return Redirect::away($redirectUrl, 301);
            }
        }

        // Let the request continue and capture the response.
        $response = $next($request);

        // Check if indexing is disabled. Use config default if no setting exists.
        $disableIndexing = $settings['disable_indexing'];
        if ($disableIndexing) {
            $firstSegment = $request->segment(1);
            $cpRoute      = Config::get('statamic.cp.route', 'cp');
            if ($firstSegment !== $cpRoute && !in_array($firstSegment, ['graphql', 'graphql-playground'])) {
                $response->headers->set('X-Robots-Tag', 'noindex, nofollow', true);
                $this->injectRobotsMetaTag($response);
            }
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

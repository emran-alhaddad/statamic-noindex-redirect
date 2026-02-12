<?php

namespace Emran\NoindexRedirect;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Addon;

final class NoindexRedirectSettings
{
    private const STORAGE_RELATIVE_PATH = 'app/noindex-redirect/settings.json';
    private const ROBOTS_BACKUP_RELATIVE_PATH = 'app/noindex-redirect/robots.backup.txt';
    private const ROBOTS_MANAGED_MARKER = '# Managed by emran-alhaddad/noindex-redirect';
    private const ROBOTS_MANAGED_MARKER_LEGACY = '# Managed by emran/noindex-redirect';

    private static ?array $cached = null;

    public static function applyToConfig(): void
    {
        $settings = self::all();

        Config::set('noindex-redirect.disable_indexing', $settings['disable_indexing']);
        Config::set('noindex-redirect.enable_redirect', $settings['enable_redirect']);
        Config::set('noindex-redirect.redirect_url', $settings['redirect_url']);

        self::syncPublicRobotsTxt($settings);
    }

    public static function robotsTxt(): string
    {
        $disableIndexing = (bool) (self::all()['disable_indexing'] ?? true);

        return self::managedRobotsTxtContents($disableIndexing);
    }

    public static function syncPublicRobotsTxt(?array $settings = null): void
    {
        if (! (bool) Config::get('noindex-redirect.manage_public_robots_txt', true)) {
            return;
        }

        $disableIndexing = (bool) (($settings['disable_indexing'] ?? null) ?? (self::all()['disable_indexing'] ?? true));

        $publicPath = public_path('robots.txt');
        $backupPath = self::robotsBackupPath();

        try {
            $publicContents = File::exists($publicPath) ? File::get($publicPath) : null;

            if ($disableIndexing) {
                if ($publicContents !== null && ! self::isManagedRobotsTxt($publicContents) && ! File::exists($backupPath)) {
                    $dir = dirname($backupPath);
                    if (! File::exists($dir)) {
                        File::makeDirectory($dir, 0755, true);
                    }

                    File::put($backupPath, $publicContents);
                }

                $desired = self::managedRobotsTxtContents(true);

                if ($publicContents === null || self::normalizeRobotsTxt($publicContents) !== self::normalizeRobotsTxt($desired)) {
                    File::put($publicPath, $desired);
                }

                return;
            }

            if (File::exists($backupPath)) {
                $backup = File::get($backupPath);

                if ($publicContents === null || self::normalizeRobotsTxt($publicContents) !== self::normalizeRobotsTxt($backup)) {
                    File::put($publicPath, $backup);
                }

                File::delete($backupPath);

                return;
            }

            if ($publicContents !== null && self::isManagedRobotsTxt($publicContents)) {
                $desired = self::managedRobotsTxtContents(false);

                if (self::normalizeRobotsTxt($publicContents) !== self::normalizeRobotsTxt($desired)) {
                    File::put($publicPath, $desired);
                }
            }
        } catch (\Throwable $e) {
            // Intentionally swallow: environments may have a read-only public directory.
        }
    }

    public static function all(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $defaults = [
            'disable_indexing' => (bool) Config::get('noindex-redirect.disable_indexing', true),
            'enable_redirect' => (bool) Config::get('noindex-redirect.enable_redirect', false),
            'redirect_url' => Config::get('noindex-redirect.redirect_url'),
        ];

        $addon = self::readAddonSettings();
        $stored = self::readStored();

        $settings = $defaults;
        foreach (array_keys($defaults) as $key) {
            if (array_key_exists($key, $addon)) {
                $settings[$key] = $addon[$key];
            }
            if (array_key_exists($key, $stored)) {
                $settings[$key] = $stored[$key];
            }
        }

        $settings['disable_indexing'] = (bool) $settings['disable_indexing'];
        $settings['enable_redirect'] = (bool) $settings['enable_redirect'];
        $settings['redirect_url'] = self::normalizeUrl($settings['redirect_url'] ?? null);

        return self::$cached = $settings;
    }

    public static function hasStoredSettings(): bool
    {
        return File::exists(self::storagePath());
    }

    public static function save(array $settings): void
    {
        $path = self::storagePath();
        $dir = dirname($path);

        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $payload = [
            'disable_indexing' => (bool) ($settings['disable_indexing'] ?? false),
            'enable_redirect' => (bool) ($settings['enable_redirect'] ?? false),
            'redirect_url' => self::normalizeUrl($settings['redirect_url'] ?? null),
        ];

        $written = File::put(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        if ($written === false) {
            throw new \RuntimeException('Unable to write Noindex Redirect settings to: '.$path);
        }

        self::$cached = null;
    }

    public static function clear(): void
    {
        $path = self::storagePath();

        if (File::exists($path)) {
            File::delete($path);
        }

        self::$cached = null;
    }

    public static function storagePath(): string
    {
        return storage_path(self::STORAGE_RELATIVE_PATH);
    }

    public static function robotsBackupPath(): string
    {
        return storage_path(self::ROBOTS_BACKUP_RELATIVE_PATH);
    }

    public static function storageRelativePath(): string
    {
        return 'storage/'.self::STORAGE_RELATIVE_PATH;
    }

    private static function readStored(): array
    {
        $path = self::storagePath();

        if (! File::exists($path)) {
            return [];
        }

        try {
            $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    private static function readAddonSettings(): array
    {
        $addon = null;

        try {
            $addon = Addon::get('emran-alhaddad/noindex-redirect');
        } catch (\Throwable $e) {
            //
        }

        if (! $addon) {
            try {
                $addon = Addon::get('emran/noindex-redirect');
            } catch (\Throwable $e) {
                //
            }
        }

        if (! $addon || ! method_exists($addon, 'settings')) {
            return [];
        }

        try {
            return $addon->settings()->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function normalizeUrl(mixed $value): ?string
    {
        $url = is_string($value) ? trim($value) : null;

        return $url !== '' ? $url : null;
    }

    private static function managedRobotsTxtContents(bool $disableIndexing): string
    {
        return self::ROBOTS_MANAGED_MARKER."\n"
            ."User-agent: *\n"
            .($disableIndexing ? "Disallow: /\n" : "Disallow:\n");
    }

    private static function isManagedRobotsTxt(string $contents): bool
    {
        return str_contains($contents, self::ROBOTS_MANAGED_MARKER)
            || str_contains($contents, self::ROBOTS_MANAGED_MARKER_LEGACY);
    }

    private static function normalizeRobotsTxt(string $contents): string
    {
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);

        return rtrim($contents)."\n";
    }
}

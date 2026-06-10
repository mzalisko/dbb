<?php

namespace Nwdb\WpFeed;

use Nwdb\Models\SitePlugin;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Рендерить шаблон WP-плагіна в унікальний per-site ZIP:
 * усі {{TOKEN}}-плейсхолдери заміняються ідентичністю білда,
 * головний файл отримує ім'я <slug>.php у папці <slug>/.
 */
class PluginBuilder
{
    public function __construct(private readonly FeedManager $feeds)
    {
    }

    public static function templatePath(): string
    {
        return dirname(__DIR__, 2).'/resources/wp-plugin-template';
    }

    /** Зібрати ZIP у тимчасовий файл, повертає його шлях. */
    public function build(SitePlugin $plugin): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'nwdb-plugin-');
        if ($zipPath === false) {
            throw new RuntimeException('Cannot create temp file for plugin ZIP.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot open plugin ZIP for writing.');
        }

        $vars = $this->identityVars($plugin);
        $slug = $plugin->slug;

        foreach ($this->templateFiles() as $relative => $absolute) {
            $target = $slug.'/'.($relative === 'plugin.php' ? $slug.'.php' : $relative);
            $zip->addFromString($target, $this->render(file_get_contents($absolute), $vars));
        }

        $zip->close();

        return $zipPath;
    }

    public function streamZip(SitePlugin $plugin): StreamedResponse
    {
        $zipPath = $this->build($plugin);

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            @unlink($zipPath);
        }, $plugin->slug.'.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }

    /** @return array<string,string> relative (без .stub) => absolute */
    private function templateFiles(): array
    {
        $root = self::templatePath();
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $relative = ltrim(str_replace($root, '', $file->getPathname()), '/');
            $files[preg_replace('/\.stub$/', '', $relative)] = $file->getPathname();
        }

        ksort($files);

        return $files;
    }

    private function render(string $template, array $vars): string
    {
        $rendered = strtr($template, $vars);

        if (preg_match('/\{\{[A-Z_]+\}\}/', $rendered, $m)) {
            throw new RuntimeException("Unrendered template token {$m[0]} in plugin build.");
        }

        return $rendered;
    }

    private function identityVars(SitePlugin $plugin): array
    {
        $classPrefix = implode('_', array_map('ucfirst', array_filter(explode('_', $plugin->prefix))));
        $constPrefix = strtoupper(rtrim($plugin->prefix, '_'));

        return [
            '{{PLUGIN_SLUG}}'        => $plugin->slug,
            '{{PLUGIN_NAME}}'        => $plugin->display_name,
            '{{TEXT_DOMAIN}}'        => $plugin->text_domain,
            '{{PREFIX}}'             => $plugin->prefix,
            '{{CLASS_PREFIX}}'       => $classPrefix,
            '{{CONST_PREFIX}}'       => $constPrefix,
            '{{CRON_HOOK}}'          => $plugin->cron_hook,
            '{{CRON_INTERVAL}}'      => (string) $plugin->cron_interval,
            '{{FEED_URL}}'           => $plugin->feedUrl(),
            '{{ED25519_PUBLIC_B64}}' => base64_encode($this->feeds->signingPublic()),
            '{{SYM_KEY_B64}}'        => $plugin->sym_key,
            '{{PLUGIN_VERSION}}'     => (string) config('nwdb.plugin.version'),
            '{{BUILD_ID}}'           => bin2hex(random_bytes(8)),
        ];
    }
}

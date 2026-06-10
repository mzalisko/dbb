<?php

namespace Nwdb\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\Cipher;
use Nwdb\WpFeed\PluginBuilder;
use Tests\TestCase;
use ZipArchive;

class PluginBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $pair = Cipher::generateSigningKeypair();
        config([
            'nwdb.signing.public' => base64_encode($pair['public']),
            'nwdb.signing.secret' => base64_encode($pair['secret']),
        ]);
    }

    /** @return array<string,string> ім'я файлу => вміст */
    private function buildAndExtract(SitePlugin $plugin): array
    {
        $zipPath = app(PluginBuilder::class)->build($plugin);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));

        $files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $files[$name] = $zip->getFromIndex($i);
        }
        $zip->close();
        @unlink($zipPath);

        return $files;
    }

    public function test_zip_contains_renamed_plugin_files_under_slug_folder(): void
    {
        $plugin = SitePlugin::factory()->create();
        $files = $this->buildAndExtract($plugin);

        $this->assertArrayHasKey("{$plugin->slug}/{$plugin->slug}.php", $files);
        $this->assertArrayHasKey("{$plugin->slug}/includes/crypto.php", $files);
        $this->assertArrayHasKey("{$plugin->slug}/includes/fetcher.php", $files);
        $this->assertArrayHasKey("{$plugin->slug}/includes/renderer.php", $files);
        $this->assertArrayHasKey("{$plugin->slug}/includes/geo.php", $files);
        $this->assertArrayHasKey("{$plugin->slug}/uninstall.php", $files);
        $this->assertArrayHasKey("{$plugin->slug}/readme.txt", $files);
    }

    public function test_no_template_tokens_remain(): void
    {
        $plugin = SitePlugin::factory()->create();

        foreach ($this->buildAndExtract($plugin) as $name => $content) {
            $this->assertDoesNotMatchRegularExpression(
                '/\{\{[A-Z_]+\}\}/', $content, "Unrendered token left in {$name}"
            );
        }
    }

    public function test_identity_and_keys_are_embedded(): void
    {
        $plugin = SitePlugin::factory()->create();
        $files = $this->buildAndExtract($plugin);
        $main = $files["{$plugin->slug}/{$plugin->slug}.php"];

        $this->assertStringContainsString("Plugin Name: {$plugin->display_name}", $main);
        $this->assertStringContainsString($plugin->feed_filename, $main);
        $this->assertStringContainsString($plugin->cron_hook, $main);
        $this->assertStringContainsString($plugin->sym_key, $main);
        $this->assertStringContainsString(config('nwdb.signing.public'), $main);

        // Симетричний ключ присутній рівно один раз у всьому білді
        $all = implode('', $files);
        $this->assertSame(1, substr_count($all, $plugin->sym_key));
        // Підписний СЕКРЕТ ніколи не потрапляє у білд
        $this->assertStringNotContainsString(config('nwdb.signing.secret'), $all);
    }

    public function test_two_builds_share_no_identity(): void
    {
        $a = SitePlugin::factory()->create();
        $b = SitePlugin::factory()->create();

        $this->assertNotSame($a->slug, $b->slug);
        $this->assertNotSame($a->prefix, $b->prefix);
        $this->assertNotSame($a->feed_token, $b->feed_token);
        $this->assertNotSame($a->cron_hook, $b->cron_hook);

        $filesA = $this->buildAndExtract($a);
        $filesB = $this->buildAndExtract($b);

        $this->assertEmpty(array_intersect_key($filesA, $filesB), 'ZIP paths must differ between sites');
        $this->assertStringNotContainsString($a->prefix, implode('', $filesB));
    }

    public function test_main_file_is_valid_php(): void
    {
        $plugin = SitePlugin::factory()->create();
        $files = $this->buildAndExtract($plugin);

        foreach ($files as $name => $content) {
            if (! str_ends_with($name, '.php')) {
                continue;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'lint');
            file_put_contents($tmp, $content);
            exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $code);
            @unlink($tmp);
            $this->assertSame(0, $code, "Syntax error in {$name}: ".implode("\n", $out));
        }
    }
}

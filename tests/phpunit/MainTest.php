<?php
/**
 * Main bootstrap tests.
 *
 * Verifies safe config defaults and plugin-root-derived asset URLs.
 *
 * @package github_plugin_updater
 */

namespace Bmd\GithubWpUpdater\PHPUnit;

use Bmd\GithubWpUpdater\Main;
use WP_Mock;
use WP_Mock\Tools\TestCase;

final class MainTest extends TestCase
{
    /**
     * Ensures invalid root files produce safe empty config values.
     *
     * @covers \Bmd\GithubWpUpdater\Main::__construct
     */
    public function testConstructorBuildsSafeConfigWithoutValidRootFile(): void
    {
        $main = new TestableMain( '/not/a/real/plugin.php' );

        $config = $main->exposeConfig();

        $this->assertSame( '', $config['plugin.dir'] );
        $this->assertSame( '', $config['plugin.url'] );
        $this->assertSame( '', $config['plugin.file'] );
        $this->assertSame( '', $config['plugin.slug'] );
        $this->assertSame( '', $config['plugin.package'] );
        $this->assertSame( '', $config['plugin.banners']['low'] );
        $this->assertSame( '', $config['plugin.icons']['default'] );
    }

    /**
     * Ensures asset defaults are built from the plugin root file rather than the Main class file.
     *
     * @covers \Bmd\GithubWpUpdater\Main::__construct
     */
    public function testConstructorBuildsAssetUrlsFromRootFile(): void
    {
        $pluginDir = sys_get_temp_dir() . '/github-plugin-updater-test';
        $pluginFile = $pluginDir . '/plugin.php';

        if ( ! is_dir( $pluginDir ) ) {
            mkdir( $pluginDir, 0777, true );
        }

        file_put_contents( $pluginFile, "<?php\n/*\nVersion: 1.2.3\n*/" );

        WP_Mock::userFunction(
            'get_file_data',
            [
                'times'  => 1,
                'args'   => [
                    $pluginFile,
                    [
                        'plugin_uri' => 'Plugin URI',
                        'version' => 'Version',
                    ],
                ],
                'return' => [
                    'plugin_uri' => 'https://example.com/plugin',
                    'version' => '1.2.3',
                ],
            ]
        );
        $main = new TestableMain( $pluginFile );

        $config = $main->exposeConfig();

        $this->assertSame( 'https://example.com/github-plugin-updater-test/', $config['plugin.url'] );
        $this->assertSame( 'https://example.com/github-plugin-updater-test/assets/images/banner-772x250.jpg', $config['plugin.banners']['low'] );
        $this->assertSame( 'https://example.com/github-plugin-updater-test/assets/images/icon-256x256.jpg', $config['plugin.icons']['default'] );

        unlink( $pluginFile );
        rmdir( $pluginDir );
    }
}

final class TestableMain extends Main
{
    /**
     * Expose computed config for assertions.
     *
     * @return array<string, mixed>
     */
    public function exposeConfig(): array
    {
        return $this->config;
    }
}
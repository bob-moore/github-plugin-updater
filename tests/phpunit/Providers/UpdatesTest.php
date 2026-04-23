<?php
/**
 * Updates provider tests.
 *
 * Verifies update eligibility and release asset handling.
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Providers
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Providers;

use Bmd\GithubWpUpdater\Providers\Updates;
use Bmd\GithubWpUpdater\Services\RemoteRequest;
use WP_Mock;
use WP_Mock\Tools\TestCase;

final class UpdatesTest extends TestCase
{
    /**
     * Ensures updates are allowed when the current PHP version exactly matches the remote requirement.
     *
     * @covers \Bmd\GithubWpUpdater\Providers\Updates::update
     */
    public function testUpdateAllowsExactPhpVersionRequirement(): void
    {
        $remote = $this->createMock( RemoteRequest::class );
        $remote->expects( $this->once() )
            ->method( 'getPluginInfo' )
            ->willReturn(
                [
                    'version'      => '2.0.0',
                    'requires'     => '6.5',
                    'tested'       => '6.6',
                    'requires_php' => PHP_VERSION,
                ]
            );
        $remote->expects( $this->once() )
            ->method( 'requestRelease' )
            ->with( '2.0.0' )
            ->willReturn(
                (object) [
                    'assets' => [
                        (object) [
                            'name' => 'plugin.zip',
                            'browser_download_url' => 'https://example.com/plugin.zip',
                        ],
                    ],
                    'created_at' => '2026-04-01T00:00:00Z',
                    'published_at' => '2026-04-02T00:00:00Z',
                ]
            );

        WP_Mock::userFunction(
            'get_bloginfo',
            [
                'times'  => 1,
                'args'   => [ 'version' ],
                'return' => '6.5',
            ]
        );
        $provider = new Updates(
            $remote,
            '1.0.0',
            'github-plugin-updater',
            'plugin.php',
            'github_wp_updater'
        );

        $transient = (object) [
            'checked' => [ 'github-plugin-updater/plugin.php' => '1.0.0' ],
            'response' => [],
        ];

        $result = $provider->update( $transient );

        $this->assertArrayHasKey( 'github-plugin-updater/plugin.php', $result->response );
        $this->assertSame( '2.0.0', $result->response['github-plugin-updater/plugin.php']->new_version );
    }

    /**
     * Ensures updates are skipped when the release has no zip asset.
     *
     * @covers \Bmd\GithubWpUpdater\Providers\Updates::update
     */
    public function testUpdateSkipsReleaseWithoutZipAsset(): void
    {
        $remote = $this->createMock( RemoteRequest::class );
        $remote->expects( $this->once() )
            ->method( 'getPluginInfo' )
            ->willReturn(
                [
                    'version'      => '2.0.0',
                    'requires'     => '6.5',
                    'tested'       => '6.6',
                    'requires_php' => PHP_VERSION,
                ]
            );
        $remote->expects( $this->once() )
            ->method( 'requestRelease' )
            ->with( '2.0.0' )
            ->willReturn(
                (object) [
                    'assets' => [],
                    'created_at' => '2026-04-01T00:00:00Z',
                    'published_at' => '2026-04-02T00:00:00Z',
                ]
            );

        WP_Mock::userFunction(
            'get_bloginfo',
            [
                'times'  => 1,
                'args'   => [ 'version' ],
                'return' => '6.5',
            ]
        );

        WP_Mock::userFunction( 'apply_filters', [ 'times' => 0 ] );

        $provider = new Updates(
            $remote,
            '1.0.0',
            'github-plugin-updater',
            'plugin.php',
            'github_wp_updater'
        );

        $transient = (object) [
            'checked' => [ 'github-plugin-updater/plugin.php' => '1.0.0' ],
            'response' => [],
        ];

        $result = $provider->update( $transient );

        $this->assertSame( [], $result->response );
    }
}
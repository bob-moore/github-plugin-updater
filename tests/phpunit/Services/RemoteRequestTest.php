<?php
/**
 * RemoteRequest service tests.
 *
 * Verifies caching and remote response handling for GitHub requests.
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Services
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Services;

use Bmd\GithubWpUpdater\Processors\PluginHeaders;
use Bmd\GithubWpUpdater\Services\RemoteRequest;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * Test suite for the RemoteRequest service.
 */
final class RemoteRequestTest extends TestCase
{
    /**
     * Defines core constants used by the service when WordPress is not loaded.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
            define( 'HOUR_IN_SECONDS', 3600 );
        }
    }

    /**
     * Ensures getPluginInfo() returns cached data without making remote calls.
     *
     * @covers \Bmd\GithubWpUpdater\Services\RemoteRequest::getPluginInfo
     */
    public function testGetPluginInfoReturnsCachedData(): void
    {
        $cached = [ 'version' => '2.0.0' ];
        $cacheKey = 'remote_info:acme:test-repo:main:plugin.php';

        WP_Mock::userFunction(
            'wp_cache_get',
            [
                'times'  => 1,
                'args'   => [ $cacheKey, 'github_wp_updater' ],
                'return' => $cached,
            ]
        );

        WP_Mock::userFunction( 'wp_remote_get', [ 'times' => 0 ] );

        $processor = $this->createMock( PluginHeaders::class );
        $processor->expects( $this->never() )->method( 'getFileData' );

        $service = new RemoteRequest(
            $processor,
            'acme',
            'test-repo',
            'main',
            'plugin.php',
            'github_wp_updater'
        );

        $this->assertSame( $cached, $service->getPluginInfo() );
    }

    /**
     * Ensures getPluginInfo() returns filtered defaults when request fails.
     *
     * @covers \Bmd\GithubWpUpdater\Services\RemoteRequest::getPluginInfo
     */
    public function testGetPluginInfoReturnsDefaultOnRequestError(): void
    {
        $default = [ 'version' => '1.0.0' ];
        $cacheKey = 'remote_info:acme:test-repo:main:plugin.php';
        $requestArgs = [
            'timeout'    => 15,
            'user-agent' => 'github_wp_updater',
            'headers'    => [
                'Accept' => 'application/vnd.github+json',
            ],
        ];

        WP_Mock::userFunction(
            'wp_cache_get',
            [
                'times'  => 1,
                'args'   => [ $cacheKey, 'github_wp_updater' ],
                'return' => false,
            ]
        );

        WP_Mock::userFunction(
            'wp_remote_get',
            [
                'times'  => 1,
                'args'   => [ 'https://raw.githubusercontent.com/acme/test-repo/main/plugin.php', $requestArgs ],
                'return' => new \WP_Error( 'network_error', 'Network failure' ),
            ]
        );

        $processor = $this->createMock( PluginHeaders::class );
        $processor->expects( $this->never() )->method( 'getFileData' );

        $service = new RemoteRequest(
            $processor,
            'acme',
            'test-repo',
            'main',
            'plugin.php',
            'github_wp_updater'
        );

        $this->assertSame( $default, $service->getPluginInfo( $default ) );
    }

    /**
     * Ensures getPluginInfo() parses response body and writes plugin headers to cache.
     *
     * @covers \Bmd\GithubWpUpdater\Services\RemoteRequest::getPluginInfo
     */
    public function testGetPluginInfoParsesAndCachesOnSuccess(): void
    {
        $body    = "<?php\n/*\nVersion: 2.0.0\n*/";
        $parsed  = [ 'version' => '2.0.0' ];
        $request = [ 'ok' => true ];
        $cacheKey = 'remote_info:acme:test-repo:main:plugin.php';
        $requestArgs = [
            'timeout'    => 15,
            'user-agent' => 'github_wp_updater',
            'headers'    => [
                'Accept' => 'application/vnd.github+json',
            ],
        ];

        WP_Mock::userFunction(
            'wp_cache_get',
            [
                'times'  => 1,
                'args'   => [ $cacheKey, 'github_wp_updater' ],
                'return' => false,
            ]
        );

        WP_Mock::userFunction(
            'wp_remote_get',
            [
                'times'  => 1,
                'args'   => [ 'https://raw.githubusercontent.com/acme/test-repo/main/plugin.php', $requestArgs ],
                'return' => $request,
            ]
        );

        WP_Mock::userFunction(
            'wp_remote_retrieve_response_code',
            [
                'times'  => 1,
                'args'   => [ $request ],
                'return' => 200,
            ]
        );

        WP_Mock::userFunction(
            'wp_remote_retrieve_body',
            [
                'times'  => 1,
                'args'   => [ $request ],
                'return' => $body,
            ]
        );

        WP_Mock::userFunction(
            'wp_cache_set',
            [
                'times' => 1,
                'args'  => [ $cacheKey, $parsed, 'github_wp_updater', HOUR_IN_SECONDS ],
            ]
        );

        $processor = $this->createMock( PluginHeaders::class );
        $processor->expects( $this->once() )
            ->method( 'getFileData' )
            ->with( $body )
            ->willReturn( $parsed );

        $service = new RemoteRequest(
            $processor,
            'acme',
            'test-repo',
            'main',
            'plugin.php',
            'github_wp_updater'
        );

        $this->assertSame( $parsed, $service->getPluginInfo() );
    }

    /**
     * Ensures requestRelease() returns null when GitHub returns invalid JSON.
     *
     * @covers \Bmd\GithubWpUpdater\Services\RemoteRequest::requestRelease
     */
    public function testRequestReleaseReturnsNullForInvalidJson(): void
    {
        $requestArgs = [
            'timeout'    => 15,
            'user-agent' => 'github_wp_updater',
            'headers'    => [
                'Accept' => 'application/vnd.github+json',
            ],
        ];
        $cacheKey = 'release_2.0.0:acme:test-repo:main:plugin.php';
        $request = [ 'ok' => true ];

        WP_Mock::userFunction(
            'wp_cache_get',
            [
                'times'  => 1,
                'args'   => [ $cacheKey, 'github_wp_updater' ],
                'return' => false,
            ]
        );

        WP_Mock::userFunction(
            'wp_remote_get',
            [
                'times'  => 1,
                'args'   => [ 'https://api.github.com/repos/acme/test-repo/releases/tags/2.0.0', $requestArgs ],
                'return' => $request,
            ]
        );

        WP_Mock::userFunction(
            'wp_remote_retrieve_response_code',
            [
                'times'  => 1,
                'args'   => [ $request ],
                'return' => 200,
            ]
        );

        WP_Mock::userFunction(
            'wp_remote_retrieve_body',
            [
                'times'  => 1,
                'args'   => [ $request ],
                'return' => '{invalid-json',
            ]
        );

        WP_Mock::userFunction( 'wp_cache_set', [ 'times' => 0 ] );

        $processor = $this->createMock( PluginHeaders::class );

        $service = new RemoteRequest(
            $processor,
            'acme',
            'test-repo',
            'main',
            'plugin.php',
            'github_wp_updater'
        );

        $this->assertNull( $service->requestRelease( '2.0.0' ) );
    }
}

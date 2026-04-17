<?php
/**
 * PluginInfo provider tests.
 *
 * Verifies plugin information response gating and transformation behavior.
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Providers
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Providers;

use Bmd\GithubWpUpdater\PHPUnit\Traits\ModuleTrait;
use Bmd\GithubWpUpdater\Providers\PluginInfo;
use Bmd\GithubWpUpdater\Services\ReadmeParser;
use Bmd\GithubWpUpdater\Services\RemoteRequest;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * Test suite for the PluginInfo provider.
 */
final class PluginInfoTest extends TestCase
{
    use ModuleTrait;

    /**
     * Fully qualified class name used by shared module trait assertions.
     *
     * @var class-string<PluginInfo>
     */
    const TEST_CLASS = PluginInfo::class;

    /**
     * Ensures pluginInfo() returns original result when action is not plugin_information.
     *
     * @covers \Bmd\GithubWpUpdater\Providers\PluginInfo::pluginInfo
     */
    public function testPluginInfoReturnsOriginalResultForDifferentAction(): void
    {
        $remote = $this->createMock( RemoteRequest::class );
        $remote->expects( $this->never() )->method( 'getPluginInfo' );

        $parser = $this->createMock( ReadmeParser::class );
        $parser->expects( $this->never() )->method( 'parseSections' );

        $provider = new PluginInfo( $remote, $parser, 'github-plugin-updater', 'plugin.php', 'github_wp_updater' );

        $result = [ 'unchanged' => true ];
        $args   = (object) [ 'slug' => 'github-plugin-updater' ];

        $this->assertSame( $result, $provider->pluginInfo( $result, 'query_plugins', $args ) );
    }

    /**
     * Ensures pluginInfo() returns original result when requested slug does not match provider slug.
     *
     * @covers \Bmd\GithubWpUpdater\Providers\PluginInfo::pluginInfo
     */
    public function testPluginInfoReturnsOriginalResultForMismatchedSlug(): void
    {
        $remote = $this->createMock( RemoteRequest::class );
        $remote->expects( $this->never() )->method( 'getPluginInfo' );

        $parser = $this->createMock( ReadmeParser::class );
        $parser->expects( $this->never() )->method( 'parseSections' );

        $provider = new PluginInfo( $remote, $parser, 'github-plugin-updater', 'plugin.php', 'github_wp_updater' );

        $result = false;
        $args   = (object) [ 'slug' => 'other-plugin' ];

        $this->assertFalse( $provider->pluginInfo( $result, 'plugin_information', $args ) );
    }

    /**
     * Ensures pluginInfo() builds an enriched response for matching plugin requests.
     *
     * @covers \Bmd\GithubWpUpdater\Providers\PluginInfo::pluginInfo
     */
    public function testPluginInfoBuildsResponseForMatchingRequest(): void
    {
        $remoteResponse = [
            'version' => '3.1.0',
            'name'    => 'GitHub Updater',
        ];

        $sections = [ 'Description' => '<p>Plugin description</p>' ];

        $remote = $this->createMock( RemoteRequest::class );
        $remote->expects( $this->once() )
            ->method( 'getPluginInfo' )
            ->willReturn( $remoteResponse );
        $remote->expects( $this->once() )
            ->method( 'requestRawContent' )
            ->with( 'readme.md' )
            ->willReturn( '# Description\nPlugin description' );

        $parser = $this->createMock( ReadmeParser::class );
        $parser->expects( $this->once() )
            ->method( 'parseSections' )
            ->with( '# Description\nPlugin description' )
            ->willReturn( $sections );

        $provider = new PluginInfo( $remote, $parser, 'github-plugin-updater', 'plugin.php', 'github_wp_updater' );

        $args = (object) [ 'slug' => 'github-plugin-updater' ];

        $result = $provider->pluginInfo( false, 'plugin_information', $args );

        $this->assertIsObject( $result );
        $this->assertSame( '3.1.0', $result->new_version );
        $this->assertSame( $sections, $result->sections );
    }
}
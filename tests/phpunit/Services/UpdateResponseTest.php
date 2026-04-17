<?php
/**
 * UpdateResponse processor tests.
 *
 * Verifies default update payload construction and override merging.
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Services
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Services;

use Bmd\GithubWpUpdater\PHPUnit\Traits\ModuleTrait;
use Bmd\GithubWpUpdater\Processors\UpdateResponse;
use WP_Mock\Tools\TestCase;

/**
 * Test suite for the UpdateResponse processor.
 */
final class UpdateResponseTest extends TestCase
{
    use ModuleTrait;

    /**
     * Fully qualified class name used by shared module trait assertions.
     *
     * @var class-string<UpdateResponse>
     */
    const TEST_CLASS = UpdateResponse::class;

    /**
     * Ensures mergeUpdateResponse() returns expected defaults from constructor values.
     *
     * @covers \Bmd\GithubWpUpdater\Processors\UpdateResponse::mergeUpdateResponse
     */
    public function testMergeUpdateResponseReturnsDefaultShape(): void
    {
        $processor = new UpdateResponse(
            'github-plugin-updater',
            'plugin.php',
            '2.3.4',
            [ 'default' => 'https://example.com/icon.png' ],
            [ 'high' => 'https://example.com/banner.png' ],
            'github_wp_updater'
        );

        $response = $processor->mergeUpdateResponse();

        $this->assertSame( 'github-plugin-updater/plugin.php', $response->id );
        $this->assertSame( 'github-plugin-updater', $response->slug );
        $this->assertSame( '2.3.4', $response->new_version );
        $this->assertSame( [ 'default' => 'https://example.com/icon.png' ], $response->icons );
        $this->assertSame( [ 'high' => 'https://example.com/banner.png' ], $response->banners );
        $this->assertInstanceOf( \stdClass::class, $response->compatibility );
    }

    /**
     * Ensures mergeUpdateResponse() applies caller-supplied overrides.
     *
     * @covers \Bmd\GithubWpUpdater\Processors\UpdateResponse::mergeUpdateResponse
     */
    public function testMergeUpdateResponseAppliesOverrides(): void
    {
        $processor = new UpdateResponse(
            'github-plugin-updater',
            'plugin.php',
            '2.3.4',
            [],
            [],
            'github_wp_updater'
        );

        $response = $processor->mergeUpdateResponse(
            [
                'new_version'  => '2.4.0',
                'package'      => 'https://example.com/download.zip',
                'requires_php' => '8.2',
            ]
        );

        $this->assertSame( '2.4.0', $response->new_version );
        $this->assertSame( 'https://example.com/download.zip', $response->package );
        $this->assertSame( '8.2', $response->requires_php );
    }
}

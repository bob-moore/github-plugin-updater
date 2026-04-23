<?php
/**
 * PluginHeaders processor tests.
 *
 * Verifies delegated file parsing and raw-content fallback behavior.
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
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * Test suite for the PluginHeaders processor.
 */
final class PluginHeadersTest extends TestCase
{
    /**
     * Defines constants used by processor logic when WordPress is not loaded.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        if ( ! defined( 'KB_IN_BYTES' ) ) {
            define( 'KB_IN_BYTES', 1024 );
        }
    }

    /**
     * Ensures getFileData() delegates to get_file_data() when passed a real file path.
     *
     * @covers \Bmd\GithubWpUpdater\Processors\PluginHeaders::getFileData
     */
    public function testGetFileDataDelegatesForFilePath(): void
    {
        $tempFile = tempnam( sys_get_temp_dir(), 'gh-updater-plugin-' );
        file_put_contents( $tempFile, "<?php\n/*\nVersion: 1.2.3\n*/" );

        $expected = [
            'plugin_uri'   => 'https://example.com',
            'version'      => '1.2.3',
            'requires'     => '6.5',
            'tested'       => '6.6',
            'requires_php' => '8.2',
        ];

        WP_Mock::userFunction(
            'get_file_data',
            [
                'times'  => 1,
                'return' => $expected,
            ]
        );

        $processor = new PluginHeaders( 'github_wp_updater' );

        $this->assertSame( $expected, $processor->getFileData( $tempFile ) );

        unlink( $tempFile );
    }

    /**
     * Ensures getFileData() returns known keys with empty values when no header matches exist.
     *
     * @covers \Bmd\GithubWpUpdater\Processors\PluginHeaders::getFileData
     */
    public function testGetFileDataReturnsEmptyDefaultsForRawContentWithoutMatches(): void
    {
        $processor = new PluginHeaders( 'github_wp_updater' );

        $result = $processor->getFileData( "This content has no plugin headers.\nJust plain text." );

        $this->assertSame(
            [
                'plugin_uri'   => '',
                'version'      => '',
                'requires'     => '',
                'tested'       => '',
                'requires_php' => '',
            ],
            $result
        );
    }
}

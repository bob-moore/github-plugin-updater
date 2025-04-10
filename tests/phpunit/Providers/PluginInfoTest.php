<?php
/**
 * Plugin Info Test Cases
 *
 * PHP Version 8.2
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Services
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\PHPUnit\Providers;

use MarkedEffect\GHPluginUpdater\Services,
    MarkedEffect\GHPluginUpdater\Providers,
    MarkedEffect\GHPluginUpdater\PHPUnit\Common;

use WP_Mock\Tools\TestCase as TestCase;

use Mockery;

class PluginInfoTest extends TestCase
{
    use Common\ModuleTests;
    /**
     * Instance of the module being tested
     *
     * @var Providers\PluginInfo
     */
    protected $module;
    /**
     * Setup the test case with a new instance of the class
     *
     * @return void
     */
    public function setUp(): void
    {
        /**
         * Mock the remote request class that fetches information from
         * the github api
         */
        $remote_request = Mockery::mock( Services\RemoteRequest::class );
        $remote_request
            ->shouldReceive('getPluginInfo')
            ->andReturn( [
                'plugin_uri'      => 'https://example.com/plugin-uri',
                'version'         => '1.0.0',
                'requires'        => '6.0',
                'tested'          => '6.1',
                'requires_php'    => '7.4',
            ]);
        $remote_request
            ->shouldReceive('requestRawContent')
            ->andReturn( '' );
        /**
         * Mock the readme parser class that parses the readme file
         * and returns the sections
         */
        $readme_parser = Mockery::mock( Services\ReadmeParser::class );
        $readme_parser
            ->shouldReceive('parseSections')
            ->andReturn( [
                'Test' => '',
                'Content' => '<h1>Test</h1><p>Content</p>',
            ]);
        
        $this->module = new Providers\PluginInfo(
            $remote_request,
            $readme_parser,
            'plugin-slug',
            'plugin-file',
            'plugin-package'
        );

        parent::setUp();
    }
    /**
     * Nullify the service class to start fresh on the next test
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->module = null;

        parent::tearDown();
    }
    /**
     * Test the parseSections method
     * 
     * @covers ReadmeParser::parseSections
     *
     * @return void
     */
    public function testPluginInfo(): void
    {

        $actual = $this->module->pluginInfo( [], 'plugin_information', (object) [
            'slug' => 'plugin-slug',
        ] );
        
        $expected = (object) [
            'plugin_uri'      => 'https://example.com/plugin-uri',
            'version'         => '1.0.0',
            'requires'        => '6.0',
            'tested'          => '6.1',
            'requires_php'    => '7.4',
            'new_version'     => '1.0.0',
            'sections'        => [
                'Test' => '',
                'Content' => '<h1>Test</h1><p>Content</p>',
            ],
        ];

        $this->assertEquals( $expected, $actual );
    }
}
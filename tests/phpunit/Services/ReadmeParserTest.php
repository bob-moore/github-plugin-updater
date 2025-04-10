<?php
/**
 * Readme Parser Test
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

namespace MarkedEffect\GHPluginUpdater\PHPUnit\Services;

use MarkedEffect\GHPluginUpdater\Services\ReadmeParser;
use WP_Mock\Tools\TestCase as TestCase;
use League\CommonMark\CommonMarkConverter;
use Mockery;

class ReadmeParserTest extends TestCase
{
    /**
     * Instance of the module being tested
     *
     * @var \Mwf\Cornerstone\Abstracts\Module
     */
    protected $module;
    /**
     * Setup the test case with a new instance of the class
     *
     * @return void
     */
    public function setUp(): void
    {
        $markdown_parser = new CommonMarkConverter();

        $this->module = new ReadmeParser( $markdown_parser );

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
    public function testParseSections(): void
    {
        $markdown_parser = new CommonMarkConverter();

        $content = "\n" . '## Subsection';
        $content .= "\n" . 'Content for subsection';

        $markdown = '# Test';
        $markdown .= "\n" . '# Content';
        $markdown .= $content;

        $markdown_parser->convert( trim( $content ) )->getContent();

        $expected = [
            'Test' => '',
            'Content' => $markdown_parser->convert( trim( $content ) )->getContent(),
        ];
        
        $actual = $this->module->parseSections( $markdown );

        $this->assertEquals( $expected, $actual );
    }

}
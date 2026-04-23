<?php
/**
 * ReadmeParser service tests.
 *
 * Verifies section parsing and markdown conversion behavior.
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Services
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Services;

use Bmd\GithubWpUpdater\Services\ReadmeParser;
use League\CommonMark\CommonMarkConverter;
use WP_Mock\Tools\TestCase;

/**
 * Test suite for the ReadmeParser service.
 */
final class ReadmeParserTest extends TestCase
{
    /**
     * Ensures parseSections() extracts H1 blocks and converts markdown body to HTML.
     *
     * @covers \Bmd\GithubWpUpdater\Services\ReadmeParser::parseSections
     */
    public function testParseSectionsReturnsConvertedSections(): void
    {
        $parser = new ReadmeParser( new CommonMarkConverter(), 'github_wp_updater' );

        $markdown = implode(
            "\n",
            [
                '# Description',
                'A **bold** package description.',
                '',
                '# Installation',
                '1. Composer install',
                '2. Activate plugin',
            ]
        );

        $sections = $parser->parseSections( $markdown );

        $this->assertArrayHasKey( 'Description', $sections );
        $this->assertArrayHasKey( 'Installation', $sections );
        $this->assertStringContainsString( '<strong>bold</strong>', $sections['Description'] );
        $this->assertStringContainsString( '<ol>', $sections['Installation'] );
    }

    /**
     * Ensures parseSections() returns an empty array when no H1 sections are present.
     *
     * @covers \Bmd\GithubWpUpdater\Services\ReadmeParser::parseSections
     */
    public function testParseSectionsReturnsEmptyArrayWithoutHeadings(): void
    {
        $parser = new ReadmeParser( new CommonMarkConverter(), 'github_wp_updater' );

        $sections = $parser->parseSections( "No H1 headings in this text.\nOnly body content." );

        $this->assertSame( [], $sections );
    }
}

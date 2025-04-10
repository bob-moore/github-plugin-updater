<?php
/**
 * Parse Readme file content into sections
 *
 * PHP Version 8.2
 *
 * @package github_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Services;

use MarkedEffect\GHPluginUpdater\Core\Abstracts;

use League\CommonMark\CommonMarkConverter;

/**
 * Class for parsing readme files
 *
 * @subpackage Services
 */
class ReadmeParser extends Abstracts\Module
{
	/**
	 * Public constructor
	 *
	 * @param CommonMarkConverter $markdown_parser : markdown parser.
	 * @param string              $package         : package name.
	 */
	public function __construct(
		protected CommonMarkConverter $markdown_parser,
		string $package = '',
	) {
		parent::__construct( $package );
	}
	/**
	 * Parse markdown content into sections
	 *
	 * @param string $markdown The markdown content to parse.
	 * @return array<string, string> Associative array of sections with H1 titles as keys.
	 */
	public function parseSections( string $markdown ): array
	{
		// Initialize sections array.
		$sections = [];

		// Split content by lines.
		$lines = explode( "\n", $markdown );

		$current_section = null;
		$current_content = [];
		$in_section = false;

		foreach ( $lines as $line ) {
			// Check if line is an H1 heading.
			if ( preg_match( '/^# (.+)$/', $line, $matches ) ) {
				// If we were already in a section, save it.
				if ( $in_section && ( null !== $current_section ) ) {
					$sections[ $current_section ] = $this->markdown_parser->convert( trim( implode( "\n", $current_content ) ) )->getContent();
					$current_content = [];
				}

				// Set the new current section.
				$current_section = trim( $matches[1] );
				$in_section = true;
				continue;
			}

			// If we're in a section, collect the content.
			if ( $in_section ) {
				$current_content[] = $line;
			}
		}

		// Save the last section if there is one.
		if ( $in_section && ( null !== $current_section ) ) {
			$sections[ $current_section ] = $this->markdown_parser->convert( trim( implode( "\n", $current_content ) ) )->getContent();
		}

		return $sections;
	}
}

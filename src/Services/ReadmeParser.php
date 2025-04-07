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

use Parsedown;

/**
 * Class for parsing readme files
 *
 * @subpackage Services
 */

class ReadmeParser extends Abstracts\Module
{
    public function __construct(
        protected Parsedown $parsedown,
        string $package = '',
    ) {
        parent::__construct( $package );
    }
    /**
     * Parse markdown content into sections
     * 
     * @param string $markdown The markdown content to parse
     * @return array Associative array of sections with H1 titles as keys
     */
    public function parseSections( string $markdown ): array
    {
        // Initialize sections array
        $sections = [];

        // Split content by lines
        $lines = explode( "\n", $markdown );

        $currentSection = null;
        $currentContent = [];
        $inSection = false;

        foreach ( $lines as $line ) {
            // Check if line is an H1 heading
            if ( preg_match('/^# (.+)$/', $line, $matches ) ) {
                // If we were already in a section, save it
                if ($inSection && $currentSection !== null) {
                    $sections[$currentSection] = $this->parsedown->text( trim( implode("\n", $currentContent) ) );
                    $currentContent = [];
                }

                // Set the new current section
                $currentSection = trim($matches[1]);
                $inSection = true;
                continue;
            }

            // If we're in a section, collect the content
            if ($inSection) {
                $currentContent[] = $line;
            }
        }

        // Save the last section if there is one
        if ($inSection && $currentSection !== null) {
            $sections[$currentSection] = $this->parsedown->text( trim( implode("\n", $currentContent) ) );
        }

        return $sections;
    }
}

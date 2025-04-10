<?php
/**
 * Controller interface definition
 *
 * PHP Version 8.2
 *
 * @package github_plugin_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Core\Interfaces;

/**
 * Define controller requirements
 *
 * @subpackage Interfaces
 */

interface Controller
{
	/**
	 * Return an array of service definitions
	 *
	 * @return array<string, mixed>
	 */
	public static function getServiceDefinitions(): array;
}

<?php
/**
 * Shared utility helpers.
 *
 * @package github_plugin_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 */

namespace Bmd\GithubWpUpdater;

/**
 * Small package-local utilities.
 */
class Utilities
{
	/**
	 * Convert a value into an underscore-separated slug.
	 *
	 * @param string $value Value to slugify.
	 *
	 * @return string
	 */
	public static function slugify( string $value ): string
	{
		$value = strtolower( $value );
		$value = preg_replace( '/[^a-z0-9]+/', '_', $value ) ?? '';
		$value = trim( $value, '_' );

		return $value;
	}
}

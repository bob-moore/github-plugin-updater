<?php
/**
 * Base module class.
 *
 * @package github_plugin_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 */

namespace Bmd\GithubWpUpdater;

use DI\Attribute\Inject;

/**
 * Abstract base for injectable updater classes.
 */
abstract class Module
{
	/**
	 * Package slug used for filter names, cache groups, and request headers.
	 *
	 * @var string
	 */
	#[Inject( 'config.package' )]
	protected string $package = '';

	/**
	 * Public constructor.
	 *
	 * @param string $package Optional package override.
	 */
	public function __construct( string $package = '' )
	{
		if ( '' !== $package ) {
			$this->package = Utilities::slugify( $package );
		}
	}

	/**
	 * Getter for the package slug.
	 *
	 * @return string
	 */
	public function getPackage(): string
	{
		return $this->package;
	}
}

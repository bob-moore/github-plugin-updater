<?php
/**
 * MountableComponent definition file
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GithubUpdater\Core\Abstracts;

use MarkedEffect\GithubUpdater\Core\Interfaces,
	MarkedEffect\GithubUpdater\Core\Traits,
	MarkedEffect\GithubUpdater\Services;

/**
 * Abstract MountableComponent class
 *
 * @subpackage Abstracts
 */
abstract class ContextHandler extends Module implements Interfaces\ContextHandler, Interfaces\Mountable
{
	use Traits\Mountable;
	use Traits\ActionLoader;
	use Traits\FilterLoader;

	/**
	 * Public constructor
	 *
	 * @param Services\StyleLoader  $style_loader : style loader service instance.
	 * @param Services\ScriptLoader $script_loader : script loader service instance.
	 */
	public function __construct(
		protected Services\StyleLoader $style_loader,
		protected Services\ScriptLoader $script_loader
	) {
		add_action( "{$this->getClassSlug()}_mount", [ $this, 'mountActions' ], 5 );
		add_action( "{$this->getClassSlug()}_mount", [ $this, 'mountFilters' ], 5 );

		parent::__construct();
	}

	/**
	 * Register a JS file with WordPress
	 *
	 * @param string             $handle : handle to register.
	 * @param string             $path : relative path to script.
	 * @param array<int, string> $dependencies : any set dependencies not in assets file, optional.
	 * @param string             $version : version of JS file, optional.
	 * @param boolean            $in_footer : whether to enqueue in footer, optional.
	 *
	 * @return void
	 */
	public function enqueueScript(
		string $handle,
		string $path,
		array $dependencies = [],
		string $version = '',
		$in_footer = true
	): void {
		$this->script_loader->enqueue(
			$handle,
			$path,
			$dependencies,
			$version,
			$in_footer
		);
	}
	/**
	 * Enqueue a style in the dist/build directories
	 *
	 * @param string             $handle : handle to register.
	 * @param string             $path : relative path to css file.
	 * @param array<int, string> $dependencies : any dependencies that should be loaded first, optional.
	 * @param string             $version : version of CSS file, optional.
	 * @param string             $screens : what screens to register for, optional.
	 *
	 * @return void
	 */
	public function enqueueStyle(
		string $handle,
		string $path,
		array $dependencies = [],
		string $version = null,
		$screens = 'all'
	): void {
		$this->style_loader->enqueue(
			$handle,
			$path,
			$dependencies,
			$version,
			$screens
		);
	}
}

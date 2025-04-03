<?php
/**
 * Frontend Context Handler Definition
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GithubUpdater\Handlers;

use MarkedEffect\GithubUpdater\Core\Abstracts,
	MarkedEffect\GithubUpdater\Services;

/**
 * Frontend router class
 *
 * @subpackage Route
 */
class Frontend extends Abstracts\ContextHandler
{
	/**
	* Public constructor
	*
	* @param Services\StyleLoader  $style_loader : style loader service instance.
	* @param Services\ScriptLoader $script_loader : script loader service instance.
	*/
	public function __construct(
		protected Services\StyleLoader $style_loader,
		protected Services\ScriptLoader $script_loader,
		protected Services\PostMeta $post_meta,
		string $package = ''
	) {

		parent::__construct(
			$style_loader,
			$script_loader,
			$package
		);
	}
	/**
	 * Mount actions for this class
	 *
	 * @return void
	 */
	public function mountActions(): void
	{
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssets' ] );
	}
	/**
	 * Enqueue admin styles and JS bundles
	 *
	 * @return void
	 */
	public function enqueueAssets(): void
	{
		$this->enqueueScript(
			"{$this->package}-frontend",
			'build/frontend.js'
		);
		$this->enqueueStyle(
			"{$this->package}-frontend",
			'build/frontend.css'
		);
	}
}

<?php
/**
 * Admin Route Definition
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Handlers;

use MarkedEffect\GHPluginUpdater\Core\Abstracts;

/**
 * Admin router class
 *
 * @subpackage Route
 */
class Admin extends Abstracts\ContextHandler
{
	/**
	 * Mount actions for this class
	 *
	 * @return void
	 */
	public function mountActions(): void
	{
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
	}
	/**
	 * Enqueue admin styles and JS bundles
	 *
	 * @return void
	 */
	public function enqueueAssets(): void
	{
		// $this->enqueueScript(
		// 	"{$this->package}-admin",
		// 	'build/scripts/admin.bundle.js'
		// );
		// $this->enqueueStyle(
		// 	"{$this->package}-admin",
		// 	'build/admin.css'
		// );
	}
}

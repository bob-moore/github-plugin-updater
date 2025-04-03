<?php
/**
 * Admin Route Definition
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

/**
 * Admin router class
 *
 * @subpackage Route
 */
class BlockEditor extends Admin
{
	/**
	* Mount actions for this class
	*
	* @return void
	*/
	public function mountActions(): void
	{
		add_action( 'enqueue_block_assets', [ $this, 'enqueueBlockAssets' ] );

		parent::mountActions();
	}
	/**
	* Enqueue block editor styles and JS bundles
	*
	* @return void
	*/
	public function enqueueBlockAssets(): void
	{
		$this->script_loader->enqueue(
			"{$this->package}-editor",
			'build/scripts/editor.bundle.js'
		);
		$this->style_loader->enqueue(
			"{$this->package}-editor",
			'build/styles/blocks.bundle.css'
		);
	}

}

<?php
/**
 * Mountable interface definition
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Core\Interfaces;

/**
 * Action Module interface requirements
 *
 * Used to type hint against MarkedEffect\GHPluginUpdater\Core\Interfaces\ActionModule.
 *
 * @subpackage Interfaces
 */
interface Mountable
{
	/**
	 * Check if loading action has already fired
	 *
	 * @return int
	 */
	public function hasMounted(): int;
	/**
	 * Fire Mounted action on mount
	 *
	 * @return void
	 */
	public function onMount(): void;
}

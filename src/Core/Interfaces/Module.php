<?php
/**
 * Module interface definition
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
 * Module interface requirements
 *
 * @subpackage Interfaces
 */
interface Module
{
	/**
	 * Setter for package name
	 *
	 * @param string $package : string name of the package.
	 *
	 * @return void
	 */
	public function setPackage( string $package ): void;
	/**
	 * Getter for package name
	 *
	 * @return string
	 */
	public function getPackage(): string;
}

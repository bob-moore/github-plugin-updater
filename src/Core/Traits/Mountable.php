<?php
/**
 * Action Loader trait definition file
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Core\Traits;

use MarkedEffect\GHPluginUpdater\Core\Interfaces,
MarkedEffect\GHPluginUpdater\Core\Helpers;

/**
 * Action Loader trait
 *
 * @subpackage Traits
 */
trait Mountable {
	/**
	 * Name of class converted to usable slug
	 *
	 * Fully qualified class name, converted to lowercase with forward slashes.
	 *
	 * @var string
	 */
	protected string $class_slug = '';
	/**
	 * Set the class slug
	 *
	 * @return void
	 */
	public function setClassSlug(): void
	{
		$this->class_slug = Helpers::slugify( static::class );
	}
	/**
	 * Get the class slug
	 *
	 * @return string
	 */
	public function getClassSlug(): string
	{
		if ( empty( $this->class_slug ) ) {
			$this->setClassSlug();
		}

		return $this->class_slug;
	}
	/**
	 * Check if class has already mounted an instance
	 *
	 * @return int
	 */
	public function hasMounted(): int
	{
		return did_action( "{$this->getClassSlug()}_mount" );
	}
	/**
	 * Fire Mounted action on mount
	 *
	 * @return void
	 */
	public function onMount(): void
	{
		if ( ! $this->hasMounted() ) {
			do_action( "{$this->getClassSlug()}_mount" );
		}
	}
	/**
	 * Generic mount method
	 *
	 * @return void
	 */
	public function mount(): void {}
}

<?php
/**
 * Module definition file
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Core\Abstracts;

use MarkedEffect\GHPluginUpdater\Core\Interfaces;

use DI\Attribute\Inject;

/**
 * Abstract Module class
 *
 * A module is the most basic type of class in the plugin. It is a class that
 * belongs to the package (plugin), and share's the plugin's namespace and package definition.
 *
 * @subpackage Abstracts
 */
abstract class Module implements Interfaces\Module
{
	/**
	 * Package this service belongs to
	 *
	 * $package defines a group of classes used together. For instance, classes
	 * outside of this plugin can extend this class, as part of a theme package.
	 *
	 * @var string
	 */
	#[Inject( 'config.package' )]
	protected string $package = '';
	/**
	 * Public constructor
	 *
	 * @param string $package : optional package name to set.
	 */
	public function __construct( string $package = '' )
	{
		if ( ! empty( $package ) ) {
			$this->setPackage( $package );
		}
	}
	/**
	 * Setter for package field
	 *
	 * @param string $package : string to set package to, transformed to Underscore separated & lowercase.
	 *
	 * @return void
	 */
	public function setPackage( string $package ): void
	{
		$this->package = strtolower( str_replace( [ '\\', '/', ' ' ], '_', trim( $package ) ) );
	}
	/**
	 * Getter for package field
	 *
	 * @return string
	 */
	public function getPackage(): string
	{
		return $this->package;
	}
}

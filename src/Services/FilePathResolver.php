<?php
/**
 * Router Service Definition
 *
 * PHP Version 8.2
 *
 * @package mwf_cornerstone
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GHPluginUpdater\Services;

use MarkedEffect\GHPluginUpdater\Core\Abstracts;

use DI\Attribute\Inject;

/**
 * Service class for router actions
 *
 * @subpackage Services
 */
class FilePathResolver extends Abstracts\Module
{
	/**
	 * Directory path to plugin instance
	 *
	 * @var string
	 */
	protected string $dir = '';
	/**
	 * Public constructor
	 *
	 * @param string $root_dir : root path of the plugin.
	 * @param string $package : optional package name.
	 * 
	 * @see https://php-di.org/doc/attributes.html
	 */
	#[Inject(['root_dir' => 'config.dir'])]
	public function __construct( string $root_dir, string $package = '' )
	{
		$this->dir = trim( untrailingslashit( $root_dir ) );

		parent::__construct( $package );
	}
	/**
	 * Get the directory path with string appended
	 *
	 * @param string $append : string to append to the directory path.
	 *
	 * @return string complete url
	 */
	public function resolve( string $append = '' ): string
	{
		return $this->appendDir( $this->dir, $append );
	}
	/**
	 * Append string safely to end of a Directory
	 *
	 * @param string $base : the base directory path.
	 * @param string $append : the string to append.
	 *
	 * @return string
	 */
	protected function appendDir( string $base, string $append = '' ): string
	{
		$append = trim( $append );
		return ! empty( $append )
			? trim( untrailingslashit( trailingslashit( $base ) . ltrim( $append, '/' ) ) )
			: $base;
	}
}

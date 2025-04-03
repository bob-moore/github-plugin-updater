<?php
/**
 * Controller interface definition
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GithubUpdater\Core\Interfaces;

/**
 * Define controller requirements
 *
 * @subpackage Interfaces
 */

interface Controller
{
	/**
	 * Return an array of service definitions
	 *
	 * @return array<string, mixed>
	 */
	public static function getServiceDefinitions(): array;
}

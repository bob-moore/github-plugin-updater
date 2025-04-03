<?php
/**
 * Service Controller
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GithubUpdater\Controllers;

use MarkedEffect\GithubUpdater\Services\ServiceLocator,
	MarkedEffect\GithubUpdater\Services,
	MarkedEffect\GithubUpdater\Core\Abstracts;

/**
 * Controls the registration and execution of services
 *
 * @subpackage Controllers
 */
class ServiceController extends Abstracts\Controller
{
	/**
	 * Get definitions that should be added to the service container
	 *
	 * @return array<string, mixed>
	 */
	public static function getServiceDefinitions(): array
	{
		return [
			Services\FilePathResolver::class => ServiceLocator::autowire(),
			Services\UrlResolver::class      => ServiceLocator::autowire(),
			Services\RemoteRequest::class    => ServiceLocator::autowire()
		];
	}
}

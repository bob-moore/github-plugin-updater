<?php
/**
 * Service Controller
 *
 * PHP Version 8.2
 *
 * @package github_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace Bmd\GithubWpUpdater\Controllers;

use Bmd\GithubWpUpdater\Services,
	Bmd\WPFramework\Abstracts,
	Bmd\WPFramework\Services\FilePathResolver,
	Bmd\WPFramework\Services\ServiceLocator,
	Bmd\WPFramework\Services\UrlResolver;

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
			FilePathResolver::class                       => ServiceLocator::autowire(),
			UrlResolver::class                            => ServiceLocator::autowire(),
			Services\RemoteRequest::class                 => ServiceLocator::autowire(),
			Services\ReadmeParser::class                  => ServiceLocator::autowire(),
			\League\CommonMark\CommonMarkConverter::class => ServiceLocator::autowire(),
		];
	}
}

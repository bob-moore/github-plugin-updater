<?php
/**
 * Provider Controller
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @category Controller
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GithubUpdater\Controllers;

use MarkedEffect\GithubUpdater\Providers,
	MarkedEffect\GithubUpdater\Services\ServiceLocator,
	MarkedEffect\GithubUpdater\Core\Helpers,
	MarkedEffect\GithubUpdater\Core\Abstracts;

use DI\Attribute\Inject;

/**
 * Controls the registration and execution of providers
 *
 * @category Controller
 */
class ProviderController extends Abstracts\Controller
{
	/**
	 * Get definitions that should be added to the service container
	 *
	 * @return array<string, mixed>
	 */
	public static function getServiceDefinitions(): array
	{
		return [
			Providers\PluginInfo::class        => ServiceLocator::autowire(),
			Providers\Updates::class       => ServiceLocator::autowire(),
		];
	}

	/**
	 * Mount PluginInfo Provider
	 *
	 * @param Providers\PluginInfo $provider : instance of PluginInfo provider.
	 *
	 * @return void
	 */
	#[Inject]
	public function mountPluginInfoProvider( Providers\PluginInfo $provider ): void
	{
		// add_action( 'plugins_loaded', [ $provider, 'dispatch' ], 4 );
	}

	/**
	 * Mount Updates Provider
	 *
	 * @param Providers\Updates $provider : instance of Updates provider.
	 *
	 * @return void
	 */
	#[Inject]
	public function mountUpdatesProvider( Providers\Updates $provider ): void
	{
		
	}
}

<?php
/**
 * Provider Controller
 *
 * PHP Version 8.2
 *
 * @package github_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Controllers;

use MarkedEffect\GHPluginUpdater\Providers,
	MarkedEffect\GHPluginUpdater\Services\ServiceLocator,
	MarkedEffect\GHPluginUpdater\Core\Helpers,
	MarkedEffect\GHPluginUpdater\Core\Abstracts;

use DI\Attribute\Inject;

/**
 * Controls the registration and execution of providers
 *
 * @category Controllers
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
			Providers\PluginInfo::class => ServiceLocator::autowire(),
			Providers\Updates::class    => ServiceLocator::autowire(),
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
		add_filter( 'plugins_api', [ $provider, 'pluginInfo' ], 20, 3 );
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
		add_filter( 'site_transient_update_plugins', [ $provider, 'update' ] );
	}
}

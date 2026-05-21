<?php
/**
 * Hook registrar for the updater.
 *
 * @package github_plugin_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 */

namespace Bmd\GithubWpUpdater;

use Bmd\GithubWpUpdater\Processors;
use Bmd\GithubWpUpdater\Providers;
use DI\Attribute\Inject;

/**
 * Registers all WordPress hooks for the updater package.
 */
class Controller extends Module
{
	/**
	 * Register WordPress filters.
	 *
	 * @param Providers\PluginInfo      $plugin_info     Plugin info provider.
	 * @param Providers\Updates         $updates         Updates provider.
	 * @param Processors\UpdateResponse $update_response Update response processor.
	 *
	 * @return void
	 */
	#[Inject]
	public function registerFilters(
		Providers\PluginInfo $plugin_info,
		Providers\Updates $updates,
		Processors\UpdateResponse $update_response,
	): void {
		add_filter( 'plugins_api', [ $plugin_info, 'pluginInfo' ], 20, 3 );
		add_filter( 'site_transient_update_plugins', [ $updates, 'update' ] );
		add_filter( "{$this->package}_update_response", [ $update_response, 'mergeUpdateResponse' ] );
	}
}

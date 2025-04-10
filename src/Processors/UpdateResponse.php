<?php
/**
 * Response class for the update processor.
 *
 * PHP Version 8.2
 *
 * @package github_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Processors;

use MarkedEffect\GHPluginUpdater\Core\Abstracts;

use DI\Attribute\Inject;

/**
 * Service class for controlling the update response.
 *
 * @subpackage Services
 */
class UpdateResponse extends Abstracts\Module
{
	/**
	 * Public constructor.
	 *
	 * @param string                $plugin_slug the folder name of the plugin.
	 * @param string                $plugin_file the main plugin file.
	 * @param string                $version the version of the plugin.
	 * @param array<string, string> $icons array of plugin icons.
	 * @param array<string, string> $banners array of plugin banners.
	 * @param string                $package the package name.
	 */
	#[Inject(
		[
			'plugin_slug' => 'plugin.slug',
			'plugin_file' => 'plugin.file',
			'version'     => 'plugin.version',
			'icons'       => 'plugin.icons',
			'banners'     => 'plugin.banners',
			'package'     => 'plugin.package',
		]
	)]
	public function __construct(
		protected string $plugin_slug,
		protected string $plugin_file,
		protected string $version,
		protected array $icons,
		protected array $banners,
		string $package = '',
	) {
		parent::__construct( $package );
	}
	/**
	 * Build the update response.
	 *
	 * @param array<string, mixed> $response_args The response arguments.
	 *
	 * @return object
	 */
	public function mergeUpdateResponse( $response_args = [] ): object
	{
		$default_response = [
			'id'            => "{$this->plugin_slug}/{$this->plugin_file}",
			'slug'          => $this->plugin_slug,
			'plugin'        => "{$this->plugin_slug}/{$this->plugin_file}",
			'new_version'   => $this->version,
			'url'           => '',
			'package'       => '',
			'icons'         => $this->icons,
			'banners'       => $this->banners,
			'banners_rtl'   => [],
			'tested'        => '',
			'requires_php'  => '',
			'compatibility' => new \stdClass(),
		];

		return (object) array_merge( $default_response, $response_args );
	}
}

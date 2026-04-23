<?php
/**
 * Main app file
 *
 * PHP Version 8.2
 *
 * @package github_plugin_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace Bmd;

use Bmd\WPFramework\ {
	Main,
	Helpers,
};
use Bmd\GithubWpUpdater\Controllers;

/**
 * Main App Class
 *
 * Extends the framework's Main class to add GitHub updater-specific config
 * and controllers.
 *
 * @subpackage Main
 */
class GithubWpUpdater extends Main
{
	/**
	 * Package identifier for this plugin.
	 *
	 * @var string
	 */
	public const PACKAGE = 'bmd_github_wp_updater';
	/**
	 * Controllers to register and mount.
	 *
	 * @var array<int, class-string>
	 */
	protected const CONTROLLERS = [
		Controllers\ProcessorController::class,
		Controllers\ServiceController::class,
		Controllers\ProviderController::class,
	];
	/**
	 * Public constructor
	 *
	 * @param string               $root_file : path to the root file of the plugin.
	 * @param array<string, mixed> $config : optional configuration array.
	 */
	public function __construct(
		protected string $root_file = '',
		array $config = [],
	) {
		if ( empty( $this->root_file ) || ! is_file( $this->root_file ) ) {
			$this->root_file = $this->getRootFileFromPath();
		}

		parent::__construct(
			array_merge( $this->getConfigFromHeaders(), $config )
		);
	}
	/**
	 * Build the config array from the plugin file headers.
	 *
	 * Keys config.dir, config.url, config.package override the framework's
	 * defaults in registerConfig(). Plugin-specific keys are added alongside.
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfigFromHeaders(): array
	{
		$plugin_headers = [
			'plugin_uri' => '',
			'version'    => '',
		];
		$config_dir     = '';
		$config_url     = '';
		$config_package = '';
		$plugin_file    = '';
		$plugin_slug    = '';
		$asset_base_url = '';

		if ( ! empty( $this->root_file ) && is_file( $this->root_file ) ) {
			$plugin_headers = get_file_data(
				$this->root_file,
				[
					'plugin_uri' => 'Plugin URI',
					'version'    => 'Version',
				]
			);
			$config_dir     = plugin_dir_path( $this->root_file );
			$config_url     = plugin_dir_url( $this->root_file );
			$config_package = Helpers::slugify( basename( dirname( $this->root_file ) ) );
			$plugin_file    = basename( $this->root_file );
			$plugin_slug    = basename( dirname( $this->root_file ) );
			$asset_base_url = trailingslashit( $config_url ) . 'assets/images/';
		}

		return [
			'config.dir'     => $config_dir,
			'config.url'     => $config_url,
			'config.package' => $config_package,
			'plugin.file'    => $plugin_file,
			'plugin.slug'    => $plugin_slug,
			'plugin.banners' => [
				'low'  => ! empty( $asset_base_url ) ? $asset_base_url . 'banner-772x250.jpg' : '',
				'high' => ! empty( $asset_base_url ) ? $asset_base_url . 'banner-1544x500.jpg' : '',
			],
			'plugin.icons'   => [
				'default' => ! empty( $asset_base_url ) ? $asset_base_url . 'icon-256x256.jpg' : '',
			],
			'plugin.version' => $plugin_headers['version'],
			'github.user'    => '',
			'github.repo'    => '',
			'github.branch'  => 'main',
		];
	}
	/**
	 * Attempt to infer the root file of the plugin from the WordPress plugins list.
	 *
	 * @return string
	 */
	protected function getRootFileFromPath(): string
	{
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return '';
		}

		$plugins      = get_plugins();
		$current_dir  = realpath( __DIR__ );
		$matched_plugin = '';
		$matched_length = 0;

		if ( false === $current_dir ) {
			return '';
		}

		foreach ( array_keys( $plugins ) as $plugin_file ) {
			$plugin_dir = realpath( trailingslashit( WP_PLUGIN_DIR ) . dirname( $plugin_file ) );

			if ( false === $plugin_dir ) {
				continue;
			}

			$normalized_plugin_dir = untrailingslashit( $plugin_dir );
			$is_match              = $current_dir === $normalized_plugin_dir
				|| str_starts_with( $current_dir, $normalized_plugin_dir . DIRECTORY_SEPARATOR );

			if ( $is_match && strlen( $normalized_plugin_dir ) > $matched_length ) {
				$matched_plugin = $plugin_file;
				$matched_length = strlen( $normalized_plugin_dir );
			}
		}

		if ( ! empty( $matched_plugin ) ) {
			return trailingslashit( WP_PLUGIN_DIR ) . $matched_plugin;
		}

		return '';
	}
	/**
	 * Locate a specific service by name.
	 *
	 * @param string $service_name : name of service to locate.
	 *
	 * @return mixed
	 */
	public static function locateService( string $service_name ): mixed
	{
		if ( ! isset( self::$service_locator ) ) {
			return null;
		}

		$services = [
			trim( $service_name ),
			'Bmd\\GithubWpUpdater\\' . trim( $service_name ),
		];

		foreach ( $services as $service ) {
			$resolved = self::$service_locator->getService( service: $service );

			if ( is_wp_error( $resolved ) ) {
				continue;
			}

			return $resolved;
		}

		return null;
	}
}

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

namespace Bmd\GithubWpUpdater;

use Bmd\GithubWpUpdater\Services\ServiceLocator,
	Bmd\GithubWpUpdater\Core\Helpers;

/**
 * Main App Class
 *
 * Defines the service container and mounts the plugin.
 *
 * @subpackage Traits
 */
class Main
{
	/**
	 * Service Locator, used to set/retrieve services from the DI container.
	 *
	 * @var ServiceLocator|null
	 */
	private ?ServiceLocator $service_locator;
	/**
	 * Public constructor
	 *
	 * @param string               $root_file : path to the root file of the plugin.
	 * @param array<string, mixed> $config : optional configuration array.
	 */
	public function __construct(
		protected string $root_file = '',
		protected array $config = [],
	) {
		/**
		 * Ensure we have a service locator.
		 */
		$this->service_locator = new ServiceLocator();
		/**
		 * If we already have a container, restore it.
		 */
		if ( $this->service_locator->hasContainer() ) {
			$this->service_locator->restoreContainer();
		}
		/**
		 * Else maybe set config and mount the plugin.
		 */
		else {
			/**
			 * Maybe set the root file based on file path.
			 */
			if ( empty( $this->root_file ) 
				|| ! is_file( $this->root_file )
			) {
				$this->setRootFile( $this->getRootFileFromPath() );
			}
			/**
			 * Merged passed in config with the config from the headers.
			 */
			$this->setConfig(
				array_merge(
					$this->getConfigFromHeaders(),
					$this->config
				)
			);
		}
	}
	/**
	 * Setter the root file
	 * 
	 * @param string $root_file : path to the root file of the plugin
	 *
	 * @return string
	 */
	public function setRootFile( string $root_file = '' ): void
	{
		$this->root_file = ! empty( $root_file ) ? $root_file : $this->getRootFileFromPath();
	}
	/**
	 * Set the configuration array
	 *
	 * @param array<string, mixed> $config : configuration array.
	 *
	 * @return bool
	 */
	public function setConfig(  array $config = [] ): void
	{
		$this->config = array_merge(
			$this->config,
			$config
		);
	}
	/**
	 * Register the configuration with the service locator.
	 *
	 * @return void
	 */
	protected function registerConfig(): void
	{
		$this->service_locator->addDefinitions( $this->config );
	}
	/**
	 * Get the configuration array from the headers of the root file.
	 *
	 * @return array<string, mixed>
	 */
	protected function getConfigFromHeaders(): array
	{
		$plugin_headers = [
			'plugin_uri' => '',
			'version'    => '',
		];
		$plugin_dir = '';
		$plugin_url = '';
		$plugin_package = '';
		$plugin_file = '';
		$plugin_slug = '';
		$asset_base_url = '';

		if ( ! empty( $this->root_file ) && is_file( $this->root_file ) ) {
			$plugin_headers = get_file_data(
				$this->root_file,
				[
					'plugin_uri' => 'Plugin URI',
					'version'    => 'Version',
				]
			);
			$plugin_dir = plugin_dir_path( $this->root_file );
			$plugin_url = plugin_dir_url( $this->root_file );
			$plugin_package = Helpers::slugify( basename( dirname( $this->root_file ) ) );
			$plugin_file = basename( $this->root_file );
			$plugin_slug = basename( dirname( $this->root_file ) );
			$asset_base_url = trailingslashit( $plugin_url ) . 'assets/images/';
		}

		$default = [
			'plugin.dir'     => $plugin_dir,
			'plugin.url'     => $plugin_url,
			'plugin.package' => $plugin_package,
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

		return $default;
	}
	/**
	 * Attempt to infer the root file of the plugin.
	 *
	 * @return string
	 */
	protected function getRootFileFromPath(): string
	{	
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return '';
		}

		$plugins = get_plugins();
		$current_dir = realpath( __DIR__ );
		$matched_plugin = '';
		$matched_length = 0;

		if ( false === $current_dir ) {
			return '';
		}

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			$plugin_dir = realpath( trailingslashit( WP_PLUGIN_DIR ) . dirname( $plugin_file ) );

			if ( false === $plugin_dir ) {
				continue;
			}

			$normalized_plugin_dir = untrailingslashit( $plugin_dir );
			$is_match = $current_dir === $normalized_plugin_dir
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
	 * Register the controllers with the service locator.
	 *
	 * @return void
	 */
	protected function registerControllers(): void
	{
		$this->service_locator->addDefinitions(
			[
				Controllers\ProcessorController::class => ServiceLocator::autowire(),
				Controllers\ServiceController::class   => ServiceLocator::autowire(),
				Controllers\ProviderController::class  => ServiceLocator::autowire(),
			]
		);
	}
	/**
	 * Fire Mounted action on mount
	 *
	 * @return void
	 */
	public function mount(): void
	{
		/**
		 * Register the configuration and controllers.
		 */
		$this->registerConfig();
		$this->registerControllers();
		/**
		 * Build the service locator.
		 */
		$this->service_locator->build();
		$this->service_locator->save();
		/**
		 * Instantiate the controllers.
		 */
		$this->service_locator->getService( Controllers\ProcessorController::class );
		$this->service_locator->getService( Controllers\ServiceController::class );
		$this->service_locator->getService( Controllers\ProviderController::class );
	}
	/**
	 * Locate a specific service
	 *
	 * Use primarily by 3rd party interactions to remove actions/filters
	 *
	 * @param string $service : name of service to locate.
	 *
	 * @return mixed
	 */
	public static function locateService( string $service ): mixed
	{
		try {
			$instance = new self();

			$resolved = $instance->service_locator->getService( $service );

			if ( is_wp_error( $resolved ) ) {
				$resolved = $instance->service_locator->getService( __NAMESPACE__ . '\\' . $service );
			}

			return $resolved;
		} catch ( \Exception $e ) {
			return new \WP_Error( $e->getMessage() );
		}
	}
}

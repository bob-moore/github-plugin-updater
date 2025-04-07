<?php
/**
 * Main app file
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GHPluginUpdater;

use MarkedEffect\GHPluginUpdater\Services\ServiceLocator,
	MarkedEffect\GHPluginUpdater\Core\Helpers;

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
	 * @param string $root_file : path to the root file of the plugin.
	 * @param array<string, mixed> $config : optional configuration array.
	 */
	public function __construct(
		protected string $root_file = '',
		array $config = []
	)
	{
		$this->service_locator = new ServiceLocator();

		if ( ! $this->service_locator->hasContainer()
			&& is_file( $this->root_file )
		) {
			$this->service_locator->init();
			$this->registerConfig( $config );
			$this->service_locator->build();
			$this->service_locator->save();
			$this->mount();
		} else {
			$this->service_locator->restoreContainer();
		}
	}
	/**
	 * Register the configuration array
	 *
	 * @param array<string, mixed> $config : configuration array.
	 *
	 * @return void
	 */
	private function registerConfig( array $config ): void
	{
		if ( ! is_file( $this->root_file ) ) {
			return;
		}

		$this->service_locator->addDefinitions(
			wp_parse_args(
				$config,
				[
					'config.dir'     => plugin_dir_path( $this->root_file ),
					'config.url'     => plugin_dir_url( $this->root_file ),
					'config.package' => Helpers::slugify( basename( dirname( $this->root_file ) ) ),
					'config.file'    => basename( $this->root_file ),
					'config.slug'    => basename( dirname( $this->root_file ) ),
					'config.banners' => [],
					'config.icons'   => [],
					'github.user'    => '',
					'github.repo'    => '',
					'github.branch'  => 'main',
				],
				[
					Controllers\ProcessorController::class => ServiceLocator::autowire(),
					Controllers\ServiceController::class   => ServiceLocator::autowire(),
					Controllers\ProviderController::class  => ServiceLocator::autowire(),
				]
			)
		);
	}
	/**
	 * Fire Mounted action on mount
	 *
	 * @return void
	 */
	public function mount(): void
	{
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
	// public static function locateService( string $service ): mixed
	// {
	// 	try {
	// 		$instance = new self();

	// 		$resolved = $instance->service_locator->getService( $service );

	// 		if ( is_wp_error( $resolved ) ) {
	// 			$resolved = $instance->service_locator->getService( __NAMESPACE__ . '\\' . $service );
	// 		}

	// 		return $resolved;
	// 	} catch ( \Exception $e ) {
	// 		return new \WP_Error( $e->getMessage() );
	// 	}
	// }
}

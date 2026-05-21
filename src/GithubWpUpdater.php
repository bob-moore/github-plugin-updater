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

use Bmd\GithubWpUpdater\Controller;
use Bmd\GithubWpUpdater\Utilities;

/**
 * Main App Class
 *
 * Bootstraps the GitHub plugin updater by resolving the target plugin's root
 * file, building a normalized config from that file's headers and the updater's
 * own bundled assets, and wiring a single config filter that promotes any known
 * plugin-side assets over the bundled defaults.
 *
 * Config is assembled in two stages:
 *   1. normalizeConfig() - runs in the constructor, derives all values from the
 *      root file. Explicit constructor config is merged on top so callers can
 *      override individual keys without re-supplying everything.
 *   2. setKnownAssets() - runs as a config filter during mount(), after
 *      normalizeConfig(). Checks for actual image files in the target plugin and
 *      overwrites only the asset keys whose files exist, leaving the bundled
 *      defaults in place for anything that is absent.
 *
 * @subpackage Main
 */
class GithubWpUpdater
{
	/**
	 * Package identifier for this plugin.
	 *
	 * Used as the base of the config filter tag: `{PACKAGE}_config`.
	 *
	 * @var string
	 */
	public const PACKAGE = 'bmd_github_wp_updater';

	/**
	 * Service container for this updater instance.
	 *
	 * @var \DI\Container|null
	 */
	protected ?\DI\Container $services = null;

	/**
	 * Public constructor.
	 *
	 * Resolves the target plugin root file, then normalizes configuration.
	 * Throws if no valid root file can be found so callers get an explicit
	 * failure rather than a silent misconfiguration.
	 *
	 * @param string               $root_file Absolute path to the target plugin's main file.
	 *                                        When omitted, the updater will attempt to infer
	 *                                        it from the active WordPress plugins list.
	 * @param array<string, mixed> $config    Optional config overrides. Any key supplied here
	 *                                        takes precedence over auto-discovered values.
	 *
	 * @throws \InvalidArgumentException When no valid root file can be found.
	 */
	public function __construct(
		protected string $root_file = '',
		protected array $config = [],
	) {
		if ( empty( $this->root_file ) || ! is_file( $this->root_file ) ) {
			$this->root_file = $this->getRootFileFromPath();
		}

		if ( empty( $this->root_file ) || ! is_file( $this->root_file ) ) {
			throw new \InvalidArgumentException(
				'Root file not found. Please provide a valid path to the root plugin file.'
			);
		}

		$this->config = $this->normalizeConfig( $config );
	}
	/**
	 * Build the base config from the target plugin's root file.
	 *
	 * Derives directory paths, URLs, package/slug/file identifiers, and version
	 * from the root file. Bundled updater assets are used as default icon and
	 * banner values so the update UI always has something to display, even when
	 * the target plugin ships no images of its own.
	 *
	 * Explicit $config values are merged on top of these defaults via
	 * array_replace_recursive so nested keys (e.g. individual banner sizes) can
	 * be overridden without replacing the whole array.
	 *
	 * @param array<string, mixed> $config Optional caller-supplied config overrides.
	 *
	 * @return array<string, mixed>
	 */
	protected function normalizeConfig( array $config ): array
	{
		$headers = get_file_data(
			$this->root_file,
			[ 'version' => 'Version' ]
		);

		// Bundled updater assets act as the lowest-priority fallback so the WP
		// update UI always has valid image URLs. setKnownAssets() will replace
		// these with target-plugin assets during mount() if they exist.
		$updater_asset_url = trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/';

		$default = [
			// WordPress service-container path identifiers.
			'config.dir'      => plugin_dir_path( $this->root_file ),
			'config.url'      => plugin_dir_url( $this->root_file ),
			'config.package'  => Utilities::slugify( basename( dirname( $this->root_file ) ) ),
			'config.cacheDir' => dirname( __DIR__ ) . '/cache',
			// Plugin identity keys consumed by the update response processor.
			'plugin.file'    => basename( $this->root_file ),
			'plugin.slug'    => basename( dirname( $this->root_file ) ),
			'plugin.version' => $headers['version'],
			// Bundled fallback images - replaced by real plugin assets in setKnownAssets().
			'plugin.icons'   => [
				'default' => $updater_asset_url . 'icon-256x256.jpg',
			],
			'plugin.banners' => [
				'low'  => $updater_asset_url . 'banner-772x250.jpg',
				'high' => $updater_asset_url . 'banner-1544x500.jpg',
			],
			// GitHub repository coordinates - must be supplied by the caller.
			'github.user'    => '',
			'github.repo'    => '',
			'github.branch'  => 'main',
			'github.token'   => '',
			'github.asset'   => '',
		];

		// array_replace_recursive lets callers override individual nested keys
		// (e.g. only 'plugin.banners.low') without wiping the rest of the array.
		return array_replace_recursive( $default, $config );
	}
	/**
	 * Config filter: promote target-plugin assets over bundled defaults.
	 *
	 * Runs at priority 5 on the `{package}_config` filter, before
	 * registerConfig() commits the config to the service container.
	 *
	 * For each asset key, only writes a value when:
	 *   - The corresponding image file actually exists on disk.
	 *   - The key is not already populated (i.e. the caller has not explicitly
	 *     set it in the constructor config, which normalizeConfig() would have
	 *     resolved to a non-empty string).
	 *
	 * This means the precedence order is:
	 *   explicit constructor config > target plugin assets > bundled defaults.
	 *
	 * @param array<string, mixed> $config The current config array.
	 *
	 * @return array<string, mixed>
	 */
	public function setKnownAssets( array $config ): array
	{
		$plugin_dir = trailingslashit( $config['config.dir'] );
		$plugin_url = trailingslashit( $config['config.url'] );

		if (
			$this->shouldPromoteKnownAsset( $config, 'plugin.icons', 'default', 'icon-256x256.jpg' )
			&& is_file( $plugin_dir . 'assets/icon-256x256.jpg' )
		) {
			$config['plugin.icons']['default'] = $plugin_url . 'assets/icon-256x256.jpg';
		}

		if (
			$this->shouldPromoteKnownAsset( $config, 'plugin.banners', 'low', 'banner-772x250.jpg' )
			&& is_file( $plugin_dir . 'assets/banner-772x250.jpg' )
		) {
			$config['plugin.banners']['low'] = $plugin_url . 'assets/banner-772x250.jpg';
		}

		if (
			$this->shouldPromoteKnownAsset( $config, 'plugin.banners', 'high', 'banner-1544x500.jpg' )
			&& is_file( $plugin_dir . 'assets/banner-1544x500.jpg' )
		) {
			$config['plugin.banners']['high'] = $plugin_url . 'assets/banner-1544x500.jpg';
		}

		return $config;
	}

	/**
	 * Determine whether a known plugin asset should replace the current value.
	 *
	 * @param array<string, mixed> $config Current config array.
	 * @param string               $group  Asset config group.
	 * @param string               $key    Asset config key.
	 * @param string               $file   Bundled asset file name.
	 *
	 * @return bool
	 */
	protected function shouldPromoteKnownAsset( array $config, string $group, string $key, string $file ): bool
	{
		$current = $config[ $group ][ $key ] ?? '';
		$bundled = trailingslashit( plugin_dir_url( __FILE__ ) ) . 'assets/' . $file;

		return empty( $current ) || $bundled === $current;
	}
	/**
	 * Definition file loaded into the container.
	 *
	 * @return string
	 */
	protected function getDefinitionsFile(): string
	{
		return __DIR__ . '/definitions.php';
	}

	/**
	 * Directory where compiled container files are stored.
	 *
	 * @return string|false
	 */
	protected function getContainerCacheDirectory(): string|false
	{
		return ! empty( $this->config['config.cacheDir'] ) && is_string( $this->config['config.cacheDir'] )
			? $this->config['config.cacheDir']
			: false;
	}

	/**
	 * Normalize config values before hashing them into a cache key.
	 *
	 * @param mixed $value Config value.
	 *
	 * @return mixed
	 */
	protected function normalizeCacheKeyValue( mixed $value ): mixed
	{
		if ( is_array( $value ) ) {
			ksort( $value );

			return array_map( [ $this, 'normalizeCacheKeyValue' ], $value );
		}

		if ( is_object( $value ) ) {
			return get_class( $value );
		}

		if ( is_resource( $value ) ) {
			return get_resource_type( $value );
		}

		return $value;
	}

	/**
	 * Build the cache key used in the compiled container class name.
	 *
	 * @return string
	 */
	protected function getContainerCacheKey(): string
	{
		$definitions_file = $this->getDefinitionsFile();

		return substr(
			hash(
				'sha256',
				serialize(
					[
						'config'      => $this->normalizeCacheKeyValue( $this->config ),
						'definitions' => is_file( $definitions_file )
							? filemtime( $definitions_file ) . ':' . filesize( $definitions_file )
							: null,
					]
				)
			),
			0,
			16
		);
	}

	/**
	 * Get the compiled container class name for the current config.
	 *
	 * @return string
	 */
	protected function getCompiledContainerClass(): string
	{
		return 'container_' . $this->getContainerCacheKey();
	}

	/**
	 * Determine whether the compiled container can be used or generated.
	 *
	 * @param string $cache_dir Cache directory.
	 * @param string $class     Compiled container class name.
	 *
	 * @return bool
	 */
	protected function canUseCompiledContainer( string $cache_dir, string $class ): bool
	{
		if ( is_readable( "{$cache_dir}/{$class}.php" ) ) {
			return true;
		}

		return is_dir( $cache_dir )
			? is_writable( $cache_dir )
			: wp_mkdir_p( $cache_dir ) && is_writable( $cache_dir );
	}

	/**
	 * Build the DI container.
	 *
	 * @return void
	 */
	protected function initContainer(): void
	{
		$builder = new \DI\ContainerBuilder();
		$builder->useAttributes( true );

		$cache_dir = $this->getContainerCacheDirectory();

		if ( false !== $cache_dir && 'production' === wp_get_environment_type() ) {
			$container_class = $this->getCompiledContainerClass();

			if ( $this->canUseCompiledContainer( $cache_dir, $container_class ) ) {
				$builder->enableCompilation( $cache_dir, $container_class );
			}
		}

		$builder->addDefinitions( $this->config );
		$builder->addDefinitions( $this->getDefinitionsFile() );

		$this->services = $builder->build();
	}

	/**
	 * Register filters and mount the updater.
	 *
	 * The asset filter is registered at priority 5 so it runs before any
	 * external filters (default priority 10) that a consuming plugin might add
	 * to further customise icons or banners.
	 *
	 * @return void
	 */
	public function mount(): void
	{
		add_filter( "{$this->config['config.package']}_config", [ $this, 'setKnownAssets' ], 5 );

		$this->config = apply_filters(
			"{$this->config['config.package']}_config",
			$this->config
		);

		if ( ! $this->validateConfig() ) {
			return;
		}

		if ( ! $this->services instanceof \DI\Container ) {
			$this->initContainer();
		}

		$this->services->get( Controller::class );
	}

	/**
	 * Validate required runtime config before building the container.
	 *
	 * @return bool
	 */
	protected function validateConfig(): bool
	{
		$required = [
			'github.user',
			'github.repo',
			'plugin.file',
			'plugin.slug',
			'plugin.version',
		];

		$missing = array_filter(
			$required,
			fn( string $key ): bool => empty( $this->config[ $key ] )
		);

		if ( empty( $missing ) ) {
			return true;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				sprintf(
					'GitHub WP Updater not mounted. Missing required config: %s',
					implode( ', ', $missing )
				)
			);
		}

		return false;
	}

	/**
	 * Set or replace a service in the built container.
	 *
	 * @param string $key   Service entry key.
	 * @param mixed  $value Service instance or value.
	 *
	 * @return void
	 * @throws \LogicException When the container has not been built.
	 */
	public function setInstance( string $key, mixed $value ): void
	{
		if ( ! $this->services instanceof \DI\Container ) {
			throw new \LogicException( 'Cannot set service before container is built.' );
		}

		$this->services->set( $key, $value );
	}

	/**
	 * Get a service instance from the container.
	 *
	 * @param string $service Fully-qualified class name or container entry key.
	 *
	 * @return object|null The service, or null if the container is not yet built.
	 */
	public function getInstance( string $service ): ?object
	{
		return $this->services instanceof \DI\Container && $this->services->has( $service )
			? $this->services->get( $service )
			: null;
	}
	/**
	 * Attempt to infer the root file of the plugin that owns this updater install.
	 *
	 * Walks the active WordPress plugin list and finds the plugin whose directory
	 * is the deepest ancestor of this file's own directory (__DIR__). "Deepest
	 * ancestor" is used rather than exact match because this class lives inside
	 * vendor/bmd/github-wp-updater/src/, not at the plugin root.
	 *
	 * Optimisations applied:
	 *   1. Bail immediately if __DIR__ is not under WP_PLUGIN_DIR - avoids
	 *      calling get_plugins() at all when the package is installed elsewhere
	 *      (e.g. a theme's vendor directory).
	 *   2. Skip candidates shorter than the current best match before calling
	 *      realpath(), since a shorter raw path can never produce a longer real
	 *      path.
	 *   3. Break early on an exact directory match - no deeper ancestor exists.
	 *
	 * @return string Absolute path to the inferred root file, or '' on failure.
	 */
	protected function getRootFileFromPath(): string
	{
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
			return '';
		}

		$current_dir = realpath( __DIR__ );

		if ( false === $current_dir ) {
			return '';
		}

		// if this file is not physically inside the plugins
		// directory there is nothing to search - return early before the
		// expensive get_plugins() filesystem scan.
		$plugins_dir = realpath( WP_PLUGIN_DIR );

		if (
			false === $plugins_dir
			|| ! str_starts_with( $current_dir, $plugins_dir . DIRECTORY_SEPARATOR )
		) {
			return '';
		}

		$plugins        = get_plugins();
		$matched_plugin = '';
		$matched_length = 0;

		foreach ( array_keys( $plugins ) as $plugin_file ) {
			$raw_dir = WP_PLUGIN_DIR . '/' . dirname( $plugin_file );

			// the raw path length is an upper bound on the
			// real path length. Skip realpath() when the raw length cannot
			// beat the current best, avoiding a syscall per candidate.
			if ( strlen( $raw_dir ) <= $matched_length ) {
				continue;
			}

			$plugin_dir = realpath( $raw_dir );

			if ( false === $plugin_dir ) {
				continue;
			}

			$normalized = untrailingslashit( $plugin_dir );

			$is_match = $current_dir === $normalized
				|| str_starts_with( $current_dir, $normalized . DIRECTORY_SEPARATOR );

			if ( ! $is_match ) {
				continue;
			}

			$matched_plugin = $plugin_file;
			$matched_length = strlen( $normalized );

			// an exact match is the deepest possible ancestor.
			// No other plugin can produce a longer match, so stop iterating.
			if ( $current_dir === $normalized ) {
				break;
			}
		}

		if ( ! empty( $matched_plugin ) ) {
			return trailingslashit( WP_PLUGIN_DIR ) . $matched_plugin;
		}

		return '';
	}
}

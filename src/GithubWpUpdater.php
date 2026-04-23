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
class GithubWpUpdater extends Main
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

		parent::__construct( $this->config );
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
			'config.dir'     => plugin_dir_path( $this->root_file ),
			'config.url'     => plugin_dir_url( $this->root_file ),
			'config.package' => Helpers::slugify( basename( dirname( $this->root_file ) ) ),
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
			empty( $config['plugin.icons']['default'] )
			&& is_file( $plugin_dir . 'assets/icon-256x256.jpg' )
		) {
			$config['plugin.icons']['default'] = $plugin_url . 'assets/icon-256x256.jpg';
		}

		if (
			empty( $config['plugin.banners']['low'] )
			&& is_file( $plugin_dir . 'assets/banner-772x250.jpg' )
		) {
			$config['plugin.banners']['low'] = $plugin_url . 'assets/banner-772x250.jpg';
		}

		if (
			empty( $config['plugin.banners']['high'] )
			&& is_file( $plugin_dir . 'assets/banner-1544x500.jpg' )
		) {
			$config['plugin.banners']['high'] = $plugin_url . 'assets/banner-1544x500.jpg';
		}

		return $config;
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

		parent::mount();
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

		if ( false === $plugins_dir
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

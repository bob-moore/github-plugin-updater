<?php
/**
 * Updates Provider
 *
 * PHP Version 8.2
 *
 * @package github_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace Bmd\GithubWpUpdater\Providers;

use Bmd\GithubWpUpdater\Module;
use Bmd\GithubWpUpdater\Services\RemoteRequest;

use DI\Attribute\Inject;

/**
 * Provides update data to the WordPress site_transient_update_plugins filter
 *
 * @subpackage Providers
 */
class Updates extends Module
{
	/**
	 * Optional release asset name to prefer.
	 *
	 * @var string
	 */
	protected string $release_asset = '';

	/**
	 * Public constructor.
	 *
	 * @param RemoteRequest $remote_request The remote request service.
	 * @param string        $version        The plugin version.
	 * @param string        $plugin_slug    The plugin slug.
	 * @param string        $plugin_file    The plugin file.
	 * @param string        $package        The package name.
	 * @param string        $release_asset  Optional release asset name.
	 */
	#[Inject(
		[
			'version'       => 'plugin.version',
			'plugin_slug'   => 'plugin.slug',
			'plugin_file'   => 'plugin.file',
			'release_asset' => 'github.asset',
		]
	)]
	public function __construct(
		protected RemoteRequest $remote_request,
		protected string $version,
		protected string $plugin_slug,
		protected string $plugin_file,
		string $package = '',
		string $release_asset = '',
	) {
		$this->release_asset = $release_asset;

		parent::__construct( $package );
	}
	/**
	 * Check for updates, and return an update response if available.
	 *
	 * @return object|null
	 */
	protected function checkUpdates(): ?object
	{
		$release = $this->remote_request->requestLatestRelease();

		if ( ! $release ) {
			return null;
		}

		$release_ref = isset( $release->tag_name ) && is_string( $release->tag_name )
			? $release->tag_name
			: null;

		$remote = wp_parse_args(
			$this->remote_request->getPluginInfo( [], $release_ref ),
			[
				'version'      => '',
				'requires'     => '',
				'tested'       => '',
				'requires_php' => '',
			]
		);

		if ( empty( $remote['version'] ) && null !== $release_ref ) {
			$remote['version'] = $this->normalizeVersion( $release_ref );
		}

		if (
			empty( $remote['version'] )
			|| ! version_compare( $this->version, $remote['version'], '<' )
			|| ( ! empty( $remote['requires'] ) && ! version_compare( $remote['requires'], get_bloginfo( 'version' ), '<=' ) )
			|| ( ! empty( $remote['requires_php'] ) && ! version_compare( $remote['requires_php'], PHP_VERSION, '<=' ) )
		) {
			return null;
		}

		$package = $this->getReleaseZip( $release );

		if ( ! $package ) {
			return null;
		}

		return (object) apply_filters(
			"{$this->package}_update_response",
			[
				'new_version'  => $remote['version'],
				'package'      => $package,
				'tested'       => $remote['tested'],
				'requires_php' => $remote['requires_php'],
				'added'        => $release->created_at ?? '',
				'last_updated' => $release->published_at ?? '',
			]
		);
	}
	/**
	 * Filters the update transient.
	 *
	 * @param mixed $transient The transient object.
	 *
	 * @return mixed
	 */
	public function update( mixed $transient ): mixed
	{
		if (
			! is_object( $transient )
			|| empty( $transient->checked )
			|| ! isset( $transient->response )
		) {
			return $transient;
		}

		if ( ! is_array( $transient->response ) ) {
			$transient->response = (array) $transient->response;
		}

		$updates = $this->checkUpdates();

		if ( ! $updates ) {
			$this->addNoUpdateResponse( $transient );

			return $transient;
		}

		$transient->response[ "{$this->plugin_slug}/{$this->plugin_file}" ] = $updates;

		return $transient;
	}
	/**
	 * Get the package URL from the release object.
	 *
	 * @param object $release The release object.
	 *
	 * @return string|null The package URL or null if not found.
	 */
	protected function getReleaseZip( object $release ): ?string
	{
		if ( empty( $release->assets ) || ! is_iterable( $release->assets ) ) {
			return null;
		}

		foreach ( $release->assets as $asset ) {
			if (
				'' !== $this->release_asset
				&& isset( $asset->name, $asset->browser_download_url )
				&& $this->release_asset === $asset->name
			) {
				return $asset->browser_download_url;
			}

			if (
				isset( $asset->name, $asset->browser_download_url )
				&& str_ends_with( strtolower( $asset->name ), '.zip' )
			) {
				return $asset->browser_download_url;
			}
		}

		return null;
	}

	/**
	 * Add a no-update response so WordPress still receives plugin metadata.
	 *
	 * @param object $transient The update transient.
	 *
	 * @return void
	 */
	protected function addNoUpdateResponse( object $transient ): void
	{
		if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
			$transient->no_update = [];
		}

		$transient->no_update[ "{$this->plugin_slug}/{$this->plugin_file}" ] = (object) apply_filters(
			"{$this->package}_update_response",
			[
				'new_version' => $this->version,
				'package'     => '',
			]
		);
	}

	/**
	 * Normalize a release tag for semantic version comparison.
	 *
	 * @param string $version Release version or tag.
	 *
	 * @return string
	 */
	protected function normalizeVersion( string $version ): string
	{
		return preg_replace( '/^v(?=\d)/i', '', $version ) ?? $version;
	}
}

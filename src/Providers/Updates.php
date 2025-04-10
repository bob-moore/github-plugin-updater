<?php
/**
 * Blocks Service Definition
 *
 * PHP Version 8.2
 *
 * @package github_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Providers;

use MarkedEffect\GHPluginUpdater\Core\Abstracts,
	MarkedEffect\GHPluginUpdater\Services\RemoteRequest;

use DI\Attribute\Inject;
/**
 * Service class for blocks
 *
 * @subpackage Providers
 */
class Updates extends Abstracts\Module
{
	/**
	 * Public constructor.
	 *
	 * @param RemoteRequest $remote_request The remote request service.
	 * @param string        $version        The plugin version.
	 * @param string        $plugin_slug    The plugin slug.
	 * @param string        $plugin_file    The plugin file.
	 * @param string        $package        The package name.
	 */
	#[Inject(
		[
			'version'     => 'plugin.version',
			'plugin_slug' => 'plugin.slug',
			'plugin_file' => 'plugin.file',
			'package'     => 'plugin.package',
		]
	)]
	public function __construct(
		protected RemoteRequest $remote_request,
		protected string $version,
		protected string $plugin_slug,
		protected string $plugin_file,
		string $package = '',
	) {
		parent::__construct( $package );
	}
	/**
	 * Check for updates, and return an update response if available.
	 *
	 * @return object|null
	 */
	protected function checkUpdates(): ?object
	{
		$remote = $this->remote_request->getPluginInfo();
		/**
		 * Check if the plugin version has bumped on the github repo,
		 * and that the new version requirements are met.
		 */
		if (
			! version_compare( $this->version, $remote['version'], '<' )
			|| ! version_compare( $remote['requires'], get_bloginfo( 'version' ), '<=' )
			|| ! version_compare( $remote['requires_php'], PHP_VERSION, '<' )
		) {
			return null;
		}

		$release = $this->remote_request->requestRelease( $remote['version'] );

		if ( ! $release ) {
			return null;
		}

		$package = $this->getReleaseZip( $release );

		if ( ! $package ) {
						return null;
		}

		$update = apply_filters(
			"{$this->package}_update_response",
			[
				'new_version'   => $remote['version'],
				'package'       => $package,
				'tested'        => $remote['tested'],
				'requires_php'  => $remote['requires_php'],
				'added'         => $release->created_at,
				'last_updated'  => $release->published_at,
			]
		);

		return $update;
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

		$updates = $this->checkUpdates();

		if ( ! $updates ) {
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
		$assets = $release->assets;

		foreach ( $assets as $asset ) {
			if ( str_contains( $asset->name, 'zip' ) ) {
				return $asset->browser_download_url;
			}
		}

		return null;
	}
}

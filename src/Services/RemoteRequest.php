<?php
/**
 * Router Service Definition
 *
 * PHP Version 8.2
 *
 * @package github_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace Bmd\GithubWpUpdater\Services;

use Bmd\GithubWpUpdater\Module;
use Bmd\GithubWpUpdater\Processors\PluginHeaders;

use DI\Attribute\Inject;

/**
 * Service class for fetching plugin data from GitHub
 *
 * @subpackage Services
 */
class RemoteRequest extends Module
{
	/**
	 * Short cache TTL for failed GitHub requests.
	 */
	protected const FAILURE_CACHE_TTL = 900;

	/**
	 * Public constructor.
	 *
	 * @param PluginHeaders $plugin_header_processor The plugin header processor.
	 * @param string        $github_user             The github user.
	 * @param string        $github_repo             The github repo.
	 * @param string        $branch                  The branch to use.
	 * @param string        $plugin_file             The plugin file.
	 * @param string        $package                 The package name.
	 * @param string        $github_token            Optional GitHub token.
	 */
	#[Inject(
		[
			'github_user' => 'github.user',
			'github_repo' => 'github.repo',
			'branch'      => 'github.branch',
			'plugin_file' => 'plugin.file',
			'github_token' => 'github.token',
		]
	)]
	public function __construct(
		protected PluginHeaders $plugin_header_processor,
		protected string $github_user = '',
		protected string $github_repo = '',
		protected string $branch = 'main',
		protected string $plugin_file = '',
		string $package = '',
		protected string $github_token = ''
	) {
		parent::__construct( $package );
	}
	/**
	 * Setter for the plugin branch.
	 *
	 * @param string $branch The plugin branch.
	 * @return void
	 */
	public function setBranch( string $branch ): void
	{
		$this->branch = $branch;
	}
	/**
	 * Setter for the plugin user.
	 *
	 * @param string $user The plugin user.
	 * @return void
	 */
	public function setUser( string $user ): void
	{
		$this->github_user = $user;
	}
	/**
	 * Setter for the plugin repo.
	 *
	 * @param string $repo The plugin repo.
	 * @return void
	 */
	public function setRepo( string $repo ): void
	{
		$this->github_repo = $repo;
	}
	/**
	 * Request the remote info from the github repository.
	 *
	 * Parses the plugin headers from the remote file, to compare against
	 * the local file.
	 *
	 * @param array<string, string> $default The default plugin headers.
	 * @param string|null           $ref     Optional Git ref.
	 *
	 * @return array<string, string>
	 */
	public function getPluginInfo( $default = [], ?string $ref = null ): array
	{
		$ref       = $ref ? $ref : $this->branch;
		$cache_key = $this->getCacheKey( 'remote_info', $ref );
		$cached    = wp_cache_get( $cache_key, $this->package );

		if ( false !== $cached ) {
			return $cached;
		}

		$request_url = sprintf(
			'https://raw.githubusercontent.com/%s/%s/%s/%s',
			$this->github_user,
			$this->github_repo,
			$ref,
			$this->plugin_file
		);

		$response = wp_remote_get( $request_url, $this->getRequestArgs() );

		if (
			is_wp_error( $response )
			|| 200 !== wp_remote_retrieve_response_code( $response )
		) {
			$defaults = apply_filters( "{$this->package}_default_plugin_headers", $default );

			wp_cache_set( $cache_key, $defaults, $this->package, self::FAILURE_CACHE_TTL );

			return $defaults;
		}

		$body = wp_remote_retrieve_body( $response );

		$plugin_headers = $this->plugin_header_processor->getFileData( $body );

		wp_cache_set( $cache_key, $plugin_headers, $this->package, HOUR_IN_SECONDS );

		return $plugin_headers;
	}
	/**
	 * Request release data from the github repository.
	 *
	 * @param string $version version of the release to request.
	 *
	 * @return object|null
	 */
	public function requestRelease( string $version ): ?object
	{
		$cache_key = $this->getCacheKey( "release_{$version}" );
		$cached    = wp_cache_get( $cache_key, $this->package );

		if ( false !== $cached ) {
			return $cached;
		}

		$request_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/tags/%s',
			$this->github_user,
			$this->github_repo,
			$version
		);

		$response = wp_remote_get( $request_url, $this->getRequestArgs() );

		if (
			is_wp_error( $response )
			|| 200 !== wp_remote_retrieve_response_code( $response )
		) {
			wp_cache_set( $cache_key, null, $this->package, self::FAILURE_CACHE_TTL );

			return null;
		}

		$body = wp_remote_retrieve_body( $response );

		$release_info = json_decode( $body );

		if ( ! is_object( $release_info ) ) {
			wp_cache_set( $cache_key, null, $this->package, self::FAILURE_CACHE_TTL );

			return null;
		}

		wp_cache_set( $cache_key, $release_info, $this->package, HOUR_IN_SECONDS );

		return $release_info;
	}

	/**
	 * Request the latest release data from the GitHub repository.
	 *
	 * @return object|null
	 */
	public function requestLatestRelease(): ?object
	{
		$cache_key = $this->getCacheKey( 'release_latest' );
		$cached    = wp_cache_get( $cache_key, $this->package );

		if ( false !== $cached ) {
			return $cached;
		}

		$request_url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			$this->github_user,
			$this->github_repo
		);

		$response = wp_remote_get( $request_url, $this->getRequestArgs() );

		if (
			is_wp_error( $response )
			|| 200 !== wp_remote_retrieve_response_code( $response )
		) {
			wp_cache_set( $cache_key, null, $this->package, self::FAILURE_CACHE_TTL );

			return null;
		}

		$release_info = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! is_object( $release_info ) ) {
			wp_cache_set( $cache_key, null, $this->package, self::FAILURE_CACHE_TTL );

			return null;
		}

		wp_cache_set( $cache_key, $release_info, $this->package, HOUR_IN_SECONDS );

		return $release_info;
	}
	/**
	 * Request the raw content of a file from the github repository.
	 *
	 * @param string      $file The file to request.
	 * @param string|null $ref  Optional Git ref.
	 *
	 * @return string|null
	 */
	public function requestRawContent( string $file, ?string $ref = null ): ?string
	{
		$ref = $ref ? $ref : $this->branch;

		$request_url = sprintf(
			'https://raw.githubusercontent.com/%s/%s/%s/%s',
			$this->github_user,
			$this->github_repo,
			$ref,
			$file
		);

		$response = wp_remote_get( $request_url, $this->getRequestArgs() );

		if (
			is_wp_error( $response )
			|| 200 !== wp_remote_retrieve_response_code( $response )
		) {
			return '';
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Request readme content using common filename fallbacks.
	 *
	 * @param string|null $ref Git ref to read from.
	 *
	 * @return string|null
	 */
	public function requestReadmeContent( ?string $ref = null ): ?string
	{
		foreach ( [ 'readme.md', 'README.md', 'readme.txt' ] as $file ) {
			$content = $this->requestRawContent( $file, $ref );

			if ( ! empty( $content ) ) {
				return $content;
			}
		}

		return null;
	}
	/**
	 * Build a unique cache key for repository-specific data.
	 *
	 * @param string      $suffix The cache key suffix.
	 * @param string|null $ref    Optional Git ref.
	 *
	 * @return string
	 */
	protected function getCacheKey( string $suffix, ?string $ref = null ): string
	{
		$ref = $ref ? $ref : $this->branch;

		return implode(
			':',
			array_filter(
				[
					$suffix,
					$this->github_user,
					$this->github_repo,
					$ref,
					$this->plugin_file,
				],
				static fn( string $value ): bool => '' !== $value
			)
		);
	}
	/**
	 * Get request arguments for GitHub requests.
	 *
	 * @return array<string, mixed>
	 */
	protected function getRequestArgs(): array
	{
		$args = [
			'timeout'    => 15,
			'user-agent' => '' !== $this->package ? $this->package : 'github-wp-updater',
			'headers'    => [
				'Accept' => 'application/vnd.github+json',
			],
		];

		if ( '' !== $this->github_token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $this->github_token;
		}

		return $args;
	}
}

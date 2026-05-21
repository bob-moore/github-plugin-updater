<?php
/**
 * PluginInfo Provider
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
use Bmd\GithubWpUpdater\Services\ReadmeParser;
use Bmd\GithubWpUpdater\Services\RemoteRequest;

use DI\Attribute\Inject;

/**
 * Provides plugin info to the WordPress plugins_api filter
 *
 * @subpackage Providers
 */
class PluginInfo extends Module
{
	/**
	 * Public constructor.
	 *
	 * @param RemoteRequest $remote_request The remote request service.
	 * @param ReadmeParser  $readme_parser  The readme parser service.
	 * @param string        $slug           The plugin slug.
	 * @param string        $file           The plugin file.
	 * @param string        $package        The package name.
	 */
	#[Inject(
		[
			'slug' => 'plugin.slug',
			'file' => 'plugin.file',
		]
	)]
	public function __construct(
		protected RemoteRequest $remote_request,
		protected ReadmeParser $readme_parser,
		protected string $slug,
		protected string $file,
		string $package = '',
	) {
		parent::__construct( $package );
	}
	/**
	 * Filters the plugins_api() response.
	 *
	 * @param false|object|array<string, mixed> $result The result object or array. Default false.
	 * @param string                            $action The type of information being requested from the Plugin Installation API.
	 * @param object                            $args   Plugin API arguments.
	 *
	 * @return false|object|array<string, mixed>
	 */
	public function pluginInfo( false|object|array $result, string $action, object $args ): false|object|array
	{
		if ( 'plugin_information' !== $action ) {
			return $result;
		}
		if ( empty( $args->slug ) || $this->slug !== $args->slug ) {
			return $result;
		}

		$response = $this->remote_request->getPluginInfo();

		if ( ! $response ) {
			return $result;
		}

		$response['new_version'] = $response['version'];
		$response                = (object) apply_filters( "{$this->package}_update_response", $response );

		$response->version = $response->new_version;
		$response->slug    = $this->slug;
		$response->plugin  = "{$this->slug}/{$this->file}";
		$response->name    = $response->name ?? $this->slug;
		$response->homepage = $response->plugin_uri ?? $response->url ?? '';

		$readme            = $this->remote_request->requestReadmeContent();
		$response->sections = ! empty( $readme )
			? $this->readme_parser->parseSections( $readme )
			: [];

		return $response;
	}
}

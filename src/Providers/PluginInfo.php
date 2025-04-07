<?php
/**
 * Blocks Service Definition
 *
 * PHP Version 8.2
 *
 * @package mwf_cornerstone
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
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
class PluginInfo extends Abstracts\Module
{
	#[Inject([
		'slug' => 'config.slug',
		'file' => 'config.file',
		'package'     => 'config.package',
	])]
	public function __construct(
		protected RemoteRequest $remote_request,
		protected string $slug,
		protected string $file,
		string $package = '',
	)
	{
		parent::__construct( $package );
	}
	/**
		* Filters the plugins_api() response.
		*
		* @param false|object|array $result The result object or array. Default false.
		* @param string             $action The type of information being requested from the Plugin Installation API.
		* @param object             $args Plugin API arguments.
		*
		* @return false|object|array
		*/
	public function pluginInfo( false|object|array $result, string $action, object $args ): false|object|array
	{
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( empty( $args->slug ) || $this->slug !== $args->slug ) {
			return $result;
		}

		$response = (object)$this->remote_request->getPluginInfo();

		if ( ! $response ) {
			return $result;
		}

		$response->version = $response->new_version;

		// $readme = $this->requestRawContent( 'readme.md' );

		// $Parsedown = new Parsedown();

		// $sections = ReadmeParser::parseSections( $readme );

		// do_action( 'qm/debug', $sections );
		$response->sections = [
			'description' => 'Placeholder'
		];
		// $response->sections = (array) $data->sections;

		return $response;
	}
}

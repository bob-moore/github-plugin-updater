<?php
/**
 * Router Service Definition
 *
 * PHP Version 8.2
 *
 * @package mwf_cornerstone
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace MarkedEffect\GithubUpdater\Services;

use MarkedEffect\GithubUpdater\Core\Abstracts;

use DI\Attribute\Inject;

/**
 * Service class for router actions
 *
 * @subpackage Services
 */
class UrlResolver extends Abstracts\Module
{
	/**
	 * URL to plugin instance
	 *
	 * @var string
	 */
	protected string $url = '';
	/**
	 * Public constructor
	 *
	 * @param string $root_url : root url of the plugin.
	 */
	#[Inject(['root_url' => 'config.url'])]
	public function __construct( string $root_url, string $package = '' )
	{
		$this->url = untrailingslashit( $root_url );
		parent::__construct( $package );
	}
	/**
	 * Get the url with string appended
	 *
	 * @param string $append : string to append to the URL.
	 *
	 * @return string complete url
	 */
	public function resolve( string $append = '' ): string
	{
		return esc_url_raw( $this->appendUrl( $this->url, $append ) );
	}
	/**
	 * Append string safely to end of a url
	 *
	 * @param string $base : the base url.
	 * @param string $append : the string to append.
	 *
	 * @return string
	 */
	protected function appendUrl( string $base, string $append = '' ): string
	{
		return ! empty( $append )
			? untrailingslashit( trailingslashit( $base ) . ltrim( $append, '/' ) )
			: untrailingslashit( $base );
	}
}

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

 namespace MarkedEffect\GHPluginUpdater\Middleware;

use MarkedEffect\GHPluginUpdater\Core\Abstracts;

/**
 * Service class for router actions
 *
 * @subpackage Services
 */
class Scope extends Abstracts\Module
{
	/**
	 * Default function that is called in the absence of an explicitly defined
	 * filter
	 *
	 * @param string       $function name of function a template tried to call.
	 * @param array<mixed> $args mixed array of passed arguments.
	 * @return mixed result of running filter, default is empty string.
	 */
	public function __call( string $function, array $args ): mixed
	{
		if ( is_callable( $function ) ) {
			ob_start();
			try {
				$output = call_user_func( $function, ...$args );
				$content = ob_get_clean();
				return $output ?? $content;
			} catch ( \Error $e ) {
				return '';
			}
		}
		return apply_filters( "{$this->package}_{$function}", '', ...$args );
	}
	/**
	 * Return the filters title of a page/post
	 *
	 * @param string $default : optional default title to pass.
	 *
	 * @return string
	 */
	public function title( string $default = '' ): string
	{
		return apply_filters( "{$this->package}_page_title", $default );
	}
}

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

namespace MarkedEffect\GHPluginUpdater\Processors;

use MarkedEffect\GHPluginUpdater\Core\Abstracts;

/**
 * Service class for router actions
 *
 * @subpackage Services
 */
class PluginHeaders extends Abstracts\Module
{
	/**
	 * The plugin headers to look for in the file.
	 *
	 * @var array<string, string>
	 */
	const PLUGIN_HEADERS = [
		'plugin_uri'      => 'Plugin URI',
		'version'         => 'Version',
		'requires'        => 'Requires at least',
		'tested'          => 'Tested up to',
		'requires_php'    => 'Requires PHP',
	];
	/**
	 * The default headers to look for in the file.
	 *
	 * @var array<string, string>
	 */
	protected array $default_headers = [];
	/**
	 * The constructor for the class.
	 *
	 * @param string $package : the package name.
	 */
	public function __construct( string $package = '' ) {
		
		$this->default_headers = array_fill_keys( 
			array_keys( self::PLUGIN_HEADERS ), ''
		);
		
		parent::__construct( $package );
	}
	/**
     * Get the file data from the file.
     * 
     * Adapted from the WordPress function get_file_data() to accept file
     * contents.
     *
     * @param string                $file Absolute path to the file, or file 
     *                              contents.
     * @param array<string, string> $default_headers List of headers, in the 
     *                              format array( 'HeaderKey' => 'Header Name' ).
     * @param string                $context If specified adds filter hook 
     *                              'extra_$context_headers'.
     * @see https://developer.wordpress.org/reference/functions/get_file_data/
     *
     * @return array
     */
    public function getFileData( 
		string $file, 
		array $default_headers = [], 
		$context = ''
	): array
    {
        $default_headers = array_merge(self::PLUGIN_HEADERS, $default_headers);
        /**
         * If passed a file, use the default WP function to get the file data.
         */
        if ( is_file( $file ) ) {
            $file_data = get_file_data( $file, $default_headers, $context );
            return is_array( $file_data ) ? $file_data : [];
        }

        // Pull only the first 8 KB of the file in.
        $file_data = substr( $file, 0, 8 * KB_IN_BYTES );

        // Make sure we catch CR-only line endings.
        $file_data = str_replace( "\r", "\n", $file_data );

        /**
         * Filters extra file headers by context.
         *
         * The dynamic portion of the hook name, `$context`, refers to
         * the context where extra headers might be loaded.
         *
         * @since 2.9.0
         *
         * @param array $extra_context_headers Empty array by default.
         */
        $extra_headers = $context ? apply_filters( "extra_{$context}_headers", array() ) : array();
        if ( $extra_headers ) {
            $extra_headers = array_combine( $extra_headers, $extra_headers ); // Keys equal values.
            $all_headers   = array_merge( $extra_headers, (array) $default_headers );
        } else {
            $all_headers = $default_headers;
        }

        foreach ( $all_headers as $field => $regex ) {
            if ( preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file_data, $match ) && $match[1] ) {
                $all_headers[ $field ] = _cleanup_header_comment( $match[1] );
            } else {
                $all_headers[ $field ] = '';
            }
        }

        return array_merge( $this->default_headers, $all_headers );
    }
}
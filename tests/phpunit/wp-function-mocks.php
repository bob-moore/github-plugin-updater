<?php
/**
 * WordPress mock functions.
 */

if ( ! function_exists( 'plugin_basename' ) ) {
	function plugin_basename() {
		return \WP_Mock\Handler::predefined_return_function_helper( __FUNCTION__, func_get_args() );
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url() {
		return \WP_Mock\Handler::predefined_return_function_helper( __FUNCTION__, func_get_args() );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return \WP_Mock\Handler::predefined_return_function_helper( __FUNCTION__, func_get_args() );
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	/**
	 * Get the filesystem directory path (with trailing slash) for the plugin __FILE__ passed in.
	 *
	 * @since 2.8.0
	 *
	 * @param string $file The filename of the plugin (__FILE__).
	 *
	 * @return string the filesystem path of the directory that contains the plugin.
	 */
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title() {
		return 'Sample Page';
	}
}

if ( ! function_exists( 'the_title' ) ) {
	function the_title() {
		echo get_the_title();
	}
}

if ( ! function_exists( 'get_theme_file_path' ) ) {
	function get_theme_file_path( $file = '' ) {
		return ! empty( $file ) ? WP_CONTENT_DIR . '/' . $file : WP_CONTENT_DIR;
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * Appends a trailing slash.
	 *
	 * Will remove trailing forward and backslashes if it exists already before adding
	 * a trailing forward slash. This prevents double slashing a string or path.
	 *
	 * The primary use of this is for paths and thus should be used for paths. It is
	 * not restricted to paths and offers no specific path support.
	 *
	 * @since 1.2.0
	 *
	 * @param string $string What to add the trailing slash to.
	 *
	 * @return string String with trailing slash added.
	 */
	function trailingslashit( $string ) {
		return untrailingslashit( $string ) . '/';
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	/**
	 * Removes trailing forward slashes and backslashes if they exist.
	 *
	 * The primary use of this is for paths and thus should be used for paths. It is
	 * not restricted to paths and offers no specific path support.
	 *
	 * @since 2.2.0
	 *
	 * @param string $string What to remove the trailing slashes from.
	 *
	 * @return string String without the trailing slashes.
	 */
	function untrailingslashit( $string ) {
		return rtrim( $string, '/\\' );
	}
}

if (  ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Check whether variable is a WordPress Error.
	 *
	 * Returns true if $thing is an object of the WP_Error class.
	 *
	 * @since 2.1.0
	 *
	 * @param mixed $thing Check if unknown variable is a WP_Error object.
	 *
	 * @return bool True, if WP_Error. False, if not WP_Error.
	 */
	function is_wp_error( $thing ) {
		return ( $thing instanceof \WP_Error );
	}
}
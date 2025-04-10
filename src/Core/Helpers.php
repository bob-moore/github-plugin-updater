<?php
/**
 * Helper Functions
 *
 * PHP Version 8.2
 *
 * @package github_plugin_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Core;

/**
 * App Factory
 *
 * @subpackage Utilities
 */
class Helpers
{
	/**
	 * Check if a class uses a specified parent class, interface, or trait
	 *
	 * @param string|object $instance_or_class : class or object to check.
	 * @param string        $needle : interface, class, or trait name.
	 *
	 * @return boolean
	 */
	public static function classUses( $instance_or_class, string $needle ): bool
	{
		$class_name = self::className( $instance_or_class );

		if ( empty( $class_name ) ) {
			return false;
		}
		switch ( true ) {
			case class_exists( $needle ):
				return is_subclass_of( $class_name, $needle ) || $class_name === $needle;
			case interface_exists( $needle ):
				return self::implements( $class_name, $needle );
			case trait_exists( $needle ):
				return self::uses( $class_name, $needle );
			default:
				return false;
		}
	}
	/**
	 * Get the string class name of an object or name
	 *
	 * @param string|object $instance_or_class : either an object or class name.
	 *
	 * @return string|false
	 */
	public static function className( $instance_or_class )
	{
		switch ( true ) {
			case is_object( $instance_or_class ):
				$name = get_class( $instance_or_class );
				break;
			case class_exists( $instance_or_class ):
				$name = $instance_or_class;
				break;
			default:
				$name = false;
		}
		return $name;
	}
	/**
	 * Recursive `uses` function
	 *
	 * Used to check all traits of class and parent to see if it is implemented
	 * in the target class/object
	 *
	 * @param string|object $instance_or_class : instance or class name.
	 * @param string        $trait_class : trait name to use.
	 *
	 * @return boolean
	 */
	public static function uses( $instance_or_class, string $trait_class ): bool
	{
		$class_name = is_object( $instance_or_class ) ? get_class( $instance_or_class ) : $instance_or_class;

		if ( ! class_exists( $class_name ) ) {
			return false;
		}
		return in_array( $trait_class, self::getTraits( $class_name ), true );
	}
	/**
	 * Check if class implements an interface
	 *
	 * Checks against a supplied interface class name.
	 *
	 * @param string|object $instance_or_class : instance or class name.
	 * @param string        $interface_class : interface class to check against.
	 *
	 * @return boolean
	 */
	public static function implements( $instance_or_class, string $interface_class ): bool
	{
		$class_name = is_object( $instance_or_class ) ? get_class( $instance_or_class ) : $instance_or_class;

		if ( ! class_exists( $class_name ) ) {
			return false;
		}
		return in_array( $interface_class, class_implements( $class_name ), true );
	}
	/**
	 * Recursively get all traits used by stack
	 *
	 * @param object|string $instance_or_class : class to check.
	 *
	 * @return array<string>
	 */
	public static function getTraits( $instance_or_class ): array
	{
		$class_name = is_object( $instance_or_class ) ? get_class( $instance_or_class ) : $instance_or_class;

		if ( ! class_exists( $class_name ) ) {
			return [];
		}

		$traits = self::usesTrait( $class_name );

		$parents = class_parents( $class_name );

		if ( ! empty( $parents ) ) {
			foreach ( $parents as $parent ) {
				$traits += self::usesTrait( $parent );
			}
		}
		return array_unique( $traits );
	}
	/**
	 * Check traits of a single class.
	 *
	 * @param string|object $instance_or_class : class to check.
	 *
	 * @return array<string>
	 */
	public static function usesTrait( $instance_or_class ): array
	{
		$class_name = is_object( $instance_or_class ) ? get_class( $instance_or_class ) : $instance_or_class;

		if ( ! class_exists( $class_name ) ) {
			return [];
		}

		$traits = class_uses( $class_name );

		return ! empty( $traits ) ? $traits : [];
	}
	/**
	 * Check if an array is associative or indexed
	 *
	 * @param mixed $array : array to check.
	 *
	 * @return boolean
	 */
	public static function isList( $array ): bool
	{
		if ( ! is_array( $array ) ) {
			return false;
		}

		if ( empty( $array ) ) {
			return true;
		}

		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $array );
		}

		return array_keys( $array ) === range( 0, count( $array ) - 1 );
	}
	/**
	 * Transform into a boolean value
	 *
	 * @param mixed $value : value to transform.
	 *
	 * @return bool
	 */
	public static function truthyFalsy( $value ): bool
	{
		switch ( true ) {
			case is_bool( $value ):
				return $value;
			case is_string( $value ):
				return in_array( strtolower( $value ), [ 'true', 'yes', 'on', '1' ], true );
			case is_numeric( $value ):
				return (bool) $value;
			default:
				return false;
		}
	}
	/**
	 * Check if a particular plugin is active and present in the environment
	 *
	 * @param string $plugin : dir/name.php of the plugin to check.
	 *
	 * @return bool
	 */
	public static function isPluginActive( string $plugin ): bool
	{
		if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_PLUGIN_DIR' ) ) {
			return false;
		}

		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		return is_file( WP_PLUGIN_DIR . '/' . $plugin ) && is_plugin_active( $plugin );
	}
	/**
	 * Replace slashes and spaces in a string with underscores, and convert to lowercase
	 *
	 * @param string $raw_string : string to slugify.
	 *
	 * @return string
	 */
	public static function slugify( string $raw_string ): string
	{
		return strtolower( str_replace( [ '\\', '/', ' ', '-' ], '_', $raw_string ) );
	}
	/**
	 * Hyphenate a string
	 *
	 * Replaces spaces, underscores, and slashes with hyphens and converts to lowercase.
	 * Used to convert class names to CSS class names.
	 *
	 * @param string $raw_string string to hyphenate.
	 *
	 * @return string
	 */
	public static function hyphenate( string $raw_string ): string
	{
		return strtolower( str_replace( [ '\\', '/', ' ', '_' ], '-', $raw_string ) );
	}
}

<?php
/**
 * Shared helpers for test classes that declare TEST_CLASS.
 *
 * Provides a common way to resolve the class under test and instantiate it
 * without invoking its constructor.
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Services
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Traits;

trait ClassInstanceTrait
{
    /**
     * Returns the fully-qualified class name under test.
     *
     * Test classes are expected to define a TEST_CLASS constant.
     *
     * @return class-string
     * @throws \RuntimeException When TEST_CLASS is not defined.
     */
    protected static function getTestClass(): string
    {
        if ( ! defined( 'static::TEST_CLASS' ) ) {
            throw new \RuntimeException( 'TEST_CLASS constant is not defined in test class.' );
        }

        return static::TEST_CLASS;
    }

    /**
     * Creates an instance of the class under test without running its constructor.
     *
     * This is useful for DI-heavy classes whose constructors require runtime wiring.
     *
     * @return object
     * @throws \RuntimeException When the class under test does not exist.
     * @throws \ReflectionException When reflection cannot instantiate the class.
     */
    protected static function getTestInstance(): object
    {
        $class_name = static::getTestClass();

        if ( ! class_exists( $class_name ) ) {
            throw new \RuntimeException( "Test class {$class_name} does not exist." );
        }

        $reflection = new \ReflectionClass( $class_name );

        return $reflection->newInstanceWithoutConstructor();
    }
}

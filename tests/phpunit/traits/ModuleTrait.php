<?php
/**
 * Shared module behavior tests.
 *
 * Validates common behavior inherited from the base Module abstraction.
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Services
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Traits;

trait ModuleTrait
{
    use ClassInstanceTrait;

    /**
     * Tests package normalization and accessors on Module implementations.
     *
     * @covers Bmd\GithubWpUpdater\Core\Abstracts\Module::setPackage
     * @covers Bmd\GithubWpUpdater\Core\Abstracts\Module::getPackage
     */
    public function testPackage(): void
    {
        $testClass = static::getTestClass();

        $this->assertTrue(
            class_exists( $testClass ),
            'Test class does not exist'
        );

        $instance = static::getTestInstance();

        $this->assertInstanceOf(
            \Bmd\GithubWpUpdater\Core\Abstracts\Module::class,
            $instance,
            'Test class does not extend Module abstract class'
        );

        $this->assertTrue(
            method_exists( $instance, 'setPackage' ),
            'Module class does not have setPackage method'
        );

        // Ensure getter and setter are wired.
        $instance->setPackage( 'test_package' );

        $this->assertEquals(
            'test_package',
            $instance->getPackage(),
            'Package setter or getter did not set or return the expected value'
        );

        // Ensure forward slashes are normalized to underscores.
        $instance->setPackage( 'test/package' );
        $this->assertEquals(
            'test_package',
            $instance->getPackage(),
            'Package setter did not handle forward slashes correctly'
        );

        // Ensure backward slashes are normalized to underscores.
        $instance->setPackage( 'test\\package' );
        $this->assertEquals(
            'test_package',
            $instance->getPackage(),
            'Package setter did not handle backward slashes correctly'
        );

        // Ensure dashes are normalized to underscores.
        $instance->setPackage( 'test-package' );
        $this->assertEquals(
            'test_package',
            $instance->getPackage(),
            'Package setter did not handle dashes correctly'
        );

        // Ensure spaces are normalized to underscores.
        $instance->setPackage( 'test package' );
        $this->assertEquals(
            'test_package',
            $instance->getPackage(),
            'Package setter did not handle spaces correctly'
        );

        // Ensure values are normalized to lowercase.
        $instance->setPackage( 'TEST_PACKAGE' );
        $this->assertEquals(
            'test_package',
            $instance->getPackage(),
            'Package setter did not handle uppercase letters correctly'
        );
    }
}

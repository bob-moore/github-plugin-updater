<?php
/**
 * Plugin Info Test Cases
 *
 * PHP Version 8.2
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Services
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\PHPUnit\Common;

trait ModuleTests
{
    /**
     * Test the package setter
     * 
     * This test will verify that the package name is set.
     * 
     * @covers Class::setPackage, Class::getPackage
     *
     * @return void
     */
    public function testPackageSettersGetters(): void
    {
        $this->module->setPackage( 'new_package_name' );

        $this->assertEquals( 'new_package_name', $this->module->getPackage() );

        $this->module->setPackage( 'new/package/name' );

        $this->assertEquals( 'new_package_name', $this->module->getPackage() );

        $this->module->setPackage( 'New\\Package\\Name' );

        $this->assertEquals( 'new_package_name', $this->module->getPackage() );
    }
}
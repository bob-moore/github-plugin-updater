<?php
/**
 * Generic Controller Test Cases
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

trait ControllerTest
{
    /**
     * Test the getter for service definitions
     * 
     * @covers Class::getServiceDefinitions
     *
     * @return void
     */
    public function testGetServiceDefinitions(): void
    {
        $actual = $this->module::getServiceDefinitions();

        $this->assertIsArray( $actual );
    }
}
<?php
/**
 * Test the Processor controller
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

namespace MarkedEffect\GHPluginUpdater\PHPUnit\Controllers;

use MarkedEffect\GHPluginUpdater\Controllers,
    MarkedEffect\GHPluginUpdater\Core\Abstracts,
    MarkedEffect\GHPluginUpdater\PHPUnit\Common;

use WP_Mock\Tools\TestCase as TestCase;

class ProcessorControllerTest extends TestCase
{
    use Common\ControllerTest;
    /**
     * Instance of the module being tested
     *
     * @var Abstracts\Controller
     */
    protected ?Abstracts\Controller $module;
    /**
     * Setup the test case with a new instance of the class
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->module = new Controllers\ProcessorController();
        parent::setUp();
    }
    /**
     * Nullify the service class to start fresh on the next test
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->module = null;

        parent::tearDown();
    }
}
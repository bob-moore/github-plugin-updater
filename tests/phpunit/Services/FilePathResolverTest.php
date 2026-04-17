<?php
/**
 * FilePathResolver service tests.
 *
 * Verifies path normalization behavior for directory resolution.
 *
 * @package github_plugin_updater
 * @subpackage PHPUnit/Tests/Services
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Services;

use Bmd\GithubWpUpdater\Services\FilePathResolver;
use WP_Mock\Tools\TestCase as TestCase;
use Bmd\GithubWpUpdater\PHPUnit\Traits\ModuleTrait;

/**
 * Test suite for the FilePathResolver service.
 */
final class FilePathResolverTest extends TestCase
{
    use ModuleTrait;

    /**
     * Fully qualified class name used by shared module trait assertions.
     *
     * @var class-string<FilePathResolver>
     */
    const TEST_CLASS = FilePathResolver::class;

    /**
     * Confirms resolve() returns normalized paths for common input patterns.
     *
     * @covers \Bmd\GithubWpUpdater\Services\FilePathResolver::resolve
     */
    public function testResolve(): void
    {
        $resolver = new FilePathResolver( __DIR__, 'github_wp_updater' );

        // No append argument should return the base directory unchanged.
        $this->assertSame( __DIR__, $resolver->resolve() );

        // Appended segments should be normalized to a single path without trailing slash.
        $this->assertSame( __DIR__ . '/Routes/Error404', $resolver->resolve( 'Routes/Error404/' ) );
        $this->assertSame( __DIR__ . '/Routes/Error404', $resolver->resolve( 'Routes/Error404' ) );

        // Whitespace-only append input should be treated as empty.
        $this->assertSame( __DIR__, $resolver->resolve( '  ' ) );
    }
}
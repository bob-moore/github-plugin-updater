<?php
/**
 * GithubWpUpdater bootstrap tests.
 *
 * @package github_plugin_updater
 */

namespace Bmd\GithubWpUpdater\PHPUnit;

use Bmd\GithubWpUpdater;
use WP_Mock;
use WP_Mock\Tools\TestCase;

final class MainTest extends TestCase
{
	/**
	 * Ensures normalizeConfig derives all expected keys from a valid root file.
	 *
	 * @covers \Bmd\GithubWpUpdater::__construct
	 */
	public function testConstructorBuildsConfigFromProvidedRootFile(): void
	{
		$fixture = $this->makeTempPlugin( 'root-file' );

		WP_Mock::userFunction(
			'get_file_data',
			[
				'times'  => 1,
				'args'   => [
					$fixture['file'],
					[ 'version' => 'Version' ],
				],
				'return' => [ 'version' => '1.2.3' ],
			]
		);

		$main   = new TestableGithubWpUpdater( $fixture['file'] );
		$config = $main->exposeConfig();

		$this->assertSame( plugin_dir_path( $fixture['file'] ), $config['config.dir'] );
		$this->assertSame( plugin_dir_url( $fixture['file'] ), $config['config.url'] );
		$this->assertSame( basename( $fixture['dir'] ), $config['plugin.slug'] );
		$this->assertSame( 'example-plugin.php', $config['plugin.file'] );
		$this->assertSame( '1.2.3', $config['plugin.version'] );

		$this->removeTempPlugin( $fixture );
	}

	/**
	 * Ensures constructor can infer root file from active plugins when missing.
	 *
	 * @covers \Bmd\GithubWpUpdater::__construct
	 * @runInSeparateProcess
	 */
	public function testConstructorInfersRootFileWhenMissing(): void
	{
		$repo_root   = dirname( __DIR__, 2 );
		$source_file = $repo_root . '/src/GithubWpUpdater.php';

		define( 'WP_PLUGIN_DIR', $repo_root );

		WP_Mock::userFunction(
			'get_plugins',
			[
				'times'  => 1,
				'return' => [
					'src/GithubWpUpdater.php' => [],
				],
			]
		);

		WP_Mock::userFunction(
			'get_file_data',
			[
				'times'  => 1,
				'args'   => [
					$source_file,
					[ 'version' => 'Version' ],
				],
				'return' => [ 'version' => '0.1.0' ],
			]
		);

		$main = new TestableGithubWpUpdater( '' );

		$this->assertSame( 'GithubWpUpdater.php', $main->exposeConfig()['plugin.file'] );
	}

	/**
	 * Ensures constructor throws when no valid root file can be resolved.
	 *
	 * @covers \Bmd\GithubWpUpdater::__construct
	 * @runInSeparateProcess
	 */
	public function testConstructorThrowsWhenRootFileCannotBeResolved(): void
	{
		define( 'WP_PLUGIN_DIR', sys_get_temp_dir() . '/not-the-repo-plugins-dir' );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Root file not found. Please provide a valid path to the root plugin file.' );

		new TestableGithubWpUpdater( '' );
	}

	/**
	 * Ensures nested banner overrides preserve unspecified defaults.
	 *
	 * @covers \Bmd\GithubWpUpdater::__construct
	 */
	public function testConstructorUsesArrayReplaceRecursiveForNestedOverrides(): void
	{
		$fixture = $this->makeTempPlugin( 'override' );

		WP_Mock::userFunction( 'get_file_data', [ 'times' => 1, 'return' => [ 'version' => '9.9.9' ] ] );

		$main = new TestableGithubWpUpdater(
			$fixture['file'],
			[
				'plugin.banners' => [
					'low' => 'https://example.com/custom-banner-low.jpg',
				],
			]
		);

		$config = $main->exposeConfig();

		$this->assertSame( 'https://example.com/custom-banner-low.jpg', $config['plugin.banners']['low'] );
		$this->assertStringContainsString( 'banner-1544x500.jpg', $config['plugin.banners']['high'] );

		$this->removeTempPlugin( $fixture );
	}

	/**
	 * Ensures setKnownAssets leaves current values untouched when files are absent.
	 *
	 * @covers \Bmd\GithubWpUpdater::setKnownAssets
	 */
	public function testSetKnownAssetsKeepsCurrentValuesWhenNoFilesExist(): void
	{
		$fixture = $this->makeTempPlugin( 'assets-none' );

		WP_Mock::userFunction( 'get_file_data', [ 'times' => 1, 'return' => [ 'version' => '1.0.0' ] ] );

		$main    = new TestableGithubWpUpdater( $fixture['file'] );
		$config  = $main->exposeConfig();
		$updated = $main->setKnownAssets( $config );

		$this->assertSame( $config['plugin.icons']['default'], $updated['plugin.icons']['default'] );
		$this->assertSame( $config['plugin.banners']['low'], $updated['plugin.banners']['low'] );
		$this->assertSame( $config['plugin.banners']['high'], $updated['plugin.banners']['high'] );

		$this->removeTempPlugin( $fixture );
	}

	/**
	 * Ensures all asset keys are set when files exist and values are empty.
	 *
	 * @covers \Bmd\GithubWpUpdater::setKnownAssets
	 */
	public function testSetKnownAssetsReplacesAllEmptyKeysWhenFilesExist(): void
	{
		$asset_fixture = $this->makeAssetFixture( 'assets-all', [
			'icon-256x256.jpg',
			'banner-772x250.jpg',
			'banner-1544x500.jpg',
		] );

		$main = $this->newUpdaterForAssetMutationTests();

		$updated = $main->setKnownAssets( $this->emptyAssetConfig( $asset_fixture['dir'] ) );

		$this->assertSame( 'https://example.com/example-plugin/assets/icon-256x256.jpg', $updated['plugin.icons']['default'] );
		$this->assertSame( 'https://example.com/example-plugin/assets/banner-772x250.jpg', $updated['plugin.banners']['low'] );
		$this->assertSame( 'https://example.com/example-plugin/assets/banner-1544x500.jpg', $updated['plugin.banners']['high'] );

		$this->removeAssetFixture( $asset_fixture );
	}

	/**
	 * Ensures only existing files are promoted when assets are partial.
	 *
	 * @covers \Bmd\GithubWpUpdater::setKnownAssets
	 */
	public function testSetKnownAssetsReplacesOnlyExistingFiles(): void
	{
		$asset_fixture = $this->makeAssetFixture( 'assets-partial', [
			'banner-772x250.jpg',
		] );

		$main = $this->newUpdaterForAssetMutationTests();

		$updated = $main->setKnownAssets( $this->emptyAssetConfig( $asset_fixture['dir'] ) );

		$this->assertSame( '', $updated['plugin.icons']['default'] );
		$this->assertSame( 'https://example.com/example-plugin/assets/banner-772x250.jpg', $updated['plugin.banners']['low'] );
		$this->assertSame( '', $updated['plugin.banners']['high'] );

		$this->removeAssetFixture( $asset_fixture );
	}

	/**
	 * Ensures explicit caller asset keys are not overwritten.
	 *
	 * @covers \Bmd\GithubWpUpdater::setKnownAssets
	 */
	public function testSetKnownAssetsDoesNotOverwritePrepopulatedAsset(): void
	{
		$asset_fixture = $this->makeAssetFixture( 'assets-prepopulated', [
			'icon-256x256.jpg',
		] );

		$main = $this->newUpdaterForAssetMutationTests();

		$config                            = $this->emptyAssetConfig( $asset_fixture['dir'] );
		$config['plugin.icons']['default'] = 'https://example.com/already-set.jpg';

		$updated = $main->setKnownAssets( $config );

		$this->assertSame( 'https://example.com/already-set.jpg', $updated['plugin.icons']['default'] );

		$this->removeAssetFixture( $asset_fixture );
	}

	/**
	 * Ensures getRootFileFromPath bails when current directory is outside WP_PLUGIN_DIR.
	 *
	 * @covers \Bmd\GithubWpUpdater::getRootFileFromPath
	 * @runInSeparateProcess
	 */
	public function testGetRootFileFromPathReturnsEmptyWhenOutsidePluginsDir(): void
	{
		$fixture = $this->makeTempPlugin( 'outside-plugins-dir' );

		define( 'WP_PLUGIN_DIR', sys_get_temp_dir() . '/outside-' . uniqid() );

		WP_Mock::userFunction( 'get_file_data', [ 'times' => 1, 'return' => [ 'version' => '1.0.0' ] ] );

		$main = new TestableGithubWpUpdater( $fixture['file'] );

		$this->assertSame( '', $main->exposeGetRootFileFromPath() );

		$this->removeTempPlugin( $fixture );
	}

	/**
	 * Ensures getRootFileFromPath resolves a plugin file on exact directory match.
	 *
	 * @covers \Bmd\GithubWpUpdater::getRootFileFromPath
	 * @runInSeparateProcess
	 */
	public function testGetRootFileFromPathReturnsFileForExactDirectoryMatch(): void
	{
		$repo_root = dirname( __DIR__, 2 );

		define( 'WP_PLUGIN_DIR', $repo_root );

		WP_Mock::userFunction(
			'get_plugins',
			[
				'times'  => 1,
				'return' => [
					'src/GithubWpUpdater.php' => [],
					'vendor/bmd/wp-framework/includes/Main.php' => [],
				],
			]
		);

		$main = $this->newUpdaterForAssetMutationTests();

		$this->assertSame( $repo_root . '/src/GithubWpUpdater.php', $main->exposeGetRootFileFromPath() );
	}

	/**
	 * Build a temporary plugin root file fixture.
	 *
	 * @param string $suffix Unique suffix.
	 *
	 * @return array{dir: string, file: string}
	 */
	private function makeTempPlugin( string $suffix ): array
	{
		$plugin_dir  = sys_get_temp_dir() . '/github-plugin-updater-' . $suffix . '-' . uniqid();
		$plugin_file = $plugin_dir . '/example-plugin.php';

		mkdir( $plugin_dir, 0777, true );
		file_put_contents( $plugin_file, "<?php\n/*\nVersion: 1.0.0\n*/" );

		return [
			'dir'  => $plugin_dir,
			'file' => $plugin_file,
		];
	}

	/**
	 * Remove a temporary plugin fixture.
	 *
	 * @param array{dir: string, file: string} $fixture Fixture from makeTempPlugin().
	 */
	private function removeTempPlugin( array $fixture ): void
	{
		if ( is_file( $fixture['file'] ) ) {
			unlink( $fixture['file'] );
		}

		if ( is_dir( $fixture['dir'] ) ) {
			rmdir( $fixture['dir'] );
		}
	}

	/**
	 * Build a plugin assets directory fixture.
	 *
	 * @param string             $suffix Unique suffix.
	 * @param array<int, string> $assets Asset file names to create.
	 *
	 * @return array{dir: string, assets: string}
	 */
	private function makeAssetFixture( string $suffix, array $assets ): array
	{
		$plugin_dir = sys_get_temp_dir() . '/github-plugin-updater-' . $suffix . '-' . uniqid();
		$asset_dir  = $plugin_dir . '/assets';

		mkdir( $asset_dir, 0777, true );

		foreach ( $assets as $asset_file ) {
			file_put_contents( $asset_dir . '/' . $asset_file, 'asset' );
		}

		return [
			'dir'    => $plugin_dir,
			'assets' => $asset_dir,
		];
	}

	/**
	 * Remove a plugin assets fixture.
	 *
	 * @param array{dir: string, assets: string} $fixture Fixture from makeAssetFixture().
	 */
	private function removeAssetFixture( array $fixture ): void
	{
		if ( is_dir( $fixture['assets'] ) ) {
			$files = scandir( $fixture['assets'] );

			if ( false !== $files ) {
				foreach ( $files as $file ) {
					if ( '.' === $file || '..' === $file ) {
						continue;
					}

					unlink( $fixture['assets'] . '/' . $file );
				}
			}

			rmdir( $fixture['assets'] );
		}

		if ( is_dir( $fixture['dir'] ) ) {
			rmdir( $fixture['dir'] );
		}
	}

	/**
	 * Create an updater instance suitable for direct setKnownAssets tests.
	 *
	 * @return TestableGithubWpUpdater
	 */
	private function newUpdaterForAssetMutationTests(): TestableGithubWpUpdater
	{
		$fixture = $this->makeTempPlugin( 'factory' );

		WP_Mock::userFunction( 'get_file_data', [ 'times' => 1, 'return' => [ 'version' => '1.0.0' ] ] );

		$main = new TestableGithubWpUpdater( $fixture['file'] );

		$this->removeTempPlugin( $fixture );

		return $main;
	}

	/**
	 * Build a config array with empty asset keys.
	 *
	 * @param string $plugin_dir Plugin directory path.
	 *
	 * @return array<string, mixed>
	 */
	private function emptyAssetConfig( string $plugin_dir ): array
	{
		return [
			'config.dir'     => $plugin_dir,
			'config.url'     => 'https://example.com/example-plugin/',
			'plugin.icons'   => [ 'default' => '' ],
			'plugin.banners' => [
				'low'  => '',
				'high' => '',
			],
		];
	}
}

final class TestableGithubWpUpdater extends GithubWpUpdater
{
	/**
	 * Expose computed config for assertions.
	 *
	 * @return array<string, mixed>
	 */
	public function exposeConfig(): array
	{
		return $this->config;
	}

	/**
	 * Expose protected root matcher for direct assertions.
	 *
	 * @return string
	 */
	public function exposeGetRootFileFromPath(): string
	{
		return $this->getRootFileFromPath();
	}
}

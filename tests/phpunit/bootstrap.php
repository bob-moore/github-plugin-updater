<?php
/**
 * Bootstrap file for unit tests.
 *
 * @package Bmd\GithubWpUpdater
 */
$project_root = dirname( __DIR__, 2 );

require_once $project_root . '/vendor/autoload.php';

if ( file_exists( $project_root . '/vendor/scoped/autoload.php' ) ) {
	require_once $project_root . '/vendor/scoped/autoload.php';
}

if ( file_exists( $project_root . '/vendor/scoped/scoper-autoload.php' ) ) {
	require_once $project_root . '/vendor/scoped/scoper-autoload.php';
}

require_once dirname(  __FILE__ ) . '/wp-function-mocks.php';

define( 'TEST_UNIT_PACKAGE_NAME', 'github_wp_updater' );

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();
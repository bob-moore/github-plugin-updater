<?php
/**
 * Bootstrap file for unit tests.
 *
 * @package Mwf\Cornerstone
 */
require_once dirname( __DIR__, 2 ) . '/vendor/scoped/autoload.php';
require_once dirname( __DIR__, 2 ) . '/vendor/scoped/scoper-autoload.php';
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

require_once dirname(  __FILE__ ) . '/wp-function-mocks.php';

define('TEST_UNIT_PACKAGE_NAME', 'mwf_cornerstone' );

\WP_Mock::setUsePatchwork(true);
\WP_Mock::bootstrap();
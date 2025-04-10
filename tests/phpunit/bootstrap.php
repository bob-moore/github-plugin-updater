<?php
/**
 * Bootstrap file for unit tests.
 */
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

require_once dirname(  __FILE__ ) . '/wp-function-mocks.php';

\WP_Mock::setUsePatchwork(true);
\WP_Mock::bootstrap();
<?php
/**
 * Plugin Name: Sync Error
 * Description: Cause an error during sync, for test purposes.
 * Version:     1.0.0
 * Author:      10up Inc.
 * License:     GPLv2 or later
 *
 * @package ElasticPress_Tests_E2e
 */

add_filter( 'ep_total_field_limit', fn() => 100 );

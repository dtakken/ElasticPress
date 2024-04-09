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

add_filter(
	'ep_prepare_meta_data',
	function ( $post_meta, $post ) {
		if ( 0 === $post->ID % 2 ) {
			for ( $i = 0; $i < 100; $i++ ) {
				$post_meta[ "test_meta_{$i}_title_{$post->ID}" ] = 'Lorem';
				$post_meta[ "test_meta_{$i}_body_{$post->ID}" ]  = 'Ipsum';
			}
		}
		return $post_meta;
	},
	10,
	2
);

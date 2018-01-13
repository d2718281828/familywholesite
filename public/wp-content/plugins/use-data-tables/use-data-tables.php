<?php
/*
Plugin Name: Use data tables
Plugin URI:
Description: Add the CDN resourc links to wp_head
Version: 0.1
Author: Derek Storkey
Author URI:
License: GPLv2 or later
Text Domain: useDataTables
*/

function use_data_tables_enqueue_style() {
	wp_enqueue_style( 'use_data_tables_cdn_css', '//cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css' ); 
}

function use_data_tables_enqueue_script() {
	$dir = plugin_dir_url(__FILE__);
	wp_enqueue_script( 'use_data_tables_cdn_js', '//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js', ["jquery"] );
	wp_enqueue_script( 'use_data_tables_cdn_js', $dir.'/js/use-data-tables.js', ["use_data_tables_cdn_js"] );
}

add_action( 'wp_enqueue_scripts', 'use_data_tables_enqueue_style' );
add_action( 'wp_enqueue_scripts', 'use_data_tables_enqueue_script' );
<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Drop tables
$tables = array(
    $wpdb->prefix . 'abf_churches',
    $wpdb->prefix . 'abf_bookings',
    $wpdb->prefix . 'abf_feedback',
    $wpdb->prefix . 'abf_images',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Delete options
delete_option( 'abf_pc_time_start' );
delete_option( 'abf_pc_time_end' );
delete_option( 'abf_pc_program_name' );
delete_option( 'abf_pc_version' );

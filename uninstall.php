<?php
/**
 * Uninstall handler for MHard Photobook
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Only remove data if the option is set
$settings = get_option( 'cl_settings', [] );
$remove = ! empty( $settings['remove_data_on_uninstall'] );

if ( $remove ) {
    global $wpdb;
    $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'configurator_options' );
    $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'configurator_groups' );
    $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'configurator_clients' );
    $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'configurator_submissions' );
    delete_option( 'cl_settings' );
}

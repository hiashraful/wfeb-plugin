<?php
/**
 * WFEB Plugin Uninstall
 *
 * Fired when the plugin is deleted via WordPress admin.
 *
 * SAFE MODE: Does NOT delete data by default.
 * Data removal only happens if the admin explicitly enables it
 * via Settings > General > "Delete all data on uninstall".
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Only delete data if the admin explicitly opted in.
if ( get_option( 'wfeb_delete_data_on_uninstall' ) !== 'yes' ) {
    return;
}

global $wpdb;

// Remove database tables
$tables = array(
    $wpdb->prefix . 'wfeb_credit_transactions',
    $wpdb->prefix . 'wfeb_certificates',
    $wpdb->prefix . 'wfeb_exams',
    $wpdb->prefix . 'wfeb_players',
    $wpdb->prefix . 'wfeb_coaches',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL
}

// Remove roles
remove_role( 'wfeb_coach' );
remove_role( 'wfeb_player' );

// Remove admin capabilities
$admin_role = get_role( 'administrator' );
if ( $admin_role ) {
    $caps = array(
        'wfeb_manage_coaches',
        'wfeb_manage_players',
        'wfeb_manage_exams',
        'wfeb_manage_certificates',
        'wfeb_manage_settings',
    );
    foreach ( $caps as $cap ) {
        $admin_role->remove_cap( $cap );
    }
}

// Delete pages created by plugin
$page_options = array(
    'wfeb_coach_dashboard_page_id',
    'wfeb_player_dashboard_page_id',
    'wfeb_coach_login_page_id',
    'wfeb_coach_registration_page_id',
    'wfeb_player_login_page_id',
    'wfeb_forgot_password_page_id',
    'wfeb_verify_certificate_page_id',
);

foreach ( $page_options as $option ) {
    $page_id = get_option( $option );
    if ( $page_id ) {
        wp_delete_post( $page_id, true );
    }
}

// Delete all plugin options
$options = array(
    'wfeb_version',
    'wfeb_cert_prefix',
    'wfeb_cert_start',
    'wfeb_coach_approval_mode',
    'wfeb_credit_product_id',
    'wfeb_authoriser_name',
    'wfeb_email_from_name',
    'wfeb_email_from_address',
    'wfeb_certificate_bg_id',
    'wfeb_cert_background',
    'wfeb_cert_authoriser_name',
    'wfeb_verification_secret',
    'wfeb_delete_data_on_uninstall',
);

foreach ( $options as $option ) {
    delete_option( $option );
}
foreach ( $page_options as $option ) {
    delete_option( $option );
}

// Delete uploaded certificate files
$upload_dir = wp_upload_dir();
$cert_dir = $upload_dir['basedir'] . '/wfeb-certificates';
if ( is_dir( $cert_dir ) ) {
    $files = glob( $cert_dir . '/*' );
    if ( is_array( $files ) ) {
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
    }
    rmdir( $cert_dir );
}

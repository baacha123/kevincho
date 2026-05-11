<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

/* Remove notification log table */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_notification_log" );

/* Remove personalization tables */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_personalization_groups" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_personalization_options" );

/* Remove consultation tables */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_consultations" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_consultation_availability" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_consultation_blocked_dates" );

/* Remove fabrics table */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_fabrics" );

/* Remove expenses table */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_expenses" );

/* Remove staff table */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_staff" );

/* Remove abandoned carts table */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_abandoned_carts" );

/* Remove fabric stock log table */
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kctm_fabric_stock_log" );

/* Remove consultation product */
$consultation_product_id = get_option( 'kctm_consultation_product_id' );
if ( $consultation_product_id ) {
    wp_delete_post( $consultation_product_id, true );
}

/* Remove all user meta with our prefix */
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_kctm_%'" );

/* Remove all post/order meta with our prefix */
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_kctm_%'" );

/* HPOS order meta cleanup */
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wc_orders_meta'" ) ) {
    $wpdb->query( "DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key LIKE '_kctm_%'" );
}

/* Remove plugin options */
delete_option( 'kctm_whatsapp_settings' );
delete_option( 'kctm_notification_statuses' );
delete_option( 'kctm_db_version' );
delete_option( 'kctm_consultation_product_id' );
delete_option( 'kctm_consultation_settings' );
delete_option( 'kctm_store_settings' );
delete_option( 'kctm_whatsapp_templates' );

/* Flush rewrite rules */
flush_rewrite_rules();

<?php
/**
 * Plugin Name: Auto Inventory Sync
 * Description: Syncs stock levels from external API to WooCommerce every hour.
 * Version: 1.0
 * Author: Shiv Khurana
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// 1. Schedule the event on activation
register_activation_hook( __FILE__, 'ais_activation' );
function ais_activation() {
    if ( ! wp_next_scheduled( 'ais_hourly_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'ais_hourly_event' );
    }
}

// 2. The Hook that runs every hour
add_action( 'ais_hourly_event', 'ais_sync_stock' );

function ais_sync_stock() {
    // Mock API Endpoint (e.g., an ERP system)
    $api_url = 'https://api.mock-erp.com/v1/inventory';
    
    // 3. Fetch Data using WordPress HTTP API
    $response = wp_remote_get( $api_url );
    
    if ( is_wp_error( $response ) ) {
        error_log( 'Inventory Sync Error: ' . $response->get_error_message() );
        return;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    // 4. Update WooCommerce Stock
    if ( ! empty( $data ) ) {
        foreach ( $data as $sku => $stock_qty ) {
            $product_id = wc_get_product_id_by_sku( $sku );
            if ( $product_id ) {
                $product = wc_get_product( $product_id );
                $product->set_stock_quantity( $stock_qty );
                $product->save();
            }
        }
        error_log( 'Inventory Sync Completed Successfully.' );
    }
}

// 5. Clear schedule on deactivation
register_deactivation_hook( __FILE__, 'ais_deactivation' );
function ais_deactivation() {
    wp_clear_scheduled_hook( 'ais_hourly_event' );
}
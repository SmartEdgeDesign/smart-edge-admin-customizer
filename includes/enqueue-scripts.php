<?php
/**
 * Enqueues CSS and JS for the admin area.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

function seac_enqueue_admin_assets() {
    // Load the main admin stylesheet
    wp_enqueue_style(
        'smart-edge-admin-main', 
        SEAC_PLUGIN_URL . 'assets/css/admin-main.css', 
        array(), 
        SEAC_VERSION
    );

    // Load the editor specific stylesheet
    wp_enqueue_style(
        'smart-edge-editor-admin', 
        SEAC_PLUGIN_URL . 'assets/css/editor-admin.css', 
        array(), 
        SEAC_VERSION
    );
}

add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets' );
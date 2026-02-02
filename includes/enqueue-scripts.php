<?php
/**
 * Enqueues CSS and JS for the admin area.
 */
function seac_enqueue_admin_assets() {
    // Load the main admin stylesheet
    wp_enqueue_style(
        'seac-admin-style', 
        SEAC_PLUGIN_URL . 'assets/css/main-admin-style.css', 
        array(), 
        SEAC_VERSION
    );
}

// This tells WordPress to run the function above inside the Admin Dashboard
add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets' );
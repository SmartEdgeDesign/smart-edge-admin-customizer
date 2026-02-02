<?php
/**
 * Enqueues CSS and JS for the admin area.
 */
function seac_enqueue_admin_assets() {
    // 1. Load the core CSS that both roles share (if any)
    // For now, we will choose based on role.
    
    if ( current_user_can( 'manage_options' ) ) {
        // This is for the Administrator
        wp_enqueue_style(
            'seac-admin-main', 
            SEAC_PLUGIN_URL . 'assets/css/admin-main.css', 
            array(), 
            SEAC_VERSION
        );
    } else {
        // This is for Staff/Editors/Clients
        wp_enqueue_style(
            'seac-editor-admin', 
            SEAC_PLUGIN_URL . 'assets/css/editor-admin.css', 
            array(), 
            SEAC_VERSION
        );
    }
}
add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets' );
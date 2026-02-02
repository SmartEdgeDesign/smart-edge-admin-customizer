<?php
if ( ! defined( 'WPINC' ) ) { die; }

/**
 * Enqueue scripts and styles.
 * The $hook variable tells us exactly which page we are on.
 */
function seac_enqueue_admin_assets( $hook ) {

    // 1. GLOBAL STYLES (Loads on every admin page)
    // -------------------------------------------------------
    $main_css = SEAC_PLUGIN_PATH . 'assets/css/admin-main.css';
    $version  = file_exists( $main_css ) ? filemtime( $main_css ) : '1.0.0';

    wp_enqueue_style(
        'smart-edge-admin-main', 
        SEAC_PLUGIN_URL . 'assets/css/admin-main.css', 
        array(), 
        $version 
    );

    // Inject the Color Variable
    $options = get_option( 'seac_settings' );
    $accent_color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';
    $custom_css = ":root { --seac-accent-color: {$accent_color}; }";
    wp_add_inline_style( 'smart-edge-admin-main', $custom_css );


    // 2. SETTINGS PAGE ONLY (Loads only on your plugin page)
    // -------------------------------------------------------
    // The hook for a settings page is usually 'settings_page_{slug}'
    if ( 'settings_page_seac-settings' === $hook ) {
        
        $settings_css = SEAC_PLUGIN_PATH . 'assets/css/admin-settings.css';
        $set_ver      = file_exists( $settings_css ) ? filemtime( $settings_css ) : '1.0.0';

        wp_enqueue_style(
            'smart-edge-settings-css',
            SEAC_PLUGIN_URL . 'assets/css/admin-settings.css',
            array(),
            $set_ver
        );

        // This is where we will eventually enqueue the Media Uploader JS
    }
}
add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets' );
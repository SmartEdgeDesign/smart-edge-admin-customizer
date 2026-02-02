<?php
if ( ! defined( 'WPINC' ) ) { die; }

function seac_enqueue_admin_assets() {
    // 1. Get the dynamic version based on file modification time
    $css_path = SEAC_PLUGIN_PATH . 'assets/css/admin-main.css';
    $version  = file_exists( $css_path ) ? filemtime( $css_path ) : '1.0.0';

    // 2. Enqueue the main CSS file
    wp_enqueue_style(
        'smart-edge-admin-main', 
        SEAC_PLUGIN_URL . 'assets/css/admin-main.css', 
        array(), 
        $version 
    );

    // 3. Get the saved color setting
    $options = get_option( 'seac_settings' );
    $accent_color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';

    // 4. Create a CSS Variable and inject it
    $custom_css = ":root { --seac-accent-color: {$accent_color}; }";
    wp_add_inline_style( 'smart-edge-admin-main', $custom_css );
}
add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets' );
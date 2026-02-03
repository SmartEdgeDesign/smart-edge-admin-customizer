<?php
if ( ! defined( 'WPINC' ) ) { die; }

/**
 * We use priority 999 to ensure this loads AFTER Elementor and WordPress Core.
 */
function seac_enqueue_admin_assets( $hook ) {

    // 1. GLOBAL STYLES
    $main_css = SEAC_PLUGIN_PATH . 'assets/css/admin-main.css';
    $version  = file_exists( $main_css ) ? filemtime( $main_css ) : '1.0.0';

    wp_enqueue_style(
        'seac-admin-main', 
        SEAC_PLUGIN_URL . 'assets/css/admin-main.css', 
        array(), 
        $version 
    );

    // Inject Color Variable
    $options = get_option( 'seac_settings' );
    $accent_color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';
    $custom_css = ":root { --seac-accent-color: {$accent_color}; }";
    wp_add_inline_style( 'seac-admin-main', $custom_css );

    // 2. SETTINGS PAGE ONLY
    if ( 'settings_page_seac-settings' === $hook ) {
        $plugin_css = SEAC_PLUGIN_PATH . 'assets/css/plugin.css';
        $p_ver      = file_exists( $plugin_css ) ? filemtime( $plugin_css ) : '1.0.0';

        wp_enqueue_style(
            'seac-plugin-settings',
            SEAC_PLUGIN_URL . 'assets/css/plugin.css',
            array(),
            $p_ver
        );
    }
}
// NOTICE THE '999' BELOW - THIS IS THE FIX
add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets', 999 );


function seac_enqueue_editor_assets() {
    $editor_css = SEAC_PLUGIN_PATH . 'assets/css/editor-main.css';
    $e_ver      = file_exists( $editor_css ) ? filemtime( $editor_css ) : '1.0.0';

    wp_enqueue_style(
        'seac-editor-main',
        SEAC_PLUGIN_URL . 'assets/css/editor-main.css',
        array(),
        $e_ver
    );
}
add_action( 'enqueue_block_editor_assets', 'seac_enqueue_editor_assets' );
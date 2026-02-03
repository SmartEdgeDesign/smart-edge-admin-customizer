<?php
if ( ! defined( 'WPINC' ) ) { die; }

/**
 * 1. ADMIN ASSETS (Global & Settings Page)
 * Hooks into standard admin pages.
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

    // 2. INJECT VARIABLES (Accent Color & Logo)
    $options = get_option( 'seac_settings' );
    
    // Color
    $accent_color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';
    
    // Logo (Default to empty string if not set)
    $logo_url = isset( $options['logo_url'] ) && !empty($options['logo_url']) 
        ? "url('" . $options['logo_url'] . "')" 
        : 'none';

    // Build the dynamic CSS
    $custom_css = "
        :root { 
            --seac-accent-color: {$accent_color}; 
            --seac-logo-url: {$logo_url};
        }
    ";
    
    wp_add_inline_style( 'seac-admin-main', $custom_css );

    // 3. SETTINGS PAGE CSS
    if ( 'toplevel_page_seac-settings' === $hook ) {
        // ... (Keep your existing settings page css logic here if you have it)
    }
}
// Keep the priority 999!
add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets', 999 );


/**
 * 2. BLOCK EDITOR ASSETS (editor-main.css)
 * Hooks specifically into the Gutenberg Editor.
 */
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
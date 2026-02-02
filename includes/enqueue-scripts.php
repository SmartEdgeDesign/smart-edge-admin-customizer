<?php
if ( ! defined( 'WPINC' ) ) { die; }

function seac_enqueue_admin_assets() {

    $css_path = SEAC_PLUGIN_PATH . 'assets/css/admin-main.css';
    $version  = file_exists( $css_path ) ? filemtime( $css_path ) : '1.0.0';

    wp_enqueue_style(
        'smart-edge-admin-main', 
        SEAC_PLUGIN_URL . 'assets/css/admin-main.css', 
        array(), 
        $version 
    );
}
add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets' );
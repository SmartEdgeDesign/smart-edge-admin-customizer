<?php
/**
 * Plugin Name:       Smart Edge Admin Customizer
 * Description:       A stable base for admin customization.
 * Version:           1.0.1
 * Author:            Ben Moreton
 * Author URI:        https://smartedgedesign.com
 * Text Domain:       smart-edge-admin-customizer
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants for easy access to paths and URLs.
define( 'SEAC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SEAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// This forces the browser to download the NEWEST CSS every time you refresh.
define( 'SEAC_VERSION', date('YmdHis') ); 

// Include the necessary files.
require_once SEAC_PLUGIN_PATH . 'includes/enqueue-scripts.php';
require_once SEAC_PLUGIN_PATH . 'includes/settings-page.php';
require_once SEAC_PLUGIN_PATH . 'includes/menu-management.php';
require_once SEAC_PLUGIN_PATH . 'includes/utility-functions.php';


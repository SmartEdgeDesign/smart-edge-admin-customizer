<?php
/**
 * Plugin Name:       Smart Edge Admin Customizer
 * Description:       A plugin to completely restyle the WordPress admin area, manage menu items by role, and apply custom branding.
 * Version:           1.0.1
 * Author:            Ben Moreton
 * Author URI:        https://smartedgedesign.com
 * Text Domain:       se-admin-customizer
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define plugin constants for easy access to paths and URLs.
define( 'SEAC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SEAC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SEAC_VERSION', '1.0.0' );

// Include the necessary files.
require_once SEAC_PLUGIN_PATH . 'includes/enqueue-scripts.php';
require_once SEAC_PLUGIN_PATH . 'includes/settings-page.php';
require_once SEAC_PLUGIN_PATH . 'includes/menu-management.php';
require_once SEAC_PLUGIN_PATH . 'includes/utility-functions.php'; // <-- ADDED THIS LINE

// =========================================================================
// INITIALIZE UPDATES
// =========================================================================
require_once SEAC_PLUGIN_PATH . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/SmartEdgeDesign/smart-edge-admin-customizer/', 
    __FILE__, 
    'se-admin-customizer'
);

// Set the branch to check for updates.
$myUpdateChecker->setBranch('main');

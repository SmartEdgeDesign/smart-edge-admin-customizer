<?php
/**
 * Plugin Name:       Smart Edge Admin Customizer
 * Description:       A plugin to completely restyle the WordPress admin area, manage menu items by role, and apply custom branding.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
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

<?php
/**
 * Plugin Name:       Smart Edge Admin Customizer
 * Description:       A plugin to completely restyle the WordPress admin area, manage menu items by role, and apply custom branding.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://yourwebsite.com
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


    // =========================================================================
    // INITIALIZE SELF-HOSTED UPDATES
    // =========================================================================
    require SEAC_PLUGIN_PATH . 'plugin-update-checker/plugin-update-checker.php';

    use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

    $myUpdateChecker = PucFactory::buildUpdateChecker(
        'https://github.com/YourGitHubUsername/your-private-repo-name/', // <-- IMPORTANT: Change this
        __FILE__, // The main plugin file
        'se-admin-customizer' // A unique slug for your plugin
    );

    // Set the branch to check for updates. 'main' or 'master' is typical.
    $myUpdateChecker->setBranch('main'); // <-- IMPORTANT: Change if your branch is different

    // (Optional) If your repository is private, you'll need a GitHub Personal Access Token.
    // $myUpdateChecker->setAuthentication('your_github_personal_access_token'); // <-- IMPORTANT: Add your token here
    

?>


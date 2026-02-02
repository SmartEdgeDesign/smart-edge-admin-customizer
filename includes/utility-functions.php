<?php
/**
 * Plugin Utility Functions
 *
 * General-purpose functions moved from the theme.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Change user role display names.
 */
function seac_change_role_name() {
	global $wp_roles;
	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}
	$wp_roles->roles['subscriber']['name'] = 'Client';
	$wp_roles->role_names['subscriber'] = 'Client';
	$wp_roles->roles['editor']['name'] = 'Staff';
	$wp_roles->role_names['editor'] = 'Staff';
}
add_action('init', 'seac_change_role_name');

/**
 * Remove user roles on plugin activation.
 * NOTE: This will run once when the plugin is activated.
 */
function seac_remove_roles_on_activation() {
	remove_role( 'author' );
	remove_role( 'contributor' );
	remove_role( 'wpseo_editor' );
	remove_role( 'wpseo_manager' );
}
register_activation_hook( SEAC_PLUGIN_PATH . 'smart-edge-admin-customizer.php', 'seac_remove_roles_on_activation' );


/**
 * Disable the editor's fullscreen mode by default.
 */
function seac_disable_editor_fullscreen_by_default() {
	$script = "window.onload = function() { const isFullscreenMode = wp.data.select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ); if ( isFullscreenMode ) { wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' ); } }";
	wp_add_inline_script( 'wp-blocks', $script );
}
add_action( 'enqueue_block_editor_assets', 'seac_disable_editor_fullscreen_by_default' );

/**
 * Customize the login page logo URL.
 */
function seac_login_logo_url() {
	return home_url();
}
add_filter( 'login_headerurl', 'seac_login_logo_url' );

/**
 * Customize the login page logo URL title attribute.
 */
function seac_login_logo_url_title() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertitle', 'seac_login_logo_url_title' );

/**
 * Send an email notification to the admin when any user logs in.
 */
function seac_send_email_on_login($user_login, $user) {
	$website_name = get_bloginfo('name');
	$from_name    = 'Login - ' . $website_name;
	$to           = 'no-reply@smartedgedesign.com';
	$subject      = $user_login . ' logged in';
	$message      = 'The user "' . $user_login . '" has just logged in to ' . $website_name . '.';

	$headers = [
		'Content-Type: text/html; charset=UTF-8',
		'From: ' . $from_name . ' <' . get_option('admin_email') . '>',
	];

	wp_mail($to, $subject, $message, $headers);
}
add_action('wp_login', 'seac_send_email_on_login', 10, 2);


/**
 * Conditionally hides the "Screen Options" tab based on the current user's role.
 */
function seac_toggle_screen_options() {
    // Get saved settings
    $options = get_option( 'seac_settings' );
    $roles_to_hide = isset( $options['hide_screen_options'] ) ? (array) $options['hide_screen_options'] : [];

    // If no roles are selected to be hidden, do nothing.
    if ( empty( $roles_to_hide ) || current_user_can('manage_options') ) {
        return;
    }

    // Get current user's roles
    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;

    // Check if the user has any of the roles that should be hidden
    $should_hide = false;
    foreach ( $user_roles as $role ) {
        if ( in_array( $role, $roles_to_hide ) ) {
            $should_hide = true;
            break;
        }
    }

    if ( $should_hide ) {
        add_filter( 'screen_options_show_screen', '__return_false' );
    }
}
add_action( 'admin_head', 'seac_toggle_screen_options' );


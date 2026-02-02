<?php
if ( ! defined( 'WPINC' ) ) { die; }

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
 * Fixed the path to point correctly to the main plugin file in the root.
 */
function seac_remove_roles_on_activation() {
    remove_role( 'author' );
    remove_role( 'contributor' );
    remove_role( 'wpseo_editor' );
    remove_role( 'wpseo_manager' );
}
register_activation_hook( dirname(__DIR__) . '/smart-edge-admin-customizer.php', 'seac_remove_roles_on_activation' );

/**
 * Disable the editor's fullscreen mode by default.
 */
function seac_disable_editor_fullscreen_by_default() {
    $script = "window.onload = function() { if ( wp.data.select( 'core/edit-post' ) ) { const isFullscreenMode = wp.data.select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ); if ( isFullscreenMode ) { wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' ); } } }";
    wp_add_inline_script( 'wp-blocks', $script );
}
add_action( 'enqueue_block_editor_assets', 'seac_disable_editor_fullscreen_by_default' );

/**
 * Customize the login page logo URL.
 */
add_filter( 'login_headerurl', function() { return home_url(); } );
add_filter( 'login_headertitle', function() { return get_bloginfo( 'name' ); } );

/**
 * Conditionally hides the "Screen Options" tab based on the current user's role.
 */
function seac_toggle_screen_options() {
    $options = get_option( 'seac_settings' );
    $roles_to_hide = isset( $options['hide_screen_options'] ) ? (array) $options['hide_screen_options'] : [];

    if ( empty( $roles_to_hide ) || current_user_can('manage_options') ) {
        return;
    }

    $user = wp_get_current_user();
    foreach ( (array) $user->roles as $role ) {
        if ( in_array( $role, $roles_to_hide ) ) {
            add_filter( 'screen_options_show_screen', '__return_false' );
            break;
        }
    }
}
add_action( 'admin_head', 'seac_toggle_screen_options' );
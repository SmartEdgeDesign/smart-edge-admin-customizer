<?php
/**
 * Plugin Utility Functions
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
    // Only run if the roles exist to prevent errors
    if ( isset($wp_roles->roles['subscriber']) ) {
        $wp_roles->roles['subscriber']['name'] = 'Client';
        $wp_roles->role_names['subscriber'] = 'Client';
    }
    if ( isset($wp_roles->roles['editor']) ) {
        $wp_roles->roles['editor']['name'] = 'Staff';
        $wp_roles->role_names['editor'] = 'Staff';
    }
}
add_action('init', 'seac_change_role_name');

/**
 * Disable the editor's fullscreen mode by default.
 */
function seac_disable_editor_fullscreen_by_default() {
    $script = "window.onload = function() { 
        if ( typeof wp !== 'undefined' && wp.data && wp.data.select( 'core/edit-post' ) ) {
            const isFullscreenMode = wp.data.select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ); 
            if ( isFullscreenMode ) { 
                wp.data.dispatch( 'core/edit-post' ).toggleFeature( 'fullscreenMode' ); 
            } 
        } 
    }";
    wp_add_inline_script( 'wp-blocks', $script );
}
add_action( 'enqueue_block_editor_assets', 'seac_disable_editor_fullscreen_by_default' );

// THE ACTIVATION HOOKS HAVE BEEN REMOVED TO PREVENT SYNC ERRORS
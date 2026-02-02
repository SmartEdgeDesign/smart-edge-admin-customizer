<?php
/**
 * Modifies the admin menu based on saved settings.
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

function seac_modify_admin_menu() {
    // Skip for Admins so you don't accidentally lock yourself out while developing
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }

    global $menu;
    $options = get_option( 'seac_settings' );
    
    // If no settings exist yet, don't do anything
    if ( ! $options ) {
        return;
    }

    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;
    $role_map = ['editor' => 'Staff', 'subscriber' => 'Client'];
    
    $current_role_key = '';
    foreach ( $user_roles as $role ) {
        if ( array_key_exists( $role, $role_map ) ) {
            $current_role_key = $role;
            break;
        }
    }

    if ( empty( $current_role_key ) ) {
        return;
    }

    foreach ( $menu as $key => $item ) {
        $menu_slug = $item[2];

        // Hide menu item if it's not checked for the user's role
        if ( empty( $options['visibility'][$menu_slug][$current_role_key] ) ) {
            remove_menu_page( $menu_slug );
        }
    }
}

// Run late to ensure all menu items are registered
add_action( 'admin_menu', 'seac_modify_admin_menu', 999 );
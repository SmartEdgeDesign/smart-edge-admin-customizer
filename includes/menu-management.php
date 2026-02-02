<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Modifies the admin menu based on saved settings.
 */
function seac_modify_admin_menu() {
	// Admins can see everything, so we don't need to do anything for them.
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	global $menu;
	$options = get_option( 'seac_settings' );
	$user = wp_get_current_user();
	$user_roles = (array) $user->roles;

    // A map of your user roles to what you call them.
    $role_map = ['editor' => 'Staff', 'subscriber' => 'Client'];
    $current_role_key = '';
    foreach($user_roles as $role) {
        if(array_key_exists($role, $role_map)) {
            $current_role_key = $role;
            break;
        }
    }

    if(empty($current_role_key)) return; // Not a role we manage

	foreach ( $menu as $key => $item ) {
		$menu_slug = $item[2];

		// Hide menu item if it's not checked for the user's role.
		if ( empty( $options['visibility'][$menu_slug][$current_role_key] ) ) {
			remove_menu_page( $menu_slug );
		}

        // Add separator classes
        if ( !empty( $options['separators'][$menu_slug]['top'] ) ) {
            $menu[$key][4] .= ' seac-separator-top';
        }
        if ( !empty( $options['separators'][$menu_slug]['bottom'] ) ) {
            $menu[$key][4] .= ' seac-separator-bottom';
        }
	}
}
// Run with a high priority (late) to affect all menu items.
add_action( 'admin_menu', 'seac_modify_admin_menu', 999 );
      
/**
 * Test: Turn the Admin Menu Black
 */
add_action('admin_head', function() {
    echo '<style>
        #adminmenu, #adminmenu .wp-submenu, #adminmenuback, #adminmenuwrap { 
            background-color: #000000 !important; 
        }
    </style>';
});

?>

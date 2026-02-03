<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Menu_Manager {

    public function __construct() {
        // Run late (9999) so we override plugins that add menus normally
        add_action( 'admin_menu', array( $this, 'apply_custom_menu' ), 9999 );
    }

    public function apply_custom_menu() {
        global $menu;

        // 1. Get Current User Role
        $user = wp_get_current_user();
        if ( empty( $user->roles ) ) return;
        
        // Just grab the first role for simplicity (usually sufficient)
        $role = $user->roles[0]; 

        // 2. Get Settings
        $saved_settings = get_option( 'seac_menu_settings', array() );

        // If no settings for this role, do nothing
        if ( ! isset( $saved_settings[$role] ) || empty( $saved_settings[$role] ) ) {
            return;
        }

        $role_config = $saved_settings[$role];
        $new_menu = array();
        
        // Create a lookup map of the ORIGINAL menu for fast access
        // Key = slug (e.g., 'index.php')
        $original_menu_map = array();
        foreach ( $menu as $index => $item ) {
            if ( ! empty( $item[2] ) ) {
                $original_menu_map[ $item[2] ] = $item;
            }
        }

        // 3. Rebuild Menu based on Config
        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            // Skip if hidden
            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) {
                continue;
            }

            // Check if this item actually exists in WordPress currently
            // (Plugins might be deactivated, etc.)
            if ( isset( $original_menu_map[$slug] ) ) {
                $menu_item = $original_menu_map[$slug];

                // Apply Rename
                if ( ! empty( $config_item['rename'] ) ) {
                    $menu_item[0] = $config_item['rename'];
                }

                // Apply Icon
                if ( ! empty( $config_item['icon'] ) ) {
                    $menu_item[6] = $config_item['icon'];
                }

                // Add to new menu array (using numeric keys to keep WP happy)
                $new_menu[ $menu_order_index ] = $menu_item;
                $menu_order_index++;
                
                // Remove from map so we know it's used
                unset( $original_menu_map[$slug] );
            }
        }

        // 4. Handle "Orphans"
        // If a new plugin was installed that isn't in our saved config yet,
        // we should append it to the end so it's visible.
        if ( ! empty( $original_menu_map ) ) {
            foreach ( $original_menu_map as $orphan ) {
                // Skip separators
                if ( strpos( $orphan[4], 'wp-menu-separator' ) !== false ) continue;
                
                $new_menu[ $menu_order_index ] = $orphan;
                $menu_order_index++;
            }
        }

        // 5. Replace Global Menu
        $menu = $new_menu;
    }
}

new SEAC_Menu_Manager();
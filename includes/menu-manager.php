<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Menu_Manager {

    public function __construct() {
        // PRIORITY FIX: Run at PHP_INT_MAX on admin_init.
        // This ensures we run AFTER every other plugin (including Bricks) 
        // has finished setting up their menu items.
        add_action( 'admin_init', array( $this, 'manage_menu_ordering' ), PHP_INT_MAX );
        add_action( 'admin_init', array( $this, 'block_hidden_pages' ), 10 );
    }

    public function manage_menu_ordering() {
        if ( ! is_admin() ) return;

        global $menu;

        // 1. CAPTURE FINAL ORIGINAL MENU
        // Because we are at PHP_INT_MAX, this snapshot includes Bricks in its correct spot.
        if ( ! isset( $GLOBALS['seac_original_menu'] ) ) {
            $GLOBALS['seac_original_menu'] = $menu;
        }

        $role = $this->get_current_role();
        if ( ! $role ) return;

        $saved_settings = get_option( 'seac_menu_settings', array() );
        
        // If no settings for this role, do nothing (leave WP default)
        if ( ! isset( $saved_settings[$role] ) || empty( $saved_settings[$role] ) ) {
            return;
        }

        $role_config = $saved_settings[$role];
        $new_menu = array();
        
        $source_menu = $GLOBALS['seac_original_menu'];
        
        // Map original menu
        $original_menu_map = array();
        foreach ( $source_menu as $index => $item ) {
            $key = isset($item[2]) ? $item[2] : "index_$index";
            $original_menu_map[$key] = $item;
        }

        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) continue; 

            // Handle Custom Separators
            if ( isset($config_item['type']) && $config_item['type'] === 'separator' ) {
                $new_menu[ $menu_order_index ] = array( 
                    '', 
                    'read', 
                    "separator_{$menu_order_index}", 
                    '', 
                    'wp-menu-separator seac-custom-divider' // Custom Class ensures visibility
                );
                $menu_order_index++;
                continue;
            }

            // Standard Items
            if ( isset( $original_menu_map[$slug] ) ) {
                $menu_item = $original_menu_map[$slug];

                if ( ! empty( $config_item['rename'] ) ) $menu_item[0] = $config_item['rename'];
                if ( ! empty( $config_item['icon'] ) ) $menu_item[6] = $config_item['icon'];

                $new_menu[ $menu_order_index ] = $menu_item;
                $menu_order_index++;
                unset( $original_menu_map[$slug] );
            }
        }

        // 4. APPEND ORPHANS
        // If an item exists in WP but not in your save file (like "Links" if you just installed it),
        // we add it to the bottom so it isn't lost.
        if ( ! empty( $original_menu_map ) ) {
            foreach ( $original_menu_map as $orphan ) {
                
                // SKIP DEFAULT WORDPRESS SEPARATORS
                // This prevents the "3 Ghost Dividers" at the bottom.
                if ( isset($orphan[4]) && strpos( $orphan[4], 'wp-menu-separator' ) !== false ) {
                    continue;
                }

                $new_menu[ $menu_order_index ] = $orphan;
                $menu_order_index++;
            }
        }

        $menu = $new_menu;
    }

    public function block_hidden_pages() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
        
        $role = $this->get_current_role();
        if ( ! $role ) return;
        if ( current_user_can( 'administrator' ) ) return;

        $saved_settings = get_option( 'seac_menu_settings', array() );
        if ( empty( $saved_settings[$role] ) ) return;

        $blocked_slugs = array();
        foreach ( $saved_settings[$role] as $item ) {
            if ( isset($item['hidden']) && $item['hidden'] == true ) {
                $blocked_slugs[] = $item['slug'];
            }
        }

        if ( empty( $blocked_slugs ) ) return;

        global $pagenow;
        $current_slug = $pagenow;
        if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) ) {
            $current_slug = $_GET['page'];
        }

        if ( in_array( $current_slug, $blocked_slugs ) ) {
            wp_die( '<h1>Access Denied</h1><p>You do not have permission to view this page.</p>', 403 );
        }
    }

    private function get_current_role() {
        $user = wp_get_current_user();
        if ( empty( $user->roles ) ) return false;
        return $user->roles[0];
    }
}

new SEAC_Menu_Manager();
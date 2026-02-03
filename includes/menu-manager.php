<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Menu_Manager {

    public function __construct() {
        // Run late to override other plugins, but we'll capture the original state first
        add_action( 'admin_menu', array( $this, 'apply_custom_menu' ), 9999 );
        add_action( 'admin_init', array( $this, 'block_hidden_pages' ) );
    }

    public function apply_custom_menu() {
        global $menu;

        // 1. CAPTURE THE ORIGINAL MENU (The Fix)
        // We save the state of the menu exactly as WordPress + Plugins built it, 
        // BEFORE we apply our own custom reordering.
        $GLOBALS['seac_original_menu'] = $menu;

        $role = $this->get_current_role();
        if ( ! $role ) return;

        $saved_settings = get_option( 'seac_menu_settings', array() );
        if ( ! isset( $saved_settings[$role] ) || empty( $saved_settings[$role] ) ) return;

        $role_config = $saved_settings[$role];
        $new_menu = array();
        
        // Map original menu for lookup
        $original_menu_map = array();
        foreach ( $menu as $index => $item ) {
            $key = isset($item[2]) ? $item[2] : "index_$index";
            $original_menu_map[$key] = $item;
        }

        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            // Skip hidden
            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) {
                continue; 
            }

            // SEPARATORS (Pass them through if they exist in config)
            if ( isset($config_item['type']) && $config_item['type'] === 'separator' ) {
                // We create a generic separator structure for WP
                $new_menu[ $menu_order_index ] = array( '', 'read', "separator_{$menu_order_index}", '', 'wp-menu-separator' );
                $menu_order_index++;
                continue;
            }

            // REBUILD ITEM
            if ( isset( $original_menu_map[$slug] ) ) {
                $menu_item = $original_menu_map[$slug];

                if ( ! empty( $config_item['rename'] ) ) {
                    $menu_item[0] = $config_item['rename'];
                }

                if ( ! empty( $config_item['icon'] ) ) {
                    $menu_item[6] = $config_item['icon'];
                }

                $new_menu[ $menu_order_index ] = $menu_item;
                $menu_order_index++;
                unset( $original_menu_map[$slug] );
            }
        }

        // APPEND ORPHANS
        if ( ! empty( $original_menu_map ) ) {
            foreach ( $original_menu_map as $orphan ) {
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
        
        // Admins can always access everything
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
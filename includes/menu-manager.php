<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Menu_Manager {

    public function __construct() {
        // 1. CAPTURE: Run at the absolute last second (Max Integer - 1)
        // This ensures we catch plugins like Bricks that load late.
        add_action( 'admin_menu', array( $this, 'capture_original_menu' ), PHP_INT_MAX - 1 ); 
        
        // 2. MODIFY: Run at the absolute Max Integer
        // This runs immediately after the capture to apply your custom order.
        add_action( 'admin_menu', array( $this, 'apply_custom_menu' ), PHP_INT_MAX );
        
        // 3. SECURITY: Block access to hidden pages
        add_action( 'admin_init', array( $this, 'block_hidden_pages' ) );
    }

    public function capture_original_menu() {
        global $menu;
        // Capture the menu state exactly as it is right before we mess with it.
        // We do NOT check isset() here anymore, because we want the latest version 
        // if this hook fires multiple times for some reason.
        $GLOBALS['seac_original_menu'] = $menu;
    }

    public function apply_custom_menu() {
        global $menu;

        $role = $this->get_current_role();
        if ( ! $role ) return;

        $saved_settings = get_option( 'seac_menu_settings', array() );
        
        // If no settings for this role, we stop.
        if ( ! isset( $saved_settings[$role] ) || empty( $saved_settings[$role] ) ) return;

        $role_config = $saved_settings[$role];
        $new_menu = array();
        
        // Use the captured menu as our 'Source of Truth'
        // If capture didn't run (rare), fall back to current $menu
        $source_menu = isset($GLOBALS['seac_original_menu']) ? $GLOBALS['seac_original_menu'] : $menu;
        
        // Create a map for easy lookup
        $original_menu_map = array();
        foreach ( $source_menu as $index => $item ) {
            $key = isset($item[2]) ? $item[2] : "index_$index";
            $original_menu_map[$key] = $item;
        }

        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) continue; 

            // Handle Separators
            if ( isset($config_item['type']) && $config_item['type'] === 'separator' ) {
                $new_menu[ $menu_order_index ] = array( '', 'read', "separator_{$menu_order_index}", '', 'wp-menu-separator' );
                $menu_order_index++;
                continue;
            }

            // Handle Standard Items
            if ( isset( $original_menu_map[$slug] ) ) {
                $menu_item = $original_menu_map[$slug];

                if ( ! empty( $config_item['rename'] ) ) $menu_item[0] = $config_item['rename'];
                if ( ! empty( $config_item['icon'] ) ) $menu_item[6] = $config_item['icon'];

                $new_menu[ $menu_order_index ] = $menu_item;
                $menu_order_index++;
                unset( $original_menu_map[$slug] );
            }
        }

        // APPEND ORPHANS (Items that exist in source but not in config)
        if ( ! empty( $original_menu_map ) ) {
            foreach ( $original_menu_map as $orphan ) {
                $new_menu[ $menu_order_index ] = $orphan;
                $menu_order_index++;
            }
        }

        // Apply to Global
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
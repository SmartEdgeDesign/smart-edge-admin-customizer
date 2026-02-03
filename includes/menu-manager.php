<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Menu_Manager {

    public function __construct() {
        // 1. Modify the Visual Menu
        add_action( 'admin_menu', array( $this, 'apply_custom_menu' ), 9999 );
        
        // 2. The Gatekeeper: Block Direct URL Access
        add_action( 'admin_init', array( $this, 'block_hidden_pages' ) );
    }

    public function apply_custom_menu() {
        global $menu;

        $role = $this->get_current_role();
        if ( ! $role ) return;

        $saved_settings = get_option( 'seac_menu_settings', array() );
        if ( ! isset( $saved_settings[$role] ) || empty( $saved_settings[$role] ) ) return;

        $role_config = $saved_settings[$role];
        $new_menu = array();
        
        // Map original menu for lookup
        $original_menu_map = array();
        foreach ( $menu as $index => $item ) {
            // Use slug as key if available, otherwise index
            $key = isset($item[2]) ? $item[2] : "index_$index";
            $original_menu_map[$key] = $item;
        }

        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            // SKIP HIDDEN ITEMS (Removes from visual menu)
            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) {
                continue; 
            }

            // REBUILD ITEM
            if ( isset( $original_menu_map[$slug] ) ) {
                $menu_item = $original_menu_map[$slug];

                // Apply Rename
                if ( ! empty( $config_item['rename'] ) ) {
                    $menu_item[0] = $config_item['rename'];
                }

                // Apply Icon (Only if not a separator)
                if ( ! empty( $config_item['icon'] ) && $config_item['type'] !== 'separator' ) {
                    $menu_item[6] = $config_item['icon'];
                }

                $new_menu[ $menu_order_index ] = $menu_item;
                $menu_order_index++;
                unset( $original_menu_map[$slug] );
            }
        }

        // APPEND ORPHANS (New plugins or items not in config)
        if ( ! empty( $original_menu_map ) ) {
            foreach ( $original_menu_map as $orphan ) {
                $new_menu[ $menu_order_index ] = $orphan;
                $menu_order_index++;
            }
        }

        $menu = $new_menu;
    }

    // --- SECURITY FUNCTION ---
    public function block_hidden_pages() {
        // Allow AJAX
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;

        $role = $this->get_current_role();
        if ( ! $role ) return;

        // Admins can always access everything (safety net)
        if ( current_user_can( 'administrator' ) ) return;

        $saved_settings = get_option( 'seac_menu_settings', array() );
        if ( empty( $saved_settings[$role] ) ) return;

        // 1. Build list of blocked slugs
        $blocked_slugs = array();
        foreach ( $saved_settings[$role] as $item ) {
            if ( isset($item['hidden']) && $item['hidden'] == true ) {
                $blocked_slugs[] = $item['slug'];
            }
        }

        if ( empty( $blocked_slugs ) ) return;

        // 2. Determine Current Page Slug
        global $pagenow;
        $current_slug = $pagenow;

        // If it's a plugin page like 'admin.php?page=my-plugin', the slug is 'my-plugin'
        if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) ) {
            $current_slug = $_GET['page'];
        }

        // 3. Check and Block
        // We check exact match OR if the blocked slug is inside the URL
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
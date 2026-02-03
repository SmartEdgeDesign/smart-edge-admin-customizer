<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Menu_Manager {

    public function __construct() {
        // MOVED TO ADMIN_INIT:
        // We run on 'admin_init' (Priority 100) instead of 'admin_menu'.
        // 'admin_init' fires AFTER 'admin_menu' is completely finished.
        // This guarantees that Bricks, Elementor, and every other plugin has 
        // already added their items to the global $menu array before we touch it.
        add_action( 'admin_init', array( $this, 'manage_menu_ordering' ), 100 );
        
        // Security check can run on the same hook, just earlier priority (10)
        add_action( 'admin_init', array( $this, 'block_hidden_pages' ), 10 );
    }

    public function manage_menu_ordering() {
        // Only run in admin context
        if ( ! is_admin() ) return;

        global $menu;

        // 1. CAPTURE THE FINAL ORIGINAL MENU
        // Since we are in admin_init, this $menu includes EVERYTHING.
        if ( ! isset( $GLOBALS['seac_original_menu'] ) ) {
            $GLOBALS['seac_original_menu'] = $menu;
        }

        // 2. CHECK ROLE
        $role = $this->get_current_role();
        if ( ! $role ) return;

        $options = get_option( 'seac_settings' );
        $saved_settings = isset($options['menu_config']) ? $options['menu_config'] : array();
        
        // If no settings, stop. Leave default menu alone.
        if ( ! isset( $saved_settings[$role] ) ) {
            return;
        }

        // 3. APPLY CUSTOM ORDER
        $role_config = $saved_settings[$role];
        $new_menu = array();
        
        // Use our perfect snapshot
        $source_menu = $GLOBALS['seac_original_menu'];
        
        // Map it
        $original_menu_map = array();
        foreach ( $source_menu as $index => $item ) {
            // This logic must be IDENTICAL to the slug generation in `includes/settings-page.php`.
            $key = (isset($item[2]) && $item[2] !== '') ? $item[2] : 'seac_item_index_' . $index;
            $original_menu_map[$key] = $item;
        }

        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) {
                // This is a hidden item. We must remove it from the original map
                // so it doesn't get re-added at the end with the "orphans".
                unset( $original_menu_map[$slug] );
                continue;
            }

            // Handle newly added or existing separators from config
            if ( isset($config_item['type']) && $config_item['type'] === 'separator' ) {
                // Use the unique slug from the config. This allows for persistent, user-added dividers.
                $new_menu[ $menu_order_index ] = array( '', 'read', $slug, '', 'wp-menu-separator' );
                $menu_order_index++;
                // If this separator was part of the original menu, remove it from the map
                // so it doesn't get added again with the orphans.
                if ( isset($original_menu_map[$slug]) ) {
                    unset( $original_menu_map[$slug] );
                }
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
        // If Bricks was in the snapshot but not in your saved config, it adds here.
        // BUT since we captured it correctly this time, if you click "Reset", 
        // it will go back to its correct spot in step 3 next time you save.
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

        $options = get_option( 'seac_settings' );
        $saved_settings = isset($options['menu_config']) ? $options['menu_config'] : array();
        if ( !isset($saved_settings[$role]) || empty( $saved_settings[$role] ) ) return;

        $blocked_slugs = array();
        foreach ( $saved_settings[$role] as $item ) {
            if ( isset($item['hidden']) && $item['hidden'] == true ) {
                $blocked_slugs[] = $item['slug'];
            }
        }

        if ( empty( $blocked_slugs ) ) return;

        global $pagenow;        
        $current_slug = $pagenow; // Default to the filename, e.g., "tools.php"

        // Case 1: Standard submenu pages, e.g., /wp-admin/admin.php?page=some-slug
        if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) ) {
            $current_slug = $_GET['page'];
        } 
        // Case 2: Core pages differentiated by query parameters. This is the key fix.
        else {
            if ( in_array( $pagenow, array( 'edit.php', 'post-new.php' ) ) && isset( $_GET['post_type'] ) ) {
                $current_slug = $pagenow . '?post_type=' . $_GET['post_type'];
            } else if ( $pagenow === 'edit-tags.php' && isset( $_GET['taxonomy'] ) ) {
                $current_slug = $pagenow . '?taxonomy=' . $_GET['taxonomy'];
            }
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
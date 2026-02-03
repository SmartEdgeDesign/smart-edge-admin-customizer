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

        $saved_settings = get_option( 'seac_menu_settings', array() );
        
        // If no settings, stop. Leave default menu alone.
        if ( ! isset( $saved_settings[$role] ) || empty( $saved_settings[$role] ) || ! is_array( $saved_settings[$role] ) ) {
            return;
        }

        // 3. APPLY CUSTOM ORDER
        $role_config = $saved_settings[$role];
        $new_menu = array();
        
        // Use our perfect snapshot
        $source_menu = $GLOBALS['seac_original_menu'];
        
        // 1. Build Lookup Maps (Slug & Name)
        // This allows us to find items even if their ID changes (e.g. users.php -> profile.php)
        $slug_to_index = array();
        $name_to_index = array();

        foreach ( $source_menu as $index => $item ) {
            // Standardize slug generation
            $raw_slug = (isset($item[2]) && $item[2] !== '') ? $item[2] : 'seac_item_index_' . $index;
            $slug_to_index[$raw_slug] = $index;

            // Clean Name for fallback matching (e.g. "Posts" matches "Posts")
            $name = isset($item[0]) ? $item[0] : '';
            $name = preg_replace( '/<span.*<\/span>/', '', $name ); // Remove update bubbles
            $name = strip_tags( $name ); 
            $name = trim( $name );
            if ( ! empty( $name ) ) {
                $name_to_index[ $name ] = $index;
            }
        }

        $used_indices = array(); // Track which items we have handled
        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            // FIND THE ITEM
            $found_index = null;

            // A. Try Exact Slug Match
            if ( isset( $slug_to_index[$slug] ) ) {
                $found_index = $slug_to_index[$slug];
            } 
            // B. Try Name Match (Fallback for when slugs differ between roles)
            else if ( isset( $config_item['original_name'] ) && isset( $name_to_index[ $config_item['original_name'] ] ) ) {
                $found_index = $name_to_index[ $config_item['original_name'] ];
            }
            // C. Special Case: Profile vs Users
            else if ( $slug === 'users.php' && isset( $slug_to_index['profile.php'] ) ) {
                $found_index = $slug_to_index['profile.php'];
            }

            // IF HIDDEN: Mark as used so it doesn't appear as orphan, then skip.
            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) {
                if ( $found_index !== null ) $used_indices[$found_index] = true;
                continue; 
            }

            // Handle newly added or existing separators from config
            if ( isset($config_item['type']) && $config_item['type'] === 'separator' ) {
                // Use the unique slug from the config. This allows for persistent, user-added dividers.
                $new_menu[ $menu_order_index ] = array( '', 'read', $slug, '', 'wp-menu-separator' );
                $menu_order_index++;
                
                // If this separator existed in source, mark it used
                if ( $found_index !== null ) {
                    $used_indices[$found_index] = true;
                }
                continue;
            }

            // Standard Items
            if ( $found_index !== null ) {
                $menu_item = $source_menu[$found_index];

                if ( ! empty( $config_item['rename'] ) ) $menu_item[0] = $config_item['rename'];
                if ( ! empty( $config_item['icon'] ) ) $menu_item[6] = $config_item['icon'];

                $new_menu[ $menu_order_index ] = $menu_item;
                $menu_order_index++;
                $used_indices[$found_index] = true;
            }
        }

        // 4. APPEND ORPHANS
        // Add any items from the source that weren't in the config or handled above.
        foreach ( $source_menu as $index => $item ) {
            if ( ! isset( $used_indices[$index] ) ) {
                $new_menu[ $menu_order_index ] = $item;
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
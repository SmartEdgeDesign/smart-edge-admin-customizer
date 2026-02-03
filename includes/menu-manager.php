<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Menu_Manager {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'manage_menu_ordering' ), 100 );
        add_action( 'admin_init', array( $this, 'block_hidden_pages' ), 10 );
    }

    public function manage_menu_ordering() {
        if ( ! is_admin() ) return;

        global $menu;

        // 1. CAPTURE ORIGINAL MENU
        if ( ! isset( $GLOBALS['seac_original_menu'] ) ) {
            $GLOBALS['seac_original_menu'] = $menu;
        }

        $role = $this->get_current_role();
        if ( ! $role ) return;

        // FIX: Read from the correct standalone option 'seac_menu_settings'
        $saved_settings = get_option( 'seac_menu_settings', array() );
        
        // If no settings for this role, stop. This allows the default WP menu to show.
        if ( ! isset( $saved_settings[$role] ) || empty( $saved_settings[$role] ) ) {
            return;
        }

        $role_config = $saved_settings[$role];
        $new_menu = array();
        $source_menu = $GLOBALS['seac_original_menu'];
        
        // Map original menu by Slug and Name for robust matching
        $slug_to_index = array();
        $name_to_index = array();

        foreach ( $source_menu as $index => $item ) {
            $raw_slug = (isset($item[2]) && $item[2] !== '') ? $item[2] : 'index_' . $index;
            $slug_to_index[$raw_slug] = $index;
            $slug_to_index[html_entity_decode($raw_slug)] = $index; // Handle &amp; etc

            // Clean Name Mapping
            $name = isset($item[0]) ? strip_tags( $item[0] ) : '';
            $name = trim( preg_replace( '/<span.*<\/span>/', '', $name ) );
            if ( ! empty( $name ) ) $name_to_index[ $name ] = $index;
        }

        $used_indices = array();
        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) {
                if ( isset($slug_to_index[$slug]) ) $used_indices[ $slug_to_index[$slug] ] = true;
                continue;
            }

            // Custom Dividers
            if ( isset($config_item['type']) && $config_item['type'] === 'separator' ) {
                $new_menu[ $menu_order_index ] = array( '', 'read', "separator_{$menu_order_index}", '', 'wp-menu-separator seac-custom-divider' );
                $menu_order_index++;
                continue;
            }

            // Find Item Index
            $found_index = null;
            if ( isset( $slug_to_index[$slug] ) ) {
                $found_index = $slug_to_index[$slug];
            } 
            // Fallback: Profile/Users mismatch
            else if ( $slug === 'users.php' && isset( $slug_to_index['profile.php'] ) ) {
                $found_index = $slug_to_index['profile.php'];
            }
            // Fallback: Name Match (Fixes dynamic slugs)
            else if ( isset( $config_item['original_name'] ) && isset( $name_to_index[ $config_item['original_name'] ] ) ) {
                $found_index = $name_to_index[ $config_item['original_name'] ];
            }

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
        foreach ( $source_menu as $index => $item ) {
            if ( ! isset( $used_indices[$index] ) ) {
                // Skip Default Dividers
                if ( isset($item[4]) && strpos( $item[4], 'wp-menu-separator' ) !== false ) continue;
                
                $new_menu[ $menu_order_index ] = $item;
                $menu_order_index++;
            }
        }

        $menu = $new_menu;
    }

    public function block_hidden_pages() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
        
        $role = $this->get_current_role();
        if ( ! $role || current_user_can( 'administrator' ) ) return;

        $saved_settings = get_option( 'seac_menu_settings', array() );
        if ( ! isset( $saved_settings[$role] ) ) return;

        $blocked_slugs = array();
        foreach ( $saved_settings[$role] as $item ) {
            if ( isset($item['hidden']) && $item['hidden'] == true ) {
                $blocked_slugs[] = $item['slug'];
                if ( $item['slug'] === 'users.php' ) $blocked_slugs[] = 'profile.php';
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
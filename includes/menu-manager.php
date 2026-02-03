<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Menu_Manager {

    public function __construct() {
        // COMBINED HOOK: Run everything at the absolute last possible moment (PHP_INT_MAX).
        // This ensures every other plugin (including Bricks) has finished adding their items.
        add_action( 'admin_menu', array( $this, 'manage_menu_ordering' ), PHP_INT_MAX );
        
        // SECURITY: Block access to hidden pages
        add_action( 'admin_init', array( $this, 'block_hidden_pages' ) );
    }

    public function manage_menu_ordering() {
        global $menu;

        // 1. CAPTURE THE ORIGINAL MENU
        // We capture it right here, right now. No earlier, no later.
        // This ensures $menu contains exactly what WordPress built before we touch it.
        if ( ! isset( $GLOBALS['seac_original_menu'] ) ) {
            $GLOBALS['seac_original_menu'] = $menu;
        }

        // 2. CHECK PERMISSIONS
        $role = $this->get_current_role();
        if ( ! $role ) return;

        $saved_settings = get_option( 'seac_menu_settings', array() );
        
        // If no settings for this role, STOP. Do not modify $menu.
        if ( ! isset( $saved_settings[$role] ) || empty( $saved_settings[$role] ) ) {
            return;
        }

        // 3. APPLY CUSTOM ORDER
        $role_config = $saved_settings[$role];
        $new_menu = array();
        
        // We use the Captured Menu as the source of truth
        $source_menu = $GLOBALS['seac_original_menu'];
        
        // Map original menu for lookup
        $original_menu_map = array();
        foreach ( $source_menu as $index => $item ) {
            $key = isset($item[2]) ? $item[2] : "index_$index";
            $original_menu_map[$key] = $item;
        }

        $menu_order_index = 0;

        foreach ( $role_config as $config_item ) {
            $slug = $config_item['slug'];

            if ( isset($config_item['hidden']) && $config_item['hidden'] == true ) continue; 

            // Separators
            if ( isset($config_item['type']) && $config_item['type'] === 'separator' ) {
                $new_menu[ $menu_order_index ] = array( '', 'read', "separator_{$menu_order_index}", '', 'wp-menu-separator' );
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
        // Any item in the Source Map that wasn't used in the Config gets added here.
        // If Bricks was captured correctly, it will be in $original_menu_map (unless you saved it in the config).
        // NOTE: If you previously Saved a config where Bricks was at the bottom, it will stay there 
        // until you click "Reset" to refresh the config.
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
<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Settings_Page {

    private $formatted_menu = array();

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        // This runs AFTER the menu manager has captured the original menu, ensuring we use the exact same source.
        add_action( 'admin_init', array( $this, 'prepare_menu_data' ), 101 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_filter( 'upload_mimes', array( $this, 'allow_svg_uploads' ) );
    }

    public function allow_svg_uploads( $mimes ) {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( 'toplevel_page_seac-settings' !== $hook ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'seac-admin-js', SEAC_PLUGIN_URL . 'assets/js/admin-settings.js', array( 'jquery', 'jquery-ui-sortable' ), SEAC_VERSION, true );
        wp_enqueue_style( 'seac-plugin-css', SEAC_PLUGIN_URL . 'assets/css/plugin.css', array(), SEAC_VERSION );
    }

    public function add_plugin_page() {
        add_menu_page( 'Smart Edge Admin', 'Smart Admin', 'manage_options', 'seac-settings', array( $this, 'create_admin_page' ), 'dashicons-admin-appearance', 110 );
    }

    public function prepare_menu_data() {
        // Only build the menu list when on our settings page to save resources.
        if ( ! isset($_GET['page']) || $_GET['page'] !== 'seac-settings' ) {
            return;
        }

        // Use the definitive original menu captured by the Menu Manager at priority 100.
        // This ensures the UI sees the exact same menu state as the reordering logic,
        // solving tricky timing issues where other plugins add items late.
        if ( ! isset( $GLOBALS['seac_original_menu'] ) ) {
            // This can happen if the menu manager didn't run, or on a different admin page.
            // Fallback to the live menu for safety.
            global $menu;
            $source_menu = $menu;
        } else {
            $source_menu = $GLOBALS['seac_original_menu'];
        }
        
        if ( !empty($source_menu) && is_array($source_menu) ) {
            foreach ( $source_menu as $index => $item ) {

                $name = isset($item[0]) ? $item[0] : '';
                // Generate a unique slug, matching the logic in menu-manager.php
                $raw_slug = (isset($item[2]) && $item[2] !== '') ? $item[2] : 'seac_item_index_' . $index;
                $slug = html_entity_decode( $raw_slug );
                $type = 'item';
                $icon = isset($item[6]) ? $item[6] : 'dashicons-admin-generic';

                // Separators
                if ( isset($item[4]) && strpos( $item[4], 'wp-menu-separator' ) !== false ) {
                    $type = 'separator';
                    $name = '--- Divider ---';
                    $icon = '';
                } 
                // Clean Names
                else {
                    $name = preg_replace( '/<span.*<\/span>/', '', $name ); 
                    $name = strip_tags( $name ); 
                    $name = trim( $name );
                }

                // Handle items that are completely empty but are not separators
                if ( empty($name) && $type === 'item' ) {
                    $name = '(Unnamed Item)';
                }

                if( $icon == 'div' ) $icon = 'dashicons-admin-generic';

                $this->formatted_menu[] = array(
                    'original_name' => $name, 
                    'slug'          => $slug, 
                    'icon'          => $icon,
                    'type'          => $type
                );
            }
        }

    }

    public function create_admin_page() {
        // --- DATA PREPARATION ---
        // The menu data is now prepared in the `prepare_menu_data` method, which runs on `admin_init`.
        // This ensures we have the final, complete menu list before it gets reordered.
        $options = get_option( 'seac_settings' );
        $saved_menu_settings = isset($options['menu_config']) ? $options['menu_config'] : array();
        $seac_data = array(
            'roles' => get_editable_roles(),
            'menu'  => $this->formatted_menu,
            'saved_settings' => $saved_menu_settings
        );
        ?>
        
        <div class="wrap seac-settings-wrap">
            <h1 class="wp-heading-inline">Smart Edge Admin Customizer</h1>
            
            <script type="text/javascript">
                var seacData = <?php echo wp_json_encode($seac_data); ?>;
            </script>

            <form method="post" action="options.php">
                <?php settings_fields( 'seac_option_group' ); ?>
                
                <div class="seac-card">
                    <div class="seac-card-header">
                        <h2>Branding & Colors</h2>
                        <p>Customize the look and feel of your admin dashboard.</p>
                    </div>
                    <div class="seac-card-body">
                        <?php do_settings_sections( 'seac-settings' ); ?>
                    </div>
                </div>

                <div class="seac-card">
                    <div class="seac-card-header">
                        <h2>Menu Manager</h2>
                        <p>Drag to reorder, rename items, or hide them per user role.</p>
                    </div>
                    <div class="seac-card-body seac-menu-manager">
                        <div class="seac-role-tabs" id="seac_role_tabs"></div>
                        <div class="seac-menu-controls" style="margin-bottom: 15px; text-align: right;">
                             <button type="button" id="seac_add_divider_btn" class="button" style="margin-right: 8px;">
                                <span class="dashicons dashicons-minus"></span> Add Divider
                             </button>
                             <button type="button" id="seac_reset_menu_btn" class="button">
                                <span class="dashicons dashicons-image-rotate" style="margin-top: 3px; font-size: 16px;"></span> Reset to Default
                             </button>
                        </div>
                        <div class="seac-menu-editor" id="seac_menu_editor">
                            <ul id="seac_menu_list" class="seac-sortable-list"></ul>
                        </div>
                        <input type="hidden" name="seac_settings[menu_config]" id="seac_menu_config_input">
                    </div>
                </div>

                <div class="seac-submit-area">
                    <?php submit_button( 'Save Changes', 'primary large', 'submit', false ); ?>
                </div>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting( 'seac_option_group', 'seac_settings', array( $this, 'sanitize' ) );
        add_settings_section( 'seac_setting_section_branding', '', null, 'seac-settings' );
        add_settings_field( 'logo_url', 'Admin Menu Logo', array( $this, 'logo_url_callback' ), 'seac-settings', 'seac_setting_section_branding' );
        add_settings_field( 'accent_color', 'Accent Color', array( $this, 'accent_color_callback' ), 'seac-settings', 'seac_setting_section_branding' );
    }

    public function sanitize( $input ) {
        $new_input = array();
        if( isset( $input['logo_url'] ) ) $new_input['logo_url'] = sanitize_text_field( $input['logo_url'] );
        if( isset( $input['accent_color'] ) ) $new_input['accent_color'] = sanitize_hex_color( $input['accent_color'] );
        
        // Standardize saving the menu config as part of the main settings array.
        // This avoids race conditions from using update_option inside a sanitize callback.
        if ( isset( $input['menu_config'] ) ) {
            $json = stripslashes( $input['menu_config'] );
            $decoded = json_decode( $json, true );
            // Only add to our settings array if it's valid decoded JSON.
            if ( is_array( $decoded ) ) $new_input['menu_config'] = $decoded;
        }
        return $new_input;
    }

    public function logo_url_callback() {
        $options = get_option( 'seac_settings' );
        $logo_url = isset( $options['logo_url'] ) ? $options['logo_url'] : '';
        ?>
        <div class="seac-control-group">
            <div id="seac_logo_preview" class="seac-logo-preview" style="<?php echo $logo_url ? 'background-image: url('.$logo_url.');' : ''; ?>"></div>
            <div class="seac-input-group">
                <input type="text" id="seac_logo_url" name="seac_settings[logo_url]" value="<?php echo esc_attr( $logo_url ); ?>" />
                <div class="seac-button-group">
                    <input type="button" class="button button-secondary" value="Select Image" id="seac_upload_logo_btn" />
                    <?php if ( $logo_url ) : ?><input type="button" class="button button-link-delete" value="Remove" id="seac_remove_logo_btn" /><?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    public function accent_color_callback() {
        $options = get_option( 'seac_settings' );
        $color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';
        echo '<div class="seac-control-group"><input type="color" id="accent_color" name="seac_settings[accent_color]" value="' . esc_attr( $color ) . '" /></div>';
    }
}

if ( is_admin() ) $seac_settings_page = new SEAC_Settings_Page();
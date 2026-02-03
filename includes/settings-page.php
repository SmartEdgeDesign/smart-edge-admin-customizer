<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Settings_Page {

    private $formatted_menu = array();

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        // Server-Side Reset Listener
        add_action( 'admin_init', array( $this, 'handle_reset_action' ) );
        add_action( 'admin_init', array( $this, 'prepare_menu_data' ), 101 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_filter( 'upload_mimes', array( $this, 'allow_svg_uploads' ) );
    }

    // --- HARD RESET HANDLER ---
    public function handle_reset_action() {
        if ( isset( $_GET['seac_reset_role'] ) && check_admin_referer( 'seac_reset_action', 'seac_nonce' ) ) {
            if ( ! current_user_can( 'manage_options' ) ) return;

            $role_to_reset = sanitize_text_field( $_GET['seac_reset_role'] );
            $settings = get_option( 'seac_menu_settings', array() );

            if ( isset( $settings[$role_to_reset] ) ) {
                unset( $settings[$role_to_reset] );
                update_option( 'seac_menu_settings', $settings );
            }

            // Redirect to clean URL
            wp_redirect( remove_query_arg( array( 'seac_reset_role', 'seac_nonce' ) ) );
            exit;
        }
    }

    public function allow_svg_uploads( $mimes ) {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( 'toplevel_page_seac-settings' !== $hook ) { return; }
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'seac-admin-js', SEAC_PLUGIN_URL . 'assets/js/admin-settings.js', array( 'jquery', 'jquery-ui-sortable' ), '43.0.0', true );
        wp_enqueue_style( 'seac-plugin-css', SEAC_PLUGIN_URL . 'assets/css/plugin.css', array(), filemtime( SEAC_PLUGIN_PATH . 'assets/css/plugin.css' ) );
    }

    public function add_plugin_page() {
        add_menu_page( 'Smart Edge Admin', 'Smart Admin', 'manage_options', 'seac-settings', array( $this, 'create_admin_page' ), 'dashicons-admin-appearance', 110 );
    }

    public function prepare_menu_data() {
        if ( ! isset($_GET['page']) || $_GET['page'] !== 'seac-settings' ) return;

        if ( ! isset( $GLOBALS['seac_original_menu'] ) ) {
            global $menu;
            $source_menu = $menu;
        } else {
            $source_menu = $GLOBALS['seac_original_menu'];
        }
        
        if ( !empty($source_menu) && is_array($source_menu) ) {
            foreach ( $source_menu as $index => $item ) {
                if ( ! isset( $item[2] ) ) continue;

                $name = isset($item[0]) ? $item[0] : '';
                $raw_slug = (isset($item[2]) && $item[2] !== '') ? $item[2] : 'seac_item_index_' . $index;
                $slug = html_entity_decode( $raw_slug );
                $type = 'item';
                $icon = isset($item[6]) ? $item[6] : 'dashicons-admin-generic';

                if ( isset($item[4]) && strpos( $item[4], 'wp-menu-separator' ) !== false ) {
                    $type = 'separator';
                    $name = '--- Divider ---';
                    $icon = '';
                } else {
                    $name = trim( strip_tags( preg_replace( '/<span.*<\/span>/', '', $name ) ) );
                }

                if ( empty($name) && $type === 'item' ) $name = '(Unnamed Item)';
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
        $saved_menu_settings = get_option( 'seac_menu_settings', array() );
        $seac_data = array(
            'roles' => get_editable_roles(),
            'menu'  => $this->formatted_menu,
            'saved_settings' => $saved_menu_settings,
            'reset_nonce' => wp_create_nonce( 'seac_reset_action' ), // Passed to JS
            'admin_url' => admin_url( 'admin.php?page=seac-settings' )
        );
        ?>
        
        <div class="wrap seac-settings-wrap">
            <h1 class="wp-heading-inline">Smart Edge Admin Customizer</h1>
            <script type="text/javascript">var seacData = <?php echo wp_json_encode($seac_data); ?>;</script>

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
                        <div style="margin-bottom: 15px; display: flex; justify-content: flex-end; gap: 10px;">
                             <button type="button" id="seac_add_divider_btn" class="button button-secondary">
                                <span class="dashicons dashicons-plus"></span> Add Divider
                             </button>
                             <button type="button" id="seac_reset_menu_btn" class="button">
                                <span class="dashicons dashicons-image-rotate"></span> Reset to Default
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
        
        // Handle Menu Save
        if ( isset( $input['menu_config'] ) ) {
            $json = stripslashes( $input['menu_config'] );
            $decoded = json_decode( $json, true );
            if ( is_array( $decoded ) ) update_option( 'seac_menu_settings', $decoded );
        }
        return $new_input;
    }

    public function logo_url_callback() {
        $options = get_option( 'seac_settings' );
        $logo_url = isset( $options['logo_url'] ) ? $options['logo_url'] : '';
        echo '<div class="seac-control-group"><div id="seac_logo_preview" class="seac-logo-preview" style="'.($logo_url ? 'background-image: url('.$logo_url.');' : '').'"></div><div class="seac-input-group"><input type="text" id="seac_logo_url" name="seac_settings[logo_url]" value="'.esc_attr($logo_url).'" /><div class="seac-button-group"><input type="button" class="button button-secondary" value="Select Image" id="seac_upload_logo_btn" />'.($logo_url ? '<input type="button" class="button button-link-delete" value="Remove" id="seac_remove_logo_btn" />' : '').'</div></div></div>';
    }

    public function accent_color_callback() {
        $options = get_option( 'seac_settings' );
        $color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';
        echo '<div class="seac-control-group"><input type="color" id="accent_color" name="seac_settings[accent_color]" value="' . esc_attr( $color ) . '" /></div>';
    }
}

if ( is_admin() ) $seac_settings_page = new SEAC_Settings_Page();
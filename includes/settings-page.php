<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Settings_Page {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
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
        // Bump version to force refresh
        wp_enqueue_script( 'seac-admin-js', SEAC_PLUGIN_URL . 'assets/js/admin-settings.js', array( 'jquery', 'jquery-ui-sortable' ), '2.0.0', true );
        wp_enqueue_style( 'seac-plugin-css', SEAC_PLUGIN_URL . 'assets/css/plugin.css', array(), filemtime( SEAC_PLUGIN_PATH . 'assets/css/plugin.css' ) );
    }

    public function add_plugin_page() {
        add_menu_page( 'Smart Edge Admin', 'Smart Admin', 'manage_options', 'seac-settings', array( $this, 'create_admin_page' ), 'dashicons-admin-appearance', 110 );
    }

    public function create_admin_page() {
        // --- DATA PREPARATION ---
        global $menu;
        $formatted_menu = array();
        
        if ( !empty($menu) && is_array($menu) ) {
            foreach ( $menu as $item ) {
                if ( ! empty( $item[0] ) ) {
                    // Skip separators
                    if ( strpos( $item[4], 'wp-menu-separator' ) !== false ) continue;
                    
                    // Icon Handling
                    $icon = isset($item[6]) ? $item[6] : 'dashicons-admin-generic';
                    if( $icon == 'div' ) $icon = 'dashicons-admin-generic';

                    $formatted_menu[] = array(
                        'original_name' => strip_tags($item[0]), 
                        'slug'          => $item[2], 
                        'icon'          => $icon
                    );
                }
            }
        }

        // Create the data array
        $seac_data = array(
            'roles' => get_editable_roles(),
            'menu'  => $formatted_menu
        );
        ?>
        
        <div class="wrap seac-settings-wrap">
            <h1 class="wp-heading-inline">Smart Edge Admin Customizer</h1>
            
            <script type="text/javascript">
                var seacData = <?php echo wp_json_encode($seac_data); ?>;
                // Console log to prove it loaded
                console.log('SEAC Data Loaded:', seacData); 
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
                        <div class="seac-role-tabs" id="seac_role_tabs">
                            </div>
                        <div class="seac-menu-editor" id="seac_menu_editor">
                            <ul id="seac_menu_list" class="seac-sortable-list">
                                </ul>
                        </div>
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
        
        // Save Branding
        if( isset( $input['logo_url'] ) )
            $new_input['logo_url'] = sanitize_text_field( $input['logo_url'] );
        if( isset( $input['accent_color'] ) )
            $new_input['accent_color'] = sanitize_hex_color( $input['accent_color'] );

        // SAVE MENU CONFIG (The new part)
        // We get this from $_POST because add_settings_field is tricky with hidden arrays
        if ( isset( $_POST['seac_settings']['menu_config'] ) ) {
            // It comes in as a JSON string
            $json = stripslashes( $_POST['seac_settings']['menu_config'] );
            $decoded = json_decode( $json, true );
            
            // Save to a separate option key for cleaner data
            update_option( 'seac_menu_settings', $decoded );
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
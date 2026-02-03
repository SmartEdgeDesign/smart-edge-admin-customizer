<?php
if ( ! defined( 'WPINC' ) ) { die; }

class SEAC_Settings_Page {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( 'toplevel_page_seac-settings' !== $hook ) {
            return;
        }
        // Enqueue WordPress Media Uploader
        wp_enqueue_media();
        
        // Enqueue our custom JS
        wp_enqueue_script( 'seac-admin-js', SEAC_PLUGIN_URL . 'assets/js/admin-settings.js', array( 'jquery' ), '1.0.0', true );
    }

    public function add_plugin_page() {
        add_menu_page(
            'Smart Edge Admin', 
            'Smart Edge Admin', 
            'manage_options', 
            'seac-settings', 
            array( $this, 'create_admin_page' ), 
            'dashicons-admin-appearance', 
            2
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Edge Admin Customizer</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'seac_option_group' );
                do_settings_sections( 'seac-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'seac_option_group', 
            'seac_settings', 
            array( $this, 'sanitize' ) 
        );

        add_settings_section(
            'seac_setting_section_branding', 
            'Branding Settings', 
            null, 
            'seac-settings'
        );

        add_settings_field(
            'logo_url', 
            'Admin Menu Logo', 
            array( $this, 'logo_url_callback' ), 
            'seac-settings', 
            'seac_setting_section_branding'
        );

        add_settings_field(
            'accent_color', 
            'Accent Color', 
            array( $this, 'accent_color_callback' ), 
            'seac-settings', 
            'seac_setting_section_branding'
        );
    }

    public function sanitize( $input ) {
        $new_input = array();
        if( isset( $input['logo_url'] ) )
            $new_input['logo_url'] = sanitize_text_field( $input['logo_url'] );
        if( isset( $input['accent_color'] ) )
            $new_input['accent_color'] = sanitize_hex_color( $input['accent_color'] );

        return $new_input;
    }

    public function logo_url_callback() {
        $options = get_option( 'seac_settings' );
        $logo_url = isset( $options['logo_url'] ) ? $options['logo_url'] : '';
        ?>
        <div style="display: flex; align-items: center; gap: 15px;">
            <div id="seac_logo_preview" style="width: 80px; height: 80px; background-color: #0c0c0c; background-size: contain; background-repeat: no-repeat; background-position: center; border: 1px solid #444; <?php echo $logo_url ? 'background-image: url('.$logo_url.');' : ''; ?>"></div>
            <div>
                <input type="text" id="seac_logo_url" name="seac_settings[logo_url]" value="<?php echo esc_attr( $logo_url ); ?>" style="width: 300px; display: block; margin-bottom: 10px;" />
                <input type="button" class="button button-secondary" value="Upload Image" id="seac_upload_logo_btn" />
                <input type="button" class="button button-link-delete" value="Remove" id="seac_remove_logo_btn" />
            </div>
        </div>
        <?php
    }

    public function accent_color_callback() {
        $options = get_option( 'seac_settings' );
        $color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';
        echo '<input type="color" id="accent_color" name="seac_settings[accent_color]" value="' . esc_attr( $color ) . '" />';
    }
}

if ( is_admin() )
    $seac_settings_page = new SEAC_Settings_Page();
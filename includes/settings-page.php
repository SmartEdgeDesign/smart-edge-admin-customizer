<?php
/**
 * Admin Settings Page
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Register the settings page.
 */
function seac_register_settings_page() {
    add_options_page(
        __( 'Admin Styler', 'smart-edge-admin-customizer' ),
        __( 'Admin Styler', 'smart-edge-admin-customizer' ),
        'manage_options',
        'seac-settings',
        'seac_settings_page_html'
    );
}
add_action( 'admin_menu', 'seac_register_settings_page' );

/**
 * Build the settings page HTML.
 */
function seac_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'seac-settings' );
            do_settings_sections( 'seac-settings' );
            submit_button( __( 'Save Settings', 'smart-edge-admin-customizer' ) );
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register the settings, sections, and fields.
 */
function seac_settings_page_init() {
    register_setting( 'seac-settings', 'seac_settings' );

    add_settings_section(
        'seac_branding_section',
        __( 'Branding', 'smart-edge-admin-customizer' ),
        function() {
            echo '<p>' . __( 'Configure your custom dashboard branding.', 'smart-edge-admin-customizer' ) . '</p>';
        },
        'seac-settings'
    );

    add_settings_field(
        'seac_accent_color',
        __( 'Accent Color', 'smart-edge-admin-customizer' ),
        'seac_accent_color_callback',
        'seac-settings',
        'seac_branding_section'
    );
}
add_action( 'admin_init', 'seac_settings_page_init' );

function seac_accent_color_callback() {
    $options = get_option( 'seac_settings' );
    $color   = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';
    echo '<input type="color" name="seac_settings[accent_color]" value="' . esc_attr( $color ) . '">';
}
<?php
/**
 * Admin Settings Page
 */

if ( ! defined( 'WPINC' ) ) { die; }

/**
 * Register the settings page.
 */
function seac_register_settings_page() {
    add_options_page(
        'Admin Styler', // Page Title
        'Admin Styler', // Menu Title
        'manage_options', // Capability
        'seac-settings', // Slug
        'seac_settings_page_html' // Callback
    );
}
add_action( 'admin_menu', 'seac_register_settings_page' );

/**
 * Register the settings and fields.
 */
function seac_settings_page_init() {
    register_setting( 'seac-settings', 'seac_settings' );

    add_settings_section(
        'seac_branding_section',
        'Branding Settings',
        null,
        'seac-settings'
    );

    add_settings_field(
        'seac_accent_color',
        'Sidebar / Accent Color',
        'seac_accent_color_callback',
        'seac-settings',
        'seac_branding_section'
    );
}
add_action( 'admin_init', 'seac_settings_page_init' );

/**
 * Callback for the color picker field
 */
function seac_accent_color_callback() {
    $options = get_option( 'seac_settings' );
    $color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba'; // Default WP Blue
    echo '<input type="color" name="seac_settings[accent_color]" value="' . esc_attr( $color ) . '">';
}

/**
 * Build the settings page HTML.
 */
function seac_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'seac-settings' );
            do_settings_sections( 'seac-settings' );
            submit_button( 'Save Settings' );
            ?>
        </form>
    </div>
    <?php
}
<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Adds the settings page to the admin menu.
 */
function seac_add_settings_page() {
	add_options_page(
		'Admin Customizer Settings', // Page Title
		'Admin Styler',              // Menu Title
		'manage_options',            // Capability
		'seac-settings',             // Menu Slug
		'seac_settings_page_html'    // Callback function
	);
}
add_action( 'admin_menu', 'seac_add_settings_page' );

/**
 * Registers the settings, section, and fields.
 */
function seac_register_settings() {
	register_setting( 'seac_settings_group', 'seac_settings' );

	add_settings_section(
		'seac_styling_section',
		'Color & Styling',
		null,
		'seac-settings'
	);

	add_settings_field(
		'seac_accent_color',
		'Accent Color',
		'seac_accent_color_callback',
		'seac-settings',
		'seac_styling_section'
	);

    add_settings_section(
		'seac_menu_management_section',
		'Admin Menu Management',
		'<p>Control which top-level menu items are visible for each role. Admins will always see everything.</p>',
		'seac-settings'
	);

    add_settings_field(
		'seac_menu_visibility',
		'Menu Item Visibility & Separators',
		'seac_menu_visibility_callback',
		'seac-settings',
		'seac_menu_management_section'
	);
}
add_action( 'admin_init', 'seac_register_settings' );

/**
 * Callback for the accent color field.
 */
function seac_accent_color_callback() {
	$options = get_option( 'seac_settings' );
	$color = isset( $options['accent_color'] ) ? $options['accent_color'] : '#3858e9';
	echo '<input type="color" name="seac_settings[accent_color]" value="' . esc_attr( $color ) . '" />';
}

/**
 * Callback for the menu visibility fields.
 */
function seac_menu_visibility_callback() {
    global $menu;
    $options = get_option('seac_settings');

    // Get your custom roles
    $roles_to_manage = ['editor' => 'Staff', 'subscriber' => 'Client'];

    echo '<table class="wp-list-table widefat striped">';
    echo '<thead><tr><th>Menu Item</th>';
    foreach($roles_to_manage as $role => $name) {
        echo '<th style="text-align:center;">Show for ' . esc_html($name) . '</th>';
    }
    echo '<th style="text-align:center;">Separator Top</th><th style="text-align:center;">Separator Bottom</th></tr></thead>';
    echo '<tbody>';

    foreach ($menu as $item) {
        if (empty($item[0])) continue; // Skip separators

        $menu_slug = $item[2];
        $menu_name = preg_replace('/<span.*span>/', '', $item[0]); // Clean up name

        echo '<tr>';
        echo '<td>' . esc_html($menu_name) . '</td>';

        foreach($roles_to_manage as $role => $name) {
            $checked = isset($options['visibility'][$menu_slug][$role]) ? 'checked' : '';
            echo '<td style="text-align:center;"><input type="checkbox" name="seac_settings[visibility]['.esc_attr($menu_slug).']['.esc_attr($role).']" value="1" ' . $checked . ' /></td>';
        }

        $sep_top_checked = isset($options['separators'][$menu_slug]['top']) ? 'checked' : '';
        $sep_bottom_checked = isset($options['separators'][$menu_slug]['bottom']) ? 'checked' : '';
        echo '<td style="text-align:center;"><input type="checkbox" name="seac_settings[separators]['.esc_attr($menu_slug).'][top]" value="1" '.$sep_top_checked.' /></td>';
        echo '<td style="text-align:center;"><input type="checkbox" name="seac_settings[separators]['.esc_attr($menu_slug).'][bottom]" value="1" '.$sep_bottom_checked.' /></td>';

        echo '</tr>';
    }
    echo '</tbody></table>';
}

/**
 * Renders the HTML for the settings page.
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
			settings_fields( 'seac_settings_group' );
			do_settings_sections( 'seac-settings' );
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}

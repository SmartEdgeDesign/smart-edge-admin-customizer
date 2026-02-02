<?php
/**
 * Admin Settings Page
 *
 * Creates the settings page for the plugin, where users can configure options.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Register the settings page.
 */
function seac_register_settings_page() {
	add_options_page(
		__( 'Admin Styler', 'se-admin-customizer' ),
		__( 'Admin Styler', 'se-admin-customizer' ),
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
			submit_button( __( 'Save Settings', 'se-admin-customizer' ) );
			?>
		</form>
	</div>
	<?php
}

/**
 * Register the settings, sections, and fields.
 */
function seac_settings_page_init() {
	// Register the main setting group.
	register_setting( 'seac-settings', 'seac_settings' );

    // Section 1: Branding
	add_settings_section(
		'seac_branding_section',
		__( 'Branding', 'se-admin-customizer' ),
		function() {
			echo '<p>' . __( 'Upload a custom logo and set a brand accent color.', 'se-admin-customizer' ) . '</p>';
		},
		'seac-settings'
	);

    // Field: Logo Upload
    add_settings_field(
		'seac_logo_id',
		__( 'Admin Menu Logo', 'se-admin-customizer' ),
		'seac_logo_upload_callback',
		'seac-settings',
		'seac_branding_section'
	);

	// Field: Accent Color
	add_settings_field(
		'seac_accent_color',
		__( 'Accent Color', 'se-admin-customizer' ),
		'seac_accent_color_callback',
		'seac-settings',
		'seac_branding_section'
	);

    // Section 2: UI Control
    add_settings_section(
		'seac_ui_control_section',
		__( 'UI Control', 'se-admin-customizer' ),
        function() {
			echo '<p>' . __( 'Control the visibility of default WordPress UI elements for different roles.', 'se-admin-customizer' ) . '</p>';
		},
		'seac-settings'
	);

    // Field: Hide Screen Options
    add_settings_field(
		'seac_hide_screen_options',
		__( 'Hide "Screen Options" Tab', 'se-admin-customizer' ),
		'seac_hide_screen_options_callback',
		'seac-settings',
		'seac_ui_control_section'
	);


	// Section 3: Menu Visibility
	add_settings_section(
		'seac_menu_visibility_section',
		__( 'Menu Item Visibility', 'se-admin-customizer' ),
		'seac_menu_visibility_section_callback',
		'seac-settings'
	);

	// Dynamically create fields for each role.
	$roles_to_manage = [
		'editor'     => __( 'Staff Menu Visibility', 'se-admin-customizer' ),
		'subscriber' => __( 'Client Menu Visibility', 'se-admin-customizer' ),
	];

	foreach ( $roles_to_manage as $role => $label ) {
		add_settings_field(
			'seac_hidden_menu_items_' . $role,
			$label,
			'seac_hidden_menu_items_callback',
			'seac-settings',
			'seac_menu_visibility_section',
			[ 'role' => $role ]
		);
	}
}
add_action( 'admin_init', 'seac_settings_page_init' );


/**
 * Callback for the accent color field.
 */
function seac_accent_color_callback() {
	$options = get_option( 'seac_settings' );
	$color   = isset( $options['accent_color'] ) ? $options['accent_color'] : '#007cba';
	echo '<input type="color" name="seac_settings[accent_color]" value="' . esc_attr( $color ) . '">';
}

/**
 * Callback for the logo upload field.
 */
function seac_logo_upload_callback() {
	$options = get_option( 'seac_settings' );
	$logo_id = isset( $options['logo_id'] ) ? $options['logo_id'] : '';
	$image_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	?>
	<div class="seac-image-uploader">
		<img src="<?php echo esc_url( $image_url ); ?>" style="<?php echo $logo_id ? '' : 'display:none;'; ?> max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px; margin-bottom: 10px;" />
		<input type="hidden" name="seac_settings[logo_id]" value="<?php echo esc_attr( $logo_id ); ?>" />
		<button type="button" class="button seac-upload-button"><?php _e( 'Upload or Select Logo', 'se-admin-customizer' ); ?></button>
		<button type="button" class="button seac-remove-button" style="<?php echo $logo_id ? '' : 'display:none;'; ?>"><?php _e( 'Remove Logo', 'se-admin-customizer' ); ?></button>
        <p class="description"><?php _e( 'Recommended size: approx 200x80 pixels.', 'se-admin-customizer' ); ?></p>
	</div>
	<?php
}

/**
 * Callback for the "Hide Screen Options" field.
 */
function seac_hide_screen_options_callback() {
    $options = get_option( 'seac_settings' );
    $roles_to_hide = isset( $options['hide_screen_options'] ) ? (array) $options['hide_screen_options'] : [];

    // Define the roles you want to offer this option for.
    $target_roles = [ 'editor' => 'Staff', 'subscriber' => 'Client' ];

    foreach ( $target_roles as $role_slug => $role_name ) {
		$checked = in_array( $role_slug, $roles_to_hide ) ? 'checked' : '';
		echo "<label><input type='checkbox' name='seac_settings[hide_screen_options][]' value='{$role_slug}' {$checked}> {$role_name}</label><br>";
    }
    echo '<p class="description">' . __( 'Check a role to hide the "Screen Options" tab for all users with that role.', 'se-admin-customizer' ) . '</p>';
}


/**
 * Callback for the menu visibility section.
 */
function seac_menu_visibility_section_callback() {
	echo '<p>' . __( 'Select the menu items you want to HIDE for each user role. Administrators will always see everything.', 'se-admin-customizer' ) . '</p>';
}

/**
 * Callback for the hidden menu items checkboxes.
 */
function seac_hidden_menu_items_callback( $args ) {
	global $menu;
	$options      = get_option( 'seac_settings' );
	$role         = $args['role'];
	$hidden_items = isset( $options[ "hidden_menu_items_{$role}" ] ) ? (array) $options[ "hidden_menu_items_{$role}" ] : [];

	// Use a copy of the menu to avoid issues.
	$menu_items = $menu;
	if ( empty( $menu_items ) ) {
		echo __( 'Could not retrieve menu items.', 'se-admin-customizer' );
		return;
	}

	echo '<div style="max-height: 250px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">';
	foreach ( $menu_items as $item ) {
		// The menu item URL is a good unique identifier.
		$item_id = esc_attr( $item[2] );
		if ( empty( trim( $item[0] ) ) ) {
			continue; // Skip separators.
		}
		$checked = in_array( $item_id, $hidden_items ) ? 'checked' : '';
		$label   = wp_strip_all_tags( $item[0] );
		echo "<label><input type='checkbox' name='seac_settings[hidden_menu_items_{$role}][]' value='{$item_id}' {$checked}> {$label} <small>({$item_id})</small></label><br>";
	}
	echo '</div>';
}

/**
 * Enqueue the media uploader script and add custom JS for the settings page.
 */
function seac_enqueue_settings_scripts( $hook_suffix ) {
    // Only load on our specific settings page.
	if ( 'settings_page_seac-settings' !== $hook_suffix ) {
		return;
	}
	wp_enqueue_media();
	add_action( 'admin_footer', 'seac_settings_page_footer_scripts' );
}
add_action( 'admin_enqueue_scripts', 'seac_enqueue_settings_scripts' );

/**
 * Add the JavaScript for the media uploader to the footer.
 */
function seac_settings_page_footer_scripts() {
	?>
	<script>
	jQuery(document).ready(function($){
		// Handle the logo uploader
		$('body').on('click', '.seac-upload-button', function(e){
			e.preventDefault();
			var button = $(this);
			var uploaderContainer = button.closest('.seac-image-uploader');
			var image = uploaderContainer.find('img');
			var input = uploaderContainer.find('input[type="hidden"]');
			var removeButton = uploaderContainer.find('.seac-remove-button');

			var frame = wp.media({
				title: '<?php _e( "Select or Upload Logo", "se-admin-customizer" ); ?>',
				button: { text: '<?php _e( "Use this logo", "se-admin-customizer" ); ?>' },
				multiple: false
			});

			frame.on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				input.val(attachment.id);
				image.attr('src', attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url).show();
				removeButton.show();
			});

			frame.open();
		});

		// Handle the logo remover
		$('body').on('click', '.seac-remove-button', function(e){
			e.preventDefault();
			var button = $(this);
			var uploaderContainer = button.closest('.seac-image-uploader');
			var image = uploaderContainer.find('img');
			var input = uploaderContainer.find('input[type="hidden"]');

			input.val('');
			image.attr('src', '').hide();
			button.hide();
		});
	});
	</script>
	<?php
}


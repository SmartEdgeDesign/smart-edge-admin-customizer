<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Enqueues admin scripts and styles.
 *
 * This function also adds inline styles to apply the custom accent color.
 */
function seac_enqueue_admin_assets() {
	$screen = get_current_screen();
	$options = get_option( 'seac_settings' );
	$accent_color = ! empty( $options['accent_color'] ) ? $options['accent_color'] : '#3858e9'; // Default to WordPress blue.

	// Enqueue the correct stylesheet based on the screen.
	// This prevents CSS conflicts on the block editor page.
	if ( $screen && $screen->is_block_editor() ) {
		wp_enqueue_style(
			'seac-admin-editor-styles',
			SEAC_PLUGIN_URL . 'assets/css/admin-editor.css',
			[],
			SEAC_VERSION
		);
	} else {
		wp_enqueue_style(
			'seac-admin-main-styles',
			SEAC_PLUGIN_URL . 'assets/css/admin-main.css',
			[],
			SEAC_VERSION
		);
	}

	// Add the dynamic accent color as inline CSS.
	// This is the best way to add dynamic styles from settings.
	$custom_css = "
        :root {
            --seac-accent-color: {$accent_color};
        }
        /* You can now use var(--seac-accent-color) in your CSS files! */
        #adminmenu .current a.menu-top,
        #adminmenu .wp-has-current-submenu .wp-menu-arrow,
        #adminmenu .wp-has-current-submenu .wp-menu-name,
        #adminmenu .wp-has-current-submenu a.wp-has-current-submenu,
        #adminmenu a.current:hover, #adminmenu a.wp-has-current-submenu:hover,
        .wp-core-ui .button-primary {
            background: {$accent_color} !important;
            color: #fff !important;
        }
        .wp-core-ui .button-primary:hover {
            background: color-mix(in srgb, {$accent_color} 90%, black) !important;
        }
        #adminmenu li.menu-top:hover,
        #adminmenu li.opensub > a.menu-top,
        #adminmenu li > a.menu-top:focus {
            background-color: {$accent_color};
        }
    ";
	wp_add_inline_style( 'seac-admin-main-styles', $custom_css );
}
add_action( 'admin_enqueue_scripts', 'seac_enqueue_admin_assets' );

?>

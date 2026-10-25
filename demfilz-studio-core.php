<?php
/**
 * Plugin Name: Demfilz Studio Core
 * Plugin URI: https://demfilz.com/studio-core
 * Description: Portfolio and Services post types, a spam-protected contact form with stored entries, and a CTA button shortcode. Companion plugin for the Demfilz Studio theme; works with any theme.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Demfilz
 * Author URI: https://demfilz.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: demfilz-studio-core
 *
 * @package Demfilz_Studio_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DSTUDIO_CORE_VERSION', '1.0.0' );
define( 'DSTUDIO_CORE_DIR', plugin_dir_path( __FILE__ ) );

require DSTUDIO_CORE_DIR . 'includes/post-types.php';
require DSTUDIO_CORE_DIR . 'includes/meta-boxes.php';
require DSTUDIO_CORE_DIR . 'includes/contact-form.php';
require DSTUDIO_CORE_DIR . 'includes/shortcodes.php';

/**
 * Read a Demfilz Studio theme option with a plugin-side default, so the
 * plugin keeps working when a different theme is active.
 *
 * @param string $key           Option key without the dstudio_ prefix.
 * @param mixed  $default_value Fallback value.
 * @return mixed
 */
function dstudio_core_mod( $key, $default_value = '' ) {
	return get_theme_mod( 'dstudio_' . $key, $default_value );
}

/**
 * Register the post types and refresh permalinks on activation.
 *
 * @return void
 */
function dstudio_core_activate() {
	dstudio_register_post_types();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dstudio_core_activate' );

/**
 * Refresh permalinks on deactivation.
 *
 * @return void
 */
function dstudio_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'dstudio_core_deactivate' );

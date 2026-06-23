<?php // phpcs:ignore Generic.Commenting.DocComment.MissingShort
/**
 * @wordpress-plugin
 * Plugin Name:       Outstand AI
 * Description:       Add AI features to WordPress.
 * Plugin URI:        https://outstand.site/?utm_source=wp-plugins&utm_medium=outstand-ai&utm_campaign=plugin-uri
 * Requires at least: 6.7
 * Requires PHP:      8.2
 * Version:           1.1.0
 * Author:            Outstand
 * Author URI:        https://outstand.site/?utm_source=wp-plugins&utm_medium=outstand-ai&utm_campaign=author-uri
 * License:           GPL-3.0-or-later
 * License URI:       https://spdx.org/licenses/GPL-3.0-or-later.html
 * Update URI:        https://outstand.site/
 * GitHub Plugin URI: https://github.com/pixelalbatross/outstand-ai
 * Text Domain:       outstand-ai
 * Domain Path:       /languages
 */

namespace Outstand\WP\AI;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'OUTSTAND_AI_VERSION', '1.1.0' );
define( 'OUTSTAND_AI_BASENAME', plugin_basename( __FILE__ ) );
define( 'OUTSTAND_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'OUTSTAND_AI_PATH', plugin_dir_path( __FILE__ ) );
define( 'OUTSTAND_AI_DIST_URL', OUTSTAND_AI_URL . 'build/' );
define( 'OUTSTAND_AI_DIST_PATH', OUTSTAND_AI_PATH . 'build/' );

if ( file_exists( OUTSTAND_AI_PATH . 'vendor/autoload.php' ) ) {
	require_once OUTSTAND_AI_PATH . 'vendor/autoload.php';
}

if ( class_exists( PucFactory::class ) ) {
	PucFactory::buildUpdateChecker(
		'https://github.com/pixelalbatross/outstand-ai/',
		__FILE__,
		'outstand-ai'
	)->setBranch( 'main' );
}

/**
 * Load the plugin. Features self-gate: standalone features always run, while
 * features that extend the WordPress AI plugin register only when it is active.
 */
add_action(
	'plugins_loaded',
	function () {
		Plugin::get_instance()->enable();
	}
);

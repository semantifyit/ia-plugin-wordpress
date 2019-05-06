<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://semantify.it
 * @since             1.0.0
 * @package           Iasemantify
 *
 * @wordpress-plugin
 * Plugin Name:       Instant Annotation
 * Description:       Deploy your annotations from semantify.it to your wordpress website with an easy interface.
 * Version:           2.2.7
 * Author:            semantify.it
 * Author URI:        www.semantify.it
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       iasemantify
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define('IASEMANTIFY_PLUGIN_NAME_VERSION', '2.2.7');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-iasemantify-activator.php
 */
function activate_iasemantify() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-iasemantify-activator.php';
	Iasemantify_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-iasemantify-deactivator.php
 */
function deactivate_iasemantify() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-iasemantify-deactivator.php';
	Iasemantify_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_iasemantify' );
register_deactivation_hook( __FILE__, 'deactivate_iasemantify' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-iasemantify.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_iasemantify() {

	$plugin = new Iasemantify();
	$plugin->run();

}
run_iasemantify();

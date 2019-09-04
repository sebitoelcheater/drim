<?php

/**
 * The plugin bootstrap file.
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://opuslabs.in
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Mobile Ecommerce
 * Plugin URI:        https://opuslabs.in
 * Description:       Provides essential capabilities to run OpusLab's mobile applications based on Wordpress
 * Version:           1.0.0
 * Author:            Ujjwal Wahi
 * Author URI:        https://opuslabs.in
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mobile-ecommerce
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

if (!defined('ME_PLUGIN_URL'))
    define('ME_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-mobile-ecommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_mobile_ecommerce()
{
	$plugin = new MobileEcommerce();
	$plugin->run();
}
run_mobile_ecommerce();

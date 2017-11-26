<?php

/**
 * Contact Form 7 To API Advanced
 *
 * @package           wpcf7_api_adv
 *
 * @wordpress-plugin
 * Plugin Name:       Contact Form 7 To API Advanced
 * Plugin URI:        https://github.com/stat1c-void/cf7-to-api-adv
 * Description:       Connect Contact Forms 7 to remote API using GET or POST (advanced version).
 * Version:           1.0.0
 * Author:            stat1c-void (original by QUERY SOLUTIONS)
 * Author URI: 		  https://github.com/stat1c-void
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       wpcf7-api-adv
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('WPCF7_API_ADV_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPCF7_API_ADV_INCLUDES_PATH', plugin_dir_path(__FILE__) . 'includes/');
define('WPCF7_API_ADV_TEMPLATE_PATH', get_template_directory());
define('WPCF7_API_ADV_ADMIN_JS_URL', plugin_dir_url(__FILE__) . 'assets/js/');
define('WPCF7_API_ADV_ADMIN_CSS_URL', plugin_dir_url(__FILE__) . 'assets/css/');
define('WPCF7_API_ADV_FRONTEND_JS_URL', plugin_dir_url(__FILE__) . 'assets/js/');
define('WPCF7_API_ADV_FRONTEND_CSS_URL', plugin_dir_url(__FILE__) . 'assets/css/');
define('WPCF7_API_ADV_IMAGES_URL', plugin_dir_url(__FILE__) . 'assets/css/');

add_action('plugins_loaded', 'wpcf7_api_adv_textdomain');

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function wpcf7_api_adv_textdomain()
{
    load_plugin_textdomain('wpcf7-api-adv', false, basename(dirname(__FILE__)) . '/languages');
}

// The core plugin class
require_once WPCF7_API_ADV_INCLUDES_PATH . 'class.cf7-api.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'wpcf7_api_adv_activation_handler');
register_deactivation_hook(__FILE__, 'wpcf7_api_adv_deactivation_handler');

function wpcf7_api_adv_activation_handler()
{
    do_action('wpcf7_api_adv_activated');
}

function wpcf7_api_adv_deactivation_handler()
{
    do_action('wpcf7_api_adv_deactivated');
}

/**
 * Begins execution of the plugin.
 *
 * Init the plugin process
 *
 * @since    1.0.0
 */
function init_wpcf7_api_adv()
{
    global $wpcf7_api_adv;

    $wpcf7_api_adv = new WPCF7_api_adv();
    $wpcf7_api_adv->plugin_basename = plugin_basename(__FILE__);
    $wpcf7_api_adv->init();
}

init_wpcf7_api_adv();
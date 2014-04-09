<?php
/**
 * Editorial Control UK
 *
 * Upgrade the contributor role in WordPress so that any contributor can also upload images and pictures.
 * Sends email notification when posts are pending review, with fixes for email layouts.
 *
 * @package   Wordpress-Editorial-Control
 * @author    Onder Vincent Koc <vincent@loophole.eu>
 * @license   GPL-2.0+
 * @link      https://github.com/koconder/Wordpress-Editorial-Control
 * @copyright 2014 Onder Vincent Koc
 *
 * @wordpress-plugin
 * Plugin Name:       Editorial Control UK
 * Plugin URI:        https://github.com/koconder
 * Description:       Upgrade the contributor role in WordPress so that any contributor can also upload images and pictures and sends an email notification
 * Version:           3.2.0
 * Author:            Onder Vincent Koc
 * Author URI:        https://github.com/koconder
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 * Copyright 2013 Vincent Koc (https://github.com/koconder)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 */

// If this file is called directly, abort.
if (!defined('WPINC')){
    die;
}

/*----------------------------------------------------------------------------*
 * SETUP
 *----------------------------------------------------------------------------*/
$ec_config = array();
$ec_config['presstrends_api_key'] = false;//PressTrends API Key
$ec_config['presstrends_api_auth'] = false;//PressTrends AUTH Key


/*----------------------------------------------------------------------------*
 * Includes
 *----------------------------------------------------------------------------*/
require_once('include/class.php');

/*----------------------------------------------------------------------------*
 * WP-Updates
 *----------------------------------------------------------------------------*/
require_once('wp-updates-plugin.php');
new WPUpdatesPluginUpdater_297( 'http://wp-updates.com/api/2/plugin', plugin_basename(__FILE__));

// This is for testing only!
//set_site_transient( 'update_plugins', null );

// Show which variables are being requested when query plugin API
//add_filter( 'plugins_api_result', array(&amp;$this, 'debug_result'), 10, 3 );

/*----------------------------------------------------------------------------*
 * Activation - Deactivation Hooks
 *----------------------------------------------------------------------------*/

// To set default config on activation
register_activation_hook(__FILE__,'editorial_control_defaults');

// To turn off supercontributor if de-activated
register_deactivation_hook( __FILE__, 'editorial_control_deactivate' );

// To remove option flag when uninstalled
register_uninstall_hook(__FILE__,'editorial_control_uninstall');


/*----------------------------------------------------------------------------*
 * Post - Email Hooks
 *----------------------------------------------------------------------------*/
// Hook for post status changes
add_filter('transition_post_status', 'notify_status',10,3);

/*----------------------------------------------------------------------------*
 * Admin and Analytics
 *----------------------------------------------------------------------------*/
// Hook for adding admin menus
add_action('admin_menu', 'ec_add_option_page');

// PressTrends WordPress Action
if(!empty($ec_config['presstrends_api_key']) && !empty($ec_config['presstrends_api_auth'])){
	add_action('admin_init', 'presstrends_plugin');
}

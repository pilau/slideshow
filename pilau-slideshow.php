<?php

/**
 * Pilau Slideshow
 *
 * @package   Pilau_Slideshow
 * @author    Steve Taylor
 * @license   GPL-2.0+
 * @copyright 2014 Public Life
 *
 * @wordpress-plugin
 * Plugin Name:			Pilau Slideshow
 * Description:			A JavaScript-driven slideshow plugin for WordPress.
 * Version:				0.1
 * Author:				Steve Taylor
 * Text Domain:			pilau-slideshow-locale
 * License:				GPL-2.0+
 * License URI:			http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:			/lang
 * GitHub Plugin URI:	https://github.com/pilau/slideshow
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once( plugin_dir_path( __FILE__ ) . 'class-pilau-slideshow.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'Pilau_Slideshow', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Pilau_Slideshow', 'deactivate' ) );

Pilau_Slideshow::get_instance();

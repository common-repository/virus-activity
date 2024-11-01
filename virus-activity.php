<?php
/**
 * Plugin Name: Virus Activity
 * Plugin URI: httsp://virusactivity.com
 * Description: Display Virus Activity level on your blog
 * Version: 1.2.2
 * Author: virusactivity
 * Author URI: https://virusactivity.com
 * License: GPL2
 */

// Make sure we don't expose any info if called directly
defined( 'ABSPATH' ) or die( 'Access denied' );

if ( ! function_exists( 'add_action' ) ) {
	die( 'Access denied' );
}
define( 'VIRUS_ACTIVITY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VIRUS_ACTIVITY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'VIRUS_ACTIVITY_PLUGIN_LANG_PATH', plugin_basename( VIRUS_ACTIVITY_PLUGIN_PATH ) . '/languages' );

require_once( VIRUS_ACTIVITY_PLUGIN_PATH . 'class-virus-activity-plugin.php' );
require_once( VIRUS_ACTIVITY_PLUGIN_PATH . 'class-virus-activity.php' );
require_once( VIRUS_ACTIVITY_PLUGIN_PATH . 'class-virus-activity-widget.php' );

register_activation_hook( __FILE__, array( 'Virus_Activity_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Virus_Activity_Plugin', 'deactivate' ) );

add_filter( 'widget_form_callback', array( 'Virus_Activity_Widget', 'widget_form_callback' ), 10, 2 );
add_filter( 'plugin_locale', 'Virus_Activity_Plugin::set_plugin_language' );
add_action( 'widgets_init', 'Virus_Activity_Plugin::register_widget' );

add_action( 'plugins_loaded', 'virus_activity_load_textdomain' );
function virus_activity_load_textdomain() {
	load_plugin_textdomain( Virus_Activity_Plugin::WIDGET_NAME, false, VIRUS_ACTIVITY_PLUGIN_LANG_PATH );
}

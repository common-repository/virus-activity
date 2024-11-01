<?php
defined( 'ABSPATH' ) or die( 'Access denied' );

/**
 * Virus Activity Plugin
 * User: virusactivity
 * Date: 23/02/2016
 * Time: 16:08
 */
class Virus_Activity_Plugin {
	const WIDGET_CLASS = 'Virus_Activity_Widget';
	const WIDGET_NAME = 'virus-activity';
	private static $widgets_urls = array();

	/**
	 * Adds widget's feed url and cache lifetime for it to use later in Virus_Activity::speed_up_feeds static function
	 *
	 * @param $url
	 * @param $cache_lifetime
	 */
	public static function add_widget_url( $url, $cache_lifetime ) {
		self::$widgets_urls[ $url ] = $cache_lifetime;
	}

	/**
	 * Gets feed urls of all registered widgets
	 * @return array
	 */
	public static function get_widget_urls() {
		return self::$widgets_urls;
	}

	/**
	 *
	 * Set plugin language according wlang setting
	 * @return mixed
	 */
	public static function set_plugin_language() {
		$widget_settings = get_option( Virus_Activity_Plugin::WIDGET_CLASS . '_settings', false );
		if ( is_array( $widget_settings ) ) {
			if ( ! empty( $widget_settings['wlang'] ) ) {
				return $widget_settings['wlang'];
			}
		}

		return get_locale();
	}

	/**
	 * Actions to perform on plugin activation
	 */
	public static function activate() {
	}

	/**
	 * Actions to perform on plugin deactivation
	 */
	public static function deactivate() {
		delete_option( 'widget_' . Virus_Activity_Plugin::WIDGET_CLASS );
		delete_option( Virus_Activity_Plugin::WIDGET_CLASS . '_lastFeed' );
		delete_option( Virus_Activity_Plugin::WIDGET_CLASS . '_settings' );
		delete_option( Virus_Activity_Plugin::WIDGET_CLASS . '_nextFeedFetchTime' );
		delete_option( Virus_Activity_Plugin::WIDGET_CLASS . '_numberOfTries' );
	}

	/**
	 * Gets plugin configuration
	 * @return array
	 */
	public static function get_config() {
		$file = VIRUS_ACTIVITY_PLUGIN_PATH . 'config.php';
		if ( file_exists( $file ) ) {
			return include $file;
		}

		return array();
	}

	/**
	 * Initializes widget
	 */
	public static function register_widget() {
		register_widget( 'Virus_Activity_Widget' );
	}
}

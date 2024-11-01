<?php
defined( 'ABSPATH' ) or die( 'Access denied' );
require_once( ABSPATH . WPINC . '/feed.php' );

class Virus_Activity_Widget extends WP_Widget {
	private $config;

	public function __construct() {
		parent::__construct(
			Virus_Activity_Plugin::WIDGET_CLASS,
			'Virus Activity',
			array(
				'classname'   => Virus_Activity_Plugin::WIDGET_CLASS,
				'description' => __( 'Displays global virus activity level.', Virus_Activity_Plugin::WIDGET_NAME )
			)
		);
		$this->config = Virus_Activity_Plugin::get_config();
	}

	public static function widget_form_callback( $instance, $widget_object ) {
		if ( $widget_object instanceof Virus_Activity_Widget ) {
			$widget_settings = get_option( Virus_Activity_Plugin::WIDGET_CLASS . '_settings', false );
			if ( count( $widget_settings ) > 0 ) {
				if ( isset( $widget_settings['wlang'] ) ) {
					$instance['wlang'] = $widget_settings['wlang'];
				}
				if ( isset( $widget_settings['max_items'] ) ) {
					$instance['max_items'] = $widget_settings['max_items'];
				}
			}
		}

		return $instance;
	}

	private function validate_items_count( $items_count ) {
		if ( isset( $items_count ) and in_array( $items_count, $this->config['items_count_select'] ) ) {
			$max_items = (int) $items_count;
		} else {
			$max_items = (int) $this->config['items_count_default'];
		}

		return $max_items;
	}

	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? $instance['title'] : '';
		$wlang     = ! empty( $instance['wlang'] ) ? esc_attr( $instance['wlang'] ) : '';
		$max_items = $this->validate_items_count( isset ( $instance['max_items'] ) ? $instance['max_items'] : null );

		$widget_id = $this->id;

		if ( $this->number == '__i__' ) {
			$widget_id = uniqid( 'WID' );
		}

		$html =
			'<div id="' . $widget_id . '">'
			. '<p><label for="' . $this->get_field_id( 'title' ) . '">'
			. __( 'Title:', Virus_Activity_Plugin::WIDGET_NAME )
			. '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '"/>'
			. '</label></p>';

		$html .= '<p><label for="' . $this->get_field_id( 'max_items' ) . '">';
		$html .= __( 'Items:', Virus_Activity_Plugin::WIDGET_NAME ) . '&nbsp;';

		$html .= '<select class="widefat" id="' . $this->get_field_id( 'max_items' ) . '" name="' . $this->get_field_name( 'max_items' ) . '">';

		foreach ( $this->config['items_count_select'] as $ic ) {
			$html .= '<option value="' . $ic . '"' . selected( $max_items, $ic, false ) . '>' . $ic . '</option>';
		}
		$html .= '</select>';
		$html .= '</label></p>';

		if ( empty( $wlang ) ) { // if widget language isn't set, then try to detect wordpress language
			$wlang = get_locale();
		}

		if ( ! empty( $this->config['all_languages'] ) && is_array( $this->config['all_languages'] ) && count( $this->config['all_languages'] ) ) {

			if ( empty( $this->config['all_languages'][ $wlang ] ) ) {
				$html .= '<p><em>' . sprintf( __( 'Translations for %s were not found. Please choose other language from the list below.',
						Virus_Activity_Plugin::WIDGET_NAME ), $wlang ) . '</em></p>';
			}
			$html .= '<p><label for="' . $this->get_field_id( 'wlang' ) . '">'
			         . __( 'Language:', Virus_Activity_Plugin::WIDGET_NAME ) . '&nbsp;';
			$html .= '<select class="widefat" id="' . $this->get_field_id( 'wlang' ) . '" name="' . $this->get_field_name( 'wlang' ) . '">';
			$html .= '<option value="en_US"> '
			         . __( 'Choose language', Virus_Activity_Plugin::WIDGET_NAME )
			         . '</option>';
			foreach ( $this->config['all_languages'] as $lang_key => $lang_name ) {
				$html .= '<option value="' . $lang_key . '"'
				         . selected( $wlang, $lang_key, false )
				         . '>' . $lang_name . '</option>';
			}
			$html .= '</select></label></p>';

		} else {
			$html .= '<p><em>' . __( 'Missing languages list. Check configuration file.',
					Virus_Activity_Plugin::WIDGET_NAME ) . '</em></p>';
		}

		$show_widget_link_value = ( ! empty( $instance['show_widget_more_link'] ) ) ? 'checked="checked"' : '';
		$html .= '<p>'
		         . '<input type="checkbox" name="' . $this->get_field_name( 'show_widget_more_link' ) . '"'
		         . ' value="1" ' . $show_widget_link_value . ' />'
		         . __( 'Show widget information link', Virus_Activity_Plugin::WIDGET_NAME ) . ''
		         . '</p>';
		$html .= '</div>';
		echo $html;
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance = $old_instance;

		$instance['title']     = strip_tags( $new_instance['title'] );
		$instance['max_items'] = $this->validate_items_count( isset ( $new_instance['max_items'] ) ? $new_instance['max_items'] : null );

		if ( ! empty( $new_instance['wlang'] ) ) {
			$instance['wlang'] = $new_instance['wlang'];
		}

		update_option( Virus_Activity_Plugin::WIDGET_CLASS . '_settings', array(
			'max_items' => $instance['max_items'],
			'wlang'     => $instance['wlang']
		) );

		$instance['show_widget_more_link'] = ! empty( $new_instance['show_widget_more_link'] );

		return $instance;
	}

	function widget( $args, $instance ) {
		$widget_html = ! empty( $args['before_widget'] ) ? $args['before_widget'] : '';

		$w_title = esc_attr( $instance['title'] );

		$widget_html .= ( ! empty( $args['before_title'] ) ? $args['before_title'] : '' )
		                . ( ! empty( $w_title ) ? __( $w_title,
				Virus_Activity_Plugin::WIDGET_NAME ) : __( 'Virus Activity Level',
				Virus_Activity_Plugin::WIDGET_NAME ) )
		                . ( ! empty( $args['after_title'] ) ? $args['after_title'] : '' );

		$virus_activity_object = new Virus_Activity();
		$widget_settings       = get_option( Virus_Activity_Plugin::WIDGET_CLASS . '_settings', false );

		if ( ! empty( $widget_settings['max_items'] ) ) {
			$max_items = $widget_settings['max_items'];
		} else {
			$max_items = isset( $instance['max_items'] ) ? $instance['max_items'] : 0;
		}

		if ( ! isset( $max_items ) || $max_items < 0 || $max_items > 15 ) {
			$max_items = 5;
		}

		add_filter( 'wp_feed_cache_transient_lifetime', 'Virus_Activity::speed_up_feeds', 10, 2 );
		add_filter( 'wp_feed_options', 'Virus_Activity::set_feed_options', 10, 2 );

		if ( ! empty( $widget_settings['wlang'] ) ) {
			$wlang = $widget_settings['wlang'];
		} else {
			$wlang = ! empty( $instance['wlang'] ) ? $instance['wlang'] : '';
		}
		if ( empty( $wlang ) ) { // if widget language isn't set, then set widget language to en_US
			$wlang = 'en_US';
		}

		if ( empty( $widget_settings ) ) { // saves max items and language settings
			update_option( Virus_Activity_Plugin::WIDGET_CLASS . '_settings', array(
				'max_items' => $max_items,
				'wlang'     => $wlang
			) );
		}

		// set feed params
		$virus_activity_object->set_locale( $wlang );
		$virus_activity_object->set_items_count( $max_items );

		Virus_Activity_Plugin::add_widget_url(
			$virus_activity_object->get_feed_url(),
			$virus_activity_object->get_cache_lifetime()
		);

		$parsed_feed = $virus_activity_object->load_feed( $max_items );  // loads feed (fetches new or gets old one)

		$widget_html .= '<div style="height:5px"></div>';
		$widget_html .= '<div style="margin:10px 15px 5px 15px;text-align:center;float:left">';

		$widget_html .= '<div style="line-height:1">';
		if ( ! empty( $this->config['widget_more_link'] ) && ! empty( $instance['show_widget_more_link'] ) ) {
			$widget_html .= '<a rel="noopener nofollow" href="' . $this->config['widget_more_link'] . '" target="_blank">';
		}
		$image = VIRUS_ACTIVITY_PLUGIN_URL . 'world';
		$widget_html .= '<img src="' . $image . '.png" '
		                . 'srcset="' . $image . '2x.png 2x, ' . $image . '3x.png 3x"'
		                . ' title="'
		                . esc_attr( __( 'Virus Activity', Virus_Activity_Plugin::WIDGET_NAME ) )
		                . '" alt="'
		                . esc_attr( __( 'Virus Activity', Virus_Activity_Plugin::WIDGET_NAME ) )
		                . '" width="48" height="48"/>';
		if ( ! empty( $this->config['widget_more_link'] ) && ! empty( $instance['show_widget_more_link'] ) ) {
			$widget_html .= '</a>';
		}
		$widget_html .= '</div>';

		$widget_html .= '<div style="margin-top:-23px;height:23px">';
		$widget_html .= $this->get_meter_html( isset( $parsed_feed['activity_level']['level'] ) ? $parsed_feed['activity_level']['level'] : ( empty( $parsed_feed ) ? 3 : 0 ) );
		$widget_html .= '</div>';

		$widget_html .= '<div style="font-weight:bold">'
		                . __( 'Virus Activity', Virus_Activity_Plugin::WIDGET_NAME )
		                . '</div>';

		$widget_html .= '<div>';
		$feedTime = ! empty( $parsed_feed['activity_level']['date'] ) ? strtotime( $parsed_feed['activity_level']['date'] ) : 0;
		$now      = time();
		if ( $now - $feedTime < DAY_IN_SECONDS ) {
			$widget_html .= date_i18n( get_option( 'date_format' ), $feedTime );
		} else {
			$widget_html .= date_i18n( get_option( 'date_format' ), $now );
		}
		$widget_html .= '</div>';

		$widget_html .= '<div style="line-height:1">'
		                . $this->get_level_text( isset( $parsed_feed['activity_level']['level'] ) ? $parsed_feed['activity_level']['level'] : ( empty( $parsed_feed ) ? 3 : 0 ) )
		                . '</div>';

		$widget_html .= '</div>';//center

		$follow = (strpos($parsed_feed['top']['link'], home_url()) !== false);

		if ( $max_items > 0 && isset( $parsed_feed['items'] ) && is_array( $parsed_feed['items'] ) && count( $parsed_feed['items'] ) ) {
			$widget_html .= '<div style="margin-top:10px;float:left"><div style="line-height:1;margin-bottom:10px">' . __( 'Discovered/Renewed Today:',
					Virus_Activity_Plugin::WIDGET_NAME ) . '</div>';
			$widget_html .= '<ul>';
			foreach ( $parsed_feed['items'] as $item ) {
				if ( ! empty( $item['title'] ) && ! empty( $item['link'] ) ) {
					$widget_html .= '<li><a target="_blank" rel="noopener' . (! $follow ? ' nofollow' : '') .'" href="' . $item['link'] . '">' . $item['title'] . '</a></li>';
				}
			}
			$widget_html .= '</ul>';
			$widget_html .= '</div>';
		}

		if ( ! empty( $parsed_feed ) && ! empty( $parsed_feed['items'] ) ) {
			$widget_html .= '<div style="margin-top:5px;clear:both;float:left">' . __( 'Most Dangerous Today:',
					Virus_Activity_Plugin::WIDGET_NAME );
			$widget_html .= ' <a target="_blank" rel="noopener' . (! $follow ? ' nofollow' : '') .'" href="' . $parsed_feed['top']['link'] . '">' . $parsed_feed['top']['title'] . '</a>';
			$widget_html .= '</div>';
		}

		if ( ! empty( $this->config['widget_more_link'] ) && ! empty( $instance['show_widget_more_link'] ) ) {
			$widget_html .= '<div style="margin-top:5px;clear:both;float:left"><a rel="noopener nofollow" target="_blank" href="' . $this->config['widget_more_link'] . '">' . __( 'Get this widget',
					Virus_Activity_Plugin::WIDGET_NAME ) . '&nbsp;&raquo;</a></div>';
		}

		// clear floats
		$widget_html .= '<div style="height:0;clear:both"></div>';

		$widget_html .= ! empty( $args['after_widget'] ) ? $args['after_widget'] : ''; // Widget magic
		echo $widget_html;
	}

	/**
	 * @return array
	 */
	private function get_meter_matrix() {
		$matrix = array(
			2 => array(
				'color' => $this->config['level_colors']['low'],
				'text'  => 'Low'
			),
			4 => array(
				'color' => $this->config['level_colors']['medium'],
				'text'  => 'Increased'
			),
			6 => array(
				'color' => $this->config['level_colors']['high'],
				'text'  => 'High'
			),
		);

		return $matrix;
	}

	/**
	 * @param $matrix
	 * @param $activity_level
	 *
	 * @return mixed
	 */
	private function get_meter_index( $matrix, $activity_level ) {
		$keys          = array_keys( $matrix );
		$highest_level = end( $keys );
		$index         = reset( $keys );

		foreach ( $keys as $key ) {
			if ( (int) $activity_level <= $key ) {
				$index = $key;
				break;
			}
		}

		if ( $activity_level > $highest_level ) {
			$index = $highest_level;
		}

		return $index;
	}

	/**
	 * This method forms html meter with colors according given $activity_level (int)
	 *
	 * @param $activity_level
	 *
	 * @return string
	 */
	private function get_meter_html( $activity_level ) {
		$matrix = $this->get_meter_matrix();
		$index  = $this->get_meter_index( $matrix, $activity_level );

		$meter_html = '';
		for ( $i = 0; $i < 6; $i ++ ) {
			$meter_html .= '<span style="display:inline-block;height:' . ( 2 + ( $i + 1 ) * 3 ) . 'px;background:' . ( $i + 1 <= $activity_level || $i < 1 ? $matrix[ $index ]['color'] : $this->config['level_colors']['default'] ) . ';margin-right:1px;width:7px"></span>';
		}

		return $meter_html;
	}

	/**
	 * @param $activity_level
	 *
	 * @return string
	 */
	private function get_level_text( $activity_level ) {
		$matrix = $this->get_meter_matrix();
		$index  = $this->get_meter_index( $matrix, $activity_level );

		return __( $matrix[ $index ]['text'], Virus_Activity_Plugin::WIDGET_NAME );
	}
}

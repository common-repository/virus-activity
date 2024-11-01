<?php
defined( 'ABSPATH' ) or die( 'Access denied' );

/**
 * Virus_Activity Class
 * User: virusactivity
 * Date: 23/02/2016
 * Time: 16:02
 */
class Virus_Activity {

	public $activity_feed = null;
	public $activity_level = null;
	private $cache_lifetime = 10800; // 3 hours
	private $locale = null;
	private $items_count = null;

	public function __construct() {
	}

	public function set_cache_lifetime( $time ) {
		$this->cache_lifetime = (int) $time;

		return $this->cache_lifetime;
	}

	public function get_cache_lifetime() {
		return (int) $this->cache_lifetime;
	}

	public function set_locale( $locale ) {
		return $this->locale = $locale;
	}

	public function get_locale() {
		return $this->locale;
	}

	public function set_items_count( $count ) {
		return $this->items_count = $count;
	}

	public function get_items_count() {
		return $this->items_count;
	}

	public static function speed_up_feeds( $interval, $url ) {
		$s = Virus_Activity_Plugin::get_widget_urls();
		if ( isset( $s[ $url ] ) ) {
			return $s[ $url ];
		}

		return $interval;
	}

	public static function set_feed_options( $feedObject, $url ) {
		$s = Virus_Activity_Plugin::get_widget_urls();
		if ( $feedObject instanceof SimplePie && isset( $s[ $url ] ) ) {
			$feedObject->set_timeout( 10 );
		}
	}

	public function get_feed_url() {
		$config = Virus_Activity_Plugin::get_config();
		if ( empty( $config['feed_url'] ) ) {
			return false;
		}
		$up       = parse_url( $config['feed_url'] );
		$hasQuery = ! empty( $up['query'] ) ? true : false;

		$url = $config['feed_url'] . ( $hasQuery ? '&' : '?' ) . http_build_query( array(
				'locale'      => $this->get_locale(),
				'items_count' => $this->get_items_count(),
				'url'         => get_site_url(),
				'check_sum'   => md5(
					$this->get_locale()
					. $this->get_items_count()
					. get_site_url()
					. ( ! empty( $config['version_salt'] )
						? $config['version_salt'] : ''
					) ),
			) );

		return $url;
	}

	/**
	 *
	 *  Tries to fetch feed, if fails, return previous feed saved in wp_options
	 * @return bool|array
	 */
	public function load_feed( $max_items ) {
		$feed = $this->fetch_feed();
		if ( $feed instanceof SimplePie ) {
			$this->activityFeed = $feed;
			$parsedFeed         = $this->parse_feed( $max_items );
		}
		if ( ! empty( $parsedFeed ) ) {
			$this->set_next_fetch_time( 0 ); // sets next fetch time to 0 - no wait time
			update_option( Virus_Activity_Plugin::WIDGET_CLASS . '_lastFeed', $parsedFeed );
		} else {
			$parsedFeed = get_option( Virus_Activity_Plugin::WIDGET_CLASS . '_lastFeed' );
		}

		return $parsedFeed;
	}

	public function is_next_fetch_time() {
		$next_time = (int) get_option( Virus_Activity_Plugin::WIDGET_CLASS . '_nextFeedFetchTime' );

		return $next_time < time();
	}

	public function set_next_fetch_time( $seconds = 300 ) {
		if ( $seconds ) {
			$number_of_tries = get_option( Virus_Activity_Plugin::WIDGET_CLASS . '_numberOfTries', 0 );
			if ( $number_of_tries < 10 ) {
				$number_of_tries ++;
			}
			update_option( Virus_Activity_Plugin::WIDGET_CLASS . '_numberOfTries', $number_of_tries );
			$next_fetch_time = time() + (int) ( ( $number_of_tries ) * $seconds );
			if ( get_option( Virus_Activity_Plugin::WIDGET_CLASS . '_nextFeedFetchTime', false ) === false ) {
				add_option( Virus_Activity_Plugin::WIDGET_CLASS . '_nextFeedFetchTime', $next_fetch_time );
			} else {
				update_option( Virus_Activity_Plugin::WIDGET_CLASS . '_nextFeedFetchTime', $next_fetch_time );
			}
		} else {
			add_option( Virus_Activity_Plugin::WIDGET_CLASS . '_nextFeedFetchTime', 0 );
			update_option( Virus_Activity_Plugin::WIDGET_CLASS . '_numberOfTries', 0 );
		}
	}

	public function fetch_feed() {
		if ( $this->is_next_fetch_time() ) {
			$url                 = $this->get_feed_url();
			$this->activity_feed = fetch_feed( $url );
		} else {
			return false;
		}
		if ( is_wp_error( $this->activity_feed ) ) {
			$this->set_next_fetch_time(); // sets default wait time

			return false;
		}

		return $this->activity_feed;
	}

	public function parse_feed( $max_items ) {
		$config = Virus_Activity_Plugin::get_config();
		if ( stripos( $config['feed_url'], $this->get_feed_channel_tag( 'webMaster' ) ) === false ) {
			return false;
		}
		$activity_level                = $this->get_level();                  // gets virus activity level from feed fetched above
		$most_dangerous_entry          = $this->get_most_dangerous_today();       // gets most dangerous today from feed fetched above
		$feed_items                    = $this->get_activity( $max_items );  // gets virus activity items from feed fetched above
		$parsed_feed                   = array();
		$parsed_feed['activity_level'] = $activity_level;
		if ( $most_dangerous_entry instanceof SimplePie_Item ) {
			$parsed_feed['top'] = array(
				'title' => $most_dangerous_entry->get_title(),
				'link'  => $most_dangerous_entry->get_permalink()
			);
		}
		$parsed_feed['items'] = array();
		if ( $max_items > 0 && is_array( $feed_items ) && count( $feed_items ) ) {
			foreach ( $feed_items as $feed_item ) {
				if ( $feed_item instanceof SimplePie_Item ) {
					array_push( $parsed_feed['items'], array(
						'title' => $feed_item->get_title(),
						'link'  => $feed_item->get_permalink()
					) );
				}
			}
		}

		return $parsed_feed;
	}

	private function get_feed_channel_tag( $tag ) {
		if ( $this->activity_feed instanceof SimplePie ) {
			$data = $this->activity_feed->get_channel_tags( '', $tag );
			if ( is_array( $data ) ) {
				$data = reset( $data );
			}
			if ( is_array( $data ) ) {
				$data = reset( $data );
			}

			return $data;
		} else {
			return false;
		}
	}

	public function get_level() {
		return array(
			'level' => $this->get_feed_channel_tag( 'level' ),
			'date'  => $this->get_feed_channel_tag( 'date' ),
		);

	}

	public function get_most_dangerous_today() {
		if ( $this->activity_feed instanceof SimplePie ) {
			return $this->activity_feed->get_item( 0 );
		} else {
			return false;
		}
	}

	public function get_activity( $limit = 0 ) {
		if ( ! ( $this->activity_feed instanceof SimplePie ) ) {
			return false;
		}
		$items_count = $this->activity_feed->get_item_quantity( $limit );
		if ( $items_count ) {
			return $this->activity_feed->get_items( 1, $items_count );
		} else {
			return false;
		}
	}
}

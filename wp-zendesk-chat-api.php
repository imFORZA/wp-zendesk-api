<?php
/**
 * WP ZenDesk Chat API (https://developer.zendesk.com/rest_api/docs/chat/introduction)
 *
 * @package WP-ZD-Chat-API
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }


if ( ! class_exists( 'WPZendeskChatAPI' ) ) {

	/**
	 * Seny API Class.
	 */
	class WPZendeskChatAPI extends WpLibrariesBase {

		private $username;

		/**
		 * Install Name.
		 *
		 * @var string
		 */
		protected $base_uri;

		/**
		 * API Key.
		 *
		 * @var string
		 */
		private $api_key;

		protected $args;

		protected $is_debug;


		/**
		 * __construct function.
		 *
		 * @access public
		 * @param mixed $install_name Install Name.
		 * @param mixed $api_key API Key.
		 * @return void
		 */
		public function __construct( $domain, $username, $api_key, $debug = false ) {
			$this->base_uri = "https://$domain.zendesk.com/api/v2/chats";
			$this->username = $username;
			$this->api_key = $api_key;
			$this->is_debug = $debug;
		}

		protected function set_headers() {
			$this->args['headers'] = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key ),
			);
		}

		protected function run( $route, $args = array(), $method = 'GET', $add_data_type = true ) {
			return $this->build_request( $route . ($add_data_type ? '.json' : ''), $args, $method )->fetch();
		}

		protected function clear() {
			$this->args = array();
		}

		/**
		 * Function for building zendesk pagination.
		 *
		 * @param  integer $per_page   [description]
		 * @param  integer $page       [description]
		 * @param  string  $sort_by    [description]
		 * @param  string  $sort_order [description]
		 * @return [type]              [description]
		 */
		public function build_zendesk_pagination( $limit = 200, $since_id = -1, $max_id = -1 ) {
			$args = array(
				'limit' => $limit,
			);

			if ( -1 !== $since_id ) {
				$args['since_id'] = $since_id;
			}

			if ( -1 !== $max_id ) {
				$args['max_id'] = $max_id;
			}

			return $args;
		}
	}
}

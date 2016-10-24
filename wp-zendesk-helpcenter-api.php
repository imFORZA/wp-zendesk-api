<?php
/**
 * WP ZenDesk HelpCenter API
 *
 * @package WP-ZD-HelpCenter-API
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) { exit; }


if ( ! class_exists( 'ZendeskHelpCenterAPI' ) ) {

	/**
	 * Seny API Class.
	 */
	class ZendeskHelpCenterAPI {

		/**
		 * Install Name.
		 *
		 * @var string
		 */
		static private $install_name;

		/**
		 * API Key.
		 *
		 * @var string
		 */
		static private $api_key;

		/**
		 * URL to the API.
		 *
		 * @var string
		 */
		private $base_uri = 'https://' . static::$install_name . '.zendesk.com';


		/**
		 * __construct function.
		 *
		 * @access public
		 * @param mixed $install_name Install Name.
		 * @param mixed $api_key API Key.
		 * @return void
		 */
		public function __construct( $install_name, $api_key ) {

			static::$install_name = $install_name;
			static::$api_key = $api_key;

		}

		/**
		 * Fetch the request from the API.
		 *
		 * @access private
		 * @param mixed $request Request URL.
		 * @return $body Body.
		 */
		private function fetch( $request ) {

			$response = wp_remote_get( $request );
			$code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== $code ) {
				return new WP_Error( 'response-error', sprintf( __( 'Server response code: %d', 'text-domain' ), $code ) );
			}

			$body = wp_remote_retrieve_body( $response );

			return json_decode( $body );
		}

		/**
		 * list_categories function.
		 *
		 * @access public
		 * @param string $locale (default: '')
		 * @param string $sort_by (default: '')
		 * @param string $sort_order (default: '')
		 * @return void
		 */
		public function list_categories( $locale = '', $sort_by = '', $sort_order = '') {

			$request = $this->base_uri . '/api/v2/help_center/' . $locale . '/categories.json';

			return $this->fetch( $request );

		}

	}
}

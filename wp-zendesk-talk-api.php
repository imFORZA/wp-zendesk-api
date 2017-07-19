<?php
/* Check if Class Exists. */
if ( ! class_exists( 'Zendesk_Talk_Wordpress_API' ) ) {


	/**
	 * Zendesk_Talk_Wordpress_API class.
	 */
	class Zendesk_Talk_Wordpress_API {


		/**
		 * api_url
		 *
		 * (default value: '')
		 *
		 * @var string
		 * @access private
		 */
		private $api_url = '';


		/**
		 * username
		 *
		 * (default value: false)
		 *
		 * @var bool
		 * @access private
		 */
		private $username = false;


		/**
		 * password
		 *
		 * (default value: false)
		 *
		 * @var bool
		 * @access private
		 */
		private $password = false;

		/**
		 * api_key
		 *
		 * (default value: false)
		 *
		 * @var bool
		 * @access private
		 */
		private $api_key = false;


		/**
		 * __construct function.
		 *
		 * The only parameter is the API url, together with the protocol.
		 * (generally http or https). The trailing slash is appended during.
		 * API calls if one doesn't exist.
		 *
		 * @access public
		 * @param mixed  $subdomain Subdomain.
		 * @param string $email (default: '') Email.
		 * @param string $api_key (default: '') API Key.
		 * @return void
		 */
		public function __construct( $subdomain, $email = '', $api_key = '' ) {

			$this->api_url = 'https://' . $subdomain . '.zendesk.com/api/v2';

			if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
				$this->cache_timeout               = 60;
				$this->cache_timeout_views         = 60 * 60;
				$this->cache_timeout_ticket_fields = 60 * 60;
				$this->cache_timeout_user          = 60 * 60;
			} else {
				$this->cache_timeout               = 5;
				$this->cache_timeout_views         = 5;
				$this->cache_timeout_ticket_fields = 5;
				$this->cache_timeout_user          = 5;
			}

			if ( $email != '' && $api_key != '' ) {
				$this->username = $email;
				$this->api_key = $api_key;
			}

		}
	}



} // end if.

<?php
/*
 * Plugin Name: WP Zendesk API
 * Plugin URI: https://github.com/wp-api-libraries/wp-zendesk-api
 * Description: Perform API requests to Zendesk in WordPress.
 * Author: WP API Libraries
 * Version: 1.0.0
 * Author URI: https://wp-api-libraries.com
 * GitHub Plugin URI: https://github.com/wp-api-libraries/wp-zendesk-api
 * GitHub Branch: master
 */


/* Check if Class Exists. */
if ( ! class_exists( 'Zendesk_Wordpress_API' ) ) {


	/**
	 * Zendesk_Wordpress_API class.
	 */
	class Zendesk_Wordpress_API {


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

			if ( ! defined( 'ZENDESK_USER_AGENT' ) ) {
				define( 'ZENDESK_USER_AGENT', '' );
			}
		}

		/**
		 * Authentication
		 *
		 * Grabs the $username and $password and stores them in its own
		 * private variables. If the $validate argument is set to true
		 * (default behaviour) a call to the Zendesk API is issued to
		 * validate the current user's credentials.
		 *
		 * This method is public and returns true or false upon authentication
		 * success or failure.
		 *
		 * @access public
		 * @param mixed $username
		 * @param mixed $password
		 * @param bool $validate (default: true)
		 * @return void
		 */
		public function authenticate( $username, $password, $validate = true ) {
			$this->username = $username;
			$this->password = $password;

			$this->api_key = false;

			if ( $validate ) {
				$result = $this->_get( 'users/me.json' );
				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					$user_data = json_decode( $result['body'] );
					$user_data = $user_data->user;

					if ( is_null( $user_data->id ) ) {
						return $this->auth_error();
					}

					return $user_data;

				} else {
					return $this->auth_error();
				}
			} else {
				return true;
			}
		}

	 /*
		* Authentication Helper
		* set username and password to false
		* return new WP_Error
   		*/
		private function auth_error() {
			$this->username = false;
			$this->password = false;

			return new WP_Error( 'zendesk-api-error', __( 'We could not authenticate you with Zendesk, please try again!', 'wp-zendesk-api' ) );
		}

		/*
		 * Use SSL
		 *
		 * Determines whether the given Zendesk account is set to use
		 * SSL. Fires a HEAD request to home.json via HTTPS and watches
		 * the response for a 302 redirect. If a redirect occurs, then
		 * there's no SSL, otherwise SSL is turned on.
		 *
		 * Works well with: cURL, PHP Streams, fsockopen
		 * @todo: Doesn't work with: fopen
		 *
		 *
		 * @access public
		 * @param mixed $account
		 * @return void
		 */
		public function is_ssl( $account ) {
			$headers = array( 'Content-Type' => 'application/json' );
			$result  = wp_remote_head( trailingslashit( 'https://' . $account . '.zendesk.com' ) . 'home.json', array( 'headers' => $headers ) );

			// Let's see if there was a redirect
			if ( ! is_wp_error( $result ) && $result['response']['code'] == 302 ) {
				return false;
			} else {
				return true;
			}
		}

		/* SEARCH */

		/**
		 * Search for Tickets, Users, or Orgs with varying params
		 * https://developer.zendesk.com/rest_api/docs/core/search
		 *
		 * @param  string $type           Type to search for {'user', 'ticket', 'organization'}
		 * @param  string $status        Status of object to check
		 * @return [type]                   [description]
		 */
		public function search( $type, $status ) {

		}

		public function get_user_id_by_email( $email ){
			$result = $this->_get( 'users/search.json?query=' . $email );

			// return $result;
			return $this->checker( $result, '' );
		}


		/* TICKETS */

		// https://developer.zendesk.com/rest_api/docs/core/tickets#list-tickets
		/**
		 * list_tickets function.
		 *
		 * @access public
		 * @param int $per_page (default: 100)
		 * @param int $page (default: 1)
		 * @return void
		 */
    public function list_tickets( $per_page = 100, $page = 1, $sort = '' ){
      $request = 'tickets.json';

      if ( $per_page != 100 ) {
        $request .= '?per_page=' . $per_page;

        if( $page != 1 ){
          $request .= '&page='.$page;
        }

        if( $sort != '' ){
          $request .= '&sort_by='.$sort;
        }
      }else if( $page != 1 ){
        $request .= '?page='.$page;

        if( $sort != '' ){
          $request .= '&sort_by='.$sort;
        }
      }
      if( $sort != '' ){
        $request .= '?sort_by='.$sort;
      }


			$result = $this->_get( $request );

			return $this->checker( $result, __( 'Tickets cannot be accessed right now.', 'wp-zendesk-api' ) );
		}

		public function get_tickets_by_email( $email ){
			$result = $this->_get( 'search.json?query=type:ticket requester:' . $email );

			return $this->checker( $result, '' );
		}

		public function get_tickets_by_user_id( $user_id ){
			$result = $this->_get( 'search.json?query=type:ticket requester:' . $user_id );

			return $this->checker( $result, '' );
		}

		// https://developer.zendesk.com/rest_api/docs/core/tickets#show-ticket
		/**
		 * show_ticket function.
		 *
		 * @access public
		 * @param mixed $ticket_id
		 * @return void
		 */
		public function show_ticket( $ticket_id ) {

			$result = $this->_get( 'tickets/' . $ticket_id . '.json' );

			return $this->checker( $result, __( 'That ticket cannot be accessed right now.', 'wp-zendesk-api' ) );
		}


		// https://developer.zendesk.com/rest_api/docs/core/tickets#show-multiple-tickets
		// Show tickets based on array of IDs. Returns a specific number of tickets.
		/**
		 * show_tickets function.
		 *
		 * @access public
		 * @param mixed $ids
		 * @return void
		 */
		public function show_tickets( $ids ) {

			$result = $this->_get( 'tickets/show_many.json?ids=' . implode( $ids, ',' ) );

			return $this->checker( $result, __( 'Tickets cannot be accessed right now.', 'wp-zendesk-api' ) );
		}

		/**
		 * Create Ticket
		 *
		 * Creates a new ticket given the $subject and $description. The
		 * new ticket is authored by the currently set user, i.e. the
		 * credentials stored in the private variables of this class.
		 *
		 * @param $tags array of tags.
		 *
		 * @return int ID of ticket after submission
		 */
		public function create_ticket( $subject, $description, $requester_name = '', $requester_email = '', $tags = '', $channel = '' ) {
			$ticket = array(
				'ticket' => array(
					'subject' => $subject,
					'comment' => array(
						'body' => $description,
					),
				),
			);

			if ( $requester_name != '' && $requester_email != '' ) { // eh... might wanna make this an or? iunno good enough.
				$ticket['ticket']['requester'] = array(
					'name'  => $requester_name, // not really that important of a field tbh.
					'email' => $requester_email,
				);
			}

			if( $tags != '' ){
				$ticket['ticket']['tags'] = implode(',', $tags);
			}

			if( $channel != '' ){
				$ticket['ticket']['via']['channel'] = $channel;
			}

			error_log(print_r( $ticket, true ));

			$result = $this->_post( 'tickets.json', $ticket );

			error_log(print_r( $result, true ));

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 201 ) {
				$location = $result['headers']['location'];
				preg_match( '/\.zendesk\.com\/api\/v2\/tickets\/([0-9]+)\.(json)/i', $location, $matches ); // cute, looks for url of thing created

				if ( isset( $matches[1] ) ) {
					return $matches[1];
				}
			}
			return new WP_Error( 'zendesk-api-error', __( 'A new ticket could not be created at this time, please try again later.', 'wp-zendesk-api' ) );

		}

		/**
		 * create_tickets function.
		 * https://developer.zendesk.com/rest_api/docs/core/tickets#create-many-tickets
		 *
		 * @access public
		 * @param mixed $ticket_objs
		 * @return void
		 */
		public function create_tickets( $ticket_objs ) {

		}

		/**
		 * update_ticket function.
		 * https://developer.zendesk.com/rest_api/docs/core/tickets#update-ticket
		 *
		 * @access public
		 * @param mixed $ticket_id
		 * @param mixed $args
		 * @return void
		 */
		public function update_ticket( $ticket_id, $args ) {

			$result = $this->_put( 'tickets/' . $ticket_id . '.json', $args );

			return $this->checker( $result, __( 'Tickets cannot be modified right now.', 'wp-zendesk-api' ) );
		}

		/**
		 * delete_ticket function.
		 * https://developer.zendesk.com/rest_api/docs/core/tickets#delete-ticket
		 *
		 * @access public
		 * @param mixed $ticket_id
		 * @return void
		 */
		public function delete_ticket( $ticket_id ) {

			$result = $this->_delete( 'tickets/' . $ticket_id . '.json' );

			return $this->checker( $result, __( 'Ticket cannot be deleted right now.', 'wp-zendesk-api' ) );
		}

		/**
		 * delete_tickets function.
		 * https://developer.zendesk.com/rest_api/docs/core/tickets#bulk-delete-tickets
		 *
		 * @access public
		 * @param mixed $ticket_ids
		 * @return void
		 */
		public function delete_tickets( $ticket_ids ) {

			$result = $this->_delete( 'tickets/destroy_many.json?ids=' . implode( $ticket_ids, ',' ) );

			return $this->checker( $result, __( 'Tickets cannot be deleted right now.', 'wp-zendesk-api' ) );
		}

		/* REQUESTS */

		/*
		* Get Requests
		*
		* Similar to the function above but used for end-users to return
		* all open requests. Returns a WP_Error if requests could not be
		* fetched. Uses the Transient API for caching results.
		*
		*/
		public function list_requests() {
			$transient_key = $this->_salt( 'requests' );

			if ( false == ( $requests = get_transient( $transient_key ) ) ) {
				$result = $this->_get( 'requests.json' );

				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					$requests = json_decode( $result['body'] );
					$requests = $requests->requests;
					set_transient( $transient_key, $requests, $this->cache_timeout );

					return $requests;
				} else {
					return new WP_Error( 'zendesk-api-error', __( 'The requests could not be fetched at this time, please try again later.', 'wp-zendesk-api' ) );
				}
			}

			// Serving from cache
			return $requests;
		}

		/**
		 * show_request function.
		 *
		 * @access public
		 * @param mixed $request_id
		 * @param bool  $altauth (default: false)
		 * @return void
		 */
		public function show_request( $request_id, $altauth = false ) {
			$result = $this->_get( 'requests/' . $request_id . '.json', array(), $altauth );

			return $this->checker( $result, __( 'Request cannot be accessed right now.', 'wp-zendesk-api' ), true );
		}

		/*
		* Create Request
		*
		* Same as the method above, but instead of tickets.json, requests.json
		* is called. Used to create tickets by non-admin and non-agent users
		* (based on their role, where 0 is generally end-users).
		*
		*/
		public function create_request( $subject, $description, $requester_id = '' ) {
			$request = array(
				'request' => array(
					// "requester" => array( "name" => "Anonymous customer" ),
					'subject' => $subject,
					'description' => $description,
				),
			);

			if( $requester_id !== '' ){
				//$request['request']['requester_id'] = $requester_id;
			}

			$headers = array();

			$result = $this->_post( 'requests.json', $request, $headers );

			/*
			* @todo: requests.json returns a 406 for end-users instead of
			* the expected 201. Should probably fix this in future update,
			* related issue: #23 Temporary fix is to allow 406's.
			*/
			return $result;
			return $this->checker( $result, __( 'A new request could not be created at this time, please try again later.', 'wp-zendesk-api' ), true );
		}

		// https://developer.zendesk.com/rest_api/docs/core/requests#update-request
		// Used to add a comment to a request. Can also update the solved status
		// Hmmm, status doesn't appear to work. Weird.
		public function update_request( $request_id, $comment, $status = '' ) {
			$request = array(
				'request' => array(
					'comment' => array(
						'body' => $comment,
					),
				),
			);

			if ( $status != '' ) {
				$request['request']['status'] = $status;
			}

			$result = $this->_put( 'requests/' . $request_id . '.json', $request );

			return $this->checker( $result, __( 'A comment could not be added to this request at this time, please try again later.', 'wp-zendesk-api' ), true );
		}

		public function get_requests_by_user( $email, $altauth = false ) {
			$result = $this->_get( 'requests/search.json?query=' . urlencode( 'requester:' . $email ) , array(), $altauth );

			return $this->checker( $result, __( 'Requests for this user could not be queried at this time, please try again later.', 'wp-zendesk-api' ) );
		}

		/* TICKET COMMENTS */

		/*
		* Create Comment
		*
		* Creates a comment to the specified ticket with the specified text.
		* The $public argument, as the name suggests, tells Zendesk whether
		* this comment should be public or private.
		* FOR. TICKETS. Not requests.
		*
		*/
		public function create_comment( $ticket_id, $text, $public = true ) {
			$ticket = array(
				'ticket' => array(
					'comment' => array(
						'public' => $public,
						'body'   => $text,
					),
				),
			);

			$result = $this->_put( 'tickets/' . $ticket_id . '.json', $ticket );

			return $this->checker( $result, __( 'A new comment could not be created at this time, please try again later.', 'wp-zendesk-api' ), true );
		}

		public function create_comment_request( $request_id, $text ){
			$request = array(
				'request' => array(
					'comment' => array(
						'body'   => $text,
					),
				),
			);

			$result = $this->_put( 'requests/' . $request_id . '.json', $request );

			return $this->checker( $result, __( 'A new comment could not be created at this time, please try again later.', 'wp-zendesk-api' ), true );
		}

		/*
		* Get Ticket Comments
		*
		* Asks the Zendesk API for the comments on a certain ticket, provided
		* the ticket id in the arguments. If the ticket was not found or is
		* inaccessible by the current user, returns a WP_Error. Values are
		* not cached.
		*
		*/
		public function list_comments( $ticket_id ) {
			$result = $this->_get( 'tickets/' . $ticket_id . '/comments.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$comments = json_decode( $result['body'] );
				$comments = $comments->comments;

				return $comments;
			} else {
				return new WP_Error( 'zendesk-api-error', __( 'Could not fetch the comments at this time, please try again later.', 'wp-zendesk-api' ), array( 'status' => $result['response']['code'] ) );
			}

			return $comments;
		}

		/*
		* Get Views
		*
		* Returns an array of available views with their IDs, titles,
		* ticket counts and more. If for some reason views cannot be
		* fetched, returns a WP_Error object. Caching is enabled via
		* the Transient API.
		*
		*/
		public function get_views() {
			$transient_key = $this->_salt( 'views' );

			if ( false === ( $views = get_transient( $transient_key ) ) ) {

				$result = $this->_get( 'views.json' );

				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					$views = json_decode( $result['body'] );
					$views = $views->views;

					set_transient( $transient_key, $views, $this->cache_timeout_views );

					return $views;

				} else {

					if ( is_wp_error( $result ) ) {
						return new WP_Error( 'zendesk-api-error', __( 'The active views could not be fetched at this time, please try again later.', 'wp-zendesk-api' ) );
					} elseif ( $result['response']['code'] == 403 ) {
						return new WP_Error( 'zendesk-api-error', __( 'Access denied You do not have access to this view.', 'wp-zendesk-api' ) );
					}
				}
			}

			// Serving from cache
			return $views;
		}

		/*
		 * Get Tickets from View
		 *
		 * Returns an array of tickets for a specific view given in the
		 * $view_id argument. If such a view does not exist or an error
		 * has occured, this method returns a WP_Error. Caching in this
		 * method is enabled through WordPress transients.
		 *
		 * @access public
		 * @param mixed $view_id
		 * @return void
		 */
		public function get_tickets_from_view( $view_id ) {

			$result = $this->_get( 'views/' . $view_id . '/tickets.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$tickets = json_decode( $result['body'] );
				$tickets = $tickets->tickets;

				return $tickets;
			} else {
				return new WP_Error( 'zendesk-api-error', __( 'The tickets for this view could not be fetched at this time, please try again later.', 'wp-zendesk-api' ) );
			}
		}

		/*
		 * Get Ticket Info
		 *
		 * Asks the Zendesk API for details about a certain ticket, provided
		 * the ticket id in the arguments. If the ticket was not found or is
		 * inaccessible by the current user, returns a WP_Error. Values are
		 * not cached.
		 *
		 *
		 * @access public
		 * @param mixed $ticket_id
		 * @return void
		 */
		public function get_ticket_info( $ticket_id ) {
			$transient_key = $this->_salt( 'ticket-' . $ticket_id );

			if ( false === ( $ticket = get_transient( $transient_key ) ) ) {
				$result = $this->_get( 'tickets/' . $ticket_id . '.json' );

				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					$ticket = json_decode( $result['body'] );
					$ticket = $ticket->ticket;
					set_transient( $transient_key, $ticket, $this->cache_timeout );

					return $ticket;
				} else {
					return new WP_Error( 'zendesk-api-error', __( 'Could not fetch the ticket at this time, please try again later.', 'wp-zendesk-api' ) );
				}
			}

			// Serving from cache.
			return $ticket;
		}

		/*
		 * Get Request Info
		 *
		 * Similar to the method above but asks for the request info, available
		 * to the end-users. If the request was not found, returns a WP_Error.
		 * Value is not cached.
		 *
		 * @access public
		 * @param mixed $ticket_id
		 * @return void
		 */
		public function get_request_info( $ticket_id ) {
			$transient_key = $this->_salt( 'request-' . $ticket_id );

			if ( false === ( $request = get_transient( $transient_key ) ) ) {

				$result = $this->_get( 'requests/' . $ticket_id . '.json' );

				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					$request = json_decode( $result['body'] );
					set_transient( $transient_key, $request, $this->cache_timeout );

					return $request;
				} else {
					return new WP_Error( 'zendesk-api-error', __( 'Could not fetch the request at this time, please try again later.', 'wp-zendesk-api' ) );
				}
			}

			// Serving from cache.
			return $request;
		}

		/* USERS */

		/**
		 * list_users function.
		 *
		 * @access public
		 * @param string $id (default: '')
		 * @param bool   $is_group (default: true)
		 * @param string $page (default: '')
		 * @return void
		 */
		public function list_users( $id = '', $is_group = true, $page = '' ) {
			$result;
			if ( $page != '' ) {
				$page = '?page=' . $page;
			}
			if ( $id != '' ) {
				if ( $is_group ) {
					$result = $this->_get( 'groups/' . $id . '/users.json' . $page );
				} else {
					$result = $this->_get( 'organizations/' . $id . '/users.json' . $page );
				}
			} else {
				$result = $this->_get( 'users.json' . $page );
			}

			return $this->checker( $result, __( 'Users cannot be accessed right now.', 'wp-zendesk-api' ) );
		}

		/*
		 * Get User Details
		 *
		 * Asks the Zendesk API for the details about a specific user. Input
		 * argument is the user id which is sometimes present in the tickets
		 * details. User objects are cached using the Transient API.
		 *
		 *
		 * @access public
		 * @param mixed $user_id
		 * @param bool $altauth (default: false)
		 * @return void
		 */
		public function show_user( $user_id, $altauth = false ) {
			$result = $this->_get( 'users/' . $user_id . '.json', array(), $altauth );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$user = json_decode( $result['body'] );
				return $user;
			} else {
				return $result;
				return new WP_Error( 'zendesk-api-error', __( 'The requested user details could not be fetched at this time, please try again later.', 'wp-zendesk-api' ) );
			}
		}

		/**
		 * show_users function.
		 *
		 * @access public
		 * @param mixed $user_ids
		 * @return void
		 */
		public function show_users( $user_ids ) {
			$result = $this->_get( 'users/show_many.json?ids=' . implode( $user_ids, ',' ) );

			return $this->checker( $result, __( 'Tickets cannot be accessed right now.', 'wp-zendesk-api' ) );
		}


		/**
		 * get_user_info function.
		 *
		 * @access public
		 * @param mixed $user_id
		 * @return void
		 */
		public function get_user_info( $user_id ) {
			$result = $this->_get( 'users/' . $user_id . '/related.json' );

			return $this->checker( $result, __( 'Tickets cannot be accessed right now.', 'wp-zendesk-api' ) );
		}

		// https://developer.zendesk.com/rest_api/docs/core/users#create-user
		/**
		 * create_user function.
		 *
		 * @access public
		 * @param mixed $user
		 * @return void
		 */
		public function create_user( $user ) {
			$result = $this->_post( 'users.json', $user );

			return $result;
		}

		/**
		 * create_or_update_user function.
		 *
		 * @access public
		 * @param mixed $idk
		 * @return void
		 */
		public function create_or_update_user( $idk ) {

		}

		/**
		 * delete_user function.
		 *
		 * @access public
		 * @param mixed $user_id
		 * @return void
		 */
		public function delete_user( $user_id ) {
			$result = $this->_delete( "users/$user_id.json" );

			return $result;
		}

		/**
		 * update_user_profile_pic function.
		 *
		 * @access public
		 * @param mixed $idk
		 * @return void
		 */
		public function update_user_profile_pic( $idk ) {

		}

		/**
		 * set_user_password function.
		 *
		 * @access public
		 * @param mixed $user_id
		 * @param mixed $pass
		 * @return void
		 */
		public function set_user_password( $user_id, $pass ) {
			$result = $this->_post( 'users/' . $user_id . '/password.json', array( 'password' => $pass ) );

			return $this->checker( $result, __( 'Password cannot be set right now.', 'wp-zendesk-api' ) );
		}



		/* TICKET FIELDS */




		/**
		 * Get Ticket Fields
		 *
		 * Retrieves the ticket fields, used mostly for custom fields display
		 * in the tickets view widget in the dashboard.
		 *
		 * @access public
		 * @return void
		 */
		public function get_ticket_fields() {

			$transient_key = $this->_salt( 'ticket_fields' );

			if ( false === ( $fields = get_transient( $transient_key ) ) ) {
				$result = $this->_get( 'ticket_fields.json' );

				if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
					$fields = json_decode( $result['body'] );
					$fields = $fields->ticket_fields;
					set_transient( $transient_key, $fields, $this->cache_timeout_ticket_fields );

					return $fields;
				} else {
					if ( is_wp_error( $result ) ) {
						return new WP_Error( 'zendesk-api-error', __( 'The ticket fields could not be fetched at this time, please try again later.', 'wp-zendesk-api' ) );
					}
				}
			}

			// Serving from cache.
			return $fields;
		}



		/* SUSPENDED TICKETS. */

		/* TICKET AUDITS. */

		/* TICKET COMMENTS. */

		/* TICKET SKIPS. */

		/* TICKET METRICS. */

		/* TICKET METRIC EVENTS. */

		/* USERS. */

		/* USER IDENTITIES. */

		/* CUSTOM AGENT ROLES. */

		/* END USERS. */

		/* GROUPS. */

				/**
		 * list_groups function.
		 *
		 * @access public
		 * @return void
		 */
		public function list_groups() {
			$result = $this->_get( 'groups.json' );

			return $this->checker( $result, __( 'Users cannot be accessed right now.', 'wp-zendesk-api' ) );
		}

		/**
		 * Show Group.
		 *
		 * @access public
		 * @param mixed $group_id Group ID.
		 * @return void
		 */
		public function show_group( $group_id ) {
			$result = $this->_get( 'groups/' . $user_id . '.json' );

			if ( ! is_wp_error( $result ) && $result['response']['code'] == 200 ) {
				$user = json_decode( $result['body'] );
				set_transient( $transient_key, $user, $this->cache_timeout_user );

				return $user;
			} else {
				return new WP_Error( 'zendesk-api-error', __( 'The requested user details could not be fetched at this time, please try again later.', 'wp-zendesk-api' ) );
			}
		}

		/**
		 * Update Groups.
		 *
		 * @access public
		 * @param mixed $stuff Stuff.
		 * @return void
		 */
		public function update_groups( $stuff ) {

		}

		/**
		 * Delete Group.
		 *
		 * @access public
		 * @param mixed $group_id
		 * @return void
		 */
		public function delete_group( $group_id ) {

		}

		/* GROUP MEMBERSHIPS. */

		/* SESSIONS. */

		/* ORGANIZATIONS. */

		public function show_organization( $org_id = '' ){
			return $this->checker( $this->_get( 'organizations/' . $org_id . '.json' ), '');
		}

		public function list_organizations( $user_id = '', $page = '' ) {
			$result;
			$page;
			if ( $page != '' ) {
				$page = '?page=' . $page;
			}else{
				$page = '';
			}

			if( $user_id != '' ){
				$result = $this->_get( "users/$user_id/organizations.json" . $page );
			}else{
				$result = $this->_get( "organizations.json" . $page );
			}

			return $this->checker( $result, __( 'Users cannot be accessed right now.', 'wp-zendesk-api' ) );
		}

		public function delete_organizations( $org_id, $testmode = false ){

			if( $testmode ){
				return array( 'members' => $this->list_organization_memberships( $org_id ), 'id' => $org_id );
			}

			$result = $this->_delete( 'organizations/' . $org_id . '.json' );

			return $this->checker( $result, __( 'Org cannot be deleted right now.', 'wp-zendesk-api' ) );

		}

		/* ORGANIZATION SUBSCRIPTIONS. */

		/* ORGANIZATION MEMBERSHIPS. */

		public function list_organization_memberships( $org_id = '', $user_id = '', $page = '' ) {
			$result;
			$page;
			if ( $page != '' ) {
				$page = '?page=' . $page;
			}else{
				$page = '';
			}

			if( $user_id != '' ){
				$result = $this->_get( "users/$user_id/organization_memberships.json" . $page );
			}else if( $org_id != '' ){
				$result = $this->_get( "organizations/$org_id/organization_memberships.json" . $page );
			}else{
				$result = $this->_get( "organization_memberships.json" . $page );
			}

			return $this->checker( $result, __( 'Memberships cannot be accessed right now.', 'wp-zendesk-api' ) );

		}

		/* AUTOMATIONS. */

		/* MACROS. */

		/* SLA POLICIES. */

		/* TARGETS. */

		/* TRIGGERS. */

		/* VIEWS. */

		/* ACCOUNT SETTINGS. */

		/* AUDIT LOGS. */

		/* BRANDS. */

		/* DYNAMIC CONTENT. */

		public function list_dynamic_content_items() {

		}

		public function get_dynamic_content_item( $dc_item_id ) {

		}

		public function add_dynamic_content_item() {

		}

		public function update_dynamic_content_item( $dc_item_id ) {

		}

		public function delete_dynamic_content_item( $dc_item_id ) {

		}

		public function list_dc_item_variants() {

		}

		public function get_dc_item_variant() {

		}

		public function add_dc_item_variant() {

		}

		public function add_bulk_dc_item_variant() {

		}

		public function update_dc_item_variant() {

		}

		public function update_bulk_dc_item_variant() {

		}

		public function delete_dc_item_variant() {

		}

		/* LOCALES. */

		/* ORGANIZATION FIELDS. */

		/* SCHEDULES. */

		/* SHARING AGREEMENTS. */

		/* SUPPORT ADDRESSES. */

		/* TICKET FORMS. */

		public function get_ticket_forms() {

		}

		public function add_ticket_form() {

		}

		public function show_ticket_form() {

		}

		public function show_many_ticket_forms() {

		}

		public function update_ticket_form() {

		}

		public function delete_ticket_form() {

		}

		public function reorder_ticket_form() {

		}

		public function clone_existing_ticket_form() {

		}

		/* TICKET FIELDS. */



		/* USER FIELDS. */

		public function get_user_fields() {

		}

		public function get_user_field( $field_id ) {

		}

		public function add_user_field() {

		}

		public function update_user_field( $field_id ) {

		}

		public function update_dropdown_field( $field_id, $custom_field_options ) {

		}

		public function delete_user_field() {

		}

		public function reorder_user_field() {

		}

		public function list_user_field_options( $field_id ) {

		}

		public function show_user_field_option( $field_id, $option_id ) {

		}

		public function add_update_user_field_option( $field_id ) {

		}

		public function delete_user_field_option( $field_id, $option_id ) {

		}

		/* APPS. */

		public function upload_app_package() {

		}

		public function create_app() {

		}

		public function update_app() {

		}

		public function get_app_info() {

		}

		public function get_app_public_key() {

		}

		public function list_owned_apps() {

		}

		public function list_all_apps() {

		}

		public function delete_app( $app_id ) {

		}

		public function send_notification_to_app() {

		}

		public function list_app_installations() {

		}

		public function install_app() {

		}

		public function show_app_installation( $app_id ) {

		}

		public function update_app_installation( $app_id ) {

		}

		public function remove_app_installation( $app_id ) {

		}

		public function get_install_requirement_status( $app_id ) {

		}

		public function list_install_requirements( $app_id ) {

		}

		/* APP INSTALL LOCATIONS. */

		public function list_location_installations() {

		}

		public function reorder_app_install_for_location() {

		}

		/* APP LOCATIONS. */

		public function get_app_locations() {
			// GET apps/locations.json
		}

		public function get_app_location( $app_location_id ) {

		}

		/* OAUTH CLIENTS. */

		/* OAUTH TOKENS. */

		/* AUTHORIZED GLOBAL CLIENTS. */

		public function get_authorized_global_clients() {
			// GET oauth/global_clients.json
		}

		/* ACTIVITY STREAM. */

		public function list_activites() {

		}

		public function get_activity( $activity_id ) {

		}

		/* BOOKMARKS. */

		public function list_bookmarks() {
			// GET bookmarks.json
		}

		public function add_bookmark() {

		}

		public function delete_bookmark() {

		}

		/* JOB STATUSES. */

		public function get_job_statuses() {
			// GET job_statuses.json
		}

		public function get_job_status( $job_id ) {

		}

		public function get_bulk_job_status( $job_ids ) {

		}

		/* PUSH NOTIFICATION DEVICES. */

		public function bulk_unregister_push_notification_devices() {
			// POST push_notification_devices/destroy_many.json
		}

		/* RESOURCE COLLECTIONS. */

		public function get_resource_collections() {
			// GET resource_collections.json
		}

		public function get_resource_collection( $resource_collection_id ) {

		}

		public function add_resource_collection() {
			// POST resource_collections.json
		}

		public function update_resource_collection() {

		}

		public function delete_resource_collection() {

		}

		/* TAGS. */

		public function get_tags() {
			return $this->_get( 'tags.json' );
			// GET tags.json
		}

		public function get_tickets_tags( $ticket_id ) {
			return $this->_get( "/tickets/$ticket_id/tags.json");
			// GET tickets/{id}/tags.json
		}

		public function get_topics_tags( $topic_id ) {
			// GET topics/{id}/tags.json
		}

		public function get_org_tags( $org_id ) {
			// GET organizations/{id}/tags.json
		}

		public function get_user_tags( $user_id ) {
			// GET users/{id}/tags.json
		}

		public function set_ticket_tags( $ticket_id ) {

		}

		public function set_topic_tags( $topic_id ) {

		}

		public function set_org_tags( $org_id ) {

		}

		public function set_users_tags( $user_id ) {

		}

		public function add_ticket_tags( $ticket_id ) {

		}

		public function add_topic_tags( $topic_id ) {

		}

		public function add_org_tags( $org_id ) {

		}

		public function add_users_tags( $user_id ) {

		}

		public function remove_ticket_tags( $ticket_id ) {

			$request = array(
				'ticket' => array(
					'tags' => array(),
				),
			);

			$result = $this->_put( 'tickets/' . $ticket_id . '.json', $request );

			return $this->checker( $result, '' );

			// return $this->_delete( 'tickets/' . $ticket_id . '/tags.json' );
		}

		public function remove_topic_tags( $topic_id ) {
			return $this->checker( $this->_delete( 'topics/' . $topic_id . '/tags.json' ), '');
		}

		public function remove_org_tags( $org_id, $fix_domains = false ) {
			$request = array(
				'organization' => array(
					'tags' => array(),
				),
			);

			if( $fix_domains ) {

				$org = $this->show_organization( $org_id );

				$new_domains = array();

				$domains = $org->organization->domain_names;

				foreach( $domains as $domain ){
					if( !(preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain) ) ){
						array_push( $new_domains, parse_url( $domain, PHP_URL_HOST ) );
						pp( "Changing domain #$org_id from $domain" );
					}else{
						array_push( $new_domains, $domain );
					}
				}

				$request['organization']['domain_names'] = $new_domains;
			}

			$result = $this->_put( 'organizations/' . $org_id . '.json', $request );

			return $this->checker( $result, '' );
		}

		public function remove_users_tags( $user_id ) {
			return $this->_delete( 'users/' . $user_id . '/tags.json' );
		}

		public function get_autocomplete_tags( $name ) {
			// GET autocomplete/tags.json?name={name}
		}

		/* CHANNEL FRAMEWORK. */

		public function push_channel_framework() {
			// POST any_channel/push
		}

		/* TWITTER CHANNEL. */

		public function list_monitored_twitter_handles() {
			// GET channels/twitter/monitored_twitter_handles.json
		}

		public function get_monitored_twitter_handle( $twitter_monitor_handle_id ) {
			// GET channels/twitter/monitored_twitter_handles/{id}.json
		}

		public function create_ticket_from_tweet() {
			// POST channels/twitter/tickets.json
		}

		public function get_twicket_status( $twicket_id ) {
			// GET channels/twitter/tickets/{id}/statuses.json
		}


			/* HELPER FUNCTIONS */

		/**
		 * API Get.
		 *
		 * @access private
		 * @param mixed $endpoint
		 * @param array $extra_headers (default: array())
		 * @param bool  $altauth (default: false)
		 * @return void
		 */
		private function _get( $endpoint, $extra_headers = array(), $altauth = false ) {
			$headers;
			if ( ! $this->api_key ) {
				$headers    = array(
					'Authorization' => 'Basic ' . ( $altauth != false ? base64_encode( $altauth ) : base64_encode( $this->username . ':' . $this->password ) ), // '',// .
					'Content-Type'  => 'application/json',
				);
			}

			if ( $this->api_key != false ) {
				$headers = array(
					'Authorization' => 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key ),
					'Content-Type' 	=> 'application/json',
				);
			}

			$target_url = trailingslashit( $this->api_url ) . $endpoint;

			$result     = wp_remote_get(
				$target_url,
				array(
					'headers' => $headers,
					'sslverify' => false,
					'user-agent' => ZENDESK_USER_AGENT,
				)
			);

			if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && is_wp_error( $result ) ) {
				$error_string = 'Zendesk API GET Error (' . $target_url . '): ' . $result->get_error_message();
				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
					echo $error_string . '<br />';
				}

				if ( class_exists( 'Zendesk_Wordpress_Logger' ) ) {
					Zendesk_Wordpress_Logger::log( $error_string, true );
				}
			}

			return $result;
		}

		/**
		 * API POST.
		 * Similar to the GET method, this function forms the request params
		 * as a POST request to the Zendesk API, given an endpoint and a
		 * $post_data which is generally an associative array.
		 *
		 * @access private
		 * @param mixed $endpoint
		 * @param mixed $post_data (default: null)
		 * @param array $extra_headers (default: array())
		 * @return void
		 */
		private function _post( $endpoint, $post_data = null, $extra_headers = array() ) {

			$post_data  = json_encode( $post_data );
			$headers;
			if ( ! $this->api_key ) {
				$headers    = array(
					'Authorization' => 'Basic ' . ( $altauth != false ? base64_encode( $altauth ) : base64_encode( $this->username . ':' . $this->password ) ), // '',// .
					'Content-Type'  => 'application/json',
				);
			}

			if ( $this->api_key != false ) {
				$headers = array(
					'Authorization' => 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key ),
					'Content-Type' 	=> 'application/json',
				);
			}

			error_log(print_r( $headers, true ));



			$headers    = array_merge( $headers, $extra_headers );
			$target_url = trailingslashit( $this->api_url ) . $endpoint;
			$result     = wp_remote_post(
				$target_url,
				array(
					'redirection' => 0,
					'headers'     => $headers,
					'body'        => $post_data,
					'sslverify'   => false,
					'user-agent' => ZENDESK_USER_AGENT,
				)
			);

			if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && is_wp_error( $result ) ) {
				$error_string = 'Zendesk API POST Error (' . $target_url . '): ' . $result->get_error_message();
				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
					echo $error_string . '<br />';
				}
				if ( class_exists( 'Zendesk_Wordpress_Logger' ) ) {
					Zendesk_Wordpress_Logger::log( $error_string, true );
				}
			}

			return $result;
		}

		/*
		 * API PUT
		 *
		 * Following the above pattern, this function uses wp_remote_request
		 * to fire a PUT request against the Zendesk API. Returns the result
		 * object as it was returned by the request.
		 *
		 * @access private
		 * @param mixed $endpoint
		 * @param mixed $put_data (default: null)
		 * @param array $extra_headers (default: array())
		 * @return void
		 */
		private function _put( $endpoint, $put_data = null, $extra_headers = array() ) {
			$put_data = json_encode( $put_data );
			$headers;
			if ( ! $this->api_key ) {
				$headers    = array(
					'Authorization' => 'Basic ' . ( $altauth != false ? base64_encode( $altauth ) : base64_encode( $this->username . ':' . $this->password ) ), // '',// .
					'Content-Type'  => 'application/json',
				);
			}

			if ( $this->api_key != false ) {
				$headers['Authorization'] = 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key );
				$headers['Content-Type']  = 'application/json';
			}
			$headers  = array_merge( $headers, $extra_headers );

			$target_url = trailingslashit( $this->api_url ) . $endpoint;
			$result     = wp_remote_request(
				$target_url,
				array(
					'method'    => 'PUT',
					'headers'   => $headers,
					'body'      => $put_data,
					'sslverify' => false,
					'user-agent' => ZENDESK_USER_AGENT,
				)
			);

			if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && is_wp_error( $result ) ) {
				$error_string = 'Zendesk API PUT Error (' . $target_url . '): ' . $result->get_error_message();
				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
					echo $error_string . '<br />';
				}

				if ( class_exists( 'Zendesk_Wordpress_Logger' ) ) {
					Zendesk_Wordpress_Logger::log( $error_string, true );
				}
			}

			return $result;
		}

		/**
		 * _delete function.
		 *
		 * @access private
		 * @param mixed $endpoint
		 * @param mixed $put_data (default: null)
		 * @param array $extra_headers (default: array())
		 * @return void
		 */
		private function _delete( $endpoint, $put_data = null, $extra_headers = array() ) {
			$put_data = json_encode( $put_data );
			$headers;
			if ( ! $this->api_key ) {
				$headers    = array(
					'Authorization' => 'Basic ' . ( $altauth != false ? base64_encode( $altauth ) : base64_encode( $this->username . ':' . $this->password ) ), // '',// .
					'Content-Type'  => 'application/json',
				);
			}

			if ( $this->api_key != false ) {
				$headers['Authorization'] = 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key );
				$headers['Content-Type']  = 'application/json';
			}
			$headers  = array_merge( $headers, $extra_headers );

			$target_url = trailingslashit( $this->api_url ) . $endpoint;
			$result     = wp_remote_request(
				$target_url,
				array(
					'method'    => 'DELETE',
					'headers'   => $headers,
					'body'      => $put_data,
					'sslverify' => false,
					'user-agent' => ZENDESK_USER_AGENT,
				)
			);

			if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) && is_wp_error( $result ) ) {
				$error_string = 'Zendesk API DELETE Error (' . $target_url . '): ' . $result->get_error_message();
				if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
					echo $error_string . '<br />';
				}

				if ( class_exists( 'Zendesk_Wordpress_Logger' ) ) {
					Zendesk_Wordpress_Logger::log( $error_string, true );
				}
			}

			return $result;
		}

		/*
		 * Cache Salts (helper)
		 *
		 * Use this function to compose Transient API keys, prepends a zd-
		 * and generates a salt based on the username and the api_url and
		 * the provided postfix variable.
		 *
		 *
		 * @access private
		 * @param mixed $postfix
		 * @return void
		 */
		private function _salt( $postfix ) {
			return 'zd-' . md5( 'zendesk-' . $this->username . $this->api_url . $postfix );
		}

		/**
		 * checker function.
		 *
		 * @access private
		 * @param mixed $result
		 * @param mixed $message
		 * @param bool  $always_error (default: false)
		 * @return void
		 */
		private function checker( $result, $message, $always_error = false ) {
			if ( ! is_wp_error( $result ) && ($result['response']['code'] == 200 || $result['response']['code'] == 201) ) {
				return json_decode( $result['body'] );
			} else {
				if ( is_wp_error( $result ) || $always_error ) {
					return new WP_Error( 'zendesk-api-error', $message );
				}
			}
			return $result; // cause probably 400 error.
		}
	}
}

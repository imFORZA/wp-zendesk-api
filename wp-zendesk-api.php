<?php
/**
 * WP Zendesk API class, for interacting with the Zendesk API.
 *
 * @package WPApiLibraries
 */

/* If access directly, exit. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

/* Confirm that not being included elsewhere. */
if ( ! class_exists( 'WpZendeskAPI' ) ) {

	/**
	 * WP Zendesk API class.
	 *
	 * Extended off the WP API Libraries Base class.
	 *
	 * @link https://github.com/wp-api-libraries/wp-api-base
	 */
	class WpZendeskAPI extends ZendeskAPIBase {

		/**
		 * The username through which to make all calls.
		 *
		 * @var string
		 */
		private $username;

		/**
		 * The alternate username (should not be accessed frequently). Used for calls
		 * where you want to act as a different user.
		 *
		 * @var string
		 */
		private $backup_username = '';

		/**
		 * Internal variable, whether to immediately reset authentication to original username
		 * (used with temporarily setting authentication).
		 *
		 * @var bool
		 */
		private $fast_rest = true;

		/**
		 * Internal variable, whether to create a call without any authorization (for anonymous calls).
		 *
		 * @var bool
		 */
		private $no_auth = false;

		/**
		 * The API key used for authentication.
		 *
		 * @var string
		 */
		private $api_key;

		/**
		 * The extended URI to which requests are made.
		 *
		 * @var string
		 */
		protected $base_uri = '';

		/**
		 * Arguments to be built upon.
		 *
		 * Contains header and body information.
		 *
		 * @var string
		 */
		protected $args;

		/**
		 * Whether to wrap errors in a wp_error object, or to return the full object.
		 *
		 * @var bool
		 */
		protected $is_debug;

		/**
		 * Constructorinatorino 9000
		 *
		 * @param string $domain   The domain extension of zendesk (basically org name).
		 * @param string $username The username through which requests will be made
		 *                         under.
		 * @param string $api_key  The API key used for authentication.
		 * @param bool   $debug    (Default: false) Whether to return calls even if error,
		 *                         or to wrap them in a wp_error object.
		 */
		public function __construct( $domain, $username, $api_key, $debug = false ) {
			$this->base_uri = "https://$domain.zendesk.com/api/v2/";
			$this->username = $username;
			$this->api_key  = $api_key;
			$this->is_debug = $debug;
		}

		/**
		 * Get the current username.
		 *
		 * @return string The username.
		 */
		public function get_username() {
			return $this->username;
		}

		/**
		 * Get the current API key.
		 *
		 * @return string The API key.
		 */
		public function get_api_key() {
			return $this->api_key;
		}

		/**
		 * Set authentication.
		 *
		 * Used for changing the authentication methods.
		 * Note: the domain cannot be changed.
		 *
		 * @param string $username The new username.
		 * @param string $api_key  The new API key.
		 */
		public function set_auth( $username, $api_key ) {
			$this->username = $username;
			$this->api_key  = $api_key;
		}

		/**
		 * Set username for a single call.
		 *
		 * Useful for, as an example, fetching requests that an end user is authorized
		 * to view, by setting the username for the next call to be their email.
		 *
		 * After fetch() is run, the username is reset to the original (or most recently
		 * updated) username.
		 *
		 * @param string $username   The temporary single call username.
		 * @param bool   $fast_reset (Default: true) whether ot reset the username after
		 *                           the next fetch() call.
		 */
		public function set_temporary_username( $username, $fast_reset = true ) {
			$this->backup_username = $this->username;
			$this->username        = $username;
			$this->fast_reset      = $fast_reset;
		}

		/**
		 * Temporarily make the next call not have any authentication headers.
		 *
		 * @param boolean $fast_reset (Default: true) Whether to reset after the next
		 *                            immediate fetch() call.
		 * @return WpZendeskAPI       Self.
		 */
		public function set_temporary_noauth( $fast_reset = true ) {
			$this->no_auth    = true;
			$this->fast_reset = $fast_reset;

			return $this;
		}

		/**
		 * Resets the username to its original status.
		 *
		 * Designed to be used with set_temporary_username('<username>', false).
		 *
		 * @return WpZendeskAPI Self.
		 */
		public function reset_username() {
			if ( '' !== $this->backup_username ) {
				$this->username        = $this->backup_username;
				$this->backup_username = '';
			}

			return $this;
		}

		/**
		 * Perform the request, normally after build_request.
		 *
		 * @return mixed The body of the call.
		 */
		protected function fetch() {
			$result = parent::fetch();

			if ( '' !== $this->backup_username && $this->fast_reset ) {
				$this->username        = $this->backup_username;
				$this->backup_username = '';
				$this->no_auth         = false;
			}

			return $result;
		}

		/**
		 * Abstract extended function that is used to set authorization before each
		 * call. $this->args['headers'] are wiped after every fetch call, hence this
		 * function is necessary.
		 *
		 * @return void
		 */
		protected function set_headers() {
			$this->args['headers'] = array(
				'Content-Type' => 'application/json',
			);

			if ( ! $this->no_auth ) {
				// @codingStandardsIgnoreStart
				$this->args['headers']['Authorization'] = 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key );
				// @codingStandardsIgnoreEnd
			}
		}

		/**
		 * Handle the build request and fetch methods, along with (optionally, but by
		 * default) adding the data type extension to the route.
		 *
		 * @param  string $route         The route to access.
		 * @param  array  $args          (Default: array()) Optional arguments. If the request
		 *                               method is 'GET', then the arguments are appended to
		 *                               the route as query args. Otherwise, they are stored
		 *                               in the body of the request.
		 * @param  string $method        (Default: 'GET') The type of request to make.
		 * @param  bool   $add_data_type (Default: true) Whether to add the data type
		 *                               extension to the route or not.
		 * @return [type]                [description]
		 */
		protected function run( $route, $args = array(), $method = 'GET', $add_data_type = true ) {
			// K, screw caching.

			return $this->build_request( $route . ( $add_data_type ? '.json' : '' ), $args, $method )->fetch();
		}

		/**
		 * Deletes all stored transients.
		 *
		 * More a helper function, should not be often routinely called.
		 *
		 * @return integer The number rows affected.
		 */
		public function clear_cache() {
			global $wpdb;

			$count = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->options
				WHERE `option_name` LIKE '%s'",
					'%hostops_zendeskapi_%'
				)
			);

			// Divided by 2 because there's a row for both the value itself and its expiration.
			return $count / 2;
		}

		/**
		 * Clear arguments.
		 *
		 * Extended just in case you don't want to wipe everything.
		 *
		 * Recommended at least clearing body.
		 *
		 * @return void
		 */
		protected function clear() {
			$this->args = array();
		}

		private function parse_args( $args, $merge = array() ) {
			$results = array();

			foreach ( $args as $key => $val ) {
				if ( $val !== null ) {
					$results[ $key ] = $val;
				} elseif ( is_array( $val ) && ! empty( $val ) ) {
					$results[ $key ] = $val;
				}
			}

			return array_merge( $merge, $results );
		}

		/**
		 * Function for building zendesk pagination.
		 *
		 * @param  integer $per_page   (Default: 100) Number of results to show per page.
		 * @param  integer $page       (Defualt: 1) Page to start on.
		 * @param  string  $sort_by    (Default: '') What to sort by.
		 * @param  string  $sort_order (Default: 'desc') What order to display results in.
		 * @return array               An array of arguments compliant with zendesk pagination.
		 */
		public function build_zendesk_pagination( $per_page = 100, $page = 1, $sort_by = '', $sort_order = 'desc' ) {
			$args = array(
				'per_page' => $per_page,
				'page'     => $page,
			);

			if ( '' !== $sort_by ) {
				$args['sort_by']    = $sort_by;
				$args['sort_order'] = $sort_order;
			}

			return $args;
		}

		/**
		 * Function for setting pagination prior to a call (should be a GET only!).
		 *
		 * Example usage:
		 *
		 *    $hapi = new WpZendeskAPI( ... );
		 *    $results = $hapi->set_pagination( 30, 2, array( 'sort_by' => 'updated_at') )->get_tasks();
		 *
		 *    // Alternatively
		 *    $hapi->set_pagination( 30, 2 );
		 *    $results = $hapi->get_tasks();
		 *
		 * If 'sort_by' is set and 'sort_order' is not set, it will be automatically set to desc.
		 *
		 * p() is a wrapper function for set_pagination.
		 *
		 * TODO: move updated_since to here (since it appears in all other cases as well).
		 *
		 * @param integer $page     (Default: 1) Page offset to get results from
		 *                          (multiplied by $per_page is the final page).
		 * @param integer $per_page (Default: 100) Number of results to display per page.
		 * @param string  $args     (Default: null) Only retrieve results that have
		 *                          been updated after this date.
		 * @return WpZendeskAPI     $this.
		 */
		public function set_pagination( $per_page = 100, $page = 1, $args = array() ) {
			$this->args['body'] = $this->parse_args(
				array(
					'per_page' => $per_page,
					'page'     => $page,
				), $args
			);

			if ( isset( $this->args['body']['sort_by'] ) && ! isset( $this->args['body']['sort_order'] ) ) {
				$this->args['body']['sort_order'] = 'desc';
			}

			return $this;
		}

		/**
		 * Wrapper function for set_pagination().
		 *
		 * @param integer $page     (Default: 1) Page offset to get results from (multiplied
		 *                          by $per_page is the final page).
		 * @param integer $per_page (Default: 100) Number of results to display per page.
		 * @return HarvestAPI       $this.           [description]
		 */
		public function p( $per_page = 100, $page = 1, $args = array() ) {
			return $this->set_pagination( $per_page, $page, $args );
		}

		/**
		 * Query the Zendesk search route.
		 *
		 * Can be paginated.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/search
		 *
		 * @param  string  $search_string The search query.
		 * @param  integer $per_page      (Default: 100) The number of results to return
		 *                                per page. Maxes out at 100.
		 * @param  integer $page          (Default: 1) The page off of results to start at.
		 * @param  string  $sort_by       (Default: '') What to sort by.
		 * @param  string  $sort_order    (Default: 'desc') What order to display results in
		 *                                either 'desc' or 'asc'.
		 * @return object                 A stdClass of the body from the response.
		 */
		public function search( $search_string ) {
			return $this->run( 'search', array( 'query' => $search_string ) );
		}

		/* Useful search functions */

		/**
		 * Get tickets associated with an email.
		 *
		 * @param  string $email The email to look for.
		 * @return object        The results of the search (Zendesk search results).
		 */
		public function get_tickets_by_email( $email ) {
			return $this->run(
				'search', array(
					'query' => rawurlencode( 'type:ticket requester:' . $email ),
				)
			);
		}

		/**
		 * Get user by email.
		 *
		 * @param  string $email The email to search by.
		 * @return object        The results of the user serach.
		 */
		public function get_user_by_email( $email ) {
			// or is it get user?
			return $this->run(
				'users/search', array(
					'query' => $email,
				)
			);
		}

		/**
		 * Get requests associated with an email.
		 *
		 * @param  string $email The email to search by.
		 * @return object        Results of the search.
		 */
		public function get_requests_by_email( $email ) {
			return $this->run(
				'search', array(
					'query' => urlencode( 'type:request requester:' . $email . ' status:all' ),
				)
			);
		}

		/**
		 * Get organizations by organization name.
		 *
		 * @param  string $organization_name The organization name.
		 * @return object                    The search results.
		 */
		public function get_organizations_by_name( $organization_name ) {
			return $this->run(
				'search', array(
					'query' => urlencode( 'type:organization ' . $organization_name ),
				)
			);
		}

		/* Tickets */

		/**
		 * List tickets.
		 *
		 * Returns a maximum of 100 tickets per page. See Pagination.
		 *
		 * Tickets are ordered chronologically by created date, from oldest to newest.
		 * The first ticket listed may not be the absolute oldest ticket in your account
		 * due to ticket archiving. To get a list of all tickets in your account, use the
		 * Incremental Ticket Export endpoint.
		 *
		 * For more filter options, use the Search API.
		 *
		 * You can also sideload related records with the tickets. See Side-Loading.
		 *
		 * Can be paginated.
		 *
		 * @param  integer $per_page   (Default: 100) Number of results to display per page. Max 100.
		 * @param  integer $page       (Default: 1) What offset to start at.
		 * @param  string  $sort_by    (Default: '') What to sort by.
		 * @param  string  $sort_order (Default: 'desc') Order of results to display.
		 * @return object              List of tickets.
		 */
		public function list_tickets() {
			return $this->run( 'tickets' );
		}

		/**
		 * Extention of list_tickets, except show tickets by user ID.
		 *
		 * Can be paginated.
		 *
		 * @param  string $user_id The user ID. Can also be int.
		 * @return object          A list of tickets requested by a specific user ID.
		 */
		public function list_tickets_by_user_id_requested( $user_id ) {
			return $this->run( "users/$user_id/tickets/requested" );
		}

		/**
		 * Show a ticket.
		 *
		 * Returns a number of ticket properties, but not the ticket comments. To
		 * get the comments, use List Comments.
		 *
		 * @param  string $ticket_id The ID of a ticket.
		 * @return object            The ticket.
		 */
		public function show_ticket( $ticket_id ) {
			return $this->run( "tickets/$ticket_id" );
		}

		/**
		 * Show multiple tickets
		 *
		 * Accepts a comma separated list of ticket ids to return.
		 *
		 * This endpoint will return up to 100 tickets records.
		 *
		 * TODO: rename to list_tickets.
		 *
		 * Can be paginated.
		 *
		 * @param  mixed $ids Either an array of ticket IDs, or a comma separated list.
		 * @return object     The multiple tickets requested.
		 */
		public function show_multiple_tickets( $ids ) {
			if ( is_array( $ids ) ) {
				$ids = implode( $ids, ',' );
			}
			return $this->run(
				'tickets/show_many', array(
					'ids' => $ids,
				)
			);
		}

		/**
		 * Extension of show_tickets, shows tickets by a request.
		 *
		 * @param  [type] $user_id [description]
		 * @return [type]          [description]
		 */
		public function get_requests_by_user( $user_id ) {
			return $this->run( "users/$user_id/tickets/requested" );
		}

		/**
		 * Extension of show_tickets, shows tickets that are cc'd to a user.
		 *
		 * Can be paginated.
		 *
		 * @param  [type] $user_id [description]
		 * @return [type]          [description]
		 */
		public function get_ccd_by_user( $user_id, $pages = null ) {
			return $this->run( "users/$user_id/tickets/ccd" );
		}

		/**
		 * Extension of show_tickets, shows tickets assigned to a user.
		 *
		 * Can be paginated.
		 *
		 * @param  [type]  $user_id  [description]
		 * @param  integer $per_page [description]
		 * @param  integer $page     [description]
		 * @return [type]            [description]
		 */
		public function get_assigned_by_user( $user_id ) {
			return $this->run( "users/$user_id/tickets/assigned" );
		}

		/**
		 * Build a zendesk ticket object, compliant with the zendesk api ticket format.
		 *
		 * @param  string $subject      (Default: '') The subject of the ticket.
		 * @param  string $description  (Default: '') The description of the ticket.
		 * @param  string $comment      (Default: '') The comment of the ticket.
		 * @param  string $requester_id (Default: '') The requester for the ticket.
		 * @param  string $tags         (Default: '') The tags for the ticket (CSV).
		 * @param  array  $other        (Default: array()) Other properties (as key => val).
		 * @param  bool   $raw          (Default: false) Whether to return the array as
		 *                              array( 'ticket' => array( / * stuff * / ) ) or
		 *                              a raw array of properties.
		 * @return array                A formatted zendesk API ticket object.
		 */
		public function build_zendesk_ticket( $subject = '', $description = '', $comment = '', $requester_id = '', $tags = '', $other = array(), $raw = false ) {
			$ticket = array();

			if ( '' !== $subject ) {
				$ticket['subject'] = $subject;
			}

			if ( '' !== $description ) {
				$ticket['description'] = $description;
			}

			if ( '' !== $comment ) {
				$ticket['comment'] = $comment;
			}

			if ( '' !== $requester_id ) {
				$ticket['requester_id'] = $requester_id;
			}

			if ( '' !== $tags ) {
				if ( gettype( $tags ) == 'array' ) {
					$ticket['tags'] = implode( ',', $tags );
				} else {
					$tickets['tags'] = $tags;
				}
			}

			if ( ! empty( $other ) ) {
				foreach ( $other as $key => $val ) {
					$ticket[ $key ] = $val;
				}
			}

			if ( $raw ) {
				return $ticket;
			}

			return array(
				'ticket' => $ticket,
			);
		}

		/**
		 * Create a ticket.
		 *
		 * @param  mixed  $ticket       If is an array, will ignore all other args and pass
		 *                              $ticket as the ticket object. If is a string, will
		 *                              assume it's the subject of the ticket.
		 * @param  string $description  (Default: '') The description.
		 * @param  string $requester_id (Default: '') The requester ID.
		 * @param  string $tags         (Default: '') The tags for the ticket.
		 * @param  array  $other        (Default: '') Other properties (key => value).
		 * @return object               The created zendesk ticket.
		 */
		public function create_ticket( $ticket, $description = '', $requester_id = '', $tags = '', $other = array() ) {

			if ( gettype( $ticket ) !== 'object' && gettype( $ticket ) !== 'array' ) {
				$ticket = $this->build_zendesk_ticket( $ticket, $description, '', $requester_id, $tags, $other );
			}

			return $this->run( 'tickets', $ticket, 'POST' );
		}

		/**
		 * Create multiple tickets.
		 *
		 * Accepts an array of ticket objects (see build_zendesk_ticket).
		 *
		 * @param  array $ticket_objs An array of ticket objects.
		 * @return object             The created tickets.
		 */
		public function create_many_tickets( $ticket_objs ) {
			return $this->run(
				'tickets/create_many', array(
					'tickets' => $ticket_objs,
				), 'POST'
			);
		}

		/**
		 * Update a ticket.
		 *
		 * @param  string $ticket_id  The ID of the ticket.
		 * @param  array  $ticket_obj The ticket object. Only properties present in this
		 *                            object will be updated, all else will be ignored.
		 * @return object             The updated ticket.
		 */
		public function update_ticket( $ticket_id, $ticket_obj ) {
			return $this->run( 'tickets/' . $ticket_id, $ticket_obj, 'PUT' );
		}


		/**
		 * @link https://developer.zendesk.com/rest_api/docs/core/tickets#update-many-tickets
		 *
		 * @param  array $ticket_objs Accepts an array of up to 100 ticket objects.
		 *                            If ticket is set, then will require ids to be set.
		 *                            Otherwise, tickets should be set, and ids is not necessary
		 *                            to be set.
		 * @param  array $ids         A comma-separated list of up to 100 ticket ids.
		 *                            Use this for modifying many tickets with the same
		 *                            change.
		 * @return [type]              [description]
		 */
		public function update_many_tickets( $ticket_obj, $ids = array() ) {
			if ( empty( $ids ) ) {
				return $this->run( 'tickets/update_many', $ticket_obj, 'PUT' );
			} else {
				return $this->run( 'tickets/update_many.json?ids=' . implode( ',', $ids ), $ticket_obj, 'PUT', false );
			}
		}

		public function protect_ticket_update_collisions() {

		}

		/**
		 * Marks a ticket as spam, and blocks the requester.
		 *
		 * @param  [type] $ticket_id [description]
		 * @return [type]            [description]
		 */
		public function mark_ticket_spam_and_block_requester( $ticket_id ) {
			return $this->run( "tickets/$ticket_id/mark_as_spam", array(), 'PUT' );
		}

		public function mark_many_tickets_as_spam( $ids ) {
			return $this->run( 'tickets/mark_many_as_spam.json?ids=' . implode( ',', $ids ), array(), 'PUT', false );
		}

		public function merge_tickets_into_target() {

		}

		public function get_ticket_related_info( $ticket_id ) {
			return $this->run( "tickets/$ticket_id/related" );
		}


		public function create_ticket_new_requester() {

		}

		public function set_ticket_fields() {

		}

		public function delete_ticket( $ticket_id ) {
			return $this->run( "tickets/$ticket_id", array(), 'DELETE' );
		}

		public function bulk_delete_tickets( $ticket_ids = array() ) {
			return $this->run( 'tickets/destroy_many.json?ids=' . implode( ',', $ticket_ids ), array(), 'DELETE', false );
		}

		public function show_delete_tickets() {
			return $this->run( 'deleted_tickets' );
		}

		public function restore_deleted_ticket( $ticket_id ) {
			return $this->run( "deleted_tickets/$ticket_id/restore", array(), 'PUT' );
		}

		public function restore_bulk_deleted_tickets( $ticket_ids = array() ) {
			return $this->run( 'deleted_tickets/restore_many?ids=' . implode( ',', $ticket_ids ), array(), 'PUT', false );
		}

		public function delete_tickets_permanently() {

		}

		/**
		 * List collaborators for a ticket.
		 *
		 * Can be paginated.
		 *
		 * @param  string $ticket_id The ID of the ticket (can also be numeric).
		 * @return array             A list of collaborators on a ticket.
		 */
		public function get_collaborators_ticket( $ticket_id ) {
			return $this->run( "tickets/$ticket_id/collaborators" );
		}

		/**
		 * List incidents for a ticket.
		 *
		 * @param  [type] $ticket_id [description]
		 * @return array             A list of incidents from a ticket.
		 */
		public function list_ticket_incidents( $ticket_id ) {
			return $this->run( "tickets/$ticket_id/incidents" );
		}

		/**
		 * List ticket problems.
		 *
		 * The response is always ordered by updated_at, in desc order.
		 *
		 * @return [type] [description]
		 */
		public function list_ticket_problems() {
			return $this->run( 'problems' );
		}

		/**
		 * Returns tickets whose type is "Problem" and whose subject contains the string
		 * specified in the <code>text</code> parameter.
		 *
		 * @return array A list of tickets that have been autocompleted.
		 */
		public function autocomplete_problems( $text ) {
			return $this->run(
				'autocomplete', array(
					'text' => $text,
				), 'POST'
			);
		}

		/* Ticket import */

		/**
		 * The endpoint takes a ticket object describing the ticket.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/ticket_import#ticket-import
		 * @param  array $ticket A ZendeskAPI ticket object (see $this->build_zendesk_ticket()).
		 * @return object        The successfully created ticket (hopefully).
		 */
		public function ticket_import( $ticket ) {
			return $this->run( 'imports/tickets', $ticket, 'POST' );
		}

		/**
		 * The endpoint takes a tickets array of up to 100 ticket objects.
		 *
		 * Similar to single tickets, except they're single tickets.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/ticket_import#ticket-bulk-import
		 * @param  [type] $tickets [description]
		 * @return [type]          [description]
		 */
		public function bulk_ticket_import( $tickets ) {
			return $this->run( 'imports/tickets/create_many', $tickets, 'POST' );
		}

		/* Requests */

		/**
		 * List general requests.
		 *
		 * Can be paginated
		 *
		 * @return [type]              [description]
		 */
		public function list_requests() {
			return $this->run( 'requests' );
		}

		/**
		 * Can be paginated.
		 *
		 * @return [type] [description]
		 */
		public function list_open_requests() {
			return $this->run( 'requests/open', $args );
		}

		/**
		 * Can be paginated.
		 *
		 * @return [type] [description]
		 */
		public function list_hold_requests() {
			return $this->run( 'requests/hold', $args );
		}

		/**
		 * Format for statuses: comma separated list (string) of statuses to browse through.
		 *
		 * Can be paginated.
		 *
		 * @param  [type] $statuses
		 * @param  array  $zendesk_pagination Zendesk pagination tool.
		 * @return [type]              [description]
		 */
		public function list_requests_by_status( $statuses ) {
			return $this->run( 'requests', array( 'status' => $statuses ) );
		}

		/**
		 * Can be paginated.
		 *
		 * @param  [type] $user_id [description]
		 * @return [type]          [description]
		 */
		public function list_requests_by_user( $user_id ) {
			return $this->run( "users/$user_id/requests" );
		}

		/**
		 * Can be paginated.
		 *
		 * @return [type] [description]
		 */
		public function list_requests_by_organization() {
			return $this->run( "organizations/$org_id/requests" );
		}

		/**
		 * Search requests.
		 *
		 * Can be paginated.
		 *
		 * GET /api/v2/requests/search.json?query=camera
		 * GET /api/v2/requests/search.json?query=camera&organization_id=1
		 * GET /api/v2/requests/search.json?query=camera&cc_id=true
		 * GET /api/v2/requests/search.json?query=camera&status=hold,open
		 *
		 * @param  [type] $query              [description]
		 * @param  [type] $zendesk_pagination
		 * @return [type]                     [description]
		 */
		public function search_requests( $query ) {
			return $this->run( 'requests/search', array( 'query' => $query ) );
		}

		/**
		 * Show a single request.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/requests#show-request
		 * @param  [type] $request_id [description]
		 * @return [type]             [description]
		 */
		public function show_request( $request_id ) {
			return $this->run( 'requests/' . $request_id );
		}

		/**
		 * Build a request (following the zendesk API structure).
		 *
		 * @param  string $subject     [description]
		 * @param  string $description [description]
		 * @param  string $comment     [description]
		 * @param  array  $other       [description]
		 * @param  bool   $raw
		 * @return [type]              [description]
		 */
		public function build_zendesk_request( $subject = '', $description = '', $comment = '', $other = array(), $raw = false ) {
			$request = array();

			if ( $subject != '' ) {
				$request['subject'] = $subject;
			}
			if ( $description != '' ) {
				$request['description'] = $description;
			}
			if ( $comment != '' ) {
				$request['comment']['body'] = $comment;
			}

			if ( ! empty( $other ) ) {
				foreach ( $other as $key => $val ) {
					$request[ $key ] = $val;
				}
			}

			if ( $raw ) {
				return $request;
			}

			return array(
				'request' => $request,
			);
		}

		/**
		 * Call build request, must fill out subject and description, should fill out requester.
		 *
		 * If not defined and not admin, then will be set to whoever is authenticated.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/requests#create-request
		 * @param  [type] $request [description]
		 * @return [type]          [description]
		 */
		public function create_request( $request ) {
			return $this->run( 'requests', $request, 'POST' );
		}

		/**
		 * Call build_request, recommended fill out comment, can fill out status
		 * This function is mostly used for adding a comment.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/requests#update-request
		 * @param  [type] $request_id [description]
		 * @param  [type] $request    [description]
		 * @return [type]             [description]
		 */
		public function update_request( $request_id, $request ) {
			return $this->run( 'requests/' . $request_id, $request, 'PUT' );
		}

		/**
		 * Lists comments from a request.
		 *
		 * I BELIEVE it will not list private comments.
		 *
		 * Not totally sure.
		 *
		 * Please test.
		 *
		 * Can be paginated.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/requests#listing-comments
		 * @param  [type] $request_id [description]
		 * @return [type]             [description]
		 */
		public function list_comments_request( $request_id ) {
			return $this->run( "requests/$request_id/comments", $zendesk_pagination );
		}

		/**
		 * Get a specific comment.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/requests#getting-comments
		 * @param  [type] $request_id [description]
		 * @param  [type] $comment_id [description]
		 * @return [type]             [description]
		 */
		public function get_comment_request( $request_id, $comment_id ) {
			return $this->run( "requests/$request_id/comments/$comment_id" );
		}

		/* Attachments */

		public function show_attachment( $attachment_id ) {
			return $this->run( "api/v2/attachments/$attachment_id" );
		}

		public function delete_attachment() {
			return $this->run( "api/v2/attachments/$attachment_id", array(), 'DELETE' );
		}

		public function upload_files( $filename, $file, $token = null ) {
			$route = "api/v2/uploads.json?filename=$filename";
			if ( $token !== null ) {
				$route .= "&token=$token";
			}

			// Okaaaaaaay... how the heck do I handle uploads?
			return $this->run( $route, $body, 'POST', false );
		}

		/**
		 * Delete an upload.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/attachments#delete-upload
		 * @param  [type] $token [description]
		 * @return [type]        [description]
		 */
		public function delete_upload( $token ) {
			return $this->run( "uploads/$token", array(), 'DELETE' );
		}

		/**
		 * Redaction allows you to permanently remove attachments from an existing
		 * comment on a ticket. Once removed from a comment, the attachment is replaced
		 * with a placeholder "redacted.txt" file.
		 *
		 * Note that redaction is permanent. It is not possible to undo redaction or
		 * see what was removed. Once a ticket is closed, redacting its attachments
		 * is no longer possible.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/attachments#redact-comment-attachment
		 * @return [type] [description]
		 */
		public function redact_comment_attachment( $ticket_id, $comment_id, $attachment_id ) {
			return $this->run( "tickets/$ticket_id/comments/$comment_id/attachments/$attachment_id/redact", array(), 'PUT' );
		}

		/* Satisfaction Ratings */

		/**
		 * List satisfcation ratings
		 *
		 * Can be paginated.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/satisfaction_ratings#list-satisfaction-ratings
		 * @param  string $score              received, received_with_comment, received_without_comment,
		 *                                    good, good_with_comment, good_without_comment,
		 *                                    bad, bad_with_comment, bad_without_comment
		 * @param  string $start_time         Time of the oldest satisfaction rating, as
		 *                                    a Unix epoch time
		 * @param  string $end_time           Time of the most recent satisfaction rating,
		 *                                    as a Unix epoch time
		 * @return [type]                     [description]
		 */
		public function list_satisfaction_ratings( $score = null, $start_time = null, $end_time = null ) {
			$args = $this->parse_args(
				array(
					'score'      => $score,
					'start_time' => $start_time,
					'end_time'   => $end_time,
				)
			);

			return $this->run( 'satisfaction_ratings', $args );
		}

		/**
		 * Show satisfaction rating.
		 *
		 * Returns a specific satisfaction rating. You can get the id from the List
		 * Satisfaction Ratings endpoint.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/satisfaction_ratings#show-satisfaction-rating
		 * @param  string $rating_id [description]
		 * @return [type] [description]
		 */
		public function show_satisfaction_rating( $rating_id ) {
			return $this->run( "satisfaction_ratings/$rating_id" );
		}

		/**
		 * Create a satisfaction rating.
		 *
		 * Creates a CSAT rating for solved tickets, or for tickets that were
		 * previously solved and then reopened.
		 *
		 * The end user must be a verified user, and the person who requested the ticket.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/core/satisfaction_ratings#create-a-satisfaction-rating
		 * @param  [type] $ticket_id  [description]
		 * @param  [type] $score      [description]
		 * @param  string $comment    [description]
		 * @param  string $sort_order [description]
		 * @return [type]             [description]
		 */
		public function create_satisfaction_rating( $ticket_id, $score, $comment = '', $sort_order = 'asc' ) {
			$args = array(
				'score'      => $score,
				'sort_order' => $sort_order,
			);

			if ( '' !== $comment ) {
				$args['comment'] = $comment;
			}

			return $this->run( "tickets/$ticket_id/satisfaction_rating", $args, 'POST' );
		}

		/* Satisfaction Reasons */

		public function list_reasons_for_satisfaction_rating() {

		}

		public function show_reasons_for_satisfaction_rating() {

		}

		/* Suspended Tickets */

		public function list_suspended_tickets() {

		}

		public function show_suspended_tickets() {

		}

		public function recover_suspended_ticket() {

		}

		public function recover_suspended_tickets() {

		}

		public function delete_suspended_ticket() {

		}

		public function delete_suspended_tickets() {

		}

		/* Ticket Audits */

		public function list_audits_for_ticket() {

		}

		public function show_audit() {

		}

		public function change_comment_to_private() {

		}

		public function get_audit_events() {

		}

		public function the_via_object() {

		}

		/* Ticket Comments */

		public function create_ticket_comment( $ticket_id, $text, $public = true ) {
			$ticket = $this->build_zendesk_ticket();

			$ticket['comment'] = array(
				'public' => $public,
				'body'   => $text,
			);

			return $this->run( 'tickets/' . $ticket_id, $ticket, 'PUT' );
		}

		public function create_request_comment( $request_id, $text ) {
			$request = $this->build_zendesk_request( '', '', $text );

			return $this->run( 'requests/' . $request_id, $request, 'PUT' );
		}

		/**
		 * I think it can be paginated?
		 *
		 * @param  [type] $ticket_id  [description]
		 * @param  string $sort_order [description]
		 * @return [type]             [description]
		 */
		public function list_comments( $ticket_id, $sort_order = 'asc' ) {
			return $this->run(
				"tickets/$ticket_id/comments", array(
					'sort_order' => $sort_order,
				)
			); // might need to do a json_decode? TODO: look into
		}

		public function redact_string_in_comment() {

		}

		public function make_comment_private() {

		}

		/* Ticket skips */

		public function record_skip_for_user() {

		}

		public function list_skips_for_account() {

		}

		/* Ticket metrics */

		/* Ticket metric events */

		/* Users */

		/**
		 * Can be paginated.
		 *
		 * @param  string  $id       [description]
		 * @param  boolean $is_group [description]
		 * @param  string  $page     [description]
		 * @return [type]            [description]
		 */
		public function list_users( $id = '', $is_group = true ) {
			$options = array();

			if ( $id != '' ) {
				if ( $is_group ) {
					return $this->run( "groups/$id/users", $options );
				} else {
					return $this->run( "organizations/$id/users", $options );
				}
			}

			return $this->run( 'users', $options );
		}

		public function show_user( $user_id ) {
			return $this->run( "users/$user_id" );
		}

		// Either a comma separated list, or an array of IDs.
		public function show_users( $user_ids ) {
			if ( is_array( $user_ids ) ) {
				$user_ids = implode( $user_ids, ',' );
			}

			return $this->run(
				'users/show_many', array(
					'ids' => $user_ids,
				)
			);
		}

		public function get_user_info( $user_id ) {
			return $this->run( "users/$user_id/related" );
		}

		/**
		 * Build zendesk user function. Used for creating a zendesk user.
		 * Ie, creating a user could be done by:
		 * <code>return $zenapi->create_user( $zenapi->build_zendesk_user( $name, $email,
		 * $role, array( 'active' => true ) ) );</code>
		 * All parameters are optional, an empty user object will be returned if they
		 * are all empty.
		 *
		 * @param  string $name  (Default: '') Name of the user.
		 * @param  string $email (Default: '') Email of the user.
		 * @param  string $role  (Default: '') Role of the user. Must be either 'end-user',
		 *                       'agent', or 'admin'
		 * @param  array  $other (Default: array()) An associative array of whatever
		 *                       else you want to put in. Each key will have its value
		 *                       placed in under the key.
		 * @return array         User object (really an array) up to specs with the Zendesk
		 *                       API style.
		 */
		public function build_zendesk_user( $name = '', $email = '', $role = '', $other = array() ) {
			$user = array(
				'user' => array(),
			);

			if ( $name != '' ) {
				$user['user']['name'] = $name;
			}
			if ( $email != '' ) {
				$user['user']['email'] = $email;
			}
			if ( $role != '' ) {
				$user['user']['role'] = $role;
			}

			if ( ! empty( $other ) ) {
				foreach ( $other as $key => $val ) {
					$user['user'][ $key ] = $val;
				}
			}

			return $user;
		}

		// Use the build_zendesk_user function
		public function create_user( $user ) {
			return $this->run( 'users', $user, 'POST' );
		}

		// Expects:
		// users:[
		// {
		// name: tj
		// email: tj@tj.tj
		// },
		// {
		// name: jk
		// email: jkrowling@harrypotter.com
		// },
		// ]
		public function create_users( $users ) {
			return $this->run( 'users/create_many', $users, 'POST' );
		}

		// Also recommended to use the build zendesk user function.
		public function update_user( $user_id, $user ) {
			return $this->run( 'users/' . $user_id, $user, 'PUT' );
		}

		// Use cases:
		// Making the same change to a bunch of users:
		// $ids = array( id, id, id )
		// $user = array( 'user' => 'org_id' => 2 ),
		// Making different changes to different usres:
		// $user = array(
		// 'users' => array(
		// array(
		// 'id' => 20,
		// 'name' => 'TJ'
		// ),
		// array(
		// 'id' => 30,
		// 'organization_id' => 2,
		// 'verified' => true
		// )
		// )
		// )
		public function update_users( $user, $ids = null ) {
			if ( null !== $ids && gettype( $ids ) === 'string' ) {
				return $this->run( "users/update_many.json?ids=$ids", $user, 'PUT', false );
			} elseif ( null !== $ids && gettype( $ids ) === 'array' ) {
				return $this->run( 'users/update_many.json?ids=' . implode( ',', $ids ), $user, 'PUT', false );
			} else {
				return $this->run( 'users/update_many', $user, 'PUT' );
			}
		}

		public function create_or_update_user( $user ) {
			return $this->run( 'users/create_or_update', $user, 'POST' );
		}

		// Each user can be identified via email or external ID.
		public function create_or_update_users( $users ) {
			return $this->run( 'users/create_or_update_many', $users, 'POST' );
		}

		/**
		 * Can be paginated.
		 *
		 * @param  [type]  $query       [description]
		 * @param  boolean $external_id [description]
		 * @return [type]               [description]
		 */
		public function search_users( $query, $external_id = false ) {
			if ( $external_id !== false ) {
				return $this->run(
					'users/search', array(
						'external_id' => $external_id,
					)
				);
			}
			return $this->run(
				'users/search', array(
					'query' => $query,
				)
			);
		}

		public function delete_user( $user_id ) {
			return $this->run( "users/$user_id", array(), 'DELETE' );
		}

		public function bulk_delete_users( $user_ids ) {
			if ( gettype( $user_ids ) === 'string' ) {
				return $this->run( "users/destroy_many.json?ids=$user_ids", array(), 'DELETE', false );
			} elseif ( gettype( $user_ids ) === 'array' ) {
				return $this->run( 'users/destroy_many.json?ids=' . implode( ',', $user_ids ), array(), 'DELETE', false );
			} else {
				return 'Error: invalid data type.';
			}
		}

		public function set_user_password( $user_id, $pass ) {
			return $this->run(
				"users/$user_id/password", array(
					'password' => $pass,
				), 'POST'
			);
		}

		public function get_user_groups( $user_id ) {
			return $this->run( "users/$user_id/groups" );
		}

		/* User identities */

		/**
		 * Might support pagination? Not sure.
		 *
		 * @param  [type] $user_id [description]
		 * @return [type]          [description]
		 */
		public function list_identities( $user_id ) {
			return $this->run( "users/$user_id/identities" );
		}

		public function show_identity( $user_id, $identity_id ) {
			return $this->run( "users/$user_id/identities/$identity_id" );
		}

		public function create_identity( $user_id, $type, $value, $verified = false ) {
			$valid_types = array( 'email', 'google', 'phone_number', 'agent_forwarding', 'twitter', 'facebook', 'sdk' );
			if( ! in_array( $type, $valid_types ) ){
				return new WP_Error( 'invalid-data', __( 'Unsupported Zendesk identity type.', 'wp-zendesk-api' ) );
			}

			$identity = array(
				'identity' => array(
					'type'     => $type,
					'value'    => $value,
					'verified' => $verified
				)
			);
			return $this->run( "users/$user_id/identities", $identity, 'POST' );
		}

		public function update_identity( $user_id, $identity_id, $identity ) {
			return $this->run( "users/$user_id/identities/$identity_id", $identity, 'PUT' );
		}

		public function make_identity_primary( $user_id, $identity_id ) {
			return $this->run( "users/$user_id/identities/$identity_id/make_primary", array(), 'PUT' );
		}

		public function verify_identity( $user_id, $identity_id ) {
			return $this->run( "users/$user_id/identities/$identity_id/verify", array(), 'PUT' );
		}

		public function request_verification( $user_id, $identity_id ) {
			return $this->run( "users/$user_id/identities/$identity_id/request_verification", array(), 'PUT' );
		}

		public function delete_identity( $user_id, $identity_id ) {
			return $this->run( "users/$user_id/identities/$identity_id", array(), 'DELETE' );
		}

		/* Custom agent roles */

		/* End users */

		/* Groups */

		/**
		 * Can be paginated.
		 *
		 * @return [type] [description]
		 */
		public function list_groups() {
			return $this->run( 'groups' );
		}

		public function show_assignable_groups() {
			return $this->run( 'groups/assignable' );
		}

		public function show_group( $group_id ) {
			return $this->run( "groups/$group_id" );
		}

		public function create_group( $group ) {
			if ( ! isset( $group['group'] ) && isset( $group['name'] ) ) {
				$group = array( 'group' => $group );
			}
			return $this->run( 'groups', $group, 'POST' );
		}

		public function update_group( $group_id, $group ) {
			if ( ! isset( $group['group'] ) ) {
				$group = array( 'group' => $group );
			}

			return $this->run( "groups/$group_id", $group, 'PUT' );
		}

		public function delete_group( $group_id ) {
			return $this->run( "groups/$group_id", array(), 'DELETE' );
		}

		/* Group memberships */

		/* Sessions */

		/* Organizations */

		public function show_organization( $org_id ){
			return $this->run( "organizations/$org_id" );
		}

		/**
		 * Can be paginated.
		 *
		 * @param  string $user_id [description]
		 * @return [type]          [description]
		 */
		public function list_organizations( $user_id = '' ) {
			if ( '' !== $user_id ) {
				return $this->run( "users/$user_id/organizations" );
			}

			return $this->run( 'organizations' );
		}

		public function build_zendesk_organization( $name = '', $other = array() ) {
			$org = array(
				'organization' => array(),
			);

			if ( '' !== $name ) {
				$org['organization']['name'] = $name;
			}

			if ( ! empty( $other ) ) {
				foreach ( $other as $key => $val ) {
					$org['organization'][ $key ] = $val;
				}
			}

			return $org;
		}

		/**
		 * Create an organization.
		 *
		 * @param  mixed $organization If a string, an organization will be created
		 *                             with the name equal to that string. Otherwise,
		 *                             send in an object created using the build_zendesk_organization
		 *                             method.
		 * @return [type]               [description]
		 */
		public function create_organization( $organization ) {
			if ( gettype( $organization ) == 'string' ) {
				$organization = $this->build_zendesk_organization( $organization );
			}

			return $this->run( 'organizations', $organization, 'POST' );
		}

		public function update_organization( $organization_id, $organization ) {
			return $this->run( "organizations/$organization_id", $organization, 'PUT' );
		}

		public function delete_organization( $organization_id ) {
			return $this->run( "organizations/$organization_id", array(), 'DELETE' );
		}

		public function delete_many_organizations( $org_ids ) {
			if ( gettype( $org_ids ) === 'string' ) {
				return $this->run( 'organizations/destroy_many.json?ids=' . $org_ids, array(), 'DELETE', false );
			} elseif ( gettype( $org_ids ) === 'array' ) {
				return $this->run( 'organizations/destroy_many.json?ids=' . implode( ',', $org_ids ), array(), 'DELETE', false );
			} else {
				return 'Error: invalid data type.';
			}
		}

		/* Organization Subscriptions */

		/* Organization Memberships */

		public function list_organization_memberships( $organization_id = '', $user_id = '' ) {
			if ( $organization_id === '' && $user_id === '' ) {
				return $this->run( 'organization_memberships' );
			} elseif ( $organization_id === '' ) {
				return $this->run( "users/$user_id/organization_memberships" );
			} else {
				return $this->run( "organizations/$organization_id/organization_memberships" );
			}
		}

		// Maybe make this name shorter?
		public function build_organization_membership( $org_id, $user_id ) {
			return array(
				'organization_membership' => array(
					'user_id'         => $user_id,
					'organization_id' => $org_id,
				),
			); // example
		}

		public function create_membership( $org_id, $user_id = null ){
			if( is_array( $org_id ) ){
				$args = $org_id;
			}else{
				$args = $this->build_organization_membership( $org_id, $user_id );
			}

			return $this->run( 'organization_memberships', $args, 'POST' );
		}

		public function create_many_memberships( $memberships ) {
			return $this->run( 'organization_memberships/create_many', $memberships, 'POST' );
		}

		public function delete_membership( $membership_id ){
			return $this->run( "organization_memberships/$membership_id", array(), 'DELETE' );
		}

		public function delete_many_memberships( $membership_ids ){
			if( is_array( $membership_ids ) ){
				$membership_ids = implode( ',', $membership_ids );
			}

			return $this->run( "organization_memberships/destroy_many.json?ids=$membership_ids", array(), 'DELETE', false );
		}

		public function set_membership_default( $user_id, $membership_id ){
			return $this->run( "users/$user_id/organization_memberships/$membership_id/default", array(), 'PUT' );
		}

		/* Automations */

		/* Macros */

		/* SLA Policies */

		/* Targets */

		/* Triggers */

		/* Views */

		/* Account Settings */

		/* Audit Logs */

		/* Brands */

		/* Dynamic content */

		/* Locales */

		/* Organization Fields */

		/* Schedules */

		/* Sharing agreements */

		/* Support addresses */

		/* Ticket forms */

		/* Ticket fields */

		/**
		 * Can be paginated.
		 *
		 * @return [type] [description]
		 */
		public function list_ticket_fields() {
			return $this->run( 'ticket_fields' );
		}

		/* User fields */

		/**
		 * Can be paginated.
		 *
		 * @return [type] [description]
		 */
		public function list_user_fields() {
			return $this->run( 'user_fields' );
		}

		/* Apps */

		/* App installation locations */

		/* App locations */

		/* OAuth clients */

		/* OAuth tokens */

		/* Authorized global clients */

		/* Activity stream */

		/* Bookmarks */

		/* Push notification devices */

		/* Resource collections */

		/* Tags */

		/* Channel framework */

		/* Twitter channel */


	}
}

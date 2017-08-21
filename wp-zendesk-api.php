<?php


if( !defined( 'ABSPATH'  ) ){ exit; }

if( ! class_exists( 'WpZendeskAPI' ) ){

  class WpZendeskAPI extends WpLibrariesBase {

    private $username;
		private $backup_username = '';

    private $api_key;

    protected $base_uri = '';

    protected $args;

    public function __construct( $domain, $username, $api_key ){
      $this->base_uri = "https://$domain.zendesk.com/api/v2";
      $this->username = $username;
      $this->api_key = $api_key;
    }

    public function get_username(){
      return $this->username;
    }
    public function get_api_key(){
      return $this->api_key;
    }

    public function set_auth( $username, $api_key ){
      $this->username = $username;
      $this->api_key = $api_key;
    }

		public function set_username_for_call( $username ){
			$this->backup_username = $this->username;
			$this->username = $username;
		}

		protected function fetch(){
			$result = parent::fetch();

			if( $this->backup_username !== '' ){
				$this->username = $this->backup_username;
				$this->backup_username = '';
			}

			return $result;
		}

    protected function set_headers(){
      $this->args['headers'] = array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key ),
      );
    }

    protected function run( $route, $args = array(), $method = 'GET' ){
      return $this->build_request( '/' . $route . '.json', $args, $method )->fetch();
    }

    protected function clear(){
      $this->args = array();
    }

    public function search( $search_string ){
      return $this->run( "search", array( 'query' => $search_string ) );
    }

		// Useful search functions
		public function get_tickets_by_email( $email ){
			return $this->run( 'search', array( 'query' => urlencode( 'type:ticket requester:'. $email ) ) );
		}

		public function get_user_id_by_email( $email ){
			return $this->run( 'users/search', array( 'query' => $email ) );
		}

		public function get_requests_by_user( $email ) {
			return $this->run( 'search', array( 'query' => urlencode( 'type:request requester:' . $email . ' status:all' ) ) );
		}

    /* Tickets */

    public function list_tickets( $per_page = 100, $page = 1, $sort_by = '', $sort_order = 'desc' ){
			$args = array(
				'per_page' => $per_page,
				'page' => $page,
			);

			if( $sort_by !== '' ){
				$args['sort_by'] = $sort_by;
				$args['sort_order'] = $sort_order;
			}
      return $this->run( "tickets", $args );
    }

    public function list_tickets_by_user_id_requested( $user_id ){
      return $this->run( "users/$user_id/tickets/requested" );
    }

    public function show_ticket( $ticket_id ){
			return $this->run( "tickets/$ticket_id" );
    }

		// Ids -> Comma separated list or array of ticket IDs to return
    public function show_tickets( $ids){
			if( is_array( $ids ) ){
				$ids = implode( $ids, ',' );
			}
			return $this->run( "tickets/show_many", array( 'ids' => $ids ) );
    }

		public function build_zendesk_ticket( $subject, $description, $requester_name = '', $requester_email = '', $tags = '', $channel = '' ){
			$ticket = array(
				'ticket' => array(
					'subject' => $subject,
					'comment' => array(
						'body' => $description,
					),
				),
			);

			if( $tags != '' ){
				$ticket['ticket']['tags'] = implode(',', $tags);
			}

			if( $channel != '' ){
				$ticket['ticket']['via']['channel'] = $channel;
			}

			return $ticket;
		}

		// Ticket could be a ticket, or it could be the subject. If it's the subject, a ticket will be built off of it.
    public function create_ticket( $ticket, $description = '', $requester_name = '', $requester_email = '', $tags = '', $channel = '' ){

			if( gettype( $ticket ) !== 'object' || gettype( $ticket ) !== 'array' ){
				$ticket = $this->build_zendesk_ticket( $subject, $description, $requester_name, $requester_email, $tags, $channel );
			}

			return $this->run( 'tickets', $ticket, 'POST' );
    }

		// Array of ticket objects.
    public function create_many_tickets( $ticket_objs ){
			return $this->run( 'tickets/create_many', array( 'tickets' => $ticket_objs ), 'POST' );
    }

		// All properties are optional
    public function update_ticket( $ticket_id, $ticket_obj ){
			return $this->run( 'tickets/' . $ticket_id, $ticket_obj, 'PUT' );
    }

		// eh, todo.
    public function update_many_tickets( ){

    }

    public function protect_ticket_update_collisions(){

    }

    public function mark_ticket_spam_and_block_requester(){

    }

    public function mark_many_tickets_as_spam(){

    }

    public function merge_tickets_into_target(){

    }

    public function get_ticket_related_info(){

    }

    public function set_collaborators(){

    }

    public function set_metadata(){

    }

    public function attach_files(){

    }

    public function create_ticket_new_requester(){

    }

    public function set_ticket_fields(){

    }

    public function delete_ticket( $ticket_id ){
			return $this->run( "tickets/$ticket_id", array(), 'DELETE' );
    }

    public function bulk_delete_tickets(){
    }

    public function show_delete_tickets(){

    }

    public function restore_deleted_ticket(){

    }

    public function restore_bulk_deleted_tickets(){

    }

    public function delete_tickets_permanently(){

    }

    public function list_collaborators_ticket(){

    }

    public function list_ticket_incidents(){

    }

    public function list_ticket_problems(){

    }

    public function autocomplete_problems(){

    }

    /* Ticket import */

    public function ticket_import(){

    }

    public function bulk_ticket_import(){

    }

    /* Requests */

    public function list_requests($per_page = 100, $page = 1, $sort_by = '', $sort_order = 'desc' ){
			$args = array(
				'per_page' => $per_page,
				'page' => $page,
			);

			if( $sort_by !== '' ){
				$args['sort_by'] = $sort_by;
				$args['sort_order'] = $sort_order;
			}
			return $this->run( 'requests', $args );
    }

    public function search_requests(){

    }

    public function show_request( $request_id ){
			return $this->run( 'requests/' . $request_id );
    }

		public function build_zendesk_request( $subject = '', $description = '', $comment = '', $status = '', $requester_id = '' ){
			$request = array(
				'request' => array()
			);

			if( $subject != '' ){
				$request['request']['subject'] = $subject;
			}
			if( $description != '' ){
				$request['request']['description'] = $description;
			}
			if( $comment != '' ){
				$request['request']['comment']['body'] = $comment;
			}
			if( $status != '' ){
				$request['request']['status'] = $status;
			}
			if( $requester_id != '' ){
				$request['request']['requester_id'] = $requester_id;
			}

			return $request;
		}

		// Call build request, must fill out subject and description, should fill out requester
    public function create_request( $request ){
			return $this->run( 'requests', $request, 'POST' );
    }

		// Call build_request, recommended fill out comment, can fill out status
		// This function is mostly used for adding a comment.
    public function update_request( $request_id, $request ){
			return $this->run( 'requests/' . $request_id, $request, 'PUT' );
    }

    public function set_collaborators_request(){

    }

    public function add_collaborators_request(){

    }

    public function list_comments_request(){

    }

    public function get_comment_request(){

    }

    /* Attachments */

    public function show_attachment(){

    }

    public function delete_attachment(){

    }

    public function upload_files(){

    }

    public function delete_upload(){

    }

    public function redact_comment_attachment(){

    }

    /* Satisfaction Ratings */

    public function list_satisfaction_ratings(){

    }

    public function show_satisfaction_rating(){

    }

    public function create_satisfaction_rating(){

    }

    /* Satisfaction Reasons */

    public function list_reasons_for_satisfaction_rating(){

    }

    public function show_reasons_for_satisfaction_rating(){

    }

    /* Suspended Tickets */

    public function list_suspended_tickets(){

    }

    public function show_suspended_tickets(){

    }

    public function recover_suspended_ticket(){

    }

    public function recover_suspended_tickets(){

    }

    public function delete_suspended_ticket(){

    }

    public function delete_suspended_tickets(){

    }

    /* Ticket Audits */

    public function list_audits_for_ticket(){

    }

    public function show_audit(){

    }

    public function change_comment_to_private(){

    }

    public function get_audit_events(){

    }

    public function the_via_object(){

    }

    /* Ticket Comments */

    public function create_ticket_comment( $ticket_id, $text, $public = true ){
			$ticket = $this->build_zendesk_ticket();

			$ticket['comment'] = array(
				'public' => $public,
				'body' => $text,
			);

			return $this->run( 'tickets/' . $ticket_id, $ticket, 'PUT' );
    }

		public function create_request_comment( $request_id, $text ){
			$request = $this->build_zendesk_request( '', '', $text );

			return $this->run( 'requests/' . $request_id, $request, 'PUT' );
		}

    public function list_comments( $ticket_id ){
			return $this->run( "tickets/$ticket_id/comments" ); // might need to do a json_decode? TODO: look into
    }

    public function redact_string_in_comment(){

    }

    public function make_comment_private(){

    }

    /* Ticket skips */

    public function record_skip_for_user(){

    }

    public function list_skips_for_account(){

    }

    /* Ticket metrics */

    /* Ticket metric events */

    /* Users */

		public function list_users( $id = '', $is_group = true, $page = '' ){
			$options = array();

			if ( $page != '' ) {
				$options = array( 'page' => $page );
			}

			if( $id != '' ){
				if( $is_group ){
					return $this->run( "groups/$id/users", $options );
				}else{
					return $this->run( "organizations/$id/users", $options );
				}
			}

			return $this->run( "users", $options );
		}

		public function show_user( $user_id ){
			return $this->run( "users/$user_id" );
		}

		// Either a comma separated list, or an array of IDs.
		public function show_users( $user_ids ){
			if( is_array( $user_ids ) ){
				$user_ids = implode( $user_ids, ',' );
			}

			return $this->run( "users/show_many", array( 'ids' => $user_ids ) );
		}

		public function get_user_info( $user_id ){
			return $this->run( "users/$user_id/related" );
		}

		public function build_zendesk_user( $name = '', $email = '', $role = '', $other = array() ){
			$user = array( 'user' => array() );

			if( $name != '' ){
				$user['user']['name'] = $name;
			}
			if( $email != '' ){
				$user['user']['email'] = $email;
			}
			if( $role != '' ){
				$user['user']['role'] = $role;
			}

			if( !empty( $other ) ){
				foreach( $other as $key => $val ){
					$user['user'][$key] = $val;
				}
			}

			return $user;
		}

		// Use the build_zendesk_user function
		public function create_user( $user ){
			return $this->run( 'users', $user, 'POST' );
		}

		public function delete_user( $user_id ){
			return $this->run( "users/$user_id", array(), 'DELETE' );
		}

		public function set_user_password( $user_id, $pass ){
			return $this->run( "users/$user_id/password", array( 'password' => $pass ), 'POST' );
		}

    /* User identities */

    /* Custom agent roles */

    /* End users */

    /* Groups */
		public function list_groups(){
			return $this->run( 'groups' );
		}

		public function show_group( $group_id ){
			return $this->run( "groups/$group_id" );
		}

    /* Group memberships */

    /* Sessions */

    /* Organizations */

    /* Organization Subscriptions */

    /* Organization Memberships */

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

    /* User fields */

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

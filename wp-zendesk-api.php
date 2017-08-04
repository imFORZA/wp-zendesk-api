<?php


if( !defined( 'ABSPATH'  ) ){ exit; }

if( ! class_exists( 'WpZendeskAPI' ) ){

  class WpZendeskAPI extends WpLibrariesBase {

    private $username;

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

    protected function set_headers(){
      $this->args['headers'] = array(
        'Content-Type' => 'application/json',
        'Authorization' => 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key ),
      );
    }

    protected function run( $route, $args = array(), $method = 'GET' ){
      return $this->build_request( $route, $argrs, $method )->fetch();
    }

    protected function clear(){
      $this->args = array();
    }

    public function search( $search_string ){
      return $this->run( "/search.json", array( 'query' => $search_string ) );
    }

    /* Tickets */

    public function list_tickets(){
      return $this->run( "/tickets.json" );
    }

    public function show_ticket( $ticket_id ){

    }

    public function show_multiple_tickets(){

    }

    public function create_ticket(){

    }

    public function create_many_tickets(){

    }

    public function update_ticket(){

    }

    public function update_many_tickets(){

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

    public function delete_ticket(){

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

    public function list_requests(){

    }

    public function search_requests(){

    }

    public function show_request(){

    }

    public function create_request(){

    }

    public function update_request(){

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

    public function create_ticket_comment(){

    }

    public function list_comments(){

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

    /* User identities */

    /* Custom agent roles */

    /* End users */

    /* Groups */

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

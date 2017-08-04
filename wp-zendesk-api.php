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

  }
}

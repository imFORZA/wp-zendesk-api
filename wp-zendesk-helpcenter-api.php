<?php
/**
 * WP ZenDesk HelpCenter API (https://developer.zendesk.com/rest_api/docs/help_center/introduction)
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
				return new WP_Error( 'response-error', sprintf( __( 'Server response code: %d', 'wp-zendesk-helpcenter-api' ), $code ) );
			}

			$body = wp_remote_retrieve_body( $response );

			return json_decode( $body );
		}

		/* CATEGORIES. */

		/**
		 * list_categories function.
		 *
		 * @access public
		 * @param string $locale (default: '')
		 * @param string $sort_by (default: '')
		 * @param string $sort_order (default: '')
		 * @return void
		 */
		public function list_categories( $locale = '', $sort_by = '', $sort_order = '' ) {

			$request = 'https://' . static::$install_name . '.zendesk.com/api/v2/help_center/' . $locale . '/categories.json';

			return $this->fetch( $request );

		}

		public function get_category( $category_id ) {

		}

		public function add_category( $category, $locale = 'en-us' ) {

		}

		public function update_category( $category_id, $locale = 'en-us' ) {

		}

		public function update_category_locale( $category_id ) {

		}

		public function delete_category( $category_id ) {

		}

		/* SECTIONS. */

		public function list_sections( $section_id, $locale = 'en-us' ) {

		}

		public function get_section( $section_id, $locale = 'en-us' ) {

		}

		public function create_section( $section_id, $locale = 'en-us' ) {

		}

		public function update_section( $section_id, $locale = 'en-us' ) {

		}

		public function delete_section( $section_id ) {

		}

		/* ARTICLES. */

		public function get_articles( $locale = 'en-us' ) {

			$request = 'https://' . static::$install_name . '.zendesk.com/api/v2/help_center/' . $locale . '/articles.json';

			return $this->fetch( $request );


		}

		public function get_category_articles( $category_id, $locale = 'en-us', $sort_by = '', $sort_order = '', $label_names = '' ) {

		}

		public function get_section_articles( $section_id, $locale = 'en-us', $sort_by = '', $sort_order = '', $label_names = '' ) {

		}

		public function get_user_articles( $user_id, $sort_by = '', $sort_order = '', $label_names = '' ) {

		}

		public function get_incremental_articles( $start_time, $sort_by = '', $sort_order = '', $label_names = '' ) {

		}

		public function add_article( $article_id, $locale = 'en-us' ) {

		}

		public function update_article( $article_id, $locale = 'en-us' ) {

		}

		public function archive_article( $article_id ) {

		}

		public function update_article_source_locale( $article_id ) {

		}

		public function associate_attachments_to_article( $article_id ) {

		}

		/* ARTICLE COMMENTS. */

		public function get_comments( $article_id, $locale = 'en-us' ) {

		}

		public function show_comment( $comment_id, $article_id, $locale = 'en-us' ) {

		}

		public function add_comment( $article_id ) {

		}

		public function update_comment( $comment_id, $article_id ) {

		}

		public function delete_comment( $comment_id, $article_id ) {

		}


		/* ARTICLE LABELS. */

		public function get_article_labels( $locale = 'en-us' ) {

		}

		public function get_label_details( $label_id ) {

		}

		public function list_article_labels( $label_id ) {

		}

		public function create_label( $label_id ) {

		}

		public function delete_label( $label_id ) {

		}

		public function get_articles_by_label( $label_names ) {

			// GET /api/v2/help_center/articles.json?label_names=photos,camera

		}

		/* ARTICLE ATTACHMENTS. */

		/* TRANSLATIONS. */

		/* SEARCH. */

		public function search_articles( $search_string, $created_before = '', $created_after = '', $created_at = '', $updated_before = '', $updated_after = '', $updated_at = '', $label_names = '', $category = '', $section = '' ) {

		}

		/* TOPICS. */

		public function get_topics() {

		}

		public function show_topics( $topic_id ) {

		}

		public function add_topic() {

		}

		public function update_topic( $topic_id ) {

		}

		public function delete_topic( $topic_id ) {

		}

		/* POSTS. */

		public function get_posts( $filter_by = '', $sort_by = '', $include = '' ) {

		}

		public function get_topic_posts( $topic_id, $filter_by = '', $sort_by = '', $include = '' ) {

		}

		public function get_topic_post( $user_id, $filter_by = '', $sort_by = '', $include = '' ) {

		}

		public function get_post( $post_id ) {

		}

		public function add_post() {

		}

		public function update_post( $post_id ) {

		}

		public function delete_post( $post_id ) {

		}

		/* POST COMMENTS. */

		public function get_post_comments( $post_id, $include = '' ) {

		}

		public function get_user_comments( $user_id, $include = '' ) {

		}

		public function get_post_comment( $post_id, $comment_id, $include = '' ) {

		}

		public function add_comments( $post_id ) {

		}

		public function update_comments( $post_id, $comment_id ) {

		}

		public function delete_comments( $post_id, $comment_id ) {

		}

		/* SUBSCRIPTIONS. */

		public function get_article_subscriptions( $article_id, $locale = 'en-us', $include = '' ) {

		}

		public function get_article_subscription( $subscription_id, $article_id, $locale = 'en-us', $include = '' ) {

		}

		public function add_article_subscription( $article_id ) {

		}

		public function delete_article_subscription( $article_id, $subscription_id ) {

		}

		public function list_section_subscriptions( $section_id, $locale = 'en-us', $include = '' ) {

		}

		public function show_section_subscription( $section_id, $subscription_id, $locale = 'en-us', $include = '' ) {

		}

		public function add_section_subscription( $section_id, $include_comments = '', $user_id = '' ) {

		}

		public function delete_section_subscription( $section_id, $subscription_id ) {

		}

		public function get_subscriptions_by_user( $user_id, $include = '' ) {

		}

		public function get_post_subscriptions( $post_id, $include = '' ) {

		}

		public function get_post_subscription( $post_id, $subscription_id, $include = '' ) {

		}

		public function add_post_subscription( $post_id ) {

		}

		public function delete_post_subscription( $post_id, $subscription_id ) {

		}

		public function get_topic_subscriptions( $topic_id, $include = '' ) {

		}

		public function get_topic_subscription( $topic_id, $subscription_id, $include = '' ) {

		}

		public function add_topic_subscription( $topic_id ) {

		}

		public function delete_topic_subscription( $topic_id, $subscription_id ) {

		}


		/* VOTES. */

		public function get_user_votes( $user_id ) {

		}

		public function get_article_votes( $article_id, $locale = 'en-us', $include = '' ) {

		}

		public function get_article_comment_votes( $article_id, $comment_id, $locale = 'en-us', $include = '' ) {

		}

		public function get_posts_votes( $post_id, $include = '' ) {

		}

		public function get_post_comment_votes( $post_id, $comment_id, $include = '' ) {

		}

		public function get_vote( $vote_id, $include = '' ) {

		}

		public function delete_vote( $vote_id ) {

		}

		public function add_article_vote_up( $article_id ) {

		}

		public function add_article_vote_down( $article_id ) {

		}

		public function add_article_comments_vote_up( $article_id, $comment_id ) {

		}

		public function add_article_comments_vote_down( $article_id, $comment_id ) {

		}

		public function add_post_vote_up( $post_id ) {

		}

		public function add_post_vote_down( $post_id ) {

		}

		public function add_post_comments_vote_up( $post_id, $comment_id ) {

		}

		public function add_post_comments_vote_down( $post_id, $comment_id ) {

		}

		/* ACCESS POLICIES. */

		public function get_section_access_policy( $section_id ) {

		}

		public function get_topic_access_policy( $topic_id ) {

		}

		public function update_section_access_policy( $section_id ) {

		}

		public function update_topic_access_policy( $topic_id ) {

		}

		/* USER SEGMENTS. */

		public function get_user_segments() {

		}

		public function get_user_segments_applicable() {

		}

		public function get_user_segment( $user_segment_id ) {

		}

		public function get_sections_with_user_segment( $user_segment_id ) {

		}

		public function get_topics_with_user_segment( $user_segment_id ) {

		}

		public function add_user_segments() {

		}

		public function update_user_segment( $user_segment_id ) {

		}

		public function delete_user_segment( $user_segment_id ) {

		}

	}
}

<?php
/**
 * WP ZenDesk HelpCenter API (https://developer.zendesk.com/rest_api/docs/help_center/introduction)
 *
 * @package WP-ZD-HelpCenter-API
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) { exit; }


if ( ! class_exists( 'WPZendeskHelpCenterAPI' ) ) {

	/**
	 * Seny API Class.
	 */
	class WPZendeskHelpCenterAPI extends WpLibrariesBase {

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
		public function __construct( $domain, $username, $api_key, $debug = false ){
			$this->base_uri = "https://$domain.zendesk.com/api/v2/";
			$this->username = $username;
			$this->api_key = $api_key;
			$this->is_debug = $debug;
		}

		protected function set_headers(){
			$this->args['headers'] = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $this->username . '/token:' . $this->api_key )
			);
		}

		protected function run( $route, $args = array(), $method = 'GET', $add_data_type = true ){
			return $this->build_request( $route . ($add_data_type?'.json':''), $args, $method )->fetch();
		}

		protected function clear(){
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
		public function build_zendesk_pagination( $per_page = 100, $page = 1, $sort_by = '', $sort_order = 'desc' ){
			$args = array(
				'per_page' => $per_page,
				'page' => $page,
			);

			if( $sort_by !== '' ){
				$args['sort_by'] = $sort_by;
				$args['sort_order'] = $sort_order;
			}

			return $args;
		}

		/* CATEGORIES. */

		/**
		 * List categories function.
		 *
		 * The {locale} is required only for end users and anomynous users.
		 * Admins and agents can omit it.
		 *
		 * The response will list only the categories that the agent, end user, or
		 * anonymous user can view in Help Center.
		 *
		 * AS LONG AS THE API IS RUNNING OFF THEIR AUTHENTICATION.
		 * You can sort the results with the sort_by and sort_order query string parameters.
		 *
		 * @access public
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/categories#list-categories
		 * @param string $locale (default: '')
		 * @param string $sort_by (default: '') Can be position, created_at, or updated_at
		 * @param string $sort_order (default: '') Can be asc or desc
		 * @return void
		 */
		public function list_categories( $locale = 'en-us', $sort_by = 'position', $sort_order = 'desc', $page = 1 ) {
			$args = array(
				'sort_by' => $sort_by,
				'sort_order' => $sort_order,
				'page' => $page
			);

			return $this->run( "help_center/$locale/categories", $args );
		}

		/**
		 * The {locale} is required only for end users and anomynous users. Admins and agents can omit it.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/categories#show-category
		 * @param  [type] $category_id [description]
		 * @param  string $locale      [description]
		 * @return [type]              [description]
		 */
		public function show_category( $category_id, $locale = 'en-us' ) {
			return $this->run( "help_center/$locale/categories/$category_id" );
		}

		public function build_zendesk_category( $name = '', $locale = '', $description = '', $position = '', $other = array(), $raw = false ){
			$cat = array();

			if( '' !== $name ){
				$cat['name'] = $name;
			}

			if( '' !== $locale ){
				$cat['locale'] = $locale;
			}

			if( '' !== $description ){
				$cat['description'] = $description;
			}

			if( '' !== $position && is_int( $position ) ){
				$cat['position'] = $position;
			}

			if( !empty( $other ) ){
				foreach( $other as $key => $val ){
					$cat[$key] = $val;
				}
			}

			if( $raw ){
				return $cat;
			}

			return array( 'category' => $cat );
		}

		/**
		 * See build_zendesk_category.
		 *
		 * @param  [type] $category [description]
		 * @param  string $locale   [description]
		 * @return [type]           [description]
		 */
		public function create_category( $category, $locale = 'en-us' ) {
			if( isset( $category['locale'] ) ){
				return $this->run( 'categories', $category, 'POST' );
			}else{
				return $this->run( "help_center/$locale/categories", $category, 'POST' );
			}
		}

		/**
		 * These endpoints only update category-level metadata such as the sorting
		 * position. They don't update category translations.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/categories#update-category
		 * @param  [type] $category_id [description]
		 * @param  [type] $category    [description]
		 * @param  string $locale      [description]
		 * @return [type]              [description]
		 */
		public function update_category( $category_id, $category, $locale = '' ) {
			if( '' !== $locale ){
				return $this->run( "help_center/$locale/categories/$category_id", $category, 'PUT' );
			}else{
				return $this->run( "help_center/categories/$category_id", $category, 'PUT' );
			}
		}

		/**
		 * Update category source locale.
		 * The endpoint updates category source_locale property.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/categories#update-category-source-locale
		 * @param  [type] $category_id [description]
		 * @return [type]              [description]
		 */
		public function update_category_locale( $category_id, $updated_source_locale ) {
			return $this->run( "help_center/categories/$category_id/source_locale", array( 'category_locale' => $updated_source_locale ), 'PUT' );
		}

		/**
		 * Delete category.
		 *
		 * WARNING::
		 * EVERY SECTION AND ALL ARTICLES IN THE CATEGORY WILL ALSO BE DELETED.
		 *
		 * @param  [type] $category_id [description]
		 * @return [type]              [description]
		 */
		public function delete_category( $category_id ) {
			return $this->run( "help_center/categories/$category_id", array(), 'DELETE' );
		}

		/* SECTIONS. */

		/**
		 * List sections.
		 *
		 * Lists all the sections in Help Center or in a specific category.
		 *
		 * The {locale} is required only for end users and anomynous users. Admins and
		 * agents can omit it.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/sections#list-sections
		 * @param  [type] $section_id  [description]
		 * @param  string $category_id [description]
		 * @param  string $locale      [description]
		 * @return [type]              [description]
		 */
		public function list_sections( $section_id, $category_id = '', $locale = 'en-us' ) {
			if( '' !== $category_id ){
				return $this->run( "help_center/$locale/categories/$category_id/sections" );
			}else{
				return $this->run( "help_center/$locale/sections" );
			}
		}

		/**
		 * Show section.
		 *
		 * Locale is only needed for end users and anonymouses.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/sections#show-section
		 * @param  [type] $section_id [description]
		 * @param  string $locale     [description]
		 * @return [type]             [description]
		 */
		public function show_section( $section_id, $locale = 'en-us' ) {
			return $this->run( "help_center/$locale/sections/$section_id" );
		}

		public function build_zendesk_section( $name = '', $description = '', $locale = '', $position = '', $other = array(), $raw = false ){
			$sect = array();

			if( '' !== $name ){
				$sect['name'] = $name;
			}

			if( '' !== $description ){
				$sect['description'] = $description;
			}

			if( '' !== $locale ){
				$sect['locale'] = $locale;
			}

			if( '' !== $position ){
				$sect['position'] = $position;
			}

			if( !empty( $other ) ){
				foreach( $other as $key => $val ){
					$sect[$key] = $val;
				}
			}

			if( $raw ){
				return $sect;
			}

			return array( 'section' => $sect );
		}

		/**
		 * See build_zendesk_section for $section.
		 *
		 * Creates a section in a given category. You must specify a section name and
		 * locale. The locale can be omitted if it's specified in the URL. Optionally,
		 * you can specify multiple translations for the section. The specified locales
		 * must be enabled for the current Help Center.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/sections#create-section
		 * @param  [type] $category_id [description]
		 * @param  [type] $section     [description]
		 * @param  string $locale      (Default: 'en-us') if blank, must be admin (but acceptable).
		 * @return [type]              [description]
		 */
		public function create_section( $category_id, $section, $locale = 'en-us' ) {
			if( $locale == '' ){
				return $this->run( "help_center/categories/$category_id/sections", $section, 'POST' );
			}else{
				return $this->run( "help_center/$locale/categories/$category_id/sections", 'POST' );
			}
		}

		/**
		 * Update section.
		 *
		 * These endpoints only update section-level metadata such as the sorting position.
		 * They don't update section translations.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/sections#update-section
		 * @param  [type] $section_id [description]
		 * @param  [type] $section    [description]
		 * @param  string $locale     [description]
		 * @return [type]             [description]
		 */
		public function update_section( $section_id, $section, $locale = 'en-us' ) {
			if( $locale == '' ){
				return $this->run( "help_center/sections/$section_id", $section, 'PUT' );
			}else{
				return $this->run( "help_center/$locale/sections/$setion_id", $section, 'PUT' );
			}
		}

		/**
		 * Update section source locale.
		 *
		 * This endpoint lets you set a section's source language to something other
		 * than the default language of your Help Center. For example, if the default
		 * language of your Help Center is English but your KB has a section only for
		 * Japanese customers, you can set the section's source locale to 'ja'.
		 *
		 * @param  [type] $section_id [description]
		 * @param  [type] $locale     [description]
		 * @return [type]             [description]
		 */
		public function update_source_locale( $section_id, $locale ){
			return $this->run( "help_center/sections/$section_id/source_locale", array( 'section_locale', $locale ), 'PUT' );
		}

		/**
		 * Delete section.
		 * <b>WARNING: All articles in the section will also be deleted.</b>
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/sections#delete-section
		 * @param  [type] $section_id [description]
		 * @return [type]             [description]
		 */
		public function delete_section( $section_id ) {
			return $this->run( "help_center/sections/$section_id", array(), 'DELETE' );
		}

		/* ARTICLES. */

		/**
		 * These endpoints let you list all articles in Help Center, all articles in
		 * a given category or section, or all the articles authored by a specific agent.
		 * You can also list all articles with metadata that changed since a specified
		 * start time.
		 *
		 * To list articles by content changes, not metadata changes, filter the articles
		 * by the updated_at timestamp of the articles' translations.
		 *
		 * The {locale} is required only for end users or anonymous users. Admins and
		 * agents can omit it.
		 *
		 * You can also use the Search API to list articles. See Search.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/articles#list-articles
		 * @param  string $locale             [description]
		 * @param  [type] $pages [description]
		 * @return [type]                     [description]
		 */
		public function list_articles( $locale = 'en-us', $label_names = '', $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			if( '' !== $label_names ){
				$pages['label_names'] = $label_names;
			}

			return $this->run( "help_center/$locale/articles", $pages );
		}

		public function list_articles_by_category( $category_id, $locale = 'en-us', $label_names = '', $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			if( '' !== $label_names ){
				$pages['label_names'] = $label_names;
			}

			return $this->run( "help_center/$locale/categories/$category_id/articles", $pages );
		}

		public function list_articles_by_section( $section_id, $locale = 'en-us', $label_names = '', $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			if( '' !== $label_names ){
				$pages['label_names'] = $label_names;
			}

			return $this->run( "help_center/$locale/sections/$section_id/articles", $pages );
		}

		public function list_articles_by_user( $user_id, $label_names = '', $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			if( '' !== $label_names ){
				$pages['label_names'] = $label_names;
			}

			return $this->run( "help_center/users/$user_id/articles", $pages );
		}

		public function list_articles_by_incremental( $start_time, $label_names = '', $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			if( '' !== $label_names ){
				$pages['label_names'] = $label_names;
			}

			return $this->run( "help_center/incremental/articles", $pages );
		}

		/**
		 * Shows the properties of an article.
		 *
		 * The {locale} is required only for end users. Admins and agents can omit it.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/articles#show-article
		 * @param  [type] $article_id [description]
		 * @param  string $locale     [description]
		 * @return [type]             [description]
		 */
		public function show_article( $article_id, $locale = 'en-us' ){
			return $this->run( "help_center/$locale/articles/$article_id" );
		}

		public function build_zendesk_article( $title = '', $body = '', $locale = '', $position = '', $other = array(), $raw = false){
			$art = array();

			if( '' !== $title ){
				$art['title'] = $title;
			}
			if( '' !== $body ){
				$art['body'] = $body;
			}
			if( '' !== $locale ){
				$art['locale'] = $locale;
			}
			if( '' !== $position ){
				$art['position'] = $position;
			}

			if( !empty( $other ) ){
				foreach( $other as $key => $val ){
					$art[$key] = $val;
				}
			}

			if( $raw ){
				return $art;
			}

			return array( 'article' => $art );
		}

		/**
		 * Create article.
		 *
		 * Creates an article in the specified section. You must specify an article title
		 * and locale. The locale can be omitted if it's specified in the URL. Optionally,
		 * you can specify multiple translations for the article. The specified locales
		 * must be enabled for the current Help Center.
		 *
		 * The current user is automatically subscribed to the article and will receive
		 * notifications when it changes.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/articles#create-article
		 * @param  [type] $section_id [description]
		 * @param  array  $article    See $this->build_zendesk_article.
		 * @param  string $locale     [description]
		 * @return [type]             [description]
		 */
		public function create_article( $section_id, $article, $locale = 'en-us' ) {
			if( isset( $article['locale'] ) ){
				return $this->run( "help_center/sections/$section_id/articles", $article, 'POST' );
			}else{
				return $this->run( "help_center/$locale/sections/$section_id/articles", $article, 'POST' );
			}
		}

		/**
		 * Update article.
		 *
		 * These endpoints update article-level metadata such as its promotion status
		 * or sorting position. The endpoints do not update translation properties such
		 * as the article's title, body, locale, or draft. See Translations.
		 *
		 * @param  [type] $article_id [description]
		 * @param  [type] $article    [description]
		 * @param  string $locale     [description]
		 * @return [type]             [description]
		 */
		public function update_article( $article_id, $article, $locale = 'en-us' ) {
			if( isset( $article['locale'] ) ){
				return $this->run( "help_center/articles/$article_id", $article, 'PUT' );
			}else{
				return $this->run( "help_center/$locale/articles/$article_id", $article, 'PUT' );
			}
		}

		/**
		 * Archives the article. You can restore the article using the Help Center user
		 * interface. See <a href="https://support.zendesk.com/hc/en-us/articles/235721587">
		 * Viewing and restoring archived articles</a>.
		 *
		 * @param  [type] $article_id [description]
		 * @return [type]             [description]
		 */
		public function archive_article( $article_id ) {
			return $this->run( "help_center/articles/$article_id", array(), 'DELETE' );
		}

		/**
		 * Update article source locale.
		 *
		 * The endpoint updates article source_locale property
		 *
		 * @param  [type] $article_id    [description]
		 * @param  [type] $source_locale [description]
		 * @return [type]                [description]
		 */
		public function update_article_source_locale( $article_id, $source_locale ) {
			return $this->run( "help_center/articles/$article_id/source_locale", array( 'article_locale', $source_locale ), 'PUT' );
		}

		/**
		 * Associate attachments in bulk to article.
		 *
		 * You can associate attachments in bulk to only one article at a time, with
		 * a maximum of 20 attachments per request.
		 *
		 * To create the attachments, see <a href="https://developer.zendesk.com/rest_api/docs/help_center/article_attachments#create-unassociated-attachment">
		 * Create Unassociated Attachment</a>.
		 *
		 * @param  [type] $article_id     [description]
		 * @param  [type] $attachment_ids Array of attachment IDs (unassociated).
		 * @return [type]                 [description]
		 */
		public function associate_attachments_to_article( $article_id, $attachment_ids ) {
			return $this->run( "help_center/articles/$article_id/bulk_attachments", array( 'attachment_ids' => $attachment_ids ), 'POST' );
		}

		/* ARTICLE COMMENTS. */

		/**
		 * List comments.
		 *
		 * Lists the comments created by a specific user, or all comments made by all
		 * users on a specific article.
		 *
		 * The {locale} for the article comments is required only for end users. Admins
		 * and agents can omit it.
		 *
		 * @param  [type] $article_id [description]
		 * @param  string $locale     [description]
		 * @return [type]             [description]
		 */
		public function get_comments( $article_id, $locale = 'en-us' ) {
			return $this->run( "help_center/$locale/articles/$article_id/comments" );
		}

		public function get_comments_by_user( $user_id ){
			return $this->run( "help_center/users/$user_id/comments" );
		}

		/**
		 * Show comment
		 *
		 * Shows the properties of the specified comment.
		 *
		 * The {locale} is required only for end users and anomynous users. Admins
		 * and agents can omit it.
		 *
		 * @param  [type] $comment_id [description]
		 * @param  [type] $article_id [description]
		 * @param  string $locale     [description]
		 * @return [type]             [description]
		 */
		public function show_comment( $article_id, $comment_id, $locale = 'en-us' ) {
			return $this->run( "help_center/$locale/articles/$article_id/comments/$comment_id" );
		}

		public function build_zendesk_comment( $body = '', $created_at = '', $notify_subscribers = '', $other = array(), $raw = false ){
			$com = array();

			if( '' !== $body ){
				$com['body'] = $body;
			}

			if( '' !== $created_at ){
				$com['created_at'] = $created_at;
			}

			if( '' !== $notify_subscribers ){
				$com['notify_subscribers'] = $notify_subscribers;
			}

			if( !empty( $other ) ){
				foreach( $other as $key => $val ){
					$com[$key] = $val;
				}
			}

			if( $raw ){
				return $com;
			}

			return array( 'comment' => $com );
		}

		/**
		 * Create comment.
		 *
		 * Adds a comment to the specified article. Because comments are associated
		 * with a specific article translation, or locale, you must specify a locale.
		 *
		 * Agents with the Help Center manager role can optionally supply a created_at
		 * as part of the comment object. If it is not provided created_at is set to
		 * the current time.
		 *
		 * Supplying a notify_subscribers parameter with a value of false will prevent
		 * subscribers to the comment's article from receiving a comment creation email
		 * notification. This can be helpful when bulk importing comments.
		 *
		 * See $this->build_zendesk_comment
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/comments#create-comment
		 * @param  [type] $article_id [description]
		 * @param  [type] $comment    If string, will be the comment as the current user.
		 *                            If array, passed in directly.
		 * @return [type]             [description]
		 */
		public function create_article_comment( $article_id, $comment ) {
			if( gettype( $comment ) === 'string' ){
				$comment = $this->build_zendesk_comment( $comment );
			}
			return $this->run( "help_center/articles/$article_id/comments", $comment, 'POST' );
		}

		public function update_comment( $article_id, $comment_id, $comment ) {
			return $this->run( "help_center/articles/$article_id/comments/$comment_id", $comment, 'PUT' );
		}

		public function delete_comment( $article_id, $comment_id ) {
			return $this->run( "help_center/articles/$article_id/comments/$comment_id", array(), 'DELETE' );
		}

		/* ARTICLE LABELS. */

		/**
		 * List article labels
		 *
		 * You can set $locale to '' to get generic labels
		 * @param  string $locale [description]
		 * @return [type]         [description]
		 */
		public function list_labels( $locale = 'en-us', $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			return $this->run( "help_center/$locale/articles/labels", $page );
		}

		/**
		 * Show label.
		 *
		 * Shows the properties of the specified label.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/labels#show-label
		 * @param  [type] $label_id [description]
		 * @return [type]           [description]
		 */
		public function show_label( $label_id ){
			return $this->run( "help_center/articles/labels/$label_id" );
		}

		/**
		 * List article labels.
		 *
		 * Lists all the labels in a given article.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/labels#list-article-labels
		 * @param  [type] $article_id [description]
		 * @param  [type] $pages      [description]
		 * @return [type]             [description]
		 */
		public function list_article_labels( $article_id, $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			return $this->run( "help_center/articles/$article_id/labels", $pages );
		}

		/**
		 * Create label
		 *
		 * @param  [type] $label_id [description]
		 * @param  [type] $name     [description]
		 * @return [type]           [description]
		 */
		public function create_label( $label_id, $name ) {
			return $this->run( "help_center/articles/$label_id/labels", array( 'label' => array( 'name' => $name ) ), 'POST' );
		}

		public function delete_label_from_article( $article_id, $label_id ) {
			return $this->run( "help_center/articles/$article_id/labels/$label_id", array(), 'DELETE' );
		}

		/**
		 * Search for articles by label
		 *
		 * The <a href="https://developer.zendesk.com/rest_api/docs/help_center/articles.html#search-articles">
		 * search articles endpoint</a> takes labels into account, but if you want to
		 * search for articles with specific labels, you can use the
		 * <a href="https://developer.zendesk.com/rest_api/docs/help_center/articles.html#list-articles">
		 * list articles</a> endpoint and filter by label names.
		 *
		 * @param  [type] $label_names [description]
		 * @return [type]              [description]
		 */
		public function get_articles_by_label( $label_names ) {
			return $this->run( 'help_center/articles', array( 'label_names' => $label_names ) );
		}

		// TODO:
		/* ARTICLE ATTACHMENTS. */

		/* TRANSLATIONS. */

		/* SEARCH. */


		/**
		 * Search articles function.
		 *
		 * Returns an array of articles.
		 *
		 * Articles returned by the search endpoint contain two additional properties:
		 *
		 * result_type (string, read only), for articles always "article".
		 *
		 * snippet (string, read only), the portion that is relevant to the query.
		 *
		 * You must specific at least one of:
		 * 		query
		 * 		category
		 * 		section
		 * 		label_names
		 *
		 * Honestly just see the link below.
		 *
		 * @access public
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/search#search-articles
		 * @param string $query The query string.
		 * @return void
		 */
		public function search_articles( $query, $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}
			return $this->run( 'help_center/articles/search', array_merge( array( 'query' => $query ), $pages ) );
		}

		/* TOPICS. */

		/**
		 * List topics.
		 *
		 * Lists all topics.
		 * @param  [type] $pages [description]
		 * @return [type]        [description]
		 */
		public function list_topics( $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			return $this->run( 'community/topics', $pages );
		}

		/**
		 * Show topic
		 *
		 * Shows information about a single topic.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/topics#show-topic
		 * @param  [type] $topic_id [description]
		 * @return [type]           [description]
		 */
		public function show_topics( $topic_id ) {
			return $this->run( "community/topics/$topic_id" );
		}

		/**
		 * Helper function for building a zendesk topic.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/topics#content
		 * @param  string $name        [description]
		 * @param  string $description [description]
		 * @param  string $position    [description]
		 * @param  array  $other       [description]
		 * @return [type]              [description]
		 */
		public function build_zendesk_topic( $name = '', $description = '', $position = '', $other = array(), $raw = false ){
			$topic = array();

			if( '' !== $name ){
				$topic['name'] = $name;
			}
			if( '' !== $description ){
				$topic['description'] = $description;
			}
			if( '' !== $position ){
				$topic['position'] = $position;
			}

			if( !empty( $other ) ){
				foreach( $other as $key => $val ){
					$topic[$key] = $val;
				}
			}

			if( $raw ){
				return $topic;
			}

			return array( 'topic' => $topic );
		}

		/**
		 * Create topic.
		 *
		 * Agents with the Help Center Manager role can optionally supply a created_at
		 * as part of the topic object. If it is not provided created_at is set to the
		 * current time.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/topics#create-topic
		 * @param  [type] $topic If a string, will be the name of the topic. Otherwise,
		 *                       see $this->build_zendesk_topic.
		 * @return [type]        [description]
		 */
		public function create_topic( $topic ) {
			if( gettype( $topic ) === 'string' ){
				$topic = $this->build_zendesk_topic( $topic );
			}

			return $this->run( 'community/topics', $topic, 'POST' );
		}

		/**
		 * Update a topic
		 *
		 * See $this->build_zendesk_topic.
		 *
		 * @param  [type] $topic_id [description]
		 * @param  [type] $topic    [description]
		 * @return [type]           [description]
		 */
		public function update_topic( $topic_id, $topic ) {
			return $this->run( "community/topics/$topic_id", $topic, 'PUT' );
		}

		public function delete_topic( $topic_id ) {
			return $this->run( "community/topics/$topic_id", array(), 'DELETE' );
		}

		/* POSTS. */

		/**
		 * List posts.
		 *
		 * Lists all posts, all posts in a given topic, or all posts by a specific user.
		 * When listing by specific user, the posts of the user making the request
		 * can be listed by specifying me as the id.
		 *
		 * @param  [type] $pages [description]
		 * @return [type]        [description]
		 */
		public function list_posts( $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			return $this->run( 'community/posts', $pages );
		}

		public function list_posts_by_topic( $topic_id, $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			return $this->run( "community/$topic_id/posts", $pages );
		}

		public function list_posts_by_user( $user_id, $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			return $this->run( "community/$user_id/posts", $pages );
		}

		/**
		 * Show post
		 *
		 * Gets information about a given post.
		 *
		 * @param  [type] $post_id [description]
		 * @return [type]          [description]
		 */
		public function show_post( $post_id ) {
			return $this->run( "community/posts/$post_id" );
		}

		/**
		 * Helper function for building a zendesk post.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/posts#posts
		 * @param  string $title   [description]
		 * @param  string $details [description]
		 * @param  array  $other   [description]
		 * @return [type]          [description]
		 */
		public function build_zendesk_post( $topic_id = '', $title = '', $details = '', $other = array(), $raw = false ){
			$post = array();

			if( '' !== $title ){
				$post['title'] = $title;
			}

			if( '' !== $details ){
				$post['details'] = $details;
			}

			if( !empty( $other ) ){
				foreach( $other as $key => $val ){
					$post[$key] = $val;
				}
			}

			if( $raw ){
				return $post;
			}

			return array( 'post' => $post );
		}

		/**
		 * Create a post.
		 *
		 * @param  [type] $post  The post object. Alternatively, the topic ID, and provide
		 *                       an argument for title.
		 * @param  string $title [description]
		 * @return [type]        [description]
		 */
		public function create_post( $post, $title = '' ) {
			if( gettype( $post ) !== 'array' ){
				$post = $this->build_zendesk_post( $post, $title );
			}

			return $this->run( 'community/posts', $post, 'POST' );
		}

		/**
		 * Update a post.
		 *
		 * Either agents or the end user who created the post is allowed to modify it.
		 *
		 * @param  [type] $post_id [description]
		 * @param  [type] $post    [description]
		 * @return [type]          [description]
		 */
		public function update_post( $post_id, $post ) {
			return $this->run( "community/posts/$post_id", $post, 'PUT' );
		}

		public function delete_post( $post_id ) {
			return $this->run( "community/posts/$post_id", array(), 'DELETE' );
		}

		/* POST COMMENTS. */

		/* https://developer.zendesk.com/rest_api/docs/help_center/post_comments#post-comments */

		public function list_post_comments( $post_id, $include = array(), $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}
			return $this->run( "community/posts/$post_id/comments", array_merge( array( 'include' => $includes ), $pages ) );
		}

		public function list_post_comments_by_user( $user_id, $include = array(), $pages = null  ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}
			return $this->run( "community/users/$user_id/comments", array_merge( array( 'include' => $includes ), $pages ) );
		}

		public function show_post_comment( $post_id, $comment_id, $include = array() ) {
			return $this->run( "community/posts/$post_id/comments/$comment_id", array( 'include' => $includes ) );
		}

		// Don't need to make a build_zendesk_comment function, since it alreayd exists
		// and would be exactly identical.

		/**
		 * Create a comment on a post
		 *
		 * If comment is a string, then the comment will be created with that as the
		 * body. Otherwise, pass in a comment object (see $this->build_zendesk_comment).
		 *
		 * @param  [type] $post_id [description]
		 * @param  [type] $comment [description]
		 * @return [type]          [description]
		 */
		public function create_post_comment( $post_id, $comment ) {
			if( gettype( $comment ) === 'string' ){
				$comment = $this->build_zendesk_comment( $comment );
			}

			return $this->run( "community/posts/$post_id/comments", $comment, 'POST' );
		}

		/**
		 * Update comment
		 *
		 * Updates the specified comment
		 *
		 * @param  [type] $post_id    [description]
		 * @param  [type] $comment_id [description]
		 * @param  [type] $comment    [description]
		 * @return [type]             [description]
		 */
		public function update_comments( $post_id, $comment_id, $comment ) {
			return $this->run( "community/posts/$post_id/comments/$comment_id", $comment, 'PUT' );
		}

		public function delete_comments( $post_id, $comment_id ) {
			return $this->run( "community/posts/$post_id/comments/$comment_id", array(), 'DELETE' );
		}

		/* SUBSCRIPTIONS. */

		// TODO: ADD THE HELP_CENTER PREFIX.

		public function list_article_subscriptions( $article_id, $locale = 'en-us', $include = array() ) {
			return $this->run( "help_center/$locale/articles/$article_id/subscriptions", $include );
		}

		public function show_article_subscription( $subscription_id, $article_id, $locale = 'en-us', $include = array() ) {
			return $this->run( "help_center/$locale/articles/$article_id/subscriptions/$subscription_id", $include );
		}

		/**
		 * Create article subscription.
		 *
		 * Creates a subscription to a given article.
		 *
		 * Designed for end users, but.
		 * Agents with the Help Center manager role can optionally supply a user_id value.
		 * If provided, the user associated with user_id will be subscribed to the article.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/subscriptions#create-article-subscription
		 * @param  [type] $article_id    [description]
		 * @param  string $source_locale [description]
		 * @param  string $user_id       [description]
		 * @return [type]                [description]
		 */
		public function create_article_subscription( $article_id, $source_locale = 'en-us', $user_id = '' ) {
			$args = array(
				'subscription' => array(
					'source_locale' => $source_locale
				)
			);

			if( '' !== $user_id ){
				$args['subscription']['user_id'] = $user_id;
			}

			return $this->run( "help_center/articles/$article_id/subscriptions", $args, 'POST' );
		}

		/**
		 * Designed for end-users
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/subscriptions#delete-article-subscription
		 * @param  [type] $article_id      [description]
		 * @param  [type] $subscription_id [description]
		 * @return [type]                  [description]
		 */
		public function delete_article_subscription( $article_id, $subscription_id ) {
			return $this->run( "help_center/articles/$article_id/subscriptions/$subscription_id", array(), 'DELETE' );
		}

		/**
		 * List section subscriptions
		 *
		 * Lists the subscriptions to a given section.
		 *
		 * The {locale} is required only for end users. Admins and agents can omit it.
		 *
		 * For end-users, the response will list only the subscriptions created by
		 * the requesting end-user.
		 *
		 * @param  [type] $section_id [description]
		 * @param  string $locale     [description]
		 * @param  string $include    [description]
		 * @return [type]             [description]
		 */
		public function list_section_subscriptions( $section_id, $locale = 'en-us', $include = array(), $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			$pages = array_merge( $pages, $include );
			return $this->run( "help_center/$locale/sections/$section_id/subscriptions", $pages );
		}

		/**
		 * Show section subscription.
		 *
		 * The {locale} is required only for end users. Admins and agents can omit it.
		 *
		 * @param  [type] $section_id      [description]
		 * @param  [type] $subscription_id [description]
		 * @param  string $locale          [description]
		 * @param  string $include         [description]
		 * @return [type]                  [description]
		 */
		public function show_section_subscription( $section_id, $subscription_id, $locale = 'en-us', $include = array() ) {
			return $this->run( "help_center/$locale/sections/$section_id/subscriptions/$subscription_id", $include );
		}

		/**
		 * Create section subscription.
		 *
		 * Creates a subscription to a given section.
		 *
		 * Agents with the Help Center manager role can optionally supply a user_id value.
		 * If provided, the user associated with user_id will be subscribed to the section.
		 *
		 * @link https://developer.zendesk.com/rest_api/docs/help_center/subscriptions#create-section-subscription
		 * @param  [type] $section_id       [description]
		 * @param  string $include_comments [description]
		 * @param  string $user_id          [description]
		 * @return [type]                   [description]
		 */
		public function create_section_subscription( $section_id, $locale = 'en-us', $include_comments = false, $user_id = '' ) {
			$args = array(
				'subscription' => array(
					'source_locale' => $locale,
					'include_comments' => $include_comments,
				)
			);

			if( '' !== $user_id ){
				$args['subscription']['user_id'] = $user_id;
			}

			return $this->run( "help_center/sections/$section_id/subscriptions", $args, 'POST' );
		}

		public function delete_section_subscription( $section_id, $subscription_id ) {
			return $this->run( "help_center/sections/$section_id/subscriptions/$subscription_id", array(), 'DELETE' );
		}

		public function list_subscriptions_by_user( $user_id, $include = array(), $page = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			$pages = array_merge( $pages, $include );
			return $this->run( "help_center/users/$user_id/subscriptions", $include );
		}

		public function list_subscriptions_by_post( $post_id, $include = array(), $page = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			$pages = array_merge( $pages, $include );
			return $this->run( "community/posts/$post_id/subscriptions", $include );
		}

		public function show_post_subscription( $post_id, $subscription_id, $include = array() ) {
			return $this->run( "community/posts/$post_id/subscriptions/$subscription_id", $include );
		}

		public function create_post_subscription( $post_id, $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['subscription'] = array( 'user_id' => $user_id );
			}

			return $this->run( "community/posts/$post_id/subscriptions", $args, 'POST' );
		}

		public function delete_post_subscription( $post_id, $subscription_id ) {
			return $this->run( "community/posts/$post_id/subscriptions/$subscription_id", array(), 'DELETE' );
		}

		public function list_topic_subscriptions( $topic_id, $include = array(), $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			$pages = array_merge( $pages, $include );
			return $this->run( "community/topics/$topic_id/subscriptions", $pages );
		}

		public function show_topic_subscription( $topic_id, $subscription_id, $include = array() ) {
			return $this->run( "community/topics/$topic_id/subscriptions/$subscription_id", $include );
		}

		public function create_topic_subscription( $topic_id, $include_comments = false, $user_id = '' ) {
			$args = array(
				'subscription' => array(
					'include_comments' => $include_comments
				)
			);

			if( '' !== $user_id ){
				$args['subscription']['user_id'] = $user_id;
			}

			return $this->run( "community/topics/$topic_id/subscriptions", $args, 'POST' );
		}

		public function update_topic_subscription( $topic_id, $include_comments ){
			return $this->run( "community/topics/$topic_id/subscriptions", array( 'subscription' => array( 'include_comments' => $include_comments ) ), 'PUT' );
		}

		public function delete_topic_subscription( $topic_id, $subscription_id ) {
			return $this->run( "community/topics/$topic_id/subscriptions/$subscription_id", array(), 'DELETE' );
		}

		/* VOTES. */

		/**
		 * To view own votes, use 'me' as $user_id.
		 * @param  [type] $user_id [description]
		 * @return [type]          [description]
		 */
		public function list_votes_by_user( $user_id, $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}
			return $this->run( "help_center/users/$user_id/votes", $pages );
		}

		public function list_votes_by_article( $article_id, $locale = 'en-us', $include = array(), $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			$include = array_merge( $pages, $include );
			return $this->run( "help_center/$locale/articles/$article_id/votes", $include);
		}

		public function list_votes_by_article_comments( $article_id, $comment_id, $locale = 'en-us', $include = array(), $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			$include = array_merge( $pages, $include );
			return $this->run( "help_center/$locale/articles/$article_id/comments/$comment_id/votes", $include );
		}

		public function list_votes_by_post( $post_id, $include = array(), $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			$include = array_merge( $pages, $include );
			return $this->run( "community/posts/$post_id/votes", $include );
		}

		public function get_post_comment_votes( $post_id, $comment_id, $include = array(), $pages = null ) {
			if( null === $pages ){
				$pages = $this->build_zendesk_pagination();
			}

			$include = array_merge( $pages, $include );
			return $this->run( "community/posts/$post_id/comments/$comment_id/votes", $include );
		}

		public function show_vote( $vote_id, $include = array() ) {
			return $this->run( "help_center/votes/$vote_id", $include );
		}

		public function create_article_vote_up( $article_id, $created_at = '', $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['vote'] = array( 'user_id' => $user_id );
			}

			if( '' !== $created_at ){
				if( isset( $args['vote'] ) ){
					$args['vote']['created_at'] = $created_at;
				}else{
					$args['vote'] = array( 'created_at' => $created_at );
				}
			}

			return $this->run( "help_center/articles/$article_id/up", $args, 'POST' );
		}

		public function create_article_vote_down( $article_id, $created_at = '', $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['vote'] = array( 'user_id' => $user_id );
			}

			if( '' !== $created_at ){
				if( isset( $args['vote'] ) ){
					$args['vote']['created_at'] = $created_at;
				}else{
					$args['vote'] = array( 'created_at' => $created_at );
				}
			}

			return $this->run( "help_center/articles/$article_id/down", $args, 'POST' );
		}

		public function create_article_comment_vote_up( $article_id, $comment_id, $created_at = '', $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['vote'] = array( 'user_id' => $user_id );
			}

			if( '' !== $created_at ){
				if( isset( $args['vote'] ) ){
					$args['vote']['created_at'] = $created_at;
				}else{
					$args['vote'] = array( 'created_at' => $created_at );
				}
			}

			return $this->run( "help_center/articldes/$article_id/comments/$comment_id/up", $args, 'POST' );
		}

		public function create_article_comment_vote_down( $article_id, $comment_id, $created_at = '', $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['vote'] = array( 'user_id' => $user_id );
			}

			if( '' !== $created_at ){
				if( isset( $args['vote'] ) ){
					$args['vote']['created_at'] = $created_at;
				}else{
					$args['vote'] = array( 'created_at' => $created_at );
				}
			}

			return $this->run( "help_center/articldes/$article_id/comments/$comment_id/down", $args, 'POST' );
		}

		public function create_post_vote_up( $post_id, $created_at = '', $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['vote'] = array( 'user_id' => $user_id );
			}

			if( '' !== $created_at ){
				if( isset( $args['vote'] ) ){
					$args['vote']['created_at'] = $created_at;
				}else{
					$args['vote'] = array( 'created_at' => $created_at );
				}
			}

			return $this->run( "community/posts/$post_id/up", $args, 'POST' );
		}

		public function create_post_vote_down( $post_id, $created_at = '', $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['vote'] = array( 'user_id' => $user_id );
			}

			if( '' !== $created_at ){
				if( isset( $args['vote'] ) ){
					$args['vote']['created_at'] = $created_at;
				}else{
					$args['vote'] = array( 'created_at' => $created_at );
				}
			}

			return $this->run( "community/posts/$post_id/down", $args, 'POST' );
		}

		public function create_post_comment_vote_up( $post_id, $comment_id, $created_at = '', $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['vote'] = array( 'user_id' => $user_id );
			}

			if( '' !== $created_at ){
				if( isset( $args['vote'] ) ){
					$args['vote']['created_at'] = $created_at;
				}else{
					$args['vote'] = array( 'created_at' => $created_at );
				}
			}

			return $this->run( "community/posts/$post_id/comments/$comment_id/up", $args, 'POST' );
		}

		public function create_post_comment_vote_down( $post_id, $comment_id, $created_at = '', $user_id = '' ) {
			$args = array();

			if( '' !== $user_id ){
				$args['vote'] = array( 'user_id' => $user_id );
			}

			if( '' !== $created_at ){
				if( isset( $args['vote'] ) ){
					$args['vote']['created_at'] = $created_at;
				}else{
					$args['vote'] = array( 'created_at' => $created_at );
				}
			}

			return $this->run( "community/posts/$post_id/comments/$comment_id/down", $args, 'POST' );
		}

		public function delete_vote( $vote_id ) {
			return $this->run( "help_center/votes/$vote_id", array(), 'DELETE' );
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

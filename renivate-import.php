<?php
/*
Plugin Name: Renivate Rating
Plugin URI: http://wordpress.org/
Description: The best rating plugin for WordPress. Renivate Rating shows all multiple Hotel ratings through Renivate API
Version: 1.0
Author: Sakhatech
Author URI: http://sakhatech.com
License: GPL2
Text Domain: multi-rating
Domain Path: languages
*/

class Renivate {

		/**
		 * Activation
		 */

		 static function install() {
				// do not generate any output here
		 }

		/**
		 * DeActivation
		 */

		function pluginprefix_deactivation() {

			global $wpdb; // Must have this or else!

			$postmeta_table = $wpdb->postmeta;
			$posts_table = $wpdb->posts;

			$wpdb->query("DELETE FROM " . $postmeta_table . " WHERE meta_key = 'link'");
			//$wpdb->query("DELETE FROM " . $postmeta_table . " WHERE meta_key = '_mrlpt_client_phone_num'");
			$wpdb->query("DELETE FROM " . $posts_table . " WHERE post_type = 'renivate_reviews'");


			flush_rewrite_rules();

		}


		function create_post_type() {

		}

		/**
		 * Table to be created while installing
		 */

	function rev_install() {
	
		ob_start();

		global $db_version;
		$db_version = '1.0';

		global $wpdb;


		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		//dbDelta( $sql );

		add_option( 'db_version', $db_version );

		//add_action( 'init', 'create_post_type' );
	}

	/**
	 *
	 *	Connection String for Renivate API
	 *  Inserting Json Values from API
	 */
	function rev_install_data() {

 		//$url = "https://porter.revinate.com/hotels/10463/reviews";
		/*$url = "https://porter.revinate.com/hotels/10470";
		$USERNAME="martin.rusteberg@snhgroup.com";
		$TOKEN="ef74b36fe595cf9fdef0bce348616c3d";
		$SECRET="f94c5129c8efd82a11c7a20c1471f77c4a08e922d9683b27456462e58878de19";   */
		$url = get_option('reniv_settings_url');
		 
		$USERNAME= get_option('reniv_settings_username');
		$TOKEN= get_option('reniv_settings_token');
		$SECRET= get_option('reniv_settings_secret');    
		$kSecret = crypt($SECRET,$const.substr(sha1(mt_rand()), 0, 22));
		$TIMESTAMP = time();

		$ENCODED = hash_hmac('sha256', $USERNAME.$TIMESTAMP,$SECRET);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'X-Revinate-Porter-Key:' .$TOKEN,
			'X-Revinate-Porter-Username:' .$USERNAME,
			'X-Revinate-Porter-Timestamp:' .$TIMESTAMP,
			'X-Revinate-Porter-Encoded:' . $ENCODED,

		));

		curl_setopt($ch, CURLOPT_URL, $url);


		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$http_result = curl_exec($ch);
		$error = curl_error($ch);

		$http_code = curl_getinfo($ch );

		curl_close($ch);
		$arr =  json_decode($http_result,true);
		$content = $arr['content'];
		
		//print_r($content);
		//exit();

		global $wpdb;

		// $table_name = $wpdb->prefix . 'renivate_reviews';
		foreach($content as $val){

			$title = $val['title'];

			if(!isset($title)){
				$title = "";
			}
			$querystr = "SELECT * FROM $wpdb->postmeta WHERE $wpdb->postmeta.meta_key = 'link' AND $wpdb->postmeta.meta_value = '".$val['links'][0]['href']."'";
			$pageposts = $wpdb->get_results($querystr, OBJECT);
			/* print_r($pageposts);
			exit(); */
			if(count($pageposts) > 0){
				continue;
			}

			$post_id = wp_insert_post(array (
				'post_type' => 'renivate_reviews',
				'post_title' => $val['title'],
				'post_content' => $val['language']['englishName'],
				'post_status' => 'publish',
				'comment_status' => 'closed',   // if you prefer
				'ping_status' => 'closed',      // if you prefer
			));

			if ($post_id) {
				// insert post meta
				add_post_meta($post_id, 'title', $val['title']);
				add_post_meta($post_id, 'link', $val['links'][0]['href']);
				add_post_meta($post_id, 'author', $val['author']);
				add_post_meta($post_id, 'rating', $val['rating']);
				add_post_meta($post_id, 'language', $val['language']['englishName']);
				add_post_meta($post_id, 'subratings', $val['subratings']['Service']);
				add_post_meta($post_id, 'roomsubratings', $val['subratings']['Rooms']);
				add_post_meta($post_id, 'valuesubratings', $val['subratings']['Value']);
				add_post_meta($post_id, 'hotelsubratings', $val['subratings']['Hotel condition']);
				add_post_meta($post_id, 'locationsubratings', $val['subratings']['Location']);
				add_post_meta($post_id, 'cleansubratings', $val['subratings']['Cleanliness']);
				add_post_meta($post_id, 'triptype', $val['tripType']);
				add_post_meta($post_id, 'pagesize', $val['page']['size']);
				add_post_meta($post_id, 'pagetotalpage', $val['page']['totalPages']);
				add_post_meta($post_id, 'numbers', $val['page']['number']);
			}


		}

	}



}
	/**
	 * Includes files
	 */
	function includes() {

        wp_enqueue_script('star-script', site_url().'/wp-content/plugins/revinateimport/js/jquery.min.js');
        wp_enqueue_script('star-js', site_url().'/wp-content/plugins/revinateimport/js/star.js');
        wp_enqueue_style('star-css', site_url().'/wp-content/plugins/revinateimport/css/star.css');

		//include  dirname( __FILE__ )  . '/admin/snh_rating.php';

	}
	add_action('wp_head', 'includes');

	// setup the location custom post type
	add_action( 'init', 'srd_reviews_register_post_type' );

	// register the location post type

	function srd_reviews_register_post_type() {

	// setup the arguments for the location post type
	$reviews_args = array(
		'public' => true,
		'query_var' => 'reviews',
		'rewrite' => array(
			'slug' => 'renivate_reviews',
			'with_front' => false
		),
		'has_archive'        => true,
		'show_in_menu'       => true,
		'supports' => array(
			'title',
			'editor',
			'thumbnail'
		),
		'labels' => array(
			'name' => 'Reviews',
            'singular_name' => 'Reviews',
			'add_new' => 'Add New Reviews',
			'add_new_item' => 'Add New Reviews',
			'edit_item' => 'Edit Reviews',
			'new_item' => 'New Reviews',
			'view_item' => 'View Reviews',
			'search_items' => 'Search Reviews',
			'not_found' => 'No Reviews Found',
			'not_found_in_trash' => 'No Reviews Found in Trash'
		),

		);
		//register the post type
		register_post_type( 'renivate_reviews', $reviews_args );

		add_action( 'add_meta_boxes', 'add_reviews_metaboxes' );
	}

	// Add the Renivate Reviews Meta Boxes

		function add_reviews_metaboxes() {
			add_meta_box('wpt_reviews_location', 'Renivate Reviews', 'wpt_reviews_location', 'renivate_reviews', 'normal', 'default');
		}

	// The Renivate Reviews Metabox

	function wpt_reviews_location() {
			global $post;

		// Noncename needed to verify where the data originated
			echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
			wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		// Get the data if its already been entered
			$title = get_post_meta($post->ID, 'title', true);
			$link = get_post_meta($post->ID, 'link', true);
			$author = get_post_meta($post->ID, 'author', true);
			$language = get_post_meta($post->ID, 'language', true);
			$rating = get_post_meta($post->ID, 'rating', true);
			$subratings = get_post_meta($post->ID, 'subratings', true);
			$roomsubratings = get_post_meta($post->ID, 'roomsubratings', true);
			$valuesubratings = get_post_meta($post->ID, 'valuesubratings', true);
			$cleansubratings = get_post_meta($post->ID, 'cleansubratings', true);
			$hotelsubratings = get_post_meta($post->ID, 'hotelsubratings', true);
			$locationsubratings = get_post_meta($post->ID, 'locationsubratings', true);
			$triptype = get_post_meta($post->ID, 'triptype', true);


		// Echo out the field
			echo '<p>Title:</p>';
			echo '<input type="text" name="title" value="' . $title  . '" class="widefat" />';
			echo '<p>Link</p>';
			echo '<input type="text" name="link" value="' . $link  . '" class="widefat" />';
			echo '<p>Author</p>';
			echo '<input type="text" name="author" value="' . $author  . '" class="widefat" />';
			echo '<p>Language</p>';
			echo '<input type="text" name="language" value="' . $language  . '" class="widefat" />';
			echo '<p>Rating</p>';
			echo '<input type="text" name="rating" value="' . $rating  . '" class="widefat" />';
			echo '<p>Subratings</p>';
			echo '<p>Service</p>';
			echo '<input type="text" name="subratings" value="' . $subratings  . '" class="widefat" />';
			echo '<p>Rooms</p>';
			echo '<input type="text" name="roomsubratings" value="' . $roomsubratings  . '" class="widefat" />';
			echo '<p>Cleanliness</p>';
			echo '<input type="text" name="cleansubratings" value="' . $cleansubratings  . '" class="widefat" />';
			echo '<p>location</p>';
			echo '<input type="text" name="locationsubratings" value="' . $locationsubratings  . '" class="widefat" />';
			echo '<p>Hotel Condition</p>';
			echo '<input type="text" name="hotelsubratings" value="' . $hotelsubratings  . '" class="widefat" />';
			echo '<p>Triptype</p>';
			echo '<input type="text" name="triptype" value="' . $triptype  . '" class="widefat" />';

	}

	function myplugin_register_settings() {
	   add_option( 'myplugin_option_name', 'This is my option value.');
	   register_setting( 'myplugin_options_group', 'myplugin_option_name', 'myplugin_callback' );
	}
	add_action( 'admin_init', 'myplugin_register_settings' );

	add_action( 'admin_menu', 'register_my_custom_menu_page1' );

	function register_my_custom_menu_page1(){
		include  dirname( __FILE__ )  . '/admin/settings.php';
		add_menu_page( 'settings', 'Renivate', 'manage_options', 'settingspage', 'my_custom_menu_page', plugins_url( 'revinateimport/images/revimg.png' ), 6 );
		add_action( 'admin_init', 'update_extra_post_info' );
	}

	if( !function_exists("update_extra_post_info") ) {
		function update_extra_post_info() {
		  register_setting( 'myplugin_options_group', 'reniv_settings_url' );
		  register_setting( 'myplugin_options_group', 'reniv_settings_username' );
		  register_setting( 'myplugin_options_group', 'reniv_settings_token' );
		  register_setting( 'myplugin_options_group', 'reniv_settings_secret' );
		}
	}

	function view_shortcode(){
		global $wpdb;
	   
		$querystr = "SELECT $wpdb->posts.ID,$wpdb->posts.post_title FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->postmeta.meta_key = 'rating' AND $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'renivate_reviews' ORDER BY $wpdb->postmeta.meta_value DESC";
	   //$querystr = "SELECT '%s'.ID from '%s' join '%s' on '%s'.ID = '%s'.post_id where %smeta_key='rating' order by '%s'.meta_value desc";
	   $pageposts = $wpdb->get_results($querystr);
	   //print_r($pageposts);
		 foreach($pageposts as $val){
			 if($val->post_title != ''){
				 $author = get_post_meta($val->ID, 'author', false);
				 $rating = get_post_meta($val->ID, 'rating', false);
				 echo "<div class='ratingbox'>";
				 echo "<div class='title'>".(string)$val->post_title."</div>";
				 echo "<div class='author'>".$author[0]."</div>";
				 echo "<span class='stars'>".$rating[0]."</span>";
	  		 echo "</div>";
			 }
		 }
	}

	function register_shortcodes(){
	   add_shortcode('starrating', 'view_shortcode');

	}
	add_action( 'init', 'register_shortcodes');

	/***
		Cron For API

	*/
	add_filter('cron_schedules', 'add_scheduled_interval');

	// add once 5 minute interval to wp schedules
	function add_scheduled_interval($schedules) {
		$schedules['minutes_5'] = array('interval'=>300, 'display'=>'Once 5 minutes');
		return $schedules;
	}

	if (!wp_next_scheduled('cron_revinate_pull')) {
			wp_schedule_event(time(), 'minutes_5', 'cron_revinate_pull');
	}

	add_action('cron_revinate_pull', 'rev_install_data');
	/**
	 * Hooks for activation of plugin
	 */

	register_activation_hook( __FILE__, array( 'Renivate', 'install' ) );
	register_deactivation_hook( __FILE__, array('Renivate','pluginprefix_deactivation') );
	register_activation_hook( __FILE__, array( 'Renivate','rev_install') );
	register_activation_hook( __FILE__, array( 'Renivate','rev_install_data') );

	/**
		Single Page Template
	*/

	function cd_display($single_templat)
	{
		global $wpdb;
		// We only want this on single posts, bail if we're not in a single post
		if( is_single() ){
			// We're in the loop, so we can grab the $post variable
			global $post;
			if($post->post_type == 'renivate_reviews'){
				$single_templat = dirname( __FILE__ ).'/templates/single_reviews.php';
			}

			return $single_templat;
		} 
		
	}
	
	add_filter( 'single_template', 'cd_display' );
	
	/**
		Archieve Page Template
	*/
	function get_custom_post_type_template($archive_template)
	{
		global $wpdb;
		if (is_post_type_archive('renivate_reviews')) {
			$archive_template = dirname(__FILE__) . '/templates/archive-reviews.php';
		}
		return $archive_template;
	}
	add_filter('archive_template', 'get_custom_post_type_template');

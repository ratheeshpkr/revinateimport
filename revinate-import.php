<?php
/*
Plugin Name: Revinate Rating
Plugin URI: http://wordpress.org/
Description: The best rating plugin for WordPress. Revinate Rating shows all multiple Hotel ratings through Revinate API
Version: 1.0
Author: Sakhatech
Author URI: https://github.com/ratheeshpkr/revinateimport
License: GPL2
Text Domain: multi-rating
Domain Path: languages
*/

class Revinate {

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
			$option_table = $wpdb->options;
			
			$log_table = $wpdb->prefix . 'revinateLog';
			//$wpdb->query("DELETE FROM " . $postmeta_table . " WHERE meta_key = 'link'");
			$wpdb->query("DELETE FROM " . $postmeta_table . " WHERE meta_key IN('title','link','author','rating',
				     'language','subratings','roomsubratings','valuesubratings','hotelsubratings',
				     'locationsubratings','cleansubratings','triptype','pagesize','pagetotalele','pagetotalpage','numbers','authorlocation')");
			$wpdb->query("DELETE FROM " . $posts_table . " WHERE post_type = 'revinate_reviews'");
			$wpdb->query("DELETE FROM " . $option_table . " WHERE option_name IN('revin_settings_url','revin_settings_username','revin_settings_token','revin_settings_secret')");
			$wpdb->query("TRUNCATE TABLE ".$log_table);########log need to be inserted for cron file
			$wpdb->query("DROP TABLE ".$log_table);########log need to be inserted for cron file
			flush_rewrite_rules();

		}


		
	/**
	 * Table to be created while installing
	 */

	function rev_install() {
	
		ob_start();

		global $db_version;
		$db_version = '1.0';

		global $wpdb;
		$table_name = $wpdb->prefix . 'revinateLog';
		$sql = "CREATE TABLE $table_name (id int(11) NOT NULL AUTO_INCREMENT,
		pageNo int(11) NOT NULL,
		TotalPage int(11) NOT NULL,
		Success int(11) NOT NULL,
		UNIQUE KEY id (id))";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );

		add_option( 'db_version', $db_version );

	}

	/**
	 *
	 *	Connection String for Revinate API
	 *  Inserting Json Values from API
	 */
	
}


#############My code
	function rev_install_data(){
		
	}
	#############My code




	
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
			'slug' => 'revinate_reviews',
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
			'name' => 'Revinate',
            'singular_name' => 'Reviews',
			'add_new' => 'Add Reviews',
			'add_new_item' => 'Add Reviews',
			'edit_item' => 'Edit Reviews',
			'new_item' => 'New Reviews',
			'view_item' => 'View Reviews',
			'search_items' => 'Search Reviews',
			'not_found' => 'No Reviews Found',
			'not_found_in_trash' => 'No Reviews Found in Trash'
		),

		);
		//register the post type
		register_post_type( 'revinate_reviews', $reviews_args );

		add_action( 'add_meta_boxes', 'add_reviews_metaboxes' );
	}
	
	

	// Add the Revinate Reviews Meta Boxes

		function add_reviews_metaboxes() {
			add_meta_box('wpt_reviews_location', 'Revinate Reviews', 'wpt_reviews_location', 'revinate_reviews', 'normal', 'default');
		}
	
	// The Revinate Reviews Metabox

	function wpt_reviews_location() {
			global $post;

		// Noncename needed to verify where the data originated
			echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
			wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		// Get the data if its already been entered
	
			/*if(!empty(get_post_meta($post->ID, 'title', true)))
				$title = get_post_meta($post->ID, 'title', true);
			else
				$title = 'No Title';
				*/
			
			$link = get_post_meta($post->ID, 'link', true);
			$author = get_post_meta($post->ID, 'author', true);
			$authorloc = get_post_meta($post->ID, 'authorlocation', true);
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
			//echo '<p>Title:</p>';
			//echo '<input type="text" name="title" value="' . $title  . '" class="widefat" />';
			echo '<p>Link</p>';
			echo '<input type="text" name="link" value="' . $link  . '" class="widefat" />';
			echo '<p>Author</p>';
			echo '<input type="text" name="author" value="' . $author  . '" class="widefat" />';
			echo '<p>Author Location</p>';
			echo '<input type="text" name="author" value="' . $authorloc  . '" class="widefat" />';
			echo '<p>Language</p>';
			echo '<input type="text" name="language" value="' . $language  . '" class="widefat" />';
			echo '<p>Rating</p>';
			echo '<input type="text" name="rating" value="' . $rating  . '" class="widefat" />';
			echo '<p><b>Subratings</b></p><hr>';
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
		//add_menu_page( 'settings', 'Revinate', 'manage_options', 'settingspage', 'my_custom_menu_page', plugins_url( 'revinateimport/images/revimg.png' ), 6 );
		//add_submenu_page( 'renivate', __( 'Reviews', 'renivate' ), __( 'Reviews', 'renivate' ), manage_options, 'edit.php?post_type=renivate_reviews' );
		add_submenu_page('edit.php?post_type=revinate_reviews', 'Settings', 'Settings', 'edit_posts', basename(__FILE__), 'my_custom_menu_page');
		add_action( 'admin_init', 'update_extra_post_info' );
	}

	
	if( !function_exists("update_extra_post_info") ) {
		function update_extra_post_info() {
		  register_setting( 'myplugin_options_group', 'revin_settings_url' );
		  register_setting( 'myplugin_options_group', 'revin_settings_username' );
		  register_setting( 'myplugin_options_group', 'revin_settings_token' );
		  register_setting( 'myplugin_options_group', 'revin_settings_secret' );
		}
	}

	function view_shortcode(){
		global $wpdb;
	   
		$querystr = "SELECT $wpdb->posts.ID,$wpdb->posts.post_title FROM $wpdb->posts LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) WHERE $wpdb->postmeta.meta_key = 'rating' AND $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'revinate_reviews' ORDER BY $wpdb->postmeta.meta_value DESC";
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

	register_activation_hook( __FILE__, array( 'Revinate', 'install' ) );
	register_deactivation_hook( __FILE__, array('Revinate','pluginprefix_deactivation') );
	register_activation_hook( __FILE__, array( 'Revinate','rev_install') );
	register_activation_hook( __FILE__, array( 'Revinate','rev_install_data') );

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
			if($post->post_type == 'revinate_reviews'){
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
		if (is_post_type_archive('revinate_reviews')) {
			$archive_template = dirname(__FILE__) . '/templates/archive-reviews.php';
		}
		return $archive_template;
	}
	add_filter('archive_template', 'get_custom_post_type_template');
	
	add_action("manage_revinate_reviews_posts_custom_column",  "revinate_custom_columns");
	add_filter("manage_revinate_reviews_posts_columns", "revinate_edit_columns");
	 
	function revinate_edit_columns($columns){
	  $columns = array(
		"cb" => "<input type='checkbox' />",
		"title" => "Reviews Title",
		"rating" => "Rating",
		"language" => "Language",
		"triptype" => "Trip Type",
		"date" => "Date",	
	  );
	 
	  return $columns;
	}
	function revinate_custom_columns($column){
	  global $post;
	 
	  switch ($column) {
		case "rating":
		  $custom = get_post_custom( $post_id, 'rating', true );
		  //foreach ($custom as $val){
			echo  $custom['rating'][0];
			//print_r($val['rating']);
		  //}
		  
		  break;
		case "language":
		  $custom = get_post_custom();
		  echo $custom["language"][0];
		  break;
		case "triptype":
		  //echo get_the_term_list($post->ID, 'Skills', '', ', ','');
		  $custom = get_post_custom();
		  echo $custom["triptype"][0];
		  break;
		case "date":
		  $custom = get_post_custom();
		  echo $custom["date"][0];
		  break;
		case "":
		  //echo get_the_term_list($post->ID, 'Skills', '', ', ','');
		  $custom = get_post_custom();
		  echo $custom["triptype"][0];
		  break;
	  }
	}

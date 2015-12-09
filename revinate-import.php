<?php
/*
Plugin Name: Revinate Rating
Plugin URI: http://wordpress.org/
Description: The best rating plugin for WordPress. Revinate Rating shows all multiple Hotel ratings through Revinate API
Version: 1.0
Author: Sakhatech
Author URI: https://github.com/ratheeshpkr/revinateimport
License: GPL2
Text Domain: Revinate-rating
Domain Path: languages
*/

class Revinate {

	/**
	 * Activation
	 */

		 static function install() {
			// do not generate any output here
		 }

		
	/*
	 * Deactivation of Plugin
	 */
		function pluginprefix_deactivation() {

			global $wpdb;

			$postmeta_table = $wpdb->postmeta;
			$posts_table = $wpdb->posts;
			$option_table = $wpdb->options;
			
			$log_table = $wpdb->prefix . 'revinateLog';
			$wpdb->query("DELETE FROM " . $postmeta_table . " WHERE meta_key IN('title','link','author','rating',
				     'language','subratings','roomsubratings','valuesubratings','hotelsubratings',
				     'locationsubratings','cleansubratings','triptype','pagesize','pagetotalele','pagetotalpage','numbers','authorlocation')");
			$wpdb->query("DELETE FROM " . $posts_table . " WHERE post_type = 'revinate_reviews'");
			$wpdb->query("DELETE FROM " . $option_table . " WHERE option_name IN('revin_settings_url','revin_settings_username','revin_settings_token','revin_settings_secret')");
			$wpdb->query("DROP TABLE ".$log_table);########log need to be inserted for cron file
			flush_rewrite_rules();

		}


	/*
	 * Creates Table while installation
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
			whr varchar(20) NOT NULL,
			UNIQUE KEY id (id))";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta( $sql );
			add_option( 'db_version', $db_version );
	
		}

}


	/*
	 * It is used to save reviews
	 * from API to the database.
	 * Called with in a span of 10 minutes
	 */
	function rev_install_data(){
		global $wpdb;
		
		/*Used to create log i.e. which page number of API should be initiated*/
		$log_table = $wpdb->prefix . 'revinateLog';
		$myrows = $wpdb->get_results( "SELECT * FROM ".$log_table );
		
		/* Check which page number to be called for API*/
		if($wpdb->num_rows < 1){
			$pageNo = '1';
		}	
		else{
			$myrows = json_decode(json_encode($myrows), true);
			if($myrows[0]['Success'] == 1)
				$pageNo =$myrows[0]['pageNo'];
			else
				$pageNo =$myrows[0]['pageNo']+1;
		}	
		
		/*Check Condition when to call API*/
		if($myrows[0]['pageNo'] != $myrows[0]['TotalPage'] || $wpdb->num_rows < 1){
			
			$arr = getCurlData($pageNo);/*Call API*/
			$content = $arr['content'];
			$totalPage = $arr['page']['totalPages'];
			
			$wpdb->query("INSERT INTO ".$log_table." (`id`, `pageNo`, `TotalPage`, `Success`,`whr`) VALUES('1','".$pageNo."','".$totalPage."','1','init') ON DUPLICATE KEY UPDATE pageNo ='".$pageNo."', Success = 1,TotalPage = '".$totalPage."',whr = 'init'");
			insertReviews($content,$pageNo,$arr['page']['totalPages']);/*Insert Review*/
		}	
	}
	
	/*
	 * It is used to insert reviews
	 * to the database.
	 * @param (content) Contain all reviews
	 * @param (pg) which page number is
	 * initiated for API.
	 * @param (totalP) Total no. of pages in API
	 */
	function insertReviews($content,$pg,$totalP){
		global $wpdb;
		if(isset($content)){
		foreach($content as $val){

			$title = $val['title'];

			if(!isset($title)){
				$title = "";
			}
			
			$querystr = "SELECT * FROM $wpdb->postmeta WHERE $wpdb->postmeta.meta_key = 'link' AND $wpdb->postmeta.meta_value = '".$val['links'][0]['href']."'";
			$pageposts = $wpdb->get_results($querystr, OBJECT);
			
			if(count($pageposts) > 0){
				continue;
			}
			/*Insert in to Post Table*/
			$post_id = wp_insert_post(array (
				'post_type' => 'revinate_reviews',
				'post_title' => $val['title'],
				'post_content' => $val['body'],
				'post_status' => 'publish',
				'comment_status' => 'closed',  
				'ping_status' => 'closed',     
			));
			
			
			if ($post_id) {
				/*Insert in to Postmeta Table*/
				add_post_meta($post_id, 'title', $val['title']);
				add_post_meta($post_id, 'link', $val['links'][0]['href']);
				add_post_meta($post_id, 'author', $val['author']);
				add_post_meta($post_id, 'authorlocation', $val['authorLocation']);
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
				add_post_meta($post_id, 'pagetotalele', $val['page']['totalElements']);
				add_post_meta($post_id, 'pagetotalpage', $val['page']['totalPages']);
				add_post_meta($post_id, 'numbers', $val['page']['number']);
				
				/*Entry in to log table for successful entry in post meta*/
				$log_table = $wpdb->prefix . 'revinateLog';
				$wpdb->query("INSERT INTO ".$log_table." (`id`, `pageNo`, `TotalPage`, `Success`,`whr`) VALUES('1','".$pg."','".$totalP."','0','bottom') ON DUPLICATE KEY UPDATE pageNo ='".$pg."', Success = 0 ,TotalPage = '".$totalP."',whr = 'bottom'");
				}
			
			}
		
		}
	}
	
	/*
	 * It is used to Call API
	 * using curl
	 * @param (pageNo) page No.
	 * to be called in  API
	 * @return (array):Contains all the
	 * reviews for respective hotel
	 */
	function getCurlData($pageNo){
		/*Get API details from post table*/
	        $hotelId = get_option('revin_settings_url'); 
		$USERNAME= get_option('revin_settings_username');
		$TOKEN= get_option('revin_settings_token');
		$SECRET= get_option('revin_settings_secret');    
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
		$url = "https://porter.revinate.com/hotels/".$hotelId."/reviews?page=".$pageNo;
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$http_result = curl_exec($ch);
		$error = curl_error($ch);

		$http_code = curl_getinfo($ch );

		curl_close($ch);
		
		$arr =  json_decode($http_result,true);
		return $arr;
	}
			
			
	/*
	 * Includes CSS and Js files
	 */
	function includes() {
        wp_enqueue_script('star-script', site_url().'/wp-content/plugins/revinateimport-master/js/jquery.min.js');
        wp_enqueue_script('star-js', site_url().'/wp-content/plugins/revinateimport-master/js/star.js');
	wp_enqueue_style('star-css', site_url().'/wp-content/plugins/revinateimport-master/css/star.css');
	}
	add_action('wp_head', 'includes');

	/* setup the location custom post type*/
	add_action( 'init', 'srd_reviews_register_post_type' );


	/* register the location post type*/
	function srd_reviews_register_post_type() {
	/* setup the arguments for the location post type*/
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
		/*register the post type*/
		register_post_type( 'revinate_reviews', $reviews_args );

		add_action( 'add_meta_boxes', 'add_reviews_metaboxes' );
	}
	
	

	/* Add the Revinate Reviews Meta Boxes*/

		function add_reviews_metaboxes() {
			add_meta_box('wpt_reviews_location', 'Revinate Reviews', 'wpt_reviews_location', 'revinate_reviews', 'normal', 'default');
		}
	
	/*The Revinate Reviews Metabox
	 *get all the reviews from db
	 *and print them
	*/
	function wpt_reviews_location() {
			global $post;

		/*Noncename needed to verify where the data originated*/
			echo '<input type="hidden" name="eventmeta_noncename" id="eventmeta_noncename" value="' .
			wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

		/*Get the data if its already been entered*/
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


		/*Print field*/
			echo '<p>Link</p>';
			echo '<input type="text" name="link" value="' . $link  . '" class="widefat" />';
			echo '<p>Author</p>';
			echo '<input type="text" name="author" value="' . $author  . '" class="widefat" />';
			echo '<p>Author Location</p>';
			echo '<input type="text" name="authorlocation" value="' . $authorloc  . '" class="widefat" />';
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
	
	/*Update all the reviews and
	 *save it to database
	*/
	add_action( 'save_post', 'myplugin_save_postdata' );
	
	function myplugin_save_postdata( $post_id ) {
		update_post_meta( $post_id,'title',$_POST['title']);
		update_post_meta( $post_id,'link',$_POST['link']);
		update_post_meta( $post_id,'author',$_POST['author']);
		
		update_post_meta( $post_id,'authorlocation',$_POST['authorlocation']);
		update_post_meta( $post_id,'language',$_POST['language']);
		update_post_meta( $post_id,'rating',$_POST['rating']);
		update_post_meta( $post_id,'subratings',$_POST['subratings']);
		update_post_meta( $post_id,'roomsubratings',$_POST['roomsubratings']);
		update_post_meta( $post_id,'cleansubratings',$_POST['cleansubratings']);
		update_post_meta( $post_id,'locationsubratings',$_POST['locationsubratings']);
		update_post_meta( $post_id,'hotelsubratings',$_POST['hotelsubratings']);
		update_post_meta( $post_id,'triptype',$_POST['triptype']);
		update_post_meta( $post_id,'subratings',$_POST['subratings']);
		
	}

	function myplugin_register_settings() {
	   add_option( 'myplugin_option_name', 'This is my option value.');
	   register_setting( 'myplugin_options_group', 'myplugin_option_name', 'myplugin_callback' );
	}
	add_action( 'admin_init', 'myplugin_register_settings' );

	add_action( 'admin_menu', 'register_my_custom_menu_page1' );

	function register_my_custom_menu_page1(){
		include  dirname( __FILE__ )  . '/admin/settings.php';
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
		$pageposts = $wpdb->get_results($querystr);
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

	
	register_activation_hook( __FILE__, array( 'Revinate', 'install' ) );
	register_deactivation_hook( __FILE__, array('Revinate','pluginprefix_deactivation') );
	register_activation_hook( __FILE__, array( 'Revinate','rev_install') );
	
	
	
	/*
	* Set cron for calling API and saving reviews
	*/
	add_filter('cron_schedules', 'add_scheduled_interval');

	/* add once 10 minute interval to wp schedules*/
	function add_scheduled_interval($schedules) {
		$schedules['minutes_10'] = array('interval'=>600, 'display'=>'Once in a span of 10 minutes');
		return $schedules;
	}

	if (!wp_next_scheduled('cron_revinate_pull')) {
			wp_schedule_event(time(), 'minutes_10', 'cron_revinate_pull');
	}

	add_action('cron_revinate_pull', 'rev_install_data');

	
	
	function cd_display($single_templat)
	{
		global $wpdb;
		/* We only want this on single posts, bail if we're not in a single post*/
		if( is_single() ){
			/* We're in the loop, so we can grab the $post variable*/
			global $post;
			if($post->post_type == 'revinate_reviews'){
				include  dirname( __FILE__ )  . '/template-functions.php';
				$single_templat = dirname( __FILE__ ).'/templates/single_reviews.php';
			}

			return $single_templat;
		} 
		
	}
	
	add_filter( 'single_template', 'cd_display' );
	
	/**
		Archive Page Template
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
		  echo  $custom['rating'][0]; 
		  break;  
		case "language":
		  $custom = get_post_custom();
		  echo $custom["language"][0];
		  break;
		case "triptype":
		  $custom = get_post_custom();
		  echo $custom["triptype"][0];
		  break;
		case "date":
		  $custom = get_post_custom();
		  echo $custom["date"][0];
		  break;
		case "":
		  $custom = get_post_custom();
		  echo $custom["triptype"][0];
		  break;
	  }
	}

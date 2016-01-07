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
				     'locationsubratings','cleansubratings','triptype','authorlocation','datereview','datecollected','reviewsitename')");
			$wpdb->query("DELETE FROM " . $posts_table . " WHERE post_type = 'revinate_reviews'");
			$wpdb->query("DELETE FROM " . $option_table . " WHERE option_name IN('revin_settings_url','revin_settings_username','revin_settings_token','revin_settings_secret','revinate_email','revinate_email_check')");
			$wpdb->query("DROP TABLE ".$log_table);########log need to be inserted for cron file
			flush_rewrite_rules();

		}
		function cronstarter_deactivate(){
			$timeMinutesCron = wp_next_scheduled ('cron_revinate_pull');
			wp_unschedule_event ($timeMinutesCron, 'cron_revinate_pull');
			$timeDailyCron = wp_next_scheduled ('cron_pull');
			wp_unschedule_event ($timeDailyCron, 'cron_pull');
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
			page_no int(11) NOT NULL,
			total_page int(11) NOT NULL,
			success int(11) NOT NULL,
			pointer int(11) NOT NULL,
			date DATETIME NOT NULL,
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
		$date = date("y-m-d h:i:s");
		/*Used to create log i.e. which page number of API should be initiated*/
		$log_table = $wpdb->prefix . 'revinateLog';
		$myrows = $wpdb->get_results( "SELECT * FROM ".$log_table );
		$updateLog = 0;
		/* Check which page number to be called for API*/
		if($wpdb->num_rows < 1){
			$pageNo = '1';
		}
		else{
			$myrows = json_decode(json_encode($myrows), true);
			if($myrows[0]['success'] == 1){
				$pageNo =$myrows[0]['page_no'];
				$updateLog = 1;
			}
			else{
				$pageNo =$myrows[0]['page_no']+1;
			}
		}

		/*Check Condition when to call API*/
		if($myrows[0]['page_no'] != $myrows[0]['total_page'] || $wpdb->num_rows < 1){
				###############fetch mail id


				$arr = getCurlData($pageNo);/*Call API*/

				if(isset($arr['content'])){
					$content = $arr['content'];
					$totalPage = $arr['page']['totalPages'];
					if($updateLog != 1){
					$sql = $wpdb->query("INSERT INTO ".$log_table." (`id`, `page_no`, `total_page`, `success`,`pointer`,`date`) VALUES('1','".$pageNo."','".$totalPage."','1','0','".$date."') ON DUPLICATE KEY UPDATE page_no ='".$pageNo."', success = 1,total_page = '".$totalPage."',pointer = '0',date = '".$date."'");
						if(empty($sql)){

							$wpdb->show_errors();
							$wpdb->print_error();

							return;
							//Error mail
						}
					}
					insertReviews($content,$pageNo,$arr['page']['totalPages'],0);/*Insert Review*/
				}
				else{
					//Error mail,change conditions to default or make conditions to start
					echo "API Acces Denied.User credentials do not have access to large page size";
					$logUpdate = $wpdb->query("UPDATE $log_table SET `pointer`= '1',date = '".$date."'");
					return;
				}
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
	function insertReviews($content,$pg,$totalP,$pointer){
		global $wpdb;
		$date = date("y-m-d h:i:s");
		$countTotal = count($content);
		$i = 0;
		if(isset($content)){
		foreach($content as $val){
			$title = $val['title'];
			if(!isset($title)){
				$title = "";
			}
			$body = $val['body'];
			if(!isset($body)){
				$body = "";
			}

			$querystr = "SELECT * FROM $wpdb->postmeta WHERE $wpdb->postmeta.meta_key = 'link' AND $wpdb->postmeta.meta_value = '".$val['links'][0]['href']."'";
			//$querystr = $wpdb->escape( $querystr );
			$pageposts = $wpdb->get_results($querystr, OBJECT);

			if(count($pageposts) > 0){
				$i++;
				continue;
			}
			#######Check Duplicate########



			/*Insert in to Post Table*/
			$post_id = wp_insert_post(array (
				'post_type' => 'revinate_reviews',
				'post_title' => $title,
				'post_content' => $body,
				'post_status' => 'publish',
				'comment_status' => 'closed',
				'ping_status' => 'closed',
			));
			if($post_id == 0){
				$wpdb->show_errors();
				$wpdb->print_error();
				return;
				//Error mail
			}
			//echo $post_id."----".$title."____".$i."<br/>";
			if ($post_id) {
				/*Insert in to Postmeta Table*/
				if(is_null($val['dateReview']))
					 		$dateReview = '';
				else
							$dateReview = date("m/d/Y",$val['dateReview']);

				if(is_null($val['dateCollected']))
					 		$dateCollected = '';
				else
							$dateCollected = date("m/d/Y",$val['dateCollected']);

				update_post_meta($post_id, 'title', $val['title']);
				update_post_meta($post_id, 'link', $val['links'][0]['href']);
				update_post_meta($post_id, 'author', $val['author']);
				update_post_meta($post_id, 'authorlocation', $val['authorLocation']);
				update_post_meta($post_id, 'rating', $val['rating']);
				update_post_meta($post_id, 'language', $val['language']['englishName']);
				update_post_meta($post_id, 'subratings', $val['subratings']['Service']);
				update_post_meta($post_id, 'roomsubratings', $val['subratings']['Rooms']);
				update_post_meta($post_id, 'valuesubratings', $val['subratings']['Value']);
				update_post_meta($post_id, 'hotelsubratings', $val['subratings']['Hotel condition']);
				update_post_meta($post_id, 'locationsubratings', $val['subratings']['Location']);
				update_post_meta($post_id, 'cleansubratings', $val['subratings']['Cleanliness']);
				update_post_meta($post_id, 'triptype', $val['tripType']);
				update_post_meta($post_id, 'datereview', $dateReview);
				update_post_meta($post_id, 'datecollected', $dateCollected);
				update_post_meta($post_id, 'reviewsitename', $val['reviewSite']['name']);

				$i++;
				}
			}
		}
		//echo $i."-----".$countTotal;
		if($i == $countTotal){
			/*Entry in to log table for successful entry in post meta*/
			$log_table = $wpdb->prefix . 'revinateLog';
			$sqlLog = $wpdb->query("INSERT INTO ".$log_table." (`id`, `page_no`, `total_page`, `success`,`pointer`,`date`) VALUES('1','".$pg."','".$totalP."','0','".$pointer."',date = '".$date."') ON DUPLICATE KEY UPDATE page_no ='".$pg."', success = 0 ,total_page = '".$totalP."',pointer = '".$pointer."',date = '".$date."'");


		}
		else{
			//mail which details didn't inserted
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
		$const = '$5$rounds=5000$';
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
		$url = "https://porter.revinate.com/hotels/".$hotelId."/reviews?page=".$pageNo."&size=100&sort=dateReview,desc";
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
	 * Cron for pulling daily reviews
	 */
	function pull_daily_reviews(){
		global $wpdb;
		$date = date("y-m-d h:i:s");
		/*Used to create log i.e. which page number of API should be initiated*/
		$log_table = $wpdb->prefix . 'revinateLog';
		$myrows = $wpdb->get_results( "SELECT * FROM ".$log_table );
		$myrows = json_decode(json_encode($myrows), true);
		if($wpdb->num_rows > 0 && $myrows[0]['pointer'] == 1){
			$pageNo =1;
			$arr = getCurlDataa($pageNo);
			$postTable = $wpdb->prefix . 'options';
			$myrows = $wpdb->get_results( "SELECT * FROM ".$postTable ."where"  );
			$myrows = json_decode(json_encode($myrows), true);
			if(isset($arr['content'])){##############incase content is there
					$content = $arr['content'];
					$totalPage = $arr['page']['totalPages'];
					$sql = $wpdb->query("UPDATE $log_table SET page_no ='".$pageNo."', success = 1,total_page = '".$totalPage."',pointer = 1,date = '".$date."'");
					insertReviewss($content,$pageNo,$arr['page']['totalPages'],1);/*Insert Review*/
				}
				else{
					//echo "API Acces Denied.User credentials do not have access to large page size";
					return;
				}
		}
	}
	/*
	 * Includes CSS and Js files
	 */
	function includes() {
        //wp_enqueue_script('star-script', site_url().'/wp-content/plugins/revinateimport-master/js/jquery.min.js');
        //wp_enqueue_script('star-js', site_url().'/wp-content/plugins/revinateimport-master/js/star.js');
				//wp_enqueue_style('star-css', site_url().'/wp-content/plugins/revinateimport-master/css/star.css');
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
			'slug' => 'reviews',
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
      'singular_name' => 'Review',
			'add_new' => 'Add Review',
			'add_new_item' => 'Add Review',
			'edit_item' => 'Edit Review',
			'new_item' => 'New Review',
			'view_item' => 'View Review',
			'search_items' => 'Search Reviews',
			'not_found' => 'No Reviews Found',
			'not_found_in_trash' => 'No Reviews Found in Trash'
		),

		);
		flush_rewrite_rules();
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
			$datereview = get_post_meta($post->ID, 'datereview', true);
			$datecollected = get_post_meta($post->ID, 'datecollected', true);
			$reviewsitename = get_post_meta($post->ID, 'reviewsitename', true);

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
			echo '<p>Review Date</p>';
			echo '<input type="text" name="datereview" value="' . $datereview  . '" class="cmb_text_small cmb_datepicker" />';
			echo '<p>Date Collected </p>';
			echo '<input type="text" name="datecollected" value="' . $datecollected  . '" class="cmb_text_small cmb_datepicker" />';
			echo '<p>Review Site Name</p>';
			echo '<input type="text" name="reviewsitename" value="' . $reviewsitename  . '" class="widefat" />';
	}

	/*Update all the reviews and
	 *save it to database
	*/
	add_action( 'save_post', 'myplugin_save_postdata' );

	function myplugin_save_postdata( $post_id ) {
		if(isset($_POST['rating'])){
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
			update_post_meta( $post_id,'datereview',$_POST['datereview']);
			update_post_meta( $post_id,'datecollected',$_POST['datecollected']);
			update_post_meta( $post_id,'reviewsitename',$_POST['reviewsitename']);
		}
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
		  register_setting( 'myplugin_options_group', 'revinate_email' );
		  register_setting( 'myplugin_options_group', 'revinate_email_check' );

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
	register_deactivation_hook( __FILE__, array('Revinate','cronstarter_deactivate') );



	/*
	* Set cron for calling API and saving reviews
	*/
	add_filter('cron_schedules', 'add_scheduled_interval');

	/* add once 10 minute interval to wp schedules*/
	function add_scheduled_interval($schedules) {
		$schedules['minutes_10'] = array('interval'=>300, 'display'=>'Once in a span of 10 minutes');
		return $schedules;
	}

	if (!wp_next_scheduled('cron_revinate_pull')) {
			wp_schedule_event(time(), 'minutes_10', 'cron_revinate_pull');
	}

	add_action('cron_revinate_pull', 'rev_install_data');

	/*Fetch latest reviews*/
	if (!wp_next_scheduled('cron_pull')) {
			wp_schedule_event(time(), 'daily', 'cron_pull');
	}

	add_action('cron_pull', 'pull_daily_reviews');


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
			//$archive_template = dirname(__FILE__) . '/templates/archive-reviews.php';
		}
		return $archive_template;
	}
	add_filter('archive_template', 'get_custom_post_type_template');

	add_action("manage_revinate_reviews_posts_custom_column",  "revinate_custom_columns");
	add_filter("manage_revinate_reviews_posts_columns", "revinate_edit_columns");

	function revinate_edit_columns($columns){
	  $columns = array(
		"cb" => "<input type='checkbox' />",
		"title" => "Title",
		"reviewcontent" => "Content",
		"authorname" => "Author",
		"rating" => "Rating",
		"language" => "Language",
		"triptype" => "Trip Type",
		"datereview" => "Review Date",
		"reviewsitename" => "Review Site",
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
		case "reviewcontent":
			$content = get_the_content();
			if(strlen($content)>90) {
					echo substr($content, 0, 90).'...';
			}
			else {
					echo $content;
			}
		  break;
		case "triptype":
		  $custom = get_post_custom();
		  echo $custom["triptype"][0];
		  break;
		case "datereview":
		  $custom = get_post_custom();
		  echo $custom["datereview"][0];
		  break;
		case "authorname":
		  $custom = get_post_custom();
		  echo $custom["author"][0];
		  break;
		case "reviewsitename":
		  $custom = get_post_custom();
		  echo $custom["reviewsitename"][0];
		  break;
	  }
	}


function review_shortcode($atts)
{
	  $type = 'revinate_reviews';

		$a = shortcode_atts( array(
				'count' => 'attribute 2 default',
    ), $atts );

		$args=array
	  (
	    'post_type' => $type,
	    'post_status' => 'publish',
	    'posts_per_page' => $a['count'],
		//'caller_get_posts' => 4,
		'suppress_filters' => false,
      	'meta_query'       => array(
          'relation'    => 'AND',
          array(
              'key'          => 'rating',
              'value'        => '4',
              'compare'      => '>=',
          ),
          array(
              'key'          => 'rating',
              'value'        => '5',
              'compare'      => '<=',
          )
      	),
	  );
	?>
	<div id="reviews-wrapper">
		<div class="reviews" data-current-index="0">
				<?php
					$my_query = new WP_Query($args);
					if( $my_query->have_posts() )
					{
						while ($my_query->have_posts()) : $my_query->the_post();
				?>
				<div class="review">
					<div class="col-xs-12 review-header">
						<div class="col-xs-2 no-padding">
								<img src="<?php echo bloginfo('template_directory'); ?>/imgs/profile.png" alt="" class="review-user-img">
						</div>
						<div class="col-xs-10">
			    			<span class="review-user-name"><?php echo get_post_meta(get_the_ID(),'author', true).' '.get_post_meta(get_the_ID(),'authorlocation', true);?></span>
								<div class="row">
										<div class="col-xs-12 padding-right-0">
											<?php
													$triptype = "";
													if(get_post_meta(get_the_ID(),'triptype', true)!=""){
														$triptype = "Trip type: ".get_post_meta(get_the_ID(),'triptype', true);
													}//date_format(strtotime(get_post_meta(get_the_ID(),'datereview', true)), 'M j, Y')
											?>
												<span class="review-date"><?php echo date( 'M j, Y', strtotime(get_post_meta(get_the_ID(),'datereview', true)));  ?></span> &nbsp;	<span class="review-trip-type"><?php echo $triptype; ?></span>
										</div>
								</div>
						</div>
					</div>
 					<div class="col-xs-12 review-body">
					<div class="col-xs-12 quote">
						<span>
							<?php
								$content = get_the_content();
								if($content != ""){
									if(strlen($content)>90) {
											echo "\"".substr($content, 0, 90).'..."';
									}
									else {
											echo '"'.$content.'"';
									}
								}
							?>
						</span>
					</div>
					<div class="col-xs-12">
						<div class="col-xs-8 no-padding review-site-name">
							<?php echo get_post_meta(get_the_ID(),'reviewsitename', true); ?>
						</div>
						<div class="col-xs-4 no-padding">
							<div class="review-star-bg"></div>
							<div class="review-star" data-value="<?php echo get_post_meta(get_the_ID(),'rating', true);?>"></div>
						</div>
					</div>
				</div>
		</div>
		<?php
    endwhile;
	  }
	      //wp_reset_query();  // Restore global post data stomped by the_post().
	  ?>
		</div>
		<div class="col-xs-12">
			<a href="<?php echo get_post_type_archive_link( $type ); ?>" class="all-review">
				Read all Reviews
			</a>
		</div>
	</div>
<?php

}


add_shortcode( 'review', 'review_shortcode' );

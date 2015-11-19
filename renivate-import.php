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
			
			global $wpdb;
		
			$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'renivate' );
			
			flush_rewrite_rules();
		 
		}

		/**
		 * Table to be created while installing
		 */

	function rev_install() {
		
		global $db_version;
		$db_version = '1.0';

		global $wpdb;
		
		$table_name = $wpdb->prefix . 'renivate';
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			rating_id bigint(20) NOT NULL AUTO_INCREMENT,
					author varchar(50) NOT NULL,
					rating varchar(20) NOT NULL,
					language text NOT NULL,
					subrating varchar(30) NOT NULL,
					triptype varchar(30) NOT NULL,
					PRIMARY KEY  (rating_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'db_version', $db_version );
		
		
	}
	
	/**
	 * 
	 *	Connection String for Renivate API 
	 *  Inserting Json Values from API
	 */
	function rev_install_data($url="") {
	
		$url = "https://porter.revinate.com/hotels/10463";
		$url = "https://porter.revinate.com/hotels/10463/reviews";
		$url = "https://porter.revinate.com/hotels/10470/reviews";
		$USERNAME="martin.rusteberg@snhgroup.com";
		$TOKEN="ef74b36fe595cf9fdef0bce348616c3d";
		$SECRET="f94c5129c8efd82a11c7a20c1471f77c4a08e922d9683b27456462e58878de19";

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
		
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'renivate';
		foreach($content as $val){
			$wpdb->insert( 
				$table_name, 
				array( 
					'author' => $val['author'],
					'rating' => $val['rating'],			
					'language' => $val['language']['englishName'],
					'subrating' => $val['subratings']['Service'],
					'triptype' => $val['tripType']
				) 
			);
		}
		
	}
	
	
	
	/**
	 * Includes files
	 */
	function includes() {
		require_once( plugin_dir_path( __FILE__ ) . 'admin/snh_rating.php');
	
	}
	
}

	function view_shortcode(){
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'renivate';
		
		$view = $wpdb->get_results("select author,rating,language,subrating,triptype from ".$table_name."");
		
		foreach($view as $result){
		
			echo $result->author; 
		}
		
		print_r($view);
		return '';
	
	}
	add_shortcode('starrating', 'view_shortcode');
	/**
	 * Function for Tabs
	 */

	function rev_admin_tabs( $current = 'homepage' ) {
		if($_REQUEST['page']=="renivate-plugin"){
			$revtabs = array( 'homepage' => 'Home Settings', 'general' => 'General' );
			echo '<div class="wrap"><div id="icon-themes" class="icon32"><br></div>';
			echo '<h2 class="nav-tab-wrapper">';
			foreach( $revtabs as $tab => $name ){
				$class = ( $tab == $current ) ? ' nav-tab-active' : '';
				echo "<a class='nav-tab$class' href='?page=theme-settings&tab=$tab'>$name</a>";

			}
			echo '</h2></div>';
		}
	}

	//add_action('plugins_loaded','rev_admin_tabs');

	/**
	 * Function for Admin menu
	 */
	add_action('admin_menu', 'rev_plugin_setup_menu');
	 
	function rev_plugin_setup_menu(){
			add_menu_page( 'Renivate Plugin Page', 'Renivate Rating', 'manage_options', 'renivate-plugin', 'rev_init' );
	}
 
	function rev_init(){
			echo "<h1>Renivate Rating Plugin Settings</h1>";
			echo "<h3>The best rating plugin for WordPress. Renivate Rating shows rating from multiple rating sites.</h3>";
			rev_admin_tabs();
	}
	/**
	 * Hooks for activation of plugin
	 */

	register_activation_hook( __FILE__, array( 'Renivate', 'install' ) );
	register_deactivation_hook( __FILE__, 'pluginprefix_deactivation' );
	register_activation_hook( __FILE__, array( 'Renivate','rev_install') );
	register_activation_hook( __FILE__, array( 'Renivate','rev_install_data') );
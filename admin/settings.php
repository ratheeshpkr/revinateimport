<?php
	function my_custom_fonts() {
	  wp_enqueue_style('star-css', site_url().'/wp-content/plugins/revinateimport/css/star.css');
	}
	add_action('admin_head', 'my_custom_fonts');
	function deleteifExist() {
		if(isset($_REQUEST['settings-updated'])){
				global $wpdb; // Must have this or else!
				 $postmeta_table = $wpdb->postmeta;
				$wpdb->query("SELECT * FROM ".$postmeta_table." WHERE meta_key = 'title'");
				if($wpdb->num_rows > 0){
					$wpdb->query("DELETE FROM " . $postmeta_table . " WHERE meta_key IN('title','link','author','rating',
						     'language','subratings','roomsubratings','valuesubratings','hotelsubratings',
						     'locationsubratings','cleansubratings','triptype','pagesize','pagetotalele','pagetotalpage','numbers')");
				}
				getCurl();
		}	

		}
	function getCurl(){
		$arr = getCurlData('1');####First Time curl called#####
		$content = $arr['content'];
		insertReviews($content,1);
		//$pgNoLimit = $arr['page']['totalPages'];
		$pgNoLimit = '5';
			for($i = 2;$i<=$pgNoLimit;$i++){
				try{	
					$arr = getCurlData($i);
					if(isset($arr['error'])){
						break;
					}
					$content = $arr['content'];
					insertReviews($content,$i);
				}
					catch(Exception $e){
					echo $e->getMessage();
				}
			}
		
			
	}
	function insertReviews($content,$pg){
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

			$post_id = wp_insert_post(array (
				'post_type' => 'revinate_reviews',
				'post_title' => $val['title'],
				'post_content' => $val['language']['englishName'],
				'post_status' => 'publish',
				'comment_status' => 'closed',   // if you prefer
				'ping_status' => 'closed',      // if you prefer
			));
			
			
			if ($post_id) {
				// insert post meta
				add_post_meta($post_id, 'title', $val['title'].$pg);
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
				add_post_meta($post_id, 'pagetotalele', $val['page']['totalElements']);
				add_post_meta($post_id, 'pagetotalpage', $val['page']['totalPages']);
				add_post_meta($post_id, 'numbers', $val['page']['number']);
			}
			
		}
		}
	}	
	function getCurlData($pageNo){
		
		
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
	function my_custom_menu_page()
	{
		global $wpdb;
	?>
	  <div class="wrap">
	  <?php screen_icon(); ?>
	  <h1>Revinate Settings</h1>
	  <form method="post" action="options.php">
	  <?php settings_fields( 'myplugin_options_group' ); 
		do_settings_sections( 'myplugin_options_group' );
	  ?>
	  <!--<h3>Please provide below details</h3>-->
	  
	  <table class="form-table">
	  <tr valign="top">
	  <th scope="row">Revinate Hotel ID</th>
	  <td>
	  <input type="text" id="revin_settings_url" name="revin_settings_url" value="<?php echo get_option('revin_settings_url'); ?>" class="regular-text " /></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">Revinate Username</th>
	  <td><input type="text" id="revin_settings_username" name="revin_settings_username" value="<?php echo get_option('revin_settings_username'); ?>" class="regular-text "/></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">Revinate API Key(Porter Key)</th>
	  <td><input type="text" id="revin_settings_token" name="revin_settings_token" value="<?php echo get_option('revin_settings_token'); ?>" class="regular-text "/></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">Revinate API Secret</th>
	  <td><input type="text" id="revin_settings_secret" name="revin_settings_secret" value="<?php echo get_option('revin_settings_secret'); ?>" class="regular-text "/></td>
	  </tr>
	  </table>
	  <?php
	  submit_button();
	  		deleteifExist();
	  
	  ?>
	  </form>
	  </div>
	<?php
	
	}
 
?>
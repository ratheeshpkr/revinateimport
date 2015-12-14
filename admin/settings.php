<?php
	function my_custom_fonts() {
	  wp_enqueue_style('star-css', site_url().'/wp-content/plugins/revinateimport-import/css/star.css');
	}
	add_action('admin_head', 'my_custom_fonts');
	
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
	// rev_install_dataa();
	  
	  ?>
	  </form>
	  </div>
	<?php
	
	}
 
?>
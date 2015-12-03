<?php
	
	function my_custom_menu_page()
	{
		global $wpdb;
	?>
	  <div>
	  <?php screen_icon(); ?>
	  <h2>Revinate Plugin Settings Page</h2>
	  <form method="post" action="options.php">
	  <?php settings_fields( 'myplugin_options_group' ); 
		do_settings_sections( 'myplugin_options_group' );
	  ?>
	  <h3>Please provide below details</h3>
	  
	  <table>
	  <tr valign="top">
	  <th scope="row">Revinate Hotel ID</th>
	  <td><input type="text" id="revin_settings_url" name="revin_settings_url" value="<?php echo get_option('revin_settings_url'); ?>" /></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">Revinate Username</th>
	  <td><input type="text" id="revin_settings_username" name="revin_settings_username" value="<?php echo get_option('revin_settings_username'); ?>" /></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">Revinate API Key(Porter Key)</th>
	  <td><input type="text" id="revin_settings_token" name="revin_settings_token" value="<?php echo get_option('revin_settings_token'); ?>" /></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">Revinate API Secret</th>
	  <td><input type="text" id="revin_settings_secret" name="revin_settings_secret" value="<?php echo get_option('revin_settings_secret'); ?>" /></td>
	  </tr>
	  </table>
	  <?php  submit_button(); ?>
	  </form>
	  </div>
	<?php
	}
 
?>
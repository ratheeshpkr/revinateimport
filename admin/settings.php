<?php
	
	function my_custom_menu_page()
	{
		global $wpdb;
	?>
	  <div>
	  <?php screen_icon(); ?>
	  <h2>Reviews Plugin Settings Page</h2>
	  <form method="post" action="options.php">
	  <?php settings_fields( 'myplugin_options_group' ); 
		do_settings_sections( 'myplugin_options_group' );
	  ?>
	  <h3>Please provide below details</h3>
	  
	  <table>
	  <tr valign="top">
	  <th scope="row">HOTEL API URL</th>
	  <td><input type="text" id="reniv_settings_url" name="reniv_settings_url" value="<?php echo get_option('reniv_settings_url'); ?>" /></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">USERNAME</th>
	  <td><input type="text" id="reniv_settings_username" name="reniv_settings_username" value="<?php echo get_option('reniv_settings_username'); ?>" /></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">TOKEN</th>
	  <td><input type="text" id="reniv_settings_token" name="reniv_settings_token" value="<?php echo get_option('reniv_settings_token'); ?>" /></td>
	  </tr>
	  <tr valign="top">
	  <th scope="row">SECRET</th>
	  <td><input type="text" id="reniv_settings_secret" name="reniv_settings_secret" value="<?php echo get_option('reniv_settings_secret'); ?>" /></td>
	  </tr>
	  </table>
	  <?php  submit_button(); ?>
	  </form>
	  </div>
	<?php
	}
 
?>
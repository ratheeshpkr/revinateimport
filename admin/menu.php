<?php

class Renivate_Settings {

    function __construct() {
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_menu() {
        $menu_position = apply_filters( 'renivate_menu_position', 16 );
        $capability = apply_filters( 'renivate_menu_capability', 'activate_plugins' );

        add_menu_page( __( 'renivate', 'wpuf' ), __( 'renivate', 'wpuf' ), $capability, 'renivate', array($this, 'plugin_settings'), null, $menu_position );

        add_submenu_page( 'renivate', __( 'Offers', 'renivate' ), __( 'Offers', 'renivate' ), $capability, 'edit.php?post_type=renivate_offer' );

        add_submenu_page( 'renivate', __( 'Accommodations', 'renivate' ), __( 'Accommodations', 'renivate' ), $capability, 'edit.php?post_type=renivate_room' );

		    add_submenu_page( 'renivate', __( 'Events', 'renivate' ), __( 'Events', 'renivate' ), $capability, 'edit.php?post_type=renivate_event' );

		    add_submenu_page( 'renivate', __( 'Facilities', 'renivate' ), __( 'Facilities', 'renivate' ), $capability, 'edit.php?post_type=renivate_facility' );
    }

    function plugin_settings() {
        echo '<div class="wrap">';
            echo '<h2>Renivate</h2';
        echo '</div>';
    }
}

new Renivate_Settings();

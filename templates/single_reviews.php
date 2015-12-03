<?php

/**
 * The Template for displaying all single event.
 *
 * Override this template by copying it to yourtheme/snhotel/single-event.php
 *
 * @author      Sakha
 * @package     Snhotel/Templates
 * @version     0.1
 */

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	get_header( 'snhotel' );
	
	do_action( 'snhotel_before_main_content' ); 
		global $post;
		global $wpdb;
	?>

	<div class="container">		
		<?php
		
		echo '<strong>Title:</strong>'.$meta_avail = get_post_meta(get_the_ID(), 'title', true);  
		echo '</br>';
		echo '<strong>Author:</strong>'.$meta_leadTime = get_post_meta(get_the_ID(),'author', true); 
		echo '</br>';
		echo '<strong>Link:</strong>'.$meta_offerPrice = get_post_meta(get_the_ID(),'link', true); 
		echo '</br>';
		echo '<strong>Language:</strong>'.$meta_offerPrice = get_post_meta(get_the_ID(),'language', true); 
		echo '</br>';
		echo '<strong>Rating:</strong>';
		$meta_offerPrice = get_post_meta(get_the_ID(),'rating', true); 
		echo '<span class="stars">'.$meta_offerPrice.'</span></br>';
		echo '<strong>Subratings:</strong>'.$meta_offerPrice = get_post_meta(get_the_ID(),'subratings', true); 
		echo '</br>';
		echo '<strong>Rooms:</strong>'.$meta_offerPrice = get_post_meta(get_the_ID(),'roomsubratings', true); 
		echo '</br>';
		echo '<strong>Cleanliness:</strong>'.$meta_offerPrice = get_post_meta(get_the_ID(),'cleansubratings', true); 
		echo '</br>';
		echo '<strong>Hotel Condition:</strong>'.$meta_offerPrice = get_post_meta(get_the_ID(),'hotelsubratings', true); 
		echo '</br>';
		echo '<strong>Triptype:</strong>'.$meta_offerPrice = get_post_meta(get_the_ID(),'triptype', true); 
		echo '</br>';
		$postmeta = $wpdb->prefix.'postmeta';
		$avg = $wpdb->get_results("select AVG(meta_value) as Average from $postmeta where meta_key='rating'");
		echo 'Average: '.array_shift($avg)->Average;
		
		?>
	</div>
	 <?php 
	 dynamic_sidebar( 'room-sidebar' ); ?>

       <!-- .snhotel-col-3 .sidebar -->
    <?php do_action( 'snhotel_after_main_content' ); ?>

    <?php do_action( 'snhotel_sidebar' ); ?>

<?php get_footer( 'snhotel' ); ?>
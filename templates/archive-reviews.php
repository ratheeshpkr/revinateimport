<?php
/**
 * The Template for displaying all archive accommodation.
 *
 * Override this template by copying it to yourtheme/renivate/archive-reviews.php
 *
 * @author      Sakha
 * @package     Renivate/Templates
 * @version     0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

get_header( 'snhotel' ); ?>

    

        <?php 	while ( have_posts() ) : the_post(); 
				global $post;
		?>
				
            <?php //snhotel_get_template_part( 'content', 'archive-accommodation' ); ?>
		<div class="container">		
			<?php
			
			echo '<strong>Title:</strong>'.$meta_avail = get_post_meta($post->ID, 'title', true); 
			echo '</br>';
			echo '<strong>Author:</strong>'.$meta_leadTime = get_post_meta($post->ID,'author', true); 
			echo '</br>';
			echo '<strong>Link:</strong>'.$meta_offerPrice = get_post_meta($post->ID,'link', true); 
			echo '</br>';
			echo '<strong>Language:</strong>'.$meta_offerPrice = get_post_meta($post->ID,'language', true); 
			echo '</br>';
			echo '<strong>Rating:</strong>'.$meta_offerPrice = get_post_meta($post->ID,'rating', true); 
			echo '</br>';
			echo '<strong>Subratings:</strong>'.$meta_offerPrice = get_post_meta($post->ID,'subratings', true); 
			echo '</br>';
			echo '<strong>Triptype:</strong>'.$meta_offerPrice = get_post_meta($post->ID,'triptype', true); 
			echo '</br>';
			
			?>
		</div>

        <?php endwhile; // end of the loop. ?>

    

    <?php do_action( 'snhotel_sidebar' ); ?>

<?php get_footer( 'snhotel' ); ?>
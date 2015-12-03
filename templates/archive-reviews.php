<?php
/**
 * The Template for displaying all archive accommodation.
 *
 * Override this template by copying it to yourtheme/revinate/archive-reviews.php
 *
 * @author      Sakha
 * @package     Revinate/Templates
 * @version     0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
get_header( 'snhotel' ); ?>

        <?php 	while ( have_posts() ) : the_post(); 
				global $post;
		?>
				
            <?php //snhotel_get_template_part( 'content', 'archive-accommodation' ); ?>
		<div class="container ratingbox">
				
			<div class="title">
				<?php
				echo '<strong>Title:</strong>';
				$meta_avail = get_post_meta($post->ID, 'title', true);?>
				<a href="<?php the_permalink() ?>"><?php echo $meta_avail; ?></a>
			</div>
			
			<?php
			echo '</br>'?>
			<div class="author">
				<?php
				echo '<strong>Author:</strong>';
				$meta_leadTime = get_post_meta($post->ID,'author', true);
				echo $meta_avail; 
				?>
			</div>
			
			<?php
			echo '</br>'?>
			<div class="stars">
				<?php
				echo '<strong>Rating:</strong>';
				$meta_offerPrice = get_post_meta(get_the_ID(),'rating', true);
				echo '</br>'; 
				echo '<span class="stars">'.$meta_offerPrice.'</span>';
				echo '</br>';?>
			</div>
			
			<?php
			echo '<strong>Triptype:</strong>'.$meta_offerPrice = get_post_meta($post->ID,'triptype', true);
			echo '</br>';
			?>

		</div>

        <?php endwhile; // end of the loop. ?>

    

    <?php do_action( 'snhotel_sidebar' ); ?>

<?php get_footer( 'snhotel' ); ?>
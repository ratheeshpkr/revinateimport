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


		<div class="container ratingbox">

			<div class="title">
				<?php
				echo '<strong>Title:</strong>';
				;?>
				<a href="<?php the_permalink() ?>"><?php echo get_post_meta($post->ID, 'title', true); ?></a>
			</div>

			<?php
			echo '</br>'?>
			<div class="author">
				<?php
				echo '<strong>Author:</strong>';
			  echo get_post_meta($post->ID,'author', true);
				?>
			</div>

			<?php
			echo '</br>'?>
			<div class="stars">
				<?php
				echo '<strong>Rating:</strong>';
				echo '</br>';
				echo '<span class="stars">'.get_post_meta(get_the_ID(),'rating', true).'</span>';
				echo '</br>';?>
			</div>

			<?php
			echo '<strong>Triptype:</strong>'.get_post_meta($post->ID,'triptype', true);
			echo '</br>';
			?>

		</div>

        <?php endwhile; // end of the loop.
	previous_posts_link();echo "&nbsp;&nbsp";
  next_posts_link();
	?>



<?php do_action( 'snhotel_sidebar' ); ?>

<?php get_footer( 'snhotel' ); ?>

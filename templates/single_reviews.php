<?php
/**

 * The Template for displaying all single event.

 *

 * Override this template by copying it to yourtheme/revinate/single-event.php

 *

 * @author      Sakha

 * @package     Revinate/Templates

 * @version     0.1

 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly





    
//do_action( 'revinate_before_main_content' );

global $post;

global $wpdb;
?>



<div class="container margin-top-100">

<?php
$content = get_post_field('post_content', get_the_ID());

if (get_the_title() != '') {
    echo '<strong>Title:&nbsp;&nbsp;</strong>' . get_the_title();
    echo '</br>';
}

if ($content != '') {
    echo '<strong>Content:&nbsp;&nbsp;</strong>' . $content;
    echo '</br>';
}
if (get_post_meta(get_the_ID(), 'author', true) != '') {
    echo '<strong>Author:&nbsp;&nbsp;</strong>' . get_post_meta(get_the_ID(), 'author', true);
    echo '</br>';
}

if (get_post_meta(get_the_ID(), 'authorlocation', true) != '') {
    echo '<strong>Author Location:&nbsp;&nbsp;</strong>' . get_post_meta(get_the_ID(), 'authorlocation', true);
    echo '</br>';
}
          
if (get_post_meta(get_the_ID(), 'language', true) != '') {
    echo '<strong>Language:&nbsp;&nbsp;</strong>' . get_post_meta(get_the_ID(), 'language', true);
    echo '</br>';
}

if (get_post_meta(get_the_ID(), 'rating', true) != '') {
    echo '<strong>Rating:&nbsp;&nbsp;</strong>';
    echo '<span class="stars">' . get_post_meta(get_the_ID(), 'rating', true) . '</span></br>';
}


if (get_post_meta(get_the_ID(), 'subratings', true) != '') {
    echo '<strong>Subratings:&nbsp;&nbsp;</strong>' . get_post_meta(get_the_ID(), 'subratings', true);
    echo '</br>';
}
if (get_post_meta(get_the_ID(), 'roomsubratings', true) != '') {
    echo '<strong>Rooms:&nbsp;&nbsp;</strong>' . get_post_meta(get_the_ID(), 'roomsubratings', true);
    echo '</br>';
}
if (get_post_meta(get_the_ID(), 'cleansubratings', true) != '') {
    echo '<strong>Cleanliness:&nbsp;&nbsp;</strong>' . get_post_meta(get_the_ID(), 'cleansubratings', true);
    echo '</br>';
}
if (get_post_meta(get_the_ID(), 'hotelsubratings', true) != '') {
    echo '<strong>Hotel Condition:&nbsp;&nbsp;</strong>' . get_post_meta(get_the_ID(), 'hotelsubratings', true);
    echo '</br>';
}
if (get_post_meta(get_the_ID(), 'triptype', true) != '') {
    echo '<strong>Triptype:&nbsp;&nbsp;</strong>' . get_post_meta(get_the_ID(), 'triptype', true);
    echo '</br>';
}


$postmeta = $wpdb->prefix . 'postmeta';
$avg = $wpdb->get_results("select AVG(meta_value) as Average from $postmeta where meta_key='rating'");
if($avg[0]->Average!='' ){
echo '<div class="reviews-subrating"></div>';
echo '<strong>Average Rating:&nbsp;&nbsp;</strong><div class="col-xs-12"><div class="review-star-bg-single"></div><div class="review-star" data-value="' . array_shift($avg)->Average . '"></div></div></div>';

echo '</br>';}
?>
    
   <div class="container">   
	<section id="comments" class="comments">
	<?php
   if (get_comments_number( $post->ID )!=0)
   {
   ?>
  <div class="comment-title">
    <h3><?php
		echo 'Hotel Response';
	?>
	
	</h3>
  </div>
  <?php
   }
   ?>
    <ol class="comment-list">
     <?php
		//Gather comments for a specific page/post 
		$comments = get_comments(array(
			'post_id' => $post->ID,
			'status' => 'approve' //Change this to the type of comments to be displayed
		));
		wp_list_comments( 'type=comment&callback=myreview_comment',$comments );
		function myreview_comment($comments, $args, $depth) {
    if ( 'div' === $args['style'] ) {
        $tag       = 'div';
        $add_below = 'comment';
    } else {
        $tag       = 'li';
        $add_below = 'div-comment';
    }
    ?>
    <<?php echo $tag ?> <?php comment_class( empty( $args['has_children'] ) ? '' : 'parent' ) ?> id="comment-<?php comment_ID() ?>">
    <?php if ( 'div' != $args['style'] ) : ?>
        <div id="div-comment-<?php comment_ID() ?>" class="comment-body">
    <?php endif; ?>
    <div class="comment-author vcard">
        <?php printf( __( '<cite class="fn">%s</cite>' ), get_comment_author_link() ); ?>
    </div>
    <?php if ( $comment->comment_approved == '0' ) : ?>
         <em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.' ); ?></em>
          <br />
    <?php endif; ?>

    <div class="comment-meta commentmetadata"><a href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ); ?>">
        <?php
        /* translators: 1: date, 2: time */
        printf( __('%1$s at %2$s'), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( '(Edit)' ), '  ', '' );
        ?>
    </div>
	<div class="clear-both">
    <?php comment_text(); ?>
    </div>
    <div class="reply">
        <?php comment_reply_link( array_merge( $args, array( 'add_below' => $add_below, 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
    </div>
    <?php if ( 'div' != $args['style'] ) : ?>
    </div>
    <?php endif; ?>
    <?php
    }
	?>
    </ol>
</section>

	</div>


    <?php dynamic_sidebar('room-sidebar'); ?>
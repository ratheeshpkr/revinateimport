<?php
add_action( 'revinate_before_main_content', 'revinate_template_archive_title', 10 );
function revinate_template_archive_title() {
    if ( !is_single() ) {
        return;
    }
    $object = get_queried_object();
?>
<h1 class="revinate-archive-title">
      <?php echo "Title: ". $object->post_title; ?>
</h1>
<?php }
?>
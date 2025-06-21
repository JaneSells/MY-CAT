<?php
get_header();
if (have_posts()) : while (have_posts()) : the_post();
?>
<div class="cbseo-single-cat-boarding">
    <h1><?php the_title(); ?></h1>
    <?php if (has_post_thumbnail()) : ?>
        <div class="cbseo-featured-image">
            <?php the_post_thumbnail('large', array('alt' => get_the_title() . ' Featured Image')); ?>
        </div>
    <?php endif; ?>
    <div class="cbseo-content">
        <?php the_content(); ?>
    </div>
</div>
<?php
endwhile; endif;
get_footer();
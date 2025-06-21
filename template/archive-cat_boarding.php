<?php
get_header();
$state_filter = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
$city_filter = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';
$args = array(
    'post_type' => 'cat_boarding',
    'post_status' => 'publish',
    'posts_per_page' => 10,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'tax_query' => array('relation' => 'AND'),
);
if ($state_filter) {
    $args['tax_query'][] = array(
        'taxonomy' => 'state',
        'field' => 'slug',
        'terms' => sanitize_title($state_filter),
    );
}
if ($city_filter) {
    $args['tax_query'][] = array(
        'taxonomy' => 'city',
        'field' => 'slug',
        'terms' => sanitize_title($city_filter),
    );
}
$locations = new WP_Query($args);
?>
<div class="cbseo-provider-archive">
    <h1>Cat Boarding Locations</h1>
    <div class="cbseo-filter-form">
    <form method="get" action="<?php echo esc_url(get_post_type_archive_link('cat_boarding')); ?>">
        <div class="cbseo-filter-row">
            <div class="cbseo-filter-field">
                <label for="state"><?php esc_html_e('Filter by State:', 'cbseo'); ?></label>
                <select name="state" id="state" onchange="this.form.submit()">
                    <option value=""><?php esc_html_e('All States', 'cbseo'); ?></option>
                    <?php
                    $states = get_terms(array('taxonomy' => 'state', 'hide_empty' => true));
                    if (!is_wp_error($states)) {
                        foreach ($states as $state) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($state->slug),
                                selected($state_filter ?? '', $state->slug, false),
                                esc_html($state->name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="cbseo-filter-field">
                <label for="city"><?php esc_html_e('Filter by City:', 'cbseo'); ?></label>
                <select name="city" id="city" onchange="this.form.submit()">
                    <option value=""><?php esc_html_e('All Cities', 'cbseo'); ?></option>
                    <?php
                    $cities = get_terms(array('taxonomy' => 'city', 'hide_empty' => true));
                    if (!is_wp_error($cities)) {
                        foreach ($cities as $city) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($city->slug),
                                selected($city_filter ?? '', $city->slug, false),
                                esc_html($city->name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        
    </form>
</div>

    <div class="cbseo-location-grid">
        <?php if ($locations->have_posts()) : ?>
            <?php while ($locations->have_posts()) : $locations->the_post(); ?>
                <div class="cbseo-location-card card primary">
                    <div class="cbseo-listing-photo">
                        <a href="<?php the_permalink(); ?>" class="photo-link">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('cbseo-provider-thumb', array('alt' => get_the_title() . ' Location Image')); ?>
                            <?php else : ?>
                                <img src="<?php echo esc_url(plugins_url('assets/placeholder.png', dirname(__FILE__))); ?>" alt="Placeholder Image" width="100" height="100">
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="cbseo-card-body">
                        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <p><?php echo wp_trim_words(get_the_excerpt(), 20); ?> <a href="<?php the_permalink(); ?>"><?php esc_html_e('Learn More', 'cbseo'); ?></a></p>
                    </div>
                </div>
            <?php endwhile; ?>
           
        <?php else : ?>
            <p><?php esc_html_e('No cat boarding locations found. Please check back later or generate more location pages.', 'cbseo'); ?></p>
        <?php endif; ?>
    </div>
    <br>
     <div class="cbseo-pagination">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'total' => $locations->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'prev_text' => __('Previous', 'cbseo'),
                    'next_text' => __('Next', 'cbseo'),
                ));
                ?>
            </div>
</div>
<?php
wp_reset_postdata();
get_footer();
?>
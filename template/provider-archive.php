<?php
get_header();
$state_filter = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
$city_filter = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';
$args = array(
    'post_type' => 'provider',
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
$providers = new WP_Query($args);
?>
<div class="cbseo-provider-archive">
    <h1>All Cat Boarding Providers</h1>
    <div class="cbseo-filter-form">
        <form method="get" action="">
            <div class="cbseo-filter-row">
                <div class="cbseo-filter-field">
                    <label for="state">Filter by State:</label>
                    <select name="state" id="state" onchange="this.form.submit()">
                        <option value="">All States</option>
                        <?php
                        $states = get_terms(array('taxonomy' => 'state', 'hide_empty' => false));
                        foreach ($states as $state) {
                            echo '<option value="' . esc_attr($state->slug) . '" ' . selected($state_filter, $state->slug, false) . '>' . esc_html($state->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="cbseo-filter-field">
                    <label for="city">Filter by City:</label>
                    <select name="city" id="city" onchange="this.form.submit()">
                        <option value="">All Cities</option>
                        <?php
                        $cities = get_terms(array('taxonomy' => 'city', 'hide_empty' => false));
                        foreach ($cities as $city) {
                            echo '<option value="' . esc_attr($city->slug) . '" ' . selected($city_filter, $city->slug, false) . '>' . esc_html($city->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <input type="hidden" name="paged" value="1">
        </form>
    </div>
    <div class="cbseo-provider-grid">
        <?php if ($providers->have_posts()) : ?>
            <?php while ($providers->have_posts()) : $providers->the_post(); ?>
                <?php
                // Consolidated meta query
                $meta = get_post_meta(get_the_ID(), '', true);
                $id_verified = isset($meta['_cbseo_id_verified'][0]) ? $meta['_cbseo_id_verified'][0] : '';
                $reply_rating = isset($meta['_cbseo_reply_rating'][0]) ? $meta['_cbseo_reply_rating'][0] : '';
                $relationship_status = isset($meta['_cbseo_relationship_status'][0]) ? $meta['_cbseo_relationship_status'][0] : '';
                $membership_level = isset($meta['_cbseo_membership_level'][0]) ? $meta['_cbseo_membership_level'][0] : '';
                $rate = isset($meta['_cbseo_rate'][0]) ? $meta['_cbseo_rate'][0] : '';
                $tagline = isset($meta['_cbseo_tagline'][0]) ? $meta['_cbseo_tagline'][0] : '';
                $reviews = isset($meta['_cbseo_reviews'][0]) && is_string($meta['_cbseo_reviews'][0]) ? json_decode($meta['_cbseo_reviews'][0], true) : [];
                $featured = isset($meta['_cbseo_featured_provider'][0]) ? $meta['_cbseo_featured_provider'][0] : '';
                ?>
                <div class="cbseo-provider-card card primary with-header search-listing">
                    <div class="cbseo-listing-photo listing-photo">
                        <a href="<?php the_permalink(); ?>" class="photo-link">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('cbseo-provider-thumb', array('alt' => get_the_title() . ' Profile Image')); ?>
                            <?php else : ?>
                                <img src="<?php echo plugins_url('assets/placeholder.png', dirname(__FILE__)); ?>" alt="Profile Image" width="100" height="100">
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="cbseo-card-header card-header">
                        <div class="cbseo-listing-tools listing-tools">
                            <button type="button" class="cbseo-icon-button icon-button" aria-label="Add to favorites">
                                <i class="fas fa-heart" style="color: #fff;"></i>
                            </button>
                        </div>
                        <div class="cbseo-listing-icons listing-icons">
                            <?php if (strtolower($id_verified) === 'yes') : ?>
                                <div class="cbseo-verified verified">
                                    <i class="fas fa-shield" style="color: white; font-size: 24px;"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($relationship_status) : ?>
                                <div class="cbseo-sitter-type">
                                    <?php
                                    $status_icon = '';
                                    switch (strtolower($relationship_status)) {
                                        case 'single':
                                            $status_icon = 'fas fa-user';
                                            break;
                                        case 'married':
                                            $status_icon = 'fas fa-users';
                                            break;
                                        default:
                                            $status_icon = 'fas fa-user';
                                    }
                                    ?>
                                    <i class="<?php echo $status_icon; ?>" style="color: white;"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($membership_level) : ?>
                                <div class="cbseo-membership-level">
                                    <?php
                                    $level_icon = '';
                                    switch (strtolower($membership_level)) {
                                        case 'starter':
                                            $level_icon = 'fas fa-star';
                                            break;
                                        case 'silver':
                                            $level_icon = 'fas fa-medal';
                                            break;
                                        case 'gold':
                                            $level_icon = 'fas fa-trophy';
                                            break;
                                        case 'platinum':
                                            $level_icon = 'fas fa-gem';
                                            break;
                                        default:
                                            $level_icon = 'fas fa-star';
                                    }
                                    ?>
                                    <i class="<?php echo $level_icon; ?>" style="color: white;"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($reply_rating) : ?>
                                <div class="cbseo-reply-rating reply-rating">
                                    <?php
                                    $rating = min(5, max(1, round($reply_rating)));
                                    for ($i = 1; $i <= 5; $i++) {
                                        $color = ($i <= $rating) ? 'white' : 'black';
                                        echo '<i class="fas fa-circle" style="color: ' . $color . '; margin-right: 4px;"></i>';
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="cbseo-card-body card-body with-pay-info">
                        <div class="cbseo-pay-info pay-info">
                            <?php if ($rate) : ?>
                                <span class="cbseo-chip chip primary-active with-icon">
                                    <i class="fas fa-dollar-sign" style="color: white;"></i>
                                    <span class="label"><?php echo esc_html($rate); ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3>
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <?php if ($tagline) : ?>
                            <div class="cbseo-listing-tag listing"><?php echo esc_html($tagline); ?></div>
                        <?php endif; ?>
                        <p class="cbseo-listing-intro listing-text">
                            <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?> <a href="<?php the_permalink(); ?>">View</a>
                        </p>
                        <?php if (!empty($reviews) && is_array($reviews)) : ?>
                            <ul class="cbseo-listing-rewards listing-tags">
                                <li>
                                    <i class="fas fa-star" style="color: #C487A5;"></i>
                                    <a href="<?php the_permalink(); ?>#reviews"><?php echo count($reviews); ?> review<?php echo count($reviews) == 1 ? '' : 's'; ?></a>
                                </li>
                            </ul>
                        <?php endif; ?>
                        <?php if ($featured === 'yes') : ?>
                            <span class="label">Featured</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
            <div class="cbseo-pagination">
                <?php
                echo paginate_links(
                    array(
                        'total' => $providers->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                        'prev_text' => __('Previous'),
                        'next_text' => __('Next'),
                    )
                );
                ?>
            </div>
        <?php else : ?>
            <p>No providers found. Contact us to list your service!</p>
        <?php endif; ?>
    </div>
</div>
<?php
wp_reset_postdata();
get_footer();
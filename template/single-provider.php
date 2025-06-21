<?php
get_header();
while (have_posts()) : the_post();
    $tagline = get_post_meta(get_the_ID(), '_cbseo_tagline', true);
    $relationship_status = get_post_meta(get_the_ID(), '_cbseo_relationship_status', true);
    $id_verified = get_post_meta(get_the_ID(), '_cbseo_id_verified', true);
    $reply_rating = get_post_meta(get_the_ID(), '_cbseo_reply_rating', true);
    $rating = get_post_meta(get_the_ID(), '_cbseo_rating', true);
    $rating = $rating ? json_decode($rating, true) : [];
    $rate = get_post_meta(get_the_ID(), '_cbseo_rate', true);
    $membership_level = get_post_meta(get_the_ID(), '_cbseo_membership_level', true);
    $gallery_images = get_post_meta(get_the_ID(), '_cbseo_gallery_images', true);
    $gallery_images = $gallery_images ? array_map('trim', explode(',', $gallery_images)) : [];
    $phone_number = get_post_meta(get_the_ID(), '_cbseo_phone_number', true);
    $pet_types = get_post_meta(get_the_ID(), '_cbseo_pet_types', true);
    $pet_types = $pet_types ? array_map('trim', explode(',', $pet_types)) : [];
    $preferred_locations = get_post_meta(get_the_ID(), '_cbseo_preferred_locations', true);
    $content = get_the_content();
    if (!$content) {
        $content = get_post_meta(get_the_ID(), '_cbseo_services_offered', true);
    }
    $main_image = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : (!empty($gallery_images) ? esc_url($gallery_images[0]) : plugins_url('assets/placeholder.png', dirname(__FILE__)));
    $trust_note = get_post_meta(get_the_ID(), '_cbseo_trust_note', true);
    if (!$trust_note) {
        $trust_note = 'I naturally enjoy maintaining spaces in prime condition. As a remote worker, I am home most of the day, so I can ensure your property and/or pets are well looked after, safe, and secure.';
    }
    $words = explode(' ', strip_tags($trust_note));
    if (count($words) > 50) {
        $trust_note = implode(' ', array_slice($words, 0, 50)) . '…';
    }
    $post_id = get_the_ID();
    $recaptcha_site_key = get_option('cbseo_recaptcha_site_key', '');
?>
<div id="page-body" class="cbseo-provider-single">
    <a href="<?php echo esc_url(get_post_type_archive_link('provider')); ?>" class="cbseo-return-link"><?php esc_html_e('Return to Search', 'cbseo'); ?></a>
    <div>
        <h1 class="combo-heading"><?php esc_html_e('House Sitter', 'cbseo'); ?> <?php the_title(); ?></h1>
        <h3>
            <?php if ($tagline) : ?>
                <span class="tagline"><?php echo esc_html($tagline); ?></span>
            <?php endif; ?>
            <div class="social-share-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="social-btn" title="<?php esc_attr_e('Share on Facebook', 'cbseo'); ?>">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title() . ' - Great house sitter to check out!'); ?>" target="_blank" class="social-btn" title="<?php esc_attr_e('Share on Twitter', 'cbseo'); ?>">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(get_permalink()); ?>&title=<?php echo urlencode(get_the_title()); ?>&summary=<?php echo urlencode('Check out this house sitter for pet care services!'); ?>" target="_blank" class="social-btn" title="<?php esc_attr_e('Share on LinkedIn', 'cbseo'); ?>">
                    <i class="fab fa-linkedin-in"></i>
                </a>
            </div>
        </h3>
    </div>
    <div class="cbseo-provider-header" id="mobile-pad">
        <?php if ($rate) : ?>
            <div></div>
            <span class="cbseo-rate-badge" id="mobile-hide">
                <?php echo esc_html($rate); ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="cbseo-profile-container">
        <div class="cbseo-profile-left">
            <div class="cbseo-profile-image" id="main-profile-image">
                <img src="<?php echo esc_url($main_image); ?>" alt="<?php echo esc_attr(get_the_title() . ' Profile Image'); ?>">
            </div>
            <?php if (!empty($gallery_images)) : ?>
                <div class="cbseo-gallery">
                    <button class="gallery-prev" onclick="scrollGallery('prev')" aria-label="<?php esc_attr_e('Previous Image', 'cbseo'); ?>">❮</button>
                    <div class="gallery-container" id="gallery-container">
                        <?php foreach ($gallery_images as $index => $image_url) : ?>
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(sprintf('Gallery Image %d for %s', $index + 1, get_the_title())); ?>" class="cbseo-gallery-thumb" onclick="switchMainImage(this, '<?php echo esc_url($image_url); ?>'); openLightbox(<?php echo $index; ?>)" tabindex="0" onkeydown="if(event.key === 'Enter') { switchMainImage(this, '<?php echo esc_url($image_url); ?>'); openLightbox(<?php echo $index; ?>); }" loading="lazy">
                        <?php endforeach; ?>
                    </div>
                    <button class="gallery-next" onclick="scrollGallery('next')" aria-label="<?php esc_attr_e('Next Image', 'cbseo'); ?>">❯</button>
                </div>
                <div id="cbseo-lightbox" class="cbseo-lightbox" role="dialog" aria-label="<?php esc_attr_e('Image Lightbox', 'cbseo'); ?>">
                    <span class="cbseo-lightbox-close" onclick="closeLightbox()" aria-label="<?php esc_attr_e('Close Lightbox', 'cbseo'); ?>">×</span>
                    <img id="cbseo-lightbox-image" src="" alt="<?php esc_attr_e('Lightbox Image', 'cbseo'); ?>">
                    <button class="cbseo-lightbox-prev" onclick="navigateLightbox(-1)" aria-label="<?php esc_attr_e('Previous Image in Lightbox', 'cbseo'); ?>">❮</button>
                    <button class="cbseo-lightbox-next" onclick="navigateLightbox(1)" aria-label="<?php esc_attr_e('Next Image in Lightbox', 'cbseo'); ?>">❯</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="cbseo-profile-right">
            <div class="about-section">
                <h2><i class="fas fa-user"></i> <?php esc_html_e('About Me', 'cbseo'); ?></h2>
                <div class="about-details">
                    <?php if ($relationship_status) : ?>
                        <?php
                        $status_icon = '';
                        switch (strtolower($relationship_status)) {
                            case 'single':
                                $status_icon = 'fas fa-user';
                                break;
                            case 'married':
                                $status_icon = 'fas fa-users';
                                break;
                            case 'complicated':
                                $status_icon = 'fas fa-question';
                                break;
                            default:
                                $status_icon = 'fas fa-user';
                        }
                        ?>
                        <p><i class="<?php echo esc_attr($status_icon); ?>"></i> <strong><?php esc_html_e('Relationship Status:', 'cbseo'); ?></strong> <?php echo esc_html($relationship_status); ?></p>
                    <?php endif; ?>
                    <?php if ($phone_number) : ?>
                        <p><i class="fas fa-phone"></i> <strong><?php esc_html_e('Phone:', 'cbseo'); ?></strong>
                            <span class="cbseo-phone" style="display: none;"><?php echo esc_html($phone_number); ?></span>
                            <button class="toggle-phone-btn" onclick="togglePhone(this)" aria-label="<?php esc_attr_e('Toggle Phone Number Visibility', 'cbseo'); ?>"><?php esc_html_e('Reveal Phone', 'cbseo'); ?></button>
                        </p>
                    <?php endif; ?>
                    <?php if ($rate) : ?>
                        <p><i class="fas fa-star"></i> <strong><?php esc_html_e('Rates:', 'cbseo'); ?></strong> <?php echo esc_html($rate); ?></p>
                    <?php endif; ?>
                    <?php if ($content) : ?>
                        <div class="content-text">
                            <?php
                            $full_content = apply_filters('the_content', $content);
                            $short_content = wp_trim_words(wp_strip_all_tags($full_content), 30, '…');
                            if (strlen(wp_strip_all_tags($full_content)) > 200) :
                            ?>
                                <div class="short-content"><?php echo esc_html($short_content); ?></div>
                                <div class="full-content" style="display: none;"><?php echo $full_content; ?></div>
                                <a href="#" class="read-more" onclick="toggleContent(this); return false;" aria-label="<?php esc_attr_e('Toggle Full Description', 'cbseo'); ?>"><?php esc_html_e('Read More', 'cbseo'); ?></a>
                            <?php else : ?>
                                <div><?php echo $full_content; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="cbseo-profile-content">
        <div class="trust-section">
            <h2><i class="fas fa-shield-alt"></i> <?php esc_html_e('Trust Rate', 'cbseo'); ?></h2>
            <div class="trust-details">
                <?php if ($membership_level) : ?>
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
                    <p class="trust-item"><strong><?php esc_html_e('Account Level:', 'cbseo'); ?></strong> <span><i class="<?php echo esc_attr($level_icon); ?>"></i> <?php echo esc_html($membership_level); ?></span></p>
                <?php endif; ?>
                <?php if ($reply_rating) : ?>
                    <?php
                    $rating_value = min(5, max(1, round($reply_rating)));
                    $stars = '';
                    for ($i = 1; $i <= 5; $i++) {
                        $stars .= '<i class="fas fa-star" style="color: ' . (($i <= $rating_value) ? '#ffd700' : '#ccc') . '"></i>';
                    }
                    ?>
                    <p class="trust-item"><strong><?php esc_html_e('Reply Rating:', 'cbseo'); ?></strong> <span><?php echo $stars; ?></span></p>
                <?php endif; ?>
                <?php if ($rating) : ?>
                    <p class="trust-item"><strong><?php esc_html_e('Reviews:', 'cbseo'); ?></strong> <span><?php echo count($rating); ?> <?php esc_html_e('star reviews', 'cbseo'); ?></span></p>
                <?php endif; ?>
            </div>
            <a href="#" class="cbseo-contact-btn"><?php esc_html_e('Join now to make contact', 'cbseo'); ?></a>
            <p class="trust-note" aria-label="<?php esc_attr_e('Provider Trust Note', 'cbseo'); ?>">
                <?php echo wp_kses_post($trust_note); ?>
            </p>
        </div>
        <div class="pet-care-section">
            <h2><i class="fas fa-paw"></i> <?php esc_html_e('Pet Care', 'cbseo'); ?></h2>
            <div class="pet-care-details">
                <div class="pet-care-item"><strong><?php esc_html_e('Willing to care for:', 'cbseo'); ?></strong> <span class="pet-types-list">
                    <?php
                    $pet_icons = [
                        'dogs' => 'fas fa-dog',
                        'cats' => 'fas fa-cat',
                        'fish' => 'fas fa-fish',
                        'birds' => 'fas fa-dove',
                        'rabbits/guinea pigs' => 'fas fa-kiwi-bird',
                        'chickens/ducks/geese' => 'fas fa-horse'
                    ];
                    $pet_list = [];
                    foreach ($pet_types as $pet_type) {
                        $lower_pet_type = strtolower(trim($pet_type));
                        $matched_key = array_reduce(array_keys($pet_icons), function($carry, $key) use ($lower_pet_type) {
                            return $lower_pet_type === strtolower($key) ? $key : $carry;
                        }, '');
                        if ($matched_key && isset($pet_icons[$matched_key])) {
                            $pet_list[] = '<span class="pet-icon"><i class="' . esc_attr($pet_icons[$matched_key]) . '"></i> ' . esc_html($matched_key) . '</span>';
                        } else {
                            $pet_list[] = '<span class="pet-icon">' . esc_html($pet_type) . '</span>';
                        }
                    }
                    echo implode(' ', $pet_list);
                    ?>
                </span></div>
            </div>
        </div>
        <div class="preferences-section">
            <h2><i class="fas fa-map-marker-alt"></i> <?php esc_html_e('Preferences', 'cbseo'); ?></h2>
            <div class="preferences-details">
                <?php if ($preferred_locations) : ?>
                    <div class="preferences-item"><strong><?php esc_html_e('Preferred Locations:', 'cbseo'); ?></strong> <span><?php echo esc_html($preferred_locations); ?></span></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="reviews-section" id="rating">
            <h2><?php printf(esc_html__('Reviews (%d)', 'cbseo'), count($rating)); ?></h2>
            <?php foreach ($rating as $review) : ?>
                <div class="review">
                    <p><strong><?php echo esc_html($review['name']); ?> (<?php echo esc_html($review['location']); ?>, <?php echo esc_html($review['duration']); ?>)</strong></p>
                    <p><?php echo esc_html($review['comment']); ?></p>
                    <p>
                        <?php
                        $ratings = explode(',', $review['ratings']);
                        foreach ($ratings as $rating_value) {
                            $rating_value = trim($rating_value);
                            $stars = '';
                            for ($i = 1; $i <= 5; $i++) {
                                $stars .= '<i class="fas fa-star" style="color: ' . (($i <= $rating_value) ? '#ffd700' : '#ccc') . '"></i>';
                            }
                            echo $stars . ' ' . esc_html($rating_value) . ' ';
                        }
                        ?>
                    </p>
                </div>
            <?php endforeach; ?>
            <?php if (is_user_logged_in()) : ?>
                <div class="cbseo-review-form">
                    <h3><?php esc_html_e('Submit a Review', 'cbseo'); ?></h3>
                    <form id="cbseo-review-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('cbseo_submit_review', 'cbseo_review_nonce'); ?>
                        <input type="hidden" name="action" value="cbseo_submit_review">
                        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                        <p>
                            <label for="review_name"><?php esc_html_e('Your Name:', 'cbseo'); ?></label>
                            <input type="text" id="review_name" name="review_name" required aria-required="true">
                        </p>
                        <p>
                            <label for="review_location"><?php esc_html_e('Your Location:', 'cbseo'); ?></label>
                            <input type="text" id="review_location" name="review_location" required aria-required="true">
                        </p>
                        <p>
                            <label for="review_duration"><?php esc_html_e('Duration of Service:', 'cbseo'); ?></label>
                            <input type="text" id="review_duration" name="review_duration" required aria-required="true" placeholder="<?php esc_attr_e('e.g., 1 week', 'cbseo'); ?>">
                        </p>
                        <p>
                            <label for="review_comment"><?php esc_html_e('Comment:', 'cbseo'); ?></label>
                            <textarea id="review_comment" name="review_comment" required aria-required="true"></textarea>
                        </p>
                        <p>
                            <label><?php esc_html_e('Ratings:', 'cbseo'); ?></label><br>
                            <span><?php esc_html_e('House Care:', 'cbseo'); ?></span>
                            <select name="rating_house" required aria-required="true">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php esc_html_e('Star', 'cbseo'); ?></option>
                                <?php endfor; ?>
                            </select>
                            <span><?php esc_html_e('Pet Care:', 'cbseo'); ?></span>
                            <select name="rating_pet" required aria-required="true">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php esc_html_e('Star', 'cbseo'); ?></option>
                                <?php endfor; ?>
                            </select>
                            <span><?php esc_html_e('Communication:', 'cbseo'); ?></span>
                            <select name="rating_communication" required aria-required="true">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php esc_html_e('Star', 'cbseo'); ?></option>
                                <?php endfor; ?>
                            </select>
                        </p>
                        <p>
                            <button type="submit" class="cbseo-submit-review-btn"><?php esc_html_e('Submit Review', 'cbseo'); ?></button>
                        </p>
                    </form>
                    <div id="cbseo-review-message"></div>
                </div>
            <?php else : ?>
                <p><a href="<?php echo wp_login_url(get_permalink()); ?>"><?php esc_html_e('Log in to submit a review', 'cbseo'); ?></a></p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        let touchStartX = 0;
        let touchEndX = 0;
        let currentLightboxIndex = 0;
        const galleryImages = <?php echo json_encode($gallery_images); ?>;

        function scrollGallery(direction) {
            const container = document.getElementById('gallery-container');
            const scrollAmount = 100;
            const maxScroll = container.scrollWidth - container.clientWidth;
            let newScroll = container.scrollLeft;

            if (direction === 'next') {
                newScroll += scrollAmount;
            } else {
                newScroll -= scrollAmount;
            }
            newScroll = Math.max(0, Math.min(newScroll, maxScroll));
            container.scrollTo({
                left: newScroll,
                behavior: 'smooth'
            });
        }

        function openLightbox(index) {
            currentLightboxIndex = index;
            const lightbox = document.getElementById('cbseo-lightbox');
            const lightboxImage = document.getElementById('cbseo-lightbox-image');
            lightboxImage.src = galleryImages[index];
            lightboxImage.alt = '<?php echo esc_attr(sprintf('Gallery Image %d for %s', ' + (currentLightboxIndex + 1) + ', get_the_title())); ?>';
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const lightbox = document.getElementById('cbseo-lightbox');
            lightbox.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function navigateLightbox(direction) {
            currentLightboxIndex = (currentLightboxIndex + direction + galleryImages.length) % galleryImages.length;
            const lightboxImage = document.getElementById('cbseo-lightbox-image');
            lightboxImage.src = galleryImages[currentLightboxIndex];
            lightboxImage.alt = '<?php echo esc_attr(sprintf('Gallery Image %d for %s', ' + (currentLightboxIndex + 1) + ', get_the_title())); ?>';
        }

        function toggleContent(link) {
            const contentText = link.parentElement;
            const shortContent = contentText.querySelector('.short-content');
            const fullContent = contentText.querySelector('.full-content');
            const readMore = contentText.querySelector('.read-more');

            if (fullContent && shortContent && readMore) {
                if (fullContent.style.display === 'none') {
                    shortContent.style.display = 'none';
                    fullContent.style.display = 'block';
                    readMore.textContent = '<?php esc_html_e("Read Less", "cbseo"); ?>';
                    readMore.setAttribute('aria-label', '<?php esc_attr_e("Hide Full Description", "cbseo"); ?>');
                } else {
                    shortContent.style.display = 'block';
                    fullContent.style.display = 'none';
                    readMore.textContent = '<?php esc_html_e("Read More", "cbseo"); ?>';
                    readMore.setAttribute('aria-label', '<?php esc_attr_e("Show Full Description", "cbseo"); ?>');
                }
            }
        }

        function togglePhone(button) {
            const phoneSpan = button.previousElementSibling;
            if (phoneSpan.style.display === 'none') {
                phoneSpan.style.display = 'inline';
                button.textContent = '<?php esc_html_e("Hide Phone", "cbseo"); ?>';
                button.setAttribute('aria-label', '<?php esc_attr_e("Hide Phone Number", "cbseo"); ?>');
            } else {
                phoneSpan.style.display = 'none';
                button.textContent = '<?php esc_html_e("Reveal Phone", "cbseo"); ?>';
                button.setAttribute('aria-label', '<?php esc_attr_e("Reveal Phone Number", "cbseo"); ?>');
            }
        }

        const galleryContainer = document.getElementById('gallery-container');
if (galleryContainer) {
    galleryContainer.addEventListener('touchstart', (e) => {
        touchStartX = e.touches[0].clientX;
    });

    galleryContainer.addEventListener('touchmove', (e) => {
        touchEndX = e.touches[0].clientX;
    });

    galleryContainer.addEventListener('touchend', () => {
        const deltaX = touchEndX - touchStartX;
        const scrollAmount = 100;
        if (Math.abs(deltaX) > 50) {
            if (deltaX > 0) {
                galleryContainer.scrollLeft -= scrollAmount;
            } else {
                galleryContainer.scrollLeft += scrollAmount;
            }
            galleryContainer.scrollLeft = Math.max(0, Math.min(galleryContainer.scrollLeft, galleryContainer.scrollWidth - galleryContainer.clientWidth));
        }
    });
}
        
        

        document.addEventListener('DOMContentLoaded', () => {
            const thumbnails = document.querySelectorAll('.cbseo-gallery-thumb');
            if (thumbnails.length > 0) {
                thumbnails[0].classList.add('active');
            }
            document.querySelectorAll('.read-more').forEach(link => {
                link.onclick = (e) => {
                    e.preventDefault();
                    toggleContent(link);
                };
            });
            document.addEventListener('keydown', (e) => {
                if (document.getElementById('cbseo-lightbox').style.display === 'flex') {
                    if (e.key === 'ArrowLeft') navigateLightbox(-1);
                    if (e.key === 'ArrowRight') navigateLightbox(1);
                    if (e.key === 'Escape') closeLightbox();
                }
            });
        });

        <?php if ($recaptcha_site_key) : ?>
            grecaptcha.ready(function() {
                grecaptcha.execute('<?php echo esc_js($recaptcha_site_key); ?>', {action: 'submit_review'}).then(function(token) {
                    document.getElementById('g-recaptcha-response').value = token;
                });
            });
        <?php endif; ?>
    </script>
</div>
<?php
endwhile;
get_footer();
?>
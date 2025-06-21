jQuery(document).ready(function($) {
    let currentIndex = 0;
    let autoSlideInterval;

    // Start auto-slide for gallery
    function startAutoSlide() {
        autoSlideInterval = setInterval(function() {
            const container = $('#gallery-container');
            const scrollAmount = 100;
            const maxScroll = container[0].scrollWidth - container[0].clientWidth;
            currentIndex = (currentIndex + 1) % $('.cbseo-gallery-thumb').length;
            let newScroll = currentIndex * scrollAmount;
            newScroll = Math.max(0, Math.min(newScroll, maxScroll));
            container.scrollTo({
                left: newScroll,
                behavior: 'smooth'
            });
        }, 3000);
    }

    // Pause auto-slide
    function pauseAutoSlide() {
        clearInterval(autoSlideInterval);
    }

    // Initialize gallery auto-slide if container exists
    if ($('#gallery-container').length) {
        startAutoSlide();
        $('#gallery-container').hover(pauseAutoSlide, startAutoSlide);
    }

    // Switch main profile image when clicking gallery thumbnail
    window.switchMainImage = function(thumb, url) {
        const mainImage = $('#main-profile-image img');
        mainImage.attr('src', url);
        $('.cbseo-gallery-thumb').removeClass('active');
        $(thumb).addClass('active');
    };

    // Keyboard navigation for gallery thumbnails
    $(document).on('keydown', function(e) {
        const thumbs = $('.cbseo-gallery-thumb');
        if (thumbs.length && document.activeElement.classList.contains('cbseo-gallery-thumb')) {
            const currentThumb = document.activeElement;
            const currentIndex = Array.from(thumbs).indexOf(currentThumb);
            if (e.key === 'ArrowLeft' && currentIndex > 0) {
                thumbs[currentIndex - 1].focus();
            } else if (e.key === 'ArrowRight' && currentIndex < thumbs.length - 1) {
                thumbs[currentIndex + 1].focus();
            }
        }
    });

    // Review form AJAX submission
    $('#cbseo-review-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $message = $('#cbseo-review-message');
        $message.removeClass('success error').text('');

        // Refresh reCAPTCHA token before submission
        if (typeof grecaptcha !== 'undefined') {
            grecaptcha.ready(function() {
                grecaptcha.execute(cbseo_recaptcha_site_key, {action: 'submit_review'}).then(function(token) {
                    $('#g-recaptcha-response').val(token);
                    submitReview($form, $message);
                }).catch(function(error) {
                    console.error('reCAPTCHA Error:', error);
                    $message.addClass('error').text('reCAPTCHA failed to load. Please try again.');
                });
            });
        } else {
            submitReview($form, $message);
        }
    });

    // Helper function to submit review via AJAX
    function submitReview($form, $message) {
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $message.addClass('success').text(response.message);
                    $form[0].reset();
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.ready(function() {
                            grecaptcha.execute(cbseo_recaptcha_site_key, {action: 'submit_review'}).then(function(token) {
                                $('#g-recaptcha-response').val(token);
                            });
                        });
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    $message.addClass('error').text(response.message || 'An error occurred.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown, jqXHR.responseText);
                $message.addClass('error').text('Failed to submit review. Please try again.');
            }
        });
    }


$(document).on('click', 'a', function(e) {
    const href = $(this).attr('href');
    const classes = $(this).attr('class') || 'no-class';
    console.log('Link Clicked: ', href, ' | Classes: ', classes);
});
$(document).off('click.cbseoPagination').on('click.cbseoPagination', '.cbseo-pagination a.page-numbers', function(e) {
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    const href = $(this).attr('href');
    console.log('CBSEO Pagination Clicked: ', href);
    window.location.href = href;
});




});
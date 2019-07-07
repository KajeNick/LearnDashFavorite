jQuery(document).ready(function ($) {
    addButtons();

    $('body').on('click', '.ldfavorite-button', function () {
        let that = $(this),
            data = {
                action: 'add_favorite',
                security: ldFavorites.security,
                videoUrl: that.data('video_url'),
                videoTitle: that.data('video_title'),
                videoLink: window.location.href
            };

        that.html('<img src="' + ldFavorites.preload + '" class="ldfavorites-preloader"/>');

        $.post(ldFavorites.ajaxurl, data, function (answer) {
            if (answer.success) {
                that.addClass('active');
                that.html('<i class="sf-icon-love"></i> In den Favoriten');
            } else {
                that.removeClass('active');
                that.html('<i class="sf-icon-love"></i> Zu den Favoriten');
            }
        }, 'json');
    });
});

/**
 * Place button after all video files
 */
function addButtons() {
    if ($('.tve_responsive_video_container').length) {
        $('.tve_responsive_video_container').each(function () {
            let that = $(this),
                video_url = that.find('iframe').data('src'),
                course_title = $('.course-lessons-widgets header h3 a').html(),
                training_title = $('.entry-content .entry-title').html(),
                video_title = "",
                active = false;
            
            if (that.closest('.thrv_responsive_video').parent().find('.thrv_heading').length) {
                video_title = that.closest('.thrv_responsive_video').parent().find('.thrv_heading h2').html();
            } else {
                video_title = that.closest('.thrv_responsive_video').prev().find('h2 span').html();
            }

            $.each(ldFavorites.list, function (index, value) {
                if (value.videoUrl == video_url) {
                    active = true;
                }
            });

            if (active) {
                $(this).parent().after('<button class="ldfavorite-button active" data-video_url="' + video_url + '" data-video_title="' + course_title + ' | ' + training_title + ' | ' + video_title + '" ><i class="sf-icon-love"></i> In den Favoriten</button>');
            } else {
                $(this).parent().after('<button class="ldfavorite-button" data-video_url="' + video_url + '" data-video_title="' + course_title + ' | ' + training_title + ' | ' + video_title + '" ><i class="sf-icon-love"></i>Zu den Favoriten</button>');
            }
        });
    }
}
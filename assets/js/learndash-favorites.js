jQuery(document).ready(function ($) {
    addButtons();

    $('body').on('click', '.ldfavorite-button', function () {
        let that = $(this),
            data = {
                action: 'add_favorite',
                security: ldFavorites.security,
                videoUrl: that.data('video_url')
            };

        that.html('<img src="' + ldFavorites.preload + '"/>');

        $.post(ldFavorites.ajaxurl, data, function (answer) {
            if (answer.success) {
                that.addClass('active');
                that.html('In favorite <i class="sf-icon-star-full"></i>');
            } else {
                that.removeClass('active');
                that.html('Add to favorite <i class="sf-icon-star-full"></i>');
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
            let video_url = $(this).parent().data('url'),
                active = false;

            $.each(ldFavorites.list, function(index, value) {
                if (value.videoUrl == video_url) {
                    active = true;
                }
            });

            if(active) {
                $(this).parent().after('<button class="ldfavorite-button active" data-video_url="' + video_url + '" >In favorite <i class="sf-icon-star-full"></i></button>');
            } else {
                $(this).parent().after('<button class="ldfavorite-button" data-video_url="' + video_url + '" >Add to favorite <i class="sf-icon-star-full"></i></button>');
            }
        });
    }
}
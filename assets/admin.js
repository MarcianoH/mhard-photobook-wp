(function($){
    'use strict';

    // Media picker utility
    function openMedia(frameTitle, cb){
        var frame = wp.media({
            title: frameTitle || (window.CLAdmin ? CLAdmin.mediaTitle : 'Select Image'),
            button: { text: (window.CLAdmin ? CLAdmin.mediaButton : 'Use this image') },
            multiple: false
        });
        frame.on('select', function(){
            var att = frame.state().get('selection').first().toJSON();
            cb(att);
        });
        frame.open();
    }

    $(document).on('click', '.cl-media-select', function(e){
        e.preventDefault();
        var $wrap = $(this).closest('[data-media-wrap]');
        openMedia(null, function(att){
            $wrap.find('[data-media-id]').val(att.id);
            $wrap.find('[data-media-url]').val(att.url);
            $wrap.find('img.cl-img-thumb').attr('src', att.url).show();
        });
    });

    $(document).on('click', '.cl-media-clear', function(e){
        e.preventDefault();
        var $wrap = $(this).closest('[data-media-wrap]');
        $wrap.find('[data-media-id]').val('');
        $wrap.find('[data-media-url]').val('');
        $wrap.find('img.cl-img-thumb').attr('src', '').hide();
    });

})(jQuery);

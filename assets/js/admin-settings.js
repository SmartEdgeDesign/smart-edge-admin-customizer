jQuery(document).ready(function($){
    var mediaUploader;

    $('#seac_upload_logo_btn').click(function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Extend the wp.media object
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Admin Logo',
            button: {
                text: 'Choose Logo'
            },
            multiple: false
        });

        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#seac_logo_url').val(attachment.url);
            $('#seac_logo_preview').css('background-image', 'url(' + attachment.url + ')');
        });

        // Open the uploader dialog
        mediaUploader.open();
    });

    // Remove Image Button
    $('#seac_remove_logo_btn').click(function(e){
        e.preventDefault();
        $('#seac_logo_url').val('');
        $('#seac_logo_preview').css('background-image', 'none');
    });
});
/**
 * Callback function for the 'click' event of the 'Set Footer Image'
 * anchor in its meta box.
 *
 * Displays the media uploader for selecting an image.
 *
 * @since 0.1.0
 */
function renderMediaUploader() {
    'use strict';
 
    var file_frame, image_data;
 
    /**
     * If an instance of file_frame already exists, then we can open it
     * rather than creating a new instance.
     */
    if ( undefined !== file_frame ) {
 
        file_frame.open();
        return;
 
    }
 
    /**
     * If we're this far, then an instance does not exist, so we need to
     * create our own.
     *
     * Here, use the wp.media library to define the settings of the Media
     * Uploader. We're opting to use the 'post' frame which is a template
     * defined in WordPress core and are initializing the file frame
     * with the 'insert' state.
     *
     * We're also not allowing the user to select more than one image.
     */
    file_frame = wp.media.frames.file_frame = wp.media({
//        frame:    'post',
//        frame: 'select',
        title: vja_params.title,
        library: {type: 'image'},
        button: {text: vja_params.insert},
 //       state: 'insert',
        multiple: false
    });
 
    /**
     * Setup an event handler for what to do when an image has been
     * selected.
     *
     * Since we're using the 'view' state when initializing
     * the file_frame, we need to make sure that the handler is attached
     * to the insert event.
     */
    file_frame.on( 'select', function() {
 
        /**
         * We'll cover this in the next version.
         */
        var media_attachment = file_frame.state().get('selection').first().toJSON();
        // Send the attachment URL to our custom input field via jQuery.
        var re = /^(.+)(\.\w+)/;
        var url = media_attachment.url.replace(re, '$1-150x150$2'); 
        $('#Cad-Contact-idPhoto').attr('src',url);
        $('#Cad_Contact_AttachmentId').val(media_attachment.id);
        $('#removeimage-div').show();
        $('#cad-dir-no-img').hide();
        $('#cad-dir-with-img').show();
    });
 
    // Now display the actual file_frame
    file_frame.open();
 
}

function removeMedia() {
    if ($('#sex').val() == 'F') {
        $('#Cad-Contact-idPhoto').attr('src', vja_params.f_avatar_path);
    }
    else {
        $('#Cad-Contact-idPhoto').attr('src', vja_params.m_avatar_path);        
    }
    $('#Cad-Contact-idPhoto').attr('srcset','');
    $('#Cad_Contact_AttachmentId').val("");
    $('#removeimage-div').hide();
    $('#cad-dir-no-img').show();
    $('#cad-dir-with-img').hide();
}
 
function uploadMedia() {
    var form = $('#your-profile').get(0);
    var formData = new FormData(form);
    formData.append('action', 'vja-directory-upload-file');
    formData.append('user_id', vja_params.user_id);
    formData.set('_wpnonce', vja_params.mediaNonce);
    $.ajax({
        type: 'POST',
        url: vja_params.ajaxurl,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json'
    })
	// using the done promise callback
	.done(function(data) {
        if ("" == data.error) {
            $('#Cad-Contact-idPhoto').attr('src', data.avatar_url);        
        }
    });
}

(function( $ ) {
    'use strict';
 
    $(function() {
        //$( '#uploadimage' ).on( 'click', function( evt ) {
        $( '#cad-dir-file' ).on( 'change', function( evt ) {
                
            // Stop the anchor's default behavior
            evt.preventDefault();
 
            // Display the media uploader
            //renderMediaUploader();
            uploadMedia();
 
        });

        $( '#removeimage ').on( 'click', function( evt ) {
            // Stop the anchor's default behavior
            evt.preventDefault();

            //Remove the media
            removeMedia();
        });

        if ($('#Cad_Contact_AttachmentId').val()=="") {
            $('#removeimage-div').hide();
            $('#cad-dir-with-img').hide();
        }
        else {
            $('#removeimage-div').show();
            $('#cad-dir-no-img').hide();
        }
 
    });
 
})( jQuery );
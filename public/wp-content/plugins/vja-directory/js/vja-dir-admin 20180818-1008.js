(function($)
{
    "use strict";
    // Uploading files
    var file_frame, attachment;

    // Add page slug to media uploader settings
    _wpPluploadSettings['defaults']['multipart_params']['admin_page']= 'VjaDirUploader';

    $('.additional-user-image').on('click', function( event ){

        event.preventDefault();

        // If the media frame already exists, reopen it.
        if ( file_frame ) {
            file_frame.open();
            $('#media-attachment-filters').hide();
            return;
        }

        var MediaLibraryTaxonomyFilter = wp.media.view.AttachmentFilters.extend({
            id: 'media-attachment-taxonomy-filter',

            createFilters: function() {
                var filters = {};
                // Formats the 'terms' we've included via wp_localize_script()
                _.each( MediaLibraryTaxonomyFilterData.terms || {}, function( value, index ) {
                    filters[ index ] = {
                        text: value.name,
                        props: {
                            // Change this: key needs to be the WP_Query var for the taxonomy
                            collection: value.slug,
                        }
                    };
                });
                filters.all = {
                    // Change this: use whatever default label you'd like
                    text:  'All collections',
                    props: {
                        // Change this: key needs to be the WP_Query var for the taxonomy
                        collection: ''
                    },
                    priority: 10
                };
                this.filters = filters;
            }
        });

        /**
         * Extend and override wp.media.view.AttachmentsBrowser to include our new filter
         */

        var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
        wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
            createToolbar: function() {
                // Make sure to load the original toolbar
                AttachmentsBrowser.prototype.createToolbar.call( this );
                this.toolbar.set( 'MediaLibraryTaxonomyFilter', new MediaLibraryTaxonomyFilter({
                    controller: this.controller,
                    model:      this.collection.props,
                    priority: -75
                }).render() );
            }
        });


        // Create the media frame.
        file_frame = new wp.media.view.MediaFrame.Post({

//        file_frame = wp.media.frames.file_frame = wp.media({
            title: "User's photo",
            button: {
                text: "Upload image",
            },
            library: {
                // CadVjaAvatar is a fake mime type
                // it is used to filter the list of queried attachment: only those having
                type: ['image/jpeg','image/png','CadVjaAvatar'],

            },
            multiple: false  // Set to true to allow multiple files to be selected
        });
        // When the file is selected, run a callback.
        file_frame.on( 'select', function() {
            // We set multiple to false so only get one file from the uploader
            attachment = file_frame.state().get('selection').first().toJSON();
            $('#Cad_Contact_AttachmentId').val(attachment.id);
            getAvatar(attachment.id);
        });

        // Finally, open the modal
        file_frame.open();
        $('#media-attachment-filters').hide();
    });
})(jQuery);


//
// /**
//  * Created by Christian on 25/12/2017.
//  */
// var Library = wp.media.controller.Library;
// var oldMediaFrame = wp.media.view.MediaFrame.Post;
// var l10n = wp.media.view.l10n;
//
// // Extending the current media library frame to add a new tab
// wp.media.view.MediaFrame.Post1 = oldMediaFrame.extend({
//
//     createStates: function(){
//         oldMediaFrame.prototype.createStates.apply(this);
//         this.states.get('insert').attributes.title=vja_params.title;
//     },
//
//     mainInsertToolbar: function( view ) {
//         var insertIntoPost = l10n.insertIntoPost;
//         l10n.insertIntoPost = vja_params.insert;
//         oldMediaFrame.prototype.mainInsertToolbar.apply( this, arguments );
//         l10n.insertIntoPost = insertIntoPost;
//     },
//
//     activate: function() {
//         oldMediaFrame.prototype.activate.apply(this);
//         this.menuItemVisibility('gallery','hide');
//         this.menuItemVisibility('embed','hide');
//         this.menuItemVisibility('playlist','hide');
//         this.menuItemVisibility('video-playlist','hide');
//     }
//
//
// });
//
// function renderMediaUploader() {
//     'use strict';
//
//     var file_frame, image_data;
//
//     /**
//      * If an instance of file_frame already exists, then we can open it
//      * rather than creating a new instance.
//      */
//     if ( undefined !== file_frame ) {
//
//         file_frame.open();
//         return;
//
//     }
//
//     /**
//      * If we're this far, then an instance does not exist, so we need to
//      * create our own.
//      *
//      * Here, use the wp.media library to define the settings of the Media
//      * Uploader. We're opting to use the 'post' frame which is a template
//      * defined in WordPress core and are initializing the file frame
//      * with the 'insert' state.
//      *
//      * We're also not allowing the user to select more than one image.
//      */
//     //file_frame = wp.media.frames.file_frame = wp.media({
//     file_frame = new wp.media.view.MediaFrame.Post1({
//         title: vja_params.title,
//         // add the parameter CadVjaAvatar to the query parameter
//         // it is used to filter the media library and only show the avatars
//         library: {type: ['image', 'CadVjaAvatar']},
//     //    button: {text: vja_params.insert},
//         multiple: false,
//     //    state: 'insert'
//     });
//     // add the parameter CadVjaAvatar to the upload query parameter
//     // it will be used to select the avatar folder to store the uploaded file
//     // see  UserPage.php - change_upload_dir
//     file_frame.uploader.options.uploader.params.CadVjaAvatar="1";
//
//     /**
//      * Setup an event handler for what to do when an image has been
//      * selected.
//      *
//      * Since we're using the 'view' state when initializing
//      * the file_frame, we need to make sure that the handler is attached
//      * to the insert event.
//      */
//     file_frame.on( 'insert', function() {
//
//         /**
//          * We'll cover this in the next version.
//          */
//         var media_attachment = file_frame.state().get('selection').first().toJSON();
//         $('#Cad_Contact_AttachmentId').val(media_attachment.id);
//         getAvatar(media_attachment.id);
//         // saveAvatar(media_attachment.id);
//         // Send the attachment URL to our custom input field via jQuery.
//         //var re = /^(.+)(\.\w+)/;
//         //var url = media_attachment.url.replace(re, '$1-150x150$2');
//         //$('#Cad-Contact-idPhoto').attr('src',url);
//         //$('#Cad_Contact_AttachmentId').val(media_attachment.id);
//     });
//
//     // Now display the actual file_frame
//     file_frame.open();
//
// }
//
function getAvatar(id) {
    postData = new FormData();
    postData.append('action','vja-directory-get-avatar');
    postData.append('_wpnonce',vja_params.mediaNonce);
    postData.append('user_id', vja_params.user_id);
    postData.append('media_id', id);
    $.ajax({
        type: 'POST',
        url: vja_params.ajaxurl,
        data: postData,
        processData: false,
        contentType: false,
        dataType: 'json'
    })
    // using the done promise callback
    .done(
        function(data) {
            if ("" == data.error) {
                $('#Cad-Contact-idPhoto')[0].outerHTML = data.avatar_image;
                $('#removeimage-div').show();
                $('#cad-dir-no-img').hide();
                $('#cad-dir-with-img').show();
            }
        }
    );
}

// function saveAvatar(id) {
//     var form = $('#your-profile').get(0);
//     var formData = new FormData(form);
//     formData.append('action', 'vja-directory-save-avatar');
//     formData.append('user_id', vja_params.user_id);
//     formData.append('media_id', id);
//     formData.set('_wpnonce', vja_params.mediaNonce);
//     $.ajax({
//         type: 'POST',
//         url: vja_params.ajaxurl,
//         data: formData,
//         processData: false,
//         contentType: false,
//         dataType: 'json'
//     })
//         // using the done promise callback
//     .done(
//         function(data) {
//             if ("" == data.error) {
//                 $('#Cad-Contact-idPhoto')[0].outerHTML = data.avatar_image;
//                 $('#removeimage-div').show();
//                 $('#cad-dir-no-img').hide();
//                 $('#cad-dir-with-img').show();
//             }
//         }
//     );
// }
//
// function removeMedia() {
//     var formData = new FormData();
//     formData.append('action', 'vja-directory-remove-avatar');
//     formData.append('user_id', vja_params.user_id);
//     formData.set('_wpnonce', vja_params.removeNonce);
//     $.ajax({
//         type: 'POST',
//         url: vja_params.ajaxurl,
//         data: formData,
//         processData: false,
//         contentType: false,
//         dataType: 'json'
//     })
//     // using the done promise callback
//     .done(
//         function(data) {
//             if ("" == data.error) {
//                 $('#Cad-Contact-idPhoto')[0].outerHTML = data.avatar_image;
//                 $('#Cad_Contact_AttachmentId').val('');
//                 $('#removeimage-div').hide();
//                 $('#cad-dir-no-img').show();
//                 $('#cad-dir-with-img').hide();
//             }
//         }
//     );
// }
//
// (function( $ ) {
//     'use strict';
//
//     $(function() {
//         $( '#uploadimage' ).on( 'click', function( evt ) {
//         //$( '#cad-dir-file' ).on( 'change', function( evt ) {
//
//             // Stop the anchor's default behavior
//             evt.preventDefault();
//
//             // Display the media uploader
//             renderMediaUploader();
//
//         });
//
//         $( '#removeimage ').on( 'click', function( evt ) {
//             // Stop the anchor's default behavior
//             evt.preventDefault();
//
//             //Remove the media
//             removeMedia();
//         });
//
//         if ($('#Cad_Contact_AttachmentId').val()=="") {
//             $('#removeimage-div').hide();
//             $('#cad-dir-with-img').hide();
//         }
//         else {
//             $('#removeimage-div').show();
//             $('#cad-dir-no-img').hide();
//         }
//
//     });
//
// })( jQuery );

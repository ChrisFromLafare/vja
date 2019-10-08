/**
 * Created by Christian on 25/12/2017.
 */
var Library = wp.media.controller.Library;
var oldMediaFrame = wp.media.view.MediaFrame.Post;
var l10n = wp.media.view.l10n;

var oldQuery = wp.media.model.Query;
var oldAttachments = wp.media.model.Attachments;

// Extending Attachments
wp.media.model.Attachments1 = oldAttachments.extend({


    _requery: function (refresh) {
        var props;
        if (this.props.get('query')) {
            props = this.props.toJSON();
            props.cache = (true !== refresh);
            this.mirror(wp.media.model.Query1.get(props));
        }
    },
});

// Extending the wp.media.query to add a parameter
var Query1;
Query1 = wp.media.model.Query1 = oldQuery.extend({

    sync: function (method, model, options) {
        options = options || {};
        if (!_.isEmpty(Query1.queryParams)) {
            options = _.extend(options, {'data': Query1.queryParams});
        }
        return oldQuery.prototype.sync.apply(this, arguments);
    }
}, {

    get: (function () {
        /**
         * @static
         * @type Array
         */
        var queries = [];

        /**
         * @returns {Query1}
         */
        return function (props, options) {
            var args = {},
                orderby = Query1.orderby,
                defaults = Query1.defaultProps,
                query,
                cache = !!props.cache || _.isUndefined(props.cache);

            // Remove the `query` property. This isn't linked to a query,
            // this *is* the query.
            delete props.query;
            delete props.cache;

            // Fill default args.
            _.defaults(props, defaults);

            // Normalize the order.
            props.order = props.order.toUpperCase();
            if ('DESC' !== props.order && 'ASC' !== props.order) {
                props.order = defaults.order.toUpperCase();
            }

            // Ensure we have a valid orderby value.
            if (!_.contains(orderby.allowed, props.orderby)) {
                props.orderby = defaults.orderby;
            }

            _.each(['include', 'exclude'], function (prop) {
                if (props[prop] && !_.isArray(props[prop])) {
                    props[prop] = [props[prop]];
                }
            });

            // Generate the query `args` object.
            // Correct any differing property names.
            _.each(props, function (value, prop) {
                if (_.isNull(value)) {
                    return;
                }

                args[Query1.propmap[prop] || prop] = value;
            });

            // Fill any other default query args.
            _.defaults(args, Query1.defaultArgs);

            // `props.orderby` does not always map directly to `args.orderby`.
            // Substitute exceptions specified in orderby.keymap.
            args.orderby = orderby.valuemap[props.orderby] || props.orderby;

            // Search the query cache for a matching query.
            if (cache) {
                query = _.find(queries, function (query) {
                    return _.isEqual(query.args, args);
                });
            } else {
                queries = [];
            }

            // Otherwise, create a new query and add it to the cache.
            if (!query) {
                query = new wp.media.model.Query1([], _.extend(options || {}, {
                    props: props,
                    args: args
                }));
                queries.push(query);
            }

            return query;
        };
    }()),

    queryParams: {},


});

wp.media.query1 = function (props) {
    return new wp.media.model.Attachments1(null, {
        props: _.extend(_.defaults(props || {}, {orderby: 'date'}), {query: true})
    });
};


// Extending the current media library frame to add a new tab
wp.media.view.MediaFrame.Post1 = oldMediaFrame.extend({

    initialize: function () {
        oldMediaFrame.prototype.initialize.apply(this, arguments);
        var options = this.options;
        // CadVjaAvatar is used to filter the media library and only show the avatars
        // Create a param which will be added to the ajax post request
        Query1.queryParams = {'CadVJAAvatar': '1'};
        this.states.add([
            new Library({
                id: 'CadVJAAvatar',
                title: vja_params.title,
                priority: 20,
                toolbar: 'main-insert',
                filterable: 'all',
                multiple: false,
                editable: false,
                library: wp.media.query1(_.defaults({
                    type: 'image'
                }, options.library)),

                // Show the attachment display settings.
                displaySettings: true,
                // Update user settings when users adjust the
                // attachment display settings.
                displayUserSettings: true
            }),
        ]);
        // Activate the CadVJAAvatar state
        this.setState('CadVJAAvatar');
    },

    mainInsertToolbar: function (view) {
        var insertIntoPost = l10n.insertIntoPost;
        l10n.insertIntoPost = vja_params.insert;
        oldMediaFrame.prototype.mainInsertToolbar.apply(this, arguments);
        l10n.insertIntoPost = insertIntoPost;
    },

    activate: function () {
        oldMediaFrame.prototype.activate.apply(this);
        this.menuItemVisibility('insert', 'hide');
        this.menuItemVisibility('gallery', 'hide');
        this.menuItemVisibility('embed', 'hide');
        this.menuItemVisibility('playlist', 'hide');
        this.menuItemVisibility('video-playlist', 'hide');
    }

});

function renderMediaUploader() {
    'use strict';

    var file_frame, image_data;

    /**
     * If an instance of file_frame already exists, then we can open it
     * rather than creating a new instance.
     */
    if (undefined !== file_frame) {

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
    //file_frame = wp.media.frames.file_frame = wp.media({
    file_frame = new wp.media.view.MediaFrame.Post1({
        title: vja_params.title,
        library: {type: 'image'},
        multiple: false,
        state: 'insert'
    });
    // add the parameter CadVjaAvatar to the upload query parameter
    // it will be used to select the avatar folder to store the uploaded file
    // see  UserPage.php - change_upload_dir
    // file_frame.uploader.options.uploader.params.CadVJADirUploader = "1";
    file_frame.uploader.options.uploader.params.CadVJADirUploader = vja_params.user_id;

    /**
     * Setup an event handler for what to do when an image has been
     * selected.
     *
     * Since we're using the 'view' state when initializing
     * the file_frame, we need to make sure that the handler is attached
     * to the insert event.
     */
    file_frame.on('insert', function () {

        /**
         * We'll cover this in the next version.
         */
        var media_attachment = file_frame.state().get('selection').first().toJSON();
        $('#Cad_Contact_AttachmentId').val(media_attachment.id);
        getAvatar(media_attachment.id);
        // saveAvatar(media_attachment.id);
        // Send the attachment URL to our custom input field via jQuery.
        //var re = /^(.+)(\.\w+)/;
        //var url = media_attachment.url.replace(re, '$1-150x150$2');
        //$('#Cad-Contact-idPhoto').attr('src',url);
        //$('#Cad_Contact_AttachmentId').val(media_attachment.id);
    });

    // Now display the actual file_frame
    file_frame.open();

}

function getAvatar(id) {
    postData = new FormData();
    postData.append('action', 'vja-directory-get-avatar');
    postData.append('_wpnonce', vja_params.mediaNonce);
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
            function (data) {
                if ("" == data.error) {
                    $('#Cad-Contact-idPhoto')[0].outerHTML = data.avatar_image;
                    $('#removeimage-div').show();
                    $('#cad-dir-no-img').hide();
                    $('#cad-dir-with-img').show();
                }
            }
        );
}

function saveAvatar(id) {
    var form = $('#your-profile').get(0);
    var formData = new FormData(form);
    formData.append('action', 'vja-directory-save-avatar');
    formData.append('user_id', vja_params.user_id);
    formData.append('media_id', id);
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
        .done(
            function (data) {
                if ("" == data.error) {
                    $('#Cad-Contact-idPhoto')[0].outerHTML = data.avatar_image;
                    $('#removeimage-div').show();
                    $('#cad-dir-no-img').hide();
                    $('#cad-dir-with-img').show();
                }
            }
        );
}

function removeMedia() {
    var formData = new FormData();
    formData.append('action', 'vja-directory-remove-avatar');
    formData.append('user_id', vja_params.user_id);
    formData.set('_wpnonce', vja_params.removeNonce);
    $.ajax({
        type: 'POST',
        url: vja_params.ajaxurl,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json'
    })
    // using the done promise callback
        .done(
            function (data) {
                if ("" == data.error) {
                    $('#Cad-Contact-idPhoto')[0].outerHTML = data.avatar_image;
                    $('#Cad_Contact_AttachmentId').val('');
                    $('#removeimage-div').hide();
                    $('#cad-dir-no-img').show();
                    $('#cad-dir-with-img').hide();
                }
            }
        );
}

(function ($) {
    'use strict';

    $(function () {
        // Hide the standard avatar
        $('.user-profile-picture').hide();

        $('#uploadimage').on('click', function (evt) {
            //$( '#cad-dir-file' ).on( 'change', function( evt ) {

            // Stop the anchor's default behavior
            evt.preventDefault();

            // Display the media uploader
            renderMediaUploader();

        });

        $('#removeimage ').on('click', function (evt) {
            // Stop the anchor's default behavior
            evt.preventDefault();

            //Remove the media
            removeMedia();
        });

        if ($('#Cad_Contact_AttachmentId').val() == "") {
            $('#removeimage-div').hide();
            $('#cad-dir-with-img').hide();
        }
        else {
            $('#removeimage-div').show();
            $('#cad-dir-no-img').hide();
        }

    });

})(jQuery);

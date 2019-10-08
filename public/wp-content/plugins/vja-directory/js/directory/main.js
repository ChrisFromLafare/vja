var VjaJS = VjaJS || {};

(function ($, VjaJS) {
    'use strict';

    /**
     * A mixin for collections/models.
     * Based on http://taylorlovett.com/2014/09/28/syncing-backbone-models-and-collections-to-admin-ajax-php/
     */
    var AdminAjaxSyncableMixin = {
        url: VjaJS.ajaxurl,
        action: 'vja_js_request',

        sync: function (method, object, options) {
            if (typeof options.data === 'undefined') {
                options.data = {};
            }

            options.data.nonce = VjaJS.nonce; // From localized script.
            options.data.action_type = method;

            // If no action defined, set default.
            if (undefined === options.data.action && undefined !== this.action) {
                options.data.action = this.action;
            }

            // // Reads work just fine.
            // if ( 'read' === method ) {
            //     return Backbone.sync( method, object, options );
            // }

            var json = this.toJSON();
            var formattedJSON = {};

            if (json instanceof Array) {
                formattedJSON.models = json;
            } else {
                formattedJSON.model = json;
            }

            _.extend(options.data, formattedJSON);

            // Need to use "application/x-www-form-urlencoded" MIME type.
            options.emulateJSON = true;

            // Force a POST with "create" method if not a read, otherwise admin-ajax.php does nothing.
            return Backbone.sync.call(this, 'create', object, options);
        }
    };

    /**
     * A model for all your syncable models to extend.
     * Based on http://taylorlovett.com/2014/09/28/syncing-backbone-models-and-collections-to-admin-ajax-php/
     */
    var BaseModel = Backbone.Model.extend(_.defaults({
        // parse: function( response ) {
        // Implement me depending on your response from admin-ajax.php!
        // return response;
        // }
    }, AdminAjaxSyncableMixin));

    /**
     * A collection for all your syncable collections to extend.
     * Based on http://taylorlovett.com/2014/09/28/syncing-backbone-models-and-collections-to-admin-ajax-php/
     */
    var BaseCollection = Backbone.Collection.extend(_.defaults({
        // parse: function( response ) {
        // Implement me depending on your response from admin-ajax.php!
        // return response;
        // }

    }, AdminAjaxSyncableMixin));

    /**
     * Single Member model.
     */

    VjaJS.Member = BaseModel.extend({
        action: 'vja_dir_member',
        defaults: {
            firstname: '',
            lastname: '',
            email: '',
            phone: '',
            sport: '',
            birthdate: '',
            addr: '',
            zipcode: '',
            city: '',
            sex: '',
            avatarsrc: ''
        }
    });

    /**
     * Collection of Members (Users).
     */
    VjaJS.MembersCollection = BaseCollection.extend({
        action: 'vja_dir_members',
        model: VjaJS.Member,

        parse: function (response) {
            return response.data;
        }
    });

    /**
     *  Directory Model
     *
     */
    VjaJS.Directory = BaseModel.extend({
//        action: 'vja_dir_directory',
        action: 'vja_dir_members',
        defaults: {
            params: {
                success: '',
                page: 1,
                pagelength: 10,
                searchpattern: '',
                searchwhere: 'contains',
                sport: ''
            },
            members: new VjaJS.MembersCollection(),
            currentpage: 1,
            maxpage: 20
        },

        initialize: function (attributes, options) {
            this.set('members', new VjaJS.MembersCollection());
        },

        toJSON: function (options) {
            var model = _.clone(this.attributes);
            model = _.omit(this.attributes, 'params');
            model.members = this.get('members').toJSON(options);
            return model;
            //    return _.omit(this.attributes, 'params')
        },

        parse: function (response, option) {
            this.get("members").set(response.data.members);
            return _.omit(response.data, 'members');
        }
    });

    /**
     * The main view for listing members.
     */
    VjaJS.DirectoryView = Backbone.View.extend({
        el: '#vja-directory-realcontent',
        template: _.template($('#vjadir-directory-tmpl').html()),
        events: {
            "change select#sport": "selectsport",
            "change select#perpage": "perpage",
            "change select#searchwhere": "searchwhere",
            "keydown input#search": "search",
            "search input#search": "immediatesearch",
        },

        selectsport: function (e) {
            var params = _.clone(this.model.get('params'));
            params.sport = $(e.target).val();
            this.model.set('params', params);
            this.refresh();
        },

        perpage: function (e) {
            var params = _.clone(this.model.get('params'));
            params.pagelength = $(e.target).val();
            this.model.set('params', params);
            this.refresh();
        },

        searchwhere: function (e) {
//            if (this.model.get('params').searchpattern == '') return;
            var params = _.clone(this.model.get('params'));
            params.searchwhere = $(e.target).val();
            this.model.set('params', params);
            this.refresh();
        },

        search: function (e) {
            // e.key = undefined if paste or use autocomplete
            if ((typeof(e.key)==='undefined') || (e.key == 'Delete') || (e.key == 'Backspace')) {
                this.delayedsearch(e);
                return;
            }
            if (e.key == 'Enter'){
                this.immediatesearch(e);
                return;
            }
            if ((e.key == 'ArrowLeft') || (e.key == 'ArrowRight')) {
                return;
            }
            var regex = new RegExp("^[a-zA-Z0-9]+$");
            var key = String.fromCharCode(!e.charCode ? e.which : e.charCode);
            if (!regex.test(key)) {
                event.preventDefault();
                return false;
            }
            this.delayedsearch(e);
        },

        immediatesearch: function (e) {
            var val = $(e.target).val();
            var params = _.clone(this.model.get('params'));
            if (params.searchpattern != val){
            params.searchpattern = val;
            this.model.set('params', params);
            this.refresh();
            }
        },

        reset: function(pageId) {
            var params = _.clone(this.model.get('params'));
            params.page = pageId;
            this.model.set('params', params);
            this.refresh();
        },

        currentpagechange: function (e) {
            var currentpage = parseInt(this.model.get('currentpage'));
            var maxpage = parseInt(this.model.get('maxpage'));
            var pagination = '';
            if (currentpage > 1) {
                pagination += '<a href="/annuairejs/page/' + (currentpage - 1) + '/">previous</a>';
                pagination += '<a href="/annuairejs/">1</a>';
                if ((currentpage - 3) > 1) pagination += '<span class="nav-ext">...</span>';
            }
            if ((currentpage - 2) > 1) {
                pagination += '<a href="/annuairejs/page/' + (currentpage - 2) + '/">' + (currentpage - 2) + '</a>';
            }
            if ((currentpage - 1) > 1) {
                pagination += '<a href="/annuairejs/page/' + (currentpage - 1) + '/">' + (currentpage - 1) + '</a>';
            }
            pagination += '<span>' + currentpage + '</span>';
            if ((currentpage + 1) < maxpage) {
                pagination += '<a href="/annuairejs/page/' + (currentpage + 1) + '/">' + (currentpage + 1) + '</a>';
            }
            if ((currentpage + 2) < maxpage) {
                pagination += '<a href="/annuairejs/page/' + (currentpage + 2) + '/">' + (currentpage + 2) + '</a>';
            }
            if (currentpage < maxpage) {
                if ((currentpage + 3) < maxpage) pagination += '<span class="nav-ext">...</span>';
                pagination += '<a href="/annuairejs/page/' + maxpage + '/">' + maxpage + '</a>';
                pagination += '<a href="/annuairejs/page/' + (currentpage + 1) + '/">next</a>';
            }
            this.$el.find('.Cad-directory-footer').html(pagination);
        },

        error: function (model, response, options) {
            var errorView = new VjaJS.ErrorView({
                model: new Backbone.Model({
                    errnum: response.status,
                    errtext: response.statusText
                })
            });
            errorView.render();
            $("#vja-directory-modal").hide();
            console.log(response.status);
            console.log(response.statusText);
        },

        success: function(model, response, options) {
            var currentpage = model.get('currentpage');
            if (model.get('params').page != currentpage)
                Backbone.history.navigate('annuairejs/page/'+ currentpage +'/', {trigger: false, replace: true});
        },

        render: function () {
            var html = this.template({});
            this.$el.html(html);
            return this;
        },

        refresh: function () {
            var previous = this.model.previous('params');
            // Refresh isn't needed if searchwhere has been modified and there is no pattern
            if ((typeof(previous) == 'undefined') || (this.model.get('params').searchwhere == previous.searchwhere) || (this.model.get('params').searchpattern != '')) {
                $("#vja-directory-modal").show();
                this.model.fetch({
                    data: this.model.get('params'),
                    reset: true,
                    error: this.error,
                    success: this.success
                });
            }
            return this;
        },

        initialize: function () {
            VjaJS.MembersList = new VjaJS.MembersListView({collection: this.model.get('members')});
            this.delayedsearch = _.debounce(this.immediatesearch, 400);
            this.on('reset',this.reset);
            this.model.on("change:currentpage", this.currentpagechange, this);
            this.model.on("change:maxpage", this.currentpagechange, this);

            this.render();
 //           this.refresh();
 //           this.currentpagechange();
        }
    });

    VjaJS.MembersListView = Backbone.View.extend({
        template: _.template($('#vjadir-members-tmpl').html()),

        initialize: function () {
            this.listenTo(this.collection, 'update', this.render.bind(this));
        },

        render: function () {
            var $list = $('.members-container');

            $list.empty();


            if (this.collection.length > 0) {
                this.collection.each(function (model) {
                    var item = new VjaJS.MembersListItemView({model: model});
                    $list.append(item.render().$el);
                }, this);
            }
            else {
                $list.append('<div class="Cad-contact Cad-error">' + VjaJS.nomember + '</div>');
            }
            $('#vja-directory-modal').hide();
            return this;
        }
    });

    /**
     * A single member's view.
     */
    VjaJS.MembersListItemView = Backbone.View.extend({
        tagName: 'div',
        className: 'Cad-contact',
        template: _.template($('#vjadir-contact-tmpl').html()),

        initialize: function () {
            this.listenTo(this.model, 'change', this.render);
        },

        render: function () {
            var html = this.template(this.model.toJSON());
            this.$el.html(html);
            return this;
        }
    });

    /**
     * An error view
     */
    VjaJS.ErrorView = Backbone.View.extend({
        el: '#vja-directory-realcontent',
        template: _.template($('#vjadir-error-tmpl').html()),
        render: function () {
            var html = this.template(this.model.toJSON());
            this.$el.html(html);
        }
    })

    /**
     *  Create a Router to handle pagination
     */
    VjaJS.Router = Backbone.Router.extend({
        routes: {
            'annuairejs/page/:id/' : 'paginate',
            '*path' : 'default'
        },

        paginate: function(id) {
            VjaJS.DirectoryV.trigger("reset", parseInt(id));
        },

        default: function() {
            VjaJS.DirectoryV.trigger("reset", 1);
        }
    });

    /**
     * Set initial data into view and start recurring display updates.
     */
    VjaJS.init = function () {
        // If the current page contains the main class for members directory
        // display the first page
        VjaJS.DirectoryM = new VjaJS.Directory();
        if ($('#vja-directory-main').length > 0) {
            VjaJS.DirectoryV = new VjaJS.DirectoryView({model: VjaJS.DirectoryM});
        }

        VjaJS.router = new VjaJS.Router();
        Backbone.history.start({pushState: true});
//        Backbone.history.start();

        $( ".Cad-directory-footer" ).on( 'click', 'a', function( e ) {
            e.preventDefault();
            Backbone.history.navigate( this.pathname, {trigger: true, replace: false} );
            $('html, body').animate({
                scrollTop: $(".Cad-directory-header").offset().top
            }, 500, 'linear');

        });
    };

    $(document).ready(function () {
        VjaJS.init();
    });


})(jQuery, VjaJS);
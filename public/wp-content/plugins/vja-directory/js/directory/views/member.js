VjaDirectory.Views.Member = Backbone.View.extend({

    tagName: 'div',
    className: 'Cad-contact',
    template: _.template($('#vjadir-contact').html()),

    // events: { 'click .delete-employee': 'onClickDelete' },
    //
    // initialize: function() {
    //     this.listenTo(this.model, 'remove', this.remove);
    // },
    //
    render: function() {
        var html = this.template(this.model.toJSON()); this.$el.append(html);
        return this;
    },

    // onClickDelete: function(e) {
    //     e.preventDefault(); this.model.collection.remove(this.model);
    // }
});


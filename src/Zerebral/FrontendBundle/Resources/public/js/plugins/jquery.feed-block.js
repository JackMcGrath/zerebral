var ZerebralCourseDetailFeedBlock = function(element, options) {
    var self = this;
    self.element = element;
    self.feedItemFormTextarea = element.find('.feed-item-textarea');
    self.feedItemForm = element.find('#ajaxFeedItemForm');
    self.feedItemFormDiv = element.find('.feed-item-form');

    self.feedItemsDiv = element.find('.feed-items');
    self.itemsDiv = element.find('.feed-item');
    self.commentsDiv = element.find('.feed-item .comments');
    self.options = options;
};

ZerebralCourseDetailFeedBlock.prototype = {
    element: undefined,
    options: undefined,

    feedItemFormTextarea: undefined,
    feedItemForm: undefined,
    feedItemFormDiv: undefined,
    commentsDiv: undefined,
    itemsDiv: undefined,
    feedItemsDiv: undefined,


    init: function() {
        var self = this;
        this.feedItemFormTextarea.click($.proxy(self.expandFeedItemForm, self));
        this.feedItemFormDiv.find('.buttons .cancel-link').click($.proxy(self.collapseFeedItemForm, self));
        this.feedItemFormDiv.find('.attach-link').click($.proxy(self.setFeedItemFormType, self));
        this.feedItemFormDiv.find('.attached-link-delete a').click($.proxy(self.resetMainFormType, self));


        this.feedItemsDiv.on('click', '.comment-input', $.proxy(self.expandCommentForm, self));
        this.feedItemsDiv.on('click', '.comment .buttons .cancel-link', $.proxy(self.collapseCommentForm, self));
        this.feedItemsDiv.on('click', '.show-comment-form-link', $.proxy(self.showCommentForm, self));

        this.feedItemForm.zerebralAjaxForm({
            success: $.proxy(self.addItemBlock, this),
            error: function() { alert('Oops, seems like unknown error has appeared!'); },
            dataType: 'html'
        });

        $.each(this.commentsDiv.find('form'), function(index, value) {
            $(this).zerebralAjaxForm({
                data: { feedType: 'course' },
                success: $.proxy(self.addCommentBlock, this),
                error: function() { alert('Oops, seems like unknown error has appeared!'); },
                dataType: 'html'
            });
        })
    },

    expandFeedItemForm: function() {
        this.feedItemFormTextarea.data('background-image', this.feedItemFormTextarea.css('background-image'));
        this.feedItemFormTextarea.css('background-image', 'none').animate({
            width: 621,
            'margin-top': 20,
            'margin-bottom': 10,
            'margin-left': 20,
            'margin-right': 20,
            'padding-left': 6,
            'padding-right': 6,
            height: '+120'
        }, 300);
        this.feedItemForm.css('background-color', '#f3f3f3').find('.feed-item-form-controls').show();
    },
    collapseFeedItemForm: function(event) {
        if (event) {
            event.preventDefault();
        }

        var self = this;
        self.resetMainFormType();
        this.feedItemFormTextarea.val('').parent().animate({'background-color': 'transparent'}).find('.feed-item-form-controls').hide();
        this.feedItemFormTextarea.animate({
            width: 571,
            margin: 0,
            'padding-right': 96,
            height: 18
        }, 500, function() {
            self.feedItemFormTextarea.css('background-image', self.feedItemFormTextarea.data('background-image'));
        });

    },
    setFeedItemFormType: function(event) {
        event.preventDefault();
        var link = $(event.target);
        this.feedItemForm.find('.attach-links').hide();
        this.feedItemForm.find('input.comment-type').val(link.parent().data('linkType'));
        this.feedItemForm.find('.attached-link').slideDown();
        this.feedItemForm.find('.attached-link-field').val('');
    },
    resetMainFormType: function(event) {
        if (event) {
            event.preventDefault();
        }
        this.feedItemForm.find('.attached-link').slideUp();
        this.feedItemForm.find('input.comment-type').val('text');
        this.feedItemForm.find('.attach-links').show();
    },

    expandCommentForm: function(event) {
        var input = $(event.target);
        input.animate({
            height: '+60'
        }, 300);
        input.parent().find('.buttons').show();
    },
    collapseCommentForm: function(event) {
        event.preventDefault();
        var link = $(event.target);
        link.parent().hide();
        link.parents('form').find('.comment-input').animate({
            height: '18'
        }, 300).val('');

    },
    showCommentForm: function(event) {
        event.preventDefault();

        var link = $(event.target);
        link.parents('.feed-item').find('.comment.hidden').removeClass('hidden');
    },
    addItemBlock: function(response) {
        var self = this;
        var itemBlock = $(response);

        itemBlock.find('form').zerebralAjaxForm({
            data: { feedType: 'course' },
            success: $.proxy(self.addCommentBlock, itemBlock.find('form')),
            error: function() { alert('Oops, seems like unknown error has appeared!');},
            dataType: 'html'
        });
        this.feedItemsDiv.find('.empty').remove().end().prepend(itemBlock);
        this.collapseFeedItemForm();
    },
    addCommentBlock: function(response) {
        $(this).parents('.comment').before(response);
        var commentsCount = $(this).parents('.feed-item').find('.show-comment-form-link').data('commentsCount');
        $(this).parents('.feed-item').find('.show-comment-form-link span').html((commentsCount + 1));
        $(this).find('.cancel-link').click();

    },
    _: ''
};

$.registry('zerebralCourseDetailFeedBlock', ZerebralCourseDetailFeedBlock, {
    methods: ['init']
});




//    ASSIGNMENT
var ZerebralAssignmentDetailFeedBlock = function(element, options) {
    var self = this;
    self.element = element;
    self.feedCommentFormDiv = element.find('.feed-comment-form');
    self.feedCommentForm = element.find('#ajaxFeedCommentForm');
    self.feedCommentsDiv = element.find('.comments');
    self.options = options;
};

ZerebralAssignmentDetailFeedBlock.prototype = {
    element: undefined,
    options: undefined,

    feedCommentFormDiv: undefined,
    feedCommentForm: undefined,
    feedCommentsDiv: undefined,


        init: function() {
        var self = this;
        this.feedCommentFormDiv.find('.attach-link').click($.proxy(self.setFeedItemFormType, self));
        this.feedCommentFormDiv.find('.attached-link-delete a').click($.proxy(self.resetMainFormType, self));

        this.feedCommentsDiv.on('click', 'a.delete-link', $.proxy(self.deleteCommentBlock, self));


        this.feedCommentForm.zerebralAjaxForm({
            data: { feedType: 'assignment' },
            success: $.proxy(self.addCommentBlock, this),
            error: function() { alert('Oops, seems like unknown error has appeared!'); },
            dataType: 'html'
        });

    },

    setFeedItemFormType: function(event) {
        event.preventDefault();
        var link = $(event.target);
        this.feedCommentForm.find('.attach-links').hide();
        this.feedCommentForm.find('input.comment-type').val(link.parent().data('linkType'));
        this.feedCommentForm.find('.attached-link').slideDown();
    },
    resetMainFormType: function(event) {
        event.preventDefault();
        var link = $(event.target);
        this.feedCommentForm.find('.attached-link').slideUp();
        this.feedCommentForm.find('input.comment-type').val('text');
        this.feedCommentForm.find('.attach-links').show();
        this.feedCommentForm.find('.attached-link-field').val('');
    },
    addCommentBlock: function(response) {
        var commentBlock = $(response);
        commentBlock.css('display', 'none');
        if (this.feedCommentsDiv.find('.comment:last').length > 0) {
            this.feedCommentsDiv.find('.comment:last').after(commentBlock);
        } else {
            this.feedCommentsDiv.find('.empty').remove().end().append(commentBlock);
        }
        commentBlock.slideDown();
        this.feedCommentFormDiv.find('textarea').val('');
        this.feedCommentFormDiv.find('.attached-link-delete a').click();

    },
    // @todo: Implement showing "empty comments" message if delete last comment
    deleteCommentBlock: function(event) {
        event.preventDefault();
        var link = $(event.target);
        if (window.confirm('Are you sure to delete comment?')) {
            var url = link.attr('href');
            $.ajax({
                url: url,
                type: 'post',
                dataType: 'json',
                success: function(response) {
                    link.parents('.comment').slideUp('fast', function() {
                        link.parents('.comment').remove();
                    });
                },
                error: function() {alert('Oops, seems like unknown error has appeared!') }
            })
        }
    },
    _: ''
};

$.registry('zerebralAssignmentDetailFeedBlock', ZerebralAssignmentDetailFeedBlock, {
    methods: ['init']
});
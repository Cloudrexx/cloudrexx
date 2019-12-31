cx.ready(function() {
    cx.jQuery('.news_category_switch').bind('click', function() {
        cx.ajax(
            'News',
            'switchCategoryVisibility',
            {
                data: {
                    id: cx.jQuery(this).data('id')
                },
                element: this,
                beforeSend: function() {
                    cx.ui.messages.showLoad();
                },
                postSuccess: function(data) {
                    cx.ui.messages.removeAll();
                    if (data.data == '1') {
                        cx.jQuery(this.element).removeClass('hidden');
                    } else {
                        cx.jQuery(this.element).addClass('hidden');
                    }
                }
            }
        );
    });
});

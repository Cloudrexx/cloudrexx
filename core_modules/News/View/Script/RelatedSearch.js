cx.ready(function () {
    if (cx.jQuery(".news-related-search").length > 0) {

        if (cx.jQuery('#news-related-news-list ul li').length == 0) {
            cx.jQuery('#news-related-news-list').hide();
        } else {
            cx.jQuery('#news-related-news-list').show();
        }

        var relatedNewsIdList = new Array(); //List of related news ids
        //Adding the already selected related news ids
        cx.jQuery('#news-related-news-list .relationNewsId').each(function () {
            relatedNewsIdList.push(cx.jQuery(this).val());
        });
//      $J('.news_list_close').on('click', function(){
//Use the above line when the jquery version is 1.7 or higher instead of the next line
        cx.jQuery('#news-related-news-list .news_list_close').live('click', function () {
            /**
             * While removing the news relationship the id is removed from
             * the related news list
             */
            var selectedId = cx.jQuery(this).closest('li').find('.relationNewsId').val();
            relatedNewsIdList.splice(relatedNewsIdList.indexOf(selectedId), 1);

            // If all the related news are removed then the row is hided
            cx.jQuery(this).closest('li').remove();
            if (cx.jQuery('#news-related-news-list ul li').length == 0) {
                cx.jQuery('#news-related-news-list').hide();
            }
        });

        var noResultsMsg = cx.variables.get("noResultsMsg", 'News/RelatedSearch');
        var ajaxRequest  = false;

        cx.jQuery('.news-related-search-input').keyup(function(e){
            if (ajaxRequest) {
                ajaxRequest.abort();
                cx.jQuery(this).removeClass('loading');
            }
        });
        cx.jQuery('.news-related-search-input').autocomplete({
            delay: 500,
            source: function (request, response) {
                var id = cx.jQuery('#newsId').val();
                ajaxRequest = cx.ajax(
                    'News',
                    'getNews',
                    {
                        data: {
                            id: id,
                            term: request.term
                        },
                        success: function(json) {
                            var newsList = new Array();
                            if (json.status == 'success') {
                                cx.jQuery.each(json.data, function(id, value) {
                                    //Checking the news is already in the related news list
                                    if (relatedNewsIdList.indexOf(id) === -1) {
                                        var news = {
                                            id: id,
                                            value: value
                                        };
                                        newsList.push(news);
                                    }
                                });
                            }
                            if (!newsList.length) {
                                newsList = [{
                                    id: 0,
                                    value: noResultsMsg
                                }];
                            }
                            response(newsList);
                        },
                        showMessage: false
                    }
                );
            },
            search: function () {
                if (ajaxRequest) {
                    ajaxRequest.abort();
                }
                cx.jQuery(this).removeClass('loading');
                if (cx.jQuery.trim(this.value).length < 3) {
                    return false;
                }
                cx.jQuery(this).addClass('loading');
            },
            open: function () {
                if (ajaxRequest) {
                    ajaxRequest.abort();
                }
                cx.jQuery(this).removeClass('loading');
            },
            select: function (event, ui) {
                if (ui.item.id !== 0) {

                    var formattedValue = (ui.item.value.length > 35)
                            ? ui.item.value.substring(0, 35) + '...'
                            : ui.item.value;

                    cx.jQuery('#news-related-news-list ul').append(
                        cx.jQuery('<li></li>').append(
                            cx.jQuery('<label>' + formattedValue + '</label>')
                        )
                        .append(
                            cx.jQuery('<span class="news_list_close">X</span>')
                        )
                        .append(
                            cx.jQuery('<input type="hidden" class="relationNewsId" name="newsRelatedNews[]" value=' + ui.item.id + '>')
                        )
                    );
                    if (!cx.jQuery('#news-related-news-list').is(':visible')) {
                        cx.jQuery('#news-related-news-list').slideDown();
                    }
                    // The Related news id is added the related news list
                    relatedNewsIdList.push(ui.item.id);
                }
                // Must be reset additionally
                ui.item.value = '';
            }
        })
    }
});

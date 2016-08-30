cx.ready(function () {
    if ($J(".live-search-news").length > 0) {

        if ($J('#related-news-list ul li').length == 0) {
            $J('#related-news-list').hide();
        } else {
            $J('#related-news-list').show();
        }

        var relatedNewsIdList = new Array(); //List of related news ids
        //Adding the already selected related news ids
        $J('#related-news-list .relationNewsId').each(function () {
            relatedNewsIdList.push($J(this).val());
        });
//      $J('.news_list_close').on('click', function(){
//Use the above line when the jquery version is 1.7 or higher instead of the next line
        $J('.news_list_close').live('click', function () {
            /**
             * While removing the news relationship the id is removed from
             * the related news list
             *
             * @type @call;$J@call;parent@call;find@call;val
             */
            var selectedId = $J(this).closest('li').find('.relationNewsId').val();
            relatedNewsIdList.splice(relatedNewsIdList.indexOf(selectedId), 1);

            // If all the related news are removed then the row is hided
            $J(this).closest('li').remove();
            if ($J('#related-news-list ul li').length == 0) {
                $J('#related-news-list').hide();
            }
        });

        var noResultsMsg = cx.variables.get("noResultsMsg", 'news/news-live-search');
        var ajaxRequest  = false;

        $J('.live-search-news-input').keyup(function(e){
            if(ajaxRequest) {
                ajaxRequest.abort();
                $J(this).removeClass('loading');
            }
        });
        $J('.live-search-news-input').autocomplete({
            delay: 500,
            source: function (request, response) {
                var id      = $J('#newsId').val();
                var langId  = cx.variables.get("langId", 'news/news-live-search');
                var cadminPath  = cx.variables.get("cadminPath", 'contrexx');
                ajaxRequest = $J.getJSON(cadminPath + 'index.php?cmd=JsonData&object=News&act=getAllNews&id='+ id +'&langId='+ langId, {
                    term: request.term
                },
                function (res) {
                    var newsList = new Array();
                    if (res.status == 'success') {
                        $J.each(res.data, function(id, value) {
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
                });
            },
            search: function () {
                if (ajaxRequest) {
                    ajaxRequest.abort();
                }
                $J(this).removeClass('loading');
                if ($J.trim(this.value).length < 3) {
                    return false;
                }
                $J(this).addClass('loading');
            },
            open: function () {
                if (ajaxRequest) {
                    ajaxRequest.abort();
                }
                $J(this).removeClass('loading');
            },
            select: function (event, ui) {
                if (ui.item.id !== 0) {

                    var formattedValue = (ui.item.value.length > 35)
                            ? ui.item.value.substring(0, 35) + '...'
                            : ui.item.value;

                    $J('#related-news-list ul').append('\
                    <li>\n\
                        <label>' + formattedValue + '</label>\n\
                        <span class="news_list_close">X</span>\n\
                        <input type=hidden class="relationNewsId" name="relatedNews[]" value=' + ui.item.id + '>\n\
                    </li>'
                            );
                    if (!$J('#related-news-list').is(':visible')) {
                        $J('#related-news-list').slideDown();
                    }
                    // The Related news id is added the related news list
                    relatedNewsIdList.push(ui.item.id);
                }
                // Must be reset additionally
                ui.item.value = "";
            }
        })
        .data("autocomplete")._renderItem = function (ul, item) {
            var highLightedText = String(item.value).replace(
                    new RegExp(this.term, "gi"),
                    "<span class='ui-state-highlight'>$&</span>");
            var formattedText = ((item.id === 0)
                    ? "<span class='no-results'>" + highLightedText + "</span>"
                    : "<a>" + highLightedText + "</a>");
            return $J("<li></li>")
                    .data("item.autocomplete", item)
                    .append(formattedText)
                    .appendTo(ul);
        };

    }
});

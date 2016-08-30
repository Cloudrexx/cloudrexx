if (document.body) {
  document.write('<div id="news_rss_feeds"></div>');
}
fnWinOnload = window.onload;
window.onload = function() {
    if (typeof(fnWinOnload) != 'undefined' && fnWinOnload != null) {
        fnWinOnload();
    }

    var rssFeedNews = new Array();rssFeedNews[0] = new Array();
rssFeedNews[0]['title'] = 'test';
rssFeedNews[0]['link'] = 'http://staging.contrexxlabs.com/contrexx/trunk/de/index.php?section=News&amp;cmd=details&amp;newsid=6&amp;teaserId=';
rssFeedNews[0]['date'] = '18.07.2013';
rssFeedNews[1] = new Array();
rssFeedNews[1]['title'] = 'MaxMuster AG begr\u00FCsst neue Mitarbeiterin';
rssFeedNews[1]['link'] = 'http://staging.contrexxlabs.com/contrexx/trunk/de/index.php?section=News&amp;cmd=details&amp;newsid=5&amp;teaserId=';
rssFeedNews[1]['date'] = '29.09.2012';
rssFeedNews[2] = new Array();
rssFeedNews[2]['title'] = 'Wir haben ein Contrexx CMS';
rssFeedNews[2]['link'] = 'http://staging.contrexxlabs.com/contrexx/trunk/de/index.php?section=News&amp;cmd=details&amp;newsid=4&amp;teaserId=';
rssFeedNews[2]['date'] = '28.09.2012';
rssFeedNews[3] = new Array();
rssFeedNews[3]['title'] = 'Neue Webseite online';
rssFeedNews[3]['link'] = 'http://staging.contrexxlabs.com/contrexx/trunk/de/index.php?section=News&amp;cmd=details&amp;newsid=1&amp;teaserId=';
rssFeedNews[3]['date'] = '27.09.2012';
if (typeof rssFeedFontColor != "string") {
    rssFeedFontColor = "";
} else {
    rssFeedFontColor = "color:"+rssFeedFontColor+";";
}
if (typeof rssFeedFontSize != "number") {
    rssFeedFontSize = "";
} else {
    rssFeedFontSize = "font-size:"+rssFeedFontSize+";";
}
if (typeof rssFeedTarget != "string") {
    rssFeedTarget = "target=\"_blank\"";;
} else {
    rssFeedTarget = "target=\""+rssFeedTarget+"\"";
}
if (typeof rssFeedFont != "string") {
    rssFeedFont = "";
} else {
    rssFeedFont = "font-family:"+rssFeedFont+";";
}
if (typeof rssFeedShowDate != "boolean") {
    rssFeedShowDate = false;
}

if (typeof rssFeedFontColor == "string" || typeof rssFeedFontSize != "number" || typeof rssFeedFont != "string") {
    style = 'style="'+rssFeedFontColor+rssFeedFontSize+rssFeedFont+'"';
}

if (typeof rssFeedLimit != 'number') {
    rssFeedLimit = 10;
}
if (rssFeedNews.length < rssFeedLimit) {
    rssFeedLimit = rssFeedNews.length;
}

    rssFeedContainer = document.getElementById('news_rss_feeds');
    rssFeedContainer.innerHTML = '';

var rssFeedNewsDate = "";
for (nr = 0; nr < rssFeedLimit; nr++) {
    if (rssFeedShowDate) {
        rssFeedNewsDate = rssFeedNews[nr]['date'];
    }
        rssCode = '<a href="'+rssFeedNews[nr]['link']+'" '+rssFeedTarget+' '+style+'>'+rssFeedNewsDate+' '+rssFeedNews[nr]['title']+'</a><br />';
        rssFeedContainer.innerHTML += rssCode;
    }
}

<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

define('UPDATE_PATH', dirname(__FILE__));@include_once(UPDATE_PATH.'/../config/configuration.php');@header('content-type: text/html; charset='.(UPDATE_UTF8 ? 'utf-8' : 'iso-8859-1'));?>

var request_active = false;
var getDebugInfo = false;

if (typeof(DOMParser) == 'undefined') {
  useAjax = false;
} else {
  useAjax = true;
}

function debugUpdate()
{
    doUpdate(false, false, true)
}

function doUpdate(goBack, viaPost, debug, timeout)
{
    getDebugInfo = debug;
    type = viaPost ? 'POST' : 'GET';
    var inputTimeout = $J("input#checkTimeout").length;

    if (useAjax) {
        if (request_active) {
            return false;
        } else {
            request_active = true;
        }

        formData = getFormData(goBack);

        if ($J("#processUpdate").length || $J("#doGroup").length || inputTimeout || timeout) {
            if (!getDebugInfo) {
                setContent('<div style="margin: 180px 0 0 155px;">Bitte haben Sie einen Moment Geduld.<br /><?php $txt = 'Das Update wird durchgeführt...';print UPDATE_UTF8 ? $txt : utf8_decode($txt);?><br /><br /><img src="template/contrexx/images/content/loading_animation.gif" width="208" height="13" alt="" /></div>');
                setNavigation('');
            }
        } else {
            setContent('Bitte warten. Die Seite wird geladen...');
        }

        if (viaPost) {
            $J(".content-migration-dialog").remove();
            $J(".content-migration-info").parent().remove();
        }

        $J("#wrapper-bottom-left").empty();

        if (inputTimeout) {
            checkTimeout();
            return false;
        }

        jQuery.ajax({
            url: 'index.php',
            type: type,
            data: {'ajax': formData, 'debug_update': getDebugInfo},
            success: parseResponse,
            error: cxUpdateErrorHandler,
        });
        return false;
    }
    return true;
}

function cxUpdateErrorHandler(jqXHR, status)
{
    showErrorScreen();
    request_active = false;
}

function getFormData(goBack)
{
  oFormData = new Object;

  oElements = document.getElementById('wrapper').getElementsByTagName('input');
  if (oElements.length > 0) {
    for (i = 0;i < oElements.length; i++) {
      if (document.getElementsByName(oElements[i].name).length > 1) {
        if (typeof(oFormData[oElements[i].name]) == 'undefined') {
          oFormData[oElements[i].name] = new Array();
        }
        if (oElements[i].value != '' && oElements[i].type != 'radio' && (oElements[i].type != 'checkbox' || oElements[i].checked == true)
        ) {
          oFormData[oElements[i].name].push(oElements[i].value);
        } else if (oElements[i].type == 'radio' && oElements[i].checked == true) {
          oFormData[oElements[i].name] = oElements[i].value;
        }
      } else {
        if (!goBack || oElements[i].name != 'updateNext') {
          if (oElements[i].type != 'radio' && oElements[i].type != 'checkbox' || oElements[i].checked == true) {
            oFormData[oElements[i].name] = oElements[i].value;
          }
        }
      }
    }
  }

  oElements = document.getElementById('wrapper').getElementsByTagName('select');
  if (oElements.length > 0) {
    for (i = 0;i < oElements.length; i++) {
      if (oElements[i].name.search('\[[0-9]+\]$') >= 0) {
        if (typeof(oFormData[oElements[i].name.substr(0,oElements[i].name.search('\[[0-9]+\]$'))]) == 'undefined') {
          oFormData[oElements[i].name.substr(0,oElements[i].name.search('\[[0-9]+\]$'))] = new Array();
        }
        oFormData[oElements[i].name.substr(0,oElements[i].name.search('\[[0-9]+\]$'))][oElements[i].name.substr(oElements[i].name.search('\[[0-9]+\]$')+1,oElements[i].name.match('\[[0-9]+\]$')[0].length-2)] = oElements[i].value;
      } else {
        oFormData[oElements[i].name] = oElements[i].value;
      }
    }
  }

  aFormData = new Array();
  for (i in oFormData) {
    aFormData.push(i+':'+((typeof(oFormData[i]) == 'object') ? '["'+oFormData[i].join('","')+'"]' : '"'+oFormData[i]+'"'));
  }

  var doGroup          = $J("#doGroup").length              ? ",doGroup:"            + $J("#doGroup").val()              : "";
  var pgUsername       = $J("#pgUsername").length           ? ",pgUsername:\""       + $J("#pgUsername").val() + "\""    : "";
  var pgPassword       = $J("#pgPassword").length           ? ",pgPassword:\""       + $J("#pgPassword").val() + "\""    : "";
  var pgCmsVersion     = $J("#pgCmsVersion").length         ? ",pgCmsVersion:\""     + $J("#pgCmsVersion").val() + "\""  : "";
  var pgMigrateLangIds = $J("#pgMigrateLangIds").length     ? ",pgMigrateLangIds:\"" + $J("#pgMigrateLangIds").val() + "\""  : "";
  var similarPages     = $J("#similarPages").length         ? ",similarPages:"       + $J("#similarPages").val()         : "";
  var removePages      = $J("#removePages").length          ? ",removePages:"        + $J("#removePages").val()          : "";
  var delInAcLangs     = $J("#delInAcLangs:checked").length ? ",delInAcLangs:"       + $J("#delInAcLangs:checked").val() : "";

  var parameters = doGroup + pgUsername + pgPassword + pgCmsVersion + pgMigrateLangIds + similarPages + removePages + delInAcLangs;

  return '{' + aFormData.join(',') + parameters + '}';
}

function parseResponse(response)
{
    if (response.length > 0) {
      try {
        eval('oResponse='+response);
        if (oResponse.time) {
            return true;
        }
        if (oResponse.dialog) {
            similarPages = oResponse.dialog.similarPages;

            setContent('<div style="margin: 180px 0 0 155px;">Bitte haben Sie einen Moment Geduld.<br /><?php $txt = 'Das Update wird durchgeführt...';print UPDATE_UTF8 ? $txt : utf8_decode($txt);?><br /><br /><img src="template/contrexx/images/content/loading_animation.gif" width="208" height="13" alt="" /></div>');
            setNavigation('');

            cx.ui.dialog({
                width:         1020,
                height:        830,
                modal:         true,
                closeOnEscape: false,
                dialogClass:   "content-migration-dialog",
                title:         "Inhaltsseiten gruppieren",
                content:       oResponse.content,

                close: function() {
                    executeGrouping();
                },
                buttons: {
                    "Seitenstruktur übernehmen": function() {
                        $J(this).dialog("close");
                    }
                }
            });
        } else if (oResponse.timeout) {
            request_active = false;
            doUpdate(false, true, false, true);
        } else {
            jQuery('#similarPages').remove();
            setContent(oResponse.content);
            setNavigation(oResponse.navigation);
            setLogout(oResponse.logout);
        }
        cx.ui.tooltip();
      } catch(e) {
        if (getDebugInfo) {
            jQuery('#debug_message').text(response).html();
            jQuery('#debug_message').show();
            jQuery('#debug_update').remove();
        } else {
            showErrorScreen();
        }
      }
    } else {
        showErrorScreen();
    }
    request_active = false;
}

function showErrorScreen()
{
    setContent('<?php $txt = '<div id="content-overview"><h1 class="first">Fehler beim Update...</h1><div class="message-alert">Das Update-Script gibt keine Antwort zurück!</div><div class="message-warning" id="debug_message"></div><input type="button" value="Fehler anzeigen..." id="debug_update" onclick="debugUpdate()" /></div>';print UPDATE_UTF8 ? $txt : utf8_encode($txt); ?>');
    //setContent('<?php $txt = '<div class="message-alert">Das Update-Script gibt keine Antwort zurück!</div>';print UPDATE_UTF8 ? $txt : utf8_encode($txt); ?>');
    setNavigation('<input type="submit" value="<?php $txt = 'Erneut versuchen...';print UPDATE_UTF8 ? utf8_encode($txt) : $txt;?>" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />');
}

function setContent(content)
{
  setHtml(content, 'wrapper-content');
}

function setLogout(logout)
{
  setHtml(logout, 'wrapper-bottom-left');
}

function setNavigation(navigation)
{
  setHtml(navigation, 'wrapper-bottom-right');
}

function setHtml(text, element)
{
  if (text.length > 0) {
    try {
      if (html2dom.getDOM(text, element) !== false) {
        document.getElementById(element).innerHTML = '';
        eval(html2dom.result);
      } else {
        throw 'error';
      }
    } catch(e) {
      document.getElementById(element).innerHTML = 'HTML-Code konnte nicht Interpretiert werden:<br /><br />';
      document.getElementById(element).innerHTML += text;
    }
  } else {
    document.getElementById(element).innerHTML = '';
  }
}

var similarPages = $J.parseJSON(cx.variables.get('similarPages', 'update/contentMigration'));
var removePages  = new Array();

$J(document).ready(function() {
    $J("body").delegate(".page-grouping-title", "click", function() {
        $J(this).toggleClass("open");
        $J(this).next().slideToggle(200);
    });

    $J("body").delegate(".page-grouping-page", "click", function() {
        var hasClassActive = $J(this).hasClass("active");
        $J(this).parent().children(".active").removeClass("active");
        if (!hasClassActive) {
            $J(this).addClass("active");
        }

        var addOrRemove = $J(".page-grouping-page.active").length ? false : true;
        $J(".page-grouping-buttons > .page-grouping-button").toggleClass("disabled", addOrRemove);
    });

    $J("body").delegate(".page-grouping-button-delete:not(.disabled)", "click", function() {
        $J(".page-grouping-buttons > .page-grouping-button").addClass("disabled");
        $J(".page-grouping-page.active").each(function() {
            removePages.push(parseInt($J(this).data("id")));
            var marginLeft = (parseInt($J(this).data("level")) - 1) * 15;
            $J(".page-grouping-removed-pages").append(
                "<div class=\"page-grouping-removed-page\" data-id=\"" + $J(this).data("id") + "\" data-lang=\"" + $J(this).data("lang") + "\" style=\"margin-left: " + marginLeft + "px;\">" +
                    "<div class=\"page-grouping-removed-page-restore\"></div>" +
                    $J.trim($J(this).text()) + " (" + $J(this).data("lang") + ")" +
                "</div>"
            );
            $J(this).addClass("removed").removeClass("active");
        });
        $J(".page-grouping-show-removed-pages").fadeIn(200);
    });

    $J("body").delegate(".page-grouping-show-removed-pages", "click", function() {
        $J(this).toggleClass("open").children(".page-grouping-removed-pages").slideToggle(200);
    });

    $J("body").delegate(".page-grouping-removed-pages", "click", function(event) {
        event.stopPropagation();
    });

    $J("body").delegate(".page-grouping-removed-page-restore", "click", function() {
        var pageId = parseInt($J(this).parent().data("id"));
        var index  = removePages.indexOf(pageId);
        removePages.splice(index, 1);

        var objRestoredPage = $J(".page-grouping-page[data-id=" + pageId + "]");
        objRestoredPage.addClass("inserted").removeClass("removed").animate({
            "background-color": "#FCFCFC"
        }, 3000, function() {
            $J(this).removeClass("inserted").css("background-color", "")
        });
        var scrollTop = objRestoredPage.position().top - 112.5;
        $J(".page-grouping-language[data-lang=" + $J(this).parent().data("lang") + "]").children(".page-grouping-pages-scroll").animate({
            scrollTop: scrollTop
        }, 200);

        $J(this).parent().remove();
        if (!$J(".page-grouping-removed-pages").children().length) {
            $J(".page-grouping-show-removed-pages").fadeOut(400, function() {
                $J(this).removeClass("open").children(".page-grouping-removed-pages").hide();
            });
        }
    });

    $J("body").delegate(".page-grouping-ungroup", "click", function() {
        var objNode = $J(this).parent();
        objNode.stop();
        delete similarPages[parseInt(objNode.data("id"))];

        $J(this).nextAll().each(function() {
            if ($J(this).data("id")) {
                var objUngroupedPage = $J(".page-grouping-page[data-id=" + $J(this).data("id") + "]");
                objUngroupedPage.addClass("inserted").removeClass("grouped").animate({
                    "background-color": "#F6F6F6"
                }, 2000, "swing", function() {
                    $J(this).removeClass("inserted").css("background-color", "");
                });
                var scrollTop = objUngroupedPage.position().top - 112.5;
                $J(".page-grouping-language[data-lang=" + $J(this).data("lang") + "]").children(".page-grouping-pages-scroll").animate({
                    scrollTop: scrollTop
                }, 200);
            }
        });

        objNode.remove();
    });

    $J("body").delegate(".page-grouping-button-group:not(.disabled)", "click", function() {
        $J(".page-grouping-page.active").stop().addClass("grouped");
        $J(".page-grouping-buttons > .page-grouping-button").addClass("disabled");

        var groupedNode  = "";
        var groupedPages = "";
        var nodeCreated  = false;
        var countLangs   = $J(".page-grouping-language").length;
        var borderWidth  = parseInt($J(".page-grouping-grouped-border").css("width")) - 2;
        var width        = (borderWidth - 10) / countLangs;
        var nodeId       = 0;
        var nodeSort     = 0;
        var nextPagesToSelect = new Array();

        for (var i = 0; i < countLangs; i++) {
            var lang = $J(".page-grouping-language").slice(i, i + 1).data("lang");
            var page = $J(".page-grouping-language").slice(i, i + 1).find(".page-grouping-page.active");

            var pageWidth = width;
            if (i === 0) {
                pageWidth = width - 24;
            } else if (i === (countLangs - 1)) {
                pageWidth = width - nodeMargin;
            }

            if (page.length) {
                var nodeMargin = (page.data("level") - 1) * 15;
                var nodeWidth  = borderWidth - nodeMargin;

                if (!nodeCreated) {
                    nodeId       = parseInt(page.data("node"));
                    nodeSort     = parseInt(page.data("sort"));
                    groupedNode += "<div class=\"page-grouping-grouped-node\" data-id=\"" + nodeId + "\" data-level=\"" + page.data("level") + "\" data-sort=\"" + nodeSort + "\" style=\"width: " + nodeWidth + "px; margin-left: " + nodeMargin + "px;\">";
                    groupedNode += "<div class=\"page-grouping-ungroup\">×</div>";
                    nodeCreated  = true;
                }
                groupedPages += "<div class=\"page-grouping-grouped-page\" data-id=\"" + page.data("id") + "\" data-lang=\"" + page.data("lang") + "\" style=\"width: " + pageWidth + "px;\">" + $J.trim(page.text()) + " (" + page.data("lang") + ")</div>";

                nextPagesInLine = $J(".page-grouping-language").slice(i, i + 1).find(".page-grouping-page.active").nextUntil(':not(.grouped)', '.page-grouping-page');
                if (nextPagesInLine.length) {
                    nextPageInLine = nextPagesInLine.next('.page-grouping-page:not(.grouped)');
                } else {
                    nextPageInLine = $J(".page-grouping-language").slice(i, i + 1).find(".page-grouping-page.active").next('.page-grouping-page:not(.grouped)');
                }

                if (nextPageInLine.length) {
                    nextPagesToSelect.push(nextPageInLine);
                }
            } else {
                groupedPages += "<div class=\"page-grouping-grouped-page no-page\" style=\"width: " + pageWidth + "px;\">Keine Seite (" + lang + ")</div>";
            }
        }
        groupedNode += groupedPages;
        groupedNode += '</div>';

        if (nodeId) {
            similarPages[nodeId] = new Array();
            $J(".page-grouping-page.active").each(function() {
                similarPages[nodeId].push(parseInt($J(this).data("id")));
            });
        }

        $J(".page-grouping-page.active").removeClass("active");
        var arrSort = new Array();
        $J(".page-grouping-grouped-node").each(function() {
            arrSort.push($J(this).data("sort"));
        });
        arrSort.push(nodeSort);
        arrSort.sort(function(a, b) {
            return a - b;
        });
        if (arrSort.indexOf(nodeSort) === 0) {
            $J(".page-grouping-grouped-border").prepend(groupedNode);
        } else {
            var indexOfPrevElement = arrSort.indexOf(nodeSort) - 1;
            var prevElementSort    = arrSort[indexOfPrevElement];
            var objPrevElement     = $J(".page-grouping-grouped-node[data-sort=" + prevElementSort + "]");
            objPrevElement.after(groupedNode);
        }

        $J(nextPagesToSelect).each(function() {
            $J(this).addClass("active");
        });

        if ($J(".page-grouping-page.active").length) {
            $J(".page-grouping-buttons > .page-grouping-button").removeClass("disabled");
        }

        var objInsertedNode = $J(".page-grouping-grouped-node[data-sort=" + nodeSort + "]");
        objInsertedNode.addClass("inserted").animate({
            "background-color": "#FCFCFC"
        }, 3000, function() {
            $J(this).removeClass("inserted").css("background-color", "");
        });
        var scrollTop = objInsertedNode.position().top - 112;
        $J(".page-grouping-grouped-scroll-y").animate({
            scrollTop: scrollTop
        }, 200);
    });
});

var executeGrouping = function() {
    $J('#doGroup').val(1);
    $J('#similarPages').val(JSON.stringify(similarPages));
    $J('#removePages').val(JSON.stringify(removePages));
    doUpdate(false, true);
}

var checkTimeout = function() {
    var startTime = new Date();
    $J.ajax({
        url: 'index.php',
        type: 'GET',
        async: true,
        data: {'check_timeout': 'true'},
        statusCode: {
            500: function() {
                var endTime = new Date();
                var executionTime = parseInt((endTime - startTime) / 1000);
                var ajax = '{execution_time: "' + parseInt(executionTime - 5) + '"}';
                $J.ajax({
                    url: 'index.php',
                    type: 'POST',
                    async: false,
                    data: {'ajax': ajax},
                    success: parseResponse
                });
                if (executionTime >= 20) {
                    $J.ajax({
                        url: 'index.php',
                        type: type,
                        data: {'ajax': formData, 'debug_update': getDebugInfo},
                        success: parseResponse,
                        error: cxUpdateErrorHandler,
                    });
                    request_active = false;
                }
            }
        },
        complete: function(jqXHR, textStatus) {
            if (request_active) {
                // I have to do the request here, otherwise the update will loop and only check the timeout
                $J.ajax({
                    url: 'index.php',
                    type: type,
                    data: {'ajax': formData, 'debug_update': getDebugInfo},
                    success: parseResponse,
                    error: cxUpdateErrorHandler,
                });
            }
            request_active = false;
        }
    });
}

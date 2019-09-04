var baseUrl = 'index.php?cmd=ContentManager';
var regExpUriProtocol = new RegExp(cx.variables.get('regExpUriProtocol', 'contentmanager'));
var firstLoad = true;

var mouseIsUp = true;
cx.jQuery(document).bind('mouseup.global', function() {
    mouseIsUp = true;
}).bind('mousedown.global', function() {
    mouseIsUp = false;
});

//called from links in history table.
loadHistoryVersion = function(version) {
    pageId = parseInt(cx.jQuery('#pageId').val());
    if (isNaN(pageId)) {
        return;
    }

    cx.cm.loadPage(pageId, 0, version, "content", false);
};

cx.jQuery.extend({
    getUrlVars: function(){
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for(var i = 0; i < hashes.length; i++)
        {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    },
    getUrlVar: function(name){
        try {
            return cx.jQuery.getUrlVars()[name];
        } catch (ex) {
            return undefined;
        }
    },
    ucfirst: function(string) {
        return (string.charAt(0).toUpperCase() + string.substr(1));
    },
    lcfirst: function(string) {
        return (string.charAt(0).toLowerCase() + string.substr(1));
    }
});

cx.jQuery.fn.equals = function(compareTo) {
    if (!compareTo || !compareTo.length || this.length!=compareTo.length) {
        return false;
    }
    for (var i=0; i<this.length; i++) {
        if (this[i]!==compareTo[i]) {
            return false;
        }
    }
    return true;
}

var arrayContains = function(array, value) {
    if (array == null) return false;
    for (var i = 0; i < array.length; i++) {
        if (array[i] == value) return true;
    }
    return false;
};

var fillBlockSelect = function(select, data) {
    select.empty();
    cx.jQuery.each(data.groups, function(group, id) {
        var selected = arrayContains(data.assignedGroups, group);
        var option = cx.jQuery('<option></option>');
        option.html(id.name);
        option.val(group);
        if (selected || id.selected)
            option.attr('selected','selected');
        if (id.disabled)
            option.attr('disabled','disabled');

        option.appendTo(select);
    });
    initMultiSelect(select);
};

var fillSelect = function(select, data) {
    select.empty();
    cx.jQuery.each(data.groups, function(group, id) {
        var selected = arrayContains(data.assignedGroups, group);
        var option = cx.jQuery('<option></option>');
        option.html(id);
        option.val(group);
        if (selected)
            option.attr('selected','selected');

        option.appendTo(select);
    });
    initMultiSelect(select);
};

var initMultiSelect = function(select) {
    select.multiselect2side({
        selectedPosition: 'right',
        moveOptions: false,
        labelsx: '',
        labeldx: '',
        autoSort: true,
        autoSortAvailable: true
    });

    // workaround for multiselect bug, disabled options are also moved to other select after click on removeAll()
    var allSel = select.next().children(".ms2side__select").children("select");
    var  leftSel = allSel.eq(0);
    var  rightSel = allSel.eq(1);
    leftSel.change(function() {
        // move all disabled options from left to right select
        leftSel.children('option[disabled]').remove().appendTo(rightSel);
    });
};

reloadCustomContentTemplates = function() {
    var skinId = cx.jQuery('#page select[name="page[skin]"]').val();
    var module = cx.jQuery('#page select[name="page[application]"]').val();
    var select = cx.jQuery('#page select[name="page[customContent]"]');
    var lastChoice = select.data('sel');
    select.empty();
    select.append(cx.jQuery("<option value=\"\" selected=\"selected\">(Default)</option>"));

    cx.jQuery('#page select[name="page[customContent]"]').trigger('change');

    // Default skin
    if (skinId == 0) {
        skinId = cx.variables.get('defaultTemplates', 'contentmanager/themes')[cx.cm.getCurrentLang()];
    }

    var templates = cx.variables.get('contentTemplates', "contentmanager");
    if (templates[skinId] == undefined) {
        return;
    }

    for (var i = 0; i < templates[skinId].length; i++) {
        var isHome = /^home_/.exec(templates[skinId][i]);
        if ((isHome && module == "Home") || !isHome && module != "Home") {
            select.append(cx.jQuery('<option>', {
                value : templates[skinId][i]
            }).text(templates[skinId][i]));
        }
    }
};

cx.ready(function() {
    // wheter expand all was once called on the tree
    cx.cm.all_opened = false;
    // are we opening all nodes at the moment?
    cx.cm.is_opening = false;
    // initialise the page skin
    cx.cm.pageSkin = 0;
    // initialise the page custom template
    cx.cm.pageContentTemplate = '';
    // initialise the page custom application template
    cx.cm.pageApplicationTemplate = '';
    // this is true between the events loadingStart and loadingEnd
    cx.cm.isAdjusting = true;

    // Disable the option use for all channels by default
    cx.jQuery('input[name="page[useSkinForAllChannels]"], input[name="page[useCustomContentForAllChannels]"], input[name="page[useCustomApplicationTemplateForAllChannels]"]').attr('disabled', 'disabled');

    setWebPageUrlCallback = function(data) {
      if(data.type=="page") {
        var url  = data.data[0].node;
        var path = data.data[0].url;

        url = url.replace(cx.variables.get('contrexxPathOffset', 'contentmanager'), '');
        if (path == '') {
            path = url;
        }
        if (path[0] == '/') {
            path = path.substr(1);
        }
        var lang = cx.variables.get('language', 'contrexx');
        cx.jQuery('#page_target_wrapper').hide();
        cx.jQuery('.page_target_text').text(cx.variables.get('contrexxBaseUrl', 'contentmanager') + lang + '/' + path).attr('href', function() {return cx.jQuery(this).text()});
        cx.jQuery('.page_target_text_wrapper').show();
        cx.jQuery('#page_target_protocol > option').removeAttr('selected');
        cx.jQuery('#page_target_protocol > option[value=""]').attr("selected", "selected");
        cx.jQuery('#page_target, .page_target_backup').val(url);
      }
      else if (data.type == "file") {
        cx.jQuery('#page_target_wrapper').hide();
        cx.jQuery('.page_target_text').text(cx.variables.get('contrexxBaseUrl', 'contentmanager') + data.data[0].datainfo.filepath.substr(1)).attr('href', function() {return cx.jQuery(this).text()});
        cx.jQuery('.page_target_text_wrapper').show();
        cx.jQuery('#page_target_protocol > option').removeAttr('selected');
        cx.jQuery('#page_target_protocol > option[value=""]').attr("selected", "selected");
        cx.jQuery('#page_target, .page_target_backup').val(data.data[0].datainfo.filepath);
      }
    }

    cx.jQuery('#page_target').keyup(function() {
        var targetValue = cx.jQuery.trim(cx.jQuery(this).val());
        if (cx.jQuery(this).val() != targetValue) {
            cx.jQuery(this).val(targetValue);
        }
        var targetValueBackup = cx.jQuery('.page_target_backup').val();
        var matchesPageTarget = regExpUriProtocol.exec(targetValueBackup);
        if (matchesPageTarget) {
            targetValueBackup = targetValueBackup.replace(matchesPageTarget[0], '');
        }
        var showOrHide = true;
        if ((targetValue == '') || (targetValue == targetValueBackup)) {
            showOrHide = false;
        }
        cx.jQuery('#page_target_check').toggle(showOrHide);
    });
    cx.jQuery('#page_target_protocol').change(function() {
        var targetValueBackup    = cx.jQuery('.page_target_backup').val();
        var matchesPageTarget    = regExpUriProtocol.exec(targetValueBackup);
        var targetProtocolBackup = '';
        if (matchesPageTarget) {
            targetProtocolBackup = matchesPageTarget[0];
        }
        var showOrHide = true;
        if (cx.jQuery(this).val() == targetProtocolBackup) {
            showOrHide = false;
        }
        cx.jQuery('#page_target_check').toggle(showOrHide);
    });
    cx.jQuery('#page_target_edit').click(function() {
        cx.jQuery('#page_target_cancel').show();
        cx.jQuery('#page_target_text_wrapper_redirect').hide().prev().show();
    });
    cx.jQuery('#page_target_cancel').click(function() {
        cx.cm.setPageTarget(
            cx.jQuery(".page_target_backup").val(),
            cx.jQuery(this).parent().next(".page_target_text_wrapper").find(".page_target_text").text()
        );
    });
    cx.jQuery('#page_target_check').click(function() {
        cx.jQuery(this).hide();
        cx.jQuery('.page_target_text').text('');
        cx.jQuery('.page_target_backup').val(cx.jQuery('#page_target_protocol').val() + cx.jQuery('#page_target').val());
        cx.jQuery.getJSON('index.php?cmd=JsonData&object=page&act=getPathByTarget', {
            target: cx.jQuery('.page_target_backup').val()
        }, function(data) {
            cx.jQuery('.page_target_text').text(data.data).attr('href', function() {return cx.jQuery(this).text()});
        });
        cx.jQuery('#page_target_wrapper').hide().next().show();
    });

    cx.jQuery('.jstree-action').click(function(event) {
        event.preventDefault();
        action = cx.jQuery(this).attr('class').split(' ')[1];
        if (action == "open" && !cx.cm.all_opened) {
            // no need to get the whole tree twice
            cx.cm.all_opened = true;
            cx.cm.is_opening = true;
            cx.ui.messages.showLoad();
            cx.jQuery("#site-tree").hide();
            // get complete tree
            cx.trigger("loadingStart", "contentmanager", {});
            cx.jQuery.ajax({
                url: "?cmd=JsonData&object=node&act=getTree&recursive=true",
                dataType: 'json',
                success: function(response) {
                    if (!response.data) {
                        return;
                    }
                    if (cx.cm.actions == undefined) {
                        cx.cm.actions = response.data.actions;
                    } else {
                        cx.jQuery(languages).each(function(index, lang) {
                            cx.jQuery.extend(cx.cm.actions[lang], response.data.actions[lang]);
                        });
                    }
                    // add tree data to jstree (replace tree by new one) and open all nodes
                    cx.cm.createJsTree(cx.jQuery("#site-tree"), response.data.tree, response.data.nodeLevels, true);
                    cx.trigger("loadingEnd", "contentmanager", {});
                }
            });
        } else {
            cx.jQuery('#site-tree').jstree(action+'_all');
        }
    });

    cx.jQuery('#multiple-actions-select').change(function() {
        data = new Object();
        data.action = cx.jQuery(this).children('option:selected').val();
        if (data.action == '0') {
            return false;
        }
        data.lang  = cx.jQuery('#site-tree').jstree('get_lang');
        data.nodes = new Array();
        cx.jQuery('#site-tree ul li.jstree-checked:not(.action-item):not(.translation)').each(function() {
            nodeId = cx.jQuery(this).attr('id').match(/\d+$/)[0];
            data.nodes.push(nodeId);
        });
        data.currentNodeId = cx.jQuery('input#pageNode').val();
        if ((data.action != '') && (data.lang != '') && (data.nodes.length > 0)) {
            object = (data.action == 'delete') ? 'node'   : 'page';
            act    = (data.action == 'delete') ? 'Delete' : 'Set';
            var recursive = false;
            if (data.action == 'delete') {
                if (!cx.cm.confirmDeleteNode()) return;
            } else {
                recursive = cx.cm.askRecursive();
            }
            if (recursive) {
                recursive = "&recursive=true";
            } else {
                recursive = "";
            }
            var multipleActionAjaxRequest = function(offset) {
                if (offset) {
                    offset = "&offset=" + offset;
                } else {
                    offset = "";
                }
                cx.trigger("loadingStart", "contentmanager", {});
                cx.jQuery.ajax({
                    type: 'POST',
                    url:  'index.php?cmd=JsonData&object='+object+'&act=multiple'+act+recursive+offset,
                    data: data,
                    success: function(json) {
                        if (json.state && json.state == 'timeout') {
                            multipleActionAjaxRequest(json.offset);
                            return;
                        }
                        if (json.message) {
                            cx.tools.StatusMessage.showMessage(json.message, null, 10000);
                        }
                        cx.jQuery('#multiple-actions-select').val(0);
                        cx.cm.createJsTree(cx.jQuery("#site-tree"), json.data.tree, json.data.nodeLevels, false);
                        if (json.data.action == 'delete') {
                            if (json.data.deletedCurrentPage) {
                                cx.cm.hideEditView();
                            }
                        } else {
                            if (json.data.id > 0) {
                                cx.cm.loadPage(json.data.id, undefined, undefined, undefined, false);
                            }
                        }
                        cx.trigger("loadingEnd", "contentmanager", {});
                    }
                });
            };
            multipleActionAjaxRequest();
        } else {
            cx.jQuery('#multiple-actions-select').val(0);
        }
    });

    // alias input fields
    cx.jQuery("div.page_alias input").keyup(function() {
        cx.jQuery("div.page_alias input.warning").removeClass("warning");

        var originalAlias  = cx.jQuery(this).val();
        var slugifiedAlias = cx.cm.slugify(originalAlias);
        if (originalAlias != slugifiedAlias) {
            cx.jQuery(this).val(slugifiedAlias);
        }
        // remove unused alias input fields
        // do not remove the last input field
        if (cx.jQuery(this).val() == "") {
            var emptyCount = 0;
            cx.jQuery("div.page_alias").each(function(index, el) {
                if (cx.jQuery(el).children("input").val() == "") {
                    emptyCount++;
                }
            });
            if (emptyCount > 1) {
                cx.jQuery(this).closest("div.page_alias").next("div.page_alias").children("#page_alias").focus();
                cx.jQuery(this).closest("div.page_alias").remove();
            }
        }
        // highlight same text
        cx.jQuery("div.page_alias").each(function(index, el) {
            var me = cx.jQuery(el);
            if (me.children("input").val() != "") {
                me.children("input").attr("id", "page_alias_" + index);
            }
            cx.jQuery("div.page_alias").each(function(index, el) {
                var it = cx.jQuery(el);
                if (me.get(0) == it.get(0)) {
                    return true;
                }
                if (me.children("input").val() == it.children("input").val()) {
                    me.children("input").addClass("warning");
                    it.children("input").addClass("warning");
                    return false;
                }
            });
        });
        // add new alias input fields
        if (cx.jQuery(this).val() != "") {
            var hasEmpty = false;
            cx.jQuery("div.page_alias").each(function(index, el) {
                if (cx.jQuery(el).children("input").val() == "") {
                    // there is already a empty field
                    hasEmpty = true;
                    return;
                }
            });
            if (!hasEmpty) {
                var parent = cx.jQuery(this).parent("div.page_alias");
                var clone = parent.clone(true);
                clone.children("input").val("");
                clone.children("input").attr("id", "page_alias");
                clone.insertAfter(parent);
            }
        }
    });

    cx.jQuery("#site-tree .translations-expanded ins").live("click", function(event) {
        if (cx.jQuery(this).is('ins.page')) {
            action = "hide";
            if (cx.jQuery(this).hasClass('invisible')) {
                action = "show";
            }
        } else {
            action = "deactivate";
            if (cx.jQuery(this).hasClass('unpublished')) {
                action = "activate";
            }
        }
        var node = cx.jQuery(this).closest("li[id^='node_']");
        var nodeId = node.attr("id").split("_")[1];
        var lang = cx.jQuery(this).parent().attr("class").split(" ")[1];
        var page = node.children("a."+lang);
        cx.cm.performAction(action, page.attr("id"), nodeId);
    });

    var data = cx.jQuery.parseJSON(cx.variables.get("tree-data", "contentmanager/tree"));
    cx.cm.actions = data.data.actions;
    cx.cm.hasHome = data.data.hasHome;
    cx.cm.createJsTree(cx.jQuery("#site-tree"), data.data.tree, data.data.nodeLevels);

    cx.jQuery(".chzn-select").chosen().change(function() {
        var str = "";
        cx.jQuery("select.chzn-select option:selected").each(function () {
            str += cx.jQuery(this).attr('value');
        });
        cx.cm.setCurrentLang(str);
        var dpOptions = {
            showSecond: false,
            dateFormat: 'dd.mm.yy',
            timeFormat: 'hh:mm'
        };
        cx.jQuery("input.date").datetimepicker(dpOptions);

        node = cx.jQuery('#page input[name="page[node]"]').val();
        //pageId = cx.jQuery('#node_'+node+" a."+str).attr("id");
        pageId = cx.jQuery('#pageId').val();

        // get translated page id (page->getNode()->getPage(lang)->getId())
        if (pageId) {
            pageId = cx.jQuery('li#node_'+node).children('.'+str).attr('id');
        }

        if (fallbacks[str]) {
            cx.jQuery('.hidable_nofallback').show();
            cx.jQuery('#fallback').text(language_labels[fallbacks[str]]);
        } else {
            cx.jQuery('.hidable_nofallback').hide();
        }
        if (pageId && pageId != "new") {
            cx.cm.loadPage(pageId, node);
        } else {
            cx.jQuery('#page input[name="source_page"]').val(cx.jQuery('#page input[name="page[id]"]').val());
            cx.jQuery('#page input[name="page[id]"]').val("new");
            cx.jQuery('#page input[name="page[lang]"]').val(str);
            cx.jQuery('#page input[name="page[node]"]').val(node);
            cx.jQuery('#page #preview').attr('href', cx.variables.get('basePath', 'contrexx') + str + '/index.php?pagePreview=1');
        }

        cx.jQuery('#site-tree>ul li .jstree-wrapper').each(function() {
            jsTreeLang = cx.jQuery('#site-tree').jstree('get_lang');
            var prevShown = cx.jQuery(this).children('.module.show, .preview.show, .lastupdate.show');
            prevShown.removeClass('show').addClass('hide');
            cx.jQuery(this).children('.module.'+jsTreeLang + ', .preview.'+jsTreeLang + ', .lastupdate.' + jsTreeLang)
              .css("left", function() {
                  return prevShown.filter("."+cx.jQuery(this).attr("class").split(" ")[0]).css("left");
              })
              .toggleClass('show hide');
        });
    });
    cx.jQuery(".chzn-select").trigger('change');
    cx.cm.updateLocaleSelect();

    //add callback to reload custom content templates available as soon as template or module changes
    cx.jQuery('#page select[name="page[skin]"]').bind('change', function() {
        if (parseInt(cx.jQuery(this).val()) == 0) {
            cx.jQuery('input[name="page[useSkinForAllChannels]"]').removeAttr('checked');
            cx.jQuery('input[name="page[useSkinForAllChannels]"]').attr('disabled', 'disabled');
        } else {
            if (parseInt(cx.cm.pageSkin) == 0) {
                cx.jQuery('input[name="page[useSkinForAllChannels]"]').attr('checked', 'checked');
            }
            cx.jQuery('input[name="page[useSkinForAllChannels]"]').removeAttr('disabled');
        }

        cx.cm.pageSkin = cx.jQuery(this).val();

        reloadCustomContentTemplates();
        cx.cm.loadApplicationTemplate(cx.jQuery('#page select[name="page[application]"]').val(),
                                      cx.jQuery('#page input[name="page[area]"]').val(),
                                      cx.jQuery('#page select[name="page[skin]"]').val());
    });

    cx.jQuery('#page select[name="page[customContent]"]').bind('change', function() {
        if (cx.jQuery(this).val() == '') {
            cx.jQuery('input[name="page[useCustomContentForAllChannels]"]').removeAttr('checked');
            cx.jQuery('input[name="page[useCustomContentForAllChannels]"]').attr('disabled', 'disabled');
        } else {
            if (cx.cm.pageContentTemplate == '') {
                cx.jQuery('input[name="page[useCustomContentForAllChannels]"]').attr('checked', 'checked');
            }
            cx.jQuery('input[name="page[useCustomContentForAllChannels]"]').removeAttr('disabled');
        }

        cx.cm.pageContentTemplate = cx.jQuery(this).val();
    });

    cx.jQuery('#page select[name="page[applicationTemplate]"]').bind('change', function() {
        if (cx.jQuery(this).val() == '') {
            cx.jQuery('input[name="page[useCustomApplicationTemplateForAllChannels]"]').removeAttr('checked');
            cx.jQuery('input[name="page[useCustomApplicationTemplateForAllChannels]"]').attr('disabled', 'disabled');
        } else {
            if (cx.cm.pageApplicationTemplate == '') {
                cx.jQuery('input[name="page[useCustomApplicationTemplateForAllChannels]"]').attr('checked', 'checked');
            }
            cx.jQuery('input[name="page[useCustomApplicationTemplateForAllChannels]"]').removeAttr('disabled');
        }

        cx.cm.pageApplicationTemplate = cx.jQuery(this).val();
    });

    cx.jQuery('#page_skin_view, #page_skin_edit').click(function(event) {
        var themeId = 0;
        var themeName = "";
        if (cx.jQuery('#page_skin').val() != '') {
            themeId = cx.jQuery('#page_skin').val();
            themeName = cx.jQuery('#page_skin option:selected').text();
        } else {
            themeId = cx.variables.get('themeId', 'contentmanager/theme');
            themeName = cx.variables.get('themeName', 'contentmanager/theme');
        }

        if (themeId == 0) {
            themeId = cx.variables.get('defaultTemplates', 'contentmanager/themes')[cx.cm.getCurrentLang()];
        }

        if (cx.jQuery(event.currentTarget).is('#page_skin_view')) {
            window.open('../index.php?preview='+themeId);
        } else {
            window.open('index.php?cmd=ViewManager&act=templates&themes='+cx.variables.get("templateFolders", "contentmanager/themes")[themeId]+'&csrf='+cx.variables.get('csrf', 'contrexx'));
        }
    });

    cx.jQuery('#page select[name="page[application]"]').bind('blur', function() {
        reloadCustomContentTemplates();
        cx.cm.loadApplicationTemplate(cx.jQuery('#page select[name="page[application]"]').val(),
                                      cx.jQuery('#page input[name="page[area]"]').val(),
                                      cx.jQuery('#page select[name="page[skin]"]').val());
    });

    cx.bind("setEditorData", function(data) {
        if (
            cx.jQuery('input[name="page[type]"]:checked').val() != 'application' ||
            !cx.cm.editorInUse() ||
            cx.jQuery("[name=\"page[application]\"").val() == 'Home'
        ) {
            return;
        }
        var pattern = /\[\[APPLICATION_DATA\]\]/;

        //check whether the application type contains the placeholder [[APPLICATION_DATA]] in textarea
        if (!pattern.test(data.content)) {
            data.content = data.content + '[[APPLICATION_DATA]]';
        }
    }, "contentmanager");

    // react to get ?loadpage=
    /*if (jQuery.getUrlVar('loadPage')) {
        cx.cm.loadPage(jQuery.getUrlVar('loadPage'));
    }*/
    if (cx.jQuery.getUrlVar("page") || cx.jQuery.getUrlVar("node")) {
        cx.cm.loadPage(cx.jQuery.getUrlVar("page"), cx.jQuery.getUrlVar("node"), cx.jQuery.getUrlVar("version"), cx.jQuery.getUrlVar("tab"));
    } else {
        cx.cm.isAdjusting = false;
    }

    cx.jQuery(document).click(function() {
        cx.jQuery(".translations-expanded, .actions-expanded").hide();
    });

    cx.cm();
});

cx.cm = function(target) {
    cx.cm.initHistory();

    var dpOptions = {
        showSecond: false,
        dateFormat: 'dd.mm.yy',
        timeFormat: 'hh:mm',
        buttonImage: "core/ContentManager/View/Media/calender.png",
        buttonImageOnly: true
    };
    cx.jQuery("input.date").datetimepicker(dpOptions);

    cx.jQuery('#page input[name="page[slug]"]').keyup(function() {
        var originalSlug  = cx.jQuery(this).val();
        var slugifiedSlug = cx.cm.slugify(originalSlug);
        if (originalSlug != slugifiedSlug) {
            cx.jQuery(this).val(slugifiedSlug);
            cx.jQuery('#liveSlug').text(slugifiedSlug);
        }
    });

    if (cx.jQuery("#page")) {
        cx.jQuery("#page").tabs().css('display', 'block');
    }

    if (cx.jQuery('#showHideInfo')) {
        cx.jQuery('#showHideInfo').toggle(function() {
            cx.jQuery('#additionalInfo').slideDown();
        }, function() {
            cx.jQuery('#additionalInfo').slideUp();
        });
    }

    cx.jQuery('#buttons input').click(function(event) {
        event.preventDefault();
    });

    var inputs = cx.jQuery('.additionalInfo input');
    inputs.focus(function(){
        cx.jQuery(this).css('color','#000000');
    });
    inputs.blur(function(){
        cx.jQuery(this).css('color','#000000');
    });

    cx.jQuery("#cancel").click(function() {
        cx.cm.hideEditView();
    });

    cx.jQuery('#publish, #release').unbind("click").click(function() {
        if (!cx.cm.validateFields()) {
            return false;
        }
        if (cx.cm.editorInUse()) {
            cx.jQuery('#cm_ckeditor').val(CKEDITOR.instances.cm_ckeditor.getData());
        }
        cx.trigger("loadingStart", "contentmanager", {});
        cx.jQuery.post('index.php?cmd=JsonData&object=page&act=set', 'action=publish&'+cx.jQuery('#cm_page').serialize(), function(response) {
            if (response.data != null) {
                if (cx.jQuery('#historyConatiner').html() != '') {
                    cx.cm.loadHistory();
                }
                var newName = cx.jQuery('#page_name').val();
                if (cx.jQuery('#pageId').val() == 'new' || cx.jQuery('#pageId').val() == 0) {
                    cx.jQuery("#pageId").val(response.data.id);
                }
                if (response.data.reload) {
                    cx.cm.createJsTree();
                    cx.cm.loadHistory(cx.jQuery("#pageId").val());
                    return;
                }
                var page = cx.cm.getPageStatus(cx.cm.getNodeId(cx.jQuery("#pageId").val()), cx.cm.getCurrentLang());
                if (publishAllowed) {
                    page.publishing.published = true;
                    page.publishing.hasDraft = "no";
                } else {
                    page.publishing.hasDraft = "waiting";
                }
                switch (cx.jQuery("[name=\"page[type]\"]:checked").attr("value")) {
                    case "content":
                        page.visibility.type = "standard";
                        page.visibility.fallback = false;
                        break;
                    case "redirect":
                        page.visibility.type = "redirection";
                        page.visibility.fallback = false;
                        break;
                    case "symlink":
                        page.visibility.type = "symlink";
                        page.visibility.fallback = false;
                        break;
                    case "application":
                        var module = cx.jQuery("[name=\"page[application]\"").val();
                        if (module != "Home") {
                            page.visibility.type = "application";
                        } else {
                            page.visibility.type = "home";
                            cx.cm.hasHome[cx.cm.getCurrentLang()] = response.data.id;
                        }
                        page.visibility.fallback = false;
                        break;
                    case "fallback":
                        if (!page.visibility.fallback) {
                            cx.cm.createJsTree();
                            return;
                        }
                        break;
                }
                //page.publishing.locked = cx.jQuery("#page_protection_backend").is(":checked");
                page.visibility.protected = cx.jQuery("#page_protection_frontend").is(":checked");
                page.name = newName;
                page.version = response.data.version;

                parameter = new Object;
                parameter.pageId = cx.jQuery('#pageId').val();
                if (cx.jQuery('#page_target_protocol option:selected').val() == '') {
                    parameter.pageRedirectPlaceholder = cx.jQuery('#page_target').val();
                }
                cx.jQuery.ajax({
                    url: 'index.php?cmd=JsonData&object=page&act=isBroken',
                    dataType: 'json',
                    async: false,
                    data: parameter,
                    success: function(reply) {
                        if (typeof reply['data'] == 'boolean') {
                            page.visibility.broken = reply['data'];
                        }
                    }
                });

                cx.cm.updateTreeEntry(page);
            } else {
                cx.trigger("loadingEnd", "contentmanager", {});
            }
            cx.tools.StatusMessage.removeAllDialogs();
        });
    });

    cx.jQuery('#save, #refuse').unbind("click").click(function() {
        if (!cx.cm.validateFields()) {
            return false;
        }
        if (CKEDITOR.instances.cm_ckeditor != null) {
            cx.jQuery('#cm_ckeditor').val(CKEDITOR.instances.cm_ckeditor.getData());
        }
        cx.trigger("loadingStart", "contentmanager", {});
        cx.jQuery.post('index.php?cmd=JsonData&object=page&act=set', cx.jQuery('#cm_page').serialize(), function(response) {
            if (response.data != null) {
                if (cx.jQuery('#historyConatiner').html() != '') {
                    cx.cm.loadHistory();
                }
                var newName = cx.jQuery('#page_name').val();
                if (cx.jQuery('#pageId').val() == 'new' || cx.jQuery('#pageId').val() == 0) {
                    cx.jQuery("#pageId").val(response.data.id);
                }
                if (response.data.reload) {
                    cx.cm.createJsTree();
                    cx.cm.loadHistory(cx.jQuery("#pageId").val());
                    return;
                }
                var page = cx.cm.getPageStatus(cx.cm.getNodeId(cx.jQuery("#pageId").val()), cx.cm.getCurrentLang());
                page.publishing.hasDraft = "yes";
                switch (cx.jQuery("[name=\"page[type]\"]:checked").attr("value")) {
                    case "content":
                        page.visibility.type = "standard";
                        page.visibility.fallback = false;
                        break;
                    case "redirect":
                        page.visibility.type = "redirection";
                        page.visibility.fallback = false;
                        break;
                    case "symlink":
                        page.visibility.type = "symlink";
                        page.visibility.fallback = false;
                        break;
                    case "application":
                        var module = cx.jQuery("[name=\"page[application]\"").val();
                        if (module != "Home") {
                            page.visibility.type = "application";
                        } else {
                            page.visibility.type = "home";
                            cx.cm.hasHome[cx.cm.getCurrentLang()] = response.data.id;
                        }
                        page.visibility.fallback = false;
                        break;
                    case "fallback":
                        if (!page.visibility.fallback) {
                            cx.cm.createJsTree();
                            return;
                        }
                        break;
                }
                page.publishing.locked = cx.jQuery("#page_protection_backend").is(":checked");
                page.visibility.protected = cx.jQuery("#page_protection_frontend").is(":checked");
                page.name = newName;
                page.version = response.data.version;

                parameter = new Object;
                parameter.pageId = cx.jQuery('#pageId').val();
                if (cx.jQuery('#page_target_protocol option:selected').val() == '') {
                    parameter.pageRedirectPlaceholder = cx.jQuery('#page_target').val();
                }
                cx.jQuery.ajax({
                    url: 'index.php?cmd=JsonData&object=page&act=isBroken',
                    dataType: 'json',
                    async: false,
                    data: parameter,
                    success: function(reply) {
                        if (typeof reply['data'] == 'boolean') {
                            page.visibility.broken = reply['data'];
                        }
                    }
                });

                cx.cm.updateTreeEntry(page);
            } else {
                cx.trigger("loadingEnd", "contentmanager", {});
            }
        });
    });

    cx.jQuery('#preview').click(function(event) {
        if (!cx.cm.validateFields()) {
            return false;
        }
        if (CKEDITOR.instances.cm_ckeditor != null) {
            cx.jQuery('#cm_ckeditor').val(CKEDITOR.instances.cm_ckeditor.getData());
        }
        cx.jQuery.ajax({
            type: 'post',
            url:  'index.php?cmd=JsonData&object=page&act=setPagePreview',
            data:  cx.jQuery('#cm_page').serialize(),
            async: false,
            error: function() {
                event.preventDefault();
            }
        });
    });

    cx.jQuery('div.wrapper').click(function(event) {
        cx.jQuery(event.target).find('input[name="page[type]"]:radio').click();
    });

    cx.jQuery('div.wrapper input[name="page[type]"]:radio').click(function(event) {
        cx.jQuery('div.activeType').removeClass('activeType');
        cx.jQuery(event.target).parentsUntil('div.type').addClass('activeType');
    });

    cx.jQuery('#page').bind('tabsselect', function(event, ui) {
        if (ui.index == 5) {
            if (cx.jQuery('#page_history').html() == '') {
                cx.cm.loadHistory();
            }
        }
    });

    cx.jQuery('#page').bind('tabsshow', function(event, ui) {
        if (ui.index == 0) {
            cx.cm.resizeEditorHeight();
        }
        cx.cm.pushHistory('tab');
    });

    //lock together the title and content title.
    var contentTitle = cx.jQuery('#contentTitle');
    var navTitle = cx.jQuery('#title');
    var headerTitlesLock = new Lock(cx.jQuery('#headerTitlesLock'), function(isClosed) {
        if (isClosed) {
            contentTitle.attr('disabled', 'true');
            contentTitle.val(navTitle.val());
        } else {
            contentTitle.removeAttr('disabled');
        }
    });
    navTitle.bind('change', function() {
        if (headerTitlesLock.isLocked())
            contentTitle.val(navTitle.val());
    });

    // show/hide elemnts when a page's type is changed
    cx.jQuery('input[name="page[type]"]').click(function(event) {
        cx.jQuery('#page .type_hidable').hide();
        cx.jQuery('#page .type_'+cx.jQuery(event.target).val()).show();
        cx.jQuery('#page #type_toggle label').text(cx.jQuery(this).next().text());

        // if we change type from fallback to content or application, we want to
        // load content from fallback page:
        var content = cx.cm.getEditorData();

        if (cx.jQuery(this).val() == 'application') {
            cx.bind("loadingStart", function() {
                cx.cm.isAdjusting = true;
            }, "contentmanager");
            cx.bind("loadingEnd", function() {
                cx.cm.isAdjusting = false;
            }, "contentmanager");
            if (    event.originalEvent !== undefined
                &&  cx.cm.isAdjusting != undefined
                &&  !cx.cm.isAdjusting
            ) {
                cx.cm.setEditorData(content);
            }
            cx.jQuery('#page #application_toggle label').text(cx.jQuery(this).next().text());
        }
        if (cx.jQuery(this).val() == 'redirect') {
            cx.jQuery('#page #preview').hide();
        } else {
            cx.jQuery('#page #preview').show();
        }
        if (cx.jQuery(this).val() == 'fallback') {
            cx.jQuery("#type_toggle, #themes_toggle, #themes_container").hide();
        } else {
            cx.jQuery("#type_toggle, #themes_toggle, #themes_container").show();
        }
        cx.cm.resizeEditorHeight();

        if (cx.cm.lastPageType == "fallback" && (cx.jQuery(this).val() == "content" || cx.jQuery(this).val() == "application") && content == "") {
            var fallbackLanguage = cx.cm.getCurrentLang();
            while (true) {
                if (!fallbacks[fallbackLanguage]) {
                    break;
                }
                fallbackLanguage = fallbacks[fallbackLanguage];
            }
            var fallbackPageId = cx.jQuery("#" + cx.jQuery("#pageId").val()).parent().children("." + fallbackLanguage).attr("id");
            cx.trigger("loadingStart", "contentmanager", {});
            cx.jQuery.ajax({
                url: "index.php?cmd=JsonData&object=page&act=get&page=" + fallbackPageId,
                async: false,
                success: function(response) {
                    var fallbackPageType = response.data.type;
                    if (fallbackPageType != "content" && fallbackPageType != "application") {
                        return;
                    }
                    var fallbackPageContent = response.data.content;
                    if (cx.cm.editorInUse()) {
                        CKEDITOR.instances.cm_ckeditor.setData(fallbackPageContent);
                    } else {
                        cx.jQuery("#cm_ckeditor").val(fallbackPageContent);
                    }
                }
            });
            cx.trigger("loadingEnd", "contentmanager", {});
        }
        cx.cm.lastPageType = cx.jQuery(this).val();
    });
    cx.jQuery('input[name="page[type]"]:checked').trigger('click');

    // togglers
    cx.jQuery('#content-manager #sidebar_toggle').click(function() {
        cx.cm.toggleSidebar();
        if (cx.jQuery('#pageId').val() !== 'new') {
            cx.cm.saveToggleStatuses();
        }
    });

    cx.jQuery('.toggle').click(function(objEvent) {
        cx.jQuery(this).toggleClass('open closed');
        if (cx.jQuery(objEvent.currentTarget).is('#titles_toggle')) {
            cx.cm.resizeEditorHeight();
        }
        cx.jQuery(this).nextAll('.container').first().animate({height: 'toggle'}, 400, function() {
            if (cx.jQuery('#pageId').val() !== 'new') {
                cx.cm.saveToggleStatuses();
            }
        });
    });

    cx.jQuery('.checkbox').click(function(event) {
        var indicator = cx.jQuery(this).children('.indicator');
        var container = cx.jQuery(this).nextAll('.container').first();

        if (!cx.jQuery(event.target).is('.indicator') ) {
            indicator.prop('checked', !indicator.prop('checked'));
            indicator.trigger('change');
        }
        if (!cx.jQuery(this).hasClass('no_toggle')) {
            container.animate({height: 'toggle'}, 400);
        }
    });

    cx.jQuery("#page_name").blur(function() {
        var val = cx.jQuery(this).val();
        if (val != "") {
            var fields = [
                "page_title",
                "page_metatitle",
                "page_metadesc",
                "page_metakeys",
                "page_slug"
            ];
            cx.jQuery.each(fields, function(index, el) {
                var element = cx.jQuery("#" + el);
                if (element.val() == "") {
                    element.val(val);
                }
            });
            var previewTarget = cx.variables.get("basePath", "contrexx") + cx.jQuery("#page_slug_breadcrumb").text() + val;
            cx.jQuery("#preview").attr("href", previewTarget + "?pagePreview=1");
        }
    });

    cx.jQuery("select#page_application").change(function() {
        return cx.cm.homeCheck(true, cx.jQuery("#pageId").val());
    });
    cx.jQuery('#page input[name="page[area]"]').keyup(function() {
        return cx.cm.homeCheck(true, cx.jQuery("#pageId").val());
    });
    cx.jQuery('#page input[name="page[area]"]').change(function() {
        cx.cm.loadApplicationTemplate(cx.jQuery('#page select[name="page[application]"]').val(),
                                      cx.jQuery('#page input[name="page[area]"]').val(),
                                      cx.jQuery('#page select[name="page[skin]"]').val());
    });
    // prevent enter key from opening fileBrowser
    cx.jQuery("#content-manager input").keydown(function(event) {
        if (event.keyCode == 13) {
            return false;
        }
    });

    cx.bind("pageStatusUpdate", cx.cm.updatePageIcons, "contentmanager");
    cx.bind("pageStatusUpdate", cx.cm.updateTranslationIcons, "contentmanager");
    cx.bind("pageStatusUpdate", cx.cm.updateActionMenu, "contentmanager");
    cx.bind("pagesStatusUpdate", cx.cm.updatePagesIcons, "contentmanager");
    cx.bind("pagesStatusUpdate", cx.cm.updateTranslationsIcons, "contentmanager");
    cx.bind("pagesStatusUpdate", cx.cm.updateActionMenus, "contentmanager");
    cx.bind("loadingStart", cx.cm.lock, "contentmanager");
    cx.bind("loadingEnd", cx.cm.unlock, "contentmanager");

    cx.cm.resetEditView();

    // toggle ckeditor when sourceMode is toggled
    cx.jQuery('#page input[name="page[sourceMode]"]').change(function() {
        cx.cm.toggleEditor();
    });

    cx.jQuery(document).ready(function() {
        if (cx.jQuery('#languageCount').val()<=1) {
            cx.jQuery("#site-language").hide();
            cx.jQuery("#content-manager").addClass("cm-single-locale");
        } else {
            cx.jQuery("#site-language").show();
            cx.jQuery("#content-manager ").removeClass("cm-single-locale");
        }
        if (cx.jQuery('#pageId').val() == 'new') {
            // make sure history tab is hidden
            cx.jQuery('.tab.page_history').hide();
            // load selected tab
            cx.cm.selectTab(cx.jQuery.getUrlVar('tab'), false);
            // load ckeditor if it's a new page
            cx.cm.createEditor();
        }
    });
};
cx.cm.loadApplicationTemplate = function(application, area, template) {
    cx.jQuery.ajax({
        url: "index.php?cmd=JsonData&object=page&act=loadApplicationTemplate&app=" + application + "&area=" + area + "&template=" + template,
        success: function(response) {
            var templateFile = response.data.files;
            var select = cx.jQuery('#page select[name="page[applicationTemplate]"]');
            select.empty();
            select.append(cx.jQuery("<option value=\"\" selected=\"selected\">(Default)</option>"));
            for (var i = 0; i < templateFile.length; i++) {
                if (templateFile[i] != '') {
                    select.append(cx.jQuery('<option>', {
                        value : templateFile[i]
                    }).text(templateFile[i]));
                }
            }

            var page = cx.cm.page;
            // in case we are creating a new page, then cx.cm.page is not yet defined
            if (typeof(page) == 'undefined') {
                page = {
                    customContent:"",
                    useCustomContentForAllChannels:0,
                    applicationTemplate:"",
                    useCustomApplicationTemplateForAllChannels:0
                }
            }
            cx.jQuery('span.area').text(response.data.area);
            cx.jQuery('span.folderPath').text(response.data.path);
            cx.jQuery('#page select[name="page[customContent]"]').val(page.customContent);
            cx.cm.pageContentTemplate = page.customContent;

            if (page.useCustomContentForAllChannels == '1') {
                cx.jQuery('#page input[name="page[useCustomContentForAllChannels]"]').attr('checked', 'checked');
            } else {
                cx.jQuery('#page input[name="page[useCustomContentForAllChannels]"]').removeAttr('checked');
            }
            cx.jQuery('#page select[name="page[customContent]"]').trigger('change');


            cx.jQuery('#page select[name="page[applicationTemplate]"]').val(page.applicationTemplate);
            cx.cm.pageApplicationTemplate = page.applicationTemplate;

            if (page.useCustomApplicationTemplateForAllChannels == '1') {
                cx.jQuery('#page input[name="page[useCustomApplicationTemplateForAllChannels]"]').attr('checked', 'checked');
            } else {
                cx.jQuery('#page input[name="page[useCustomApplicationTemplateForAllChannels]"]').removeAttr('checked');
            }
            cx.jQuery('#page select[name="page[applicationTemplate]"]').trigger('change');
        }
    }).always(function(response) {
        if(response.hasOwnProperty('area')){
            delete response.data.area;
        }
        if(response.hasOwnProperty('files')){
            delete response.data.files;
        }
        if(response.hasOwnProperty('path')){
            delete response.data.path;
        }
    });
}

cx.cm.homeCheck = function(addClasses, pageId) {
    var module = cx.jQuery("select#page_application");
    var cmd = cx.jQuery("input#page_application_area");

    module.removeClass("warning");
    cmd.removeClass("warning");

    // this is no home page
    if (module.val() != "Home" || cmd.val() != "") {
        return false;
    }

    // there is no home for this language yet
    if (!cx.cm.hasHome[cx.cm.getCurrentLang()]) {
        return false;
    }

    // is the page not the current page?
    if (pageId && cx.cm.hasHome[cx.cm.getCurrentLang()] == pageId) {
        return false;
    }

    if (addClasses) {
        module.addClass("warning");
        cmd.addClass("warning");
    }
    return true;
}

cx.cm.createJsTree = function(target, data, nodeLevels, open_all) {
    cx.trigger("loadingStart", "contentmanager", {});
    var langPreset;
    try {
        langPreset = cx.cm.getCurrentLang();
    } catch (ex) {}
    if (!target) {
        target = cx.cm.getTree();
    }
    try {
        target.jstree("destroy");
    } catch (ex) {}

    var eventAdded = false;

    // reset expanded table
    if (cx.jQuery(".switch-tag-dropdown").hasClass("open")) {
        cx.cm.resetExpandedTable();
    }

    target.jstree({
        // List of active plugins
        "plugins" : [
            "themes","json_data","ui","crrm","cookies","dnd","types", "languages", "checkbox"
        ], // TODO: hotkeys, search?
        "languages" : languages,
        "checkbox": {
            // We want to select every single node separately
            "two_state" : true
        },
        "json_data" : {
            "data" : data,
            "ajax" : {
                "url" : function (nodeId) {
                    if (nodeId == -1) {
                        nodeId = "";
                    } else {
                        nodeId = cx.jQuery(nodeId).closest('li').attr('id').split("_")[1];
                        nodeId = "&nodeid=" + nodeId;
                    }
                    return "?cmd=JsonData&object=node&act=getTree" + nodeId;
                },
                "progressive_render" : true,
                "success" : function(response) {
                    if (!response.data) {
                        return null;
                    }
                    if (cx.cm.actions == undefined) {
                        cx.cm.actions = response.data.actions;
                    } else {
                        cx.jQuery(languages).each(function(index, lang) {
                            cx.jQuery.extend(cx.cm.actions[lang], response.data.actions[lang]);
                        });
                    }
                    nodeLevels = [];
                    for (nodeId in response.data.nodeLevels) {
                        nodeLevels[nodeId] = response.data.nodeLevels[nodeId];
                    }
                    cx.cm.hasHome = response.data.hasHome;
                    return response.data.tree;
                }/*,
                                   // the `data` function is executed in the instance's scope
                                   // the parameter is the node being loaded
                                   // (may be -1, 0, or undefined when loading the root nodes)
                                   "data" : function (n) {
                                   return {
                                   "operation" : "get_children",
                                   "id" : n.attr ? n.attr("id").replace("node_", "") : 1
                                   };
                                   }*/
            }
        },
        "types" : {
            // I set both options to -2, as I do not need depth and children count checking
            // Those two checks may slow jstree a lot, so use only when needed
            "max_depth" : -2,
            "max_children" : -2,
            // next ln will be neede as soon as we want to manage multiple sites in one cloudrexx install
            //"valid_children" : [ "site" ],
            "types" : {
                // The default type
                "default" : {
                    "valid_children" : "default"
                }/*,
                     // sites - i.e. manage multiple sites in one cloudrexx install
                     "site" : {
                     // can have pages in them
                     "valid_children" : [ "default" ],
                     // those prevent the functions with the same name to be used on site nodes
                     "start_drag" : false,
                     "move_node" : false,
                     "delete_node" : false,
                     "remove" : false
                     }*/
            }
        },
        "cookies" : {
            'save_loaded' : cx.variables.get('save_loaded', 'contentmanager/jstree'),
            'save_opened' : cx.variables.get('save_opened', 'contentmanager/jstree'),
            'save_selected' : false,
            'cookie_options': {path: cx.variables.get('basePath')}
        }
    })
    .bind("before.jstree", function(e, data) {
        if (!eventAdded) {
            cx.jQuery('#site-tree').delegate('a', 'mouseup', function() {
                mouseIsUp = true;
            }).delegate('a', 'mousedown', function() {
                mouseIsUp = false;
            });
        }
        eventAdded = true;;
    })
    .bind("create.jstree", function (e, data) {
        cx.jQuery.post(
            "server.php",
            {
                "operation" : "create_node",
                "id" : data.rslt.parent.attr("id").replace("node_", ""),
                "position" : data.rslt.position,
                "title" : data.rslt.name,
                "type" : data.rslt.obj.attr("rel")
            },
            function (r) {
                if (r.status) {
                    cx.jQuery(data.rslt.obj).attr("id", "node_" + r.id);
                } else {
                    cx.jQuery.jstree.rollback(data.rlbk);
                }
            }
            );
    })
    .bind("remove.jstree", function (e, data) {
        data.rslt.obj.each(function () {
            cx.jQuery.ajax({
                async : false,
                type: 'POST',
                url: "server.php",
                data : {
                    "operation" : "remove_node",
                    "id" : this.id.replace("node_", "")
                },
                success : function (r) {
                    if (!r.status) {
                        data.inst.refresh();
                    }
                }
            });
        });
    })
    /*.bind("rename.jstree", function (e, data) {
      jQuery.post(
      "server.php",
      {
      "operation" : "rename_node",
      "id" : data.rslt.obj.attr("id").replace("node_", ""),
      "title" : data.rslt.new_name
      },
      function (r) {
      if (!r.status) {
      jQuery.jstree.rollback(data.rlbk);
      }
      }
      );
      })*/
    .bind("prepare_move.jstree", function(e, data) {
        cx.jQuery(".jstree-leaf").addClass("cm-leaf");
    })
    .bind("move_node.jstree", function (e, data) {
        // The following line (together with prepare_move event listener) fixes #1359
        cx.jQuery(".cm-leaf").removeClass("jstree-closed").addClass("jstree-leaf").removeClass("cm-leaf");
        data.rslt.o.each(function (i) {
            cx.trigger("loadingStart", "contentmanager", {});
            cx.jQuery.ajax({
                async : false,
                type: 'POST',
                url: "?cmd=JsonData&object=node&act=move",
                data : {
                    "operation" : "move_node",
                    "id" : cx.jQuery(this).attr("id").replace("node_", ""),
                    "ref" : data.rslt.cr === -1 ? 1 : data.rslt.np.attr("id").replace("node_", ""),
                    "position" : data.rslt.o.index(),
                    "title" : data.rslt.name,
                    "copy" : data.rslt.cy ? 1 : 0
                },
                success : function (r) {
                    for (nodeId in r.data.nodeLevels) {
                        nodeLevels[nodeId] = r.data.nodeLevels[nodeId];
                    }
                    for (nodeId in r.data.nodeLevels) {
                        cx.jQuery('#node_' + nodeId).children('a').children('.jstree-checkbox').css('left', '-' + ((r.data.nodeLevels[nodeId] * 18) + 20) + 'px');
                    }
                    cx.trigger("loadingEnd", "contentmanager", {});
                    return true;
                    // TODO: response/reporting/refresh
                    if (!r.status) {
                        cx.jQuery.jstree.rollback(data.rlbk);
                    } else {
                        cx.jQuery(data.rslt.oc).attr("id", "node_" + r.id);
                        if (data.rslt.cy && cx.jQuery(data.rslt.oc).children("UL").length) {
                            data.inst.refresh(data.inst._get_parent(data.rslt.oc));
                        }
                    }
                }
            });
        });
    })
    .bind("load_node.jstree", function (event, siteTreeData) {
        var jst = cx.jQuery.jstree._reference('#site-tree');
        var langs = jst.get_settings().languages;

        for (nodeId in nodeLevels) {
            cx.jQuery('#node_' + nodeId).children('a').children('.jstree-checkbox').css('left', '-' + ((nodeLevels[nodeId] * 18) + 20) + 'px');
        }

        cx.jQuery('#site-tree ul li').not(".actions-expanded li, .translations-expanded li").each(function() {
            cx.jQuery(this).children('a:last').after(function() {
                if (!cx.jQuery(this).hasClass('jstree-move') && cx.jQuery(this).siblings('.jstree-move').length == 0) {
                    return '<a class="jstree-move" href="#"></a>';
                }
            });
        });

        // load pages on click
        cx.jQuery('#site-tree a').each(function(index, leaf) {
            cx.jQuery(leaf).not('.jstree-move').click(function(event) {
                var action;
                // don't load a page if the user only meant to select/unselect its checkbox
                if (!cx.jQuery(event.target).hasClass('jstree-checkbox')
                    && !cx.jQuery(this).hasClass('broken')
                    && !cx.jQuery(event.target).is('ins')) {
                    var module = "";
                    try {
                        module = cx.jQuery.trim(cx.jQuery.parseJSON(cx.jQuery(leaf).attr("data-href")).module);
                        module = module.split(" ")[0];
                    } catch (ex) {}
                    if (cx.jQuery.inArray(module, ["", "Home", "Login", "Imprint", "Ids", "Error", "Sitemap", "Agb", "Privacy", "Search"]) == -1) {
                        cx.cm.showEditModeWindow(module, this.id, cx.jQuery(this).closest('li').attr("id").split("_")[1]);
                    } else {
                        cx.cm.loadPage(this.id, cx.jQuery(this).closest('li').attr("id").split("_")[1], null, "content");
                    }
                } else if (cx.jQuery(event.target).is('ins.page') ||
                        cx.jQuery(event.target).is('ins.publishing')) {
                    if (cx.jQuery(event.target).is('ins.page')) {
                        action = "hide";
                        if (cx.jQuery(event.target).hasClass('invisible')) {
                            action = "show";
                        }
                    } else {
                        action = "deactivate";
                        if (cx.jQuery(event.target).hasClass('unpublished')) {
                            action = "activate";
                        }
                    }
                    var nodeId = cx.jQuery(event.target).closest("li").attr("id").split("_")[1];
                    cx.cm.performAction(action, this.id, nodeId);
                }
            });

            cx.jQuery(this).hover(
                function() {
                    if (mouseIsUp) {
                        cx.jQuery(this).siblings('.jstree-wrapper').addClass('hover');
                        cx.jQuery(this).parent().children('.jstree-move').css('display', 'inline-block');
                    } else {
                        cx.jQuery(this).mouseup(function() {
                            cx.jQuery(this).siblings('.jstree-wrapper').addClass('hover');
                            cx.jQuery(this).parent().children('.jstree-move').css('display', 'inline-block');
                            cx.jQuery(this).unbind('mouseup');
                        });
                    }
                },
                function(e) {
                    if (mouseIsUp) {
                        cx.jQuery(this).siblings('.jstree-wrapper').removeClass('hover');
                        cx.jQuery(this).parent().children('.jstree-move').css('display', 'none');
                    } else {
                        cx.jQuery(document).bind('mouseup.link', function() {
                            if (e.type != 'mouseleave') {
                                cx.jQuery(e.currentTarget).siblings('.jstree-wrapper').removeClass('hover');
                                cx.jQuery(e.currentTarget).parent().children('.jstree-move').css('display', 'none');
                            }
                            cx.jQuery(document).unbind('mouseup.link');
                        });
                    }
                }
            );
        });

        // highlight active page
        cx.jQuery('#' + cx.jQuery('#pageId').val()).siblings('.jstree-wrapper').addClass('active');

        // add a wrapper div for the horizontal lines
        cx.jQuery('#site-tree li:not(.translation) > ins.jstree-icon').each(function(index, node) {
            cx.jQuery(this).hover(
                function() {
                    if (mouseIsUp) {
                        cx.jQuery(this).siblings('.jstree-wrapper').addClass('hover');
                        cx.jQuery(this).siblings('.jstree-move').css('display', 'inline-block');
                    } else if (!mouseIsUp) {
                        cx.jQuery(this).mouseup(function() {
                            cx.jQuery(this).siblings('.jstree-wrapper').addClass('hover');
                            cx.jQuery(this).siblings('.jstree-move').css('display', 'inline-block');
                            cx.jQuery(this).unbind('mouseup');
                        });
                    }
                },
                function(e) {
                    if (mouseIsUp) {
                        cx.jQuery(this).siblings('.jstree-wrapper').removeClass('hover');
                        cx.jQuery(this).siblings('.jstree-move').css('display', 'none');
                    } else {
                        cx.jQuery(document).bind('mouseup.ins', function() {
                            cx.jQuery(e.currentTarget).siblings('.jstree-wrapper').removeClass('hover');
                            cx.jQuery(e.currentTarget).siblings('.jstree-move').css('display', 'none');
                            cx.jQuery(document).unbind('mouseup.ins');
                        });
                    }
                }
            );

            if (cx.jQuery(node).prev().is(".jstree-wrapper")) {
                return;
            }

            var translations = cx.jQuery("<div class=\"translations\" />");
            var nodeIds = [];
            cx.jQuery(this).parent().children("a").each(function(index, el) {
                if (!cx.jQuery(el).is(".jstree-move")) {
                    var lang = cx.jQuery(el).attr("class");
                    var node = cx.jQuery(el).parent("li");
                    nodeIds[lang] = node.attr("id").substr(5);
                }
            });

            // show dropdown for more than 4 locales
            var languages = cx.jQuery("select.chzn-select option");
            // generate tags
            cx.jQuery.each(languages, function(index, el) {
                var lang = cx.jQuery(el).val();
                var langEl = cx.jQuery("<div class=\"translation " + lang + "\" />");
                langEl.text(lang);
                langEl.click(function() {
                    var page = cx.cm.getPageStatus(nodeIds[lang], lang);
                    if (page.existing) {

                        cx.cm.loadPage(page.id, null, null, "content");
                    } else {
                        cx.cm.setCurrentLang(lang);
                        cx.cm.loadPage(undefined, nodeIds[lang], null, "content");
                    }
                });
                translations.append(langEl);
            });
            // dropdown
            translations
              .append('<div class="label">' + cx.variables.get('TXT_CORE_CM_TRANSLATIONS', 'contentmanager/lang') + '</div><div class="arrow" /></div>')
              .prepend("<div class=\"translations-expanded\" style=\"display: none;\"><ul></ul></div>")
              .click(function(e) {
                  e.stopPropagation();
                  if (!cx.jQuery(e.target).is(".translations > .translation")) {
                      cx.jQuery(this).children(".translations-expanded").toggle();
                      cx.jQuery(".translations.dropdown").not(this).children(".translations-expanded").hide();
                      cx.jQuery('.actions-expanded').hide();
                  }
              });
            var translationDropdown = translations.find(".translations-expanded ul");
            cx.jQuery.each(languages, function(index, el) {
                var lang = cx.jQuery(el).val();
                var langEl = cx.jQuery("<li class=\"translation " + lang + "\" />");
                langEl.html("<span>" + cx.jQuery(el).text() + "</span>");
                langEl.children("span").click(function() {
                    var page = cx.cm.getPageStatus(nodeIds[lang], lang);
                    if (page.existing) {
                        cx.cm.loadPage(page.id, null, null, "content");
                    } else {
                        cx.cm.setCurrentLang(lang);
                        cx.cm.loadPage(undefined, nodeIds[lang], null, "content");
                    }
                });
                translationDropdown.append(langEl);
            });

            var actions = cx.jQuery('<div class="actions"><div class="label">' + cx.variables.get('TXT_CORE_CM_ACTIONS', 'contentmanager/lang') + '</div><div class="arrow" /></div>')
                            .prepend("<div class=\"actions-expanded\" style=\"display: none;\"><ul></ul></div>")
                            .click(function(e) {
                                e.stopPropagation();
                                cx.jQuery(this).children(".actions-expanded").toggle();
                                cx.jQuery(".actions").not(this).children(".actions-expanded").hide();
                                cx.jQuery('.translations-expanded').hide();
                            });
            var wrapper = cx.jQuery(actions).wrap('<div class="jstree-wrapper" />').parent();
            wrapper.prepend(translations);
            cx.jQuery(node).before(wrapper);
        });
        if (
            !cx.jQuery(".translations").hasClass("dropdown") &&
            cx.jQuery(".translations").first().children(".translation").length <= 1
        ) {
            cx.jQuery(".translations").hide();
            cx.jQuery(".translation").html("");
        }

        cx.jQuery('.jstree-wrapper').hover(
            function(e) {
                if (mouseIsUp) {
                    cx.jQuery(this).addClass('hover');
                    cx.jQuery(this).siblings('.jstree-move').css('display', 'inline-block');
                } else {
                    cx.jQuery(this).mouseup(function() {
                        cx.jQuery(this).addClass('hover');
                        cx.jQuery(this).siblings('.jstree-move').css('display', 'inline-block');
                        cx.jQuery(this).unbind('mouseup');
                    });
                }
            },
            function (e) {
                if (mouseIsUp) {
                    cx.jQuery(this).removeClass('hover');
                    cx.jQuery(this).siblings('.jstree-move').css('display', 'none');
                } else {
                    cx.jQuery(document).bind('mouseup.jstree-wrapper', function() {
                        cx.jQuery(e.currentTarget).removeClass('hover');
                        cx.jQuery(e.currentTarget).siblings('.jstree-move').css('display', 'none');
                        cx.jQuery(document).unbind('mouseup.jstree-wrapper');
                    });
                }
            }
        );

        // prepare the expanded table
        cx.jQuery(langs).each(function(a, lang) {
            $J('div#site-tree li .jstree-wrapper').each(function(b, e) {
                if (cx.jQuery(e).children('span.module.' + lang).length > 0) {
                    return;
                }
                if (lang == cx.jQuery('#site-tree').jstree('get_lang')) {
                    display = 'show';
                } else {
                    display = 'hide';
                }
                cx.jQuery(e).find('.translations').after('<span class="module ' + lang + ' ' + display + '" /><a class="preview ' + lang + ' ' + display + '" target="_blank">' + cx.variables.get('TXT_CORE_CM_VIEW', 'contentmanager/lang') + '</a>');
                cx.jQuery(e).find('.actions').after($J('<span class="lastupdate ' + lang + ' ' + display + '"><span class="date" /><span class="user tp-trigger" /><span class="user tp-value"/></span>'));
                var info = cx.jQuery.parseJSON(cx.jQuery(e).siblings('a[data-href].' + lang).attr('data-href'));
                try {
                    if (info != null) {
                        cx.jQuery(e).children('span.module.' + lang).text(info.module);
                        cx.jQuery(e).children('a.preview.' + lang).attr('href', "#");
                        cx.jQuery(e).find('span.lastupdate.' + lang + ' .date').text(info.lastupdate);
                        cx.jQuery(e).find('span.lastupdate.' + lang + ' .user.tp-value').text(cx.variables.get('TXT_CORE_CM_LAST_MODIFIED', 'contentmanager/lang/tooltip') + ' ' + (info.user != '' ? '„'+info.user+'“'  : '“'));
                    }
                } catch (ex) {
                    cx.jQuery(e).children('a.preview.' + lang).css('display', 'none');
                }
            });
        });

        cx.jQuery("a.preview").click(function() {
            var pageId = cx.jQuery(this).parent().parent().children("a." + cx.cm.getCurrentLang()).attr("id");
            var path = cx.variables.get("basePath", "contrexx") + cx.cm.getCurrentLang() + cx.cm.getPagePath(pageId) + "?pagePreview=1";
            cx.jQuery(this).attr("href", path);
        });

        cx.jQuery(".switch-tag-dropdown").unbind("click").bind("click", function(e) {
            e.preventDefault();
            cx.cm.switchTagDropdown(true);
        });

        // publishing and visibility icons
        cx.jQuery('#site-tree li a ins.jstree-icon').each(function(index, node) {
            if (cx.jQuery(node).hasClass("publishing") || cx.jQuery(node).hasClass("page")) {
                return;
            }
            publishing = cx.jQuery(node).closest('li').data(cx.jQuery(node).parent().attr('id')).publishing;
            visibility = cx.jQuery(node).closest('li').data(cx.jQuery(node).parent().attr('id')).visibility;

            cx.jQuery(node).before('<ins class="jstree-icon publishing '+publishing+'">&nbsp;</ins>');
            cx.jQuery(node).addClass("page " + visibility);
        });

        cx.jQuery("#site-tree ul li > a").each(function(index, element) {
            var pageId = cx.jQuery(element).attr("id");
            var nodeId = cx.jQuery(element).parent("li").attr("id").substr(5);
            var lang = cx.jQuery(element).attr("class").split(" ")[0];
            // theres an error here, we'll fix it later:
            if (!cx.jQuery(element).children(".name").length) {
                var pageName = jQuery.trim(cx.jQuery(element).text());
                cx.jQuery(element).contents().filter(function() {
                    return this.nodeType == 3;
                }).each(function(index, el) {
                    // It would be nicer if we could select all page <a>
                    // elements directly using a class and just remove all text
                    // nodes or not to add the text nodes in the first place.
                    // ATTENTION: The space " " is necessary otherwise drag&drop
                    // stops working.
                    el.textContent = " ";
                });
                cx.jQuery(element).append("<div class=\"name\">" + pageName + "</div>");
            }
            if (pageId) {
                cx.cm.updateTreeEntry(cx.cm.getPageStatus(nodeId, lang));
            }

            var langEl = cx.jQuery("#node_" +nodeId+ " > .jstree-wrapper  .translations-expanded .translation."+lang);
            if (
              !cx.jQuery(this).hasClass("jstree-move") &&
              !langEl.find("ins.page, ins.publishing").length
            ) { // don't prepend twice
                // copy publishing and visibility icon to dropdown
                if (
                    !cx.jQuery(this).has("ins.page") ||
                    !cx.jQuery(this).has("ins.publishing")
                ) {
                    return;
                }
                var insertPage = cx.jQuery(this).children("ins.page").clone();
                var insertPublishing = cx.jQuery(this).children("ins.publishing").clone();
                langEl.prepend(insertPage).prepend(insertPublishing);
            }
        });

        var checkSiteTree = setInterval(function() {
            if (cx.jQuery('#site-tree li').length) {
                cx.jQuery('.jstree-move').empty();
                clearInterval(checkSiteTree);
            }
        }, 100);


        cx.jQuery('#site-tree .user.tp-trigger').tooltip({
            tip: '#tooltip_message',
            offset: [-124,-202],
            position: 'top left',
            predelay: 250,
            onBeforeShow: function(objEvent) {
                this.getTip().html(this.getTrigger().siblings('.user.tp-value').text());
            }
        });
        cx.jQuery('#site-tree .publishing, #site-tree .page, #site-tree .jstree-move, #site-tree .translation, #site-tree .preview, #site-tree .name').tooltip({
            tip: '#tooltip_message',
            offset: [-130,-231],
            predelay: 700,
            onBeforeShow: function(objEvent) {
                var objTrigger = this.getTrigger();
                var objTip = this.getTip();
                objTip.html('');
                var arrCssClasses = cx.jQuery.trim(objTrigger.attr('class')).split(' ');

                if (objTrigger.hasClass('publishing')) {

                    var arrStatuses = new Array();
                    var arrTipMessage = new Array();

                    if (objTrigger.hasClass('unpublished')) {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_UNPUBLISHED', 'contentmanager/lang/tooltip'));
                    } else {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_PUBLISHED', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('draft') && !objTrigger.hasClass('waiting')) {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_DRAFT', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('draft') && objTrigger.hasClass('waiting')) {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_DRAFT_WAITING', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('locked')) {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_LOCKED', 'contentmanager/lang/tooltip'));
                    }

                    if (arrStatuses.length > 0) {
                        arrTipMessage.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_INFO_STATUSES', 'contentmanager/lang/tooltip')+cx.jQuery.ucfirst(arrStatuses.join(', ')));
                    }
                    if (!objTrigger.hasClass('inexistent')) {
                        if (objTrigger.hasClass('unpublished')) {
                            arrTipMessage.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_INFO_ACTION_ACTIVATE', 'contentmanager/lang/tooltip'));
                        } else {
                            arrTipMessage.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_INFO_ACTION_DEACTIVATE', 'contentmanager/lang/tooltip'));
                        }
                    }

                    objTip.html(arrTipMessage.join('<br />'));

                } else if (objTrigger.hasClass('page')) {

                    var arrStatuses = new Array();
                    var arrTypes = new Array();
                    var arrTipMessage = new Array();

                    if (objTrigger.hasClass('broken')) {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PAGE_STATUS_BROKEN', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('invisible')) {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PAGE_STATUS_INVISIBLE', 'contentmanager/lang/tooltip'));
                    } else {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PAGE_STATUS_VISIBLE', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('protected')) {
                        arrStatuses.push(cx.variables.get('TXT_CORE_CM_PAGE_STATUS_PROTECTED', 'contentmanager/lang/tooltip'));
                    }

                    if (!objTrigger.hasClass('home') && !objTrigger.hasClass('application') && !objTrigger.hasClass('redirection') && !objTrigger.hasClass('symlink')) {
                        arrTypes.push(cx.variables.get('TXT_CORE_CM_PAGE_TYPE_CONTENT_SITE', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('application')) {
                        arrTypes.push(cx.variables.get('TXT_CORE_CM_PAGE_TYPE_APPLICATION', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('redirection')) {
                        arrTypes.push(cx.variables.get('TXT_CORE_CM_PAGE_TYPE_REDIRECTION', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('symlink')) {
                        arrTypes.push(cx.variables.get('TXT_CORE_CM_PAGE_TYPE_SYMLINK', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('home')) {
                        arrTypes.push(cx.variables.get('TXT_CORE_CM_PAGE_TYPE_HOME', 'contentmanager/lang/tooltip'));
                    }
                    if (objTrigger.hasClass('fallback')) {
                        arrTypes.push(cx.variables.get('TXT_CORE_CM_PAGE_TYPE_FALLBACK', 'contentmanager/lang/tooltip'));
                    }

                    if (arrStatuses.length > 0) {
                        arrTipMessage.push(cx.variables.get('TXT_CORE_CM_PAGE_INFO_STATUSES', 'contentmanager/lang/tooltip')+cx.jQuery.ucfirst(arrStatuses.join(', ')));
                    }
                    if (arrTypes.length > 0) {
                        arrTipMessage.push(cx.variables.get('TXT_CORE_CM_PUBLISHING_INFO_TYPES', 'contentmanager/lang/tooltip')+cx.jQuery.ucfirst(arrTypes.join(', ')));
                    }
                    if (!objTrigger.parent().hasClass('inexistent')) {
                        if (objTrigger.hasClass('invisible')) {
                            arrTipMessage.push(cx.variables.get('TXT_CORE_CM_PAGE_INFO_ACTION_SHOW', 'contentmanager/lang/tooltip'));
                        } else {
                            arrTipMessage.push(cx.variables.get('TXT_CORE_CM_PAGE_INFO_ACTION_HIDE', 'contentmanager/lang/tooltip'));
                        }
                    }

                    objTip.html(arrTipMessage.join('<br />'));

                } else if (objTrigger.hasClass('jstree-move')) {
                    objTip.html(cx.variables.get('TXT_CORE_CM_PAGE_MOVE_INFO', 'contentmanager/lang/tooltip'));
                } else if (objTrigger.hasClass('translation')) {
                    objTip.html(cx.variables.get('TXT_CORE_CM_TRANSLATION_INFO', 'contentmanager/lang/tooltip'));
                } else if (objTrigger.hasClass('preview')) {
                    objTip.html(cx.variables.get('TXT_CORE_CM_PREVIEW_INFO', 'contentmanager/lang/tooltip'));
                } else if (objTrigger.hasClass('name')) {
                    objTip.html(objTrigger.text());
                }

                if (objTip.html() === '') {
                    return false;
                }
            }
        });

        if (cx.jQuery.browser.msie  && parseInt(cx.jQuery.browser.version, 10) === 7) {
            zIndex = cx.jQuery('#site-tree li').length * 10;
            cx.jQuery('#site-tree li').each(function() {
                cx.jQuery(this).children('.jstree-wrapper').css('zIndex', zIndex);
                cx.jQuery(this).children('a, ins').css('zIndex', zIndex + 1);
                zIndex -= 10;
            });
        }
        if (
          firstLoad &&
          cx.variables.get("showLocaleTagsByDefault", "contentmanager") == "on"
        ) {
            cx.cm.switchTagDropdown(true);
            firstLoad = false;
        } else {
            cx.cm.resetExpandedTable();
            cx.cm.switchTagDropdown(false);
        }
    })
    .bind("loaded.jstree", function(event, data) {
        if (open_all) {
            cx.jQuery("#site-tree").jstree("open_all");
        }
        cx.cm.is_opening = false;
        cx.jQuery("#site-tree").show();
        cx.tools.StatusMessage.removeAllDialogs();

        var setPageTitlesWidth = setInterval(function() {
            if (cx.jQuery('#content-manager').hasClass('edit_view') && cx.jQuery('#site-tree .name').length) {
                cx.jQuery('#site-tree .name').each(function() {
                    width    = cx.jQuery(this).width();
                    var data = cx.jQuery(this).parent().attr('data-href');
                    if (data == null) {
                        clearInterval(setPageTitlesWidth);
                        return;
                    }
                    level    = cx.jQuery.parseJSON(data).level;
                    maxWidth = 228 - ((level - 1) * 18) - 26;
                    if (width >= maxWidth) {
                        cx.jQuery(this).css('width', maxWidth + 'px');
                    }
                });
                clearInterval(setPageTitlesWidth);
            }
        }, 100);
    })
    .bind("refresh.jstree", function(event, data) {
        cx.jQuery(event.target).jstree('loaded');
    })
    .bind("set_lang.jstree", function(event, data) {
        document.cookie = "userFrontendLangId=" + data.rslt;
    })
    .bind("open_node.jstree", function(event, data) {
        if (cx.jQuery(".switch-tag-dropdown").hasClass("open")) {
            var node = cx.jQuery(data.rslt.obj);
            // adjust left position of cols (adopt from parent row)
            var alreadyExpanded = node.children(".jstree-wrapper");
            var lang = cx.cm.getCurrentLang();
            //module
            var cols = node.children("ul").find(".translations," +
            "."+lang+".module," +
            "."+lang+".preview," +
            ".actions," +
            "."+lang+".lastupdate"
            );
            cols.css("left", function() {
               return alreadyExpanded.children("."+cx.jQuery(this).attr("class").split(" ")[0]).not(".hide").css("left");
            });
            // node.children("ul").find(".module."+lang).css("left", alreadyExpanded.children(".module."+lang).css("left"));
        }
    })
    .bind("open_all.jstree", function(event, data) {
        // setTimout 0 is neccessary, otherwise the translation col width is 0
        setTimeout(function() {
            cx.cm.expandSiteStructure();
        },0);
    })
    .ajaxStart(function(){
        if (!cx.cm.is_opening) {
            cx.ui.messages.showLoad();
        }
    })
    .ajaxError(function(event, request, settings) {
    })
    .ajaxStop(function(event, request, settings){
        if (!cx.cm.is_opening) {
            cx.tools.StatusMessage.removeAllDialogs();
        }
    })
    .ajaxSuccess(function(event, request, settings) {
        try {
            response = $J.parseJSON(request.responseText);
            if (response.message) {
                cx.tools.StatusMessage.showMessage(response.message, null, 10000);
            }
        }
        catch (e) {}
    });
    if (typeof(langPreset) == 'string' && langPreset.length >= 2) {
        cx.cm.setCurrentLang(langPreset);
    }
};

cx.cm.saveToggleStatuses = function() {
    var toggleStatuses = {
        toggleTitles: cx.jQuery('#titles_container').css('display'),
        toggleType: cx.jQuery('#type_container').css('display'),
        toggleNavigation: cx.jQuery('#navigation_container').css('display'),
        toggleBlocks: cx.jQuery('#blocks_container').css('display'),
        toggleThemes: cx.jQuery('#themes_container').css('display'),
        toggleApplication: cx.jQuery('#application_container').css('display'),
        sidebar: cx.jQuery('#content-manager #cm-left').css('display')
    };
    cx.variables.set('toggleTitles', toggleStatuses['toggleTitles'], 'contentmanager/toggle');
    cx.variables.set('toggleType', toggleStatuses['toggleType'], 'contentmanager/toggle');
    cx.variables.set('toggleNavigation', toggleStatuses['toggleNavigation'], 'contentmanager/toggle');
    cx.variables.set('toggleBlocks', toggleStatuses['toggleBlocks'], 'contentmanager/toggle');
    cx.variables.set('toggleThemes', toggleStatuses['toggleThemes'], 'contentmanager/toggle');
    cx.variables.set('toggleApplication', toggleStatuses['toggleApplication'], 'contentmanager/toggle');
    cx.variables.set('sidebar', toggleStatuses['sidebar'], 'contentmanager/toggle');
    cx.jQuery.post('index.php?cmd=JsonData&object=cm&act=saveToggleStatuses', toggleStatuses);
};

CKEDITOR.on('instanceReady', function() {
    cx.cm.resizeEditorHeight();
});

cx.jQuery(window).resize(function() {
    if (cx.cm.isEditView()) {
        if (this.resizeTimeout) {
            clearTimeout(this.resizeTimeout);
        }
        this.resizeTimeout = setTimeout(function() {
            cx.cm.resizeEditorHeight();
        }, 250);
    }
});

cx.cm.resizeEditorHeight = function() {
    var windowHeight = cx.jQuery(window).height();
    var contentHeightWithoutEditor =
        cx.jQuery('#header').outerHeight(true) +
        parseInt(cx.jQuery('#content').css('padding-top')) +
        cx.jQuery('.breadcrumb').outerHeight(true) +
        cx.jQuery('#cm-tabs').outerHeight(true) +
        cx.jQuery('#titles_toggle').outerHeight(true) +
        (cx.jQuery('#titles_toggle').hasClass('closed') ? 0 : cx.jQuery('#titles_container').outerHeight(true)) +
        cx.jQuery('#type_toggle').outerHeight(true) +
        (cx.jQuery('#type_container .container').is(':visible') ? cx.jQuery('#type_container .container').outerHeight() : 0) +
        cx.jQuery('#buttons').outerHeight(true) +
        parseInt(cx.jQuery('#content-manager').css('padding-bottom')) +
        parseInt(cx.jQuery('#content').css('padding-bottom')) +
        cx.jQuery('#footer').outerHeight(true)
    ;
    var restHeight = windowHeight-contentHeightWithoutEditor;

    if (cx.cm.editorInUse() && CKEDITOR.status == 'basic_ready') {//resize ckeditor
        var ckeditorSpacing =
            parseInt(cx.jQuery('.cke_wrapper').css('padding-top')) +
            cx.jQuery('#cke_top_cm_ckeditor').outerHeight(true) +
            cx.jQuery('#cke_bottom_cm_ckeditor').outerHeight(true) +
            parseInt(cx.jQuery('.cke_wrapper').css('padding-bottom'))
        ;
        if (restHeight > 400) {
            ckeditorHeight = restHeight - ckeditorSpacing;
            if (cx.jQuery.browser.msie && cx.jQuery.browser.version == 9 ) {
                ckeditorHeight = ckeditorHeight - 1;
            }
        } else if (restHeight < 400) {
            ckeditorHeight = 400 - ckeditorSpacing;
        }
        cx.jQuery('#cke_contents_cm_ckeditor').css('height', ckeditorHeight + 'px');
    } else {//resize textarea
        textareaSpacing = (cx.jQuery('#cm_ckeditor').outerHeight(true) - cx.jQuery('#cm_ckeditor').height());
        if (restHeight > 400) {
            textareaHeight = restHeight - textareaSpacing;
        } else if (restHeight < 400) {
            textareaHeight = 400 - textareaSpacing;
        }
        cx.jQuery('#cm_ckeditor').css('height', textareaHeight + 'px');
    }
};

cx.cm.validateFields = function() {
    var error = false;
    var errorMessage = cx.variables.get('TXT_CORE_CM_VALIDATION_FAIL', 'contentmanager/lang');
    var firstError = true;
    var fields = [cx.jQuery("#page_name"), cx.jQuery("#page_title")];
    cx.jQuery.each(fields, function(index, el) {
        el.removeClass("warning");
        if (el.val() == "") {
            error = true;
            el.addClass("warning");

            if (firstError) {
                var tabName = el.closest(".ui-tabs-panel").attr("id");
                cx.cm.selectTab(tabName.substr(5));
            }
            firstError = false;
        }
    });
    if (cx.cm.homeCheck(true, cx.jQuery("#pageId").val())) {
        error = true;
        if (firstError) {
            errorMessage = cx.variables.get('TXT_CORE_CM_HOME_FAIL', 'contentmanager/lang');

            var tabName = cx.jQuery("#page_application").closest(".ui-tabs-panel").attr("id");
            cx.cm.selectTab(tabName.substr(5));
        }
        firstError = false;
    }
    if (error) {
        cx.tools.StatusMessage.showMessage(errorMessage, 'error', 10000);
    }
    return !error;
}

cx.cm.performAction = function(action, pageId, nodeId) {
    var pageElement = cx.jQuery("a#" + pageId);
    var pageLang = pageElement.attr("class").split(" ")[0];
    var page = cx.cm.getPageStatus(nodeId, pageLang);
    var url = "index.php?cmd=JsonData&object=page&act=set&action=" + action + "&pageId=" + pageId;
    switch (action) {
        case "new":
            cx.cm.showEditView(true);
            cx.jQuery('.tab.page_history').hide();
            cx.jQuery("#parent_node").val(nodeId);
            cx.cm.createEditor();
            cx.cm.setEditorData("");
            return;
        case "copy":
            url = "index.php?cmd=JsonData&object=node&act=copy&id=" + nodeId;
            break;
        case "activate":
            // do not try to activate inexisting pages, open them in editor instead
            if (!page.existing) {
                cx.cm.setCurrentLang(pageLang);
                cx.cm.loadPage(undefined, nodeId, null, "content");
                return;
            }
            // intentionally no "break" here!
        case "deactivate":
            // do not toggle activity for drafts
            if (page.publishing.hasDraft != "no") {
                return;
            }
            break;
        case "show":
        case "hide":
            // do not try to activate inexisting pages, open them in editor instead
            if (!page.existing) {
                cx.cm.setCurrentLang(pageLang);
                cx.cm.loadPage(undefined, nodeId, null, "content");
                return;
            }
        case "publish":
            // nothing to do yet
            break;
        case "delete":
            if (!cx.cm.confirmDeleteNode()) {
                return;
            }
            var currentNodeId = cx.jQuery('input#pageNode').val();
            url = "index.php?cmd=JsonData&object=node&act=delete&action=" + action + "&id=" + nodeId + "&currentNodeId=" + currentNodeId;
            break;
        default:
            // do not perform unknown actions
            alert("Unknown action \"" + action + "\"");
            return;
    }
    cx.trigger("loadingStart", "contentmanager", {});
    cx.jQuery.ajax({
        url: url,
        dataType: "json",
        type: "POST",
        data: url,
        success: function(json) {
            switch (action) {
                case "show":
                    page.visibility.visible = true;
                    break;
                case "hide":
                    page.visibility.visible = false;
                    break;
                case "publish":
                    if (publishAllowed) {
                        page.publishing.published = true;
                    } else {
                        page.publishing.hasDraft = "waiting";
                    }
                    break;
                case "activate":
                    page.publishing.published = true;
                    break;
                case "deactivate":
                    page.publishing.published = false;
                    break;
                case "copy":
                    cx.cm.createJsTree();
                    break;
                case "delete":
                    page.deleted = true;
                    if (json.data.deletedCurrentPage) {
                        cx.cm.hideEditView();
                    }
                    break;
                default:
                    // do not perform unknown actions
                    alert("Unknown action \"" + action + "\"");
                    return;
            }
            cx.cm.updateTreeEntry(page);
            cx.trigger("loadingEnd", "contentmanager", {});
        }
    });
}

cx.cm.updatePageIcons = function(args) {
    var node = cx.jQuery("#node_" + args.page.nodeId);
    var page = node.children("a." + args.page.lang);

    if (!args.page.existing) {
        page.addClass("inexistent");
    } else {
        page.removeClass("inexistent");
    }

    // reload the editor values, in case a page had been loaded into the editor
    if (
        args.page.id > 0 &&
        args.page.id == cx.jQuery('input#pageId').val()
    ) {
        cx.cm.loadPage(args.page.id, undefined, args.page.version, undefined, false);
    }
}

cx.cm.updatePagesIcons = function(args) {
    for (var i = 0; i < args.pages.length; i++) {
        var pageId = args.pages[i].id;
        var arg = {page: args.pages[pageId]};
        cx.cm.updatePageIcons(arg);
    }
}

cx.cm.updateTranslationIcons = function(args) {
    var node = cx.jQuery("#node_" + args.page.nodeId);
    var page = node.children("a." + args.page.lang);
    var translationIcon = page.siblings(".jstree-wrapper").children(".translations").find(".translation." + args.page.lang);

    // reset classes
    translationIcon.attr("class", "translation " + args.page.lang);
    // set new status
    if (args.page.deleted || !args.page.publishing.published) {
        translationIcon.addClass("unpublished");
    }
    if (cx.jQuery.inArray(args.page.publishing.hasDraft, ["yes", "waiting"]) >= 0) {
        translationIcon.addClass("draft");
    }
    if (!args.page.existing) {
        translationIcon.addClass("inexistent");
    }
}

cx.cm.updateTranslationsIcons = function(args) {
    for (var i = 0; i < args.pages.length; i++) {
        var pageId = args.pages[i].id;
        var arg = {page: args.pages[pageId]};
        cx.cm.updateTranslationIcons(arg);
    }
}

cx.cm.updateActionMenu = function(args) {
    // actions menu is always in frontend lang, not in page lang (so it is "per node")
    if (args.page.lang != cx.cm.getCurrentLang()) {
        args.page = cx.cm.getPageStatus(args.page.nodeId, cx.cm.getCurrentLang());
    }

    var node = cx.jQuery("#node_" + args.page.nodeId);
    var menu = node.children(".jstree-wrapper").children(".actions").children(".actions-expanded").children("ul");

    if (!menu.length) {
        return;
    }

    // reset menu
    menu.html("");

    // add actions
    menu.append(cx.jQuery("<li class=\"action-item\">").addClass("new").text(cx.variables.get("new", "contentmanager/lang/actions")));
    menu.append(cx.jQuery("<li class=\"action-item\">").addClass("copy").text(cx.variables.get("copy", "contentmanager/lang/actions")));
    if (!args.page.publishing.locked) {
        if (args.page.publishing.hasDraft == "no") {
            if (args.page.publishing.published) {
                menu.append(cx.jQuery("<li class=\"action-item\">").addClass("deactivate").text(cx.variables.get("deactivate", "contentmanager/lang/actions")));
            } else {
                menu.append(cx.jQuery("<li class=\"action-item\">").addClass("activate").text(cx.variables.get("activate", "contentmanager/lang/actions")));
            }
        } else {
            menu.append(cx.jQuery("<li class=\"action-item\">").addClass("publish").text(cx.variables.get("publish", "contentmanager/lang/actions")));
        }
        if (args.page.visibility.visible) {
            menu.append(cx.jQuery("<li class=\"action-item\">").addClass("hide").text(cx.variables.get("hide", "contentmanager/lang/actions")));
        } else {
            menu.append(cx.jQuery("<li class=\"action-item\">").addClass("show").text(cx.variables.get("show", "contentmanager/lang/actions")));
        }
        menu.append(cx.jQuery("<li class=\"action-item\">").addClass("delete").text(cx.variables.get("delete", "contentmanager/lang/actions")));
        menu.find('li.action-item').click(function(event) {
            var classes = cx.jQuery(event.target).attr('class').split(/\s+/);
            var lang    = cx.jQuery('#site-tree').jstree('get_lang');
            var action  = classes[1];
            var pageId  = cx.jQuery(event.target).closest('.jstree-wrapper').nextAll('a.' + lang).attr('id');
            var nodeId  = cx.jQuery(event.target).closest('.jstree-wrapper').parent().attr('id').split('_')[1];

            cx.cm.performAction(action, pageId, nodeId);

            setTimeout(function(){
                cx.jQuery(event.target).closest('.actions-expanded').hide();
            }, 100);
        });
    }
}

cx.cm.updateActionMenus = function(args) {
    for (var i = 0; i < args.pages.length; i++) {
        var pageId = args.pages[i].id;
        var arg = {page: args.pages[pageId]};
        cx.cm.updateActionMenu(arg);
    }
}

/**
 * Updates the publishing and visibility status of a page
 * Base structure for a status is
 * page: {
 *     id: {id},
 *     lang: {lang},
 *     nodeId: {id},
 *     existing: true|false,
 *     deleted: true|false,
 *     publishing: {
 *         locked: true|false,
 *         published: true|false,
 *         hasDraft: no|yes|waiting
 *     },
 *     visibility: {
 *         visible: true|false,
 *         broken: true|false,
 *         protected: true|false,
 *         fallback: true|false,
 *         type: standard|application|home|redirection
 *     }
 * }
 * id is optional, nodeId and lang are not!
 * publishing.locked and visibility.protected are both optional, default is the current value
 * @param int pageId ID of the page to update
 * @param object newStatus New status as array, see method description
 * @return boolean True on success, false otherwise
 */
cx.cm.updateTreeEntry = function(newStatus) {
    // get things we won't change
    var node = cx.jQuery("#node_" + newStatus.nodeId);
    if (!node.length) {
        // we don't have such a node, so our data must be outdated --> reload()
        cx.cm.createJsTree();
        // no need to trigger any event, createJsTree will do that on load
        return true;
    }
    var page = node.children("a." + newStatus.lang);
    var pageId = page.attr("id");
    var nodeId = newStatus.nodeId;
    var pageLang = page.attr("class").split(" ")[0];
    var publishing = page.children("ins.publishing");
    var visibility = page.children("ins.page");

    // get temporary helpers
    var tmpPublishingStatus = publishing.attr("class");
    var tmpVisibilityStatus = visibility.attr("class");

    // get things we will change
    var lockingStatus = publishing.hasClass("locked");
    var protectionStatus = visibility.hasClass("protected");

    // handle special cases
    if (!newStatus.existing) {}
    if (newStatus.deleted) {
        /** we don't care for now, we just reload the tree */
        cx.cm.createJsTree();
        // no need to trigger any event, createJsTree will do that on load
        return true;
    }

    if (newStatus.publishing.locked == undefined) {
        newStatus.publishing.locked = lockingStatus;
    }
    if (newStatus.publishing.published == undefined) {
        // Illegal publishing state
        return false;
    }
    if (cx.jQuery.inArray(newStatus.publishing.hasDraft, ["no", "yes", "waiting"]) < 0) {
        // Illegal draft state
        return false;
    }
    if (newStatus.visibility.protected == undefined) {
        newStatus.visibility.protected = protectionStatus;
    }
    if (newStatus.visibility.visible == undefined) {
        // Illegal visibility state
        return false;
    }
    if (newStatus.visibility.broken == undefined) {
        // Illegal broken state
        return false;
    }
    if (newStatus.visibility.fallback == undefined) {
        // Illegal fallback state
        return false;
    }
    if (cx.jQuery.inArray(newStatus.visibility.type, ["standard", "application", "home", "redirection", "symlink"]) < 0) {
        // Illegal type
        return false;
    }
    if (newStatus.name != "" && newStatus.publishing.hasDraft == "no") {
        page.children(".name").text(newStatus.name);
    }

    // set css classes
    publishing.attr("class", "");
    publishing.addClass("jstree-icon");
    publishing.addClass("publishing");
    visibility.attr("class", "");
    visibility.addClass("jstree-icon");
    visibility.addClass("page");
    if (newStatus.publishing.locked) {
        publishing.addClass("locked");
    }
    if (!newStatus.publishing.published) {
        publishing.addClass("unpublished")
    }
    switch (newStatus.publishing.hasDraft) {
        case "waiting":
            publishing.addClass("waiting");
        case "yes":
            publishing.addClass("draft");
            break;
        default:
            break;
    }
    if (!newStatus.existing) {
        publishing.addClass("inexistent");
    }
    if (!newStatus.visibility.visible) {
        visibility.addClass("invisible");
    }
    if (newStatus.visibility.broken) {
        visibility.addClass("broken");
    }
    if (newStatus.visibility.fallback) {
        visibility.addClass("fallback");
    }
    if (newStatus.visibility.protected) {
        visibility.addClass("protected");
    }
    switch (newStatus.visibility.type) {
        case "application":
        case "home":
        case "redirection":
        case "symlink":
            visibility.addClass(newStatus.visibility.type);
        default:
            break;
    }

    // update publishing/visibility in dropdown as well
    var translation = node.find(".translations-expanded .translation."+pageLang);
    translation.children("ins.publishing").attr("class", publishing.attr("class"));
    translation.children("ins.page").attr("class", visibility.attr("class"));


    // make sure IDs are correct
    newStatus.id = pageId;
    newStatus.lang = pageLang;
    newStatus.nodeId = nodeId;

    cx.trigger("pageStatusUpdate", "contentmanager", {page: newStatus});

    // return
    return true;
}

/**
 * @see cx.cm.updateTreeEntry()
 * @param array pageIds List of page IDs
 * @param array newStatuses List of new statuses ({pageId}=>{status})
 */
cx.cm.updateTreeEntries = function(newStatuses) {
    /** we don't care for now, we just reload the tree */
    cx.cm.createJsTree();
    cx.trigger("pagesStatusUpdate", "contentmanager", {pages: newStatuses});
}

/**
 * Reads the status of a page
 * @param int pageId ID of the page you wan't the state of
 * @return object page: {
 *     id: {id},
 *     existing: true|false,
 *     deleted: false,
 *     publishing: {
 *         locked: true|false,
 *         published: true|false,
 *         hasDraft: no|yes|waiting
 *     },
 *     visibility: {
 *         visible: true|false,
 *         broken: true|false,
 *         protected: true|false,
 *         fallback: true|false,
 *         type: standard|application|home|redirection
 *     }
 * }
 * deleted is true if no page with that ID could be found or the ID is 0
 * if an error occurs, null is returned
 */
cx.cm.getPageStatus = function(nodeId, lang) {
    var node = cx.jQuery("#node_" + nodeId);
    var page = node.children("a." + lang);
    var pageId = page.attr("id");
    if (!page || !page.length || pageId == 0) {
        return {
            id: 0,
            lang: lang,
            name: "",
            nodeId: nodeId,
            existing: false,
            deleted: false,
            publishing: {
                locked: false,
                published: false,
                hasDraft: "no"
            },
            visibility: {
                visible: false,
                broken: true,
                protected: false,
                fallback: false,
                type: "standard"
            }
        };
    }

    var publishing = page.children("ins.publishing");
    var visibility = page.children("ins.page");

    if (!publishing || !visibility) {
        // ins elements do not exists, state unknown, abort!
        return null;
    }

    var hasDraft = "no";
    if (publishing.hasClass("draft")) {
        if (publishing.hasClass("waiting")) {
            hasDraft = "waiting";
        } else {
            hasDraft = "yes";
        }
    }

    var type = "standard";
    if (visibility.hasClass("application")) {
        type = "application";
    } else if (visibility.hasClass("home")) {
        type = "home";
    } else if (visibility.hasClass("redirection")) {
        type = "redirection";
    } else if (visibility.hasClass("symlink")) {
        type = "symlink";
    }

    var name = "";
    if (page.children(".name").length) {
        name = page.children(".name").text();
    } else {
        name = cx.jQuery.trim(page.text());
    }

    return {
        id: pageId,
        lang: lang,
        name: name,
        nodeId: nodeId,
        existing: true,
        deleted: false,
        publishing: {
            locked: publishing.hasClass("locked"),
            published: !publishing.hasClass("unpublished"),
            hasDraft: hasDraft
        },
        visibility: {
            visible: !visibility.hasClass("invisible") && !visibility.hasClass("inactive"),
            broken: visibility.hasClass("broken"),
            protected: visibility.hasClass("protected"),
            fallback: visibility.hasClass("fallback"),
            type: type
        }
    };
}

/**
 * Returns the nodeId for the given pageId
 * @param int pageId ID of a page
 * @return int nodeId or null
 */
cx.cm.getNodeId = function(pageId) {
    if (!pageId || pageId == 0) {
        return null;
    }
    var page = cx.jQuery("a#" + pageId);
    if (!page || !page.length) {
        return null;
    }
    var node = page.parent("li");
    // if pageId is something like "new" we won't find a node
    if (!node || !node.length) {
        return null;
    }
    return node.attr("id").substr(5);
}

/**
 * Returns the contact formId for the given pageId
 * @param int pageId ID of a page
 * @returns int FormId or null
 */
cx.cm.getcontactFormId = function(pageId) {
    if (!pageId || pageId == 0) {
        return null;
    }
    var page = cx.jQuery("a#" + pageId);
    if (!page || !page.length) {
        return null;
    }

    formId = 0;
    module = cx.jQuery.trim(cx.jQuery.parseJSON(page.attr("data-href")).module);
    formId = module.split(" ")[1];

    return formId;
}

/**
 * Returns the element on which we use .jstree
 * @return object jQuery object
 */
cx.cm.getTree = function() {
    return cx.jQuery("#site-tree");
}

/**
 * Returns the current lang selected in contentmanager
 * @return string Language in the form "en"
 */
cx.cm.getCurrentLang = function() {
    return cx.cm.getTree().jstree("get_lang");
}

/**
 * Sets the current lang to the specified one
 * @param string newLang New language in format "de"
 */
cx.cm.setCurrentLang = function(newLang) {
    cx.cm.getTree().jstree("set_lang", newLang);
    cx.jQuery('.chzn-select').trigger("chosen:updated");
}

/**
 * Selects an editor tab
 * @param string tab Tab identifier
 * @param boolean push (optional) Wheter to push this to browser history or not, default is true
 */
cx.cm.selectTab = function(tab, push) {
    if (push == undefined) {
        push = true;
    }
    var tabElement = cx.jQuery(".tab.page_" + tab);
    if (tabElement) {
        var adjusting = cx.cm.historyAdjusting;
        cx.cm.historyAdjusting = true;
        tabElement.click();
        cx.cm.historyAdjusting = adjusting;
    }
    if (push) {
        cx.cm.pushHistory("tab", false);
    }
}

cx.cm.isEditView = function() {
    return cx.jQuery("#content-manager").hasClass("edit_view");
}

cx.cm.showEditView = function(forceReset) {
    cx.jQuery(".jstree-wrapper.active").removeClass("active");
    if (!cx.cm.isEditView()) {
        cx.jQuery("#content-manager").addClass("edit_view");
        cx.cm.resetEditView();
    } else if (forceReset) {
        cx.cm.resetEditView();
    }
    cx.jQuery('#multiple-actions-strike').hide();
    cx.jQuery('.jstree .actions .label, .jstree .actions .arrow').hide();

    cx.jQuery('#site-tree .name').each(function() {
        width    = cx.jQuery(this).width();
        data     = cx.jQuery(this).parent().attr('data-href');
        if (data == null) {
            return;
        }
        level    = cx.jQuery.parseJSON(data).level;
        maxWidth = 228 - ((level - 1) * 18) - 26;
        if (width >= maxWidth) {
            cx.jQuery(this).css('width', maxWidth + 'px');
        }
    });
}

cx.cm.resetEditView = function() {
    // reset all input fields
    cx.jQuery("form#cm_page input").not('#buttons input').each(function(index, el) {
        el = cx.jQuery(el);
        var type = el.attr("type");
        var id = el.attr("id");
        if (type != "hidden") {
            if (id == undefined) {
                // this only happens if we have an error in the template
                // (input field without id --> label won't work!)
                //alert(el.attr("name"));
            }
            if (type == "checkbox") {
                // uncheck all checkboxes
                el.attr("checked", false);
            } else if (type == "radio") {
                // do not clear val of radio buttons
            } else {
                // empty all text inputs
                el.val("");
            }
        }
    });
    // empty all textareas
    cx.jQuery("form#cm_page textarea").each(function(index, el) {
        el = cx.jQuery(el);
        el.val("");
    });

    // reset hidden fields
    cx.jQuery("input#pageId").val("new");
    cx.jQuery("input#pageLang").val(cx.jQuery('.chzn-select').val());
    cx.jQuery("input#pageNode").val("");
    cx.jQuery("input#source_page").val("new");
    cx.jQuery("input#parent_node").val("");
    cx.jQuery("input#page[type]").val("off");
    // reset page type
    cx.jQuery("input#type_content").click();
    // remove application
    cx.jQuery("select#page_application").val("");
    // show seo details
    cx.jQuery("#page_metarobots").attr("checked", true);
    cx.jQuery("#metarobots_container").show();
    // same for scheduled publishing
    cx.jQuery("#scheduled_publishing_container").hide();
    // reset theme
    cx.jQuery("select#page_skin").val("");
    cx.jQuery("select#page_custom_content").val("");
    // reset multiselects
    var options = cx.jQuery("select#frontendGroupsms2side__dx").html();
    cx.jQuery("select#frontendGroupsms2side__sx").html(options);
    cx.jQuery("select#frontendGroupsms2side__dx").html("");
    options = cx.jQuery("select#backendGroupsms2side__dx").html();
    cx.jQuery("select#backendGroupsms2side__sx").html(options);
    cx.jQuery("select#backendGroupsms2side__dx").html("");
    // (re-)load access data into multiselect
    cx.cm.loadAccess(cx.jQuery.parseJSON(cx.variables.get('cleanAccessData', 'contentmanager')).data);
    // (re-)load block data into multiselect
    var data = {"groups": cx.jQuery.parseJSON(cx.variables.get('availableBlocks', 'contentmanager')).data,"assignedGroups": []};
    fillBlockSelect(cx.jQuery('#pageBlocks'), data);
    // hide refuse button by default
    cx.jQuery('#page input#refuse').hide();

    // remove unused classes
    cx.jQuery(".warning").removeClass("warning");

    // switch to content tab
    cx.cm.selectTab("content", false);

    // remove or show language dropdown
    if (cx.cm.isEditView()) {
        cx.jQuery("#site-language .chzn-container").show();
    } else {
        if (cx.jQuery(".chzn-select").chosen().children("option").length == 1) {
            cx.jQuery("#site-language .chzn-container").hide();
        }
    }
}

cx.cm.hideEditView = function() {
    if (cx.jQuery('#content-manager').hasClass('sidebar-show')) {
        cx.cm.toggleSidebar();
    }
    cx.jQuery("#content-manager").removeClass("edit_view");
    cx.jQuery('#multiple-actions-strike').show();
    cx.jQuery('.jstree .actions .label, .jstree .actions .arrow').show();
    cx.cm.resetEditView();
    cx.cm.pushHistory("tab");

    cx.jQuery('#site-tree .name').each(function() {
        cx.jQuery(this).css('width', 'auto');
    });
};

cx.cm.toggleSidebar = function() {
    cx.jQuery('#content-manager #cm-left').toggle();
    cx.jQuery('#content-manager').toggleClass('sidebar-show sidebar-hide');
};

cx.cm.toggleEditor = function() {
    if (cx.jQuery('#page_sourceMode').prop('checked')) {
        cx.cm.destroyEditor();
    } else {
        cx.cm.createEditor();
    }
};

cx.cm.editorInUse = function() {
    if (typeof(CKEDITOR.instances.cm_ckeditor) == 'undefined') {
        return false;
    } else {
        return true;
    }
};

cx.cm.createEditor = function() {
    if (!cx.cm.editorInUse()) {
        var buildUrl = function(base, key, value) {
            var sep = (base.indexOf('?') > -1) ? '&' : '?';
            return base + sep + key + '=' + value;
        }
        var config = {
            customConfig: buildUrl(cx.variables.get('ckeditorconfigpath', 'contentmanager'), 'pageId', cx.jQuery('#pageId').val()),
            toolbar: 'Full',
            removePlugins: 'bbcode'
        };
        CKEDITOR.replace('page[content]', config);

        cx.cm.resizeEditorHeight();
    }
};

cx.cm.destroyEditor = function() {
    if (cx.cm.editorInUse()) {
        try {
            CKEDITOR.instances.cm_ckeditor.destroy();
        } catch (e) {
            // this is a bug in CKEDITOR. Until we apply the patch we just catch it. See http://dev.ckeditor.com/ticket/6203
        }
    }
};

cx.cm.setEditorData = function(pageContent) {
    cx.jQuery(document).ready(function() {
        var eventData = {content: pageContent};
        cx.trigger("setEditorData", "contentmanager", eventData);
        pageContent = eventData.content;
        if (!cx.jQuery('#page_sourceMode').prop('checked') && cx.cm.editorInUse()) {
            // This is bit of a hacky solution but CKEDITOR seems to have
            // problems with setData() sometimes (without throwing an exception)
            // and this seems to do the trick.
            CKEDITOR.instances.cm_ckeditor.setData(pageContent, function() {
                CKEDITOR.instances.cm_ckeditor.setData(pageContent);
            });
        } else {
            cx.jQuery('#page textarea[name="page[content]"]').val(pageContent);
        }
    });
};

cx.cm.getEditorData = function() {
    if (!cx.jQuery('#page_sourceMode').prop('checked') && cx.cm.editorInUse()) {
        return CKEDITOR.instances.cm_ckeditor.getData();
    } else {
        return cx.jQuery('#page textarea[name="page[content]"]').val();
    }
}

cx.cm.showEditModeWindow = function(cmdName, pageId) {
    var dialog = cx.variables.get("editmodedialog", 'contentmanager');
    if (dialog) {
        return;
    }
    var csrf = cx.variables.get("csrf", "contrexx");
    var title = cx.variables.get("editmodetitle", "contentmanager");
    var content = cx.variables.get("editmodecontent", "contentmanager");

    var editModeLayoutLink = "cx.cm.hideEditModeWindow(); cx.cm.loadPage(" + pageId + ", null, null, 'content'); return false;";
    var editModeModuleLink = "index.php?cmd=" + cmdName + "&csrf=" + csrf;

    // Redirect to edit page of the contact form if module is contact
    if (cmdName == 'Contact') {
        var contactFormId  = cx.cm.getcontactFormId(pageId);
        editModeModuleLink = "index.php?cmd=" + cmdName + "&act=forms&tpl=edit&formId=" + contactFormId + "&csrf=" + csrf;

    // Redirect to media module for media1, 2, 3 and 4
    } else if (/Media[1-4]/.exec(cmdName)) {
        var archiveId = /Media([1-4])/.exec(cmdName)[1];
        editModeModuleLink = "index.php?cmd=Media&archive=archive" + archiveId + "&csrf=" + csrf;
    }

    content = content.replace(/\%1/g, editModeLayoutLink);
    content = content.replace(/\%2/g, editModeModuleLink);

    dialog = cx.ui.dialog({
        dialogClass: 'edit-mode',
        title: title,
        width: 400,
        content: content,
        autoOpen: true,
        modal: true
    });
    cx.jQuery('.ui-dialog #edit_mode a').blur();

    dialog.bind("close", function() {
        cx.variables.set("editmodedialog", null, "contentmanager");
    });
    cx.variables.set("editmodedialog", dialog, "contentmanager");
}

cx.cm.hideEditModeWindow = function() {
    var dialog = cx.variables.get("editmodedialog", "contentmanager");
    if (!dialog) {
        return;
    }
    dialog.close();
}

cx.cm.loadHistory = function(id, pos) {
    if (!pos) {
        pos = 0;
    }

    var hideDrafts = "";
    if (cx.jQuery("#hideDrafts").length) {
        if (cx.jQuery("#hideDrafts").is(":checked")) {
            hideDrafts = "&hideDrafts=on";
        } else {
            hideDrafts = "&hideDrafts=off";
        }
    }

    cx.jQuery("#page_history").html("<div class=\"historyInit\"><img src=\"" + cx.variables.get('basePath', 'contrexx') + "lib/javascript/jquery/jstree/themes/default/throbber.gif\" alt=\"Loading...\" /></div>");
    pageId = (id != undefined) ? parseInt(id) : parseInt(cx.jQuery('#pageId').val());
    if (isNaN(pageId) || (pageId == 0)) {
        return;
    }

    cx.jQuery('#page_history').load('index.php?cmd=JsonData&object=page&act=getHistoryTable&page='+pageId+'&pos='+pos+hideDrafts, function() {
        cx.jQuery("#history_paging").find("a").each(function(index, el) {
            el = cx.jQuery(el);
            var pos;
            if (el.attr("class") == "pagingFirst") {
                pos = 0;
            } else {
                pos = el.attr("href").match(/pos=(\d*)/)[1];
            }
            el.data("pos", pos);
        }).attr("href", "#").click(function() {
            cx.cm.loadHistory(id, cx.jQuery(this).data("pos"));
        });
        cx.cm.updateHistoryTableHighlighting();
        cx.jQuery("#hideDrafts").change(function(event) {
            // Exclude non-human events or history is loaded twice
            if (event.originalEvent === undefined) {
                return;
            }
            cx.cm.loadHistory(id, pos);
        });
    });
};

cx.cm.loadPage = function(pageId, nodeId, historyId, selectTab, reloadHistory) {
    cx.cm.resetEditView();
    var url = '?cmd=JsonData&object=page&act=get&page='+pageId+'&node='+nodeId+'&lang='+cx.jQuery("#site-tree").jstree("get_lang")+'&userFrontendLangId='+cx.jQuery("#site-tree").jstree("get_lang");
    if (historyId) {
        url += '&history=' + historyId;
    }

    if (reloadHistory == undefined) {
        reloadHistory = true;
    }

    cx.trigger("loadingStart", "contentmanager", {});
    cx.jQuery.ajax({
        url : url,
        complete : function(response) {
            var page = cx.jQuery.parseJSON(response.responseText);
            if (page.status == "success") {
                cx.cm.pageLoaded(page.data, selectTab, reloadHistory, historyId);
            }
            if (page.status == "error" && page.message)  {
                cx.tools.StatusMessage.showMessage(page.message, "error", 10000);
            } else if (page.message) {
                cx.tools.StatusMessage.showMessage(page.message, null, 10000);
            }
            cx.cm.updateHistoryTableHighlighting();
            cx.trigger("loadingEnd", "contentmanager", page);
        }
    });
};
cx.cm.pageLoaded = function(page, selectTab, reloadHistory, historyId) {
    cx.cm.showEditView();
    cx.cm.page = page;

    // make sure history tab is shown
    cx.jQuery('.tab.page_history').show();

    if (cx.jQuery('#page input[name="page[lang]"]').val() != page.lang) {
        // lang has changed, preselect correct entry in lang select an reload tree
        cx.jQuery("#site-tree").jstree("set_lang", page.lang);
        cx.jQuery('.chzn-select').val(page.lang);
        cx.jQuery('#language_chzn').remove();
        cx.jQuery('#language').val(page.lang).change().removeClass('chzn-done').chosen();
    }
    var str = "";
    cx.jQuery("select.chzn-select option:selected").each(function () {
        str += cx.jQuery(this).attr('value');
    });
    if (fallbacks[str]) {
        cx.jQuery('.hidable_nofallback').show();
        cx.jQuery('#fallback').text(language_labels[fallbacks[str]]);
    } else {
        cx.jQuery('.hidable_nofallback').hide();
    }

    // set toggle statuses
    var toggleElements = new Array(
        ['toggleTitles', '#titles_container'],
        ['toggleType', '#type_container'],
        ['toggleNavigation', '#navigation_container'],
        ['toggleBlocks', '#blocks_container'],
        ['toggleApplication', '#application_container'],
        ['toggleThemes', '#themes_container']
    );
    cx.jQuery.each(toggleElements, function() {
        if (cx.jQuery(this[1]).css('display') !== cx.variables.get(this[0], 'contentmanager/toggle')) {
            cx.jQuery(this[1]).css('display', cx.variables.get(this[0], 'contentmanager/toggle'));
            cx.jQuery(this[1]).prevAll('.toggle').first().toggleClass('open closed');
        }
    });

    // set sidebar status
    if (cx.jQuery('#content-manager #cm-left').css('display') !== cx.variables.get('sidebar', "contentmanager/toggle")) {
        cx.jQuery('#content-manager #cm-left').css('display', cx.variables.get('sidebar', "contentmanager/toggle"));
        cx.jQuery('#content-manager').toggleClass('sidebar-show sidebar-hide');
    }

    // tab content
    if (!historyId) {
        historyId = page.historyId;
    }
    cx.jQuery('#page input[name="page[id]"]').val(page.id);
    cx.jQuery('#page input[name="page[historyId]"]').val(historyId);
    cx.jQuery('#page input[name="page[lang]"]').val(page.lang);
    cx.jQuery('#page input[name="page[node]"]').val(page.node);
    cx.jQuery('#page input[name="page[name]"]').val(page.name);
    cx.jQuery('#page input[name="page[title]"]').val(page.title);
    cx.jQuery('#page input[name="page[contentTitle]"]').val(page.contentTitle);

    cx.jQuery('#page input[name="page[type]"][value="'+page.type+'"]').trigger('click');
    cx.jQuery('#page select[name="page[application]"]').val(page.module);
    cx.jQuery('#page input[name="page[area]"]').val(page.area);

    cx.cm.setPageTarget(page.target, page.target_path);

    // tab seo
    cx.jQuery('#page input[name="page[metarobots]"]').prop('checked', page.metarobots);
    if (page.metarobots) {
        cx.jQuery("#metarobots_container").show();
    } else {
        cx.jQuery("#metarobots_container").hide();
    }
    cx.jQuery('#page input[name="page[metatitle]"]').val(page.metatitle);
    cx.jQuery('#page textarea[name="page[metadesc]"]').val(page.metadesc);
    cx.jQuery('#page textarea[name="page[metakeys]"]').val(page.metakeys);
    cx.jQuery('#page input[name="page[metaimage]"]').val(page.metaimage);

    // tab access protection
    cx.jQuery('#page input[name="page[protection_frontend]"]').prop('checked', page.frontend_protection);
    cx.jQuery('#page input[name="page[protection_backend]"]').prop('checked', page.backend_protection);

    // tab settings
    cx.jQuery('#page input[name="page[scheduled_publishing]"]').prop('checked', page.scheduled_publishing);
    if (page.scheduled_publishing) {
        cx.jQuery('#page input[name="page[scheduled_publishing]"]').parent().nextAll('.container').first().show();
    }
    cx.jQuery('#page input[name="page[start]"]').val(page.start);
    cx.jQuery('#page input[name="page[end]"]').val(page.end);

    cx.jQuery('#page select[name="page[skin]"]').val(page.skin);
    cx.cm.pageSkin = page.skin;

    if (page.useSkinForAllChannels == '1') {
        cx.jQuery('#page input[name="page[useSkinForAllChannels]"]').attr('checked', 'checked');
    } else {
        cx.jQuery('#page input[name="page[useSkinForAllChannels]"]').removeAttr('checked');
    }
    cx.jQuery('#page select[name="page[skin]"]').trigger('change');

    cx.jQuery('#page input[name="page[cssName]"]').val(page.cssName);

    if (page.module === 'Home') {
        cx.jQuery(".content_template_info").html('home.html');
    } else {
        cx.jQuery(".content_template_info").html('content.html');
    }

    cx.jQuery('#page input[name="page[caching]"]').prop('checked', page.caching);

    cx.jQuery('#page select[name="page[link_target]"]').val(page.linkTarget);
    cx.jQuery('#page input[name="page[slug]"]').val(page.slug);
    cx.jQuery('#page input[name="page[cssNavName]"]').val(page.cssNavName);

    cx.jQuery("#page span#page_slug_breadcrumb").html(cx.jQuery("#site-tree").jstree("get_lang") + '/' + page.parentPath);

    cx.jQuery('#page input[name="page[sourceMode]"]').prop('checked', page.sourceMode);
    cx.cm.toggleEditor();
    cx.cm.setEditorData(page.content);
    cx.cm.resizeEditorHeight();

    // .change doesn't fire if a checkbox is changed through .prop. This is a workaround.
    cx.jQuery(':checkbox').trigger('change');

    if (reloadHistory) {
        cx.jQuery('#page_history').empty();
    }

    if (page.editingStatus == 'hasDraftWaiting') {
        cx.jQuery('#page input#refuse').show();
    } else {
        cx.jQuery('#page input#refuse').hide();
    }

    if (page.type == 'redirect' || page.type == 'symlink') {
        cx.jQuery('#preview').hide();
    }
    cx.jQuery('#page #preview').attr('href', cx.variables.get('basePath', 'contrexx') + page.lang + '/' + page.parentPath + page.slug + '?pagePreview=1');

    cx.cm.loadAccess(page.accessData);

    var data = {"groups": cx.jQuery.parseJSON(cx.variables.get('availableBlocks', 'contentmanager')).data,"assignedGroups": page.assignedBlocks};
    fillBlockSelect(cx.jQuery('#pageBlocks'), data);

    /*                'editingStatus' =>  $page->getEditingStatus(),
                'display'       =>  $page->getDisplay(),
                'active'        =>  $page->getActive(),*/

    var container = cx.jQuery("div.page_alias").first().parent();
    var field = cx.jQuery("div.page_alias").first();
    if (cx.jQuery("div.page_alias").length > 1) {
        // remove all alias fields
        field.children("input").val('');
        field.children(".noedit").html('');
        cx.jQuery("div.page_alias").remove();
        container.append(field);
    }
    cx.jQuery(page.aliases).each(function(index, alias) {
        // add a new field
        var myField = field.clone(true);
        myField.children("input").val(alias);
        myField.children("input").attr("id", "page_alias_" + index);
        myField.children("span.noedit").html(alias);
        field.before(myField);
    });

    // If alias management is forbidden
    if (!aliasManagementAllowed) {
        // never show the "new alias" field
        jQuery("div.page_alias").show().last().hide();

        // show alias title if there are any aliases
        jQuery("label[for=page_alias]").parent().toggle(!!page.aliases.length);

        // show if aliases
        jQuery("#page_alias_container").toggle(!!page.aliases.length);
    }

    if (selectTab != undefined) {
        cx.cm.selectTab(selectTab);
    } else {
        // will be done by selectTab too
        cx.cm.pushHistory('tab');
    }
    cx.jQuery("#node_" + page.node).children(".jstree-wrapper").addClass("active");
    cx.jQuery('html, body').animate({scrollTop:0}, 'slow');

    // store page's locale in last used locales
    var lastUsedLocales = '';
    if (!!Cookies.get('lastUsedLocales')) {
        lastUsedLocales = Cookies.get('lastUsedLocales');
    }
    if (lastUsedLocales.length) {
        lastUsedLocales = lastUsedLocales.split(",");
    } else {
        lastUsedLocales = [];
    }
    // push used lang to begin of array
    lastUsedLocales.unshift(page.lang);
    // drop dublicate entries
    lastUsedLocales = lastUsedLocales.filter(function(el, index, arr) {
        return index === arr.indexOf(el);
    });
    // convert to string and store last used locales in cookie
    lastUsedLocales = lastUsedLocales.join(",");
    Cookies.set('lastUsedLocales', lastUsedLocales);

    cx.cm.updateLocaleSelect();
};

cx.cm.setPageTarget = function(pageTarget, pageTargetPath) {
    if (pageTarget == null) {
        pageTarget = "";
    }
    cx.jQuery('.page_target_backup').val(pageTarget);
    cx.jQuery('#page_target_protocol > option').removeAttr("selected");

    var matchesPageTarget = regExpUriProtocol.exec(pageTarget);
    if (matchesPageTarget) {
        cx.jQuery('#page_target_protocol > option[value="' + matchesPageTarget[0] + '"]').attr("selected", "selected");
        pageTarget = pageTarget.replace(matchesPageTarget[0], "");
    } else {
        var pageTargetOptionValue = "";
        if (pageTarget == "") {
            pageTargetOptionValue = "http://";
        }
        cx.jQuery('#page_target_protocol > option[value="' + pageTargetOptionValue + '"]').attr("selected", "selected");
    }
    if (pageTarget != "") {
        cx.jQuery('.page_target_text').text(pageTargetPath).attr('href', function() {return cx.jQuery(this).text()});
        cx.jQuery('#page_target_wrapper').hide().next().show();
    }
    cx.jQuery('#page_target').val(pageTarget);
}

cx.cm.loadAccess = function(accessData) {
    cx.jQuery('.ms2side__div').remove();

    fillSelect(cx.jQuery('#frontendAccessGroups'), accessData.frontend);
    fillSelect(cx.jQuery('#backendAccessGroups'), accessData.backend);
}

cx.cm.confirmDeleteNode = function() {
    return confirm(cx.variables.get('confirmDeleteQuestion', "contentmanager/lang"));
}

cx.cm.askRecursive = function() {
    return confirm(cx.variables.get("recursiveQuestion", "contentmanager/lang/actions"));
}

cx.cm.historyPushes = 0;

cx.cm.historyAdjusting = false;

cx.cm.pushHistory = function(source) {
    // pushHistory("tab") is always called last, so we wait for that
    if (source != "tab" || cx.cm.historyAdjusting) {
        return;
    }
    cx.cm.historyAdjusting = true;
    var History = window.History;

    // get state
    var activeTabName = cx.jQuery("#cm-tabs li.ui-tabs-selected").children('a').attr('href');
    activeTabName = activeTabName.split("_")[1];
    var activePageId = cx.jQuery('#pageId').val();
    var activeLanguageId = cx.jQuery("#site-tree").jstree("get_lang");
    var activeVersion = cx.jQuery("#historyId").val();
    var oldPageId = undefined;
    try {
        oldPageId = /[?&]page=(\d+)/.exec(window.location)[1];
    } catch (e) {}
    var oldTabName = undefined;
    try {
        oldTabName = /[?&]tab=([^&]*)/.exec(window.location)[1];
    } catch (e) {}
    var oldVersion = undefined;
    try {
        oldVersion = /[?&]version=(\d+)/.exec(window.location)[1];
    } catch (e) {}

    // prevent state from being written twice
    if (activeTabName == oldTabName && oldPageId == activePageId && oldVersion == activeVersion) {
        cx.cm.historyAdjusting = false;
        return;
    }

    // push state
    if (!cx.cm.isEditView()) {
        History.pushState({
            state:cx.cm.historyPushes
        }, document.title, "?cmd=ContentManager" + "&userFrontendLangId=" + activeLanguageId + "&csrf=" + cx.variables.get("csrf", "contrexx"));
    } else if (activePageId == "new" || activePageId == "" || activePageId == "0") {
        var node = "";
        var act = "&act=new";
        if (cx.jQuery("#parent_node").val() != "") {
            node = "&node=" + cx.jQuery("#parent_node").val();
        } else if (cx.jQuery("#pageNode").val() != "") {
            act = "";
            node = "&node=" + cx.jQuery("#pageNode").val();
        }
        History.pushState({
            state:cx.cm.historyPushes
        }, document.title, "?cmd=ContentManager" + act + "&userFrontendLangId=" + activeLanguageId + node + "&tab=" + activeTabName + "&csrf=" + cx.variables.get("csrf", "contrexx"));
    } else {
        var version = "";
        if (cx.jQuery("#historyId").val() != "") {
            version = "&version=" + cx.jQuery("#historyId").val();
        }
        History.pushState({
            state:cx.cm.historyPushes
        }, document.title, "?cmd=ContentManager&page=" + activePageId + version + "&tab=" + activeTabName + "&csrf=" + cx.variables.get("csrf", "contrexx"));
    }
    cx.cm.historyPushes++;
}

cx.cm.hashChangeEvent = function(pageId, nodeId, lang, version, activeTab) {
    // do not push history during change
    if (cx.cm.historyAdjusting) {
        cx.cm.historyAdjusting = false;
        return;
    }

    cx.cm.historyAdjusting = true;

    if (lang != undefined) {
        cx.jQuery("#site-tree").jstree("set_lang", lang);
    }

    // load leaf if necessary
    if (pageId != undefined) {
        if (pageId != cx.jQuery("#pageId").val() || version != cx.jQuery("#historyId").val()) {
            cx.cm.loadPage(pageId, undefined, version, activeTab);
        }
    } else if (nodeId != undefined && pageId != undefined && lang != undefined) {
        cx.cm.loadPage(undefined, node, version, activeTab);
    } else if (cx.jQuery.getUrlVar("act") == "new") {
        // make sure history tab is hidden
        cx.jQuery('.tab.page_history').hide();
        // load empty editor
        cx.cm.showEditView();
    } else {
        cx.cm.hideEditView();
    }
    cx.cm.selectTab(activeTab, false);

    cx.cm.historyAdjusting = false;
}

cx.cm.initHistory = function() {
    var History = window.History;
    History.Adapter.bind(window, "statechange", function() {
        var state = History.getState();
        var url = state.url;
        var urlParams = url.split("?")[1].split("&");
        var params = [];
        cx.jQuery.each(urlParams, function(index, el) {
            el = el.split("=");
            params[el[0]] = el[1];
        });
        var pageId = params["page"];
        var lang = params["userFrontendLangId"];
        var activeTab = params["tab"];
        var version = params["version"];
        var nodeId = params["node"];
        cx.cm.hashChangeEvent(pageId, nodeId, lang, version, activeTab);
    });
}

cx.cm.updateHistoryTableHighlighting = function() {
    var version = cx.jQuery("#historyId").val();
    if (version == "" || version == "new") {
        cx.jQuery('.historyLoad, .historyPreview').first().parent().children().hide();
        return;
    }
    var hasHidden = false;
    cx.jQuery('.historyLoad, .historyPreview').each(function () {
        if ((cx.jQuery(this).attr('id') == 'load_' + version) || (cx.jQuery(this).attr('id') == 'preview_' + version)) {
            cx.jQuery(this).css('display', 'none');
            hasHidden = true;
        } else {
            cx.jQuery(this).css('display', 'block');
        }
    });
    if (!hasHidden) {
        if (cx.jQuery('#load_' + (version - 1)).length > 0) {
            cx.jQuery('#load_' + (version - 1)).hide();
        }
        if (cx.jQuery('#preview_' + (version - 1)).length > 0) {
            cx.jQuery('#preview_' + (version - 1)).hide();
        }
    }
}

cx.cm.slugify = function(string) {
    // replace international characters
    cx.jQuery.each(cx.variables.get("charReplaceList"), function(search, replace) {
        string = string.replace(search, replace);
    });
    // replace spaces
    string = string.replace(/\s+/g, '-');
    // replace all non-url characters
    string = string.replace(/[^a-zA-Z0-9-_]/g, '');
    return string;
}

/**
 * Locks the ContentManager in order to prevent user input
 */
cx.cm.lock = function() {
    cx.jQuery("#cm-load-lock").show();
}

/**
 * Unlocks the ContentManager in order to allow user input
 */
cx.cm.unlock = function() {
    cx.jQuery("#cm-load-lock").hide();
}

/**
 * Returns the id of the parent page or undefined if none
 */
cx.cm.getParentPageId = function(pageId) {
    return cx.jQuery("#" + pageId).
        parent().                               // node
        parent().                               // <ul>
        parent().                               // parent node
        children("." + cx.cm.getCurrentLang()). // parent page
        attr("id");
}

/**
 * Returns the slug for the given page id
 */
cx.cm.getPageSlug = function(pageId) {
    return cx.jQuery("#" + pageId).
        data().
        href.
        slug;
}

/**
 * Returns recursive path for page id
 */
cx.cm.getPagePath = function(pageId) {
    var path = "";
    while (pageId) {
        path = "/" + cx.cm.getPageSlug(pageId) + path;
        pageId = cx.cm.getParentPageId(pageId);
    }
    return path;
}

/**
 * Sets new meta image from media browser callback
 * @param data
 */
cx.cm.setSelectedMetaimage = function (data) {
    if (data.type == 'file') {
        var url = data.data[0].datainfo;
        document.getElementById('page_metaimage').value = url.filepath;
    }
}

/**
 * Places the last used locales (stored in cookie) on top of the select
 */
cx.cm.updateLocaleSelect = function() {
    // place last used locales on top of select
    if (!!Cookies.get("lastUsedLocales")) {
        var lastUsedLocales = Cookies.get("lastUsedLocales").split(",");
        // loop over last used locales backwards
        for (var i = lastUsedLocales.length - 1; i >= 0; --i) {
            // place option on top of select
            cx.jQuery(".chzn-select").prepend(cx.jQuery(".chzn-select option[value="+ lastUsedLocales[i] + "]"));
            cx.jQuery(".translations-expanded ul").each(function() {
               jQuery(this).prepend(cx.jQuery(this).find("li." + lastUsedLocales[i]));
            });
        }
        cx.jQuery(".chzn-select").trigger("chosen:updated");
    }
}

/**
 * Expands/contracts site structure when locale tags are shown/hidden
 */
cx.cm.expandSiteStructure = function() {
    var translations = cx.jQuery("#site-tree .translations");
    // expand site-structure according to translations column width
    var difference = translations.width() - cx.jQuery("th.translation").width();
    var cols = cx.cm.getSiteStructureCols();

    if (difference >= 0) {
        var parentWidth = cx.jQuery("table.adminlist").outerWidth();
        var parentExpandedWidth = parentWidth + difference;
        var expandFactor = parentWidth / parentExpandedWidth;
        cx.jQuery("table.adminlist").width(parentExpandedWidth);

        // adjust cols
        cols.css("left", function() {
            var oldLeft = cx.jQuery(this).position().left;
            if (cx.jQuery(this).hasClass("translations")) {
                var newLeft = oldLeft * expandFactor;
            } else {
                var newLeft = oldLeft * expandFactor + difference;
            }
            // adjust table headers
            var thEl = null;
            switch (true) {
                case cx.jQuery(this).hasClass('translations'):
                    thEl = cx.jQuery("th.translation");
                    break;
                case cx.jQuery(this).hasClass('module'):
                    thEl = cx.jQuery("th.module");
                    break;
                case cx.jQuery(this).hasClass('preview'):
                    thEl = cx.jQuery("th.preview");
                    break;
                case cx.jQuery(this).hasClass('actions'):
                    thEl = cx.jQuery("th.actions");
                    break;
            }
            if (thEl) {
                thEl.css({
                    "position": "absolute",
                    "left": newLeft + "px",
                });
            }
            return newLeft + "px";
        });
    } else {
        cx.cm.resetExpandedTable();
    }
}

/**
 * Resets the expanded tables width and the positions of its headers and cols
 */
cx.cm.resetExpandedTable = function() {
    // table headers
    tableHeaders = cx.cm.getSiteStructureHeaders();
    tableHeaders.removeAttr("style");
    // cols
    cols = cx.cm.getSiteStructureCols();
    cols.removeAttr("style");
    // table
    cx.jQuery("table.adminlist").removeAttr("style");
}

/**
 * Gets the headers of the site structure table
 * @returns {jQuery} The selected ths
 */
cx.cm.getSiteStructureHeaders = function() {
    return cx.jQuery("th.page," +
      "th.translation," +
      "th.module," +
      "th.preview," +
      "th.actions," +
      "th.lastupdate"
    );
}

/**
 * Gets the (shown) cols of the site structure table
 * @returns {jQuery} The selected cols
 */
cx.cm.getSiteStructureCols = function() {
    return cx.jQuery("#site-tree .translations," +
      "#site-tree .module," +
      "#site-tree .preview," +
      "#site-tree .actions," +
      "#site-tree .lastupdate"
    ).not(".hide");
}

/**
 * Handles the switching between the locale tags and the dropdown
 * @param toggle Wether to switch or not
 */
cx.cm.switchTagDropdown = function(toggle) {
    // do not render locale-selection-dropdown in single-locale-mode
    if (cx.jQuery('#content-manager').hasClass('cm-single-locale')) {
        return;
    }
    var switchTagDropdown = cx.jQuery(".switch-tag-dropdown");
    var translations = cx.jQuery("#site-tree .translations");

    if (toggle) {
        // toggle open class
        cx.jQuery(switchTagDropdown).toggleClass("open");
    }
    if (switchTagDropdown.hasClass("open")) { // tags
      // show tags
      translations.removeClass("dropdown");
      translations.find(".translations-expanded").hide();
      translations.children(".translation").show();
    } else {
      // show dropdown
      translations.addClass("dropdown");
      translations.children(".translation").hide();
    }
    cx.cm.expandSiteStructure();
}

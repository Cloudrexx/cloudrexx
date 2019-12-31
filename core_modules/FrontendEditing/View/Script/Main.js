/**
 * Frontend Editing
 * @author: Ueli Kramer <ueli.kramer@comvation.com>
 * @version: 1.0
 * @package: cloudrexx
 * @subpackage: coremodules_frontendediting
 */

/**
 * Set CKEDITOR configuration, so the inline editing will not start automatically
 * @type {boolean}
 */
CKEDITOR.disableAutoInline = true;

/**
 * Init the extra plugin array
 * @type Array|Array
 */
var extraPlugins = [];

/**
 * Init the frontend editing when DOM is loaded and ready
 */
cx.ready(function() {
    cx.fe();
});

/**
 * Init the editor
 * Do some configurations and start the editor
 * Open the toolbar if it was active already
 */
cx.fe = function() {
    /**
     * Lang vars which are used for the template
     * write language variables from php to javascript variable
     * @type {Array}
     */
    cx.fe.langVars = cx.variables.get("langVars", "FrontendEditing");

    /**
     * Used as flag
     * The editor is not active by default
     * @type {boolean}
     */
    cx.fe.editMode = false;

    /**
     * Object for the page state which is published
     * Used as a temporary storage for page data
     * @type {{}}
     */
    cx.fe.publishedPage = {};

    /**
     * Object for the block state which is published
     * Used as a temporary storage for block data
     * @type {{}}
     */
    cx.fe.publishedBlocks = {};

    /**
     * The state of the toolbar
     * Default: closed
     * @type {boolean}
     */
    cx.fe.toolbar_opened = false;

    // add the ckeditor plugins
    // not used at the moment, perhaps later when it will be possible to edit blocks
//    cx.fe.addCustomPlugins();

    // add the toolbar, hide the anchors and hide the action buttons
    cx.fe.toolbar();

    // hide state icon
    cx.jQuery("#fe_state_wrapper").hide();

    cx.jQuery("#fe_toolbar").show();
};

/**
 * The current focused editor
 * @type CKEDITOR.editor
 */
cx.fe.currentEditor = null;

/**
 * Init the ckeditor for the content and title element
 * this line is necessary for the start and end method of contentEditor
 */
cx.fe.contentEditor = function() {
};

cx.fe.contentEditor.loadPageEditors = false;

/**
 * Start content editor
 */
cx.fe.contentEditor.start = function(pageEditor) {
    // set a flag to true
    cx.fe.editMode = true;

    cx.fe.contentEditor.loadPageEditors = pageEditor;

    // check for publish permission and add publish button
    // not used at the moment, perhaps later if it is possible to edit blocks
    extraPlugins = [];
//    var extraPlugins = ["save"];
//    if (cx.variables.get("hasPublishPermission", "FrontendEditing")) {
//        extraPlugins.push("publish");
//    }

    // init empty js object for storing the data
    cx.fe.publishedPage = {};

    // init the editors if the editor is not already initialized
    cx.fe.contentEditor.initCkEditors();

    // bind event on window
    // if the user leaves the page without saving, ask him to save as draft
    cx.jQuery(window).bind("beforeunload", function() {
        if (cx.fe.pageHasBeenModified()) {
            return cx.fe.langVars.TXT_FRONTEND_EDITING_CONFIRM_UNSAVED_EXIT;
        }
    });
};

/**
 * init the ckeditor instances for the editable fields
 */
cx.fe.contentEditor.initCkEditors = function() {
    if (cx.fe.contentEditor.loadPageEditors) {
        cx.fe.contentEditor.initPageCkEditors();
    }
    cx.fe.contentEditor.initBlockCkEditors();
};

/**
 * init the ckeditor instances for the page fields (title, content)
 */
cx.fe.contentEditor.initPageCkEditors = function() {
    if (!CKEDITOR.instances.fe_title && cx.jQuery("#fe_title").length > 0) {
        CKEDITOR.inline("fe_title", {
            customConfig: CKEDITOR.getUrl(cx.variables.get("configPath", "FrontendEditing")),
            toolbar: "FrontendEditingTitle",
            forcePasteAsPlainText: true,
            extraPlugins: extraPlugins.join(","),
            removePlugins: 'bbcode',
            basicEntities: false,
            entities: false,
            entities_latin: false,
            entities_greek: false,
            on: {
                instanceReady: function(event) {
                    cx.fe.publishedPage.title = event.editor.getData();
                },
                focus: function(event) {
                    cx.fe.currentEditor = event.editor;
                    cx.fe.startPageEditing();
                }
            }
        });
    }
    if (!CKEDITOR.instances.fe_content && cx.jQuery("#fe_content").length > 0) {
        CKEDITOR.inline("fe_content", {
            customConfig: CKEDITOR.getUrl(cx.variables.get("configPath", "FrontendEditing")),
            toolbar: "FrontendEditingContent",
            extraPlugins: extraPlugins.join(","),
            removePlugins: 'bbcode',
            startupOutlineBlocks: false,
            on: {
                instanceReady: function(event) {
                    cx.fe.publishedPage.content = event.editor.getData();
                },
                focus: function(event) {
                    cx.fe.currentEditor = event.editor;
                    cx.fe.startPageEditing();
                }
            }
        });
    }

    // add border around the editable contents
    cx.jQuery("#fe_content,#fe_title").attr("contenteditable", true).addClass("fe_outline");
};

/**
 * init ckeditors for all block areas
 */
cx.fe.contentEditor.initBlockCkEditors = function() {
    cx.jQuery(".fe_block").each(function() {
        var blockId = cx.jQuery(this).attr("data-id");
        if (!CKEDITOR.instances["fe_block_" + blockId]) {
            cx.fe.publishedBlocks["fe_block_" + blockId] = {};
            cx.fe.publishedBlocks["fe_block_" + blockId].contentHtml = cx.jQuery(this).html();

            var data = {
                block: blockId,
                lang: cx.variables.get("language", "contrexx"),
                parsing: 'false'
            }
            cx.ajax(
                'Block',
                'getBlockContent',
                {
                    data: data,
                    success: function(data) {
                        // get the block json data response
                        if (data.status != 'success') {
                            return;
                        }
                        cx.jQuery("#fe_block_" + blockId).html(data.data);
                        CKEDITOR.inline("fe_block_" + blockId, {
                            customConfig: CKEDITOR.getUrl(cx.variables.get("configPath", "FrontendEditing")),
                            toolbar: "FrontendEditingContent",
                            extraPlugins: extraPlugins.join(","),
                            removePlugins: 'bbcode',
                            startupOutlineBlocks: false,
                            on: {
                                instanceReady: function(event) {
                                    cx.fe.publishedBlocks[event.editor.name].contentRaw = event.editor.getData();
                                },
                                focus: function(event) {
                                    cx.fe.startBlockEditing(event.editor);
                                }
                            }
                        });
                    }
                }
            );
        }
    });
    // add border around the editable contents
    cx.jQuery(".fe_block").attr("contenteditable", true).addClass("fe_outline");
};

/**
 * destroy all ckeditor instances for block areas
 */
cx.fe.contentEditor.destroyBlockCkEditors = function() {
    cx.jQuery.each(cx.fe.publishedBlocks, function(index, object) {
        if (CKEDITOR.instances[index]) {
            CKEDITOR.instances[index].destroy();
        }
    });
    // remove border around the editable contents
    cx.jQuery(".fe_block").attr("contenteditable", false).removeClass("fe_outline");
};

/**
 * User starts to edit page (page title or page content)
 */
cx.fe.startPageEditing = function() {
    // change value of cancel button
    cx.jQuery("#fe_toolbar_startEditMode").html(cx.fe.langVars.TXT_FRONTEND_EDITING_CANCEL_EDIT);
    // show state icon
    cx.jQuery("#fe_state_wrapper").show();
    // show and hide action buttons
    cx.fe.actionButtons.hideBlockButtons();
    cx.fe.actionButtons.showPageButtons();
    // remove all block editors
    cx.fe.contentEditor.destroyBlockCkEditors();
};

/**
 * User stops edit the page (page title or page content)
 */
cx.fe.stopPageEditing = function() {
    if (cx.fe.pageHasBeenModified()) {
    // want to save as draft?
        if (cx.fe.confirmSaveAsDraft()) {
            cx.fe.savePage();
        }
        if (cx.jQuery("#fe_title").length > 0) {
            cx.jQuery("#fe_title").html(cx.fe.publishedPage.title);
        }
        if (cx.jQuery("#fe_content").length > 0) {
            cx.jQuery("#fe_content").html(cx.fe.publishedPage.content);
        }
    }
    // change value of cancel button
    cx.jQuery("#fe_toolbar_startEditMode").html(cx.fe.langVars.TXT_FRONTEND_EDITING_FINISH_EDIT_MODE);
    // show state icon
    cx.jQuery("#fe_state_wrapper").hide();
    // show action buttons
    cx.fe.actionButtons.hidePageButtons();
    // init all editors
    cx.fe.contentEditor.initCkEditors();
};

/**
 * start editing a block area
 */
cx.fe.startBlockEditing = function(editor) {
    // change value of cancel button
    cx.jQuery("#fe_toolbar_startEditMode").html(cx.fe.langVars.TXT_FRONTEND_EDITING_CANCEL_EDIT);
    // show and hide action buttons
    cx.fe.actionButtons.hidePageButtons();
    cx.fe.actionButtons.showBlockButtons();

    if (!cx.fe.currentEditor) {
        // load html version of data
        editor.setData(cx.fe.publishedBlocks[editor.name].contentRaw);
    }
    cx.fe.currentEditor = editor;
};

/**
 * @param CKEDITOR.editor editorInstance
 */
cx.fe.stopBlockEditing = function(editorInstance) {
    if (editorInstance) {
        if (cx.fe.blockHasBeenModified(editorInstance)) {
            if (confirm(cx.fe.langVars.TXT_FRONTEND_EDITING_CONFIRM_BLOCK_SAVE)) {
                cx.fe.saveBlock(editorInstance);
            }
        }
        // load html content
        editorInstance.setData(cx.fe.publishedBlocks[editorInstance.name].contentHtml);
    }
    // change value of cancel button
    cx.jQuery("#fe_toolbar_startEditMode").html(cx.fe.langVars.TXT_FRONTEND_EDITING_FINISH_EDIT_MODE);
    // hide block action buttons
    cx.fe.actionButtons.hideBlockButtons();
    // show outlines
    cx.jQuery("#fe_content,#fe_title").attr("contenteditable", true).addClass("fe_outline");
};

/**
 * stop the edit mode
 */
cx.fe.contentEditor.stop = function() {
    // set flag to false
    cx.fe.editMode = false;

    // remove outline of editable content divs
    cx.jQuery("#fe_content,#fe_title,.fe_block").attr("contenteditable", false).removeClass('fe_outline');

    // remove history and options
    cx.fe.toolbar.hideAnchors();

    // remove status messages which are permanent
    cx.tools.StatusMessage.removeAllDialogs();

    // load published page data
    if (cx.fe.contentEditor.loadPageEditors) {
    cx.fe.loadPageData(null, true, function() {
        if (cx.fe.pageIsADraft()) {
            cx.fe.loadPageData(cx.fe.page.historyId + 1, true);
        }
    });
    }

    // load html format
    cx.jQuery(".fe_block").each(function() {
        var blockId = cx.jQuery(this).attr("data-id");
        cx.jQuery(this).html(cx.fe.publishedBlocks["fe_block_" + blockId].contentHtml);
    });

    // hide action buttons
    cx.fe.actionButtons.hidePageButtons();
    cx.fe.actionButtons.hideBlockButtons();

    // hide all destroys
    cx.jQuery.each(CKEDITOR.instances, function(index, element) {
        element.destroy();
    });

    // remove event on window
    //cx.jQuery(window).unbind();
};

/**
 * If the page has been modified (content or other options)
 * @returns {boolean}
 */
cx.fe.pageHasBeenModified = function() {
    return (CKEDITOR.instances.fe_title && CKEDITOR.instances.fe_title.getData() != cx.fe.publishedPage.title) ||
        (CKEDITOR.instances.fe_content && CKEDITOR.instances.fe_content.getData() != cx.fe.publishedPage.content) ||
        cx.jQuery("#fe_options .fe_box select[name=\"page[skin]\"]").val() != cx.fe.page.skin ||
        cx.jQuery("#fe_options .fe_box select[name=\"page[customContent]\"]").val() != cx.fe.page.customContent ||
        cx.jQuery("#fe_options .fe_box input[name=\"page[cssName]\"]").val() != cx.fe.page.cssName;
};

/**
 * If the block has been modified (content of block)
 * @param CKEDITOR.editor editorInstance
 * @returns {boolean}
 */
cx.fe.blockHasBeenModified = function(editorInstance) {
    if (!cx.fe.publishedBlocks[editorInstance.name]) {
        return false;
    }
    return editorInstance.getData() != cx.fe.publishedBlocks[editorInstance.name].contentRaw;
};

/**
 * Ask to save as draft if the content or the options has been edited
 * @returns {*}
 */
cx.fe.confirmSaveAsDraft = function() {
    return confirm(cx.fe.langVars.TXT_FRONTEND_EDITING_SAVE_CURRENT_STATE);
};

/**
 * Init the toolbar
 * Show the toolbar if the cookie for the toolbar is set what means that it was opened
 * in the last session
 */
cx.fe.toolbar = function() {
    // is toolbar already opened from last session
    cx.fe.toolbar_opened = Cookies.get("fe_toolbar") == "true";

    // if it was opened the last time, open now or hide
    if (cx.fe.toolbar_opened) {
        cx.fe.toolbar.show();
    } else {
        cx.fe.toolbar.hide();
    }

    // add click handler for toolbar tab
    cx.jQuery("#fe_toolbar_tab").click(function(e) {
        e.preventDefault();
        if (cx.fe.toolbar_opened) {
            cx.fe.toolbar.hide();
        } else {
            cx.fe.toolbar.show();
        }
    });
    cx.jQuery("a.start-frontend-editing").click(function(e) {
        e.preventDefault();
        cx.fe.toolbar.show();
        cx.jQuery("html, body").animate({ scrollTop: 0 }, 600);
    });

    // add csrf to links where needed
    cx.jQuery('#fe_metanavigation a.backend, #fe_metanavigation a.profile').each(function() {
        cx.jQuery(this).attr('href', cx.jQuery(this).attr('href') + '&csrf=' + cx.variables.get('csrf'));
    });

    // start / stop edit mode button
    cx.jQuery("#fe_toolbar_startEditMode").html(cx.fe.langVars.TXT_FRONTEND_EDITING_EDIT).click(function(e) {
        e.preventDefault();
        if (cx.fe.editMode) {
            if (!cx.fe.currentEditor) {
                // if the edit mode was active, stop the editor
                cx.jQuery(this).html(cx.fe.langVars.TXT_FRONTEND_EDITING_EDIT);
                cx.fe.contentEditor.stop();
                // show state icon
                cx.jQuery("#fe_state_wrapper").hide();
                    return;
            }

            if (cx.fe.currentEditor.name.indexOf("fe_block") < 0) {
                cx.fe.stopPageEditing();
            } else {
                cx.fe.stopBlockEditing(cx.fe.currentEditor);
            }
            // blur current editor
            cx.fe.currentEditor.focusManager.blur(true);
            cx.fe.currentEditor = null;
        } else {
            // if the edit mode was not active, start the editor
            cx.jQuery(this).html(cx.fe.langVars.TXT_FRONTEND_EDITING_FINISH_EDIT_MODE);

            // load newest version, draft or published and refresh the editor's content
            if (cx.jQuery("#fe_title").length > 0 || cx.jQuery("#fe_content").length > 0) {
                cx.fe.loadPageData(null, true, function() {
                    cx.fe.options.load();

                    // check whether the content is editable or not
                    // don't show inline editor for module pages, except home
                    // don't show inline editor if the content and title cannot be found
                    if ((
                            cx.fe.page.type == "content"
                                    || (cx.fe.page.type == "application" && cx.fe.page.module == "Home"))) {
                        // init the inline ckeditor
                            cx.fe.contentEditor.start(true);
                        cx.fe.toolbar.showAnchors(true, true); // show both anchors, history and options
                } else {
                    if (cx.jQuery("#fe_title").length > 0 || cx.jQuery("#fe_content").length > 0) {
                        cx.tools.StatusMessage.showMessage(cx.fe.langVars.TXT_FRONTEND_EDITING_MODULE_PAGE, 'info', 5000);
                    } else {
                            cx.tools.StatusMessage.showMessage(cx.fe.langVars.TXT_FRONTEND_EDITING_NO_TITLE_AND_CONTENT, 'info', 5000);
                        }
                            cx.fe.contentEditor.start(false);
                        cx.fe.toolbar.showAnchors(false, true); // only show option anchor, hide history anchor
                    }
                });
            } else {
                cx.fe.contentEditor.start(false);
            }
        }
    }).hide();

    // show start / stop button if the page is not an application and does not have any blocks to edit
    cx.fe.loadPageData(null, false, function() {
        if (cx.fe.page.type == "content" ||
            (
                cx.fe.page.type == "application" &&
                cx.fe.page.module == "Home"
            )
           ) {
            cx.jQuery("#fe_toolbar_startEditMode").show();
        }
    });

    if (cx.jQuery(".fe_block").length > 0) {
        cx.jQuery("#fe_toolbar_startEditMode").show();
    }

    // detect esc key press
    cx.jQuery(document).keyup(function(e) {
        if (e.keyCode == 27) {
            cx.fe.toolbar.hideBoxes();
        }
    });

    // init action buttons and hide them
    cx.fe.actionButtons();
    cx.fe.actionButtons.hidePageButtons();
    cx.fe.actionButtons.hideBlockButtons();

    // init history and options
    cx.fe.history();
    cx.fe.options();

    // init the admin menu anchor and box
    // not used for contrexx 3.1
//    cx.fe.adminmenu();

    cx.fe.toolbar.hideAnchors();
};

/**
 * Hide the toolbar
 */
cx.fe.toolbar.hide = function() {
    // hide anchor boxes
    cx.fe.toolbar.hideBoxes();

    if (cx.fe.toolbar_opened) {
        var toolbarOffset = parseInt(cx.jQuery("#fe_toolbar").css("top"));
    } else {
        var toolbarOffset = parseInt(cx.jQuery("body").css("padding-top"));
    }
    if (!toolbarOffset) {
        toolbarOffset = 0;
    }

    // do the css
    cx.jQuery("#fe_toolbar").css("top", (toolbarOffset - parseInt(cx.jQuery("#fe_toolbar").height())) + "px");
    cx.jQuery("body").css("padding-top", toolbarOffset + "px");

    // do the html
    cx.jQuery("#fe_toolbar_tab").html(cx.fe.langVars.TXT_FRONTEND_EDITING_SHOW_TOOLBAR);

    // save the status
    cx.fe.toolbar_opened = false;
    Cookies.set("fe_toolbar", cx.fe.toolbar_opened);
};

/**
 * Show the toolbar
 */
cx.fe.toolbar.show = function() {
    // do the css

    var toolbarOffset = parseInt(cx.jQuery("body").css("padding-top"));
    if (!toolbarOffset) {
        toolbarOffset = 0;
    }

    cx.jQuery("body").css("padding-top", (parseInt(cx.jQuery("#fe_toolbar").height()) + toolbarOffset) + "px");
    cx.jQuery("#fe_toolbar").css({
        top: toolbarOffset + "px"
    });

    // do the html
    cx.jQuery("#fe_toolbar_tab").html(cx.fe.langVars.TXT_FRONTEND_EDITING_HIDE_TOOLBAR);

    // save the status
    cx.fe.toolbar_opened = true;
    Cookies.set("fe_toolbar", cx.fe.toolbar_opened);
};


/**
 * Init the administration menu (not used for contrexx 3.1)
 */
/*
cx.fe.adminmenu = function() {
    cx.jQuery("#fe_adminmenu .fe_box").find("a").each(function(index, el) {
        cx.jQuery(el).click(function() {
            // ajax to backend link, show in cx.ui.dialog
            cx.ui.dialog({
                title: cx.jQuery(this).text(),
                modal: true,
                content: "<iframe style=\"height: 100%; width: 100%;\" src=\"" + cx.jQuery(this).attr("href").replace(/de\/index.php/, "cadmin/index.php") + "\" />"
            });
            // replace all link and form targets by this javascript handler
            return false;
        });
    });

    cx.jQuery("#fe_adminmenu").click(function() {
        if (cx.jQuery("#fe_adminmenu .fe_box").css("display") == "none") {
            cx.fe.toolbar.hideBoxes();
            cx.fe.adminmenu.show();
        }
        return false;
    }).hover(
        function() {
            clearTimeout(cx.fe.adminmenu.displayTimeout);
        },
        function() {
            cx.fe.adminmenu.displayTimeout = setTimeout(function() {
                cx.fe.adminmenu.hide();
            }, 2000);
        }
    );
};
//*/

/**
 * Show admin menu box (not used for contrexx 3.1)
 */
/*
cx.fe.adminmenu.show = function() {
    cx.jQuery("#fe_adminmenu .fe_toggle").show();
};
//*/

/**
 * Hide admin menu box (not used for contrexx 3.1)
 */
/*
cx.fe.adminmenu.hide = function() {
    cx.jQuery("#fe_adminmenu .fe_toggle").hide();
};
//*/

/**
 * hide history and options anchors
 */
cx.fe.toolbar.hideAnchors = function() {
    cx.jQuery("#fe_metanavigation .fe_anchor.fe_toggle").hide();
    cx.fe.toolbar.hideBoxes();
};

/**
 * hide boxes of anchors
 */
cx.fe.toolbar.hideBoxes = function() {
    cx.jQuery("#fe_metanavigation .fe_anchor .fe_toggle").hide();
};

/**
 * show history and options anchors
 * @param history
 * @param options
 */
cx.fe.toolbar.showAnchors = function(history, options) {
    if (history) {
        cx.jQuery("#fe_history").show();
    }
    if (options) {
        // not used for contrexx 3.1
        //cx.jQuery("#fe_options").show();
    }
};

/**
 * prepare Page for sending
 *
 * replace true and false statements to "on" and "off"
 */
cx.fe.preparePageToSend = function() {
    // load data from options box and write to page object
    if (cx.fe.editorLoaded()) {
        if (CKEDITOR.instances.fe_title) {
            cx.fe.page.title = CKEDITOR.instances.fe_title.getData();
        }
        if (CKEDITOR.instances.fe_content) {
            cx.fe.page.content = CKEDITOR.instances.fe_content.getData();
        }
    }
    cx.fe.page.application = cx.fe.page.module;
    cx.fe.page.skin = cx.jQuery("#fe_options .fe_box select[name=\"page[skin]\"]").val();
    cx.fe.page.customContent = cx.jQuery("#fe_options .fe_box select[name=\"page[customContent]\"]").val();
    cx.fe.page.cssName = cx.jQuery("#fe_options .fe_box input[name=\"page[cssName]\"]").val();

    // rewrite true and false to on and off
    cx.fe.page.scheduled_publishing = (cx.fe.page.scheduled_publishing === true ? "on" : "off");
    cx.fe.page.protection_backend = (cx.fe.page.protection_backend === true ? "on" : "off");
    cx.fe.page.protection_frontend = (cx.fe.page.protection_frontend === true ? "on" : "off");
    cx.fe.page.caching = (cx.fe.page.caching === true ? "on" : "off");
    cx.fe.page.sourceMode = (cx.fe.page.sourceMode === true ? "on" : "off");
    cx.fe.page.metarobots = (cx.fe.page.metarobots === true ? "on" : "off");
};

/**
 * This method is necessary to check whether the editors are intialized.
 * @returns {boolean}
 */
cx.fe.editorLoaded = function() {
    return CKEDITOR.instances.fe_title != undefined || CKEDITOR.instances.fe_content != undefined;
};

/**
 * load the page data
 * @param historyId
 * @param putTheData
 * @param callback
 */
cx.fe.loadPageData = function(historyId, putTheData, callback) {
    var data = {
        page: cx.variables.get("pageId", "FrontendEditing"),
        lang: cx.variables.get("language", "contrexx"),
        userFrontendLangId: cx.variables.get("language", "contrexx")
    }
    if (historyId) {
        data.history = historyId;
    }
    cx.ajax(
        'page',
        'get',
        {
            data: data,
            complete: function(response) {
                // get the page json data response
                cx.fe.page = cx.jQuery.parseJSON(response.responseText).data;

                // check whether the page is a content page or a home page
                // the application pages do not allow to update title and content
                if (putTheData && (cx.fe.page.type != "application" || cx.fe.page.module == "Home")) {
                    // put the new data of page into the html and start editor if the user is in edit mode
                    if (cx.fe.page.title) {
                        cx.jQuery("#fe_title").html(cx.fe.page.title);
                    }
                    if (cx.fe.page.content) {
                        cx.jQuery("#fe_content").html(cx.fe.page.content);
                    }
                    // when the editor is in the edit mode, restart the content editor
                    if (cx.fe.editMode) {
                        cx.fe.contentEditor.start();
                    }
                }

                // a specific history is requested
                if (historyId) {
                    cx.fe.history.loadedVersion = historyId;
                } else {
                    // no specific history requested
                    // check if the current page is a draft
                    // if it is a draft, load the previous history
                    if (cx.fe.pageIsADraft()) {
                        cx.fe.history.loadedVersion = cx.fe.page.historyId - 1;
                    } else {
                        // load the current history
                        cx.fe.history.loadedVersion = cx.fe.page.historyId;
                    }
                }

                // update the history highlighting in history box
                cx.fe.history.updateHighlighting();

                // call the callback function after loading the content from db
                if (callback) {
                    callback();
                }

                // if it is a draft tell the user that he is editing a draft
                if (cx.fe.pageIsADraft() &&
                    cx.fe.editMode &&
                    (
                        (historyId && historyId == cx.fe.page.historyId - 1) || !historyId
                        )
                    ) {
                    cx.tools.StatusMessage.showMessage(cx.fe.langVars.TXT_FRONTEND_EDITING_THE_DRAFT, 'warning', 5000);
                }

                // add icon on the right side of the publish and stop button
                cx.jQuery("#fe_state_icon")
                    .removeClass("publishing")
                    .removeClass("draft")
                    .removeClass("waiting");
                if (cx.fe.pageHasDraft()) {
                    cx.jQuery("#fe_state_icon").addClass("publishing").addClass("draft");
                    cx.jQuery("#fe_state_text").html(cx.fe.langVars.TXT_FRONTEND_EDITING_DRAFT);
                } else if(cx.fe.pageHasDraftWaiting()) {
                    cx.jQuery("#fe_state_icon").addClass("publishing").addClass("waiting");
                    cx.jQuery("#fe_state_text").html(cx.fe.langVars.TXT_FRONTEND_EDITING_DRAFT);
                } else {
                    cx.jQuery("#fe_state_icon").addClass("publishing");
                    cx.jQuery("#fe_state_text").html(cx.fe.langVars.TXT_FRONTEND_EDITING_PUBLISHED);
                }

                cx.fe.actionButtons.refresh();

                // reload the boxes
                cx.fe.history.load();
                cx.fe.options.load();
            }
        }
    );
};

/**
 * Init click handler on action buttons
 */
cx.fe.actionButtons = function() {
    cx.jQuery("#fe_toolbar_publishPage").click(function(e) {
            e.preventDefault();
            if (!cx.fe.editMode) {
                return false;
            }
            cx.fe.publishPage();
        });

    // init save as draft button and hide it
    cx.jQuery("#fe_toolbar_savePage").click(function(e) {
            e.preventDefault();
            cx.fe.savePage();
        });

    // init save block button and hide it
    cx.jQuery("#fe_toolbar_saveBlock").click(function(e) {
            e.preventDefault();
            cx.fe.saveBlock(cx.fe.currentEditor);
        });
};

/**
 * Show action buttons for page
 */
cx.fe.actionButtons.showPageButtons = function() {
    // show buttons
    cx.jQuery("#fe_toolbar_publishPage").show();
    cx.jQuery("#fe_toolbar_savePage").show();
};

/**
 * Hide action buttons for page
 */
cx.fe.actionButtons.hidePageButtons = function() {
    // hide buttons
    cx.jQuery("#fe_toolbar_publishPage").hide();
    cx.jQuery("#fe_toolbar_savePage").hide();
};

/**
 * Show action buttons for block
 */
cx.fe.actionButtons.showBlockButtons = function() {
    // show buttons
    cx.jQuery("#fe_toolbar_saveBlock").show();
};

/**
 * Hide action buttons for block
 */
cx.fe.actionButtons.hideBlockButtons = function() {
    // hide buttons
    cx.jQuery("#fe_toolbar_saveBlock").hide();
};

/**
 * Refresh the action buttons on the top left
 */
cx.fe.actionButtons.refresh = function() {
    // init publish button and hide it
    cx.jQuery("#fe_toolbar_publishPage")
        .html(
            cx.variables.get("hasPublishPermission", "FrontendEditing") ?
                cx.fe.langVars.TXT_FRONTEND_EDITING_PUBLISH :
                cx.fe.langVars.TXT_FRONTEND_EDITING_SUBMIT_FOR_RELEASE
        );

    // init save as draft button and hide it
    cx.jQuery("#fe_toolbar_savePage")
        .html(
            cx.variables.get("hasPublishPermission", "FrontendEditing") && cx.fe.pageHasDraftWaiting() ?
                cx.fe.langVars.TXT_FRONTEND_EDITING_REFUSE_RELEASE :
                cx.fe.langVars.TXT_FRONTEND_EDITING_SAVE
        );
};

/**
 * Returns true when the page is a draft, false if not
 * @returns {boolean}
 */
cx.fe.pageIsADraft = function() {
    return cx.fe.pageHasDraft() || cx.fe.pageHasDraftWaiting();
};

/**
 * Page has a draft
 * @returns {boolean}
 */
cx.fe.pageHasDraft = function() {
    return cx.fe.page.editingStatus == "hasDraft";
};

/**
 * Page has a draft waiting
 * @returns {boolean}
 */
cx.fe.pageHasDraftWaiting = function() {
    return cx.fe.page.editingStatus == "hasDraftWaiting";
};

/**
 * Does a request to publish the new contents
 */
cx.fe.publishPage = function() {
    cx.fe.preparePageToSend();

    cx.jQuery.post(
        cx.variables.get("basePath", "contrexx") + "cadmin/index.php?cmd=JsonData&object=page&act=set",
        {
            action: "publish",
            page: cx.fe.page,
            ignoreBlocks: true
        },
        function(response) {
            var className = "success";
            if (response.status != "success") {
                className = "error";
            }

            cx.tools.StatusMessage.showMessage(response.message, className, 5000);

            if(cx.fe.editorLoaded()) {
                cx.fe.publishedPage = {};
                if (CKEDITOR.instances.fe_title) {
                    cx.fe.publishedPage.title = CKEDITOR.instances.fe_title.getData();
                }
                if (CKEDITOR.instances.fe_content) {
                    cx.fe.publishedPage.content = CKEDITOR.instances.fe_content.getData();
                }
            }
            // load new page data, but don't reload and don't put data into content
            cx.fe.loadPageData(null, false);
        }
    );
};

/**
 * Save a page
 * Does a request to the page jsonadapter to put the new values into the database
 */
cx.fe.savePage = function() {
    cx.fe.preparePageToSend();

    cx.jQuery.post(
        cx.variables.get("basePath", "contrexx") + "cadmin/index.php?cmd=JsonData&object=page&act=set",
        {
            page: cx.fe.page,
            ignoreBlocks: true
        },
        function(response) {
            if (response.data != null) {
                var className = "success";
                if (response.status != "success") {
                    className = "error";
                }
                cx.tools.StatusMessage.showMessage(response.message, className, 5000);
            }
            // load new page data, but don't reload and don't put data into content
            cx.fe.loadPageData(null, false);
        }
    );
};

/**
 * Save a block
 * @param CKEditor.editor editorInstance
 */
cx.fe.saveBlock = function(editorInstance) {
    cx.jQuery.post(
        cx.variables.get("basePath", "contrexx") + "cadmin/index.php?cmd=JsonData&object=Block&act=saveBlockContent&block=" + editorInstance.name.substr(9) + "&lang=" + cx.variables.get("language", "contrexx"),
        {
            content: editorInstance.getData()
        },
        function(response) {
            var className = "success";
            if (response.status != "success") {
                className = "error";
            }
            cx.tools.StatusMessage.showMessage(response.message, className, 5000);

            if (response.data != null) {
                cx.fe.publishedBlocks[editorInstance.name].contentHtml = response.data.content;
                cx.fe.publishedBlocks[editorInstance.name].contentRaw = editorInstance.getData();
            }
            cx.fe.stopBlockEditing(editorInstance);
        }
    );
};

/**
 * Init the history. Show the box when clicking on label.
 * Hide the history box after 2 seconds
 */
cx.fe.history = function() {
    cx.jQuery("#fe_history").children("a").click(function(e) {
        e.preventDefault();
        if (!cx.fe.history.isVisible()) {
            cx.fe.toolbar.hideBoxes();
        }
        // toggle box, if it is opened hide it
        cx.fe.history.toggle();
        // load history data
        cx.fe.history.load();
    }).parent().hover(
        function() {
            clearTimeout(cx.fe.history.displayTimeout);
        },
        function() {
            cx.fe.history.displayTimeout = setTimeout(function() {
                cx.fe.history.hide();
            }, 2000);
        }
    );
};

/**
 * is the history box visible?
 * @returns {boolean}
 */
cx.fe.history.isVisible = function() {
    return cx.jQuery("#fe_history .fe_toggle").css("display") != "none";
};

/**
 * toggle history anchor
 */
cx.fe.history.toggle = function() {
    // if you use cx.jQuery.toggle instead, the timeout is not cleared (see: cx.fe.history.hide();)
    if(cx.fe.history.isVisible()) {
        cx.fe.history.hide();
    } else {
        cx.fe.history.show();
    }
};

/**
 * show history anchor
 */
cx.fe.history.show = function() {
    cx.jQuery("#fe_history .fe_toggle").show();
};

/**
 * hide the history anchor
 */
cx.fe.history.hide = function() {
    cx.jQuery("#fe_history .fe_toggle").hide();
    clearTimeout(cx.fe.history.displayTimeout);
};

/**
 * Load history and put the history into the correct container
 * @param pos
 */
cx.fe.history.load = function(pos) {
    if (!pos) {
        pos = 0;
    }

    var hideDrafts = "";
    if (cx.jQuery("#hideDrafts").length && !cx.jQuery("#hideDrafts").is(":checked")) {
        hideDrafts = "&hideDrafts=off";
    }

    cx.jQuery("#fe_history .fe_box").html(
        "<div class=\"historyInit\"><img src=\"" + cx.variables.get("basePath", "contrexx") + "lib/javascript/jquery/jstree/themes/default/throbber.gif\" alt=\"Loading...\" /></div>"
    );
    cx.jQuery("#fe_history .fe_box").load(
        cx.variables.get("basePath", "contrexx") + "cadmin/index.php?cmd=JsonData&object=page&act=getHistoryTable&page=" + cx.fe.page.id + "&pos=" + pos + "&limit=10" + hideDrafts,
        function() {
            cx.jQuery("#history_paging").find("a").each(function(index, el) {
                el = cx.jQuery(el);
                var pos;
                if (el.attr("class") == "pagingFirst") {
                    pos = 0;
                } else {
                    pos = el.attr("href").match(/pos=(\d*)/)[1];
                }
                el.data("pos", pos);
            }).attr("href", "#").click(function(e) {
                    e.preventDefault();
                    cx.fe.history.load(cx.jQuery(this).data("pos"));
                });
            cx.fe.history.updateHighlighting();
            cx.jQuery("#hideDrafts").change(function() {
                cx.fe.history.load(pos);
            });
        }
    );
};

/**
 * Remove functions for active history version
 */
cx.fe.history.updateHighlighting = function() {
    cx.jQuery(".historyLoad, .historyPreview").each(function() {
        if (cx.jQuery(this).attr("id") == "load_" + cx.fe.history.loadedVersion ||
            cx.jQuery(this).attr("id") == "preview_" + cx.fe.history.loadedVersion) {
            cx.jQuery(this).css("display", "none");
        } else {
            cx.jQuery(this).css("display", "block");
        }
    });
};

/**
 * Init the options. show the box when clicking on label.
 * Hide the options box after 2 seconds
 */
cx.fe.options = function() {
    cx.jQuery("#fe_options").children("a").click(function(e) {
        e.preventDefault();
        if (cx.jQuery("#fe_options .fe_box").css("display") != "none") {
            return false;
        }
        cx.fe.toolbar.hideBoxes();
        cx.fe.options.load();
        cx.fe.options.show();
    }).parent().hover(
        function() {
            clearTimeout(cx.fe.options.displayTimeout);
        },
        function() {
            cx.fe.options.displayTimeout = setTimeout(function() {
                cx.fe.options.hide();
            }, 2000);
        }
    );

    cx.jQuery("#fe_options .fe_box select[name=\"page[skin]\"]").bind("change", function() {
        cx.fe.options.reloadCustomContentTemplates();
    });
};

/**
 * reload the custom content templates
 */
cx.fe.options.reloadCustomContentTemplates = function() {
    var skinId = cx.jQuery("#fe_options .fe_box select[name=\"page[skin]\"]").val();
    var application = cx.jQuery("#fe_options .fe_box select[name=\"page[application]\"]").val();
    var select = cx.jQuery("#fe_options .fe_box select[name=\"page[customContent]\"]");
    select.empty();
    select.append(cx.jQuery("<option value=\"\" selected=\"selected\">(Default)</option>"));

    // Default skin
    if (skinId == 0) {
        skinId = cx.variables.get("defaultTemplate", "FrontendEditing");
    }

    var templates = cx.variables.get("contentTemplates", "FrontendEditing");
    if (templates[skinId] == undefined) {
        return;
    }

    for (var i = 0; i < templates[skinId].length; i++) {
        var isHome = /^home_/.exec(templates[skinId][i]);
        if ((isHome && application == "Home") || !isHome && application != "Home") {
            select.append(cx.jQuery("<option>", {
                value: templates[skinId][i]
            }).text(templates[skinId][i]));
        }
    }
};

/**
 * show options anchor
 */
cx.fe.options.show = function() {
    cx.jQuery("#fe_options .fe_toggle").show();
};

/**
 * hide the options anchor
 */
cx.fe.options.hide = function() {
    cx.jQuery("#fe_options .fe_toggle").hide();
};

/**
 * load the options into the options container
 */
cx.fe.options.load = function() {
    cx.fe.options.reloadCustomContentTemplates();

    cx.jQuery("#fe_options .fe_box select[name=\"page[skin]\"]").val(cx.fe.page.skin);
    cx.jQuery("#fe_options .fe_box select[name=\"page[customContent]\"]").val(cx.fe.page.customContent);
    cx.jQuery("#fe_options .fe_box input[name=\"page[cssName]\"]").val(cx.fe.page.cssName);
};

/**
 * function which is called when the user clicks on "load" in history box
 * @param version
 */
loadHistoryVersion = function(version) {
    cx.fe.loadPageData(version, true);
};

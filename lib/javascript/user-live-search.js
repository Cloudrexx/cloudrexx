cx.ready(function() {
    if ($J(".live-search-user-id").length > 0) {
        var scope         = "user/live-search";
        var userMinLength     = cx.variables.get("userMinLength",     scope);
        var userCanCancel     = cx.variables.get("userCanCancel",     scope);
        var userCanClear      = cx.variables.get("userCanClear",      scope);
        var txtUserSearch     = cx.variables.get("txtUserSearch",     scope);
        var txtUserCancel     = cx.variables.get("txtUserCancel",     scope);
        var txtUserSearchInfo = cx.variables.get("txtUserSearchInfo", scope);

        var userAddIcon   = cx.variables.get("basePath", 'contrexx') + "core/Core/View/Media/icons/icon-user-add.png";
        var userAddAlt    = "Add user";
        var userEditIcon  = cx.variables.get("basePath", 'contrexx') + "core/Core/View/Media/icons/icon-user-edit.png";
        var userEditAlt   = "Edit user";
        var userClearIcon = cx.variables.get("basePath", 'contrexx') + "core/Core/View/Media/icons/icon-user-clear.png";
        var userClearAlt  = "Clear user";

        var replaceInput = function(userInput) {
            return userInput.clone().attr({type: "hidden", class: userInput.attr("class") + "-replaced"}).prop("outerHTML");
        }

        var clearUser = function(userClear) {
            userClear.hide().prevAll(".live-search-user-title").slice(0, 1).hide();
            userClear.next(".live-search-user-name-replaced").val("");
            userClear.prevAll(".live-search-user-id-replaced").slice(0, 1).val(0);
            userClear.prev(".live-search-user-edit").addClass("live-search-user-add").children("img").attr("src", userAddIcon);
        }

        var showUser = function(userEdit) {
            userEdit.removeClass("live-search-user-add").children("img").attr({src: userEditIcon, alt: userEditAlt});
            userEdit.prev(".live-search-user-title").show();

            if (userCanClear) {
                userEdit.next(".live-search-user-clear").show();
            }
        }

        var getUserById = function(userId) {
            $J.ajax({
                url:      "index.php?cmd=JsonData&object=user&act=getUserById",
                data:     {id: userId},
                dataType: "json",
                async:    false,
                success:  function(response) {
                    userId    = response.data.id;
                    userTitle = response.data.title;
                }
            });

            return {
                id:    userId,
                title: userTitle
            };
        };

        var replaceHTML = function(userInput) {
            var userId         = userInput.val()
            var userTitle      = userInput.next(".live-search-user-name-replaced");
            var userTitleVal   = userTitle.val();
            var userTitleStyle = "";
            var userClear      = "";
            var userEditClass  = "";
            var userIcon       = userEditIcon;
            var userAlt        = userEditAlt;

            var userConditionTitle       = userTitleVal == "" || userTitleVal == undefined;
            var userConditionCanClear    = userCanClear  && userConditionTitle && userId > 0;
            var userConditionCanNotClear = !userCanClear && userConditionTitle;

            if (userConditionCanClear || userConditionCanNotClear) {
                var objUser = getUserById(userId);

                userInput.val(objUser.id);
                userTitleVal = objUser.title;
            }

            if (userCanClear) {
                var userClearStyle = "";

                if (userConditionTitle) {
                    userTitleVal   = "";
                    userTitleStyle = "style=\"display: none;\"";
                    userEditClass  = " live-search-user-add";
                    userIcon       = userAddIcon;
                    userAlt        = userAddAlt;
                    userClearStyle = "style=\"display: none;\""
                }

                userClear =
                    "<a class=\"live-search-user-clear\" href=\"#\" " + userClearStyle + ">" +
                        "<img src=\"" + userClearIcon + "\" alt=\"" + userClearAlt + "\" />" +
                    "</a>"
                ;
            }

            var replacedHTML =
                replaceInput(userInput) +
                "<span class=\"live-search-user-title\" " + userTitleStyle + ">" + userTitleVal + "</span>" +
                "<a class=\"live-search-user-edit" + userEditClass + "\" href=\"#\">" +
                    "<img src=\"" + userIcon + "\" alt=\"" + userEditAlt + "\" />" +
                "</a>" +
                userClear
            ;

            return replacedHTML;
        }

        $J(".live-search-user-id").next(".live-search-user-name").replaceWith(function() {
            return replaceInput($J(this));
        });

        $J(".live-search-user-id").replaceWith(function() {
            return replaceHTML($J(this));

        });

        var userSearchDialog = cx.ui.dialog({
            width:       500,
            height:      170,
            modal:       true,
            autoOpen:    false,
            dialogClass: "ui-dialog-live-search-user",
            title:       txtUserSearch,
            content:     "<div class=\"live-search-user-info\">" + txtUserSearchInfo + "</div>" +
                         "<input class=\"live-search-user-input ui-corner-all\" placeholder=\"" + txtUserSearch + "...\" />",
            buttons: [
                {
                    text: txtUserCancel,
                    click: function() {
                        $J(this).dialog("close");
                    }
                },
                {
                    text:  "OK",
                    click: function() {
                        var input     = $J(this).children(".live-search-user-input");
                        var value     = $J.trim(input.val());
                        var userName  = $J("input[name=" + input.attr("name").split("-")[0] + "]");
                        var userEdit  = null;

                        if (userCanClear) {
                            userEdit  = userName.prev().prev(".live-search-user-edit");
                        } else {
                            userEdit  = userName.prev(".live-search-user-edit");
                        }

                        var userTitle = userEdit.prev(".live-search-user-title");

                        if (value.length >= userMinLength) {
                            if (userEdit.hasClass("live-search-user-add")) {
                                showUser(userEdit);
                            }

                            userName.val(value);
                            userTitle.text(value).prev(".live-search-user-id-replaced").val(0);

                            var replacedInputId = userTitle.prev(".live-search-user-id-replaced").attr("id");
                            var customizedScope = replacedInputId ? scope + "/" + replacedInputId : scope;
                            cx.trigger("userSelected", customizedScope, {id: 0, name: value});

                            $J(this).dialog("close");
                        } else {
                            input.css("border", "1px solid #FF0000")
                        }
                    }
                }
            ]
        });

        var addUserAutocompletion = function(selector, userEdit, userName) {
            $J(selector).autocomplete({
                source: function(request, response) {
                    $J.getJSON("index.php?cmd=JsonData&object=user&act=getUsers", {
                        term: request.term,
                        limit: cx.variables.get("limit", scope),
                        resultFormat: cx.variables.get("resultFormat", scope),
                        searchFields: cx.variables.get("searchFields", scope),
                        searchAnd: cx.variables.get("searchAnd", scope)
                    },
                    function(data) {
                        var users = new Array();

                        for (id in data.data) {
                            var user = {
                                id: id,
                                value: data.data[id]
                            };
                            users.push(user);
                        }

                        response(users);
                    });
                },
                search: function() {
                    if ($J.trim(this.value).length < userMinLength) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    if (userEdit.hasClass("live-search-user-add")) {
                        showUser(userEdit);
                    }
                    if (userName.length) {
                        userName.val(ui.item.value);
                    }

                    userEdit.prev(".live-search-user-title").text(ui.item.value).prev(".live-search-user-id-replaced").val(ui.item.id);

                    var replacedInputId = userEdit.prevAll(".live-search-user-id-replaced").slice(0, 1).attr("id");
                    var customizedScope = replacedInputId ? scope + "/" + replacedInputId : scope;
                    cx.trigger("userSelected", customizedScope, {id: ui.item.id, name: ui.item.value});

                    userSearchDialog.close();

                    // Must be reset additionally
                    ui.item.value = "";
                }
            });
            // sort result alphabetically
            $J.ui.autocomplete.prototype._renderMenu = function(ul, items) {
                items = items.sort(function(a, b) {
                    return a.value.localeCompare(b.value);
                });
                var that = $J.ui.autocomplete.prototype;
                $J.each(items, function(index, item) {
                    that._renderItem(ul, item);
                });
            };
        }

        var openUserLiveSearch = function(userEdit) {
            var userName = null;

            if (userCanClear) {
                userName = userEdit.next().next(".live-search-user-name-replaced");
            } else {
                userName = userEdit.next(".live-search-user-name-replaced")
            }

            var dialog           = $J(".ui-dialog-live-search-user");
            var dialogButtonpane = dialog.children(".ui-dialog-buttonpane");
            var dialogButtons    = dialogButtonpane.children(".ui-dialog-buttonset").children(".ui-button");

            if (userName.length) {
                dialogButtonpane.show();
                if (dialogButtons.length > 1 && !userCanCancel) {
                    dialogButtons.slice(0, 1).hide();
                }
                $J(".live-search-user-input").attr("name", userName.attr("name") + "-search");
            } else {
                if (dialogButtons.length > 1 && userCanCancel) {
                    dialogButtons.slice(1, 2).hide();
                } else {
                    dialogButtonpane.hide();
                }
            }

            addUserAutocompletion(".live-search-user-input", userEdit, userName);
            userSearchDialog.open();
        }

        userSearchDialog.bind("open", function() {
            $J(".ui-dialog-live-search-user").children(".ui-dialog-content").css("height", "70px");
        });

        userSearchDialog.bind("close", function() {
            $J(".live-search-user-input").val("").removeAttr('name').removeAttr('style');
        });

        $J(".live-search-user-clear").click(function(event) {
            event.preventDefault();
            var replacedInputId = $J(this).prevAll(".live-search-user-id-replaced").slice(0, 1).attr("id");
            var customizedScope = replacedInputId ? scope + "/" + replacedInputId : scope;
            var id   = $J(this).prevAll(".live-search-user-id-replaced").slice(0, 1).val();
            var name = $J(this).next(".live-search-user-name-replaced").val();
            cx.trigger("userCleared", customizedScope, {id: id, name: name});
            clearUser($J(this));
        });

        $J(".live-search-user-edit").click(function(event) {
            event.preventDefault();
            openUserLiveSearch($J(this));
        });
    }
});

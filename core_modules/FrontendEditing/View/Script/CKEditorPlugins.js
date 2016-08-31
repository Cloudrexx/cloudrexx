/**
 * Frontend Editing
 * @author: Ueli Kramer <ueli.kramer@comvation.com>
 * @version: 1.0
 * @package: cloudrexx
 * @subpackage: coremodules_frontendediting
 */

/**
 * Add the custom plugins to the ckeditor
 * * the "publish" button
 * * the "save as draft" button
 */
cx.fe.addCustomPlugins = function () {
    // publish a page
    CKEDITOR.plugins.add('publish', {
        init: function (editor) {
            var pluginName = 'Publish';
            editor.addCommand(pluginName, {
                exec: function (editor) {
                    cx.fe.publishPage();
                }
            });

            editor.ui.addButton(pluginName, {
                label: cx.fe.langVars.TXT_FRONTEND_EDITING_PUBLISH,
                command: pluginName,
                className: 'cke_button_publish'
            });
        }
    });

    // save as draft
    CKEDITOR.plugins.add('save', {
        init: function (editor) {
            var pluginName = 'Save';
            editor.addCommand(pluginName, {
                exec: function (editor) {
                    cx.fe.savePage();
                }
            });

            editor.ui.addButton(pluginName, {
                label: cx.fe.langVars.TXT_FRONTEND_EDITING_SAVE,
                command: pluginName,
                className: 'cke_button_save'
            });
        }
    });
};

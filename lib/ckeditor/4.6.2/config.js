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

CKEDITOR.editorConfig = function( config ) {
  // This config.js file is only used to load the ckeditor.config.js.php file
  // for the toolbar configurator

  // Prepare the groupId and pattern variable
  var groupId = '', pattern = /&id=([0-9]+)/;
  // Check if the user is in the group managing page
  if (window.location.pathname == '/cadmin/Access/group') {
    // Verify that the user is editing a user group
    if (pattern.test(window.location.search)) {
      // Populate the groupId variable with the GET-param groupId and the
      // id of the user group that is edited
      groupId = '?id=' + parseInt(pattern.exec(window.location.search)[1]);
    }
  }
  config.customConfig = cx.variables.get('basePath', 'contrexx') + 'core/Wysiwyg/ckeditor.config.js.php' + groupId;
};

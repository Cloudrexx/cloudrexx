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

/**
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @package     cloudrexx
 * @subpackage  coremodule_alias
 */
$_ARRAYLANG['TXT_ALIAS_ALIASES'] = "Overview";
$_ARRAYLANG['TXT_ALIAS_ALIAS'] = "SEO URL Alias";
$_ARRAYLANG['TXT_ALIAS_SETTINGS'] = "Settings";
$_ARRAYLANG['TXT_ALIAS_DELETE'] = "Delete";
$_ARRAYLANG['TXT_ALIAS_MODIFY'] = "Edit";
$_ARRAYLANG['TXT_ALIAS_PAGE'] = "Page";
$_ARRAYLANG['TXT_ALIAS_ALIAS_ES'] = "Alias(es)";
$_ARRAYLANG['TXT_ALIAS_FUNCTIONS'] = "Functions";
$_ARRAYLANG['TXT_ALIAS_MODIFY_ALIAS'] = "Edit alias";
$_ARRAYLANG['TXT_ALIAS_ADD_ALIAS'] = "Create alias";
$_ARRAYLANG['TXT_ALIAS_TARGET_PAGE'] = "Target Page";
$_ARRAYLANG['TXT_ALIAS_LOCAL'] = "Local";
$_ARRAYLANG['TXT_ALIAS_URL'] = "URL";
$_ARRAYLANG['TXT_ALIAS_BROWSE'] = "Search Page";
$_ARRAYLANG['TXT_ALIAS_CONFIRM_REMOVE_ALIAS'] = "Are you sure you want to remove the alias %s?";
$_ARRAYLANG['TXT_ALIAS_ADD_ANOTHER_ALIAS'] = "Create alias";
$_ARRAYLANG['TXT_ALIAS_SAVE'] = "Save";
$_ARRAYLANG['TXT_ALIAS_CANCEL'] = "Cancel";
$_ARRAYLANG['TXT_ALIAS_ONE_ALIAS_REQUIRED_MSG'] = "You need to define at least one alias!";
$_ARRAYLANG['TXT_ALIAS_URL_REQUIRED_MSG'] = "You need to specify an URL!";
$_ARRAYLANG['TXT_ALIAS_PAGE_REQUIRED_MSG'] = "You need to choose a web page!";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_UPDATED'] = "The alias was updated successfully.";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_ADDED'] = "The alias was added successfully.";
$_ARRAYLANG['TXT_ALIAS_ALIAS_UPDATE_FAILED'] = "An error occurred while updating the alias";
$_ARRAYLANG['TXT_ALIAS_ALIAS_ADD_FAILED'] = "An error occurred while adding the alias";
$_ARRAYLANG['TXT_ALIAS_RETRY_OPERATION'] = "Try to repeat the operation";
$_ARRAYLANG['TXT_ALIAS_ALREADY_IN_USE'] = "The alias %s is already in use!";
$_ARRAYLANG['TXT_ALIAS_OPERATION_IRREVERSIBLE'] = "This action will not be reversible!";
$_ARRAYLANG['TXT_ALIAS_CONFIRM_DELETE_ALIAS'] = "Are you sure you want to remove the alias for the page %s?";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_REMOVED'] = "The alias was removed successfully.";
$_ARRAYLANG['TXT_ALIAS_ALIAS_REMOVE_FAILED'] = "An error occurred while removing the alias";
$_ARRAYLANG['TXT_ALIAS_NO_ALIASES_MSG'] = "There are no aliases defined!";
$_ARRAYLANG['TXT_ALIAS_REQUIREMENTS_DESC'] = "Using aliases, you can make a complicated URL like <b>www.yourdomain.com/index.php?page=84472</b> look more friendly. With an alias defined, the URL might become <b>www.yourdomain.com/services</b>. This not only makes it more readable, but also improves placement in search results. <br/><br/>Note: The alias administration does only work on an <strong>Apache</strong> webserver with the extension <strong>mod_rewrite</strong> enabled.<br /> Also, the usage of <strong>.htaccess</strong> files must be allowed by the server.";
$_ARRAYLANG['TXT_ALIAS_USE_ALIAS_ADMINISTRATION'] = "Use alias administration";
$_ARRAYLANG['TXT_ALIAS_APACHE_MISSING'] = "You are using another webserver than Apache. Alias management only works with Apache, so you cannot use aliases on this server.";
$_ARRAYLANG['TXT_ALIAS_MOD_REWRITE_MISSING'] = "Cloudrexx could not determine whether the extension <strong>mod_rewrite</strong> is enabled. Therefore, the alias management cannot be activated, as it would render the site inaccessible if the extension <strong>mod_rewrite</strong> was missing.<br/><br/>If you are sure that the extension <strong>mod_rewrite</strong> is available, you can enable the alias management manually. To do so, create a new file named <strong>.htaccess</strong> in the top directory of the web site with the following content:<br/><div style=\"margin: 10px;\"><code>RewriteEngine On</code></div>Next time you access alias management, it will be activated automatically.";
$_ARRAYLANG['TXT_ALIAS_HTACCESS_HINT'] = "Please assure that the server supports .htaccess files.";
$_ARRAYLANG['TXT_ALIAS_CONFIG_SUCCESSFULLY_APPLYED'] = "The configuration has been successfully applied.";
$_ARRAYLANG['TXT_ALIAS_CONFIG_FAILED_APPLY'] = "An error occured while saving the configuration!";
$_ARRAYLANG['TXT_ALIAS_TARGET_PAGE_NOT_EXIST'] = "The target page does not exist anymore!";
$_ARRAYLANG['TXT_ALIAS_MUST_NOT_BE_A_FILE'] = "The alias %s couldn't be used, because it is physically present!";
$_ARRAYLANG['TXT_ALIAS_TARGET_ALREADY_IN_USE'] = "An alias for the page %s has already been defined!";
$_ARRAYLANG['TXT_ALIAS_NOT_ACTIVE_ALIAS_MSG'] = "This alias isn't active anymore!<br />Click on this icon to reactive it.";
$_ARRAYLANG['TXT_ALIAS_STANDARD_RADIOBUTTON'] = "Use as default:";
$_ARRAYLANG['TXT_ALIAS_OPEN_ALIAS_NEW_WINDOW'] = "Open alias in a new window";
$_ARRAYLANG['TXT_ALIAS_IIS_HTACCESS_NOT_REGISTERED'] = "The file &ldquo;web.config&rdquo; is not registered in the server configuration. Please contact the server administrator and request a corresponding entry.";
$_ARRAYLANG['TXT_ALIAS_SHOW_LEGACY_PAGE_ALIASES'] = "Show legacy page aliases";
?>

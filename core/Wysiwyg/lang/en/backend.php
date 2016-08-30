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
 * This is the english language file for backend mode.
 * This file is included by Cloudrexx and all entries are set as placeholder
 * values for backend ACT template by SystemComponentBackendController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 */

global $_ARRAYLANG;

// Let's start with module info:
$_ARRAYLANG['TXT_CORE_WYSIWYG'] = 'WYSIWYG';
$_ARRAYLANG['TXT_CORE_WYSIWYG_DESCRIPTION'] = 'With this module you can manage the wysiwyg templates';

// Here come the ACTs:
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_DEFAULT'] = 'Overview';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYG_TEMPLATE'] = 'WYSIWYG Templates';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYG_TEMPLATE_TOOLTIP'] = 'Click here to create a content template , which you can select in the Editor under Templates later.';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_SETTINGS_DEFAULT'] = 'Mailing';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_SETTINGS_HELP'] = 'Help';
$_ARRAYLANG['TXT_CORE_WYSIWYG_TEMPLATE_ENTITY'] = 'WysiwygTemplate';

// Now our content specific values:
$_ARRAYLANG['TXT_CORE_WYSIWYG_CONGRATULATIONS'] = 'Overview';
$_ARRAYLANG['TXT_CORE_WYSIWYG_SUCCESSFUL_CREATION'] = 'This is the Overview/Dashboard of your new Component. More tabs will be generated if you add entities to this component.';
$_ARRAYLANG['TXT_CORE_WYSIWYG_EXAMPLE_TEMPLATE'] = 'This is the default template for this component, located in View/Template/Backend/Default.html. In order to add entities, place your YAML files in Model/Yaml folder and execute ./workbench.bat db update. Then add a language file entry for your entity.';

$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE'] = 'WYSIWYG Templates';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_ENTITY'] = 'WYSIWYG Template';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_TITLE'] = 'Title';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_DESCRIPTION'] = 'Description';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_ACTIVE'] = 'Active';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_IMAGE_PATH'] = 'Preview image';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_HTML_CONTENT'] = 'HTML';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_STATE'] = 'State';

//Settings
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_SETTINGS'] = 'Settings';
$_ARRAYLANG['TXT_CORE_WYSIWYG_SPECIFICSTYLESHEET'] = 'Consideration of specific CSS';
$_ARRAYLANG['TXT_CORE_WYSIWYG_REPLACEACTUALCONTENTS'] = 'Replace actual content in the WYSIWYG';

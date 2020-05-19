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
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_FUNCTIONS'] = 'Editor functions';

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
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_ORDER'] = 'Order';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_WYSIWYGTEMPLATE_STATE'] = 'State';

//Settings
$_ARRAYLANG['TXT_CORE_WYSIWYG_ACT_SETTINGS'] = 'Settings';
$_ARRAYLANG['TXT_CORE_WYSIWYG_SPECIFICSTYLESHEET'] = 'Consideration of specific CSS';
$_ARRAYLANG['TXT_CORE_WYSIWYG_REPLACEACTUALCONTENTS'] = 'Replace actual content in the WYSIWYG';
$_ARRAYLANG['TXT_CORE_WYSIWYG_SORTBEHAVIOUR'] = 'Sort behaviour of WYSIWYG Templates';
$_ARRAYLANG['TXT_CORE_WYSIWYG_ALPHABETICAL']  = 'Alphabetical';
$_ARRAYLANG['TXT_CORE_WYSIWYG_CUSTOM']        = 'Custom';

// Functions
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_CONFIGURATOR'] = 'Editor functions';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_FUNC_DESC'] = 'Select which functions shall be available in the WYSIWYG-editor.';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_FUNC_DESC_INFO_GROUPS'] = 'The available function-set can further be restricted per user group (under %s).';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_SAVE'] = 'Save';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_MODE'] = 'Mode';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_DOCUMENT'] = 'Empty content';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_DOCUMENT_TOOLS'] = 'Templates';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_CLIPBOARD'] = 'Clipboard';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_UNDO'] = 'Change management';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_FIND'] = 'Find & replace';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_SELECTION'] = 'Selection';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_SPELLCHECKER'] = 'Spellchecker';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_BASICSTYLES'] = 'Text styling';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_CLEANUP'] = 'Cleanup styling';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_LIST'] = 'Listes';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_INDENT'] = 'Indents';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_BLOCKS'] = 'Blocks';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_ALIGN'] = 'Alignment';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_LINKS'] = 'Links';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_INSERT'] = 'Object insertion';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_STYLES'] = 'Format';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_COLORS'] = 'Colors';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_TOOLS'] = 'Tools';
$_ARRAYLANG['TXT_WYSIWYG_TOOLBAR_BIDI'] = 'Text direction';

// GUI
$_ARRAYLANG['TXT_WYSIWYG_MODAL_OPTION_LABEL'] = 'Open image in a modal window (Shadowbox)';
$_ARRAYLANG['TXT_WYSIWYG_MODAL_OPTION_SRC'] = 'Image to display in the modal window';

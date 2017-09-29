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
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 */

global $_ARRAYLANG;

// General module info:
$_ARRAYLANG['TXT_CORE_LOCALE'] = 'Localization';
$_ARRAYLANG['TXT_CORE_LOCALE_DESCRIPTION'] = 'Handles language versions by their country and/or the effective language.';

// Module ACTs:
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_DEFAULT'] = 'Webiste';
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_LOCALE'] = 'Website';
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_BACKEND'] = 'Administration area';

// Module fields
$_ARRAYLANG['TXT_CORE_LOCALE_LOCALE_NAME'] = 'Locale';
$_ARRAYLANG['TXT_CORE_LOCALE_BACKEND_NAME'] = 'Edit languages';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_ID'] = 'ID';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_ISO1'] = 'Language';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_LABEL'] = 'Label';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_FALLBACK'] = 'Fallback';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_COUNTRY'] = 'Country';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_DEFAULT'] = 'Default';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_SOURCE_LANGUAGE'] = 'Source language';
$_ARRAYLANG['TXT_CORE_LOCALE_ACTIVE_LANGUAGES'] = 'Active languages';
$_ARRAYLANG['TXT_CORE_LOCALE_DEFAULT_LANGUAGE'] = 'Default language';

// Tooltips
$_ARRAYLANG['TXT_CORE_LOCALE_FALLBACK_TOOLTIP'] = 'Not existing content is replaced with the content of the fallback language.';
$_ARRAYLANG['TXT_CORE_LOCALE_SOURCE_LANGUAGE_TOOLTIP'] = 'Specifies which language files to use.';

$_ARRAYLANG['TXT_CORE_LOCALE_BACKEND_SELECT_ACTIVE_LANGUAGES'] = 'Select active languages...';
$_ARRAYLANG['TXT_CORE_LOCALE_CANNOT_DELETE_DEFAULT_BACKEND'] = 'The language %s was not deleted, because it is the default one.';
$_ARRAYLANG['TXT_CORE_LOCALE_ACTION_COPY'] = 'Copy contents from this language\'s fallback language to this language';
$_ARRAYLANG['TXT_CORE_LOCALE_ACTION_LINK'] = 'Link contents from this language\'s fallback language to this language';
$_ARRAYLANG['TXT_CORE_LOCALE_COPY_TITLE'] = 'Copy language';
$_ARRAYLANG['TXT_CORE_LOCALE_COPY_TEXT'] = 'Copy contents from language %1 to language %2?';
$_ARRAYLANG['TXT_CORE_LOCALE_COPY_SUCCESS'] = 'Contents were successfully copied!';
$_ARRAYLANG['TXT_CORE_LOCALE_LINK_TITLE'] = 'Link language';
$_ARRAYLANG['TXT_CORE_LOCALE_LINK_TEXT'] = 'Link contents from language %2 with language %1?';
$_ARRAYLANG['TXT_CORE_LOCALE_LINK_SUCCESS'] = 'Contents were successfully linked!';
$_ARRAYLANG['TXT_CORE_LOCALE_WARNING_TITLE'] = 'Warning';
$_ARRAYLANG['TXT_CORE_LOCALE_WARNING_TEXT'] = 'I confirm that this operation will remove all existing content pages of the language %2.';
$_ARRAYLANG['TXT_CORE_LOCALE_WAIT_TITLE'] = 'Please wait';
$_ARRAYLANG['TXT_CORE_LOCALE_WAIT_TEXT'] = 'The chosen action is executed, please wait...';
$_ARRAYLANG['TXT_YES'] = 'Yes';
$_ARRAYLANG['TXT_NO'] = 'No';
$_ARRAYLANG['TXT_CORE_LOCALE_LABEL_LANG_REMOVAL'] = 'Confirm language data removal';
$_ARRAYLANG['TXT_CORE_LOCALE_LANG_REMOVAL_CONTENT'] = 'Remove all language related data of the deactivated language from all applications.';
$_ARRAYLANG['TXT_CORE_LOCALE_SAVE'] = 'Apply changes';
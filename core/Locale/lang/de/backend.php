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
 * This is the german language file for backend mode.
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
$_ARRAYLANG['TXT_CORE_LOCALE'] = 'Lokalisierung';
$_ARRAYLANG['TXT_CORE_LOCALE_DESCRIPTION'] = 'Steuert die Sprachversionen anhand des Landes und/oder der effektiven Sprache.';

// Module ACTs:
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_DEFAULT'] = 'Website';
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_LOCALE'] = 'Website';
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_BACKEND'] = 'Administrationsoberfläche';
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_LANGUAGEFILE'] = 'Sprachplatzhalter';
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_LANGUAGEFILE_DEFAULT'] = 'Frontend';
$_ARRAYLANG['TXT_CORE_LOCALE_ACT_LANGUAGEFILE_BACKEND'] = 'Backend';

// Module ACLs:
$_ARRAYLANG['TXT_CORE_LOCALE_ACL_LIST'] = 'Anzeige Website & Administrationsoberfläche';
$_ARRAYLANG['TXT_CORE_LOCALE_ACL_MANAGEMENT'] = 'Verwaltung Website & Administrationsoberfläche';
$_ARRAYLANG['TXT_CORE_LOCALE_ACL_VARS'] = 'Verwaltung der Sprachplatzhalter';

// Module fields
$_ARRAYLANG['TXT_CORE_LOCALE_LOCALE_NAME'] = 'Sprachversion';
$_ARRAYLANG['TXT_CORE_LOCALE_BACKEND_NAME'] = 'Sprachen bearbeiten';
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_NAME'] = 'Sprachplatzhalter bearbeiten';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_ID'] = 'ID';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_ISO1'] = 'Sprache';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_LABEL'] = 'Label';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_FALLBACK'] = 'Fallback';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_COUNTRY'] = 'Land';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_DEFAULT'] = 'Standard';
$_ARRAYLANG['TXT_CORE_LOCALE_FIELD_SOURCE_LANGUAGE'] = 'Quellsprache';
$_ARRAYLANG['TXT_CORE_LOCALE_ACTIVE_LANGUAGES'] = 'Aktive Sprachen';
$_ARRAYLANG['TXT_CORE_LOCALE_DEFAULT_LANGUAGE'] = 'Standard Sprache';

// Tooltips
$_ARRAYLANG['TXT_CORE_LOCALE_FALLBACK_TOOLTIP'] = 'Nicht vorhandene Inhalte werden durch die Inhalte der Fallback-Sprache ersetzt.';
$_ARRAYLANG['TXT_CORE_LOCALE_SOURCE_LANGUAGE_TOOLTIP'] = 'Gibt an welche Sprachdateien verwendet werden.';

$_ARRAYLANG['TXT_CORE_LOCALE_BACKEND_SELECT_ACTIVE_LANGUAGES'] = 'Aktive Sprachen auswählen...';
$_ARRAYLANG['TXT_CORE_LOCALE_CANNOT_DELETE_DEFAULT_BACKEND'] = 'Die Sprache %s wurde nicht gelöscht, da diese als Standard-Sprache ausgewählt ist.';
$_ARRAYLANG['TXT_CORE_LOCALE_ACTION_COPY'] = 'Inhalte der Fallbacksprache in diese Sprache kopieren';
$_ARRAYLANG['TXT_CORE_LOCALE_ACTION_LINK'] = 'Inhalte der Fallbacksprache in diese Sprache verknüpfen';
$_ARRAYLANG['TXT_CORE_LOCALE_COPY_TITLE'] = 'Sprache kopieren';
$_ARRAYLANG['TXT_CORE_LOCALE_COPY_TEXT'] = 'Inhalt von Sprache %1 in Sprache %2 übernehmen?';
$_ARRAYLANG['TXT_CORE_LOCALE_COPY_SUCCESS'] = 'Inhalte wurden erfolgreich kopiert!';
$_ARRAYLANG['TXT_CORE_LOCALE_LINK_TITLE'] = 'Sprache verknüpfen';
$_ARRAYLANG['TXT_CORE_LOCALE_LINK_TEXT'] = 'Inhalt von Sprache %1 mit Sprache %2 verknüpfen?';
$_ARRAYLANG['TXT_CORE_LOCALE_LINK_SUCCESS'] = 'Inhalte wurden erfolgreich verknüpft!';
$_ARRAYLANG['TXT_CORE_LOCALE_WARNING_TITLE'] = 'Warnung';
$_ARRAYLANG['TXT_CORE_LOCALE_WARNING_TEXT'] = 'Ich bestätige, dass mit dieser Aktion alle bestehenden Inhaltsseiten der Sprache %2 gelöscht werden.';
$_ARRAYLANG['TXT_CORE_LOCALE_WAIT_TITLE'] = 'Bitte warten';
$_ARRAYLANG['TXT_CORE_LOCALE_WAIT_TEXT'] = 'Die gewählte Aktion wird ausgeführt, bitte haben Sie etwas Geduld...';
$_ARRAYLANG['TXT_YES'] = 'Ja';
$_ARRAYLANG['TXT_NO'] = 'Nein';
$_ARRAYLANG['TXT_CORE_LOCALE_LABEL_LANG_REMOVAL'] = 'Bitte bestätigen Sie das Entfernen der Sprachdaten!';
$_ARRAYLANG['TXT_CORE_LOCALE_LANG_REMOVAL_CONTENT'] = 'Sprachbezogene Daten der deaktivieren Sprachen in allen Anwendungen löschen.';
$_ARRAYLANG['TXT_CORE_LOCALE_SAVE'] = 'Änderungen übernehmen';

// Messages
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_SUCCESSFULLY_UPDATED'] = 'Die Sprachplatzhalter wurden erfolgreich aktualisiert.';
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_NOTHING_CHANGED'] = 'Es wurde kein Sprachplatzhalter bearbeitet.';
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_LANGUAGE_NOT_SET'] = 'Die Sprachdatei kann nicht geladen werden, weil die Quellsprache nicht gesetzt ist.';
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_EXPORT_FAILED'] = 'Exportieren der angepassten Sprachplatzhalter in die YAML-Datei schlug fehl!';
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_IMPORT_FAILED'] = 'Importieren der Sprachplatzhalter aus der YAML-Datei schlug fehl!';
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_NOT_FOUND'] = 'Sprachdatei nicht gefunden.';
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_RESET_SUCCESS'] = 'Standardwert erfolgreich geladen.';
$_ARRAYLANG['TXT_CORE_LOCALE_LANGUAGEFILE_RESET_ERROR'] = 'Standardwert nicht gefunden.';
$_ARRAYLANG['TXT_CORE_LOCALE_ADD_NEW_INFORMATION'] = 'Bevor weitere Sprachversionen hinzugefügt werden können, muss die Option %1$s (unter %2$s) zuerst aktiviert werden.';

// Translation view
$_ARRAYLANG['id'] = 'Platzhalter';
$_ARRAYLANG['sourceLang'] = 'Ausgangssprache';
$_ARRAYLANG['destLang'] = 'Zielsprache';
$_ARRAYLANG['TXT_CORE_LOCALE_RESET'] = 'Zurücksetzen';
$_ARRAYLANG['TXT_CORE_LOCALE_UNSAVED_CHANGES'] = 'Bitte klicken Sie auf "Änderungen übernehmen"';

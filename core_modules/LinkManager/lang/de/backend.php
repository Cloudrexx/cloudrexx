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
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_linkmanager
 */

global $_ARRAYLANG;

// Let's start with module info:
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER'] = 'LinkManager';
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_DESCRIPTION'] = 'Dieses Modul scannt die Webseite nach fehlerhafte Links und Bilderpfaden. (Cronjob erforderlich)';

// Here come the ACTs:
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_ACT_DEFAULT'] = 'Übersicht';
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_ACT_CRAWLERRESULT'] = 'Crawler Resultate';
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_ACT_SETTINGS'] = 'Einstellungen';

//overview page specific values:
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_CRAWLER_RUNS'] = "Crawler Runs";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_ID'] = "ID";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_STARTTIME'] = "Startzeit";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_ENDTIME'] = "Endzeit";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_TOTAL_LINKS'] = "gefundene Links";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_TOTAL_BROKEN_LINKS'] = "defekte Links gefunden";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LINKS'] = "Links";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LAST_RUN'] = "letzter Durchlauf";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LAST_CHECK'] = "letzte Prüfung";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_DURATION'] = "Dauer";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_CHECKED_LINKS'] = "geprüfte Links";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_BROKEN_LINKS'] = "defekte Links";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LABEL_START'] = "Start";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LABEL_END'] = "Ende";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_RUNS_FOUND'] = "Es existieren noch keine Durchläufe .....";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LABEL_CLOCK'] = "Uhr";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LANGUAGE'] = "Sprache";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_STATUS'] = "Status";

//crawler result specific valies
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_BROKEN_LINK'] = "defekter Link";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_FOUND_MODULE'] = "Gefunden in Modul";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_ENTRY_NAME'] = "Titel";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_STATUS_CODE'] = "Antwortcode";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_DETECTED'] = "erkannt am";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_SOLVED'] = "Gelöst";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_UPDATED_BY'] = "aktualisiert von";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_INITIAL'] = "#";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_RESULT_FOUND'] = "Keine kaputten Links gefunden .....";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_SELECT_ALL'] = "Alles markieren";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_DESELECT_ALL'] = "Auswahl entfernen";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_CHECK_AGAIN'] = "Erneut prüfen";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_DELETE_CONFORM_MSG'] = "Sind Sie sicher, dass der Link gelöscht werden soll?";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_UPDATE_SUCCESS_MSG'] = "Link updated successfully";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LABEL_LOADING'] = "Laden...";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_CRAWLED_PAGE'] = "geprüfte Seite";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NON_EXISTING_DOMAIN'] = "Die Domain existiert nicht";

//settings page specific values:
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_GENERAL'] = "Allgemeine Einstellungen";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_ENTRIES_PER_PAGE'] = "Anzahl Einträge pro Seite";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_LABEL_SAVE'] = "Speichern";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_SUCCESS_MSG'] = "Änderungen wurden erfolgreich gespeichert";

//Linkcrawler script specific values:
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_IMAGE'] = "Kein Bild";
$_ARRAYLANG['TXT_CORE_MODULE_LINKMANAGER_NO_LINK'] = "Kein Link";

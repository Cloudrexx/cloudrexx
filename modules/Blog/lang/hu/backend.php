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
 * @subpackage  module_blog
 */
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_TITLE'] = "Allgemein";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION'] = "Anzahl Zeichen in Einleitungstext";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION_HELP'] = "Dieser Wert legt die Anzahl der Zeichen im Einleitungstext fest. Wenn Sie immer den kompletten Text anzeigen lassen wollen, so setzen Sie hier die Zahl 0 ein.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TITLE'] = "Kommentare";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW'] = "Kommentare erlauben";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_HELP'] = "Wenn Sie Ihren Besuchern die Möglichkeit geben wollen, zu Ihren Beiträgen Kommentare zu schreiben, so müssen Sie diese Option aktivieren.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS'] = "Anonyme Kommentare erlauben";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_HELP'] = "Wenn es auch unregistrierten Besuchern erlaubt sein soll Kommentare zu verfassen, so aktivieren Sie diese Option.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE'] = "Neue Kommentare automatisch aktivieren";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE_HELP'] = "Durch setzen dieses Schalters werden neue Beiträge automatisch aktiviert. Andernfalls müssen alle Kommentare von Ihnen freigeschaltet werden.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION'] = "Benachrichtung bei neuen Kommentaren";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION_HELP'] = "Ist diese Einstellung aktiviert, so erhalten Sie bei neuen Kommentaren eine E-Mail zugestellt.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT'] = "Wartezeit zwischen Kommentaren";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT_HELP'] = "Dieser Wert beschreibt, wie viele Sekunden zwischen zwei Kommentaren eines Benutzers liegen müssen. Dadurch wird übermässiges Verwenden der Kommentarfunktion verhindert. Empfohlener Wert sind 30 Sekunden.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR'] = "Editor";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_HELP'] = "Legen Sie fest, welchen Editor Sie Ihren Besuchern zum Verfassen von Kommentaren zur Verfügung stellen wollen.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_WYSIWYG'] = "WYSIWYG-Editor";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_TEXTAREA'] = "Textfeld";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_TITLE'] = "Bewertungen";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW'] = "Bewertungen erlauben";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW_HELP'] = "Wenn Sie Ihren Besuchern die Möglichkeit geben wollen, Ihre Beiträge zu bewerten, so müssen Sie diese Option aktivieren.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_TITLE'] = "Schlüsselwörter";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST'] = "Schlüsselwörter in Hitliste";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST_HELP'] = "Legen Sie hier die Zahl der angezeigten Schlüsselwörter in der Hitliste fest.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_TITLE'] = "RSS";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE'] = "RSS-Feeds aktivieren";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE_HELP'] = "Wenn Sie von Ihrem Blog einen RSS-Feed erstellen lassen möchten, so müssen Sie diese Option aktivieren. Dadurch werden im Ordner <pre>feed/</pre> Dateien vom Schema <pre>blog_messages_XX.xml<br />blog_comments_XX.xml<br />blog_category_ID_XX.xml</pre> erstellt, wobei XX für die jeweilige Systemsprachen-Kurzform und ID für die ID der Kategorie steht.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES'] = "Anzahl Nachrichten";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES_HELP'] = "Anzahl der Nachrichten, welche in der XML-Datei enthalten sind. Es handelt sich dabei immer um die aktuellsten Nachrichten.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS'] = "Anzahl Kommentare";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS_HELP'] = "Anzahl der Kommentare, welche in der XML-Datei enthalten sind. Es handelt sich dabei immer um die aktuellsten Kommentare.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_TITLE'] = "Block Vorlage";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE'] = "Block-Funktion aktivieren";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE_HELP'] = "Durch Aktivieren dieser Funktion können Sie an verschiedenen Orten im System den Platzhalter <pre>[[BLOG_FILE]]</pre> durch die Datei <pre>blog.html</pre> ersetzen lassen. Dazu können Sie untenstehende Platzhalter einsetzen.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES'] = "Anzahl Nachrichten";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES_HELP'] = "Anzahl der Nachrichten, welche angezeigt werden sollen. Es werden dabei immer die neusten Nachrichten angezeigt.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE'] = "Verwendung";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE_HELP'] = "Jegliche hier aufgelisteten Platzhalter können unter dem Menüpunkt \"Layout & Designs\" in der Datei <b>blog.html</b> einer Webdesignvorlage verwendet werden. Diese Datei kann anschliessend mit dem Platzhalter <b>[[BLOG_FILE]]</b> in einer beliebigen Webdesignvorlage Datei (index.html, home.html, content.html und sidebar.html) oder in einer Inhaltsseite (Content Manager) eingebunden werden.<br /><br />Ferner können die Variablen unter <b>Allgemein</b> auch ausserhalb der <b>blog.html</b>-Datei in den Design-Dateien (index.html, home.html, content.html und sidebar.html) eingesetzt werden.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_SAVE_SUCCESSFULL'] = "Die Einstellungen wurden erfolgreich gespeichert.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIVE_LANGUAGES'] = "Aktivierte Sprachen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'] = "Aktionen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_NO_CATEGORIES'] = "Momentan sind keine Kategorien vorhanden.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ASSIGNED_MESSAGES'] = "Meldungen dieser Kategorie anzeigen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'] = "Markierungen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'] = "Alles markieren";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'] = "Markierungen löschen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'] = "Aktion wählen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTIVATE'] = "Markierte aktivieren";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DEACTIVATE'] = "Markierte deaktivieren";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'] = "Markierte löschen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE_JS'] = "Sollen wirklich alle markierten Kategorien gelöscht werden? Dieser Vorgang kann nicht rückgängig gemacht werden!";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_NAME'] = "Name";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_EXTENDED'] = "Erweitert";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'] = "Sprachen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_SUCCESSFULL'] = "Die neue Kategorie wurde erfolgreich hinzugefügt.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_ERROR_ACTIVE'] = "Kategorie wurde nicht hinzugefügt. Sie müssen mindestens eine Sprache für die neue Kategorie aktivieren.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_TITLE'] = "Kategorie löschen";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_JS'] = "Soll diese Kategorie wirklich gelöscht werden?";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_SUCCESSFULL'] = "Die Kategorie wurde erfolgreich gelöscht.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_ERROR'] = "Die Kategorie mit der gewünschten ID konnte nicht gelöscht werden..";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_TITLE'] = "Kategorie editieren";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_ERROR_ID'] = "Die Kategorie mit der angegebenen ID existiert nicht. Überprüfen Sie die eingegebene ID.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_SUCCESSFULL'] = "Die Kategorie wurde erfolgreicht aktualisiert.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_ERROR_ACTIVE'] = "Kategorie konnte nicht aktualisiert werden. Sie müssen mindestens eine Sprache für die Kategorie aktivieren.";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'] = "Titel";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_KEYWORDS'] = "Schlüsselwörter";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE'] = "Nachrichten-Bild";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'] = "Durchsuchen";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES'] = "Kategorien";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUCCESSFULL'] = "Die neue Nachricht wurde erfolgreich hinzugefügt.";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_ERROR_LANGUAGES'] = "Sie müssen die neue Nachricht in mindestens einer Sprache veröffentlichen.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_DATE'] = "Veröffentlichung";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_HITS'] = "Leser";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENT'] = "Kommentar";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'] = "Kommentare";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTE'] = "Bewertung";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTES'] = "Stimmen";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_UPDATED'] = "Letzte Aktualisierung";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_SUBMIT_DELETE_JS'] = "Sollen wirklich alle markierten Einträge gelöscht werden? Dieser Vorgang kann nicht rückgängig gemacht werden!";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_NO_ENTRIES'] = "Momentan sind keine Einträge vorhanden.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_PAGING'] = "Nachrichten";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_TITLE'] = "Nachricht löschen";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_JS'] = "Soll diese Nachricht wirklich gelöscht werden?";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_SUCCESSFULL'] = "Der Eintrag wurde erfolgreich gelöscht.";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_ERROR_ID'] = "Der Eintrag mit der angegebenen ID existiert nicht. Überprüfen Sie die eingegebene ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_TITLE'] = "Nachricht editieren";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_ERROR_ID'] = "Die Nachricht mit der angegebenen ID existiert nicht. Überprüfen Sie die eingegebene ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_SUCCESSFULL'] = "Die Nachricht wurde erfolgreich aktualisiert.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_ERROR_LANGUAGES'] = "Sie müssen die Nachricht in mindestens einer Sprache aktivieren.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_TITLE'] = "Bewertung des Themas";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_COUNT'] = "Anzahl Bewertungen";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_AVG'] = "Durschnittliche Bewertung";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS'] = "Statistiken";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS_NONE'] = "Bisher sind keine Statistiken zu diesem Thema verfügbar.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DETAILS'] = "Bewertungen";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DATE'] = "Datum & Zeit";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_IP'] = "IP-Adresse";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_JS'] = "Wollen Sie diese Bewertung wirklich löschen?";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_SUCCESSFULL'] = "Die Bewertung wurde erfolgreich gelöscht.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_SUBMIT_DELETE_JS'] = "Sollen wirklich alle markierten Bewertungen gelöscht werden?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_NONE'] = "Bisher sind keine Kommentare zu diesem Thema verfasst worden.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_LANGUAGE'] = "Sprache";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_STATUS'] = "Kommentar de-/aktivieren";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT'] = "Kommentar bearbeiten";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE'] = "Kommentar löschen";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_SUCCESSFULL'] = "Der Kommentar wurde erfolgreich gelöscht.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS'] = "Soll dieser Kommentar wirklich gelöscht werden?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS_ALL'] = "Sollen wirklich alle markierten Kommentare gelöscht werden?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_ERROR'] = "Der Kommentar mit der angegebenen ID existiert nicht. Überprüfen Sie die eingegebene ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS'] = "Benutzerstatus";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_REGISTERED'] = "Registrierter Benutzer";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_UNREGISTERED'] = "Unregistrierter Benutzer";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_NAME'] = "Benutzername";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_WWW'] = "Website";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_SUCCESSFULL'] = "Der Kommentar wurde erfolgreich aktualisiert.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_ERROR'] = "Der Kommentar konnte nicht aktualisiert werden. Vermutlich haben Sie ungültige Werte eingegeben.";
$_ARRAYLANG['TXT_BLOG_BLOCK_ERROR_DEACTIVATED'] = "Die Block-Funktionalität ist momentan deaktiviert. Aktivieren Sie diese zuerst in den Einstellungen.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TITLE'] = "Allgemein";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CALENDAR'] = "Ein Kalender des aktuellen Monats. Tage mit Meldungen werden hervorgehoben.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_SELECT'] = "Ein Dropdown-Menu, welches alle bestehenden Kategorien enthält. Erlaubt die Filterung von Nachrichten auf eine Kategorie.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_LIST'] = "Eine Liste mit allen bestehenden Kategorien. Erlaubt die Filterung von Nachrichten auf eine Kategorie.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGCLOUD'] = "Gibt eine komplette Tag-Cloud aller Schlüsselwörter aus.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGHITLIST'] = "Gibt eine Rangliste der beliebtesten Schlüsselwörter aus.";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_TITLE'] = "Meldungen";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_LINK'] = "Link";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROW'] = "Jegliche nachfolgenden Platzhalter müssen inerhalb des folgende Blocks verwendet werden: <pre><!-- BEGIN latestBlogMessages--><br />...<br /><!-- END latestBlogMessages --></pre>";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROWCLASS'] = "CSS-Klasse für die Tabellen-Zeile";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ID'] = "Eindeutige ID der Kategorie";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_DATE'] = "Datum der Meldung";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_POSTEDBY'] = "Text welcher das Datum und den Benutzer enthält";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_SUBJECT'] = "Titel der Meldung";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_INTRODUCTION'] = "Kurzer Einleitungstext der Meldung";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CONTENT'] = "Vollständiger Text der Meldung";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_ID'] = "Eindeutige ID des Verfassers";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_NAME'] = "Name des Verfassers";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CATEGORIES'] = "Kategorien der Meldung";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_TAGS'] = "Schlüsselwörter der Meldung";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COMMENTS'] = "Anzahl der Kommentare der Meldung";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_VOTING'] = "Durchschnittliche Bewertung der Meldung";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_STARS'] = "Bewertung der Meldung in Sternen";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_LINK'] = "Link zur Detailseite";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_IMAGE'] = "Zugewiesenes Bild";
$_ARRAYLANG['TXT_BLOG_BLOCK_CATEGORY_TITLE'] = "Kategorien";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_NAME'] = "Name der Kategorie";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COUNT'] = "Anzahl Meldungen in dieser Kategorie";
$_ARRAYLANG['TXT_BLOG_BLOCK_TEXT'] = "Text";
$_ARRAYLANG['TXT_BLOG_BLOCK_CONTENT'] = "Inhalt";
$_ARRAYLANG['TXT_BLOG_BLOCK_EXAMPLE'] = "Beispielcode";
$_ARRAYLANG['TXT_BLOG_NETWORKS'] = "Netzwerke";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_NONE'] = "Momentan sind keine Netzwerke eingetragen.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE'] = "Markierte löschen";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE_JS'] = "Wollen Sie wirklich alle markierten Netzwerke entfernen?";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_TITLE'] = "Netzwerk hinzufügen";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_NAME'] = "Name des Anbieter";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_WWW'] = "URL des Anbieters";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_SUBMIT'] = "URL für Eintragungen";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_ICON'] = "Icon des Anbieters";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_BROWSE'] = "Durchsuchen";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_SUCCESSFULL'] = "Der Netzwerk-Anbieter wurde erfolgreich hinzugefügt.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_ERROR'] = "Eine oder mehrer Ihrer Eingaben enthielten Fehler. Der Netzwerk-Anbieter konnte nicht erstellt werden.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_TITLE'] = "Netzwerk editieren";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_ERROR'] = "Es existiert kein Netzwerk mit dieser ID. Überprüfen Sie Ihre Eingabe.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_SUCCESSFULL'] = "Das Netzwerk wurde erfolgreich aktualisiert.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_ERROR'] = "Eine oder mehrer Ihrer Eingaben enthielten Fehler. Der Netzwerk-Anbieter konnte nicht aktualisiert werden.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_TITLE'] = "Netzwerk löschen";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_JS'] = "Soll dieses Netzwerk wirklich gelöscht werden?";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_SUCCESSFULL'] = "Das Netzwerk wurde erfolgreich gelöscht.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_ERROR'] = "Das Netzwerk mit dieser ID wurde nicht gefunden.";
$_ARRAYLANG['TXT_BLOG_LIB_POSTED_BY'] = "gepostet von [USER] um [DATE]";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_WEEKDAYS'] = "So,Mo,Di,Mi,Do,Fr,Sa";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_MONTHS'] = "Januar,Februar,Maerz,April,Mai,Juni,Juli,August,September,Oktober,November,Dezember";
$_ARRAYLANG['TXT_BLOG_LIB_RATING'] = "Bewertung";
$_ARRAYLANG['TXT_BLOG_LIB_ALL_CATEGORIES'] = "Alle Kategorien";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'] = "Blog Meldungen";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_COMMENTS_TITLE'] = "Blog Kommentare";
?>

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

$_ARRAYLANG = array(
    'TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'   => 'Die Struktur der Datenbanktabelle %s konnte nicht ermittelt werden!',
    'TXT_UNABLE_DETERMINE_DATABASE_STRUCTURE'       => 'Die Struktur der Datenbank konnte nicht ermittelt werden',
    'TXT_SET_WRITE_PERMISSON_TO_FILE'               => 'Setzen Sie die Zugriffsberechtigung für die Datei %s auf 777 (Unix) oder vergeben Sie auf diese Datei Schreibrechte (Windows) und betätigen Sie die Schaltfläche <strong>%s</strong>',
    'TXT_SET_WRITE_PERMISSON_TO_DIR'                => 'Setzen Sie die Zugriffsberechtigung für das Verzeichnis %s auf 777 (Unix) oder vergeben Sie dem Verzeichnis Schreibrechte (Windows) und betätigen Sie die Schaltfläche <strong>%s</strong>',
    'TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'    => 'Setzen Sie die Zugriffsberechtigung für das Verzeichnis %s und dessen Inhalt auf 777 (Unix) oder vergeben Sie dem Verzeichnis und dessen Inhalt Schreibrechte (Windows) und betätigen Sie die Schaltfläche <strong>%s</strong>',
    'TXT_UNABLE_APPLY_NEW_NEWS_LAYOUT'              => 'Bei den folgenden Inhaltsseiten konnte die neue Layoutstruktur nicht übernommen werden. Lesen Sie im README nach, wie Sie das Layout der News-Inhaltsseiten anpassen müssen!',
    'TXT_UNABLE_CREATE_TABLE_PRIMARY_KEY'           => 'Die Primärschlüssel der Datenbanktabelle %s konnten nicht ermittelt werden!',
    'TXT_SYSTEM_CONFIG_IN_USE_BY_ID'                => 'Die Systemkonfiguration %s konnte nicht hinzugefügt werden, da bereits eine andere Systemkonfiguration fälschlicherweise die selbe Konfigurations-ID %u verwendet!',
    'TXT_UNABLE_CREATE_SETTINGS_FILE'               => 'Die Konfigurationsdatei %s kann nicht erstellt werden!',
    'TXT_UNABLE_WRITE_SETTINGS_FILE'                => 'Die Konfigurationsdatei %s kann nicht geschrieben werden!',
    'TXT_UNABLE_CONVERT_FILE'                       => 'Die Datei %s kann nicht auf UTF-8 umgestellt werden!',
    'TXT_UNABLE_WRITE_VERSION_FILE'                 => 'Die Versionsdatei %s kann nicht geschrieben werden!',
    'TXT_UNABLE_WRITE_FILE'                         => 'Die Datei %s kann nicht geschrieben werden!',
    'TXT_UNABLE_CREATE_VERSION_FILE'                => 'Die Versionsdatei %s kann nicht erstellt werden!',
    'TXT_UNABLE_UPGRADE_ACCESS_SYSTEM'              => 'Konnte das Berechtigungssystem nicht aktualisieren!',
    'TXT_UNABLE_DETERMINE_AVAILABLE_GROUPS'         => 'Konnte die vorhandenen System Gruppen nicht ermitteln!',
    'TXT_CHECK_CONTENT_PAGE_FOR_BUGS'               => 'Bitte überprüfen Sie bei den folgenden Inhaltsseiten das Layout und dessen Funktion auf ihre Korrektheit!',
    'TXT_FINISH_MSG'                                => 'Gratulation, Sie haben die Version 3.0 Service Pack 2 erfolgreich installiert.',
    'TXT_README_MSG'                                => 'WICHTIG: Es wird dringend empfohlen die README Datei durchzulesen, da noch manuelle Änderungen, wie z.B. an den Inhaltsseiten, durchgeführt werden müssen! Das neue modules.css, sowie weitere aktuelle Informationen zum Release finden Sie <a href="http://www.contrexx.com/wiki/de/index.php/Version_3.0.0">hier</a>',
    'TXT_FINISH_LINKS'                              => '<a href="%s">Frontend</a><br /><a href="%s">Backend</a>',
    'TXT_UPDATE_DIRECTORY'                          => 'Verzeichnis Update...',
    'TXT_DIRECTORY_UPDATE_NOT_COMPLETED_MSG'        => 'Das migrieren der Verzeichniseinträge wurde nicht komplett abgeschlossen.<br />Betätigen Sie die Schaltfläche <strong>Mit dem Update fortfahren...</strong> um mit dem Migrieren der Einträge fort zu fahren.',
    'TXT_UNABLE_CREATE_THUMBNAIL'                   => 'Bei den folgenden Bilder konnte kein Thumbnail erstellt werden!',
    'TXT_IMAGE_IS_MISSING'                          => 'WARNUNG: Das Bild %s ist in der Datenbank eingetragen, existiert aber nicht mehr!',
    'TXT_MODULE_ENTRY_IS_MISSING'                   => 'Das Modul %s ist nicht installiert!',
    'TXT_SHOP_COULD_NOT_COPY_IMAGE'                 => 'Die folgenden Bilder konnten nicht ins Bilderverzeichnis des Shops kopiert werden:',
    'TXT_SHOP_IMAGE_EXISTS'                         => 'Die folgenden Bilder konnten nicht ins Bilderverzeichnis des Shops kopiert werden, da dort bereits eine Datei mit dem selben Namen existiert!',
    'TXT_SELECT_DB_COLLATION'                       => 'Datenbankkollation auswählen...',
    'TXT_SELECT_DB_COLLATION_MSG'                   => 'Wählen Sie eine Datenbankkollation aus, die für die Datenablage verwendet werden soll:<br /><br />%s<br /><div class="message-info">Es wird empfohlen, die Kollation <strong>utf8_unicode_ci</strong> zu verwenden, da diese die meisten Sprachen abdeckt.</div>',
    'TXT_NEW_BASIC_ADMINISTRATION'                  => 'Neue Grundeinstellungen',
    'TXT_GLOBAL_PAGE_TITLE_TXT'                     => 'Definieren Sie im nachfolgenden Textfeld einen globalen Seitentitel für Ihre Webseite. Diesen können Sie nach dem Update auch später bei den Grundeinstellungen in der Administrationskonsole ändern.<br />Der globale Seitentitel können Sie mit der Variable [[GLOBAL_TITLE]] in Ihren Designs einbinden.',
    'TXT_DOMAIN_URL_TXT'                            => "Geben Sie in das nachfolgende Textfeld Ihre Domain an, auf der diese Contrexx Installation läuft.<br />Zum Beispiel 'www.ihredomain.com' (ohne http:// oder zusätzliche Pfade!)<br />Bei einem Domainwechsel können Sie nach dem Update auch später bei den Grundeinstellungen in der Administrationskonsole die Domain ändern.",
    'TXT_CONFIG_CONTACT_FORMS'                      => 'Kontaktformulare Konfigurieren',
    'TXT_SELECT_CONTACT_FORM_LANG'                  => 'Wählen Sie für das folgende Kontaktformular die Frontend Sprache aus:',
    'TXT_SELECT_CONTACT_FORMS_LANG'                 => 'Wählen Sie für jedes Kontaktformular die Frontend Sprache aus:',
    'TXT_SHOW_IMAGE'                                => 'Bild anzeigen',
    'TXT_SHOP_IMAGE_MISSING'                        => 'Die folgenden Bilder können nicht in das Bilderverzeichnis des Shops kopiert werden, da diese nicht existieren:',
    'TXT_REMOVE_IMAGE'                              => 'Bild entfernen',
    'TXT_RETRY'                                     => 'Wiederholen',
    'TXT_OVERWRITE'                                 => 'Überschreiben',
    'TXT_UPDATE_CONTACT_STYLES'                     => 'Wählen Sie die Designordner aus, bei welchen die Kontakformular-Stile (CSS) hinzugefügt werden sollen.<br/><br />%s<br />',
    'TXT_SELECT_CONTACT_DESIGN_DIRS'                => 'Designordner auswählen...',
    'TXT_UNABLE_TO_MOVE_DIRECTORY'                  => 'Verzeichnis %s konnte nicht nach %s kopiert werden. Bitte überprüfen Sie die Berechtigungen.',
);

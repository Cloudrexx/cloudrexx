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
 * @subpackage  core_config
 */
global $_ARRAYLANG;
$_ARRAYLANG['TXT_SETTINGS_MENU_SYSTEM'] = 'System';
$_ARRAYLANG['TXT_SETTINGS_MENU_CACHE'] = 'Caching';
$_ARRAYLANG['TXT_EMAIL_SERVER'] = 'E-mail server';
$_ARRAYLANG['TXT_SETTINGS_IMAGE'] = 'Bilder';
$_ARRAYLANG['TXT_LICENSE'] = 'Lizenzverwaltung';
$_ARRAYLANG['TXT_SETTING_FTP_CONFIG_WARNING'] = 'Es wurde in der Konfigurationsdatei (%s) eine ungültige oder keine FTP-Verbindung konfiguriert. Dadurch können Probleme beim Hochladen oder Bearbeiten von Dateien und Verzeichnissen auftreten!';
$_ARRAYLANG['TXT_SETTINGS_ERROR_NO_WRITE_ACCESS'] = 'Filen <strong>%s</strong> er skrivebeskyttet!<br /> Det er ikke muligt at ændre indstillinger før skrivebeskyttelsen af filen er blevet fjernet!';
$_ARRAYLANG['TXT_SYSTEM_SETTINGS'] = 'Systemindstillinger';
$_ARRAYLANG['TXT_ACTIVATED'] = 'Aktiver';
$_ARRAYLANG['TXT_DEACTIVATED'] = 'Deactivated';
$_ARRAYLANG['TXT_CORE_CONFIG_SITE'] = 'Site';
$_ARRAYLANG['TXT_CORE_CONFIG_ADMINISTRATIONAREA'] = 'Administration area';
$_ARRAYLANG['TXT_CORE_CONFIG_SECURITY'] = 'Security';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTINFORMATION'] = 'Contact information';
$_ARRAYLANG['TXT_SETTINGS_TITLE_DEVELOPMENT'] = 'Development tools';
$_ARRAYLANG['TXT_CORE_CONFIG_OTHERCONFIGURATIONS'] = 'Other configuration options';
$_ARRAYLANG['TXT_DEBUGGING_STATUS'] = 'Debugging mode';
$_ARRAYLANG['TXT_DEBUGGING_FLAGS'] = 'Flags';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG'] = 'Statusmeldungen / Ereignisse';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_PHP'] = 'PHP-Fehler';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB'] = 'Datenbank: Alle Abfragen (inkl. Änderungen und Fehler)';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_TRACE'] = 'Datenbank: Abfragerückverfolgung';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_CHANGE'] = 'Datenbank: Änderungen (INSERT/UPDATE/DELETE)';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_ERROR'] = 'Datenbank: Fehler';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG_FILE'] = 'Ausgabe in Datei';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG_FIREPHP'] = 'Ausgabe in FirePHP';
$_ARRAYLANG['TXT_DEBUGGING_EXPLANATION'] = 'Mode that helps cloudrexx developers with troubleshooting tasks. Activated for the currently logged in user only. Settings are discarded as soon as the session is closed.';
$_ARRAYLANG['TXT_SAVE'] = 'Gem';
$_ARRAYLANG['TXT_CORE_CONFIG_SYSTEMSTATUS'] = 'Sidestatus';
$_ARRAYLANG['TXT_CORE_CONFIG_SYSTEMSTATUS_TOOLTIP_HELP'] = 'Is the page activated? - Status (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_COREIDSSTATUS'] = 'Sikkerhedssystem';
$_ARRAYLANG['TXT_CORE_CONFIG_COREIDSSTATUS_TOOLTIP_HELP'] = 'Cloudrexx Intrusion Detection System - Reporting Status (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_XMLSITEMAPSTATUS'] = 'XML Sitemap';
$_ARRAYLANG['TXT_CORE_CONFIG_XMLSITEMAPSTATUS_TOOLTIP_HELP'] = 'Automatisk generering af XML Sitemap - status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_COREGLOBALPAGETITLE'] = 'Global sidetitel';
$_ARRAYLANG['TXT_CORE_CONFIG_COREGLOBALPAGETITLE_TOOLTIP_HELP'] = 'Global pagetitle. It can be added to your design with the use of [[GLOBAL_TITLE]].';
$_ARRAYLANG['TXT_SETTINGS_DOMAIN_URL'] = 'URL for homepage';
$_ARRAYLANG['TXT_SETTINGS_DOMAIN_URL_HELP'] = 'URL of your website. Please make sure, that you don"t add a slash at the end of the URL! ( / )';
$_ARRAYLANG['TXT_CORE_CONFIG_COREPAGINGLIMIT'] = 'Records per page';
$_ARRAYLANG['TXT_CORE_CONFIG_COREPAGINGLIMIT_TOOLTIP_HELP'] = 'Værdier mellem 1 og 200 er tilladt.';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHDESCRIPTIONLENGTH'] = 'Viste tegn ved søgeresultater.';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHDESCRIPTIONLENGTH_TOOLTIP_HELP'] = 'Number of characters shown in the description of a search-result';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIME'] = 'Session length';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIME_TOOLTIP_HELP'] = 'Session length in seconds';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIMEREMEMBERME'] = 'Session length (remember me)';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIMEREMEMBERME_TOOLTIP_HELP'] = 'Session length in seconds for users which have set the checkbox "Remember me" at login.';
$_ARRAYLANG['TXT_CORE_CONFIG_DNSSERVER'] = 'DNS-server';
$_ARRAYLANG['TXT_CORE_CONFIG_DNSSERVER_TOOLTIP_HELP'] = 'En DNS-server er for opslaget af en MX-Lookup"s ved netværk værktøjet (network tool).';
$_ARRAYLANG['TXT_CORE_CONFIG_COREADMINNAME'] = 'Administrators name';
$_ARRAYLANG['TXT_CORE_CONFIG_COREADMINEMAIL'] = 'Administratorens e-mail adresse';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFORMEMAIL'] = 'E-mail adresse kontaktformular (standard)';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFORMEMAIL_TOOLTIP_HELP'] = 'Modtager e-mail-adresser for standardformularet (ingen Id angivet). Flere modtageradresser adskilles med komma.';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTCOMPANY'] = 'Company';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTADDRESS'] = 'Address';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTZIP'] = 'ZIP-Code';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTPLACE'] = 'Place';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTCOUNTRY'] = 'Country';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTPHONE'] = 'Phone';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFAX'] = 'Fax';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHVISIBLECONTENTONLY'] = 'Søg kun i synlige indholdssider';
$_ARRAYLANG['TXT_CORE_CONFIG_LANGUAGEDETECTION'] = 'Auto-detect sprog';
$_ARRAYLANG['TXT_CORE_CONFIG_LANGUAGEDETECTION_TOOLTIP_HELP'] = 'Den indstilling indebærer, at browserens standardsprog automatisk vil blive brugt som contentsprog.';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEMAPSAPIKEY_TOOLTIP_HELP'] = 'Global Google-Map API nøgle for hoveddomainet. <br />Nye nøgler kan blive oprettet her: http://code.google.com/apis/maps/signup.html';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEMAPSAPIKEY'] = 'Google-Map API nøgle';
$_ARRAYLANG['TXT_CORE_CONFIG_FRONTENDEDITINGSTATUS'] = 'Frontend editing';
$_ARRAYLANG['TXT_CORE_CONFIG_FRONTENDEDITINGSTATUS_TOOLTIP_HELP'] = 'Ved hjælp af Frontend Editing kan du tilpasse en side iden at logge på backend-området - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_USECUSTOMIZINGS'] = 'Individuelle Kundenanpassungen';
$_ARRAYLANG['TXT_CORE_CONFIG_USECUSTOMIZINGS_TOOLTIP_HELP'] = 'Verwenden Sie diese Option um individuelle Kundenanpassungen welche sich im Ordner %1 befinden zu aktivieren - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_CORELISTPROTECTEDPAGES'] = 'Vis beskyttede sider';
$_ARRAYLANG['TXT_CORE_CONFIG_CORELISTPROTECTEDPAGES_TOOLTIP_HELP'] = 'Definerer om beskyttede sider skal blive vist/inkluderet i navigationen, søgefunktionen, sitemap og XML sitemap hivs brugeren ikke er godkent - Status  (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_TIMEZONE'] = 'Timezone';
$_ARRAYLANG['TXT_CORE_CONFIG_DASHBOARDNEWS'] = 'Dashboard news';
$_ARRAYLANG['TXT_CORE_CONFIG_DASHBOARDSTATISTICS'] = 'Dashboard statistics';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEANALYTICSTRACKINGID'] = 'Google Analytics Tracking ID';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEANALYTICSTRACKINGID_TOOLTIP_HELP'] = 'Enter your Google Analytics tracking ID here. These can be found in your Google Analytics account under Admin => Tracking Code.';
$_ARRAYLANG['TXT_CORE_CONFIG_PASSWORDCOMPLEXITY'] = 'Passwords must meet the complexity requirements';
$_ARRAYLANG['TXT_CORE_CONFIG_PASSWORDCOMPLEXITY_TOOLTIP_HELP'] = 'Password must contain the following characters: upper and lower case character and number';
$_ARRAYLANG['TXT_CORE_CONFIG_ADVANCEDUPLOADBACKEND'] = 'Erweiterte Dateiuploadmöglickeiten (Administrationsoberfläche)';
$_ARRAYLANG['TXT_CORE_CONFIG_ADVANCEDUPLOADFRONTEND'] = 'Erweiterte Dateiuploadmöglickeiten (Frontend)';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLFRONTEND_TOOLTIP_HELP'] = $_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLBACKEND_TOOLTIP_HELP'] = 'Cloudrexx erzwingt standardmässig kein Protokoll. Sie können HTTP erzwingen um die Suchmaschinen-Bewertung zu verbessern oder HTTPS (Hypertext Transfer Protocol Secure), ein Protokoll das zusätzlich mehr Sicherheit garantiert, aktivieren. Sollte Ihr Webserver dies nicht unterstützen, deaktiviert Cloudrexx diese Einstellung automatisch wieder.';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLBACKEND'] = 'Verwendetes Protokoll (Administrationsoberfläche)';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLFRONTEND'] = 'Verwendetes Protokoll (Frontend)';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_NONE'] = 'dynamisch';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_HTTP'] = 'HTTP';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_HTTPS'] = 'HTTPS';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEDOMAINURL_TOOLTIP_HELP'] = 'Suchmaschinen schauen, sofern Ihre Homepage unter mehreren Internetadressen aufrufbar ist, die Homepage-Inhalte als doppelten Inhalt an. Aus diesem Grund sollte falls möglich diese Option aktiviert werden.';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEDOMAINURL'] = 'Domain Ihrer Homepage erzwingen';
$_ARRAYLANG['TXT_CORE_TIMEZONE_INVALID'] = 'The selected timezone is not valid.';
$_ARRAYLANG['TXT_SETTINGS_UPDATED'] = 'Settings have been updated.';
$_ARRAYLANG['TXT_SETTINGS_ERROR_WRITABLE'] = 'could not be written. Please check permissions (666) of the file.';
$_ARRAYLANG['TXT_SETTINGS_DEFAULT_SMTP_CHANGED'] = 'SMTP kontoen %s vil nu blive brugt som standard konto for sending af e-mails.';
$_ARRAYLANG['TXT_SETTINGS_CHANGE_DEFAULT_SMTP_FAILED'] = 'Ændringen af standardkonto slog fejl!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_DELETE_SUCCEED'] = 'SMTP kontoen %s er blevet slettet.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_DELETE_FAILED'] = 'Der opstod en fejl under sletningen af SMTP kontoen %s!';
$_ARRAYLANG['TXT_SETTINGS_COULD_NOT_DELETE_DEAULT_SMTP'] = 'SMTP kontoen %s kunne ikke blive slettet da det er standard kontoen!';
$_ARRAYLANG['TXT_SETTINGS_EMAIL_ACCOUNTS'] = 'E-mail konti';
$_ARRAYLANG['TXT_SETTINGS_ACCOUNT'] = 'Konto';
$_ARRAYLANG['TXT_SETTINGS_HOST'] = 'Host';
$_ARRAYLANG['TXT_SETTINGS_USERNAME'] = 'Brugernavn';
$_ARRAYLANG['TXT_SETTINGS_STANDARD'] = 'Standard';
$_ARRAYLANG['TXT_SETTINGS_FUNCTIONS'] = 'Funktioner';
$_ARRAYLANG['TXT_SETTINGS_ADD_NEW_SMTP_ACCOUNT'] = 'Tilføj ny SMTP konto';
$_ARRAYLANG['TXT_SETTINGS_CONFIRM_DELETE_ACCOUNT'] = 'Vil de virkelig slette SMTP kontoen %s?';
$_ARRAYLANG['TXT_SETTINGS_OPERATION_IRREVERSIBLE'] = 'Denne hændelse kan ikke fortrydes!';
$_ARRAYLANG['TXT_SETTINGS_MODFIY'] = 'Rediger';
$_ARRAYLANG['TXT_SETTINGS_DELETE'] = 'Slet';
$_ARRAYLANG['TXT_SETTINGS_EMPTY_ACCOUNT_NAME_TXT'] = 'Du skal definere et navn for kontoen!';
$_ARRAYLANG['TXT_SETTINGS_NOT_UNIQUE_SMTP_ACCOUNT_NAME'] = 'Du skal vælge et andet kontonavn, da navnet %s allerede er i brug';
$_ARRAYLANG['TXT_SETTINGS_EMPTY_SMTP_HOST_TXT'] = 'Du skal angive en SMTP server!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_UPDATE_SUCCEED'] = 'SMTP kontoen %s er blevet aktualiseret.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_UPDATE_FAILED'] = 'Der opstod en fejl under aktualiseringen af SMTP Kontoen %s!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_ADD_SUCCEED'] = 'Brugerkontoen %s er blevet tilføjet.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_ADD_FAILED'] = 'Der opstod en fejl under tilføjelsen af den nye SMTP konto!';
$_ARRAYLANG['TXT_SETTINGS_MODIFY_SMTP_ACCOUNT'] = 'Rediger SMTP server';
$_ARRAYLANG['TXT_SETTINGS_NAME_OF_ACCOUNT'] = 'Kontoens navn';
$_ARRAYLANG['TXT_SETTINGS_SMTP_SERVER'] = 'SMTP server';
$_ARRAYLANG['TXT_SETTINGS_PORT'] = 'Port';
$_ARRAYLANG['TXT_SETTINGS_AUTHENTICATION'] = 'Autentificering';
$_ARRAYLANG['TXT_SETTINGS_PASSWORD'] = 'Kodeord';
$_ARRAYLANG['TXT_SETTINGS_SMTP_AUTHENTICATION_TXT'] = 'Angiv hverken brugernavn eller kodeord her, hvis den angivede SMTP-server ikke kræver en autentifikation.';
$_ARRAYLANG['TXT_SETTINGS_BACK'] = 'Tilbage';
$_ARRAYLANG['TXT_SETTINGS_SAVE'] = 'Gem';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_TITLE'] = 'Bildeinstellungen';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_CUT_WIDTH'] = 'Standardbreite für die Bildzuschneidung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_CUT_HEIGHT'] = 'Standardhöhe für die Bildzuschneidung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_SCALE_WIDTH'] = 'Standardbreite für die Skalierung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_SCALE_HEIGHT'] = 'Standardhöhe für die Skalierung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_COMPRESSION'] = 'Standardwert für die Kompression';

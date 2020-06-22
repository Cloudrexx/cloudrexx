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
$_ARRAYLANG['TXT_LICENSE'] = 'License Management';
$_ARRAYLANG['TXT_SETTING_FTP_CONFIG_WARNING'] = 'There are wrong ftp credentials defined in the configurations file (%s) or the ftp connection is disabled. If you don"t fix this issue, cloudrexx probably doesn"t have access to upload or edit files and folders!';
$_ARRAYLANG['TXT_SETTINGS_ERROR_NO_WRITE_ACCESS'] = 'The file <strong>%s</strong> is write-protected!<br />No changes to the settings can be made until the write protection on that file has been removed!';
$_ARRAYLANG['TXT_SYSTEM_SETTINGS'] = 'Системные установки';
$_ARRAYLANG['TXT_ACTIVATED'] = 'Активирован';
$_ARRAYLANG['TXT_DEACTIVATED'] = 'Deactivated';
$_ARRAYLANG['TXT_CORE_CONFIG_SITE'] = 'Site';
$_ARRAYLANG['TXT_CORE_CONFIG_ADMINISTRATIONAREA'] = 'Administration area';
$_ARRAYLANG['TXT_CORE_CONFIG_SECURITY'] = 'Security';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTINFORMATION'] = 'Contact information';
$_ARRAYLANG['TXT_SETTINGS_TITLE_DEVELOPMENT'] = 'Development tools';
$_ARRAYLANG['TXT_CORE_CONFIG_OTHERCONFIGURATIONS'] = 'Other configuration options';
$_ARRAYLANG['TXT_DEBUGGING_STATUS'] = 'Debugging mode';
$_ARRAYLANG['TXT_DEBUGGING_FLAGS'] = 'Flags';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG'] = 'Messages / Events';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_PHP'] = 'PHP errors';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB'] = 'Database: All queries (incl. changes und failed queries)';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_TRACE'] = 'Database: Trace queries';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_CHANGE'] = 'Database: Changes (INSERT/UPDATE/DELETE)';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_ERROR'] = 'Database: Failed queries';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG_FILE'] = 'Output to file';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG_FIREPHP'] = 'Output to FirePHP';
$_ARRAYLANG['TXT_DEBUGGING_EXPLANATION'] = 'Mode that helps cloudrexx developers with troubleshooting tasks. Activated for the currently logged in user only. Settings are discarded as soon as the session is closed.';
$_ARRAYLANG['TXT_SAVE'] = 'Сохранить';
$_ARRAYLANG['TXT_CORE_CONFIG_SYSTEMSTATUS'] = 'Page status';
$_ARRAYLANG['TXT_CORE_CONFIG_SYSTEMSTATUS_TOOLTIP_HELP'] = 'Is the page activated? - Status (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_COREIDSSTATUS'] = 'Securitysystem';
$_ARRAYLANG['TXT_CORE_CONFIG_COREIDSSTATUS_TOOLTIP_HELP'] = 'Cloudrexx Intrusion Detection System - Reporting Status (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_XMLSITEMAPSTATUS'] = 'XML Sitemap';
$_ARRAYLANG['TXT_CORE_CONFIG_XMLSITEMAPSTATUS_TOOLTIP_HELP'] = 'Automatic generation of a XML Sitemap - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_COREGLOBALPAGETITLE'] = 'Global pagetitle';
$_ARRAYLANG['TXT_CORE_CONFIG_COREGLOBALPAGETITLE_TOOLTIP_HELP'] = 'Global pagetitle. It can be added to your design with the use of [[GLOBAL_TITLE]].';
$_ARRAYLANG['TXT_SETTINGS_DOMAIN_URL'] = 'URL of homepage';
$_ARRAYLANG['TXT_SETTINGS_DOMAIN_URL_HELP'] = 'URL of your website. Please make sure, that you don"t add a slash at the end of the URL! ( / )';
$_ARRAYLANG['TXT_CORE_CONFIG_COREPAGINGLIMIT'] = 'Records per page';
$_ARRAYLANG['TXT_CORE_CONFIG_COREPAGINGLIMIT_TOOLTIP_HELP'] = 'Values between 1 and 200 allowed.';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHDESCRIPTIONLENGTH'] = 'Shown characters in search-results.';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHDESCRIPTIONLENGTH_TOOLTIP_HELP'] = 'Number of characters shown in the description of a search-result';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIME'] = 'Session length';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIME_TOOLTIP_HELP'] = 'Session length in seconds';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIMEREMEMBERME'] = 'Session length (remember me)';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIMEREMEMBERME_TOOLTIP_HELP'] = 'Session length in seconds for users which have set the checkbox "Remember me" at login.';
$_ARRAYLANG['TXT_CORE_CONFIG_COREADMINNAME'] = 'Administrators name';
$_ARRAYLANG['TXT_CORE_CONFIG_COREADMINEMAIL'] = 'E-Mail of administrator';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFORMEMAIL'] = 'E-Mail address for contactform (default)';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFORMEMAIL_TOOLTIP_HELP'] = 'The E-Mail address of the receiver for the default-form (no id specified). Several adresses can be divided by comma.';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTCOMPANY'] = 'Company';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTADDRESS'] = 'Address';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTZIP'] = 'ZIP-Code';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTPLACE'] = 'Place';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTCOUNTRY'] = 'Country';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTPHONE'] = 'Phone';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFAX'] = 'Fax';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHVISIBLECONTENTONLY'] = 'Nur in sichtbaren Inhaltsseiten suchen';
$_ARRAYLANG['TXT_CORE_CONFIG_LANGUAGEDETECTION'] = 'Auto-detect language';
$_ARRAYLANG['TXT_CORE_CONFIG_LANGUAGEDETECTION_TOOLTIP_HELP'] = 'Diese Einstellung bewirkt, dass automatisch die Standardsprache des Browsers ausgelesen und als Contentsprache verwendet wird.';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEMAPSAPIKEY_TOOLTIP_HELP'] = 'Globaler Google-Map API Schlüssel für die Hauptdomain. <br />Neue Schlüssel können erstellt werden unter: http://code.google.com/apis/maps/signup.html';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEMAPSAPIKEY'] = 'Google-Map API Schlüssel';
$_ARRAYLANG['TXT_CORE_CONFIG_FRONTENDEDITINGSTATUS'] = 'Frontend Editing';
$_ARRAYLANG['TXT_CORE_CONFIG_FRONTENDEDITINGSTATUS_TOOLTIP_HELP'] = 'Mit Hilfe des Frontend Editing können Sie Ihre Seite auch ohne ein vorheriges Einloggen im Backend anpassen - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_USECUSTOMIZINGS'] = 'Customizing';
$_ARRAYLANG['TXT_CORE_CONFIG_USECUSTOMIZINGS_TOOLTIP_HELP'] = 'Use this option to activate customizings found in %1 - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_CORELISTPROTECTEDPAGES'] = 'List protected pages';
$_ARRAYLANG['TXT_CORE_CONFIG_CORELISTPROTECTEDPAGES_TOOLTIP_HELP'] = 'Defines if protected pages should be listed/included in the navigation, full text search, sitemap and XML-Sitemap if the user isn"t authenticated - Status  (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_TIMEZONE'] = 'Timezone';
$_ARRAYLANG['TXT_CORE_CONFIG_DASHBOARDNEWS'] = 'Dashboard news';
$_ARRAYLANG['TXT_CORE_CONFIG_DASHBOARDSTATISTICS'] = 'Dashboard statistics';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEANALYTICSTRACKINGID'] = 'Google Analytics Tracking ID';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEANALYTICSTRACKINGID_TOOLTIP_HELP'] = 'Enter your Google Analytics tracking ID here. These can be found in your Google Analytics account under Admin => Tracking Code.';
$_ARRAYLANG['TXT_CORE_CONFIG_PASSWORDCOMPLEXITY'] = 'Passwords must meet the complexity requirements';
$_ARRAYLANG['TXT_CORE_CONFIG_PASSWORDCOMPLEXITY_TOOLTIP_HELP'] = 'Password must contain the following characters: upper and lower case character and number';
$_ARRAYLANG['TXT_CORE_CONFIG_ADVANCEDUPLOADBACKEND'] = 'Erweiterte Dateiuploadmöglickeiten (Administrationsoberfläche)';
$_ARRAYLANG['TXT_CORE_CONFIG_ADVANCEDUPLOADFRONTEND'] = 'Erweiterte Dateiuploadmöglickeiten (Frontend)';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLFRONTEND_TOOLTIP_HELP'] = $_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLBACKEND_TOOLTIP_HELP'] = 'By default, Cloudrexx does not force the usage of a protocol. You have the option to change the setting to force HTTP in order to improve search engine ranking or to force HTTPS (Hypertext Transfer Protocol Secure), a secure protocol that additionally provides authenticated and encrypted communication. If your webserver doesn"t support HTTPS, Cloudrexx will reset this option to default.';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLBACKEND'] = 'Protocol in use (administrator interface)';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLFRONTEND'] = 'Protocol in use (frontend)';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_NONE'] = 'dynamic';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_HTTP'] = 'HTTP';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_HTTPS'] = 'HTTPS';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEDOMAINURL_TOOLTIP_HELP'] = 'Search engines interprets your homepage content as duplicated content as long as the homepage is accessible by multiple addresses. We recommend to activate this option.';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEDOMAINURL'] = 'Force the url of homepage';
$_ARRAYLANG['TXT_CORE_TIMEZONE_INVALID'] = 'The selected timezone is not valid.';
$_ARRAYLANG['TXT_SETTINGS_UPDATED'] = 'Settings have been updated.';
$_ARRAYLANG['TXT_SETTINGS_ERROR_WRITABLE'] = 'could not be written. Please check permissions (666) of the file.';
$_ARRAYLANG['TXT_SETTINGS_DEFAULT_SMTP_CHANGED'] = 'Das SMTP Konto %s wird nun als Standardkonto für das Versenden von E-Mails verwendet.';
$_ARRAYLANG['TXT_SETTINGS_CHANGE_DEFAULT_SMTP_FAILED'] = 'Das Wechseln des Standard Kontos schlug fehl!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_DELETE_SUCCEED'] = 'Das SMTP Konto %s wurde erfolgreich gelöscht.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_DELETE_FAILED'] = 'Beim Löschen des SMTP Kontos %s trat ein Fehler auf!';
$_ARRAYLANG['TXT_SETTINGS_COULD_NOT_DELETE_DEAULT_SMTP'] = 'Das SMTP Konto %s konnte nicht gelöscht werden, da es das Standard-Konto ist!';
$_ARRAYLANG['TXT_SETTINGS_EMAIL_ACCOUNTS'] = 'E-Mail Konten';
$_ARRAYLANG['TXT_SETTINGS_ACCOUNT'] = 'Konto';
$_ARRAYLANG['TXT_SETTINGS_HOST'] = 'Host';
$_ARRAYLANG['TXT_SETTINGS_USERNAME'] = 'Benutzername';
$_ARRAYLANG['TXT_SETTINGS_STANDARD'] = 'Standard';
$_ARRAYLANG['TXT_SETTINGS_FUNCTIONS'] = 'Funktionen';
$_ARRAYLANG['TXT_SETTINGS_ADD_NEW_SMTP_ACCOUNT'] = 'Neues SMTP Konto hinzufügen';
$_ARRAYLANG['TXT_SETTINGS_CONFIRM_DELETE_ACCOUNT'] = 'Möchten Sie das SMTP Konto %s wirklich löschen?';
$_ARRAYLANG['TXT_SETTINGS_OPERATION_IRREVERSIBLE'] = 'Dieser Vorgang kann nicht rückgängig gemacht werden!';
$_ARRAYLANG['TXT_SETTINGS_MODFIY'] = 'Bearbeiten';
$_ARRAYLANG['TXT_SETTINGS_DELETE'] = 'Löschen';
$_ARRAYLANG['TXT_SETTINGS_EMPTY_ACCOUNT_NAME_TXT'] = 'Sie müssen einen Kontonamen definieren!';
$_ARRAYLANG['TXT_SETTINGS_NOT_UNIQUE_SMTP_ACCOUNT_NAME'] = 'Sie müssen einen anderen Kontonamen verwenden, da der Kontoname %s bereits verwendet wird!';
$_ARRAYLANG['TXT_SETTINGS_EMPTY_SMTP_HOST_TXT'] = 'Sie müssen einen SMTP Server angeben!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_UPDATE_SUCCEED'] = 'Das SMTP Konto %s wurde erfolgreich aktualisiert.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_UPDATE_FAILED'] = 'Beim Aktualisieren des SMTP Kontos %s trat ein Fehler auf!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_ADD_SUCCEED'] = 'Das Konto %s wurde erfolgreich hinzugefügt.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_ADD_FAILED'] = 'Beim Hinzufügen des neuen SMTP Kontos trat ein Fehler auf!';
$_ARRAYLANG['TXT_SETTINGS_MODIFY_SMTP_ACCOUNT'] = 'SMTP Konto bearbeiten';
$_ARRAYLANG['TXT_SETTINGS_NAME_OF_ACCOUNT'] = 'Name des Kontos';
$_ARRAYLANG['TXT_SETTINGS_SMTP_SERVER'] = 'SMTP Server';
$_ARRAYLANG['TXT_SETTINGS_PORT'] = 'Port';
$_ARRAYLANG['TXT_SETTINGS_AUTHENTICATION'] = 'Authentifikation';
$_ARRAYLANG['TXT_SETTINGS_PASSWORD'] = 'Kennwort';
$_ARRAYLANG['TXT_SETTINGS_SMTP_AUTHENTICATION_TXT'] = 'Geben Sie hier kein Benutzernamen und Kennwort an, wenn der angegebene SMTP Server keine Authentifikation erfordert.';
$_ARRAYLANG['TXT_SETTINGS_BACK'] = 'Zurück';
$_ARRAYLANG['TXT_SETTINGS_SAVE'] = 'Speichern';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_TITLE'] = 'Bildeinstellungen';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_CUT_WIDTH'] = 'Standardbreite für die Bildzuschneidung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_CUT_HEIGHT'] = 'Standardhöhe für die Bildzuschneidung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_SCALE_WIDTH'] = 'Standardbreite für die Skalierung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_SCALE_HEIGHT'] = 'Standardhöhe für die Skalierung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_COMPRESSION'] = 'Standardwert für die Kompression';

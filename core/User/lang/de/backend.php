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
 * @subpackage  core_user
 */

global $_ARRAYLANG;

// Let's start with module info:
$_ARRAYLANG['TXT_CORE_USER'] = 'User';
$_ARRAYLANG['TXT_CORE_USER_DESCRIPTION'] = 'This is a new module with some sample content to show how to start.';

// Here come the ACTs:
$_ARRAYLANG['TXT_CORE_USER_ACT_USER'] = 'Benutzer';
$_ARRAYLANG['TXT_CORE_USER_ACT_GROUP'] = 'Gruppen';
$_ARRAYLANG['TXT_CORE_USER_ACT_SETTINGS'] = 'Einstellungen';
$_ARRAYLANG['TXT_CORE_USER_ACT_SETTINGS_DEFAULT'] = 'Mailing';
$_ARRAYLANG['TXT_CORE_USER_ACT_SETTINGS_HELP'] = 'Help';

// Now our content specific values:
$_ARRAYLANG['TXT_CORE_USER_CONGRATULATIONS'] = 'Overview';
$_ARRAYLANG['TXT_CORE_USER_SUCCESSFUL_CREATION'] = 'This is the Overview/Dashboard of your new Component. More tabs will be generated if you add entities to this component.';
$_ARRAYLANG['TXT_CORE_USER_EXAMPLE_TEMPLATE'] = 'This is the default template for this component, located in View/Template/Backend/Default.html. In order to add entities, place your YAML files in Model/Yaml folder and execute ./workbench.bat db update. Then add a language file entry for your entity.';
$_ARRAYLANG['TXT_CORE_USER_EDIT_TITLE'] = 'Benutzerkonto bearbeiten';
$_ARRAYLANG['TXT_CORE_USER_EMAIL_TITLE'] = 'E-Mail versenden an';
$_ARRAYLANG['TXT_CORE_USER_EDIT_GROUP'] = 'Gruppe bearbeiten';
$_ARRAYLANG['TXT_CORE_USER_BROWSE'] = 'Durchsuchen';
$_ARRAYLANG['TXT_CORE_USER_TYPE_FRONTEND'] = 'Website (frontend)';
$_ARRAYLANG['TXT_CORE_USER_TYPE_BACKEND'] = 'Administrationskonsole (backend)';
$_ARRAYLANG['TXT_CORE_USER_TYPE'] = 'Gruppentyp';
$_ARRAYLANG['TXT_CORE_USER_EXPORT_ALL'] = 'Alle';

$_ARRAYLANG['id'] = 'ID';
$_ARRAYLANG['isAdmin'] = 'Administrator';
$_ARRAYLANG['email'] = 'E-Mail';
$_ARRAYLANG['regdate'] = 'Registriert seit';
$_ARRAYLANG['lastActivity'] = 'Letzte Aktivität';
$_ARRAYLANG['lastAuth'] = 'Letzte Aktivität';
$_ARRAYLANG['groupId'] = 'ID';
$_ARRAYLANG['isActive'] = 'Aktiv';
$_ARRAYLANG['groupName'] = 'Name';
$_ARRAYLANG['groupDescription'] = 'Beschreibung';
$_ARRAYLANG['type'] = 'Typ';
$_ARRAYLANG['username'] = 'Benutzername';
$_ARRAYLANG['user'] = 'Benutzer';
$_ARRAYLANG['homepage'] = 'Startseite';

$_ARRAYLANG['picture'] = 'Profilbild';
$_ARRAYLANG['gender'] = 'Geschlecht';
$_ARRAYLANG['title'] = 'Anrede';
$_ARRAYLANG['designation'] = 'Titel';
$_ARRAYLANG['firstname'] = 'Vorname';
$_ARRAYLANG['lastname'] = 'Nachname';
$_ARRAYLANG['company'] = 'Firma';
$_ARRAYLANG['address'] = 'Adresse';
$_ARRAYLANG['city'] = 'Ort';
$_ARRAYLANG['zip'] = 'PLZ';
$_ARRAYLANG['country'] = 'Land';
$_ARRAYLANG['phone_office'] = 'Tel. Büro';
$_ARRAYLANG['phone_private'] = 'Tel. Privat';
$_ARRAYLANG['phone_mobile'] = 'Tel. Mobile';
$_ARRAYLANG['phone_fax'] = 'Fax';
$_ARRAYLANG['birthday'] = 'Geburtstag';
$_ARRAYLANG['website'] = 'Webseite';
$_ARRAYLANG['profession'] = 'Beruf';
$_ARRAYLANG['interests'] = 'Interessen';
$_ARRAYLANG['signature'] = 'Signatur';
$_ARRAYLANG['active'] = 'Status';
$_ARRAYLANG['expiration'] = 'Zeitbegrenzung';
$_ARRAYLANG['profileAccess'] = 'Privatsphäre';
$_ARRAYLANG['frontendLangId'] = 'Sprache';
$_ARRAYLANG['passwordConfirmed'] = 'Kennwort bestätigen';
$_ARRAYLANG['password'] = 'Kennwort';
$_ARRAYLANG['primaryGroup'] = 'Primäre Benutzergruppe';
$_ARRAYLANG['group'] = 'Zugeordnete Gruppen';
$_ARRAYLANG['newsletter'] = 'Newsletter';
$_ARRAYLANG['moduleSpecificExtensions'] = 'Zusätzliche Funktionen';
$_ARRAYLANG['downloadExtension'] = 'Digital Asset Management';

$_ARRAYLANG['TXT_CORE_USER_PROFILE'] = 'Profil';
$_ARRAYLANG['TXT_CORE_USER_GROUP_S'] = "Benutzergruppe(n)";
$_ARRAYLANG['TXT_CORE_USER_NONE_SPECIFIED'] = 'Keine Angabe';
$_ARRAYLANG['TXT_CORE_USER_GENDER_UNDEFINED'] = 'Keine Angabe';
$_ARRAYLANG['TXT_CORE_USER_GENDER_FEMALE'] = 'Weiblich';
$_ARRAYLANG['TXT_CORE_USER_GENDER_MALE'] = 'Männlich';

$_ARRAYLANG['TXT_CORE_USER_PASSWORD_TOO_SHORT'] = "Zu kurz";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_WEAK'] = "Schwach";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_GOOD'] = "Gut";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_STRONG'] = "Stark";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_INVALID'] = "Ungültig";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_MINIMAL_CHARACTERS'] = "Das Passwort muss mindestens 6 Zeichen lang sein.<br /><br />Die Komplexitätsvoraussetzung kann in den Grundeinstellungen aktiviert werden.";
$_ARRAYLANG['TXT_CORE_USER_PASSWORD_MINIMAL_CHARACTERS_WITH_COMPLEXITY'] = "Das Passwort muss mindestens 6 Zeichen lang sein und mindestens einen Gross-, einen Kleinbuchstaben und eine Zahl enthalten.<br /><br />Die Komplexitätsvoraussetzung kann in den Grundeinstellungen deaktiviert werden.";

$_ARRAYLANG['TXT_CORE_USER_NO_ADMINISTRATOR'] = "Kein Administrator";
$_ARRAYLANG['TXT_CORE_USER_ADMINISTRATOR'] = "Administrator";

$_ARRAYLANG['TXT_CORE_USER_FILTER'] = 'Filter';
$_ARRAYLANG['TXT_CORE_USER_ALL'] = 'Alle';
$_ARRAYLANG['TXT_CORE_SELECT_GROUP'] = 'Gruppe auswählen';
$_ARRAYLANG['TXT_CORE_USER_ACCOUNT'] = "Benutzerkonto";
$_ARRAYLANG['TXT_CORE_USER_ROLE'] = "Benutzerrolle";
$_ARRAYLANG['TXT_CORE_USER_STATUS'] = "Benutzerstatus";
$_ARRAYLANG['TXT_CORE_USER_ONLY_CRM'] = "Nur CRM";
$_ARRAYLANG['TXT_CORE_USER_ACTIVE'] = "Aktiv";
$_ARRAYLANG['TXT_CORE_USER_INACTIVE'] = "Inaktiv";
$_ARRAYLANG['TXT_CORE_USER_ADMINISTRATORS'] = "Administratoren";
$_ARRAYLANG['TXT_CORE_USER_USERS'] = "Benutzer";
$_ARRAYLANG['TXT_CORE_USER_NO_USER_WITH_SAME_ID'] = "Sie können Ihr eigenes Benutzerkonto nicht deaktivieren.";
$_ARRAYLANG['TXT_CORE_USER_ADD_DAM_CATEGORY'] = "Für diesen Benutzer eine neue, persönliche und nur für ihn zugängliche Kategorie beim Digital Asset Management Modul hinzufügen.";
$_ARRAYLANG['TXT_CORE_USER_CUSTOMER_TITLE'] = "Kunde: %s";
$_ARRAYLANG['TXT_CORE_USER_ACCOUNT_GROUP_DESC'] = "Persönliche Gruppe des Benutzers %s";
$_ARRAYLANG['TXT_CORE_USER_NEW_DAM_CATEGORY_CREATED_TXT'] = "Für den Benutzer %s wurde die neue persönliche Kategorie %s im Digital Asset Management Modul erstellt.";
$_ARRAYLANG['TXT_CORE_USER_NOT_FOUND'] = 'Benutzer konnte nicht gefunden werden.';
$_ARRAYLANG['TXT_CORE_USER_NOT_BE_SAVED'] = 'Benutzer konnte nicht gespeichert werden.';
$_ARRAYLANG['TXT_CORE_USER_ATTRIBUTE_NOT_FOUND'] = 'Attribut konnte nicht gefunden werden.';
$_ARRAYLANG['TXT_CORE_USER_DO_NOT_EDIT_USER_ONE'] = 'Dieser Benutzer darf nicht bearbeitet werden!';

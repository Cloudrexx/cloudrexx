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
$_ARRAYLANG['TXT_SETTINGS_MENU_SYSTEM'] = 'Système';
$_ARRAYLANG['TXT_SETTINGS_MENU_CACHE'] = 'Cache';
$_ARRAYLANG['TXT_EMAIL_SERVER'] = 'E-mail server';
$_ARRAYLANG['TXT_SETTINGS_IMAGE'] = 'Bilder';
$_ARRAYLANG['TXT_LICENSE'] = 'License Management';
$_ARRAYLANG['TXT_SETTING_FTP_CONFIG_WARNING'] = 'There are wrong ftp credentials defined in the configurations file (%s) or the ftp connection is disabled. If you don"t fix this issue, cloudrexx probably doesn"t have access to upload or edit files and folders!';
$_ARRAYLANG['TXT_SETTINGS_ERROR_NO_WRITE_ACCESS'] = 'Impossible d"enregistrer les paramètres, car le fichier <strong>%s</strong> est protégé contre l"écriture!<br />Veuillez recommencer après avoir modifié ces droits d"accès!';
$_ARRAYLANG['TXT_SYSTEM_SETTINGS'] = 'Paramètres de base';
$_ARRAYLANG['TXT_ACTIVATED'] = 'Activé';
$_ARRAYLANG['TXT_DEACTIVATED'] = 'Déactivé';
$_ARRAYLANG['TXT_CORE_CONFIG_SITE'] = 'Site WEB';
$_ARRAYLANG['TXT_CORE_CONFIG_ADMINISTRATIONAREA'] = 'Administration area';
$_ARRAYLANG['TXT_CORE_CONFIG_SECURITY'] = 'Security';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTINFORMATION'] = 'Contact';
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
$_ARRAYLANG['TXT_SAVE'] = 'Enregistrer';
$_ARRAYLANG['TXT_CORE_CONFIG_SYSTEMSTATUS'] = 'Statut de la page';
$_ARRAYLANG['TXT_CORE_CONFIG_SYSTEMSTATUS_TOOLTIP_HELP'] = 'Page activée? - Statut (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_COREIDSSTATUS'] = 'Système de sécurité';
$_ARRAYLANG['TXT_CORE_CONFIG_COREIDSSTATUS_TOOLTIP_HELP'] = 'Statut du reporting de la détection d"intrusion Cloudrexx - (on|off)';
$_ARRAYLANG['TXT_CORE_CONFIG_XMLSITEMAPSTATUS'] = 'XML Sitemap';
$_ARRAYLANG['TXT_CORE_CONFIG_XMLSITEMAPSTATUS_TOOLTIP_HELP'] = 'Statut de la génération automatique du plan du site XML Sitemap - (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_COREGLOBALPAGETITLE'] = 'Titre de page général';
$_ARRAYLANG['TXT_CORE_CONFIG_COREGLOBALPAGETITLE_TOOLTIP_HELP'] = 'Titre de page général, utilisable dans les modèles de page via la variable [[GLOBAL_TITLE]].';
$_ARRAYLANG['TXT_SETTINGS_DOMAIN_URL'] = 'URL de la page d"accueil';
$_ARRAYLANG['TXT_SETTINGS_DOMAIN_URL_HELP'] = 'Veuillez saisir l"URL de votre site, SANS slash ( / ) final!';
$_ARRAYLANG['TXT_CORE_CONFIG_COREPAGINGLIMIT'] = 'Nombre de résultats par page';
$_ARRAYLANG['TXT_CORE_CONFIG_COREPAGINGLIMIT_TOOLTIP_HELP'] = 'Valeurs valides: entre 1 et 200';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHDESCRIPTIONLENGTH'] = 'Nombre de caractères affichés dans les résultats de la recherche';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHDESCRIPTIONLENGTH_TOOLTIP_HELP'] = 'Nombre de caractères pour la description des résultats de la recherche';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIME'] = 'Durée de la session';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIME_TOOLTIP_HELP'] = 'Durée de la session en secondes';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIMEREMEMBERME'] = 'Session length (remember me)';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIMEREMEMBERME_TOOLTIP_HELP'] = 'Session length in seconds for users which have set the checkbox "Remember me" at login.';
$_ARRAYLANG['TXT_CORE_CONFIG_DNSSERVER'] = 'Serveur DNS';
$_ARRAYLANG['TXT_CORE_CONFIG_COREADMINNAME'] = 'Nom de l"administrateur';
$_ARRAYLANG['TXT_CORE_CONFIG_COREADMINEMAIL'] = 'E-mail de l"administrateur';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFORMEMAIL'] = 'Adresse E-mail du formulaire de contact (standard)';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFORMEMAIL_TOOLTIP_HELP'] = 'Adresses E-mail des destinataires du formulaire standard (formulaires sans id). Séparer les différentes adresses par des virgules.';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTCOMPANY'] = 'Company';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTADDRESS'] = 'Address';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTZIP'] = 'ZIP-Code';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTPLACE'] = 'Place';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTCOUNTRY'] = 'Country';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTPHONE'] = 'Phone';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFAX'] = 'Fax';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHVISIBLECONTENTONLY'] = 'Rechercher uniquement dans les pages visibles';
$_ARRAYLANG['TXT_CORE_CONFIG_LANGUAGEDETECTION'] = 'Auto-détection de la langue';
$_ARRAYLANG['TXT_CORE_CONFIG_LANGUAGEDETECTION_TOOLTIP_HELP'] = 'Ce paramètre permet de choisir automatiquement la langue en fonction de celle utilisée par le Navigateur Internet de l"utilisateur.';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEMAPSAPIKEY_TOOLTIP_HELP'] = 'Clé globale Google-Map API pour le domaine principal. <br />Les clés peuvent être obtenues chez: http://code.google.com/apis/maps/signup.html';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEMAPSAPIKEY'] = 'Clé globale Google-Map API';
$_ARRAYLANG['TXT_CORE_CONFIG_FRONTENDEDITINGSTATUS'] = 'Edition au Frontend';
$_ARRAYLANG['TXT_CORE_CONFIG_FRONTENDEDITINGSTATUS_TOOLTIP_HELP'] = 'Avec l"édition au Frontend, vous pouvez modifier votre page sans devoir vous connecter au Backend - Statut (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_USECUSTOMIZINGS'] = 'Customizing';
$_ARRAYLANG['TXT_CORE_CONFIG_USECUSTOMIZINGS_TOOLTIP_HELP'] = 'Use this option to activate customizings found in %1 - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_CORELISTPROTECTEDPAGES'] = 'Inclure pages à accès limité';
$_ARRAYLANG['TXT_CORE_CONFIG_CORELISTPROTECTEDPAGES_TOOLTIP_HELP'] = 'Inclus les pages à accès limités dans la Navigation, la recherche plein texte et le plan du site (Sitemap et XML-Sitemap) pour les utilisateurs non authentifiés - Statut  (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_TIMEZONE'] = 'Timezone';
$_ARRAYLANG['TXT_CORE_CONFIG_DASHBOARDNEWS'] = 'Dashboard news';
$_ARRAYLANG['TXT_CORE_CONFIG_DASHBOARDSTATISTICS'] = 'Dashboard statistics';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEANALYTICSTRACKINGID'] = 'Google Analytics Tracking ID';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEANALYTICSTRACKINGID_TOOLTIP_HELP'] = 'Enter your Google Analytics tracking ID here. These can be found in your Google Analytics account under Admin => Tracking Code.';
$_ARRAYLANG['TXT_CORE_CONFIG_DEFAULTMETAIMAGE'] = 'Image meta par défaut';
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
$_ARRAYLANG['TXT_SETTINGS_UPDATED'] = 'Paramètres mis à jour.';
$_ARRAYLANG['TXT_SETTINGS_ERROR_WRITABLE'] = 'n"a pas pu être mis à jour. Vérifiez les privilèges de fichier (666).';
$_ARRAYLANG['TXT_SETTINGS_DEFAULT_SMTP_CHANGED'] = 'Le compte SMTP %s sera désormais utilisé comme compte par défaut pour l"envoi d"E-mails.';
$_ARRAYLANG['TXT_SETTINGS_CHANGE_DEFAULT_SMTP_FAILED'] = 'Impossible de modifier le compte standard!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_DELETE_SUCCEED'] = 'Compte SMTP %s supprimé.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_DELETE_FAILED'] = 'Erreur lors de la suppression du compte SMTP %s!';
$_ARRAYLANG['TXT_SETTINGS_COULD_NOT_DELETE_DEAULT_SMTP'] = 'Impossible de supprimer le compte SMTP %s, car il est défini comme compte par défaut!';
$_ARRAYLANG['TXT_SETTINGS_EMAIL_ACCOUNTS'] = 'Comptes E-Mail';
$_ARRAYLANG['TXT_SETTINGS_ACCOUNT'] = 'Compte';
$_ARRAYLANG['TXT_SETTINGS_HOST'] = 'Host';
$_ARRAYLANG['TXT_SETTINGS_USERNAME'] = 'Identifiant';
$_ARRAYLANG['TXT_SETTINGS_STANDARD'] = 'Standard';
$_ARRAYLANG['TXT_SETTINGS_FUNCTIONS'] = 'Fonctions';
$_ARRAYLANG['TXT_SETTINGS_ADD_NEW_SMTP_ACCOUNT'] = 'Créer un nouveau compte SMTP';
$_ARRAYLANG['TXT_SETTINGS_CONFIRM_DELETE_ACCOUNT'] = 'Êtes-vous sûr de vouloir supprimer le compte SMTP %s?';
$_ARRAYLANG['TXT_SETTINGS_OPERATION_IRREVERSIBLE'] = 'Cette opération est irréversible!';
$_ARRAYLANG['TXT_SETTINGS_MODFIY'] = 'Editer';
$_ARRAYLANG['TXT_SETTINGS_DELETE'] = 'Supprimer';
$_ARRAYLANG['TXT_SETTINGS_EMPTY_ACCOUNT_NAME_TXT'] = 'Veuillez saisir un nom de compte!';
$_ARRAYLANG['TXT_SETTINGS_NOT_UNIQUE_SMTP_ACCOUNT_NAME'] = 'Veuillez utiliser un autre compte: le compte %s est déjà utilisé';
$_ARRAYLANG['TXT_SETTINGS_EMPTY_SMTP_HOST_TXT'] = 'Veuillez saisir un serveur SMTP!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_UPDATE_SUCCEED'] = 'Compte SMTP %s mis à jour.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_UPDATE_FAILED'] = 'Erreur lors de la mise à jour du compte SMTP!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_ADD_SUCCEED'] = 'Compte SMTP ajouté.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_ADD_FAILED'] = 'Erreur lors de l"ajout du nouveau compte SMTP!';
$_ARRAYLANG['TXT_SETTINGS_MODIFY_SMTP_ACCOUNT'] = 'Editer le compte SMTP';
$_ARRAYLANG['TXT_SETTINGS_NAME_OF_ACCOUNT'] = 'Nom du compte';
$_ARRAYLANG['TXT_SETTINGS_SMTP_SERVER'] = 'Serveur SMTP';
$_ARRAYLANG['TXT_SETTINGS_PORT'] = 'Port';
$_ARRAYLANG['TXT_SETTINGS_AUTHENTICATION'] = 'Authentification';
$_ARRAYLANG['TXT_SETTINGS_PASSWORD'] = 'Mot de passe';
$_ARRAYLANG['TXT_SETTINGS_SMTP_AUTHENTICATION_TXT'] = 'N"indiquer ni identifiant ni mot de passe si le serveur SMTP n"exige aucune authentification.';
$_ARRAYLANG['TXT_SETTINGS_BACK'] = 'Retour';
$_ARRAYLANG['TXT_SETTINGS_SAVE'] = 'Enregistrer';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_TITLE'] = 'Bildeinstellungen';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_CUT_WIDTH'] = 'Standardbreite für die Bildzuschneidung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_CUT_HEIGHT'] = 'Standardhöhe für die Bildzuschneidung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_SCALE_WIDTH'] = 'Standardbreite für die Skalierung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_SCALE_HEIGHT'] = 'Standardhöhe für die Skalierung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_COMPRESSION'] = 'Standardwert für die Kompression';

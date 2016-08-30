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
 * @subpackage  coremodule_alias
 */
$_ARRAYLANG['TXT_ALIAS_ALIASES'] = "SEO URL Aliases";
$_ARRAYLANG['TXT_ALIAS_ALIAS'] = "SEO URL Alias";
$_ARRAYLANG['TXT_ALIAS_SETTINGS'] = "Paramètres";
$_ARRAYLANG['TXT_ALIAS_DELETE'] = "Supprimer";
$_ARRAYLANG['TXT_ALIAS_MODIFY'] = "Editer";
$_ARRAYLANG['TXT_ALIAS_PAGE'] = "Page";
$_ARRAYLANG['TXT_ALIAS_ALIAS_ES'] = "Alias";
$_ARRAYLANG['TXT_ALIAS_FUNCTIONS'] = "Fonctions";
$_ARRAYLANG['TXT_ALIAS_MODIFY_ALIAS'] = "Editer alias";
$_ARRAYLANG['TXT_ALIAS_ADD_ALIAS'] = "Nouvel alias";
$_ARRAYLANG['TXT_ALIAS_TARGET_PAGE'] = "Page cible";
$_ARRAYLANG['TXT_ALIAS_LOCAL'] = "Local";
$_ARRAYLANG['TXT_ALIAS_URL'] = "URL";
$_ARRAYLANG['TXT_ALIAS_BROWSE'] = "Parcourir les pages Web";
$_ARRAYLANG['TXT_ALIAS_CONFIRM_REMOVE_ALIAS'] = "Voulez-vous vraiment supprimer l'alias %s?";
$_ARRAYLANG['TXT_ALIAS_ADD_ANOTHER_ALIAS'] = "Ajouter d'autres alias";
$_ARRAYLANG['TXT_ALIAS_SAVE'] = "Enregistrer";
$_ARRAYLANG['TXT_ALIAS_CANCEL'] = "Annuler";
$_ARRAYLANG['TXT_ALIAS_ONE_ALIAS_REQUIRED_MSG'] = "Définir au moins un alias!";
$_ARRAYLANG['TXT_ALIAS_URL_REQUIRED_MSG'] = "Saisir un RUL!";
$_ARRAYLANG['TXT_ALIAS_PAGE_REQUIRED_MSG'] = "Sélectionner une page Web!";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_UPDATED'] = "Alias mis à jour.";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_ADDED'] = "Alias ajouté.";
$_ARRAYLANG['TXT_ALIAS_ALIAS_UPDATE_FAILED'] = "Erreur lors de la mise à jour de l'alias";
$_ARRAYLANG['TXT_ALIAS_ALIAS_ADD_FAILED'] = "Erreur lors de la création de l'alias";
$_ARRAYLANG['TXT_ALIAS_RETRY_OPERATION'] = "Veuillez essayer de répéter l'opération!";
$_ARRAYLANG['TXT_ALIAS_ALREADY_IN_USE'] = "L'alias %s est déjà utilisé!";
$_ARRAYLANG['TXT_ALIAS_OPERATION_IRREVERSIBLE'] = "Cette opération est irréversible!";
$_ARRAYLANG['TXT_ALIAS_CONFIRM_DELETE_ALIAS'] = "Voulez-vous vraiment supprimer l'alias pour la page %s?";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_REMOVED'] = "Alias supprimé.";
$_ARRAYLANG['TXT_ALIAS_ALIAS_REMOVE_FAILED'] = "Erreur lors de la suppression de l'alias";
$_ARRAYLANG['TXT_ALIAS_NO_ALIASES_MSG'] = "Auncun alias défini!";
$_ARRAYLANG['TXT_ALIAS_REQUIREMENTS_DESC'] = "Le gestion d'alias fonctionne uniquement sur un serveur <strong>Apache</strong> avec le module <strong>mod_rewrite</strong> activé.<br />De plus, le serveur doit autoriser l'utilisation de fichiers <strong>.htaccess</strong>.";
$_ARRAYLANG['TXT_ALIAS_USE_ALIAS_ADMINISTRATION'] = "Utiliser la gestion d'alias";
$_ARRAYLANG['TXT_ALIAS_APACHE_MISSING'] = "La gestion des alias ne peut pas fonctionner sur ce serveur, car il ne s'agit pas d'un serveur Apache";
$_ARRAYLANG['TXT_ALIAS_MOD_REWRITE_MISSING'] = "Impossible de déterminer si le module <strong>mod_rewrite</strong> est actif.<br />Par précaution, la gestion des alias n'a pas été activée, car, si le module <strong>mod_rewrite</strong> n'était pas actif, le site deviendrait inaccessible.<br /><br />Si vous êtes sûr que le module <strong>mod_rewrite</strong> est actif, vous pouvez activer manuellement la gestion des alias en créant un fichier <strong>.htaccess</strong> dans le répertoire racine du site avec le contenu suivant:<br /><div style=\"margin:10px;\"><code>RewriteEngine On</code></div>Ainsi, à votre prochaine visite de la gestion des alias, celle-ci sera automatiquement activée ";
$_ARRAYLANG['TXT_ALIAS_HTACCESS_HINT'] = "Assurez-vous que l'utilisation de <strong>.htaccess</strong> est supportée par ce serveur.";
$_ARRAYLANG['TXT_ALIAS_CONFIG_SUCCESSFULLY_APPLYED'] = "Paramètres appliqués.";
$_ARRAYLANG['TXT_ALIAS_CONFIG_FAILED_APPLY'] = "Erreur lors de l'enregistrement de la configuration";
$_ARRAYLANG['TXT_ALIAS_TARGET_PAGE_NOT_EXIST'] = "Page cible inexistante";
$_ARRAYLANG['TXT_ALIAS_MUST_NOT_BE_A_FILE'] = "L'alias %s ne peut pas être utilisé, car il existe physiquement!";
$_ARRAYLANG['TXT_ALIAS_TARGET_ALREADY_IN_USE'] = "Il existe déjà un alias pour la page %s!";
$_ARRAYLANG['TXT_ALIAS_NOT_ACTIVE_ALIAS_MSG'] = "Cet alias n'est plus actif!<br />Cliquer pour le réactiver";
$_ARRAYLANG['TXT_ALIAS_STANDARD_RADIOBUTTON'] = "Utiliser comme défaut:";
$_ARRAYLANG['TXT_ALIAS_OPEN_ALIAS_NEW_WINDOW'] = "Ouvrir Alias dans une nouvelle fenêtre";
$_ARRAYLANG['TXT_ALIAS_IIS_HTACCESS_NOT_REGISTERED'] = "The file &ldquo;web.config&rdquo; is not registered in the server configuration. Please contact the server administrator and request a corresponding entry.";
?>

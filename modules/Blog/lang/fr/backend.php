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
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_TITLE'] = "Général";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION'] = "Nombre de caractères dans le texte d'introduction";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION_HELP'] = "Nombre de caractères dans le texte d'accroche. Pour afficher systématiquement le texte complet, saisir 0.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TITLE'] = "Commentaires";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW'] = "Autoriser commentaires";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_HELP'] = "Permettre aux visiteurs d'écrire des commentaires sur vos articles.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS'] = "Autoriser commentaires anonymes";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_HELP'] = "Permettre également à des visiteurs qui ne se sont pas inscrits de saisir des commentaires.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE'] = "Publier automatiquement les nouveaux commentaires";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE_HELP'] = "Publier immédiatement tous les commentaires. Si cet option est inactive, les commentaires ne seront visibles qu'une fois que vous les aurez publiés individuellement.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION'] = "Avertissement lors de nouveaux commentaires";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION_HELP'] = "Vous êtes avertis par E-mail à chaque nouveau commentaire saisi.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT'] = "Temps d'attente entre commentaires";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT_HELP'] = "Durée minimale, exprimée en secondes, entre deux commentaires d'un même utilisateur. Ceci permet d'éviter l'utilisation démesurée des commentaires. La valeur conseillée est de 30 secondes.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR'] = "Auteur";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_HELP'] = "Défini le type d'éditeur mis à la disposition des visiteurs pour saisir leurs commentaires.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_WYSIWYG'] = "Editeur WYSIWYG";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_TEXTAREA'] = "Texte brut";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_TITLE'] = "Evaluation";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW'] = "Autoriser les évaluations";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW_HELP'] = "Donne aux visiteurs la possibilité d'évaluer vos articles.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_TITLE'] = "Mots clés";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST'] = "Hitparade des mots clés";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST_HELP'] = "Nombre de mots clés listés dans le hitparade des mots clés.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_TITLE'] = "RSS";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE'] = "Activer flux RSS ";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE_HELP'] = "Mettre à disposition un flux RSS de votre blog, qui permette la syndication d'autres sites à votre contenu. Avec cette option, le répertoire  <pre>feed/</pre> est alimenté avec vos articles dans des fichiers de la forme  <pre>blog_messages_XX.xml<br />blog_comments_XX.xml<br />blog_category_ID_XX.xml</pre>, où XX est remplacé par le code langue et ID par l'ID de la catégorie.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES'] = "Nombre d'articles";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES_HELP'] = "Nombre d'articles contenus dans le fichier XML. Il s'agit toujours des articles les plus récents.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS'] = "Nombre de commentaires";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS_HELP'] = "Nombre de commentaires contenus dans le fichier XML. Il s'agit toujours des commentaires les plus récents.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_TITLE'] = "Modèle de brique";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE'] = "Activer la fonction brique";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE_HELP'] = "Cette fonction permet d'utiliser la variable <pre>[[BLOG_FILE]]</pre> à différents endroits dans le système, qui sera alors remplacée par le fichier <pre>blog.html</pre>. D'autres variables sont également disponibles sous l'onglet blog.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES'] = "Nombre d'articles";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES_HELP'] = "Nombre d'articles affichées. Ce sont toujours les plus récents qui seront affichés.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE'] = "Utilisation";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE_HELP'] = "Toutes les variables ci-dessous peuvent être utilisées dans le fichier <b>blog.html</b> d'un modèle de présentation (dans le menu \"Gestion de la présentation\"). Ce fichier peut ensuite être inséré à l'aide de la variable <b>[[BLOG_FILE]]</b> dans n'importe quel autre fichier de présentation (index.html, home.html, content.html und sidebar.html) ou même directement dans le contenu d'une page (Gestionnaire de contenu).<br /><br />De plus, les variables de l'onglet <b>Général</b> peuvent aussi être utilisées directement dans les fichers de présentations (index.html, home.html, content.html und sidebar.html) sans passer par le fichier <b>blog.html</b>.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_SAVE_SUCCESSFULL'] = "Paramètres mis à jour.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIVE_LANGUAGES'] = "Langues actives";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'] = "Fonctions";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_NO_CATEGORIES'] = "Pas de catégorie définies";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ASSIGNED_MESSAGES'] = "Aperçu des articles de cette catégorie";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'] = "Sélection";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'] = "Sélectionner tout";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'] = "Désélectionner tout";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'] = "Choisir la fonction";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTIVATE'] = "Activer les éléments sélectionnés";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DEACTIVATE'] = "Désactiver les éléments sélectionnés";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'] = "Supprimer les éléments sélectionnés";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE_JS'] = "Voulez-vous vraiment supprimer toutes les catégories sélectionnées? Cette opération est irréversible!";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_NAME'] = "Nom";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_EXTENDED'] = "Etendu";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'] = "Langues";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_SUCCESSFULL'] = "Catégorie créée.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_ERROR_ACTIVE'] = "Impossible de créer la catégorie: au moins une langue doit être activée pour la nouvelle catégorie";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_TITLE'] = "Supprimer catégorie";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_JS'] = "Voulez-vous vraiment supprimer cette catégorie?";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_SUCCESSFULL'] = "Catégorie supprimée.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_ERROR'] = "Impossible de supprimer la catégorie avec cet ID.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_TITLE'] = "Editer catégorie";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_ERROR_ID'] = "Il n'existe pas de catégorie avec cet ID.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_SUCCESSFULL'] = "Catégorie mise à jour.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_ERROR_ACTIVE'] = "Impossible de mettre à jour la catégorie: au moins une langue doit être activée pour la nouvelle catégorie";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'] = "Titre";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_KEYWORDS'] = "Mots clés";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE'] = "Image";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'] = "Parcourir";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES'] = "Catégories";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUCCESSFULL'] = "Article créé.";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_ERROR_LANGUAGES'] = "Veuillez publier le nouvel article au moins dans une langue.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_DATE'] = "Publication";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_HITS'] = "Hits";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENT'] = "Commentaire";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'] = "Commentaires";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTE'] = "Evaluation";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTES'] = "Evaluations";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_UPDATED'] = "Dernière mise à jour";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_SUBMIT_DELETE_JS'] = "Voulez-vous vraiment supprimer tous les articles sélectionnés? Cette opération est irréverible!";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_NO_ENTRIES'] = "Aucun article.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_PAGING'] = "Articles";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_TITLE'] = "Supprimer article";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_JS'] = "Voulez-vous vraiment supprimer cet article?";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_SUCCESSFULL'] = "Article supprimé.";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_ERROR_ID'] = "Il n'existe aucun article avec cet ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_TITLE'] = "Editer article";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_ERROR_ID'] = "Il n'existe aucun article avec cet ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_SUCCESSFULL'] = "Article mis à jour.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_ERROR_LANGUAGES'] = "L'article doit être activé dans au moins une langue.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_TITLE'] = "Evaluation du thème";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_COUNT'] = "Nombre d'évaluation";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_AVG'] = "Evaluation moyenne.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS'] = "Statistiques";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS_NONE'] = "Aucune statistique disponible pour ce thème.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DETAILS'] = "Evaluation";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DATE'] = "Date & heure";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_IP'] = "Adresse IP";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_JS'] = "Voulez-vous vraiment supprimer cette évaluation?";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_SUCCESSFULL'] = "Evaluation supprimée.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_SUBMIT_DELETE_JS'] = "Voulez-vous vraiment supprimer toutes les évaluations sélectionnées?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_NONE'] = "Aucun commentaire sur ce thème pour l'instant.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_LANGUAGE'] = "Langue";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_STATUS'] = "Activer / Désactiver commentaire";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT'] = "Editer commentaire";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE'] = "Supprimer commentaire";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_SUCCESSFULL'] = "Commentaire supprimé.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS'] = "Voulez-vous vraiment supprimer ce commentaire?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS_ALL'] = "Voulez-vous vraiment supprimer tous les commentaires sélectionnés?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_ERROR'] = "Il n'existe aucun commentaire avec cet ID.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS'] = "Statut utilisateur";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_REGISTERED'] = "Utilisateur inscrit";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_UNREGISTERED'] = "Utilisateur non inscrit";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_NAME'] = "Identifiant";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_WWW'] = "Site Web";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_SUCCESSFULL'] = "Commentaire mis à jour.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_ERROR'] = "Impossible de mettre à jour le commentaire. Vous avez probablement saisi des données invalides.";
$_ARRAYLANG['TXT_BLOG_BLOCK_ERROR_DEACTIVATED'] = "La gestion des briques est déactivée. Veuillez tout d'abord l'activer dans les paramères.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TITLE'] = "Général";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CALENDAR'] = "Calendrier du mois en cours. Les jours contenant des messages sont mis en évidence.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_SELECT'] = "Menu déroulant avec la liste des catégories, permettant de filtrer les articles d'une catégorie.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_LIST'] = "Liste de toutes les catégories, permettant de filtrer les articles d'une catégorie.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGCLOUD'] = "Nuage de mots-clés (tag cloud).";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGHITLIST'] = "Hit parade des mots clés";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_TITLE'] = "Articles";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_LINK'] = "lien";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROW'] = "Toutes les variables ci-dessous doivent être utilisées au sein de ce bloc: <pre><!-- BEGIN latestBlogMessages--><br />...<br /><!-- END latestBlogMessages --></pre>";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROWCLASS'] = "Classe CSS pour les lignes de tableaux";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ID'] = "Id unique de la catégorie";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_DATE'] = "Date de l'article";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_POSTEDBY'] = "Date et auteur";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_SUBJECT'] = "Titre de l'article";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_INTRODUCTION'] = "Texte d'accroche de l'article";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CONTENT'] = "Texte de l'article";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_ID'] = "ID unique de l'auteur";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_NAME'] = "Nom de l'auteur";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CATEGORIES'] = "Catégorie de l'article";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_TAGS'] = "Mots clés de l'article";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COMMENTS'] = "Nombre de commentaires sur l'article";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_VOTING'] = "Evaluation moyenne de l'article";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_STARS'] = "Evaluation des articles à l'aide d'étoiles";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_LINK'] = "Lien sur le détail";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_IMAGE'] = "Image associée";
$_ARRAYLANG['TXT_BLOG_BLOCK_CATEGORY_TITLE'] = "Catégories";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_NAME'] = "Nom de catégorie";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COUNT'] = "Nombre d'articles dans cette catégorie";
$_ARRAYLANG['TXT_BLOG_BLOCK_TEXT'] = "Texte";
$_ARRAYLANG['TXT_BLOG_BLOCK_CONTENT'] = "Contenu";
$_ARRAYLANG['TXT_BLOG_BLOCK_EXAMPLE'] = "Exemple de code";
$_ARRAYLANG['TXT_BLOG_NETWORKS'] = "Réseaux";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_NONE'] = "Encore aucun fournisseur en réseau";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE'] = "Supprimer les éléments sélectionnés";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE_JS'] = "Voulez-vous vraiment supprimer tous les fournisseurs sélectionnés?";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_TITLE'] = "Nouveau réseau";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_NAME'] = "Nom du fournisseur";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_WWW'] = "URL du fournisseur";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_SUBMIT'] = "URL pour la soumission d'articles";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_ICON'] = "Icône du fournisseur";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_BROWSE'] = "Parcourir";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_SUCCESSFULL'] = "Fournisseur ajouté.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_ERROR'] = "Impossible de créer le fournisseur. Une ou plusieurs données sont invalides.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_TITLE'] = "Editer fournisseur";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_ERROR'] = "Il n'existe aucun fournisseur avec cet ID.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_SUCCESSFULL'] = "Fournisseur mis à jour";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_ERROR'] = "Impossible de mettre à jour le fournisseur. Une ou plusieurs données sont invalides.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_TITLE'] = "Supprimer fournisseur";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_JS'] = "Voulez-vous vraiment supprimer ce fournisseur";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_SUCCESSFULL'] = "Fournisseur supprimé";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_ERROR'] = "Aucun réseau avec cet ID";
$_ARRAYLANG['TXT_BLOG_LIB_POSTED_BY'] = "Ecrit par [USER] le [DATE]";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_WEEKDAYS'] = "Di,Lu,Ma,Me,Je,Ve,Sa";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_MONTHS'] = "janvier,février,mars,avril,mai,juin,juillet,août,septembre,octobre,novembre,décembre";
$_ARRAYLANG['TXT_BLOG_LIB_RATING'] = "Evaluation";
$_ARRAYLANG['TXT_BLOG_LIB_ALL_CATEGORIES'] = "Toutes les catégories";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'] = "Articles";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_COMMENTS_TITLE'] = "Commentaires";
?>

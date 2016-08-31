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
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_TITLE'] = "General";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION'] = "N&uacute;mero de caracteres en la introducci&oacute;n";
$_ARRAYLANG['TXT_BLOG_SETTINGS_GENERAL_INTRODUCTION_HELP'] = "Este valor define el n&uacute;mero de caracteres usado en la introducci&oacute;n. si desea ver el texto completo en lugar de una introducci&oacute;n corta debe usar el valor 0.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TITLE'] = "Comentarios";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW'] = "Permitir comentarios";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_HELP'] = "Si desea que los visitantes puedan escribir comentarios sobre tus mensajes debes activar esta opci&oacute;n.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS'] = "Permitir comentarios an&oacute;nimos";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_ALLOW_ANONYMOUS_HELP'] = "Si deseas que los usuarios no registrados puedan escribir comentarios debes activar esta copci&oacute;n.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE'] = "Activar comentarios de forma autom&aacute;tica";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_AUTO_ACTIVATE_HELP'] = "Marcando esta opci&oacute;n los nuevos comentarios ser&aacute;n activados de forma autom&aacute;tica. De no ser as&iacute; estos deben ser activados manualmente por el administrador.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION'] = "Notificar nuevos comentarios";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_NOTIFICATION_HELP'] = "Si esta opci&oacute;n est&aacute; activada, recibir&aacute;s un email cuando se creen nuevos comentarios.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT'] = "Tiempo de espera entre dos comentarios";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_TIMEOUT_HELP'] = "Este valor indica cuantos segundos deben transcurrir entre dos comentarios del mismo usuario. Esto evita la sobrecarga de comentarios y el spam. Nosotros recomendamos el uso de 30 segundos.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR'] = "Editor";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_HELP'] = "Determina que editor les est&aacute; permitido usar a tus visitas para escribir sus comentarios.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_WYSIWYG'] = "Editor WYSIWYG";
$_ARRAYLANG['TXT_BLOG_SETTINGS_COMMENTS_EDITOR_TEXTAREA'] = "&Aacute;rea de texto";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_TITLE'] = "Valoraci&oacute;n";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW'] = "Permitir votaciones";
$_ARRAYLANG['TXT_BLOG_SETTINGS_VOTING_ALLOW_HELP'] = "Si desea que los visitantes puedan votar tus mensajes debes activar esta opci&oacute;n.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_TITLE'] = "Palabras clave";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST'] = "Palabras clave dentro del ranking";
$_ARRAYLANG['TXT_BLOG_SETTINGS_TAG_HITLIST_HELP'] = "Determina el n&uacute;mero de palabras del ranking.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_TITLE'] = "RSS";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE'] = "Activar Feeds RSS";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_ACTIVATE_HELP'] = "Si el sistema debe crear un Feed RSS de tu Blog, debes activar esta opci&oacute;n. Los ficheros <pre>blog_messages_XX.xml<br/>blog_comments_XX.xml<br/>blog_category_ID_XX.xml</pre> ser&aacute;n creados en la carpeta <pre>feed</pre>. La variable XX sirve para el control del idioma.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES'] = "N&uacute;mero de mensajes";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_MESSAGES_HELP'] = "N&uacute;mero de mensajes en el fichero XML. El sistema siempre usa los mensajes mas nuevos.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS'] = "N&uacute;mero de comentarios";
$_ARRAYLANG['TXT_BLOG_SETTINGS_RSS_COMMENTS_HELP'] = "N&uacute;mero de comentarios en el fichero XML. El sistema siempre usa los comentarios mas nuevos.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_TITLE'] = "Bloqueos";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE'] = "Activar bloqueo";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_ACTIVATE_HELP'] = "Si activas esta funci&oacute;n la variable <pre>[[BLOG_FILE]]</pre> ser&aacute; reemplazada por el fichero <pre>blog.html</pre>. Puedes usarl a variable mencionada en varios lugares.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES'] = "N&uacute;mero de mensajes";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_MESSAGES_HELP'] = "N&uacute;mero de mensajes que ser&aacute;n mostrados. El sistema siempre usa los mensajes mas nuevos.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE'] = "Uso";
$_ARRAYLANG['TXT_BLOG_SETTINGS_BLOCK_USAGE_HELP'] = "Todas las variables que se muestran pueden ser usadas en su dise&ntilde;o (Plantillas y Dise&ntilde;o) en el fichero <b>blog.html</b>. El fichero mencionado puede ser incluido con la variable <b>[[BLOG_FILE]]</b> en uno de los siguientes ficheros: index.html, home.html, content.html y sidebar.html  Adem&aacute;s es posible usarlo dentro de cualquier p&aacute;gina del gestor de contenidos.";
$_ARRAYLANG['TXT_BLOG_SETTINGS_SAVE_SUCCESSFULL'] = "La configuraci&oacute;n ha sido actualizada con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIVE_LANGUAGES'] = "Idiomas activos";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ACTIONS'] = "Acciones";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_NO_CATEGORIES'] = "No existen categor&iacute;as.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_ASSIGNED_MESSAGES'] = "Ver mensajes de esta categor&iacute;a";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_MARKED'] = "Selecci&oacute;n";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_SELECT'] = "Seleccionar todo";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DESELECT'] = "Eliminar seleccionados";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTION'] = "Seleccionar acci&oacute;n";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_ACTIVATE'] = "Activar marcados";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DEACTIVATE'] = "Desactivar los seleccionados";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE'] = "Eliminar los marcados";
$_ARRAYLANG['TXT_BLOG_CATEGORY_MANAGE_SUBMIT_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar todas las categor&iacute;as seleccionadas? \¡Esta operaci&oacute;n no se puede deshacer!";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_NAME'] = "Nombre";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_EXTENDED'] = "Expandir";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_LANGUAGES'] = "Idiomas";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_SUCCESSFULL'] = "La nueva categor&iacute;a ha sido a&ntilde;adida con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_ADD_ERROR_ACTIVE'] = "La categor&iacute;a no pudo ser a&ntilde;adida. Debe seleccionar al menos un idioma para la categor&iacute;a.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_TITLE'] = "Eliminar categor&iacute;a";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar esta categor&iacute;a?";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_SUCCESSFULL'] = "La categor&iacute;a ha sido eliminada con &eacute;xito";
$_ARRAYLANG['TXT_BLOG_CATEGORY_DELETE_ERROR'] = "La categor&iacute;a con el ID seleccionado no ha podido ser eliminada.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_TITLE'] = "Editar categor&iacute;a";
$_ARRAYLANG['TXT_BLOG_CATEGORY_EDIT_ERROR_ID'] = "No existe la categor&iacute;a con dicho ID. Por favor, compruebe el ID introducido.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_SUCCESSFULL'] = "La categor&iacute;a ha sido actualizada con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_CATEGORY_UPDATE_ERROR_ACTIVE'] = "La categor&iacute;a no pudo ser aactualizada. Debe seleccionar al menos un idioma para la categor&iacute;a.";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUBJECT'] = "T&iacute;tulo";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_KEYWORDS'] = "Palabras clave";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE'] = "Imagen del mensaje";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_IMAGE_BROWSE'] = "Examinar";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_CATEGORIES'] = "Categor&iacute;s";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_SUCCESSFULL'] = "El nuevo mensaje ha sido a&ntilde;adido con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_ENTRY_ADD_ERROR_LANGUAGES'] = "Debe publicar el mensaje en al menos un idioma.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_DATE'] = "Publicaci&oacute;n";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_HITS'] = "Lecturas";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENT'] = "Comentario";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_COMMENTS'] = "Comentarios";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTE'] = "Valoraci&oacute;n";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_VOTES'] = "Votos";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_UPDATED'] = "&Uacute;ltima actualizaci&oacute;n";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_SUBMIT_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar todas las entradas seleccionadas? \¡Esta operaci&oacute;n no se puede deshacer!";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_NO_ENTRIES'] = "No existen mensajes.";
$_ARRAYLANG['TXT_BLOG_ENTRY_MANAGE_PAGING'] = "Mensajes";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_TITLE'] = "Eliminar mensaje";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar este mensaje?";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_SUCCESSFULL'] = "La entrada ha sido eliminada con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_ENTRY_DELETE_ERROR_ID'] = "No existe la entrada con dicho ID. Por favor, compruebe el ID introducido.";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_TITLE'] = "Editar mensaje";
$_ARRAYLANG['TXT_BLOG_ENTRY_EDIT_ERROR_ID'] = "No existe el mensaje con dicho ID. Por favor, compruebe el ID introducido.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_SUCCESSFULL'] = "El mensaje ha sido actualizado con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_ENTRY_UPDATE_ERROR_LANGUAGES'] = "Debe activar el mensaje en al menos un idioma.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_TITLE'] = "Valoraci&oacute;n del mensaje";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_COUNT'] = "N&uacute;mero de votos";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_AVG'] = "Media de votos";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS'] = "Estad&iacute;sticas";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_STATISTICS_NONE'] = "No existen estad&iacute;sticas para este mensaje.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DETAILS'] = "Valoraci&oacute;n";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DATE'] = "Fecha y hora";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_IP'] = "Direcci&oacute; IP";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar esta votaci&oacute;n?";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_DELETE_SUCCESSFULL'] = "Los votos han sido eliminados con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_ENTRY_VOTES_SUBMIT_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar los votos?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_NONE'] = "No existen comentarios.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_LANGUAGE'] = "Idioma";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_STATUS'] = "Activar / Desactivar comentarios";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT'] = "Editar comentario";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE'] = "Eliminar comentarios";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_SUCCESSFULL'] = "El comentario ha sido eliminado con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar este comentario?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_DELETE_JS_ALL'] = "\¿Est&aacute; seguro de que desea eliminar los comentarios?";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_ERROR'] = "No existe el comentario con dicho ID. Por favor, compruebe el ID introducido.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS'] = "Estado del usuario";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_REGISTERED'] = "Usuario registrado";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_STATUS_UNREGISTERED'] = "Usuario no registrado";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_NAME'] = "Nombre de usuario";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_EDIT_USER_WWW'] = "Web";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_SUCCESSFULL'] = "El comentario ha sido actualizado con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_ENTRY_COMMENTS_UPDATE_ERROR'] = "El comentario no ha podido ser actualizado. Revise los campos ya que al parecer contienen valores no v&aacute;lidos.";
$_ARRAYLANG['TXT_BLOG_BLOCK_ERROR_DEACTIVATED'] = "La funci&oacute;n de bloques est&aacute; desactivada. Por favor, act&iacute;vela primero en configuraci&oacute;n.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TITLE'] = "General";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CALENDAR'] = "Calendario. Los d&iacute;as con mensajes aparecen resaltados";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_SELECT'] = "Seleccione la categor&iacute;a.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_CATEGORIES_LIST'] = "Una lista de todas las categor&iacute;s. Permite ver el mensaje filtrado por la categor&iacute;a seleccionada.";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGCLOUD'] = "Generar una entrada con todas las palabras";
$_ARRAYLANG['TXT_BLOG_BLOCK_GENERAL_TAGHITLIST'] = "Generar una lista de las palabras clave mas populares";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_TITLE'] = "Mensajes";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_LINK'] = "Enlace";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROW'] = "Todos los contenedores tienen que ser usados en un bloque en el Blog: <pre><!-- BEGIN latestBlogMessages--><br />...<br /><!-- END latestBlogMessages --></pre>";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ROWCLASS'] = "Estilo CSS (clase) de la fila";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_ID'] = "Id de la categor&iacute;a";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_DATE'] = "Fecha del mensaje";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_POSTEDBY'] = "Texto que contiene la fecha y el usuario";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_SUBJECT'] = "T&iacute;tulo del mensaje";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_INTRODUCTION'] = "Breve introducci&oacute;n para este mensaje";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CONTENT'] = "Contenido del mensaje";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_ID'] = "ID del autor";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_AUTHOR_NAME'] = "Nombre del autor";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_CATEGORIES'] = "Categor&iacute;a de este mensaje";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_TAGS'] = "Palabras clave del mensaje";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COMMENTS'] = "N&uacute;mero de comentarios";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_VOTING'] = "Media de votos del mensaje";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_STARS'] = "Valoraci&oacute;n del mensaje en estrellas";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_LINK'] = "Enlace a la p&aacute;gin de detalle";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_IMAGE'] = "Im&aacute;genes asignadas";
$_ARRAYLANG['TXT_BLOG_BLOCK_CATEGORY_TITLE'] = "Categor&iacute;s";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_NAME'] = "Nombre de la categor&iacute;a";
$_ARRAYLANG['TXT_BLOG_BLOCK_ENTRY_CONTENT_COUNT'] = "N&uacute;mero de mensajes en esta categor&iacute;a";
$_ARRAYLANG['TXT_BLOG_BLOCK_TEXT'] = "Texto";
$_ARRAYLANG['TXT_BLOG_BLOCK_CONTENT'] = "Contenido";
$_ARRAYLANG['TXT_BLOG_BLOCK_EXAMPLE'] = "C&oacute;digo de ejemplo";
$_ARRAYLANG['TXT_BLOG_NETWORKS'] = "Redes";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_NONE'] = "No existen redes.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE'] = "Eliminar los marcados";
$_ARRAYLANG['TXT_BLOG_NETWORKS_OVERVIEW_SUBMIT_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar todas las redes seleccionadas? \¡Esta operaci&oacute;n no se puede deshacer!";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_TITLE'] = "A&ntilde;adir Red (Servicios)";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_NAME'] = "Nombre de la fuente";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_WWW'] = "URL del proveedor";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_SUBMIT'] = "URL para la red";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_ICON'] = "Icono del proveedor";
$_ARRAYLANG['TXT_BLOG_NETWORKS_ADD_BROWSE'] = "Examinar";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_SUCCESSFULL'] = "La nueva red ha sido a&ntilde;adida con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_INSERT_ERROR'] = "Faltan uno o m&aacute;s campos. La red no ha podido ser creada.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_TITLE'] = "Editar red";
$_ARRAYLANG['TXT_BLOG_NETWORKS_EDIT_ERROR'] = "No existe la red con dicho ID. Por favor, compruebe el ID introducido.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_SUCCESSFULL'] = "El red ha sido actualizada con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_UPDATE_ERROR'] = "Faltan uno o m&aacute;s campos. La red no ha podido ser creada.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_TITLE'] = "Eliminar red";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_JS'] = "\¿Est&aacute; seguro de que desea eliminar esta red?";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_SUCCESSFULL'] = "El red ha sido eliminada con &eacute;xito.";
$_ARRAYLANG['TXT_BLOG_NETWORKS_DELETE_ERROR'] = "No se ha podido encontrar la red que est&aacute; intentando eliminar.";
$_ARRAYLANG['TXT_BLOG_LIB_POSTED_BY'] = "a&ntilde;adido por [USER] el [DATE]";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_WEEKDAYS'] = "Do,Lu,Ma,Mi,Ju,Vi,Sa";
$_ARRAYLANG['TXT_BLOG_LIB_CALENDAR_MONTHS'] = "Enero,Febrero,Marzo,Abril,Mayo,Junio,Julio,Agosto,Septiembre,Octubre,Noviembre,Diciembre";
$_ARRAYLANG['TXT_BLOG_LIB_RATING'] = "Valoraci&oacute;n";
$_ARRAYLANG['TXT_BLOG_LIB_ALL_CATEGORIES'] = "Todas las categor&iacute;as";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_MESSAGES_TITLE'] = "Mensajes del Blog";
$_ARRAYLANG['TXT_BLOG_LIB_RSS_COMMENTS_TITLE'] = "Comentarios del Blog";
?>

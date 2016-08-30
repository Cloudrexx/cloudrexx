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
$_ARRAYLANG['TXT_ALIAS_SETTINGS'] = "Configuraci&oacute;n";
$_ARRAYLANG['TXT_ALIAS_DELETE'] = "Eliminar";
$_ARRAYLANG['TXT_ALIAS_MODIFY'] = "Modificar";
$_ARRAYLANG['TXT_ALIAS_PAGE'] = "P&aacute;gina";
$_ARRAYLANG['TXT_ALIAS_ALIAS_ES'] = "Alias";
$_ARRAYLANG['TXT_ALIAS_FUNCTIONS'] = "Funciones";
$_ARRAYLANG['TXT_ALIAS_MODIFY_ALIAS'] = "Modificar alias";
$_ARRAYLANG['TXT_ALIAS_ADD_ALIAS'] = "Agregar un nuevo alias";
$_ARRAYLANG['TXT_ALIAS_TARGET_PAGE'] = "P&aacute;gina de destino";
$_ARRAYLANG['TXT_ALIAS_LOCAL'] = "Local";
$_ARRAYLANG['TXT_ALIAS_URL'] = "URL";
$_ARRAYLANG['TXT_ALIAS_BROWSE'] = "Buscar P&aacute;gina";
$_ARRAYLANG['TXT_ALIAS_CONFIRM_REMOVE_ALIAS'] = "\¿Est&aacute;s seguro de que deseas eliminar el alias %s? ";
$_ARRAYLANG['TXT_ALIAS_ADD_ANOTHER_ALIAS'] = "A&ntilde;adir otro alias";
$_ARRAYLANG['TXT_ALIAS_SAVE'] = "Guardar";
$_ARRAYLANG['TXT_ALIAS_CANCEL'] = "Cancelar";
$_ARRAYLANG['TXT_ALIAS_ONE_ALIAS_REQUIRED_MSG'] = "\¡Necesita definir al menos un alias!";
$_ARRAYLANG['TXT_ALIAS_URL_REQUIRED_MSG'] = "\¡Necesita especificar una URL!";
$_ARRAYLANG['TXT_ALIAS_PAGE_REQUIRED_MSG'] = "\¡Debe elegir una p&aacute;gina web! ";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_UPDATED'] = "El alias se actualiz&oacute; con &eacute;xito.";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_ADDED'] = "El alias se a&ntilde;adi&oacute; con &eacute;xito.";
$_ARRAYLANG['TXT_ALIAS_ALIAS_UPDATE_FAILED'] = "Se ha producido un error al actualizar el alias";
$_ARRAYLANG['TXT_ALIAS_ALIAS_ADD_FAILED'] = "Se ha producido un error al a&ntilde;adir el alias";
$_ARRAYLANG['TXT_ALIAS_RETRY_OPERATION'] = "Intentelo de nuevo";
$_ARRAYLANG['TXT_ALIAS_ALREADY_IN_USE'] = "\¡El alias %s ya est&aacute; en uso!";
$_ARRAYLANG['TXT_ALIAS_OPERATION_IRREVERSIBLE'] = "\¡Esta acci&oacute;n no se puede deshacer!";
$_ARRAYLANG['TXT_ALIAS_CONFIRM_DELETE_ALIAS'] = "\¿Est&acute;s seguro de que deseas eliminar el alias de la p&aacute;gina de %s?";
$_ARRAYLANG['TXT_ALIAS_ALIAS_SUCCESSFULLY_REMOVED'] = "El alias se elimin&oacute; con &eacute;xito. ";
$_ARRAYLANG['TXT_ALIAS_ALIAS_REMOVE_FAILED'] = "Se ha producido un error al eliminar el alias";
$_ARRAYLANG['TXT_ALIAS_NO_ALIASES_MSG'] = "\¡No hay alias definidos!";
$_ARRAYLANG['TXT_ALIAS_REQUIREMENTS_DESC'] = "Usando alias, puede dar a una URL compleja como <b> www.yourdomain.com/index.php?page=84472 </ b> un aspecto m&aacute;s amigable. Con un alis, dicha URL aparecer&iacute;a como <b> www.yourdomain.com / servicios </ b>. Esto no s&oacute;lo hace que sea m&aacute;s legible, sino que adem&aacute; mejora el posicionamiento en buscadores. <br/> <br/> Nota: la administraci&oacute;n de Alias s&oacute;lo trabaja en un servidor <strong> Apache </ strong> con la extensi&oacute;n <strong> mod_rewrite </ strong> habilitada. <br /> Adem&aacute;s, el uso de archivos < strong>. htaccess </ strong> debe estar permitido.";
$_ARRAYLANG['TXT_ALIAS_USE_ALIAS_ADMINISTRATION'] = "Utilice alias de administraci&oacute;n";
$_ARRAYLANG['TXT_ALIAS_APACHE_MISSING'] = "Usted est&aacute; utilizando otro servidor web que no es Apache. La gesti&oacute;n de alias s&oacute;lo funciona con Apache, por lo que no puede usar alias en este servidor.";
$_ARRAYLANG['TXT_ALIAS_MOD_REWRITE_MISSING'] = "Cloudrexx no pudo determinar si la opci&oacute;n <strong> mod_rewrite </ strong> est&aacute; activada. Por lo tanto, el alias de gesti&oacute;n no puede ser activado, ya que har&iacute;a que el sitio quedase inaccesible. <br/> <br/> Si est&aacute; seguro de que la opci&oacute;n <strong> mod_rewrite < / strong> est&aacute; disponible, puede activar el alias de gesti&oacute;n de forma manual. Para ello, cree un nuevo archivo llamado <strong>. .htaccess </ strong> en el directorio raiz de la p&aacute;gina web con el siguiente contenido: <br/> <div style=\"margin: 10px;\"> <code> RewriteEngine On </ code> </ div> La pr&oacute;xima vez que acceda a la gesti&oacute;n de alias, se activar&aacute; autom&aacute;ticamente.";
$_ARRAYLANG['TXT_ALIAS_HTACCESS_HINT'] = "Por favor, aseg&uacute;rese de que el servidor soporta archivos .htaccess";
$_ARRAYLANG['TXT_ALIAS_CONFIG_SUCCESSFULLY_APPLYED'] = "La configuraci&oacute;n ha sido cambiada con &eacute;xito. ";
$_ARRAYLANG['TXT_ALIAS_CONFIG_FAILED_APPLY'] = "\¡Se ha producido un error al guardar la configuraci&oacute;n!";
$_ARRAYLANG['TXT_ALIAS_TARGET_PAGE_NOT_EXIST'] = "\¡La p&aacute;gina de destino no existe!";
$_ARRAYLANG['TXT_ALIAS_MUST_NOT_BE_A_FILE'] = "\¡El alias %s no puede ser utilizado, porque esta f&iacute;sicamente presente!";
$_ARRAYLANG['TXT_ALIAS_TARGET_ALREADY_IN_USE'] = "\¡Ya ha sido definido un alias para la p&aacute;gina de  %s!";
$_ARRAYLANG['TXT_ALIAS_NOT_ACTIVE_ALIAS_MSG'] = "\¡Este alias ya no est&aacute; activo! <br /> Haga click en este icono para reactivarlo.";
$_ARRAYLANG['TXT_ALIAS_STANDARD_RADIOBUTTON'] = "Utilizar como predeterminado:";
$_ARRAYLANG['TXT_ALIAS_OPEN_ALIAS_NEW_WINDOW'] = "Open alias in a new window";
$_ARRAYLANG['TXT_ALIAS_IIS_HTACCESS_NOT_REGISTERED'] = "The file &ldquo;web.config&rdquo; is not registered in the server configuration. Please contact the server administrator and request a corresponding entry.";
?>

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
 * File System Framework
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Janik Tschanz <janik.tschanz@comvation.com>
 * @author      Reto Kohli <reto.kohli@comvation.com>
 *              (new static methods, error system)
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  lib_filesystem
 */

/**
 * Legacy file manager
 *
 * <b>Don't use this anymore</b>, use the static refactored class \Cx\Lib\FileSystem\FileSystem
 * instead.
 * This class allows the instantiation of the class Cx\Lib\FileSystem by its
 * former name <File> (Cx < 3.0)
 * I.e.: $objLegacyFile = new File();
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     3.0.0
 * @deprecated  deprecated since 3.0.0
 * @package     cloudrexx
 * @subpackage  lib_filesystem
 */
class File extends Cx\Lib\FileSystem\FileSystem{}

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
 * This is the english language file for frontend mode.
 * This file is included by Cloudrexx and all entries are set as placeholder
 * values for frontent page template by SystemComponentFrontendController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  modules_skeleton
 */

global $_ARRAYLANG;

// Let's start with module info:
$_ARRAYLANG['TXT_CORE_MODULE_UPLOADER'] = 'Datei-Upload';
$_ARRAYLANG['TXT_CORE_MODULE_UPLOADER_DESCRIPTION'] = 'Diese Anwendung ermöglicht den Upload von Dateien.';

// Now our content specific values:
$_ARRAYLANG['TXT_CORE_MODULE_UPLOADER_CONGRATULATIONS'] = 'Congratulations';
$_ARRAYLANG['TXT_CORE_MODULE_UPLOADER_SUCCESSFUL_CREATION'] = 'You successfully created a new module!';
$_ARRAYLANG['TXT_CORE_MODULE_UPLOADER_EXAMPLE_TEMPLATE'] = 'This is the default template for this component. It is used for all ACTs that do not have their own template.';
require_once 'backend.php';

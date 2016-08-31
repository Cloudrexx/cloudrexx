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

global $arrBrowserRegExps, $arrBrowserNames;

/**
 * Defines browser identification regular expressions and browser names
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0
 * @package     cloudrexx
 * @subpackage  coremodule_stats
 */

$arrBrowserRegExps = array(
    "=(Opera) ?/?([0-9]{1,2}).[0-9]{1,2}=",
    "=(MSIE) ?([0-9]{1,2}.[0-9])=",
    "=(Firefox)/([0-9]{1,2}.[0-9]{1,2})=",
    "=(Firebird)/([0-9].[0-9])=",
    "=(Netscape)[0-9]?/([0-9]{1,2})=",
    "=(Chrome)/([0-9]{1,2}.[0-9]{1,2})=",
    "=(Safari)/([0-9]{1,3}).[0-9]{1,2}=",
    "=(Konqueror)/([0-9]{1,2}).[0-9]{1,2}=",
    "=(Lynx)/([0-9]{1,2}.[0-9]{1,2})=",
    "=(Mozilla)/([0-9]{1,2})="
);

$arrBrowserNames = array(
    'MSIE'  => 'Internet Explorer',
    'Netscape'  => 'Netscape Navigator',
);

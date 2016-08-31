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

global $arrOperatingSystems;

/**
 * Defines operating systems
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @version 1.0
 * @package     cloudrexx
 * @subpackage  coremodule_stats
 * @todo        Edit PHP DocBlocks!
 */

$arrOperatingSystems = array(
    // mobile operating systems
    array(
        'regExp' => '=Android=i',
        'name' => 'Android'
    ),
    array(
        'regExp' => '=windows\sphone=i',
        'name' => 'Windows Phone'
    ),
    array(
        'regExp' => '=BlackBerry=',
        'name' => 'BlackBerry'
    ),
    array(
        'regExp' => '=symbian=i',
        'name' => 'Symbian'
    ),
    array(
        'regExp' => '=i(Phone|Pad|Pod)=i',
        'name' => 'iOS'
    ),

    // desktop operating systems
    array(
        'regExp' => '=Windows NT 5\.0|Windows 2000=',
        'name' => 'Windows 2000'
    ),
    array(
        'regExp' => '=Windows NT 4\.0=',
        'name' => 'Windows NT'
    ),
    array(
        'regExp' => '=Windows NT 5\.1|Windows XP=',
        'name' => 'Windows XP'
    ),
    array(
        'regExp' => '=Windows NT 6\.0=',
        'name' => 'Windows Vista'
    ),
    array(
        'regExp' => '=Windows 98=',
        'name' => 'Windows 98'
    ),
    array(
        'regExp' => '=Windows 95=',
        'name' => 'Windows 95'
    ),
    array(
        'regExp' => '=Mac_PowerPC|Macintosh=',
        'name' => 'Macintosh'
    ),
    array(
        'regExp' => '=Linux=',
        'name' => 'Linux'
    ),
    array(
        'regExp' => '=SunOS=',
        'name' => 'SunOS'
    ),
    array(
        'regExp' => '=AIX=',
        'name' => 'AIX'
    ),
    array(
        'regExp' => '=FreeBSD=',
        'name' => 'FreeBSD'
    ),
    array(
        'regExp' => '=BeOS=',
        'name' => 'BeOS'
    ),
    array(
        'regExp' => '=IRIX=',
        'name' => 'IRIX'
    ),
    array(
        'regExp' => '=Windows NT 6\.1=', # o'rly? not 7? 't least the RC shows 6.1
        'name' => 'Windows 7'
    ),
    array(
        'regExp' => '=Windows NT 6\.2=',
        'name' => 'Windows 8'
    ),
    array(
        'regExp' => '=Windows NT 6\.3=',
        'name' => 'Windows 8.1'
    ),
    array(
        'regExp' => '=Windows NT 10\.0=',
        'name' => 'Windows 10'
    ),
    // attention: iPhone/iPad/iPod can be missinterpreted as OS/2
    //            therefore, OS/2 must be the last option in this list
    array(
        'regExp' => '=OS/2=',
        'name' => 'OS/2'
    ),
);

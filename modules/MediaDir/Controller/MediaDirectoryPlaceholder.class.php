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
 * Media  Directory Placeholder Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Placeholder Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryPlaceholder extends MediaDirectoryLibrary
{
    function __construct($name)
    {
        parent::__construct('.', $name);
    }

    function getPlaceholder($strPlaceHolder)
    {
        if(substr($strPlaceHolder,0,14) == '[[ACCESS_USER_'){
// TODO: seams not to be working in the frontend right now
            $strValue = self::__getAccessUserPlaceholder($strPlaceHolder);
        }

        return $strValue;
    }

    function __getAccessUserPlaceholder($strPlaceHolder)
    {
        global $objDatabase, $objInit;

        if($objInit->mode == 'frontend') {
            if (!\FWUser::getFWUserObject()->objUser->login()) {
                return;
            }
            $objFWUser  = \FWUser::getFWUserObject();
            $objUser        = $objFWUser->objUser;

            $strFieldName = substr($strPlaceHolder,14);
            $strFieldName = strtolower(substr($strFieldName,0,-2));

            if ($objUser->getId()) {
                $intUserId = intval($objUser->getId());

                 switch($strFieldName) {
                    case 'email':
                        $strValue = ($objUser->getEmail() != "") ? $objUser->getEmail() : '';
                           break;
                    case 'username':
                        $strValue = ($objUser->getUsername() != "") ? $objUser->getUsername() : '';
                        break;
                    case 'country':
                        //if(intval($strFieldName) != 0) {
                            $strValue = $objUser->getProfileAttribute($strFieldName);
                        //} else {
                        //    $strValue = $objFWUser->objUser->objAttribute->getById('country_'.$objUser->getProfileAttribute($strFieldName))->getId();
                        //}
                        break;
                    default:
                           $strValue = ($objUser->getProfileAttribute($strFieldName) != "") ? $objUser->getProfileAttribute($strFieldName) : '';
                        break;
                 }
            }
        }

        return $strValue;
    }
}

?>

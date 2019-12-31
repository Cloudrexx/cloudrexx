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
 * Media Directory Inputfield Label Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;
/**
 * Media Directory Inputfield Label Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfieldLabel implements Inputfield
{
    public $arrPlaceholders = array('MEDIADIR_INPUTFIELD_VALUE');

    /**
     * Constructor
     */
    function __construct()
    {
    }

    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase, $objInit;

        $langId = static::getOutputLocale()->getId();

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                $strValue = empty($arrInputfield['default_value'][$langId]) ? $arrInputfield['default_value'][0] : $arrInputfield['default_value'][$langId];

                return $strValue;

                break;
            case 2:
                //search View
                break;
        }
    }

    function saveInputfield($strValue)
    {
        return true;
    }

    function deleteContent($intEntryId, $intIputfieldId)
    {
        return true;
    }

    function getContent($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
        return null;
    }

    function getRawData($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
        return null;
    }

    function getJavascriptCheck()
    {
         return null;
    }

    function getFormOnSubmit($intInputfieldId)
    {
        return null;
    }
}

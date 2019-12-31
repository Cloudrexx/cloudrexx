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
 * Media Directory Inputfield Add Stepp Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;
/**
 * Media Directory Inputfield Add Stepp Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfieldAddStep extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary
{
    public $arrPlaceholders = array('TXT_MEDIADIR_INPUTFIELD_NAME');


    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
    }


    function getInputfield($intView, $arrInputfield, $intEntryId=null, $objAddStep)
    {
        global $objDatabase, $objInit;

        $langId = static::getOutputLocale()->getId();

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                if($objInit->mode == 'backend') {
                    return null;
                } else {
                    $arrStepInfos = $objAddStep->getLastStepInformations();

                    $strValue = empty($arrInputfield['default_value'][$langId]) ? $arrInputfield['default_value'][0] : $arrInputfield['default_value'][$langId];


                    if($arrStepInfos['first'] == true) {
                        $strNotFirst = '';
                        $strDisplay = 'block';
                    } else {
                        $strNotFirst = '</div>';
                        $strDisplay = 'none';
                    }

                    return $strNotFirst.'<div id="Step_'.$arrStepInfos['id'].'" class="'.$this->moduleNameLC.'AddStep" style="display: '.$strDisplay.'; float: left; width: 100%; height: auto !important;"><p class="'.$this->moduleNameLC.'AddStepText">'.$strValue.'</p>';
                }

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

    function getRawData($intEntryId, $arrInputfield, $arrTranslationStatus) {
        return null;
    }


    function getJavascriptCheck()
    {
        return null;
    }
}

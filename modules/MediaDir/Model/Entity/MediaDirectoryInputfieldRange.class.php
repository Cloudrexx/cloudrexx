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
 * Media Directory Inputfield Range Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;
/**
 * Media Directory Inputfield Range Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfieldRange extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary implements Inputfield
{
    public $arrPlaceholders = array('TXT_MEDIADIR_INPUTFIELD_NAME','MEDIADIR_INPUTFIELD_VALUE');

    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
    }

    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase, $objInit, $_ARRAYLANG;

        $intId = intval($arrInputfield['id']);
        
        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                if(isset($intEntryId) && $intEntryId != 0) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                        WHERE
                            field_id=".$intId."
                        AND
                            entry_id=".$intEntryId."
                        LIMIT 1
                    ");

                    $strValue = intval($objInputfieldValue->fields['value']);
                } else {
                    $strValue = null;
                }


                $arrInfoValue = array();
                if(!empty($arrInputfield['info'][0])){
                	$arrInfoValue[0] = 'title="'.$arrInputfield['info'][0].'"';
	                foreach($arrInputfield['info'] as $intLangKey => $strInfoValue) {
	                	$strInfoClass = 'mediadirInputfieldHint';
	                    $arrInfoValue[$intLangKey] = empty($strInfoValue) ? 'title="'.$arrInputfield['info'][0].'"' : 'title="'.$strInfoValue.'"';
	                }
                } else {
                	$arrInfoValue = null;
                    $strInfoClass = '';
                }

                if($objInit->mode == 'backend') {
                    $strInputfield = '<div id="'.$this->moduleName.'Inputfield_'.$intId.'_Minimized" style="display: block;"><input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.']" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_0" value="'.$strValue.'" style="width: 300px" onfocus="this.select();" />&nbsp;</div>';

                    $strInputfield .= '</div>';
                } else {
                    $strInputfield = '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][0]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_0" value="'.$strValue.'" class="'.$this->moduleNameLC.'InputfieldText '.$strInfoClass.'" '.$arrInfoValue[0].'/>';
                }
                return $strInputfield;
            case 2:
                //search View

                // Slider-Config from the "default_value"
                // default_value = [MIN-VALUE],[MAX-VALUE],[STEP]
                // Example = 0, 1000000, 100
                $arrSliderConfig = explode(",",$arrInputfield['default_value'][0]);
                $intMin = (int) $arrSliderConfig[0];
                $intMax = (int) $arrSliderConfig[1];
                $intCurMin = (int)(isset($_GET[$intId][0])) ? $_GET[$intId][0] : $intMin;
                $intCurMax = (int)(isset($_GET[$intId][1])) ? $_GET[$intId][1] : $intMax;
                $intStep = (int)$arrSliderConfig[2];
                
                $rangeSlider = new \Cx\Core\Html\Model\Entity\RangeSliderElement($intId, $this->moduleNameLC.'_'.$intId, $intMin, $intMax, $intCurMin, $intCurMax, $intStep);
                return $rangeSlider->render();
        }
    }

    function saveInputfield($intInputfieldId, $strValue, $langId = 0)
    {
        $strValue = contrexx_strip_tags(contrexx_input2raw($strValue));
        return $strValue;
    }

    function deleteContent($intEntryId, $intIputfieldId)
    {
        global $objDatabase;

        return (boolean)$objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
             WHERE `entry_id`='".intval($intEntryId)."'
               AND `field_id`='".intval($intIputfieldId)."'");
    }

    function getContent($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
        $strValue = static::getRawData($intEntryId, $arrInputfield, $arrTranslationStatus);
        $strValue = strip_tags(htmlspecialchars($strValue, ENT_QUOTES, CONTREXX_CHARSET));

        if(!empty($strValue)) {
            $arrContent['TXT_'.$this->moduleLangVar.'_INPUTFIELD_NAME'] = htmlspecialchars($arrInputfield['name'][0], ENT_QUOTES, CONTREXX_CHARSET);
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'] = $strValue;
        } else {
            $arrContent = null;
        }

        return $arrContent;
    }

    function getRawData($intEntryId, $arrInputfield, $arrTranslationStatus) {
        global $objDatabase;

        $intId = intval($arrInputfield['id']);
            
        $objInputfieldValue = $objDatabase->Execute("
            SELECT
                `value`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
            WHERE
                field_id=".$intId."
            AND
                entry_id=".intval($intEntryId)."
            LIMIT 1
        ");

        return $objInputfieldValue->fields['value'];
    }

    function getJavascriptCheck()
    {
    	$fieldName = $this->moduleName."Inputfield_";
        $strJavascriptCheck = <<<EOF

            case 'range':
                value = document.getElementById('$fieldName' + field + '_0').value;
                if (value == "" && isRequiredGlobal(inputFields[field][1], value)) {
                	isOk = false;
                	document.getElementById('$fieldName' + field + '_0').style.border = "#ff0000 1px solid";
                } else if (value != "" && !matchType(inputFields[field][2], value)) {
                	isOk = false;
                	document.getElementById('$fieldName' + field + '_0').style.border = "#ff0000 1px solid";
                } else {
                	document.getElementById('$fieldName' + field + '_0').style.borderColor = '';
                }
                break;

EOF;
        return $strJavascriptCheck;
    }


    function getFormOnSubmit($intInputfieldId)
    {
        return null;
    }
}


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
 * Media Directory Inputfield Link Group Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;
/**
 * Media Directory Inputfield Link Group Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
class MediaDirectoryInputfieldLinkGroup extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary implements Inputfield
{
    public $arrPlaceholders = array('TXT_MEDIADIR_INPUTFIELD_NAME','MEDIADIR_INPUTFIELD_VALUE');



    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
        parent::getFrontendLanguages();
        parent::getSettings();
    }



    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase, $objInit, $_ARRAYLANG;

        $langId = static::getOutputLocale()->getId();

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                $intId = intval($arrInputfield['id']);
                $arrValue = null;

                if (!empty($intEntryId)) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`,
                            `lang_id`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                        WHERE
                            field_id=".$intId."
                        AND
                            entry_id=".$intEntryId."
                    ");
                    if ($objInputfieldValue !== false) {
                        while (!$objInputfieldValue->EOF) {
                            $arrValue[intval($objInputfieldValue->fields['lang_id'])] = contrexx_raw2xhtml($objInputfieldValue->fields['value']);
                            $objInputfieldValue->MoveNext();
                        }
                        $arrValue[0] = isset($arrValue[$langId]) ? $arrValue[$langId] : null;
                    }
                }

                if (empty($arrValue)) {
                    foreach ($arrInputfield['default_value'] as $intLangKey => $strDefaultValue) {
                        $strDefaultValue = empty($strDefaultValue) ? $arrInputfield['default_value'][0] : $strDefaultValue;
                        if (substr($strDefaultValue,0,2) == '[[') {
                            $objPlaceholder = new \Cx\Modules\MediaDir\Controller\MediaDirectoryPlaceholder($this->moduleName);
                            $arrValue[$intLangKey] = $objPlaceholder->getPlaceholder($strDefaultValue);
                        } else {
                            $arrValue[$intLangKey] = $strDefaultValue;
                        }
                    }
                }

                $arrInfoValue = null;
                $strInfoClass = '';

                if (!empty($arrInputfield['info'][0])) {
                    $arrInfoValue[0] = 'title="'.$arrInputfield['info'][0].'"';
                    $strInfoClass = 'mediadirInputfieldHint';
                    foreach ($arrInputfield['info'] as $intLangKey => $strInfoValue) {
                        $arrInfoValue[$intLangKey] = empty($strInfoValue) ? 'title="'.$arrInputfield['info'][0].'"' : 'title="'.$strInfoValue.'"';
                    }
                }

                $countFrontendLang = count($this->arrFrontendLanguages);

                if ($objInit->mode == 'backend') {
                    $strInputfield = '<div id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_Minimized" style="display: block;"><textarea data-id="' . $intId . '" class="' . $this->moduleNameLC . 'InputfieldDefault" name="' . $this->moduleNameLC . 'Inputfield[' . $intId . '][0]" id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_0" style="width: 300px; height: 60px;" onfocus="this.select();" />' . $arrValue[0] . '</textarea>&nbsp;<a href="javascript:ExpandMinimize(\'' . $intId . '\');">' . $_ARRAYLANG['TXT_MEDIADIR_MORE'] . '&nbsp;&raquo;</a></div>';

                    $strInputfield .= '<div id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_Expanded" style="display: none;">';
                    foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                        $intLangId = $arrLang['id'];

                        $minimize = "";
                        if (($key + 1) == $countFrontendLang) {
                            $minimize = "&nbsp;<a href=\"javascript:ExpandMinimize('" . $intId . "');\">&laquo;&nbsp;" . $_ARRAYLANG['TXT_MEDIADIR_MINIMIZE'] . "</a>";
                        }

                        $value = isset($arrValue[$intLangId]) ? $arrValue[$intLangId] : '';
                        $strInputfield .= '<textarea data-id="' . $intId . '" name="' . $this->moduleNameLC . 'Inputfield[' . $intId . '][' . $intLangId . ']" id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_' . $intLangId . '" style="height: 60px; width: 279px; margin-bottom: 2px; padding-left: 21px; background: #ffffff url(\'' . \Env::get('cx')->getCodeBaseOffsetPath() . \Env::get('cx')->getCoreFolderName() . '/Country/View/Media/Flag/flag_' . $arrLang['lang'] . '.gif\') no-repeat 3px 3px;" onfocus="this.select();" />' . $value . '</textarea>&nbsp;' . $arrLang['name'] . '<a href="javascript:ExpandMinimize(\'' . $intId . '\');">&nbsp;' . $minimize . '</a><br />';
                    }
                    $strInputfield .= '</div>';
                } else {
                    if ($this->arrSettings['settingsFrontendUseMultilang'] == 1) {
                        $strInputfield = '<div id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_Minimized" style="display: block; float: left;" class="' . $this->moduleNameLC . 'GroupMultilang"><textarea data-id="' . $intId . '" class="' . $this->moduleNameLC . 'InputfieldDefault" name="' . $this->moduleNameLC . 'Inputfield[' . $intId . '][0]" id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_0" class="' . $this->moduleNameLC . 'InputfieldTextarea ' . $strInfoClass . '" ' . $arrInfoValue[0] . ' onfocus="this.select();" />' . $arrValue[0] . '</textarea>&nbsp;<a href="javascript:ExpandMinimize(\'' . $intId . '\');">' . $_ARRAYLANG['TXT_MEDIADIR_MORE'] . '&nbsp;&raquo;</a></div>';

                        $strInputfield .= '<div id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_Expanded" style="display: none; float: left;" class="' . $this->moduleNameLC . 'GroupMultilang">';
                        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                            $intLangId = $arrLang['id'];

                            $minimize = "";
                            if (($key + 1) == $countFrontendLang) {
                                $minimize = "&nbsp;<a href=\"javascript:ExpandMinimize('" . $intId . "');\">&laquo;&nbsp;" . $_ARRAYLANG['TXT_MEDIADIR_MINIMIZE'] . "</a>";
                            }

                            $value = isset($arrValue[$intLangId]) ? $arrValue[$intLangId] : '';
                            $strInputfield .= '<textarea data-id="' . $intId . '" name="' . $this->moduleNameLC . 'Inputfield[' . $intId . '][' . $intLangId . ']" id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_' . $intLangId . '" class="' . $this->moduleNameLC . 'InputfieldTextarea ' . $strInfoClass . '" ' . $arrInfoValue[$intLangId] . ' onfocus="this.select();" />' . $value . '</textarea>&nbsp;' . $arrLang['name'] . '<a href="javascript:ExpandMinimize(\'' . $intId . '\');">&nbsp;' . $minimize . '</a><br />';
                        }
                        $strInputfield .= '</div>';
                    } else {
                        $strInputfield = '<textarea name="' . $this->moduleNameLC . 'Inputfield[' . $intId . '][0]" id="' . $this->moduleNameLC . 'Inputfield_' . $intId . '_0" class="' . $this->moduleNameLC . 'InputfieldTextarea ' . $strInfoClass . '" ' . $arrInfoValue[0] . ' onfocus="this.select();" />' . $arrValue[0] . '</textarea>';
                    }
                }
                return $strInputfield;

                break;
            case 2:
                //search View
                break;
        }
        return null;
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
               AND  `field_id`='".intval($intIputfieldId)."'");
    }



    function getContent($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
        $strValue = static::getRawData($intEntryId, $arrInputfield, $arrTranslationStatus);
        $strValue = strip_tags(htmlspecialchars($strValue, ENT_QUOTES, CONTREXX_CHARSET));

        if(!empty($strValue)) {
            //get seperator
            $strSeperator = $this->getSeperartor($strValue);

            //explode links
            $arrLinkGroup = explode($strSeperator, $strValue);

            //open link <ul> list
            $strValue = '<ul class="mediadirInputfieldLink_group">';

            //make list elements
            foreach ($arrLinkGroup as $strLink) {

                //make link name without "http://"
                $strValueName = $strLink;
                if (substr($strValueName, 0,7) == "http://") {
                    $strValueName = substr($strValueName,7);
                }

                if (strlen($strValueName) >= 55 ) {
                    $strValueName = substr($strValueName, 0, 55)." [...]";
                }

                //make link href with "http://"
                $strValueHref = $strLink;
                if (substr($strValueHref, 0,7) != "http://") {
                    $strValueHref = "http://".$strValueHref;
                }

                //make hyperlink with <a> and <li> tag
                $strValue .= '<li><a href="'.$strValueHref.'" target="_blank">'.$strValueName.'</a></li>';
            }

            //close link </ul> list
            $strValue .= '</ul>';
        }

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
        $objEntryDefaultLang = $objDatabase->Execute("SELECT `lang_id` FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_entries WHERE id=".intval($intEntryId)." LIMIT 1");
        $intEntryDefaultLang = intval($objEntryDefaultLang->fields['lang_id']);
        $langId = static::getOutputLocale()->getId();

        $intLangId = $langId;
        if($this->arrSettings['settingsTranslationStatus'] == 1) {
            $intLangId = in_array($langId, $arrTranslationStatus) ? $langId : $intEntryDefaultLang;
        }

        $objInputfieldValue = $objDatabase->Execute("
            SELECT
                `value`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
            WHERE
                field_id=".$intId."
            AND
                entry_id=".intval($intEntryId)."
            AND
                lang_id=".$intLangId."
            LIMIT 1
        ");

        if(empty($objInputfieldValue->fields['value'])) {
            $objInputfieldValue = $objDatabase->Execute("
                SELECT
                    `value`
                FROM
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                WHERE
                    field_id=".$intId."
                AND
                    entry_id=".intval($intEntryId)."
                AND
                    lang_id=".intval($intEntryDefaultLang)."
                LIMIT 1
            ");
        }

        return $objInputfieldValue->fields['value'];
    }



    function getSeperartor($strValue)
    {
        $arrAllowedSeperators = array("," => 0,";" => 0,"\n" => 0," " => 0);

        foreach (array_keys($arrAllowedSeperators) as $strSeperator) {
            $intMatches = substr_count($strValue, $strSeperator);
            $arrSeperators[$intMatches] = $strSeperator;
        }

        ksort($arrSeperators);

        $strSeperator = array_pop($arrSeperators);

        return $strSeperator;
    }



    function getJavascriptCheck()
    {
        $fieldName = $this->moduleNameLC."Inputfield_";
        $strJavascriptCheck = <<<EOF

            case 'link_group':
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

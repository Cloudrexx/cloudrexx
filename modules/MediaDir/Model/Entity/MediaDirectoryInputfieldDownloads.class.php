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
 * Media Directory Inputfield Downloads Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;
/**
 * Media Directory Inputfield Downloads Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfieldDownloads extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary
{
    public $arrPlaceholders = array('TXT_MEDIADIR_INPUTFIELD_NAME','MEDIADIR_INPUTFIELD_VALUE');



    /**
     * Constructor
     */
    function __construct($name)
    {
        $this->imagePath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesMediaDirPath() . '/';
        $this->imageWebPath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesMediaDirWebPath() . '/';
        parent::__construct('.', $name);
        parent::getFrontendLanguages();
        parent::getSettings();
    }




    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase, $objInit, $_ARRAYLANG, $_CORELANG;

        $intId = intval($arrInputfield['id']);

        switch ($intView) {
            default:
            case 1:
                $arrValue = null;
                //modify (add/edit) View
                if(isset($intEntryId) && $intEntryId != 0) {
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
                        $arrParents = null;
                        while (!$objInputfieldValue->EOF) {
                            $strValue = htmlspecialchars($objInputfieldValue->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                            $arrParents = explode("||", $strValue);

                            foreach($arrParents as $intKey => $strChildes) {
                                $arrChildes = array();
                                $arrChildes = explode("##", $strChildes);

                                $arrValue[intval($objInputfieldValue->fields['lang_id'])][$intKey]['title'] = $arrChildes[0];
                                $arrValue[intval($objInputfieldValue->fields['lang_id'])][$intKey]['desc'] = $arrChildes[1];
                                $arrValue[intval($objInputfieldValue->fields['lang_id'])][$intKey]['file'] = $arrChildes[2];

                                if(!empty($arrChildes[2]) && file_exists(\Env::get('cx')->getWebsitePath().$arrChildes[2])) {
                                    $arrFileInfo    = pathinfo($arrChildes[2]);
                                    $strFileName    = htmlspecialchars($arrFileInfo['basename'], ENT_QUOTES, CONTREXX_CHARSET);

                                    $pos = strrpos($arrChildes[2], ".");

                                    if ($pos != false) {
                                        $ext = strtolower(trim(substr($arrChildes[2], $pos)));
                                        $imgExts = array(".gif", ".jpg", ".jpeg", ".png", ".tiff", ".tif"); // this is far from complete but that's always going to be the case...
                                        if (in_array($ext, $imgExts)) {
                                            $strFilePreview = '<img src="'.urldecode($arrChildes[2]).'" />';
                                        } else {
                                            $strFilePreview = '<a href="'.urldecode($arrChildes[2]).'" target="_blank">'.$strFileName.'</a><br />';
                                        }
                                    } else {
                                        $strFilePreview = '<a href="'.urldecode($arrChildes[2]).'" target="_blank">'.$strFileName.'</a><br />';
                                    }
                                } else {
                                    $strFilePreview = null;
                                }

                                $arrValue[intval($objInputfieldValue->fields['lang_id'])][$intKey]['preview'] = $strFilePreview;

                                if(empty($arrChildes[2]) || $arrChildes[2] == "new_file") {
                                    $strValueHidden = "new_file";
                                    $arrChildes[2] = "";
                                } else {
                                    $strValueHidden = $arrChildes[2];
                                }

                                $arrValue[intval($objInputfieldValue->fields['lang_id'])][$intKey]['hidden'] = $strValueHidden;
                            }

                            $objInputfieldValue->MoveNext();
                        }
                        $arrValue[0] = $arrValue[static::getOutputLocale()->getId()];
                        $intNumElements = count($arrParents);
                    }
                } else {
                    $arrValue = null;
                    $intNumElements = 0;
                }

                /*$arrInfoValue = array();

                if(!empty($arrInputfield['info'][0])){
                    $arrInfoValue[0] = 'title="'.$arrInputfield['info'][0].'"';
                    foreach($arrInputfield['info'] as $intLangKey => $strInfoValue) {
                        $strInfoClass = 'mediadirInputfieldHint';
                        $arrInfoValue[$intLangKey] = empty($strInfoValue) ? 'title="'.$arrInputfield['info'][0].'"' : 'title="'.$strInfoValue.'"';
                    }
                } else {
                    $arrInfoValue = null;
                    $strInfoClass = '';
                }*/

                $intNextElementId = $intNumElements;

                if($objInit->mode == 'backend') {
                    $strFieldsetStyle = 'border: 1px solid #0A50A1; width: 402px; margin-bottom: 10px; position: relative;';
                    $strLegendStyle = 'color: #0A50A1;';
                    $strInputStyle = 'width: 300px';
                    $strInputFlagStyle = 'width: 279px; margin-bottom: 2px; padding-left: 21px;';
                    $strTextAreaStyle = 'width: 300px; height: 60px;';
                    $strTextAreaFlagStyle = 'width: 279px; margin-bottom: 2px; padding-left: 21px;';
                    $strDeleteImagePath = '../core/Core/View/Media/icons/delete.gif';
                } else {
                    $strFieldsetStyle = 'margin-bottom: 10px; position: relative;';
                    $strLegendStyle = '';
                    $strInputStyle = '';
                    $strInputFlagStyle = '';
                    $strTextAreaStyle = '';
                    $strTextAreaFlagStyle = '';
                    $strDeleteImagePath = '../core/Core/View/Media/icons/delete.gif';
                }

                $strBlankElement =
                    '<fieldset style="'.$strFieldsetStyle.'"  id="'.$this->moduleNameLC.'DownloadsElement_'.$intId.'_ELEMENT-KEY">'.
                    '<img src="'.$strDeleteImagePath.'" onclick="downloadsDeleteElement_'.$intId.'(ELEMENT-KEY);" style="cursor: pointer; position: absolute; top: -7px; right: 10px; z-index: 1000;"/>'.
                    '<legend style="'.$strLegendStyle.'">'.$arrInputfield['name'][0].' #ELEMENT-NR</legend>'.
                    '<div id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_Minimized" style="display: block;">'.
                    '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][0][ELEMENT-KEY][title]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_0_title" value="'.$_ARRAYLANG['TXT_MEDIADIR_TITLE'].'"  style="'.$strInputStyle.'" onfocus="this.select();" />&nbsp;'.
                    $arrLang['name'].
                    '<br />';
                $strBlankElement .= '<textarea name="'.$this->moduleNameLC.'Inputfield['.$intId.'][0][ELEMENT-KEY][desc]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_0_title" style="'.$strTextAreaStyle.'" onfocus="this.select();">'.$_CORELANG['TXT_CORE_SETTING_NAME'].'</textarea><br />';

                if($objInit->mode == 'backend') {
                    $browserButton    = '<input type="button" onClick="getMediaBrowser(\$J(this));" data-input-id="'. $this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_0_file" data-views="filebrowser" data-startmediatype="'. $this->moduleNameLC .'" value="'. $_ARRAYLANG['TXT_BROWSE'] .'" />';
                    $strBlankElement .= '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][0][ELEMENT-KEY][file]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_0_file" style="width: 213px;" onfocus="this.select();" />&nbsp;'. $browserButton;
                } else {
                    $strBlankElement .= $this->getFrontendUploaderInputField($intId);
                }

                if($this->arrSettings['settingsFrontendUseMultilang'] == 1 || $objInit->mode == 'backend') {
                    $strBlankElement .= '&nbsp;<a href="javascript:ExpandMinimizeMultiple('.$intId.', ELEMENT-KEY);">'.$_ARRAYLANG['TXT_MEDIADIR_MORE'].'&nbsp;&raquo;</a>';
                    $strBlankElement .= '</div>';

                    $strBlankElement .= '<div id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_Expanded" style="display: none;">';
                    foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                        $intLangId = $arrLang['id'];

                        if(($key+1) == count($this->arrFrontendLanguages)) {
                            $minimize = "&nbsp;<a href=\"javascript:ExpandMinimizeMultiple(".$intId.", ELEMENT-KEY);\">&laquo;&nbsp;".$_ARRAYLANG['TXT_MEDIADIR_MINIMIZE']."</a>";
                        } else {
                            $minimize = "";
                        }

                        $strBlankElement .= '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.'][ELEMENT-KEY][title]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_'.$intLangId.'_title" value=""  style="'.$strInputFlagStyle.' background: #ffffff url('. \Env::get('cx')->getCodeBaseOffsetPath() . \Env::get('cx')->getCoreFolderName().'/Country/View/Media/Flag/flag_'.$arrLang['lang'].'.gif) no-repeat 3px 3px;" onfocus="this.select();" />&nbsp;'.$arrLang['name'].'<br />';
                        $strBlankElement .= '<textarea name="'.$this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.'][ELEMENT-KEY][desc]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_'.$intLangId.'_title" style="'.$strTextAreaFlagStyle.' background: #ffffff url('. \Env::get('cx')->getCodeBaseOffsetPath() . \Env::get('cx')->getCoreFolderName().'/Country/View/Media/Flag/flag_'.$arrLang['lang'].'.gif) no-repeat 3px 3px;" onfocus="this.select();" /></textarea><br />';

                        if($objInit->mode == 'backend') {
                            $browserButton    = '<input type="button" onClick="getMediaBrowser(\$J(this));" data-input-id="'. $this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_'.$intLangId.'_file" data-views="filebrowser" data-startmediatype="'. $this->moduleNameLC .'" value="'. $_ARRAYLANG['TXT_BROWSE'] .'" />';
                            $strBlankElement .= '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.'][ELEMENT-KEY][file]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT-KEY_'.$intLangId.'_file" style="width: 192px; margin-bottom: 2px; padding-left: 21px; background: #ffffff url('. \Env::get('cx')->getCodeBaseOffsetPath() . \Env::get('cx')->getCoreFolderName().'/Country/View/Media/Flag/flag_'.$arrLang['lang'].'.gif) no-repeat 3px 3px;" onfocus="this.select();" />&nbsp;'. $browserButton .$minimize.'<br /><br />';
                        } else {
                            $strBlankElement .= $this->getFrontendUploaderInputField($intId, $intLangId) . $minimize.'<br /><br />';
                        }
                    }
                    $strBlankElement .= '</div>';
                } else {
                    $strBlankElement .= '</div>';
                }

                $strBlankElement .= '</fieldset>';

                $strElementSetId =  $this->moduleNameLC.'DownloadsElements_'.$intId;
                $strElementId =  $this->moduleNameLC.'DownloadsElement_'.$intId.'_';

                $strInputfield = <<< EOF
<script type="text/javascript">
/* <![CDATA[ */

var nextDownloadId_$intId = $intNextElementId;
var blankDownload_$intId  = '$strBlankElement';

function downloadsAddElement_$intId(){
    var replace1BlankDownload_$intId = blankDownload_$intId.replace(/ELEMENT-KEY/g, nextDownloadId_$intId);
    var replace2BlankDownload_$intId = replace1BlankDownload_$intId.replace(/ELEMENT-NR/g, nextDownloadId_$intId+1);

    \$J('#$strElementSetId').append(replace2BlankDownload_$intId);
    \$J('#$strElementId' + nextDownloadId_$intId).css('display', 'none');
    \$J('#$strElementId' + nextDownloadId_$intId).fadeIn("fast");

    nextDownloadId_$intId = nextDownloadId_$intId + 1;
}

function downloadsDeleteElement_$intId(key){
    \$J('#$strElementId'+key).fadeOut("fast", function(){ \$J('#$strElementId'+key).remove();});
}

/* ]]> */
</script>

EOF;
                $strInputfield .= '<div class="'.$this->moduleNameLC.'GroupMultilang">';
                $strInputfield .= '<div id="'.$this->moduleNameLC.'DownloadsElements_'.$intId.'">';

                if($objInit->mode == 'backend') {
                    $strFieldsetStyle = 'border: 1px solid #0A50A1; width: 402px; margin-bottom: 10px; position: relative;';
                    $strLegendStyle = 'color: #0A50A1;';
                    $strInputStyle = 'width: 300px';
                    $strInputFlagStyle = 'width: 279px; margin-bottom: 2px; padding-left: 21px;';
                    $strTextAreaStyle = 'width: 300px; height: 60px;';
                    $strTextAreaFlagStyle = 'width: 279px; margin-bottom: 2px; padding-left: 21px;';
                    $strDeleteImagePath = '../core/Core/View/Media/icons/delete.gif';
                } else {
                    $strFieldsetStyle = 'margin-bottom: 10px; position: relative;';
                    $strLegendStyle = '';
                    $strInputStyle = '';
                    $strInputFlagStyle = '';
                    $strTextAreaStyle = '';
                    $strTextAreaFlagStyle = '';
                    $strDeleteImagePath = '../core/Core/View/Media/icons/delete.gif';
                }

                for($intKey = 0; $intKey < $intNumElements; $intKey++) {
                    $intNummer = $intKey+1;

                    $strInputfield .= '<fieldset id="'.$this->moduleNameLC.'DownloadsElement_'.$intId.'_'.$intKey.'" style="'.$strFieldsetStyle.'">';
                    $strInputfield .= '<img src="'.$strDeleteImagePath.'" onclick="downloadsDeleteElement_'.$intId.'('.$intKey.');" style="cursor: pointer; position: absolute; top: -7px; right: 10px; z-index: 1000;"/>';
                    $strInputfield .= '<legend style="'.$strLegendStyle.'">'.$arrInputfield['name'][0].' #'.$intNummer.'</legend>';
                    $strInputfield .= '<div id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_Minimized" style="display: block;">';
                    $strInputfield .= '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][0]['.$intKey.'][title]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_0_title" value="'.$arrValue[0][$intKey]['title'].'" style="'.$strInputStyle.'" onfocus="this.select();" /><br />';
                    $strInputfield .= '<textarea name="'.$this->moduleNameLC.'Inputfield['.$intId.'][0]['.$intKey.'][desc]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_0_title" style="'.$strTextAreaStyle.'" onfocus="this.select();" />'.$arrValue[0][$intKey]['desc'].'</textarea><br />';

                    if($objInit->mode == 'backend') {
                        $browserButton  = '<input type="button" onClick="getMediaBrowser(\$J(this));" data-input-id="'. $this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_0_file" data-views="filebrowser" data-startmediatype="'. $this->moduleNameLC .'" value="'. $_ARRAYLANG['TXT_BROWSE'] .'" />';
                        $strInputfield .= '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][0]['.$intKey.'][file]" value="'.$arrValue[0][$intKey]['file'].'" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_0_file" style="width: 213px;" onfocus="this.select();" />&nbsp;'. $browserButton;
                    } else {
                        $strInputfield .= $this->getFrontendUploaderInputField($intId, 0, $value, $intKey);
                        $strInputfield .= '<input id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_0_'.$intKey.'_hidden" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][0]['.$intKey.'][file]" value="'.$arrValue[0][$intKey]['hidden'].'" type="hidden">';
                    }
                    if($this->arrSettings['settingsFrontendUseMultilang'] == 0 && $objInit->mode == 'frontend') {
                        $strInputfield .= '<br />'.$arrValue[0][$intKey]['preview'];
                    }

                    if($this->arrSettings['settingsFrontendUseMultilang'] == 1 || $objInit->mode == 'backend') {
                        $strInputfield .= '&nbsp;<a href="javascript:ExpandMinimizeMultiple('.$intId.', '.$intKey.');">'.$_ARRAYLANG['TXT_MEDIADIR_MORE'].'&nbsp;&raquo;</a><br />';
                        $strInputfield .= $arrValue[0][$intKey]['preview'];
                        $strInputfield .= '</div>';

                        $strInputfield .= '<div id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_Expanded" style="display: none;">';
                        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                            $intLangId = $arrLang['id'];

                            if(($key+1) == count($this->arrFrontendLanguages)) {
                                $minimize = "&nbsp;<a href=\"javascript:ExpandMinimizeMultiple(".$intId.", ".$intKey.");\">&laquo;&nbsp;".$_ARRAYLANG['TXT_MEDIADIR_MINIMIZE']."</a>";
                            } else {
                                $minimize = "";
                            }

                            $strInputfield .= '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.']['.$intKey.'][title]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_'.$intLangId.'_title" value="'.$arrValue[$intLangId][$intKey]['title'].'"  style="'.$strInputFlagStyle.' background: #ffffff url(\''. \Env::get('cx')->getCodeBaseOffsetPath() . \Env::get('cx')->getCoreFolderName().'/Country/View/Media/Flag/flag_'.$arrLang['lang'].'.gif\') no-repeat 3px 3px;" onfocus="this.select();" />&nbsp;'.$arrLang['name'].'<br />';
                            $strInputfield .= '<textarea name="'.$this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.']['.$intKey.'][desc]" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_'.$intLangId.'_title" style="'.$strTextAreaFlagStyle.' background: #ffffff url(\''. \Env::get('cx')->getCodeBaseOffsetPath() . \Env::get('cx')->getCoreFolderName().'/Country/View/Media/Flag/flag_'.$arrLang['lang'].'.gif\') no-repeat 3px 3px;" onfocus="this.select();" />'.$arrValue[$intLangId][$intKey]['desc'].'</textarea><br />';

                            if($objInit->mode == 'backend') {
                                $browserButton  = '<input type="button" onClick="getMediaBrowser(\$J(this));" data-input-id="'. $this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_'.$intLangId.'_file" data-views="filebrowser" data-startmediatype="'. $this->moduleNameLC .'" value="'. $_ARRAYLANG['TXT_BROWSE'] .'" />';
                                $strInputfield .= '<input type="text" name="'.$this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.']['.$intKey.'][file]" value="'.$arrValue[$intLangId][$intKey]['file'].'" id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intKey.'_'.$intLangId.'_file" style="width: 192px; margin-bottom: 2px; padding-left: 21px; background: #ffffff url('. \Env::get('cx')->getCodeBaseOffsetPath() . \Env::get('cx')->getCoreFolderName().'/Country/View/Media/Flag/flag_'.$arrLang['lang'].'.gif) no-repeat 3px 3px;" onfocus="this.select();" />&nbsp;'. $browserButton . $minimize.'<br />'.$arrValue[$intLangId][$intKey]['preview'].'<br />';
                            } else {
                                $strInputfield .= $this->getFrontendUploaderInputField($intId, $intLangId, $strValue, $intKey) .$minimize.'<br />'.$arrValue[$intLangId][$intKey]['preview'].'<br />';
                                $strInputfield .= '<input id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_'.$intLangId.'_'.$intKey.'_hidden" name="'.$this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.']['.$intKey.'][file]" value="'.$arrValue[$intLangId][$intKey]['hidden'].'" type="hidden">';
                            }
                        }
                        $strBlankElement .= '</div>';
                    } else {
                        $strBlankElement .= '</div>';
                    }

                    $strInputfield .= '<input type="hidden" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][old]['.$intKey.'][title]" value="'.$arrValue[0][$intKey]['title'].'" />';
                    $strInputfield .= '<input type="hidden" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][old]['.$intKey.'][desc]" value="'.$arrValue[0][$intKey]['desc'].'" />';
                    $strInputfield .= '<input type="hidden" name="'.$this->moduleNameLC.'Inputfield['.$intId.'][old]['.$intKey.'][file]" value="'.$arrValue[0][$intKey]['file'].'" />';

                    $strInputfield .= '</div>';
                    $strInputfield .= '</fieldset>';
                }

                $strInputfield .= '</div>';
                $strInputfield .= '<input type="button" value="'.$arrInputfield['name'][0].' '.$_ARRAYLANG['TXT_MEDIADIR_ADD'].'" onclick="downloadsAddElement_'.$intId.'();" />';
                $strInputfield .= '</div>';

                return $strInputfield;

                break;
            case 2:
                //search View
                break;
        }
        return null;
    }



    function saveInputfield($intInputfieldId, $arrValue, $intLangId)
    {
        global $objInit;

        $arrValues = array();

        if($objInit->mode == 'backend') {
            foreach($arrValue as $intKey => $arrValuesTmp) {
                $arrValues[] = join("##", $arrValuesTmp);
            }
        } else {
            $uploaderId = !empty($_POST['uploaderId']) ? $_POST['uploaderId'] : '';
            foreach($arrValue as $intKey => $arrValuesTmp) {
                if ($_POST['mediadirInputfieldSource'][$intInputfieldId][0][$intKey] != ''  && $intLangId == static::getOutputLocale()->getId()) {
                    $this->deleteFile($arrValuesTmp['file']);
                    $filePath   = $this->getUploadedFilePath($uploaderId, $_POST['mediadirInputfieldSource'][$intInputfieldId][0][$intKey]);
                    if ($filePath) {
                        $arrValuesTmp['file'] = $this->uploadMedia($filePath);
                        // ugly way,try to get it from post
                        $_POST['mediadirInputfieldSource'][$intInputfieldId][$intKey]['defaultFile'] = $arrValuesTmp['file'];
                    }
                }

                if ($_POST['mediadirInputfieldSource'][$intInputfieldId][$intLangId][$intKey] != '') {
                    $this->deleteFile($arrValuesTmp['file']);
                    $filePath   = $this->getUploadedFilePath($uploaderId, $_POST['mediadirInputfieldSource'][$intInputfieldId][$intLangId][$intKey]);
                    $arrValuesTmp['file'] = $this->uploadMedia($filePath);
                } else {
                    if($arrValuesTmp['file'] == '' || $arrValuesTmp['file'] == 'new_file') {
                        $arrValuesTmp['file'] = $_POST['mediadirInputfieldSource'][$intInputfieldId][$intKey]['defaultFile'];
                    }
                }

                $arrValues[] = join("##", $arrValuesTmp);
            }
        }

        $strValue = contrexx_input2raw(contrexx_strip_tags(join("||", $arrValues)));
        return $strValue;
    }

    /**
     * Copy the Upload the image to the path
     * Note: validation should be done before calling this function
     *
     * @param string $filePath Temp path of the uploaded media
     *
     * @return boolean|string relative path of the uploaded file, false otherwise
     */
    function uploadMedia($filePath)
    {
        if ($filePath == '' || !\FWValidator::is_file_ending_harmless($filePath)) {
            return false;
        }

        $fileName      = basename($filePath);
        //get extension
        $arrFileInfo   = pathinfo($fileName);
        $fileExtension = !empty($arrFileInfo['extension']) ? '.'.$arrFileInfo['extension'] : '';
        $fileBasename  = $arrFileInfo['filename'];
        $randomSum     = rand(10, 99);

        //encode filename
        if ($this->arrSettings['settingsEncryptFilenames'] == 1) {
            $fileName = md5($randomSum.$fileBasename).$fileExtension;
        }

        //check filename
        if (file_exists($this->imagePath.'uploads/'.$fileName)) {
            $fileName = $fileBasename.'_'.time().$fileExtension;
        }

        //upload file
        if (\Cx\Lib\FileSystem\FileSystem::copy_file($filePath, $this->imagePath . 'uploads/' . $fileName) !== false) {
            $objFile = new \File();
            $objFile->setChmod($this->imagePath, $this->imageWebPath, 'uploads/'. $fileName);

            return $this->imageWebPath.'uploads/'.$fileName;
        } else {
            return false;
        }
    }


    function deleteFile($strPathFile)
    {
        if(!empty($strPathFile)) {
            $objFile = new \File();
            $arrFileInfo = pathinfo($strPathFile);
            $fileName    = $arrFileInfo['basename'];

            //delete file
            if (file_exists(\Env::get('cx')->getWebsitePath().$strPathFile)) {
                $objFile->delFile($this->imagePath, $this->imageWebPath, 'uploads/'.$fileName);
            }
        }
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

        if(!empty($strValue)) {
            $strValue = strip_tags(htmlspecialchars($strValue, ENT_QUOTES, CONTREXX_CHARSET));
            $arrParents = array();
            $arrParents = explode("||", $strValue);
            $strValue = null;

            foreach($arrParents as $strChildes) {
                $arrChildes = array();
                $arrChildes = explode("##", $strChildes);

                $strTitle = '<span class="'.$this->moduleNameLC.'DownloadTitle">'.$arrChildes[0].'</span>';
                $strDesc = '<span class="'.$this->moduleNameLC.'DownloadDescription">'.$arrChildes[1].'</span>';
                $arrFileInfo    = pathinfo($arrChildes[2]);
                $strFileName    = htmlspecialchars($arrFileInfo['basename'], ENT_QUOTES, CONTREXX_CHARSET);
                $strFile = '<span class="'.$this->moduleNameLC.'DownloadFile"><a href="'.$arrChildes[2].'">'.$strFileName.'</a></span>';

                $strValue .= '<div class="'.$this->moduleNameLC.'Download">'.$strTitle.$strDesc.$strFile.'</div>';
            }

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

        if($this->arrSettings['settingsTranslationStatus'] == 1) {
            if(in_array($langId, $arrTranslationStatus)) {
                $intLangId = $langId;
            } else {
                $intLangId = $intEntryDefaultLang;
            }
        } else {
            $intLangId = $langId;
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


    function getJavascriptCheck()
    {
        //$fieldName = $this->moduleNameLC."Inputfield_";
        $strJavascriptCheck = <<<EOF

            case 'downloads':
                break;

EOF;
        return $strJavascriptCheck;
    }


    function getFormOnSubmit($intInputfieldId)
    {
        return null;
    }

    /**
     * Get Uploader input for frontend
     *
     * @param integer $intId     Input field id
     * @param integer $langId    Language id
     * @param string  $value     Value for the uploader input
     * @param string  $elementId Element key
     *
     * @return string Uploader input
     */
    public function getFrontendUploaderInputField($intId, $langId = 0, $value = '', $elementId = 'ELEMENT-KEY')
    {
        global $_ARRAYLANG;

        return <<<ELM
<input type="text" name="{$this->moduleNameLC}InputfieldSource[$intId][$langId][$elementId]" value="$value" id="{$this->moduleNameLC}Inputfield_{$intId}_{$langId}_{$elementId}" autocomplete="off" onfocus="this.select();" /> &nbsp;<input type="button" onClick="getUploader(\$J(this));" data-input-id="{$this->moduleNameLC}Inputfield_{$intId}_{$langId}_$elementId" value="{$_ARRAYLANG['TXT_BROWSE']}" />
ELM;
    }
}

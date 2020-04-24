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
 * Media Directory Inputfield File Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;
/**
 * Media Directory Inputfield File Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfieldFile extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary implements Inputfield
{
    public $arrPlaceholders = array('TXT_MEDIADIR_INPUTFIELD_NAME','MEDIADIR_INPUTFIELD_VALUE','MEDIADIR_INPUTFIELD_VALUE_SRC', 'MEDIADIR_INPUTFIELD_VALUE_NAME', 'MEDIADIR_INPUTFIELD_VALUE_FILENAME');

    private $imagePath;
    private $imageWebPath;

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
        global $objDatabase, $_ARRAYLANG, $objInit;

        $langId = static::getOutputLocale()->getId();

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                $intId = intval($arrInputfield['id']);

                $arrValue = array();
                if(!empty($intEntryId)) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`,
                            `lang_id`
                          FROM ".DBPREFIX."module_mediadir_rel_entry_inputfields
                         WHERE field_id=$intId
                           AND entry_id=$intEntryId");
                    if ($objInputfieldValue) {
                        while (!$objInputfieldValue->EOF) {
                            $arrValue[intval($objInputfieldValue->fields['lang_id'])] = contrexx_raw2xhtml($objInputfieldValue->fields['value']);
                            $objInputfieldValue->MoveNext();
                        }
                        $arrValue[0] = isset($arrValue[$langId]) ? $arrValue[$langId] : null;
                    }
                } else {
                    $arrValue = null;
                }

                $countFrontendLang = count($this->arrFrontendLanguages);

                $minimize  = '';
                if ($objInit->mode == 'backend' || $this->arrSettings['settingsFrontendUseMultilang']) {
                    $minimize  = "<a href=\"javascript:ExpandMinimize('$intId');\">{$_ARRAYLANG['TXT_MEDIADIR_MORE']}&nbsp;&raquo;</a>";
                }

                $strDefaultValue = isset($arrValue[0]) ? $arrValue[0] : '';
                $strDefaultInput = $this->getInput($intId, $strDefaultValue, 0, $arrInputfield);
                $strInputfield   = <<<INPUT
                        <div id="{$this->moduleNameLC}Inputfield_{$intId}_Minimized" class="{$this->moduleNameLC}GroupMultilang" style="display: block; float:left;">
                            $strDefaultInput
                            $minimize
                        </div>
INPUT;
                if ($objInit->mode == 'backend' || $this->arrSettings['settingsFrontendUseMultilang']) {

                    $strInputfield .= '<div id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_Expanded" class="'. $this->moduleNameLC.'GroupMultilang" style="display: none; float:left;">';

                    foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                        $intLangId = $arrLang['id'];
                        $minimize  = '';
                        if(($key+1) == $countFrontendLang) {
                            $minimize = "&nbsp;<a href=\"javascript:ExpandMinimize('".$intId."');\">&laquo;&nbsp;".$_ARRAYLANG['TXT_MEDIADIR_MINIMIZE']."</a>";
                        }

                        $value    = isset($arrValue[$intLangId]) ? $arrValue[$intLangId] : '';
                        $strInput = $this->getInput($intId, $value, $intLangId);
                        $strInputfield .= <<<INPUT
                            <div>
                                $strInput
                                $minimize
                            </div>
INPUT;
                    }
                    $strInputfield .= '</div>';
                }
                return $strInputfield;
                break;
            case 2:
                //search View
                break;
        }
    }


    /**
     * Get input field based on language id and value
     *
     * @param integer $id            Input field id
     * @param string  $value         Input field value
     * @param integer $langId        Language id
     * @param array   $arrInputfield Language id
     *
     * @return string Return input field based on language id and value
     */
    private function getInput($id = 0, $value = '', $langId = 0, $arrInputfield = array())
    {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        $arrValue = explode(",", $value);

        $filePath    = $arrValue[0];
        $displayName = null;

        $strFilePreview = null;
        if(!empty($filePath) && file_exists(\Env::get('cx')->getWebsitePath().$filePath)) {
            $arrFileInfo = pathinfo($filePath);
            $strFileName = htmlspecialchars($arrFileInfo['basename'], ENT_QUOTES, CONTREXX_CHARSET);

            if (empty($arrValue[1])) {
                $displayName = $strFileName;
            } else {
                $displayName = strip_tags(htmlspecialchars($arrValue[1], ENT_QUOTES, CONTREXX_CHARSET));
            }
            $strFilePreview = '<a href="'.urldecode($filePath).'" target="_blank">'.$strFileName.'</a>&nbsp;
                                <input
                                    data-id="'.$id.'"
                                    type="checkbox"
                                    class="'. (!$langId ? 'mediadirInputfieldDefaultDeleteFile' : '') .'"
                                    id="mediadirInputfield_delete_'.$id.'_'.$langId.'"
                                    value="1"
                                    name="deleteMedia['.$id.']['.$langId.']"
                                />'.$_ARRAYLANG['TXT_MEDIADIR_DELETE'].'<br />';
        }

        $flagPath   = $cx->getCodeBaseOffsetPath() . $cx->getCoreFolderName().'/Country/View/Media/Flag';
        $inputStyle =   !empty($langId)
                      ? 'background: #ffffff url(\''. $flagPath .'/flag_'. \FWLanguage::getLanguageCodeById($langId) .'.gif\') no-repeat 3px 3px;'
                      : '';
        $inputDefaultClass = empty($langId) ? $this->moduleNameLC . 'InputfieldDefault' : $this->moduleNameLC . 'LangInputfield';

        $mode = $cx->getMode();
        if ($mode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            $strInputfield = <<<INPUT
            $strFilePreview
            <input type="text" name="{$this->moduleNameLC}Inputfield[$id][file][$langId]"
                value="$filePath"
                data-id="$id"
                class="$inputDefaultClass"
                id="{$this->moduleNameLC}Inputfield_{$id}_$langId"
                style="$inputStyle"
                autocomplete="off"
                onfocus="this.select();" />
            &nbsp;
            <input type="button"
                onClick="getMediaBrowser(\$J(this));"
                data-input-id="{$this->moduleNameLC}Inputfield_{$id}_$langId"
                data-views="filebrowser"
                data-startmediatype="{$this->moduleNameLC}"
                value="{$_ARRAYLANG['TXT_BROWSE']}"
            />
            <br />
            <input type="text" name="{$this->moduleNameLC}Inputfield[{$id}][name][$langId]"
                value="$displayName"
                data-id="$id"
                data-related-field-prefix="{$this->moduleNameLC}InputfieldFileDisplayName"
                class="{$this->moduleNameLC}InputfieldFileDisplayName $inputDefaultClass"
                id="{$this->moduleNameLC}InputfieldFileDisplayName_{$id}_$langId"
                onfocus="this.select();" />
            &nbsp;<i>{$_ARRAYLANG['TXT_MEDIADIR_DISPLAYNAME']}</i>
INPUT;
        } else {
            if (empty($filePath) || $filePath == "new_image") {
                $strValueHidden = "new_image";
                $filePath = "";
            } else {
                $strValueHidden = $filePath;
            }

            $strInfoValue = $strInfoClass = '';
            $strInfo =  !empty($arrInputfield['info'][$langId])
                      ? $arrInputfield['info'][$langId]
                      : (  !empty($arrInputfield['info'][0])
                         ? $arrInputfield['info'][0]
                         : '');
            if ($strInfo) {
                $strInfoValue = 'title="' . $strInfo . '"';
                $strInfoClass = 'mediadirInputfieldHint';
            }
            $strInputfield = <<<INPUT
            $strFilePreview
            <input type="text" name="{$this->moduleNameLC}InputfieldSource[$id][$langId]"
                value="$value"
                data-id="$id"
                class="$inputDefaultClass"
                id="{$this->moduleNameLC}Inputfield_{$id}_$langId"
                style="$inputStyle"
                autocomplete="off"
                onfocus="this.select();" />
            &nbsp;
            <input type="button"
                onClick="getUploader(\$J(this));"
                data-input-id="{$this->moduleNameLC}Inputfield_{$id}_$langId"
                value="{$_ARRAYLANG['TXT_BROWSE']}"
            />
            <br />
            <input id="{$this->moduleNameLC}Inputfield_{$id}_{$langId}_hidden"
                name="{$this->moduleNameLC}Inputfield[$id][file][$langId]"
                value="{$strValueHidden}" type="hidden" />
            <br />
            <input type="text" name="{$this->moduleNameLC}Inputfield[$id][name][$langId]"
                value="$displayName"
                data-id="$id"
                data-related-field-prefix="{$this->moduleNameLC}InputfieldFileDisplayName"
                class="{$this->moduleNameLC}InputfieldFileDisplayName $inputDefaultClass"
                id="{$this->moduleNameLC}InputfieldFileDisplayName_{$id}_$langId"
                onfocus="this.select();" />
            &nbsp;<i>{$_ARRAYLANG['TXT_MEDIADIR_DISPLAYNAME']}</i>
INPUT;
        }
        return $strInputfield;
    }

    function saveInputfield($intInputfieldId, $strValue, $langId = 0)
    {
        global $objInit;
        static $strNewDefault = null;

        $strValue = contrexx_input2raw($_POST[$this->moduleNameLC.'Inputfield'][$intInputfieldId]['file'][$langId]);
        $strName =  !empty($_POST[$this->moduleNameLC.'Inputfield'][$intInputfieldId]['name'][$langId])
                        ? ",".contrexx_input2raw($_POST[$this->moduleNameLC.'Inputfield'][$intInputfieldId]['name'][$langId])
                        : '';
        $deleteMedia = !empty($_POST["deleteMedia"]) && !empty($_POST["deleteMedia"][$intInputfieldId]);

        if($objInit->mode == 'backend') {
            if (   $deleteMedia
                && $_POST["deleteMedia"][$intInputfieldId][$langId] == 1
            ) {
                $strValue = null;
            }
        } else {
            $inputFiles  = !empty($_POST['mediadirInputfieldSource'][$intInputfieldId]) ? $_POST['mediadirInputfieldSource'][$intInputfieldId] : array();

            if ($deleteMedia && $_POST["deleteMedia"][$intInputfieldId][$langId] == 1) {
                //delete file
                $this->deleteFile($strValue);
                $strValue = null;
            } elseif (!empty($inputFiles) && !empty($inputFiles[$langId])) {
                $uploaderId = !empty($_POST['uploaderId']) ? $_POST['uploaderId'] : '';
                $filePath   = $this->getUploadedFilePath($uploaderId, $inputFiles[$langId]);

                if ($filePath) {
                    //delete file
                    $this->deleteFile($strValue);
                    $strValue = $this->uploadMedia($filePath);
                } else {
                    $strValue = null;
                }
            } else {
                if (!$strNewDefault && !empty($langId)) {
                    $strNewDefault = $this->saveInputfield($intInputfieldId, '');
                }
                $strValue = $strNewDefault;
            }
        }

        return $strValue.$strName;
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


    function deleteContent($intEntryId, $intIputfieldId)
    {
        global $objDatabase;

        //get file path
        // $objFilePathRS = $objDatabase->Execute("SELECT value FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields WHERE `entry_id`='".intval($intEntryId)."' AND  `field_id`='".intval($intIputfieldId)."'");
        // $strFilePath   = $objFilePathRS->fields['value'];

        //delete relation
        $objDeleteInputfieldRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields WHERE `entry_id`='".intval($intEntryId)."' AND  `field_id`='".intval($intIputfieldId)."'");

        if($objDeleteInputfieldRS !== false) {
            //delete image
            //$this->deleteFile($strFilePath);
            return true;
        } else {
            return false;
        }
    }



    function getContent($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
        $arrValue = explode(
            ",",
            static::getRawData($intEntryId, $arrInputfield, $arrTranslationStatus)
        );
        $strValue = strip_tags(htmlspecialchars($arrValue[0], ENT_QUOTES, CONTREXX_CHARSET));

        if(!empty($strValue) && $strValue != 'new_file') {
            $arrFileInfo    = pathinfo($strValue);
            $strFileName    = htmlspecialchars($arrFileInfo['basename'], ENT_QUOTES, CONTREXX_CHARSET);
            if(empty($arrValue[1])) {
                $strName = $strFileName;
            } else {
                $strName = strip_tags(htmlspecialchars($arrValue[1], ENT_QUOTES, CONTREXX_CHARSET));
            }

            $arrContent['TXT_'.$this->moduleLangVar.'_INPUTFIELD_NAME'] = htmlspecialchars($arrInputfield['name'][0], ENT_QUOTES, CONTREXX_CHARSET);
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'] = '<a href="'.urldecode($strValue).'" alt="'.$strName.'" title="'.$strName.'" target="_blank">'.$strName.'</a>';
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE_SRC'] = urldecode($strValue);
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE_NAME'] = $strName;
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE_FILENAME'] = $strFileName;
        } else {
            $arrContent = null;
        }

        return $arrContent;
    }

    function getRawData($intEntryId, $arrInputfield, $arrTranslationStatus) {
        global $objDatabase;

        $intId = intval($arrInputfield['id']);
        $intEntryDefaultLang = $objDatabase->getOne("SELECT `lang_id` FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_entries WHERE id=".intval($intEntryId)." LIMIT 1");
        $langId = static::getOutputLocale()->getId();

        if($this->arrSettings['settingsTranslationStatus'] == 1) {
            $intLangId = in_array($langId, $arrTranslationStatus) ? $langId : contrexx_input2int($intEntryDefaultLang);
        } else {
            $intLangId = $langId;
        }
        $objResult = $objDatabase->Execute("
            SELECT `value`
              FROM ".DBPREFIX."module_mediadir_rel_entry_inputfields
             WHERE field_id=$intId
               AND entry_id=$intEntryId
               AND lang_id=$intLangId
             LIMIT 1 ");

        if(empty($objResult->fields['value'])) {
            $objResult = $objDatabase->Execute("
                SELECT `value`
                  FROM ".DBPREFIX."module_mediadir_rel_entry_inputfields
                 WHERE field_id=$intId
                   AND entry_id=$intEntryId
                   AND lang_id=$intEntryDefaultLang
                 LIMIT 1 ");
        }

        return $objResult->fields['value'];
    }


    function getJavascriptCheck()
    {
        $fieldName = $this->moduleNameLC."Inputfield_";

        if(\Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            $hiddenField = "value_hidden = false";
        } else {
            $hiddenField = "value_hidden = document.getElementById('".$fieldName."' + field + '_0_hidden').value;";
        }

        $strJavascriptCheck = <<<EOF

            case 'file':
                value = document.getElementById('$fieldName' + field + '_0').value;
                $hiddenField
                if (value == "" && value_hidden == "" && isRequiredGlobal(inputFields[field][1], value)) {
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

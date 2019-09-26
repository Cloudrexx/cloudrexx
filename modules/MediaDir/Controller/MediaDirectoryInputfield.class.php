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
 * Media  Directory Inputfield Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/*function loadInputfieldClasses($strClassName) {
    $strClassFileName = strtolower(str_replace('\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield', '', $strClassName));

    if(!file_exists(ASCMS_MODULE_PATH . '/mediadir/lib/inputfields/'.$strClassFileName.'.class.php')) {
        throw new \Exception(ASCMS_MODULE_PATH . '/mediadir/lib/inputfields/'.$strClassFileName.'.class.php not found!<br />');
    } else {
        return require_once(ASCMS_MODULE_PATH . '/mediadir/lib/inputfields/'.$strClassFileName.'.class.php');
    }
}

spl_autoload_register('loadInputfieldClasses');*/

function safeNew($strClassName, $name) {
    return new $strClassName($name);
}

/**
 * Media Directory Inputfield Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfield extends MediaDirectoryLibrary
{
    public $arrInputfields = array();

    public $strOkMessage;
    public $strErrMessage;

    public $intFormId;
    public $bolExpSearch;

    private $strJavascriptInputfieldArray;
    private $strJavascriptInputfieldCheck = array();
    private $arrTranslationStatus = array();

    public $arrJavascriptFormOnSubmit = array();

    /**
     * Constructor
     */
    function __construct($intFormId=null, $bolExpSearch=false, $arrTranslationStatus=null, $name)
    {
        //get active frontent languages
        parent::__construct('.', $name);
        parent::getFrontendLanguages();
        parent::getSettings();
        $this->intFormId = intval($intFormId);
        $this->bolExpSearch = $bolExpSearch;
        $this->arrTranslationStatus = $arrTranslationStatus;
        $this->arrInputfields = self::getInputfields();
    }

    function getInputfields()
    {
        global $_ARRAYLANG, $objDatabase;

        $langId = static::getOutputLocale()->getId();

        $whereFormId  = 'AND (`form`.`active` = 1)';
        $joinFormsTbl = 'LEFT JOIN `' . DBPREFIX .'module_' . $this->moduleTablePrefix . '_forms` as form
                        ON (`form`.`id` = `input`.`form`)';
        if (intval($this->intFormId) != 0) {
            $joinFormsTbl = '';
            $whereFormId  = 'AND (`input`.`form` = "' . $this->intFormId . '")';
        }

        $whereExpSearch = null;
        if ($this->bolExpSearch) {
            $whereExpSearch = 'AND (`input`.`search` = "1")';
        }

        $objInputfields = $objDatabase->Execute('
            SELECT
                input.`id` AS `id`,
                input.`order` AS `order`,
                input.`form` AS `form`,
                input.`type` AS `type`,
                input.`show_in` AS `show_in`,
                input.`verification` AS `verification`,
                input.`required` AS `required`,
                input.`search` AS `search`,
                input.`context_type` AS `context_type`,
                names.`field_name` AS `field_name`,
                names.`field_default_value` AS `field_default_value`,
                names.`field_info` AS `field_info`,
                verifications.`regex` AS `pattern`,
                types.`name` AS `type_name`,
                types.`multi_lang` AS `type_multi_lang`,
                types.`dynamic` AS `type_dynamic`,
                types.`exp_search` As `exp_search`
            FROM
                `' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_inputfields` AS input
                ' . $joinFormsTbl . '
                LEFT JOIN `' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_inputfield_names` AS names
                    ON (`names`.`field_id` = `input`.`id`)
                LEFT JOIN `' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_inputfield_verifications` AS verifications
                    ON (`input`.`verification` = `verifications`.`id`)
                LEFT JOIN `' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_inputfield_types` AS types
                    ON (`input`.`type` = `types`.`id`)
            WHERE
                (`names`.`lang_id` = ' . $langId . ')
                ' . $whereFormId . '
                ' . $whereExpSearch . '
            ORDER BY
                `input`.`order` ASC, `input`.`id` ASC
        ');

        $arrInputfields = array();
        if ($objInputfields !== false) {
            while (!$objInputfields->EOF) {
                $arrInputfield = array();
                $arrInputfieldName = array();
                $arrInputfieldDefaultValue = array();

                //get default lang attributes
                $arrInputfieldName[0] = $objInputfields->fields['field_name'];
                $arrInputfieldDefaultValue[0] = $objInputfields->fields['field_default_value'];
                $arrInputfieldInfo[0] = $objInputfields->fields['field_info'];

                $objInputfieldAttributes = $objDatabase->Execute("
                    SELECT
                        `lang_id`,
                        `field_name`,
                        `field_default_value`,
                        `field_info`
                    FROM
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfield_names
                    WHERE
                        field_id=".$objInputfields->fields['id']."
                ");

                if ($objInputfieldAttributes !== false) {
                    while (!$objInputfieldAttributes->EOF) {
                        $arrInputfieldName[$objInputfieldAttributes->fields['lang_id']] = htmlspecialchars($objInputfieldAttributes->fields['field_name'], ENT_QUOTES, CONTREXX_CHARSET);
                        $arrInputfieldDefaultValue[$objInputfieldAttributes->fields['lang_id']] = $objInputfieldAttributes->fields['field_default_value'];
                        $arrInputfieldInfo[$objInputfieldAttributes->fields['lang_id']] = $objInputfieldAttributes->fields['field_info'];

                        $objInputfieldAttributes->MoveNext();
                    }
                }

                $arrInputfield['id'] = intval($objInputfields->fields['id']);
                $arrInputfield['order'] = intval($objInputfields->fields['order']);
                $arrInputfield['form'] = intval($objInputfields->fields['form']);
                $arrInputfield['type'] = intval($objInputfields->fields['type']);
                $arrInputfield['type_name'] = htmlspecialchars($objInputfields->fields['type_name'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrInputfield['type_multi_lang'] = intval($objInputfields->fields['type_multi_lang']);
                $arrInputfield['type_dynamic'] = intval($objInputfields->fields['type_dynamic']);
                $arrInputfield['show_in'] = intval($objInputfields->fields['show_in']);
                $arrInputfield['verification'] = intval($objInputfields->fields['verification']);
                $arrInputfield['regex'] = $objInputfields->fields['pattern'];
                $arrInputfield['required'] = intval($objInputfields->fields['required']);
                $arrInputfield['search'] = intval($objInputfields->fields['search']);
                $arrInputfield['name'] = $arrInputfieldName;
                $arrInputfield['default_value'] = $arrInputfieldDefaultValue;
                $arrInputfield['info'] = $arrInputfieldInfo;
                $arrInputfield['context_type'] = $objInputfields->fields['context_type'];
                $arrInputfield['exp_search']   = $objInputfields->fields['exp_search'];
                $arrInputfields[$objInputfields->fields['id']] = $arrInputfield;
                $objInputfields->MoveNext();
            }
        }

        $arrCategorySelector['id'] = 1;
        $arrCategorySelector['order'] = !empty($this->intFormId) ? $this->arrSettings['categorySelectorOrder'][$this->intFormId] : 0;
        $arrCategorySelector['name'] = array(0 => $_ARRAYLANG['TXT_MEDIADIR_CATEGORIES']);
        $arrCategorySelector['type_name'] = '';
        $arrCategorySelector['required'] = 1;
        $arrCategorySelector['type'] = 0;
        // in frontend, categorySelectorExpSearch is only set for active forms
        $arrCategorySelector['search'] = !empty($this->intFormId) ? $this->arrSettings['categorySelectorExpSearch'][$this->intFormId] : 0;
        $arrInputfields[1] = $arrCategorySelector;

        if($this->arrSettings['settingsShowLevels']) {
            $arrLevelSelector['id'] = 2;
            $arrLevelSelector['order'] = !empty($this->intFormId) ? $this->arrSettings['levelSelectorOrder'][$this->intFormId] : 0;
            $arrLevelSelector['name'] = array(0 => $_ARRAYLANG['TXT_MEDIADIR_LEVELS']);
            $arrLevelSelector['type_name'] = '';
            $arrLevelSelector['required'] = 1;
            $arrLevelSelector['type'] = 0;
            // in frontend, levelSelectorExpSearch is only set for active forms
            $arrLevelSelector['search'] = !empty($this->intFormId) ? $this->arrSettings['levelSelectorExpSearch'][$this->intFormId] : 0;
            $arrInputfields[2] = $arrLevelSelector;
        }

        return $arrInputfields;
    }



    function sortInputfields($a, $b)
    {
        if ($a['order'] == $b['order']) {
            return ($a['id'] < $b['id']) ? -1 : 1;
        }
        return ($a['order'] < $b['order']) ? -1 : 1;
    }



    public function listInputfields($objTpl, $intView, $intEntryId = null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $objInit;

        usort($this->arrInputfields, array(__CLASS__, "sortInputfields"));

        switch ($intView) {
            case 1:
                //Settings View
                $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_INPUTFIELDS_CONTENT', 'settings_inputfields_content', 'module_'.$this->moduleNameLC.'_settings_inputfields.html');

                $objForms = new MediaDirectoryForm($this->intFormId, $this->moduleName);

                $arrShow = array(
                    1 => $_ARRAYLANG['TXT_MEDIADIR_SHOW_BACK_N_FRONTEND'],
                    2 => $_ARRAYLANG['TXT_MEDIADIR_SHOW_FRONTEND'],
                    3 => $_ARRAYLANG['TXT_MEDIADIR_SHOW_BACKEND'],
                );

                $i=0;
                $intLastId = 0;
                foreach ($this->arrInputfields as $arrInputfield) {
                    $strMustfield = $arrInputfield['required']==1 ? 'checked="checked"' : '';
                    $strExpSearch = $arrInputfield['search']==1 ? 'checked="checked"' : '';

                    if($arrInputfield['id'] > $intLastId) {
                        $intLastId = $arrInputfield['id'];
                    }

                    $objTpl->setGlobalVariable(array(
                        $this->moduleLangVar.'_SETTINGS_INPUTFIELD_ROW_CLASS' => $i%2==0 ? 'row1' : 'row2',
                        $this->moduleLangVar.'_SETTINGS_INPUTFIELD_LASTID' => $intLastId,
                    ));

                    if($arrInputfield['id'] != 1 && $arrInputfield['id'] != 2) {
                        $objTpl->setGlobalVariable(array(
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_ID' => $arrInputfield['id'],
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_FORM_ID' => $this->intFormId,
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_ORDER' => $arrInputfield['order'],
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_TYPE' => $this->buildDropdownmenu($this->getInputfieldTypes(), $arrInputfield['type']),
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_VERIFICATION' => $this->buildDropdownmenu($this->getInputfieldVerifications(), $arrInputfield['verification']),
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_SHOW' => $this->buildDropdownmenu($arrShow, $arrInputfield['show_in']),
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_CONTEXT' => $this->buildDropdownmenu($this->getInputContexts(), $arrInputfield['context_type']),
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_MUSTFIELD' => $strMustfield,
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_EXP_SEARCH' => $strExpSearch,
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_NAME_MASTER' => $arrInputfield['name'][0],
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_DEFAULTVALUE_MASTER' => contrexx_raw2xhtml($arrInputfield['default_value'][0]),
                            $this->moduleLangVar.'_SETTINGS_INPUTFIELD_INFO_MASTER' => $arrInputfield['info'][0],
                        ));

                        //fieldname
                        foreach ($this->arrFrontendLanguages as $arrLang) {
                            $objTpl->setVariable(array(
                                $this->moduleLangVar.'_INPUTFIELD_NAME_LANG_ID' => $arrLang['id'],
                                $this->moduleLangVar.'_INPUTFIELD_NAME_LANG_SHORTCUT' => $arrLang['lang'],
                                $this->moduleLangVar.'_INPUTFIELD_NAME_LANG_NAME' => $arrLang['name'],
                                $this->moduleLangVar.'_SETTINGS_INPUTFIELD_NAME' => $arrInputfield['name'][$arrLang['id']],
                            ));
                            $objTpl->parse($this->moduleNameLC.'InputfieldNameList');
                        }

                        //default values
                        foreach ($this->arrFrontendLanguages as $arrLang) {
                            $objTpl->setVariable(array(
                                $this->moduleLangVar.'_INPUTFIELD_DEFAULTVALUE_LANG_ID' => $arrLang['id'],
                                $this->moduleLangVar.'_INPUTFIELD_DEFAULTVALUE_LANG_SHORTCUT' => $arrLang['lang'],
                                $this->moduleLangVar.'_INPUTFIELD_DEFAULTVALUE_LANG_NAME' => $arrLang['name'],
                                $this->moduleLangVar.'_SETTINGS_INPUTFIELD_DEFAULTVALUE' => contrexx_raw2xhtml($arrInputfield['default_value'][$arrLang['id']]),
                            ));
                            $objTpl->parse($this->moduleNameLC.'InputfieldDefaultvalueList');
                        }

                        //infotext
                        foreach ($this->arrFrontendLanguages as $arrLang) {
                            $objTpl->setVariable(array(
                                $this->moduleLangVar.'_INPUTFIELD_INFO_LANG_ID' => $arrLang['id'],
                                $this->moduleLangVar.'_INPUTFIELD_INFO_LANG_SHORTCUT' => $arrLang['lang'],
                                $this->moduleLangVar.'_INPUTFIELD_INFO_LANG_NAME' => $arrLang['name'],
                                $this->moduleLangVar.'_SETTINGS_INPUTFIELD_INFO' => $arrInputfield['info'][$arrLang['id']],
                            ));
                            $objTpl->parse($this->moduleNameLC.'InputfieldInfoList');
                        }

                        //language names
                        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                            if(($key+1) == count($this->arrFrontendLanguages)) {
                                $minimize = "<a id=\"inputfieldMinimize_".$arrInputfield['id']."\" href=\"javascript:ExpandMinimizeInputfields('inputfieldName', '".$arrInputfield['id']."'); ExpandMinimizeInputfields('inputfieldDefaultvalue', '".$arrInputfield['id']."'); ExpandMinimizeInputfields('inputfieldLanguages', '".$arrInputfield['id']."'); ExpandMinimizeInputfields('inputfieldInfo', '".$arrInputfield['id']."');\">&laquo;&nbsp;".$_ARRAYLANG['TXT_MEDIADIR_MINIMIZE']."</a>";
                            } else {
                                $minimize = "";
                            }

                            $objTpl->setVariable(array(
                                $this->moduleLangVar.'_INPUTFIELD_LANG_NAME' => $arrLang['name'],
                                $this->moduleLangVar.'_INPUTFIELD_MINIMIZE' => $minimize,
                            ));
                            $objTpl->parse($this->moduleNameLC.'InputfieldLanguagesList');
                        }

                        if ($arrInputfield['exp_search'] == 0) {
                            $objTpl->hideBlock($this->moduleNameLC . 'InputfieldAdvancedSearch');
                        } else {
                            $objTpl->touchBlock($this->moduleNameLC . 'InputfieldAdvancedSearch');
                        }

                        $objTpl->parse($this->moduleNameLC.'Inputfield');
                    } else {
                        if(($arrInputfield['id'] == 2 && $objForms->arrForms[$this->intFormId]['formUseLevel']) || ($arrInputfield['id'] == 1 && $objForms->arrForms[$this->intFormId]['formUseCategory'])) {

                            $objTpl->setVariable(array(
                                $this->moduleLangVar.'_SETTINGS_SELECTOR_ID' => $arrInputfield['id'],
                                $this->moduleLangVar.'_SETTINGS_SELECTOR_NAME' => $arrInputfield['name'][0],
                                $this->moduleLangVar.'_SETTINGS_SELECTOR_ORDER' => $arrInputfield['order'],
                                $this->moduleLangVar.'_SETTINGS_SELECTOR_EXP_SEARCH' => $strExpSearch,
                            ));

                            $objTpl->parse($this->moduleNameLC.'Selector');
                        }
                    }

                    $i++;
                    $objTpl->parse($this->moduleNameLC.'InputfieldList');
                }

                $objTpl->parse('settings_inputfields_content');
                break;
            case 2:
                //modify (add/edit) View
                $objAddStep = new MediaDirectoryAddStep($this->moduleName);
                $i = 0;
                $isFileInputFound = false;
                $langId = static::getOutputLocale()->getId();
                foreach ($this->arrInputfields as $arrInputfield) {
                    $strInputfield = null;

                    if($arrInputfield['required'] == 1) {
                        $strRequiered = '<font color="#ff0000"> *</font>';
                    } else {
                        $strRequiered = null;
                    }

                    if(!empty($arrInputfield['type'])) {
                        if (   !$isFileInputFound
                            && in_array($arrInputfield['type_name'], array('image', 'file', 'downloads'))
                        ) {
                            $isFileInputFound = true;
                        }
                        $strType = $arrInputfield['type_name'];
                        $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield".ucfirst($strType);

                        try {
                            $objInputfield = safeNew($strInputfieldClass, $this->moduleName);

                            switch($strType) {
                                case 'add_step':
                                    $objAddStep->addNewStep(empty($arrInputfield['name'][$langId]) ? $arrInputfield['name'][0].$strRequiered : $arrInputfield['name'][$langId]);
                                    $strInputfield = $objInputfield->getInputfield(1, $arrInputfield, $intEntryId, $objAddStep);
                                    break;
                                case 'field_group':
                                    //to do
                                    break;
                                default:
                                    if($arrInputfield['show_in'] == 1) {
                                        $bolGetInputfield = true;
                                    } else {
                                        if($objInit->mode == 'backend' && $arrInputfield['show_in'] == 3) {
                                            $bolGetInputfield = true;
                                        } else if ($objInit->mode == 'frontend' && $arrInputfield['show_in'] == 2) {
                                            $bolGetInputfield = true;
                                        } else {
                                            $bolGetInputfield = false;
                                        }
                                    }

                                    if($bolGetInputfield) {
                                        $strInputfield = $objInputfield->getInputfield(1, $arrInputfield, $intEntryId);
                                    } else {
                                        $strInputfield = null;
                                    }

                                    break;
                            }

                            if($strInputfield != null) {
                                $this->makeJavascriptInputfieldArray($arrInputfield['id'], $this->moduleNameLC."Inputfield[".$arrInputfield['id']."]",  $arrInputfield['required'],  $arrInputfield['regex'], $strType);
                                $this->strJavascriptInputfieldCheck[$strType] = $objInputfield->getJavascriptCheck();
                                $this->arrJavascriptFormOnSubmit[$arrInputfield['id']] = $objInputfield->getFormOnSubmit($arrInputfield['id']);
                            }
                        } catch (Exception $error) {
                            echo "Error: ".$error->getMessage();
                        }
                    } else {
                        $objForms = new MediaDirectoryForm($this->intFormId, $this->moduleName);

                        /*if($objInit->mode == 'backend') {
                            $strStyle = 'style="overflow: auto; border: 1px solid #0A50A1; background-color: #ffffff; width: 298px; height: 200px; float: left; list-style: none; padding: 0px; margin: 0px 5px 0px 0px;"';
                        } else {
                            $strStyle = 'style="overflow: auto; float: left; list-style: none; padding: 0px; margin: 0px 5px 0px 0px;"';
                        }*/

                        if(($arrInputfield['id'] == 2 && $objForms->arrForms[$this->intFormId]['formUseLevel']) || ($arrInputfield['id'] == 1 && $objForms->arrForms[$this->intFormId]['formUseCategory'])) {
                            if($arrInputfield['id'] == 2) {
                                $objLevel = new MediaDirectoryLevel(null, null, 1, $this->moduleName);
                                    $arrSelectorOptions = $objLevel->listLevels($objTpl, 4, null, null, $intEntryId);
                                $strSelectedOptionsName = "selectedLevels";
                                $strNotSelectedOptionsName = "deselectedLevels";
                            } else {
                                $objCategory = new MediaDirectoryCategory(null, null, 1, $this->moduleName);
                                $arrSelectorOptions = $objCategory->listCategories($objTpl, 4, null, null, $intEntryId);
                                $strSelectedOptionsName = "selectedCategories";
                                $strNotSelectedOptionsName = "deselectedCategories";
                            }

                            $strInputfield .= '<div class="mediadirSelector container-fluid"><div class="row"><div class="col-md-offset-3">';
                            $strInputfield .= '<div class="col-md-4 col-sm-12 col-xs-12 mediadirSelectorLeft"><div class="row"><select id="'.$strNotSelectedOptionsName.'" name="'.$strNotSelectedOptionsName.'[]" size="12" multiple="multiple">';
                            $strInputfield .= $arrSelectorOptions['not_selected'];
                            $strInputfield .= '</select></div></div>';
                            $strInputfield .= '<div class="mediadirSelectorCenter col-md-2 col-sm-12 col-xs-12">';
                            $strInputfield .= '<input class="btn btn-default" value=" &gt;&gt; " name="addElement" onclick="moveElement(document.entryModfyForm.elements[\''.$strNotSelectedOptionsName.'\'],document.entryModfyForm.elements[\''.$strSelectedOptionsName.'\'],addElement,removeElement);" type="button">';
                            $strInputfield .= '<br />';
                            $strInputfield .= '<input class="btn btn-default" value=" &lt;&lt; " name="removeElement" onclick="moveElement(document.entryModfyForm.elements[\''.$strSelectedOptionsName.'\'],document.entryModfyForm.elements[\''.$strNotSelectedOptionsName.'\'],removeElement,addElement);" type="button">';
                            $strInputfield .= '</div>';
                            $strInputfield .= '<div class="col-md-4 col-sm-12 col-xs-12 mediadirSelectorRight"><div class="row"><select id="'.$strSelectedOptionsName.'" name="'.$strSelectedOptionsName.'[]" size="12" multiple="multiple">';
                            $strInputfield .= $arrSelectorOptions['selected'];
                            $strInputfield .= '</select></div></div>';
                            $strInputfield .= '</div></div></div>';

                            $this->makeJavascriptInputfieldArray($arrInputfield['id'], $strSelectedOptionsName, 1, 1, "selector");
                            $this->arrJavascriptFormOnSubmit[$arrInputfield['id']] = "selectAll(document.entryModfyForm.elements['".$strSelectedOptionsName."[]']); ";
                        }
                    }

                    if($arrInputfield['type_name'] == 'add_step' && $objInit->mode != 'backend') {
                        $objTpl->setVariable(array(
                            $this->moduleLangVar.'_INPUTFIELD_ADDSTEP' => $strInputfield,
                        ));

                        $objTpl->parse($this->moduleNameLC.'InputfieldAddStep');
                    } else {
                        if($strInputfield != null) {
                            if($arrInputfield['type_name'] == 'title') {
                                $strStartTitle = '<h2>';
                                $strEndTitle = '</h2>';
                            } else {
                                $strStartTitle = '';
                                $strEndTitle = '';
                            }

                            $objTpl->setVariable(array(
                                'TXT_'.$this->moduleLangVar.'_INPUTFIELD_NAME' => $strStartTitle.(empty($arrInputfield['name'][$langId]) ? $arrInputfield['name'][0].$strRequiered : $arrInputfield['name'][$langId].$strRequiered).$strEndTitle,
                                $this->moduleLangVar.'_INPUTFIELD_FIELD' => $strInputfield,
                                $this->moduleLangVar.'_INPUTFIELD_ROW_CLASS' => $i%2==0 ? 'row1' : 'row2',
                            ));

                            if($arrInputfield['type_name'] != 'add_step') {
                                $i++;
                                $objTpl->parse($this->moduleNameLC.'InputfieldList');
                            }
                        }
                    }

                    if($objInit->mode != 'backend') {
                        $objTpl->parse($this->moduleNameLC.'InputfieldElement');
                    }
                }

                if ($isFileInputFound && $objInit->mode != 'backend' ) {
                    // init uploader to upload images
                    $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
                    $uploader->setCallback($this->moduleNameLC .'UploaderCallback');
                    $uploader->setOptions(array(
                        'id'                 => $this->moduleNameLC . 'ImageUploader',
                        'style'              => 'display:none',
                        'data-upload-limit'  => 1,
                    ));
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_UPLOADER_ID'   => $uploader->getId(),
                        $this->moduleLangVar.'_UPLOADER_CODE' => $uploader->getXHtml(),
                    ));
                }

                if(!empty($objAddStep->arrSteps) && $objInit->mode != 'backend') {
                    $objAddStep->getStepNavigation($objTpl);
                    $objTpl->parse($this->moduleNameLC.'EntryAddStepNavigation');

                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_INPUTFIELD_ADDSTEP_TERMINATOR' => "</div>",
                    ));
                }

                break;
            case 3:
                //frontend View
                foreach ($this->arrInputfields as $arrInputfield) {
                    $intInputfieldId = intval($arrInputfield['id']);
                    $intInputfieldType = intval($arrInputfield['type']);

                    if(($objTpl->blockExists($this->moduleNameLC.'_inputfield_'.$intInputfieldId) || $objTpl->blockExists($this->moduleNameLC.'_inputfields')) && ($intInputfieldType != 16 && $intInputfieldType != 17)){
                        if(!empty($arrInputfield['type'])) {
                            $strType = $arrInputfield['type_name'];
                            $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield".ucfirst($strType);
                            try {
                                $objInputfield = safeNew($strInputfieldClass, $this->moduleName);

                                if(intval($arrInputfield['type_multi_lang']) == 1) {
                                    $arrInputfieldContent = $objInputfield->getContent($intEntryId, $arrInputfield, $this->arrTranslationStatus);
                                } else {
                                    $arrInputfieldContent = $objInputfield->getContent($intEntryId, $arrInputfield, null);
                                }

                                if(!empty($arrInputfieldContent)) {
                                    // Workaround as inputfields have placeholder prefix hard-coded to: MEDIADIR_
                                    // Set placeholder prefix according to configured option $this->moduleLangVar
                                    if ($this->moduleLangVar != 'MEDIADIR') {
                                        foreach ($arrInputfieldContent as $key => $value) {
                                            $arrInputfieldContent[preg_replace('/^(TXT_)?MEDIADIR/', '\1' . $this->moduleLangVar, $key)] = $value;
                                            unset($arrInputfieldContent[$key]);
                                        }
                                    }

                                    if (\Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND && \Cx\Core\Setting\Controller\Setting::getValue('blockStatus', 'Config')) {
                                        $arrInputfieldContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'] = preg_replace('/\\[\\[(BLOCK_[A-Z0-9_-]+)\\]\\]/', '{\\1}', $arrInputfieldContent[$this->moduleLangVar.'_INPUTFIELD_VALUE']);
                                        \Cx\Modules\Block\Controller\Block::setBlocks($arrInputfieldContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'], \Cx\Core\Core\Controller\Cx::instanciate()->getPage());
                                    }
                                    foreach ($arrInputfieldContent as $strPlaceHolder => $strContent) {
                                        $objTpl->setVariable(array(
                                            strtoupper($strPlaceHolder) => $strContent
                                        ));
                                    }

                                    if($objTpl->blockExists($this->moduleNameLC.'_inputfields')){
                                         $objTpl->parse($this->moduleNameLC.'_inputfields');
                                    } else {
                                        if ($objTpl->blockExists($this->moduleNameLC.'_inputfield_'.$intInputfieldId)){
                                            $objTpl->parse($this->moduleNameLC.'_inputfield_'.$intInputfieldId);
                                        }
                                    }
                                } else {
                                    if($objTpl->blockExists($this->moduleNameLC.'_inputfield_'.$intInputfieldId)){
                                         $objTpl->hideBlock($this->moduleNameLC.'_inputfield_'.$intInputfieldId);
                                    }
                                }
                            } catch (Exception $error) {
                                echo "Error: ".$error->getMessage();
                            }
                        }
                    }

                    $objTpl->clearVariables();
                }
                break;
            case 4:
                //Exp Search View
                $strInputfields = '';
                foreach ($this->arrInputfields as $arrInputfield) {
                    if($this->checkFieldTypeIsExpSeach($arrInputfield['type'])) {
                        if(!empty($arrInputfield['type'])) {
                            $strType = $arrInputfield['type_name'];
                            $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield".ucfirst($strType);
                            try {
                                $objInputfield = safeNew($strInputfieldClass, $this->moduleName);
                                $strInputfield = $objInputfield->getInputfield(2, $arrInputfield);

                                if($strInputfield != null) {
                                    $strInputfields .= '<p><label>'.$arrInputfield['name'][0].'</label>'.$strInputfield.'</p>';
                                }
                            } catch (Exception $error) {
                                echo "Error: ".$error->getMessage();
                            }
                        }
                    }
                }

                return $strInputfields;

                break;
        }
    }

    /**
     * Update form inputfields
     *
     * Before calling this method Remove the existing form inputfield entries from db
     * for avoiding the duplicate entries in db.
     *
     * @param integer $intFieldId            Form InputField id
     * @param array   $arrFieldNames         Form inputField Names array, the key is refered as the language id
     * @param array   $arrFieldDefaultValues Form inputField Default values array, the key is refered as the language id
     * @param array   $arrFieldInfos         Form inputField Information values  array the key is refered as the language id
     *
     * @return boolean true | false
     */
    public function updateInputFields($intFieldId, $arrFieldNames, $arrFieldDefaultValues, $arrFieldInfos, $existingLocaleIds = array())
    {
        global $objDatabase;

        foreach ($this->arrFrontendLanguages as $arrLang) {
            $sourceLocaleId = $this->getSourceLocaleIdForTargetLocale($arrLang['id'], $existingLocaleIds);

            // init output locale values
            if (empty($arrFieldNames[0])){
                $arrFieldNames[0] = '';
            }
            if (empty($arrFieldDefaultValues[0])){
                $arrFieldDefaultValues[0] = '';
            }
            if (empty($arrFieldInfos[0])){
                $arrFieldInfos[0] = '';
            }

            if (
                (
                    !$existingLocaleIds ||
                    in_array($arrLang['id'], $existingLocaleIds)
                ) &&
                isset($arrFieldNames[$arrLang['id']])
            ) {
                $strFieldName = $arrFieldNames[$arrLang['id']];
            } else {
                $strFieldName = $arrFieldNames[$sourceLocaleId];
            }

            if (
                (
                    !$existingLocaleIds ||
                    in_array($arrLang['id'], $existingLocaleIds)
                ) &&
                isset($arrFieldDefaultValues[$arrLang['id']])
            ) {
                $strFieldDefaultValue = $arrFieldDefaultValues[$arrLang['id']];
            } else {
                $strFieldDefaultValue = $arrFieldDefaultValues[$sourceLocaleId];
            }

            if (
                (
                    !$existingLocaleIds ||
                    in_array($arrLang['id'], $existingLocaleIds)
                ) &&
                isset($arrFieldInfos[$arrLang['id']])
            ) {
                $strFieldInfo = $arrFieldInfos[$arrLang['id']];
            } else {
                $strFieldInfo = $arrFieldInfos[$sourceLocaleId];
            }

            if ($arrLang['id'] == static::getOutputLocale()->getId()) {
                if (
                    // value of output locale has changed
                    $this->arrInputfields[$intFieldId]['name'][0] != $arrFieldNames[0]
                ) {
                    $strFieldName = $arrFieldNames[0];
                }

                if (
                    $this->arrInputfields[$intFieldId]['default_value'][0] != $arrFieldDefaultValues[0]
                ) {
                    $strFieldDefaultValue = $arrFieldDefaultValues[0];
                }

                if (
                    $this->arrInputfields[$intFieldId]['info'][0] != $arrFieldInfos[0]
                ) {
                    $strFieldInfo = $arrFieldInfos[0];
                }
            }

            $objSaveInputfieldName = $objDatabase->Execute('
                    INSERT INTO
                        ' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_inputfield_names
                    SET
                        `lang_id` = "' . contrexx_raw2db($arrLang['id']) . '",
                        `form_id` = "' . contrexx_raw2db($this->intFormId) . '",
                        `field_id` = "' . contrexx_raw2db($intFieldId) . '",
                        `field_name` = "' . contrexx_raw2db($strFieldName) . '",
                        `field_default_value` = "' . contrexx_raw2db($strFieldDefaultValue) . '",
                        `field_info` = "' . contrexx_addslashes(htmlentities($strFieldInfo, ENT_QUOTES, CONTREXX_CHARSET)) . '"
                ');

            if (!$objSaveInputfieldName) {
                return false;
            }
        }
        return true;
    }

    function saveInputfields($arrData)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields WHERE form='".$this->intFormId."'");
        $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfield_names WHERE form_id='".$this->intFormId."'");

        $inputfieldId = isset($arrData['inputfieldId']) ? $arrData['inputfieldId'] : array();

        foreach ($inputfieldId as $intFieldId) {
            $intFieldId = intval($intFieldId);
            $intFieldOrder = intval($arrData['inputfieldOrder'][$intFieldId]);
            $arrFieldNames = contrexx_input2raw($arrData['inputfieldName'][$intFieldId]);
            $intFieldType = intval($arrData['inputfieldType'][$intFieldId]);
            $intFieldShowIn = intval($arrData['inputfieldShow'][$intFieldId]);
            $arrFieldDefaultValues = contrexx_input2raw($arrData['inputfieldDefaultvalue'][$intFieldId]);
            $arrFieldInfos = isset($arrData['inputfieldInfo'][$intFieldId]) ? contrexx_input2raw($arrData['inputfieldInfo'][$intFieldId]) : array();
            $intFieldVerification = intval($arrData['inputfieldVerification'][$intFieldId]);
            $intFieldMustfield = isset($arrData['inputfieldMustfield'][$intFieldId]) ? contrexx_input2int($arrData['inputfieldMustfield'][$intFieldId]) : 0;
            $intFieldExpSearch = isset($arrData['inputfieldExpSearch'][$intFieldId]) ? contrexx_input2int($arrData['inputfieldExpSearch'][$intFieldId]) : 0;
            $fieldContextType = contrexx_input2db($arrData['inputfieldContext'][$intFieldId]);

            //add inputfield
            $objSaveInputfield = $objDatabase->Execute("
                INSERT INTO
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields
                SET
                    `id` = '".$intFieldId."',
                    `form` = '".$this->intFormId."',
                    `order` = '".$intFieldOrder."',
                    `type` = '".$intFieldType."',
                    `show_in` = '".$intFieldShowIn."',
                    `verification` = '".$intFieldVerification."',
                    `required` = '".$intFieldMustfield."',
                    `search` = '".$intFieldExpSearch."',
                    `context_type` = '".$fieldContextType."'

            ");

            if ($objSaveInputfield === false) {
                return false;
            }

            //add inputfield names and default values
            $saveInputFieldName = $this->updateInputFields($intFieldId, $arrFieldNames, $arrFieldDefaultValues, $arrFieldInfos);

            if (!$saveInputFieldName) {
                return false;
            }
        }

        $selectorOrder = $selectorOrder2 = $selectorExpSearch = $selectorExpSearch2 = 0;
        if (isset($arrData['selectorOrder'])) {
            $selectorOrder = isset($arrData['selectorOrder'][1]) ? $arrData['selectorOrder'][1] : 0;
            $selectorOrder2 = isset($arrData['selectorOrder'][2]) ? $arrData['selectorOrder'][2] : 0;
        }
        if (isset($arrData['selectorExpSearch'])) {
            $selectorExpSearch = isset($arrData['selectorExpSearch'][1]) ? $arrData['selectorExpSearch'][1] : 0;
            $selectorExpSearch2 = isset($arrData['selectorExpSearch'][2]) ? $arrData['selectorExpSearch'][2] : 0;
        }

        $objCategorySelector = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors SET `selector_order`='".  contrexx_input2int($selectorOrder)."', `exp_search`='".intval($selectorExpSearch)."' WHERE `selector_id`='9' AND `form_id`='".$this->intFormId."'");
        $objLevelSelector = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors SET `selector_order`='".  contrexx_input2int($selectorOrder2)."', `exp_search`='".intval($selectorExpSearch2)."' WHERE `selector_id`='10' AND `form_id`='".$this->intFormId."'");

        if ($objCategorySelector === false || $objLevelSelector === false) {
            return false;
        }

        return true;
    }



    function addInputfield()
    {
        global $objDatabase;

        $objOrderInputfield = $objDatabase->Execute("
            SELECT
                `id`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields
            WHERE
                `form` = '".$this->intFormId."'
        ");

        if($this->arrSettings['settingsShowLevels']) {
            $intOrder = $objOrderInputfield->RecordCount() + 2;
        } else {
            $intOrder = $objOrderInputfield->RecordCount() + 1;
        }
        //insert new field
        $objAddInputfield = $objDatabase->Execute("
            INSERT INTO
                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields
            SET
                `form` = '".$this->intFormId."',
                `order` = '".intval($intOrder)."',
                `type` = '1',
                `show_in` = '1',
                `verification` = '1',
                `search` = '0',
                `required` = '0'
        ");

        $intInsertId = $objDatabase->Insert_ID();
        //insert blank field name
        $objAddInputfieldName = $objDatabase->Execute("
            INSERT INTO
                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfield_names
            SET
                `lang_id` = '".intval(static::getOutputLocale()->getId())."',
                `form_id` = '".$this->intFormId."',
                `field_id` = '".intval($intInsertId)."',
                `field_name` = '',
                `field_default_value` = '',
                `field_info` = ''
        ");

        return $intInsertId;
    }



    function moveInputfield($intFieldId, $intDirectionId)
    {
        global $objDatabase;

        $bolChangeOrder = false;
        $intCountFields = count($this->arrInputfields)-1;
        $intOrder = intval($this->arrInputfields[$intFieldId]['order']);

        if($intDirectionId == 1) {
            if($intOrder > 0) {
                $intNewOrder = $intOrder-1;
                $intNeighborKey = $intNewOrder;
                $bolChangeOrder = true;
            }
        } else {
            if($intOrder < $intCountFields) {
                $intNewOrder = $intOrder+1;
                $intNeighborKey = $intNewOrder;
                $bolChangeOrder = true;
            }
        }

        if($bolChangeOrder) {
            usort($this->arrInputfields, array(__CLASS__, "sortInputfields"));

            $intNeighborId = $this->arrInputfields[$intNeighborKey]['id'];
            $intNeighborOrder = $intOrder;

            $arrElements = array(
                array(
                    'id'    => $intFieldId,
                    'order' => $intNewOrder,
                ),
                array(
                    'id'    => $intNeighborId,
                    'order' => $intNeighborOrder,
                )
            );

            foreach ($arrElements as $arrData) {
                if($arrData['id'] == 1) {
                    $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors SET `selector_order`='".intval($arrData['order'])."' WHERE `selector_id`='9' AND `form_id`='".$this->intFormId."'");
                } else if ($arrData['id'] == 2) {
                    $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors SET `selector_order`='".intval($arrData['order'])."' WHERE `selector_id`='10' AND `form_id`='".$this->intFormId."'");
                } else {
                    $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields SET `order`='".intval($arrData['order'])."' WHERE `id`='".intval($arrData['id'])."'");
                }
            }
        }
    }



    function deleteInputfield($intFieldId)
    {
        global $objDatabase;

        //delete field
        $objAddInputfield = $objDatabase->Execute("
            DELETE FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields
            WHERE
                `id` = '".intval($intFieldId)."'
        ");

        //delete field names
        $objAddInputfieldName = $objDatabase->Execute("
            DELETE FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfield_names
            WHERE
                `field_id` = '".intval($intFieldId)."'
        ");

        //remove from array
        unset($this->arrInputfields[$intFieldId]);

        //refresh order
        $this->refreshOrder();
    }



    function refreshOrder()
    {
        global $objDatabase;

        foreach($this->arrInputfields as $fieldId => $arrData) {
            $arrOrder[$fieldId] = $arrData['order'];
        }

        asort($arrOrder);

        $i=0;
        foreach ($arrOrder as $fieldId => $oldOrder) {
            if($fieldId == 1) {
                $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors SET `selector_order`='".intval($i)."' WHERE `selector_id`='9' AND `form_id`='".$this->intFormId."'");
            } else if ($fieldId == 2) {
                $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors SET `selector_order`='".intval($i)."' WHERE `selector_id`='10' AND `form_id`='".$this->intFormId."'");
            } else {
                $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields SET `order`='".intval($i)."' WHERE `id`='".intval($fieldId)."'");
            }
            $i++;
        }

    }



    /**
     * Refresh the Input fields
     *
     * @param \Cx\Core\Html\Sigma $objTpl Template object
     * @return string Parsed Template content
     */
    function refreshInputfields($objTpl)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_settings_inputfields.html',true,true);

        $objForms = new MediaDirectoryForm($this->intFormId, $this->moduleName);

        usort($this->arrInputfields, array(__CLASS__, "sortInputfields"));

        $arrShow = array(
            1 => $_ARRAYLANG['TXT_MEDIADIR_SHOW_BACK_N_FRONTEND'],
            2 => $_ARRAYLANG['TXT_MEDIADIR_SHOW_FRONTEND'],
            3 => $_ARRAYLANG['TXT_MEDIADIR_SHOW_BACKEND'],
        );

        $i = 0;
        $intLastId = 0;
        foreach ($this->arrInputfields as $arrInputfield) {
            $strMustfield = $arrInputfield['required']==1 ? 'checked="checked"' : '';
            $strExpSearch = $arrInputfield['search']==1 ? 'checked="checked"' : '';

            if($arrInputfield['id'] > $intLastId) {
                $intLastId = $arrInputfield['id'];
            }

            $objTpl->setGlobalVariable(array(
                $this->moduleLangVar.'_SETTINGS_INPUTFIELD_ROW_CLASS' => $i%2==0 ? 'row1' : 'row2',
                $this->moduleLangVar.'_SETTINGS_INPUTFIELD_LASTID' => $intLastId,
            ));

            if($arrInputfield['id'] != 1 && $arrInputfield['id'] != 2) {
                $objTpl->setGlobalVariable(array(
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_ID' => $arrInputfield['id'],
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_FORM_ID' => $this->intFormId,
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_ORDER' => $arrInputfield['order'],
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_TYPE' => $this->buildDropdownmenu($this->getInputfieldTypes(), $arrInputfield['type']),
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_VERIFICATION' => $this->buildDropdownmenu($this->getInputfieldVerifications(), $arrInputfield['verification']),
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_SHOW' => $this->buildDropdownmenu($arrShow, $arrInputfield['show_in']),
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_CONTEXT' => $this->buildDropdownmenu($this->getInputContexts(), $arrInputfield['context_type']),
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_MUSTFIELD' => $strMustfield,
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_EXP_SEARCH' => $strExpSearch,
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_NAME_MASTER' => $arrInputfield['name'][0],
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_INFO_MASTER' => $arrInputfield['info'][0],
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_DEFAULTVALUE_MASTER' => $arrInputfield['default_value'][0],
                ));

                //fieldname
                foreach ($this->arrFrontendLanguages as $arrLang) {
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_INPUTFIELD_NAME_LANG_ID' => $arrLang['id'],
                        $this->moduleLangVar.'_INPUTFIELD_NAME_LANG_SHORTCUT' => $arrLang['lang'],
                        $this->moduleLangVar.'_INPUTFIELD_NAME_LANG_NAME' => $arrLang['name'],
                        $this->moduleLangVar.'_SETTINGS_INPUTFIELD_NAME' => isset($arrInputfield['name'][$arrLang['id']]) ? $arrInputfield['name'][$arrLang['id']] : '',
                    ));
                    $objTpl->parse($this->moduleNameLC.'InputfieldNameList');
                }

                //default values
                foreach ($this->arrFrontendLanguages as $arrLang) {
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_INPUTFIELD_DEFAULTVALUE_LANG_ID' => $arrLang['id'],
                        $this->moduleLangVar.'_INPUTFIELD_DEFAULTVALUE_LANG_SHORTCUT' => $arrLang['lang'],
                        $this->moduleLangVar.'_INPUTFIELD_DEFAULTVALUE_LANG_NAME' => $arrLang['name'],
                        $this->moduleLangVar.'_SETTINGS_INPUTFIELD_DEFAULTVALUE' => isset($arrInputfield['default_value'][$arrLang['id']]) ? $arrInputfield['default_value'][$arrLang['id']] : '',
                    ));
                    $objTpl->parse($this->moduleNameLC.'InputfieldDefaultvalueList');
                }



                //infotext
                foreach ($this->arrFrontendLanguages as $arrLang) {
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_INPUTFIELD_INFO_LANG_ID' => $arrLang['id'],
                        $this->moduleLangVar.'_INPUTFIELD_INFO_LANG_SHORTCUT' => $arrLang['lang'],
                        $this->moduleLangVar.'_INPUTFIELD_INFO_LANG_NAME' => $arrLang['name'],
                        $this->moduleLangVar.'_SETTINGS_INPUTFIELD_INFO' => isset($arrInputfield['info'][$arrLang['id']]) ? $arrInputfield['info'][$arrLang['id']] : '',
                    ));
                    $objTpl->parse($this->moduleNameLC.'InputfieldInfoList');
                }

                //language names
                foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                    if(($key+1) == count($this->arrFrontendLanguages)) {
                        $minimize = "<a id=\"inputfieldMinimize_".$arrInputfield['id']."\" href=\"javascript:ExpandMinimizeInputfields('inputfieldName', '".$arrInputfield['id']."'); ExpandMinimizeInputfields('inputfieldDefaultvalue', '".$arrInputfield['id']."'); ExpandMinimizeInputfields('inputfieldLanguages', '".$arrInputfield['id']."'); ExpandMinimizeInputfields('inputfieldInfo', '".$arrInputfield['id']."');\">&laquo;&nbsp;".$_ARRAYLANG['TXT_MEDIADIR_MINIMIZE']."</a>";
                    } else {
                        $minimize = "";
                    }

                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_INPUTFIELD_LANG_NAME' => $arrLang['name'],
                        $this->moduleLangVar.'_INPUTFIELD_MINIMIZE' => $minimize,
                    ));
                    $objTpl->parse($this->moduleNameLC.'InputfieldLanguagesList');
                }
                if ($arrInputfield['exp_search'] == 0) {
                    $objTpl->hideBlock($this->moduleNameLC . 'InputfieldAdvancedSearch');
                } else {
                    $objTpl->touchBlock($this->moduleNameLC . 'InputfieldAdvancedSearch');
                }
                $objTpl->parse($this->moduleNameLC.'Inputfield');
            } else {
                if(($arrInputfield['id'] == 2 && $objForms->arrForms[$this->intFormId]['formUseLevel']) || ($arrInputfield['id'] == 1 && $objForms->arrForms[$this->intFormId]['formUseCategory'])) {

                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_SETTINGS_SELECTOR_ID' => $arrInputfield['id'],
                        $this->moduleLangVar.'_SETTINGS_SELECTOR_NAME' => $arrInputfield['name'][0],
                        $this->moduleLangVar.'_SETTINGS_SELECTOR_ORDER' => $arrInputfield['order'],
                        $this->moduleLangVar.'_SETTINGS_SELECTOR_EXP_SEARCH' => $strExpSearch,
                    ));

                    $objTpl->parse($this->moduleNameLC.'Selector');
                }
            }

            $i++;
            $objTpl->parse($this->moduleNameLC.'InputfieldList');
        }

        return $objTpl->get();
    }



    function getInputfieldTypes()
    {
        global $_ARRAYLANG, $objDatabase;

        $objInputfieldTypes = $objDatabase->Execute("
            SELECT
                `id`,
                `name`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfield_types
            WHERE
                `active` = '1'
            ORDER BY
                `id` ASC
        ");

        $arrInputfieldTypes = array();
        if ($objInputfieldTypes !== false) {
            while (!$objInputfieldTypes->EOF) {

                $arrInputfieldTypes[$objInputfieldTypes->fields['id']] = $_ARRAYLANG['TXT_MEDIADIR_INPUTFIELD_TYPE_'.strtoupper(htmlspecialchars($objInputfieldTypes->fields['name'], ENT_QUOTES, CONTREXX_CHARSET))];

                $objInputfieldTypes->MoveNext();
            }
        }

        return $arrInputfieldTypes;
    }



    function makeJavascriptInputfieldArray($intId, $strName, $intRequired, $strVerification, $strType)
    {
        $strVerification = addslashes($strVerification);
        $this->strJavascriptInputfieldArray .= <<<EOF

inputFields[$intId] = Array(
    '$strName',
    $intRequired,
    '$strVerification',
    '$strType');
EOF;
    }



    function getInputfieldJavascript()
    {
        $strInputfieldErrorMessage = $this->moduleNameLC."ErrorMessage";

        $strstrInputfieldJavascript = <<<EOF

var inputId, isImageField, uploaderInputBox;
function getUploader(e) { // e => jQuery element
    inputId = e.data('inputId');
    isImageField = e.data('isImage');
    uploaderInputBox = \$J('#' + inputId);
    \$J('#mediadirImageUploader').trigger('click');
}
function mediadirUploaderCallback(data) {
    if (typeof data[0] !== 'undefined') {
        var data       = data[0].split('/'),
            fileName   = data.pop();

        uploaderInputBox.val(fileName);
        uploaderInputBox.trigger('keyup');
    }
}
function selectAddStep(stepName){
    if(document.getElementById(stepName).style.display != "block")
    {
        document.getElementById(stepName).style.display = "block";
        strClass = document.getElementById(stepName).className;
        document.getElementById(strClass+"_"+stepName).className = "active";

        arrTags = document.getElementsByTagName("*");
        for (i=0;i<arrTags.length;i++)
            {
                if(arrTags[i].className == strClass && arrTags[i] != document.getElementById(stepName))
                {
                    arrTags[i].style.display = "none";
                    if (document.getElementById(strClass+"_"+arrTags[i].getAttribute("id"))) {
                        document.getElementById(strClass+"_"+arrTags[i].getAttribute("id")).className = "";
                    }
                }
            }
    }
}


inputFields = new Array();
$this->strJavascriptInputfieldArray

function checkAllFields() {
    var isOk = true;

    if (document.getElementById('{$this->moduleNameLC}Inputfield_ReadyToConfirm') != null && !document.getElementById('{$this->moduleNameLC}Inputfield_ReadyToConfirm').checked) {
        return true;
    }

    for (var field in inputFields) {
        var type = inputFields[field][3];

        switch (type){
            case 'selector':
                name =  inputFields[field][0];
                value = document.getElementById(name).value;
                if (value == "") {
                    isOk = false;
                    document.getElementById(name).style.border = "#ff0000 1px solid";
                } else {
                    document.getElementById(name).style.borderColor = '';
                }
                break;
EOF;

        foreach($this->strJavascriptInputfieldCheck as $strType => $strCase) {
             $strstrInputfieldJavascript .= <<<EOF
             $strCase
EOF;
        }

        $strstrInputfieldJavascript .= <<<EOF
        }
    }

    if (!isOk) {
        document.getElementById('$strInputfieldErrorMessage').style.display = "block";
    }

    return isOk;
}

function isRequiredGlobal(required, value) {
    if (required == 1) {
        if (value == "") {
            return true;
        }
    }

    return false;
}

function matchType(pattern, value) {
    var reg = new RegExp(pattern);
    if (value.match(reg)) {
        return true;
    }
    return false;
}

EOF;

        return $strstrInputfieldJavascript;
    }



    function getInputfieldVerifications()
    {
        global $_ARRAYLANG, $objDatabase;

        $objInputfieldVerifications = $objDatabase->Execute("
            SELECT
                `id`,
                `name`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfield_verifications
            ORDER BY
                `id` ASC
        ");

        $arrInputfieldVerifications = array();
        if ($objInputfieldVerifications !== false) {
            while (!$objInputfieldVerifications->EOF) {

                $arrInputfieldVerifications[$objInputfieldVerifications->fields['id']] = $_ARRAYLANG['TXT_MEDIADIR_INPUTFIELD_VERIFICATION_'.strtoupper(htmlspecialchars($objInputfieldVerifications->fields['name'], ENT_QUOTES, CONTREXX_CHARSET))];

                $objInputfieldVerifications->MoveNext();
            }
        }

        return $arrInputfieldVerifications;
    }

    /**
     * Returns available context options
     *
     * @return array available contexts
     */
    public static function getInputContexts()
    {
        global $_ARRAYLANG;

        $arrContexts = array(
          'none'     => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_NONE"],
          'title'    => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_TITLE"],
          'content'  => $_ARRAYLANG['TXT_MEDIADIR_INPUTFIELD_CONTEXT_CONTENT'],
          'address'  => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_ADDRESS"],
          'zip'      => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_ZIP"],
          'city'     => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_CITY"],
          'country'  => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_COUNTRY"],
          'image'    => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_IMAGE"],
          'keywords' => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_KEYWORDS"],
          'slug'    => $_ARRAYLANG["TXT_MEDIADIR_INPUTFIELD_CONTEXT_SLUG"],
        );

        return $arrContexts;
    }

    function listPlaceholders($objTpl)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $objTemplate;

        foreach ($this->arrInputfields as $arrInputfield) {
            if($arrInputfield['id'] != 1 && $arrInputfield['id'] != 2 && $arrInputfield['type'] != 16 && $arrInputfield['type'] != 18) {
                $strType = $arrInputfield['type_name'];
                $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield".ucfirst($strType);

                try {
                    $objInputfield = safeNew($strInputfieldClass, $this->moduleName);
                } catch (Exception $e) {
                    echo "Error: ".$e->getMessage();
                }

                $objTpl->setGlobalVariable(array(
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_ID' => $arrInputfield['id'],
                ));

                $arrPlaceholders = $objInputfield->arrPlaceholders;
                $strPlaceholders = null;

                foreach ($arrPlaceholders as $strPlaceholder) {
                    $strPlaceholders .= '<li>[['.strtoupper($strPlaceholder).']]</li>';
                }


                $strBlockDescription = str_replace('%i', $arrInputfield['name'][0], $_ARRAYLANG['TXT_MEDIADIR_SETTINGS_PLACEHOLDER_DESCRIPTION']);

                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_DESCRIPTION' => $strBlockDescription,
                    $this->moduleLangVar.'_SETTINGS_INPUTFIELD_PLACEHOLDERS' => $strPlaceholders
                ));
                $objTpl->parse($this->moduleNameLC.'InputfieldPlaceholderList');
            }
        }
    }



    function checkFieldTypeIsExpSeach($intType)
    {
        global $objDatabase;

        $objResultTypeCheck = $objDatabase->Execute("SELECT exp_search FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfield_types WHERE id='".intval($intType)."' LIMIT 1");

        if ($objResultTypeCheck) {
            if($objResultTypeCheck->fields['exp_search'] == 1) {
                $status = true;
            } else {
                $status = false;
            }
        } else {
            $status = false;
        }

        return $status;
    }
}

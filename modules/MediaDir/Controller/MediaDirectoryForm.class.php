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
 * Media  Directory Form Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Form Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryForm extends MediaDirectoryLibrary
{
    public $intFormId;

    public $arrForms = array();

    /**
     * Constructor
     */
    function __construct($intFormId=null, $name)
    {
        $this->intFormId = intval($intFormId);

        parent::__construct('.', $name);
        parent::getSettings();
        parent::getFrontendLanguages();
        $this->arrForms = self::getForms($this->intFormId);
    }

    function getForms($intFormId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $_LANGID, $objInit;

        $arrForms = array();

        if(!empty($intFormId)) {
            $whereFormId = "form.id='".$intFormId."' AND";
        } else {
            $whereFormId = null;
        }

        $objFormsRS = $objDatabase->Execute("
            SELECT
                form.`id` AS `id`,
                form.`order` AS `order`,
                form.`picture` AS `picture`,
                form.`cmd` AS `cmd`,
                form.`use_category` AS `use_category`,
                form.`use_level` AS `use_level`,
                form.`use_ready_to_confirm` AS `use_ready_to_confirm`,
                form.`entries_per_page` AS `entries_per_page`,
                form.`active` AS `active`,
                form_names.`form_name` AS `name`,
                form_names.`form_description` AS `description`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_forms AS form,
                ".DBPREFIX."module_".$this->moduleTablePrefix."_form_names AS form_names
            WHERE
                ($whereFormId form_names.form_id=form.id)
            AND
                (form_names.lang_id='".$_LANGID."')
            ORDER BY
                `order` ASC
            ");

        if ($objFormsRS !== false) {
            while (!$objFormsRS->EOF) {

                $arrForm = array();
                $arrFormName = array();
                $arrFormDesc = array();

                //get lang attributes
                $arrFormName[0] = $objFormsRS->fields['name'];
                $arrFormDesc[0] = $objFormsRS->fields['description'];

                $objFormAttributesRS = $objDatabase->Execute("
                    SELECT
                        `lang_id` AS `lang_id`,
                        `form_name` AS `name`,
                        `form_description` AS `description`
                    FROM
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_form_names
                    WHERE
                        form_id=".$objFormsRS->fields['id']."
                ");

                if ($objFormAttributesRS !== false) {
                    while (!$objFormAttributesRS->EOF) {
                        $arrFormName[$objFormAttributesRS->fields['lang_id']] = htmlspecialchars($objFormAttributesRS->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
                        $arrFormDesc[$objFormAttributesRS->fields['lang_id']] = htmlspecialchars($objFormAttributesRS->fields['description'], ENT_QUOTES, CONTREXX_CHARSET);

                        $objFormAttributesRS->MoveNext();
                    }
                }

                $arrForm['formId']                = intval($objFormsRS->fields['id']);
                $arrForm['formOrder']             = intval($objFormsRS->fields['order']);
                $arrForm['formPicture']           = htmlspecialchars($objFormsRS->fields['picture'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrForm['formName']              = $arrFormName;
                $arrForm['formDescription']       = $arrFormDesc;
                $arrForm['formActive']            = intval($objFormsRS->fields['active']);
                $arrForm['formCmd']               = htmlspecialchars($objFormsRS->fields['cmd'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrForm['formUseCategory']       = intval($objFormsRS->fields['use_category']);
                $arrForm['formUseLevel']          = intval($objFormsRS->fields['use_level']);
                $arrForm['formUseReadyToConfirm'] = intval($objFormsRS->fields['use_ready_to_confirm']);
                $arrForm['formEntriesPerPage']    = $objFormsRS->fields['entries_per_page'];

                $arrForms[$objFormsRS->fields['id']] = $arrForm;
                $objFormsRS->MoveNext();
            }
        }

        return $arrForms;
    }



    function listForms($objTpl, $intView, $intFormId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $_LANGID;

        $i = 0;

        switch ($intView) {
            case 1:
                //settings overview
                if(!empty($this->arrForms)){
                    foreach ($this->arrForms as $key => $arrForm) {
                        //get status
                        if($arrForm['formActive'] == 1) {
                            $strStatus = '../core/Core/View/Media/icons/status_green.gif';
                            $intStatus = 0;
                        } else {
                            $strStatus = '../core/Core/View/Media/icons/status_red.gif';
                            $intStatus = 1;
                        }

                        //parse data variables
                        $objTpl->setVariable(array(
                            $this->moduleLangVar.'_FORM_ROW_CLASS' => $i%2==0 ? 'row1' : 'row2',
                            $this->moduleLangVar.'_FORM_ID' => $arrForm['formId'],
                            $this->moduleLangVar.'_FORM_TITLE' => contrexx_raw2xhtml($arrForm['formName'][0]),
                            $this->moduleLangVar.'_FORM_DESCRIPTION' => contrexx_raw2xhtml($arrForm['formDescription'][0]),
                            $this->moduleLangVar.'_FORM_ORDER' => $arrForm['formOrder'],
                            $this->moduleLangVar.'_FORM_STATUS' => $strStatus,
                            $this->moduleLangVar.'_FORM_SWITCH_STATUS' => $intStatus,
                        ));

                        $i++;
                        $objTpl->parse($this->moduleNameLC.'FormTemplateList');
                        $objTpl->hideBlock($this->moduleNameLC.'FormTemplateNoEntries');
                        $objTpl->clearVariables();
                    }
                } else {
                    $objTpl->setGlobalVariable(array(
                        'TXT_'.$this->moduleLangVar.'_NO_ENTRIES_FOUND' => $_ARRAYLANG['TXT_MEDIADIR_NO_ENTRIES_FOUND']
                    ));

                    $objTpl->touchBlock($this->moduleNameLC.'FormTemplateNoEntries');
                    $objTpl->clearVariables();
                }
                break;
            case 2:
                //form selector backend (add entry view)
                $arrDropdownOptions[0] = $_ARRAYLANG['TXT_MEDIADIR_CHOOSE'];

                foreach ($this->arrForms as $key => $arrForm) {
                    if($arrForm['formActive'] == 1) {
                        $arrDropdownOptions[$arrForm['formId']] = $arrForm['formName'][0];
                    }
                }

                $strDropdown = $this->buildDropdownmenu($arrDropdownOptions, null);

                //parse data variables
                $objTpl->setVariable(array(
                    'TXT_'.$this->moduleLangVar.'_CHOOSE_FORM' => $_ARRAYLANG['TXT_MEDIADIR_CHOOSE_FORM'],
                    $this->moduleLangVar.'_FORM_LIST' => '<select onchange="document.entryModfyForm.submit();" name="selectedFormId" style="width: 302px">'.$strDropdown."</select>",
                ));

                $objTpl->parse($this->moduleNameLC.'FormList');
                $objTpl->clearVariables();

                break;
            case 3:
                //form selector frontend (add entry view)

                foreach ($this->arrForms as $key => $arrForm) {
                    if($arrForm['formActive'] == 1) {

                        $hasPicture = $arrForm['formPicture'] != '';
                        $thumbImage = $this->getThumbImage($arrForm['formPicture']);
                        //parse data variables
                        $objTpl->setVariable(array(
                            $this->moduleLangVar.'_FORM_ROW_CLASS' => $i%2==0 ? 'row1' : 'row2',
                            'TXT_'.$this->moduleLangVar.'_FORM_ID' => $arrForm['formId'],
                            'TXT_'.$this->moduleLangVar.'_FORM_TITLE' => contrexx_raw2xhtml($arrForm['formName'][0]),
                            'TXT_'.$this->moduleLangVar.'_FORM_DESCRIPTION' => nl2br(contrexx_raw2xhtml($arrForm['formDescription'][0])),
                            'TXT_'.$this->moduleLangVar.'_FORM_IMAGE' => $hasPicture ? '<img src="'.$arrForm['formPicture'].'" alt="'.contrexx_raw2xhtml($arrForm['formName'][0]).'" />' : '',
                            'TXT_'.$this->moduleLangVar.'_FORM_IMAGE_SRC' => $arrForm['formPicture'],
                            'TXT_'.$this->moduleLangVar.'_FORM_IMAGE_SRC_THUMB' => $thumbImage,
                            'TXT_'.$this->moduleLangVar.'_FORM_IMAGE_THUMB' => $hasPicture ?'<img src="'. $thumbImage .'" alt="'.contrexx_raw2xhtml($arrForm['formName'][0]).'" />' : '',
                        ));

                        $i++;
                        $objTpl->parse($this->moduleNameLC.'FormList');
                        $objTpl->clearVariables();
                    }
                }

                $objTpl->parse($this->moduleNameLC.'Forms');
                break;

            case 4:
                //Dropdown Menu
                $strDropdownOptions = '';
                foreach ($this->arrForms  as $key => $arrForm) {
                    if($arrForm['formActive'] == 1) {
                        if($arrForm['formId'] == $intFormId) {
                            $strSelected = 'selected="selected"';
                        } else {
                            $strSelected = '';
                        }

                        $strDropdownOptions .= '<option value="'.$arrForm['formId'].'" '.$strSelected.' >'.contrexx_raw2xhtml($arrForm['formName'][0]).'</option>';
                    }
                }

                return $strDropdownOptions;
                break;
        }
    }

    /**
     * Update the form values
     *
     * @param array   $arrName        Form names array, The array key is refered as the language id
     * @param array   $arrDescription Form description array, The array key is refered as the language id
     * @param integer $intFormId      Form id
     *
     * @return boolean true | false
     */
    public function updateFormLocale($arrName, $arrDescription, $intFormId)
    {
        global $objDatabase, $_LANGID;

        if (empty($intFormId)) {
            return false;
        }

        $objDefaultLang = $objDatabase->Execute('
            SELECT
                `form_name` AS `name`,
                `form_description` AS `description`
            FROM
                '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_form_names
            WHERE
                lang_id='.$_LANGID.'
                AND `form_id` = "'.$intFormId.'"
            LIMIT
                1
        ');

        $strOldDefaultName        = '';
        $strOldDefaultDescription = '';

        if ($objDefaultLang !== false) {
            $strOldDefaultName        = $objDefaultLang->fields['name'];
            $strOldDefaultDescription = $objDefaultLang->fields['description'];
        }

        foreach ($this->arrFrontendLanguages as $lang) {
            $activeLang[] = $lang['id'];
        }
        // Before updating the form names Remove the corresponding existing form names from db.
        $objDatabase->Execute('DELETE FROM ' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_form_names WHERE form_id="' . $intFormId . '" AND lang_id IN("'.  implode('","', $activeLang).'")');

        foreach ($this->arrFrontendLanguages as $arrLang) {
            $strName        = $arrName[$arrLang['id']];
            $strDescription = $arrDescription[$arrLang['id']];

            if ($arrLang['id'] == $_LANGID) {
                if ($arrName[0] != $strOldDefaultName) {
                    $strName = $arrName[0];
                }
                if ($arrName[$arrLang['id']] != $strOldDefaultName) {
                    $strName = $arrName[$arrLang['id']];
                }
                if ($arrDescription[0] != $strOldDefaultDescription) {
                    $strDescription = $arrDescription[0];
                }
                if ($arrDescription[$arrLang['id']] != $strOldDefaultDescription) {
                    $strDescription = $arrDescription[$arrLang['id']];
                }
            }

            if (empty($strName)) {
                $strName = $arrName[0];
            }
            if (empty($strDescription)) {
                $strDescription = $arrDescription[0];
            }
            $objInsertNames = $objDatabase->Execute('
                        INSERT INTO
                            ' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_form_names
                        SET
                            `lang_id`="' . intval($arrLang['id']) . '",
                            `form_id`="' . intval($intFormId) . '",
                            `form_name`="' . contrexx_input2db($strName) . '",
                            `form_description`="' . contrexx_input2db($strDescription) . '"
                    ');
        }

        return $objInsertNames;
    }

    function saveForm($arrData, $intFormId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $_LANGID;

        $intId = intval($intFormId);
        $strPicture = contrexx_addslashes(contrexx_strip_tags($arrData['formImage']));

        $arrName = $arrData['formName'];
        $arrDescription = $arrData['formDescription'];
        $strCmd = strtolower(contrexx_addslashes(contrexx_strip_tags($arrData['formCmd'])));
        $intUseCategory = intval($arrData['formUseCategory']);
        $intUseLevel = isset($arrData['formUseLevel']) ? contrexx_input2int($arrData['formUseLevel']) : 0;
        $intUseReadyToConfirm = isset($arrData['formUseReadyToConfirm']) ? contrexx_input2int($arrData['formUseReadyToConfirm']) : 0;
        $intEntriesPerPage = isset($arrData['formEntriesPerPage']) ? contrexx_input2int($arrData['formEntriesPerPage']) : 0;

        if(empty($intId)) {
            //insert new form
            $objInsertAttributes = $objDatabase->Execute("
                INSERT INTO
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_forms
                SET
                    `order`='99',
                    `picture`='".$strPicture."',
                    `cmd`='".$strCmd."',
                    `use_category`='".$intUseCategory."',
                    `use_level`='".$intUseLevel."',
                    `use_ready_to_confirm`='".$intUseReadyToConfirm."',
                    `entries_per_page`='".$intEntriesPerPage."',
                    `active`='0'
            ");

            if($objInsertAttributes !== false) {
                $intId = $objDatabase->Insert_ID();

                foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                    if(empty($arrName[0])) $arrName[0] = "";

                    $strName = $arrName[$arrLang['id']];
                    $strDescription = $arrDescription[$arrLang['id']];

                    if(empty($strName)) $strName = $arrName[0];
                    if(empty($strDescription)) $strDescription = $arrDescription[0];

                    $objInsertNames = $objDatabase->Execute("
                        INSERT INTO
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_form_names
                        SET
                            `lang_id`='".intval($arrLang['id'])."',
                            `form_id`='".intval($intId)."',
                            `form_name`='".contrexx_raw2db(contrexx_input2raw($strName))."',
                            `form_description`='".contrexx_raw2db(contrexx_input2raw($strDescription))."'
                    ");
                }



                $objCreateCatSelectors = $objDatabase->Execute("
                    INSERT INTO
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors
                    SET
                        `selector_id`='9',
                        `form_id`='".intval($intId)."',
                        `selector_order`='0',
                        `exp_search`='1'
                ");

                $objCreateLevelSelectors = $objDatabase->Execute("
                    INSERT INTO
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors
                    SET
                        `selector_id`='10',
                        `form_id`='".intval($intId)."',
                        `selector_order`='1',
                        `exp_search`='1'
                ");

                //permissions
                parent::getCommunityGroups();
                foreach ($this->arrCommunityGroups as $intGroupId => $arrGroup) {
                    $objInsertPerm = $objDatabase->Execute("
                        INSERT INTO
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_settings_perm_group_forms
                        SET
                            `group_id`='".intval($intGroupId)."',
                            `form_id`='".intval($intId)."',
                            `status_group`='1'
                    ");
                }

                if($objInsertNames !== false && $objCreateCatSelectors !== false && $objCreateLevelSelectors !== false) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }

        } else {
            //update form
            $objUpdateAttributes = $objDatabase->Execute("
                UPDATE
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_forms
                SET
                    `picture`='".$strPicture."',
                    `cmd`='".$strCmd."',
                    `use_category`='".$intUseCategory."',
                    `use_level`='".$intUseLevel."',
                    `use_ready_to_confirm`='".$intUseReadyToConfirm."',
                    `entries_per_page`='".$intEntriesPerPage."'
                WHERE
                    `id`='".$intId."'
            ");

            if($objUpdateAttributes !== false) {

                //permissions
                $objDeletePerm = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_settings_perm_group_forms WHERE form_id='".$intId."'");
                $settingsPermissionGroupForm = isset($arrData['settingsPermGroupForm'][$intId]) ? $arrData['settingsPermGroupForm'][$intId] : array();

                foreach ($settingsPermissionGroupForm as $intGroupId => $intGroupStatus) {
                    $objInsertPerm = $objDatabase->Execute("
                        INSERT INTO
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_settings_perm_group_forms
                        SET
                            `group_id`='".intval($intGroupId)."',
                            `form_id`='".intval($intId)."',
                            `status_group`='".intval($intGroupStatus)."'
                    ");
                }

                $objInsertNames = $this->updateFormLocale($arrName, $arrDescription, $intId);

                if($objInsertNames !== false) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

    }



    function deleteForm($intFormId)
    {
        global $objDatabase;
        $arrEntryIds = array();

        //delete entries
        $objRSEntriesDelete = $objDatabase->Execute("SELECT
                                                        id
                                                     FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
                                                     WHERE
                                                        `form_id`='".intval($intFormId)."'
                                                    ");
        if ($objRSEntriesDelete !== false) {
            while (!$objRSEntriesDelete->EOF) {
                $arrEntryIds[] = $objRSEntriesDelete->fields['id'];
                $objRSEntriesDelete->MoveNext();
            }

            foreach ($arrEntryIds as $key => $intEntryId) {
                //delete rel levels
                $objRSEntryDeleteRelLevels = $objDatabase->Execute("DELETE FROM
                                                                         ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels
                                                                    WHERE
                                                                         `entry_id`='".intval($intEntryId)."'
                                                                    ");

                //delete rel categories
                $objRSEntryDeleteRelCategories = $objDatabase->Execute("DELETE FROM
                                                                         ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories
                                                                    WHERE
                                                                         `entry_id`='".intval($intEntryId)."'
                                                                    ");

                //delete rel inputfields
                $objRSEntryDeleteRelInputfields = $objDatabase->Execute("DELETE FROM
                                                                         ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                                                                    WHERE
                                                                         `entry_id`='".intval($intEntryId)."'
                                                                    ");

                if ($objRSEntryDeleteRelLevels !== false && $objRSEntryDeleteRelCategories !== false && $objRSEntryDeleteRelInputfields !== false) {
                    //delete entries
                    $objRSEntryDeleteRelInputfields = $objDatabase->Execute("DELETE FROM
                                                                                 ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
                                                                             WHERE
                                                                                 `form_id`='".intval($intFormId)."'
                                                                             ");
                    if ($objRSEntryDeleteRelInputfields === false) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }

        //delete selector order
        $objRSEntriesDelete = $objDatabase->Execute("DELETE FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_order_rel_forms_selectors
                                                     WHERE
                                                        `form_id`='".intval($intFormId)."'
                                                    ");

        //delete inputfields
        $objRSEntriesDelete = $objDatabase->Execute("DELETE FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields
                                                     WHERE
                                                        `form`='".intval($intFormId)."'
                                                    ");

        //delete inputfields names
        $objRSEntriesDelete = $objDatabase->Execute("DELETE FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfield_names
                                                     WHERE
                                                        `form_id`='".intval($intFormId)."'
                                                    ");

        //delete permissions
        $objRSEntriesDelete = $objDatabase->Execute("DELETE FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_settings_perm_group_forms
                                                     WHERE
                                                        `form_id`='".intval($intFormId)."'
                                                    ");

        //delete forms
        $objRSEntriesDelete = $objDatabase->Execute("DELETE FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_forms
                                                     WHERE
                                                        `id`='".intval($intFormId)."'
                                                    ");

        return true;
    }



    function saveOrder($arrData) {
        global $objDatabase;

        foreach($arrData['formsOrder'] as $intFormId => $intFormOrder) {
            $objRSFormOrder = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_forms SET `order`='".intval($intFormOrder)."' WHERE `id`='".intval($intFormId)."'");

            if ($objRSFormOrder === false) {
                return false;
            }
        }

        return true;
    }
}
?>

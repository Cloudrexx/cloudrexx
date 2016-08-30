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
 * Media  Directory Library
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media directory access id constants.
 * This class is used as fake enum.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryAccessIDs {
    const MediaDirectory = 153; //use media directory
    const AddEntry = 154;
    const ModifyEntry = 155; //modify / delete entry
    const ManageLevels = 156; //add, modify / delete levels
    const ManageCategories = 157; //add, modify / delete categories
    const Interfaces = 158; //use the interfaces
    const Settings = 159; //change module settings
}

/**
 * Media Directory Library
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryLibrary
{
    public $_objTpl;
    public $pageContent;

    public $arrFrontendLanguages = array();
    public $arrSettings = array();
    public $arrCommunityGroups = array();

    public $strJavascript;



    public $moduleName             = '';

    public $moduleNameLC             = '';

    public $moduleTablePrefix = "mediadir";
    public $moduleLangVar = "MEDIADIR";
    public $moduleConstVar = "MEDIADIR";

    /**
     * Constructor
     */
    function __construct($tplPath, $name)
    {
        $this->_objTpl = new \Cx\Core\Html\Sigma($tplPath);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->moduleName    = $name;
        $this->moduleNameLC  = strtolower($this->moduleName);

        $this->_objTpl->setGlobalVariable(array(
            'MODULE_NAME' =>  $this->moduleName,
            'MODULE_NAME_LC' => $this->moduleNameLC,
            'CSRF' =>  'csrf='.\Cx\Core\Csrf\Controller\Csrf::code(),
        ));
    }

    function checkDisplayduration()
    {
        self::getSettings();

        if($this->arrSettings['settingsEntryDisplaydurationNotification'] >= 1) {

            $objEntries = new MediaDirectoryEntry($this->moduleName);
            $objEntries->getEntries(null, null, null, null, null, null, true);

            $intDaysbefore = intval($this->arrSettings['settingsEntryDisplaydurationNotification']);
            $intToday = mktime();

            foreach ($objEntries->arrEntries as $intEntryId => $arrEntry) {
                $intWindowEnd =  $arrEntry['entryDurationEnd'];
                $intWindowEndDay =  date("d", $intWindowEnd);
                $intWindowEndMonth =  date("m", $intWindowEnd);
                $intWindowEndYear =  date("Y", $intWindowEnd);

                $intWindowStartDay = $intWindowEndDay-$intDaysbefore;
                $intWindowStart = mktime(0,0,0,$intWindowEndMonth,$intWindowStartDay,$intWindowEndYear);

                if(($intWindowStart <= $intToday && $intToday <= $intWindowEnd) && $arrEntry['entryDurationNotification'] == 0) {
                    $objMail = new MediaDirectoryMail(9, $intEntryId, $this->moduleName);
                    $objEntries->setDisplaydurationNotificationStatus($intEntryId, 1);
                }
            }
        }
    }



    function checkAccess($strAction)
    {
        global $objInit;

        if($objInit->mode == 'backend') {
            //backend access
        } else {
            //frontend access

            $strStatus = '';
            $objFWUser  = \FWUser::getFWUserObject();

            //get user attributes
            $objUser         = $objFWUser->objUser;
            $intUserId      = intval($objUser->getId());
            $bolUserLogin   = $objUser->login();
            $intUserIsAdmin = $objUser->getAdminStatus();
            $intSelectedFormId = empty($_REQUEST['selectedFormId']) ? substr($_REQUEST['cmd'],3) : intval($_REQUEST['selectedFormId']);

            $accessId = 0; //used to remember which access id the user needs to have. this is passed to Permission::checkAccess() later.

            if(!$intUserIsAdmin) {
                self::getSettings();

                switch($strAction) {
                    case 'add_entry':
                        $accessId = MediaDirectoryAccessIDs::AddEntry;

                        if($this->arrSettings['settingsAllowAddEntries']) {
                            if($this->arrSettings['settingsAddEntriesOnlyCommunity'] == 1) {
                                if($bolUserLogin) {
                                    $bolAdd = true;
                                } else {
                                    $bolAdd = false;
                                }
                            } else {
                                $bolAdd = true;
                            }

                            if($bolAdd) {
                                //get groups attributes
                                $arrUserGroups  = array();
                                $objGroup = $objFWUser->objGroup->getGroups($filter = array('is_active' => true, 'type' => 'frontend'));

                                while (!$objGroup->EOF) {
                                    if(in_array($objGroup->getId(), $objUser->getAssociatedGroupIds())) {
                                        $arrUserGroups[] = $objGroup->getId();
                                    }
                                    $objGroup->next();
                                }

                                self::getCommunityGroups();
                                $strMaxEntries = 0;
                                $bolFormAllowed = false;

                                //check max entries
                                foreach ($arrUserGroups as $intGroupId) {
                                    $strNewMaxEntries = $this->arrCommunityGroups[$intGroupId]['num_entries'];

                                    if(($strNewMaxEntries === 'n') || ($strMaxEntries === 'n')) {
                                        $strMaxEntries = 'n';
                                    } else {
                                        if(($strNewMaxEntries >= $strMaxEntries)) {
                                            $strMaxEntries = $strNewMaxEntries;
                                        }
                                    }

                                    if($this->arrCommunityGroups[$intGroupId]['status_group'][$intSelectedFormId] == 1 && !$bolFormAllowed) {
                                        $bolFormAllowed = true;
                                    }
                                }

                                $objEntries = new MediaDirectoryEntry($this->moduleName);
                                $objEntries->getEntries(null, null, null, null, null, null, null, null, 'n', $intUserId);

                                if($strMaxEntries <= intval(count($objEntries->arrEntries)) && $strMaxEntries !== 'n' && $this->arrSettings['settingsAddEntriesOnlyCommunity'] == 1) {
                                    $strStatus = 'redirect';
                                }

                                //OSEC CUSTOMIZING
                                if($intSelectedFormId == 5) {
                                    // entry is not yet ready to get confirmed
                                    $objEntries = new MediaDirectoryEntry($this->moduleName);
                                    $objEntries->getEntries(null, null, null, null, null, true, null, null, 'n', $intUserId, null, $intSelectedFormId);

                                    if(count($objEntries->arrEntries) >= 1) {
                                        foreach ($objEntries->arrEntries as $intEntryId => $arrEntry) {
                                            $strStatus = 'osec'.$intEntryId;
                                        }
                                    }

                                    // entry is ready to get confirmed
                                    $objEntries = new MediaDirectoryEntry($this->moduleName);
                                    $objEntries->getEntries(null, null, null, null, null, null, null, null, 'n', $intUserId, null, $intSelectedFormId, true);

                                    if(count($objEntries->arrEntries) >= 1) {
                                        foreach ($objEntries->arrEntries as $intEntryId => $arrEntry) {
                                            $strStatus = 'osec'.$intEntryId;
                                        }
                                    }
                                }

                                //check from type
                                if(!$bolFormAllowed && $intSelectedFormId != 0 && $this->arrSettings['settingsAddEntriesOnlyCommunity'] == 1) {
                                    $strStatus = 'no_access';
                                }
                            } else {
                                $strStatus = 'login';
                            }
                        } else {
                            $strStatus = 'redirect';
                        }
                        break;
                    case 'edit_entry':
                        $accessId = MediaDirectoryAccessIDs::ModifyEntry;

                        if($this->arrSettings['settingsAllowEditEntries']) {
                            if($bolUserLogin) {
                                $objEntries = new MediaDirectoryEntry($this->moduleName);

                                if(isset($_POST['submitEntryModfyForm'])) {
                                    $intEntryId = intval($_POST['entryId']);
                                } else {
                                    $intEntryId = intval($_GET['eid']);
                                }

                                $objEntries->getEntries($intEntryId, null, null, null, null, null, null, null, 'n', $intUserId, null, $intSelectedFormId, true);
                                if($objEntries->arrEntries[$intEntryId]['entryAddedBy'] !== $intUserId) {
                                    $strStatus = 'confirm_in_progress';
                                }
                            } else {
                                $strStatus = 'login';
                            }
                        } else {
                            $strStatus = 'redirect';
                        }
                        break;
                    case 'delete_entry':
                        $accessId = MediaDirectoryAccessIDs::ModifyEntry;

                        if($this->arrSettings['settingsAllowDelEntries']) {
                            if($bolUserLogin) {
                                $objEntries = new MediaDirectoryEntry($this->moduleName);
                                $objEntries->getEntries(intval($_GET['eid']));

                                if($objEntries->arrEntries[intval($_GET['eid'])]['entryAddedBy'] !== $intUserId) {
                                    $strStatus = 'no_access';
                                }
                            } else {
                                $strStatus = 'login';
                            }
                        } else {
                            $strStatus = 'redirect';
                        }
                        break;
                    case 'show_entry':
                        //no access rules define
                        break;
                    case 'my_entries':
                        if(!$bolUserLogin) {
                            $strStatus = 'login';
                        }
                        break;
                }

                //only run Permission::checkAccess if user is logged in.
                //logged out users are redirected to a login page with redirect param
                //a few lines below
                if($bolUserLogin && $accessId)
                    \Permission::checkAccess($accessId, 'static');

                switch($strStatus) {
                    case 'no_access':
                        header('Location: '.CONTREXX_SCRIPT_PATH.'?section=Login&cmd=noaccess');
                        exit;
                        break;
                    case 'login':
                        $link = base64_encode(CONTREXX_SCRIPT_PATH.'?'.$_SERVER['QUERY_STRING']);
                        header("Location: ".CONTREXX_SCRIPT_PATH."?section=Login&redirect=".$link);
                        exit;
                        break;
                    case 'redirect':
                        header('Location: '.CONTREXX_SCRIPT_PATH.'?section='.$this->moduleName);
                        exit;
                        break;
                    case 'confirm_in_progress':
                        header('Location: '.CONTREXX_SCRIPT_PATH.'?section='.$this->moduleName.'&cmd=confirm_in_progress');
                        exit;
                        break;
                    default:
                        if(substr($strStatus,0,4) == 'osec') {
                            header('Location: '.CONTREXX_SCRIPT_PATH.'?section='.$this->moduleName.'&cmd=edit5&eid='.intval(substr($strStatus,4)));
                            exit;
                        }
                        break;
                }
            }
        }
    }



    function getFrontendLanguages()
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $arrLanguages = array();
        $arrActiveLangs = array();

        self::getSettings();
        $arrActiveLangs = explode(",",$this->arrSettings['settingsActiveLanguages']);

        $objLanguages = $objDatabase->Execute("SELECT id,lang,name,frontend,is_default FROM ".DBPREFIX."languages ORDER BY is_default ASC");
        if ($objLanguages !== false) {
            while (!$objLanguages->EOF) {
                if(in_array($objLanguages->fields['id'], $arrActiveLangs)) {
                    $arrData = array();

                    $arrData['id'] = intval($objLanguages->fields['id']);
                    $arrData['lang'] = htmlspecialchars($objLanguages->fields['lang'], ENT_QUOTES, CONTREXX_CHARSET);
                    $arrData['name'] = htmlspecialchars($objLanguages->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
                    $arrData['frontend'] = intval($objLanguages->fields['frontend']);
                    $arrData['is_default'] = htmlspecialchars($objLanguages->fields['is_default'], ENT_QUOTES, CONTREXX_CHARSET);

                    $arrLanguages[$objLanguages->fields['id']] = $arrData;
                }

                $objLanguages->MoveNext();
            }
        }

        // return $arrLanguages;
        $this->arrFrontendLanguages = $arrLanguages;
    }



    function getSettings()
    {
        global $objDatabase;

        $arrSettings = array();

        $objSettings = $objDatabase->Execute("SELECT id,name,value FROM ".DBPREFIX."module_".$this->moduleNameLC."_settings ORDER BY name ASC");
        if ($objSettings !== false) {
            while (!$objSettings->EOF) {
                if($objSettings->fields['id'] == 9 || $objSettings->fields['id'] == 10) {
                    $arrOrders = $this->getSelectorOrder($objSettings->fields['value']);
                    $arrSettings[htmlspecialchars($objSettings->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)] = $arrOrders;
                } else if($objSettings->fields['id'] == 42 || $objSettings->fields['id'] == 43) {
                    $arrExpSearch = $this->getSelectorSearch($objSettings->fields['value']);
                    $arrSettings[htmlspecialchars($objSettings->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)] = $arrExpSearch;
                } else{
                    $arrSettings[htmlspecialchars($objSettings->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)] = htmlspecialchars($objSettings->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                }
                $objSettings->MoveNext();
            }
        }
        // return $arrSettings;
        $this->arrSettings = $arrSettings;
    }



    function getSelectorOrder($intSelectorId)
    {
        global $objDatabase;

        $arrOrder = array();

        $objSelectorOrder = $objDatabase->Execute("SELECT form_id,selector_order FROM ".DBPREFIX."module_".$this->moduleNameLC."_order_rel_forms_selectors WHERE selector_id='".intval($intSelectorId)."'");
        if ($objSelectorOrder !== false) {
            while (!$objSelectorOrder->EOF) {
                $arrOrder[intval($objSelectorOrder->fields['form_id'])] = intval($objSelectorOrder->fields['selector_order']);
                $objSelectorOrder->MoveNext();
            }
        }

        return $arrOrder;
    }



    function getSelectorSearch($intSelectorId)
    {
        global $objDatabase;

        $arrExpSearch = array();

        $objSelectorSearch = $objDatabase->Execute("SELECT form_id, exp_search FROM ".DBPREFIX."module_".$this->moduleNameLC."_order_rel_forms_selectors WHERE selector_id='".intval($intSelectorId)."'");
        if ($objSelectorSearch !== false) {
            while (!$objSelectorSearch->EOF) {
                $arrExpSearch[intval($objSelectorSearch->fields['form_id'])] = intval($objSelectorSearch->fields['exp_search']);
                $objSelectorSearch->MoveNext();
            }
        }

        return $arrExpSearch;
    }



    function getCommunityGroups()
    {
        global $objDatabase;

        $arrCommunityGroups = array();

        $objCommunityGroups = $objDatabase->Execute("SELECT
                                                        `group`.`group_id` AS group_id,
                                                        `group`.`group_name` AS group_name,
                                                        `group`.`is_active` AS is_active,
                                                        `group`.`type` AS `type`,
                                                        `num_entry`.`num_entries` AS num_entries,
                                                        `num_category`.`num_categories` AS num_categories,
                                                        `num_level`.`num_levels` AS num_levels
                                                      FROM
                                                        ".DBPREFIX."access_user_groups AS `group`
                                                      LEFT JOIN ".DBPREFIX."module_".$this->moduleNameLC."_settings_num_entries AS `num_entry` ON `num_entry`.`group_id` = `group`.`group_id`
                                                      LEFT JOIN ".DBPREFIX."module_".$this->moduleNameLC."_settings_num_categories AS `num_category` ON `num_category`.`group_id` = `group`.`group_id`
                                                      LEFT JOIN ".DBPREFIX."module_".$this->moduleNameLC."_settings_num_levels AS `num_level` ON `num_level`.`group_id` = `group`.`group_id`");
        if ($objCommunityGroups !== false) {
            while (!$objCommunityGroups->EOF) {
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['name'] = htmlspecialchars($objCommunityGroups->fields['group_name'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['active'] = intval($objCommunityGroups->fields['is_active']);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['type'] = htmlspecialchars($objCommunityGroups->fields['type'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['num_entries'] = htmlspecialchars($objCommunityGroups->fields['num_entries'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['num_categories'] = htmlspecialchars($objCommunityGroups->fields['num_categories'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['num_levels'] = htmlspecialchars($objCommunityGroups->fields['num_levels'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['status_group'] = array();

                $objCommunityGroupPermForms = $objDatabase->Execute("SELECT
                                                        `perm_group_form`.`form_id` AS form_id ,
                                                        `perm_group_form`.`status_group` AS status_group
                                                      FROM
                                                        ".DBPREFIX."module_".$this->moduleNameLC."_settings_perm_group_forms AS `perm_group_form`
                                                      WHERE
                                                        `perm_group_form`.`group_id` = '".intval($objCommunityGroups->fields['group_id'])."'");
                if ($objCommunityGroupPermForms !== false) {
                    while (!$objCommunityGroupPermForms->EOF) {
                        $arrCommunityGroups[intval($objCommunityGroups->fields['group_id'])]['status_group'][intval($objCommunityGroupPermForms->fields['form_id'])] = htmlspecialchars($objCommunityGroupPermForms->fields['status_group'], ENT_QUOTES, CONTREXX_CHARSET);
                        $objCommunityGroupPermForms->MoveNext();
                    }
                }

                $objCommunityGroups->MoveNext();
            }
        }

        // return $arrCommunityGroups;
        $this->arrCommunityGroups = $arrCommunityGroups;
    }



    function buildDropdownmenu($arrOptions, $intSelected=null)
    {
        $strOptions = '';
        foreach ($arrOptions as $intValue => $strName) {
            $checked = $intValue==$intSelected ? 'selected="selected"' : '';
            $strOptions .= "<option value='".$intValue."' ".$checked.">".contrexx_raw2xhtml($strName)."</option>";
        }

        return $strOptions;
    }

    /**
     * getQueryToFindFirstInputFieldId
     *
     * @return string
     */
    public function getQueryToFindFirstInputFieldId()
    {
        $query = "SELECT
                        first_rel_inputfield.`field_id` AS `id`
                    FROM
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields AS first_rel_inputfield
                    LEFT JOIN
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields AS inputfield
                    ON
                        first_rel_inputfield.`field_id` = inputfield.`id`
                    WHERE
                        (inputfield.`type` != 16 AND inputfield.`type` != 17 AND inputfield.`type` != 30)
                    AND
                        (first_rel_inputfield.`entry_id` = entry.`id`)
                    AND
                        (first_rel_inputfield.`form_id` = entry.`form_id`)
                    ORDER BY
                        inputfield.`order` ASC
                    LIMIT 1";
        return $query;
    }


    function getSelectorJavascript(){
        global $objInit, $_LANGID;

        if($objInit->mode == 'frontend') {
            self::getSettings();
            if($this->arrSettings['settingsAddEntriesOnlyCommunity'] == 1) {
                $objFWUser      = \FWUser::getFWUserObject();
                $objUser         = $objFWUser->objUser;
                $intUserId      = intval($objUser->getId());
                $bolUserLogin   = $objUser->login();
                $bolUserIsAdmin = $objUser->getAdminStatus();

                if(!$bolUserIsAdmin) {
                    if($bolUserLogin) {
                        $arrUserGroups  = array();
                        $objGroup = $objFWUser->objGroup->getGroups($filter = array('is_active' => true, 'type' => 'frontend'));

                        while (!$objGroup->EOF) {
                            if(in_array($objGroup->getId(), $objUser->getAssociatedGroupIds())) {
                                $arrUserGroups[] = $objGroup->getId();
                            }
                            $objGroup->next();
                        }

                        self::getCommunityGroups();
                        $strMaxCategorySelect = 0;
                        $strMaxLevelSelect = 0;

                        foreach ($arrUserGroups as $intGroupId) {
                            $strNewMaxCategorySelect = $this->arrCommunityGroups[$intGroupId]['num_categories'];
                            $strNewMaxLevelSelect = $this->arrCommunityGroups[$intGroupId]['num_levels'];

                            if(($strNewMaxCategorySelect === 'n') || ($strMaxCategorySelect === 'n')) {
                                $strMaxCategorySelect = 'n';
                            } else {
                                if(($strNewMaxCategorySelect >= $strMaxCategorySelect)) {
                                    $strMaxCategorySelect = $strNewMaxCategorySelect;
                                }
                            }

                            if(($strNewMaxLevelSelect === 'n') || ($strMaxLevelSelect === 'n')) {
                                $strMaxLevelSelect = 'n';
                            } else {
                                if(($strNewMaxLevelSelect >= $strMaxLevelSelect)) {
                                    $strMaxLevelSelect = $strNewMaxLevelSelect;
                                }
                            }
                        }
                    }
                } else {
                    $strMaxCategorySelect = 'n';
                    $strMaxLevelSelect = 'n';
                }
            } else {
                $strMaxCategorySelect = 'n';
                $strMaxLevelSelect = 'n';
            }
        } else {
            $strMaxCategorySelect = 'n';
            $strMaxLevelSelect = 'n';
        }

        //get languages
        self::getFrontendLanguages();
        foreach ($this->arrFrontendLanguages as $intKey => $arrLang) {
            $arrActiveLang[$arrLang['id']] = $arrLang['id'];
        }
        $arrActiveLang = join(",", $arrActiveLang);
        $strModulName = $this->moduleName;

        $strSelectorJavascript = <<< EOF

function moveElement(from, dest, add, remove) {
    if(checkNum(dest)) {
        if (from.selectedIndex < 0) {
            if (from.options[0] != null) from.options[0].selected = true;
                from.focus();
                return false;
            } else {
                for (i = 0; i < from.length; ++i) {
                    if (from.options[i].selected) {
                        dest.options[dest.options.length] = new Option(from.options[i].text, from.options[i].value, false, false);
                    }
                }
                for (i = from.options.length-1; i >= 0; --i) {
                    if (from.options[i].selected) {
                    from.options[i] = null;
                }
            }
        }
    }
}

function checkNum(dest){
    if(dest.id == 'selectedCategories' || dest.id == 'selectedLevels') {
        if(dest.id == 'selectedCategories') {
            maxLength = '$strMaxCategorySelect';
        } else {
            maxLength = '$strMaxLevelSelect';
        }

        if(maxLength != 'n') {
            if(dest.options.length < maxLength) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    } else {
        return true;
    }
}


function selectAll(control){
    for (i = 0; i < control.length; ++i) {
        control.options[i].selected = true;
    }
    if ( typeof(CKEDITOR) !== "undefined" ) {
        \$J.each(instancesToManipulate, function(i, v) {
            v.setData(CKEDITOR.instances['mediadirInputfield['+ wysiwygId +'][0]'].getData());
        });
    }
}

function deselectAll(control){
    for (i = 0; i < control.length; ++i) {
        control.options[i].selected = false;
    }
}
var defaultLang = '$_LANGID';
var activeLang = [$arrActiveLang];
\$J(function(){
    \$J('.mediadirInputfieldDefault').each(function(){
        var id = \$J(this).data('id');
        var relatedFieldPrefix = \$J(this).data('relatedFieldPrefix') ? \$J(this).data('relatedFieldPrefix') : 'mediadirInputfield';
        \$J(this).data('lastDefaultValue', \$J(this).val());

        \$J(this).keyup(function(){
            var that = \$J(this);
            var id = \$J(this).data('id');

            var relatedFieldPrefix = \$J(this).data('relatedFieldPrefix') ? \$J(this).data('relatedFieldPrefix') : 'mediadirInputfield';
            \$J.each(activeLang, function(i, v) {
                if (\$J('#'+ relatedFieldPrefix + '_' + id +'_'+ v).val() == that.data('lastDefaultValue')) {
                    \$J('#'+ relatedFieldPrefix + '_' + id +'_'+ v).val(that.val());
                    if (that.data('isImage') && \$J('#'+ relatedFieldPrefix + '_' + id +'_'+ v +'_preview')) {
                        changeImagePreview(\$J('#'+ relatedFieldPrefix + '_' + id +'_'+ v +'_preview'), that.val());
                    }
                }
            });
            \$J(this).data('lastDefaultValue', \$J(this).val());
        });

        \$J('#'+ relatedFieldPrefix + '_' + id +'_'+ defaultLang).keyup(function(){
            var id = \$J(this).data('id');
            var relatedFieldPrefix = \$J(this).data('relatedFieldPrefix') ? \$J(this).data('relatedFieldPrefix') : 'mediadirInputfield';
            \$J('#'+ relatedFieldPrefix + '_' + id +'_0').val(\$J(this).val());
            if (\$J(this).data('isImage') && \$J('#'+ relatedFieldPrefix + '_' + id +'_0_preview')) {
                changeImagePreview(\$J('#'+ relatedFieldPrefix + '_' + id +'_0_preview'), \$J(this).val());
            }
            \$J('#'+ relatedFieldPrefix + '_' + id +'_0').data('lastDefaultValue', \$J(this).val());
        });
    });
    \$J('.mediadirInputfieldDefaultDeleteFile').each(function(){
        id = \$J(this).data('id');
        \$J(this).data('isChecked', \$J(this).is( ":checked" ));

        \$J(this).click(function(){
            var that = \$J(this);
            var id = \$J(this).data('id');

            \$J.each(activeLang, function(i, v) {
                if (\$J('#mediadirInputfield_delete_'+ id +'_'+ v).is( ":checked" ) == that.data('isChecked')) {
                    \$J('#mediadirInputfield_delete_'+ id +'_'+ v).prop('checked', that.is( ":checked" ));
                }
            });
            \$J(this).data('isChecked', \$J(this).is( ":checked" ));
        });

        \$J('#mediadirInputfield_delete_'+ id +'_'+ defaultLang).click(function(){
            var id = \$J(this).data('id');
            \$J('#mediadirInputfield_delete_'+ id +'_0').prop('checked', \$J(this).is( ":checked" ));
            \$J('#mediadirInputfield_delete_'+ id +'_0').data('isChecked', \$J(this).is( ":checked" ));
        });
    });
});

function changeImagePreview(elm, src) {
    elm.after('<span class="loading">Loading ...</span>');
    elm.fadeOut(300, function(){
        \$J(this).attr('src',src).bind('onreadystatechange load', function() {
            if (this.complete) {
                \$J(this).fadeIn(300);
                \$J(this).next('span.loading').remove();
            }
      });
   });
}

function rememberWysiwygFields(ev) {
    fieldArr   = ev.editor.name.split(/\[(\d+)\]/);
    wysiwygId     = fieldArr[1];
    \$minimized = \$J('#mediadirInputfield_' + wysiwygId + '_ELEMENT_Minimized');
    instancesToManipulate = [];
    if (\$minimized.is(":visible")) {
        \$J.each(activeLang, function(i, v) {
            if (CKEDITOR.instances['mediadirInputfield['+ wysiwygId +']['+ v +']'].getData() === lastCKeditorValues[wysiwygId]) {
                instancesToManipulate.push(CKEDITOR.instances['mediadirInputfield['+ wysiwygId +']['+ v +']']);
            }
        });
    }
}

function copyWysiwygFields(ev) {
    fieldArr   = ev.editor.name.split(/\[(\d+)\]/);
    wysiwygId     = fieldArr[1];
    \$J.each(instancesToManipulate, function(i, v) {
        v.setData(CKEDITOR.instances['mediadirInputfield['+ wysiwygId +'][0]'].getData());
    });
    instancesToManipulate = [];
    lastCKeditorValues[wysiwygId] = CKEDITOR.instances['mediadirInputfield['+ wysiwygId +'][0]'].getData();
}

if ( typeof(CKEDITOR) !== "undefined" ) {
    var lastCKeditorValues = new Array();
    var processedCKeditorInstances = new Array();
    var instancesToManipulate = new Array();
    var wysiwygId = 0;
    CKEDITOR.on("instanceReady", function(event)
    {
        for ( instance in CKEDITOR.instances )
        {
            if (\$J.inArray(CKEDITOR.instances[instance].name, processedCKeditorInstances)) {
                fieldArr = CKEDITOR.instances[instance].name.split(/\[(\d+)\]/);
                id       = fieldArr[1];
                langId   = fieldArr[3];

                if (langId == '0') {
                    lastCKeditorValues[id] = CKEDITOR.instances[instance].getData();
                    CKEDITOR.instances[instance].on('focus', function (ev) {
                        rememberWysiwygFields(ev);
                   });
                   CKEDITOR.instances[instance].on('blur', function (ev) {
                       copyWysiwygFields(ev);
                   });
                   CKEDITOR.instances[instance].on('mode', function (ev) {
                        if ( this.mode == 'source' ) {
                            rememberWysiwygFields(ev);
                        }
                   });
                }
                if (langId == defaultLang) {
                   CKEDITOR.instances[instance].on('change', function (ev) {
                        fieldArr   = ev.editor.name.split(/\[(\d+)\]/);
                        var id     = fieldArr[1];
                        \$expand    = \$J('#mediadirInputfield_' + id + '_ELEMENT_Expanded');
                        if (\$expand.is(":visible")) {
                            CKEDITOR.instances['mediadirInputfield['+ id +'][0]'].setData(ev.editor.getData());
                            lastCKeditorValues[id] = ev.editor.getData();
                        }
                   });
                }
                processedCKeditorInstances.push(CKEDITOR.instances[instance].name);
            }
        }
    });
}

function ExpandMinimize(toggle){
    elm1 = document.getElementById('mediadirInputfield_' + toggle + '_Minimized');
    elm2 = document.getElementById('mediadirInputfield_' + toggle + '_Expanded');

    elm1.style.display = (elm1.style.display=='none') ? 'block' : 'none';
    elm2.style.display = (elm2.style.display=='none') ? 'block' : 'none';
}

function ExpandMinimizeMultiple(toggleId, toggleKey){
    if ( typeof(CKEDITOR) !== "undefined" ) {
        \$J.each(instancesToManipulate, function(i, v) {
            v.setData(CKEDITOR.instances['mediadirInputfield['+ wysiwygId +'][0]'].getData());
        });
    }

    elm1 = document.getElementById('mediadirInputfield_' + toggleId +  '_' + toggleKey + '_Minimized');
    elm2 = document.getElementById('mediadirInputfield_' + toggleId +  '_' + toggleKey + '_Expanded');

    elm1.style.display = (elm1.style.display=='none') ? 'block' : 'none';
    elm2.style.display = (elm2.style.display=='none') ? 'block' : 'none';
}

EOF;

        return $strSelectorJavascript;
    }

    function getFormOnSubmit($arrScripts){
        $strFormOnSubmit = '';
        foreach ($arrScripts as $intInputfieldId => $strScript) {
           if(!empty($strScript) || $strScript != '') {
               $strFormOnSubmit .= $strScript;
           }
        }

        $strFormOnSubmit   .= "return checkAllFields();";

        return $strFormOnSubmit;
    }


    function setJavascript($strJavascript){
        $this->strJavascript .= $strJavascript;
    }



    function getJavascript(){
// TODO: do we need the shadowbox every time?
        \JS::activate('shadowbox');

        $strJavascript = <<< EOF
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
EOF;
        $strJavascript .= $this->strJavascript;
        $strJavascript .= <<< EOF
/* ]]> */
</script>
EOF;
        return $strJavascript;
    }

    /**
     * Get mediabrowser button
     *
     * @param string $buttonValue Value of the button
     * @param string $options     Input button options
     * @param string $callback    Media browser callback function
     *
     * @return string html element of browse button
     */
    public function getMediaBrowserButton($buttonValue, $options = array(), $callback = '')
    {
        // Mediabrowser
        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions($options);
        if ($callback) {
            $mediaBrowser->setCallback($callback);
        }

        return $mediaBrowser->getXHtml($buttonValue);
    }

    /**
     * Get the Thumbnail image path from given file
     * Thumbnail will be created it is not exists
     *
     * @param string $path Relative path to the file
     *
     * @return string Thumbnail path
     */
    public function getThumbImage($path)
    {
        if (empty($path)) {
            return '';
        }
        $thumbnails = \Cx\Core\Core\Controller\Cx::instanciate()
                        ->getMediaSourceManager()
                        ->getThumbnailGenerator()
                        ->getThumbnailsFromFile(dirname($path), $path, true);

        return current($thumbnails);
    }

    /**
    * Get uploaded file path by using uploader id and file name
    *
    * @param string $uploaderId Uploader id
    * @param string $fileName   File name
    *
    * @return boolean|string File path when File exists, false otherwise
    */
    public function getUploadedFilePath($uploaderId, $fileName)
    {
        global $sessionObj;

        if (empty($uploaderId) || empty($fileName)) {
            return false;
        }

        if (empty($sessionObj)) {
            $sessionObj = \cmsSession::getInstance();
        }

        $uploaderFolder = $sessionObj->getTempPath() . '/' . $uploaderId;
        if (!\Cx\Lib\FileSystem\FileSystem::exists($uploaderFolder)) {
            return false;
        }

        $filePath = $uploaderFolder .'/'. $fileName;
        if (!\Cx\Lib\FileSystem\FileSystem::exists($filePath)) {
            return false;
        }

        return $filePath;
    }
}

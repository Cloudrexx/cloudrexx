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

    /**
     * Two-dimensional array of activated frontend locales
     *
     * Contains array-data of the frontend locales that have
     * been activated in the settings section.
     *
     * @var array
     */
    protected static $usedFrontendLocales = array();

    /**
     * List of the component's configuration
     *
     * @var array
     */
    protected static $settings = array();

    public $arrSettings = array();
    public $arrCommunityGroups = array();

    public $strJavascript;



    public $moduleName             = '';

    public $moduleNameLC             = '';

    public $moduleTablePrefix = "mediadir";
    public $moduleLangVar = "MEDIADIR";
    public $moduleConstVar = "MEDIADIR";

    protected $slugConversions = array(
        '_' => '',
        '-' => '_',
        ' ' => '-',
        '&' => '<and>',
    );

    protected static $level = null;
    protected static $category = null;
    protected static $form = null;

    /*
     * @var \Cx\Core\Core\Controller\Cx
     */
    protected $cx;

    /**
     * Holds the MediaDirectoryEntry object with the most recently
     * loaded entries (using MediaDirectoryEntry::getEntries())
     * @var MediaDirectoryEntry
     */
    protected static $currentFetchedEntryDataObject = null;

    /**
     * The locale in which the output shall be parsed for
     *
     * @var \Cx\Core\Locale\Model\Entity\Locale
     */
    protected static $outputLocale;

    /**
     * Constructor
     */
    function __construct($tplPath, $name)
    {
// TODO: assignment will be obsolete once this component has been migrated to extend form SystemComponentController
        $this->cx = \Cx\Core\Core\Controller\Cx::instanciate();

        $this->_objTpl = new \Cx\Core\Html\Sigma($tplPath);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->moduleName    = $name;
        $this->moduleNameLC  = strtolower($this->moduleName);

        $this->_objTpl->setGlobalVariable(array(
            'MODULE_NAME' =>  $this->moduleName,
            'MODULE_NAME_LC' => $this->moduleNameLC,
            'CSRF' =>  'csrf='.\Cx\Core\Csrf\Controller\Csrf::code(),
        ));

        $this->getFrontendLanguages();
    }

    function checkDisplayduration()
    {
        $this->getSettings();

        if($this->arrSettings['settingsEntryDisplaydurationNotification'] >= 1) {

            $objEntries = new MediaDirectoryEntry($this->moduleName);
            $objEntries->getEntries(null, null, null, null, null, null, true);

            $intDaysbefore = intval($this->arrSettings['settingsEntryDisplaydurationNotification']);
            $intToday = time();

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
                $this->getSettings();

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

                                $this->getCommunityGroups();
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

        $this->getSettings();
        $arrActiveLangs = explode(",",$this->arrSettings['settingsActiveLanguages']);

        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            if(in_array($frontendLanguage['id'], $arrActiveLangs)) {
                $arrData = array();

                $arrData['id'] = intval($frontendLanguage['id']);
                $arrData['lang'] = htmlspecialchars($frontendLanguage['lang'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrData['name'] = htmlspecialchars($frontendLanguage['name'], ENT_QUOTES, CONTREXX_CHARSET);
                $arrData['frontend'] = intval($frontendLanguage['frontend']);
                $arrData['is_default'] = htmlspecialchars($frontendLanguage['is_default'], ENT_QUOTES, CONTREXX_CHARSET);

                $arrLanguages[$frontendLanguage['id']] = $arrData;
            }
        }

        $this->arrFrontendLanguages = $arrLanguages;
        static::$usedFrontendLocales = $arrLanguages;
    }



    function getSettings()
    {
        global $objDatabase;

        $this->arrSettings = array();
        static::$settings = array();

        $objSettings = $objDatabase->Execute("SELECT id,name,value FROM ".DBPREFIX."module_".$this->moduleNameLC."_settings ORDER BY name ASC");
        if ($objSettings === false) {
            return;
        }

        while (!$objSettings->EOF) {
            $this->arrSettings[htmlspecialchars($objSettings->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)] = htmlspecialchars($objSettings->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
            $objSettings->MoveNext();
        }

        // the calls to getSelectorOrder() and getSelectorSearch() must be
        // done after the above assignment, as those methods will need the
        // setting legacyBehavior to be loaded already
        $this->arrSettings['categorySelectorOrder'] = $this->getSelectorOrder($this->arrSettings['categorySelectorOrder']);
        $this->arrSettings['levelSelectorOrder'] = $this->getSelectorOrder($this->arrSettings['levelSelectorOrder']);

        $this->arrSettings['categorySelectorExpSearch'] = $this->getSelectorSearch($this->arrSettings['categorySelectorExpSearch']);
        $this->arrSettings['levelSelectorExpSearch'] = $this->getSelectorSearch($this->arrSettings['levelSelectorExpSearch']);

        static::$settings = $this->arrSettings;
    }



    function getSelectorOrder($intSelectorId)
    {
        global $objDatabase;

        $arrOrder = array();

        // only load from active forms in frontend
        $query = "SELECT selectors.form_id, selectors.selector_order FROM ".DBPREFIX."module_".$this->moduleNameLC."_order_rel_forms_selectors AS selectors";
        $where = array("selectors.selector_id='".intval($intSelectorId)."'");
        if (!$this->arrSettings['legacyBehavior'] && \Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            $query .= ' INNER JOIN '.DBPREFIX.'module_'.$this->moduleNameLC.'_forms AS forms ON forms.id = selectors.form_id';
            $where[] = 'forms.active=1';
        }
        $query .= " WHERE ".implode(" AND ", $where);
        $objSelectorOrder = $objDatabase->Execute($query);
        if ($objSelectorOrder !== false) {
            while (!$objSelectorOrder->EOF) {
                $arrOrder[intval($objSelectorOrder->fields['form_id'])] = intval($objSelectorOrder->fields['selector_order']);
                $objSelectorOrder->MoveNext();
            }
        }

        return $arrOrder;
    }



    /**
     * Get setting if levels or categories shall be included in extended search 
     * functionality of a specific form.
     *
     * @param   integer $intSelectorId  Set to 9 to get the setting for category.
     *                                  set to 10 to get the setting for level.
     * @return  array   Array containung the setting if the levels or categories
     *                  shall be included in the extended seach functionality
     *                  for each form. Format: array(<form-id> => <true or false>)
     */
    function getSelectorSearch($intSelectorId)
    {
        global $objDatabase;

        $arrExpSearch = array();

        // only load from active forms in frontend
        $query = "SELECT selectors.form_id, selectors.exp_search FROM ".DBPREFIX."module_".$this->moduleNameLC."_order_rel_forms_selectors AS selectors";
        $where = array("selectors.selector_id='".intval($intSelectorId)."'");
        if (!$this->arrSettings['legacyBehavior'] && \Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            $query .= ' INNER JOIN '.DBPREFIX.'module_'.$this->moduleNameLC.'_forms AS forms ON forms.id = selectors.form_id';
            $where[] = 'forms.active=1';
        }
        $query .= " WHERE ".implode(" AND ", $where);
        $objSelectorSearch = $objDatabase->Execute($query);
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
     * Get SQL statement to fetch the ID of the primary field of a form.
     * The SQL statement can be used as a sub-query in a query where
     * contrexx_module_mediadir_entry is used and has been aliased as 'entry'
     *
     * @return string   The SQL statement to be used as sub-query
     */
    public function getQueryToFindPrimaryInputFieldId() 
    {
        return $this->getQueryToFindInputFieldIdByContextType('title');
    }

    /**
     * Get SQL statement to fetch the ID of the field of a form identified by
     * its context.
     * The SQL statement can be used as a sub-query in a query where
     * contrexx_module_mediadir_entry is used and has been aliased as 'entry'
     *
     * @return string   The SQL statement to be used as sub-query
     */
    protected function getQueryToFindInputFieldIdByContextType($type)
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
                        FIELD(inputfield.context_type, '".$type."') DESC,
                        inputfield.`order` ASC
                    LIMIT 1";
        return $query;
    }


    function getSelectorJavascript(){
        global $objInit;
        $langId = static::getOutputLocale()->getId();

        if($objInit->mode == 'frontend') {
            $this->getSettings();
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

                        $this->getCommunityGroups();
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
        $this->getFrontendLanguages();
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
var defaultLang = '$langId';
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

    public function getSlugFromName($name) {
        $slug = str_replace(array_keys($this->slugConversions), $this->slugConversions, $name);
        return urlencode($slug);
    }

    public function getNameFromSlug($slug) {
        $slugConversions = array_reverse($this->slugConversions, true);
        return  str_replace($slugConversions, array_keys($slugConversions), $slug);
    }

    /**
     * Get the human readable url for a mediadir location
     *
     *  URI-Slug scheme precedence
     *  1. $levelId & $categoryId are set:
     *      Url will point to specific level & category application page (if existing).
     *      I.e.: section=MediaDir&cmd=4-2
     *
     *  2. $levelId is set:
     *      Url will point to specific level application page (if existing).
     *      I.e.: section=MediaDir&cmd=4
     *
     *  3. $categoryId is set:
     *      Url will point to specific category application page (if existing).
     *      I.e.: section=MediaDir&cmd=2
     *
     *  4. if $arrEntry is set:
     *     Url will point to specific form application page (if existing).
     *     I.e.: section=MediaDir&cmd=team
     *
     *  5. $categoryId is set:
     *     Url will point to main application page (if existing).
     *     I.e.: section=MediaDir
     *
     *  6. if $arrEntry is set:
     *     Url will point to specific form detail application page (if existing).
     *     I.e.: section=MediaDir&cmd=detail12
     *
     *  7. if $arrEntry is set:
     *     Url will point to generic detail application page (if existing).
     *     I.e.: section=MediaDir&cmd=detail
     *
     *  8. fallback #1
     *     Url will point to main application page (if existing).
     *     I.e.: section=MediaDir
     *
     *  9. fallback #2
     *     Lookup failed, NULL will be returned
     *
     * @param   array   $arrEntry   (Optional) Array definition of the entry to locate
     * @param   integer $categoryId (Optional) ID of the category to locate
     * @param   integer $levelId    (Optional) ID of the level to locate
     * @param   boolean $useRequestedPageAsFallback    (Optional) Whether or not to use
     *                                                 the requested page as fallback in
     *                                                 case no matching mediadir application
     *                                                 could be found
     * @param   boolean $includeDetailApplicationPage  (Optional) Whether or not to include
     *                                                 the detail application page as a 
     *                                                 feasible url target
     * @return  \Cx\Core\Routing\Url    Returns an Url object of the mediadir location.
     *                                  If location is invalid, method will return NULL.
     */
    public function getAutoSlugPath($arrEntry = null, $categoryId = null, $levelId = null, $useRequestedPageAsFallback = false, $includeDetailApplicationPage = true) {
        $entryId = null;
        $entrySlug = null;
        $formId = null;
        $formCmd = null;
        $page = null;

        if (isset($arrEntry['entryId'])) {
            $entryId = $arrEntry['entryId'];
        }

        if (isset($arrEntry['slug'])) {
            $entrySlug = $arrEntry['slug'];
        }

        if (isset($arrEntry['entryFormId'])) {
            $formId = $arrEntry['entryFormId'];
            $formData = $this->getFormData();
            if (isset($formData[$formId])) {
                $formCmd = $formData[$formId]['formCmd'];
            }
        }

        // fetch level & category specific page
        if ($levelId && $categoryId) {
            $page = $this->getApplicationPageByLevelAndCategory($levelId, $categoryId);
            if ($page) {
                $levelId = null;
                $categoryId = null;
            }
        }

        // fetch level specific page
        if (!$page && $levelId) {
            $page = $this->getApplicationPageByLevel($levelId);
            if ($page) {
                $levelId = null;
            }
        }

        // fetch category specific page
        if (!$page && $categoryId) {
            $page = $this->getApplicationPageByCategory($categoryId);
            if ($page) {
                $categoryId = null;
            }
        }

        // fetch form specific page
        if (!$page && $entryId && $formCmd) {
            $page = $this->getApplicationPageByForm($formCmd);
        }

        // fetch generic application page
        if (!$page && $categoryId) {
            $page = $this->getMainApplicationPage();
        }

        // fetch specific detail page
        if (!$page && $includeDetailApplicationPage && $entryId) {
            $page = $this->getApplicationPageByEntry($formId);
        }

        // fetch generic application page
        if (!$page) {
            $page = $this->getMainApplicationPage();
        }

        if (!$page && $useRequestedPageAsFallback) {
            $page = $this->cx->getPage();
        }

        if (!$page) {
            return null;
        }

        $url = \Cx\Core\Routing\Url::fromPage($page);

        // create human readable url if option has been enabled to do so
        if ($this->arrSettings['usePrettyUrls']) {
            $path = $url->getPath() . $this->getLevelSlugPath($levelId) . $this->getCategorySlugPath($categoryId);
            if (isset($entrySlug)) {
                $path .= '/' . $entrySlug;
            }
            $url->setPath($path);
        } else {
            if ($entryId) {
                $url->setParam('eid', $entryId);
            }
            if ($categoryId) {
                $url->setParam('cid', $categoryId);
            }
            if ($levelId) {
                $url->setParam('lid', $levelId);
            }
        }

        return $url;
    }

    public function getApplicationPageByLevelAndCategory($levelId, $categoryId) {
        // abort in case levels are not in use
        if (!$this->arrSettings['settingsShowLevels']) {
            return null;
        }

        $pageRepo = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // fetch level & category specific application page (i.e. section=MediaDir&cmd=3_2)
        $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $levelId.'-'.$categoryId, FRONTEND_LANG_ID);
        if ($page && $page->isActive()) {
            return $page;
        }

        return null;
    }

    public function getApplicationPageByLevel($levelId) {
        // abort in case levels are not in use
        if (!$this->arrSettings['settingsShowLevels']) {
            return null;
        }

        $pageRepo = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // fetch level specific application page (i.e. section=MediaDir&cmd=3)
        $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $levelId, FRONTEND_LANG_ID);
        if ($page && $page->isActive()) {
            return $page;
        }

        return null;
    }

    public function getApplicationPageByCategory($categoryId) {
        $cmdPrefix = '';

        // in case levels are in use, the cmd of a category is prefixed by a dash
        if ($this->arrSettings['settingsShowLevels']) {
            $cmdPrefix = '-';
        }

        $pageRepo = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // fetch category specific application page (i.e. section=MediaDir&cmd=3)
        $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $cmdPrefix.$categoryId, FRONTEND_LANG_ID);
        if ($page && $page->isActive()) {
            return $page;
        }

        return null;
    }

    protected function getApplicationPageByForm($formCmd) {
        $pageRepo = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // fetch form specific application page (i.e. section=MediaDir&cmd=team)
        $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $formCmd, FRONTEND_LANG_ID);
        if ($page && $page->isActive()) {
            return $page;
        }

        return null;
    }

    protected function getMainApplicationPage() {
        $pageRepo = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // fetch main application page (i.e. section=MediaDir)
        $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, '', FRONTEND_LANG_ID);
        if ($page && $page->isActive()) {
            return $page;
        }

        return null;
    }

    public function getApplicationPageByEntry($formId = null) {
        $detailCmd = 'detail';
        $formSpecificDetailCmd = $detailCmd . $formId;
        $pageRepo = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // fetch form specific detail page (i.e. section=MediaDir&cmd=detail3)
        $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $formSpecificDetailCmd, FRONTEND_LANG_ID);

        // check if form specific detail page exists
        if (!$page || !$page->isActive()) {
            // fetch regular detail page (section=MediaDir&cmd=detail)
            $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $detailCmd, FRONTEND_LANG_ID);
        }

        if ($page && $page->isActive()) {
            return $page;
        }

        return null;
    }

    public function getLevelSlugPath($levelId) {
        if (!$levelId) {
            return '';
        }

        $slugParts = array();
        $arrLevels = $this->getLevelData();
        while (isset($arrLevels[$levelId])) {
            $slugParts[] = $this->getSlugFromName($arrLevels[$levelId]['levelName'][0]);
            $levelId = $arrLevels[$levelId]['levelParentId'];
        }

        if ($slugParts) {
            $slugParts = array_reverse($slugParts);
            return '/' . join('/', $slugParts);
        }

        return '';
    }

    public function getCategorySlugPath($categoryId) {
        if (!$categoryId) {
            return '';
        }

        $slugParts = array();
        $arrCategories = $this->getCategoryData();
        while (isset($arrCategories[$categoryId])) {
            $slugParts[] = $this->getSlugFromName($arrCategories[$categoryId]['catName'][0]);
            $categoryId = $arrCategories[$categoryId]['catParentId'];
        }

        if ($slugParts) {
            $slugParts = array_reverse($slugParts);
            return '/' . join('/', $slugParts);
        }

        return '';
    }

    public function getLevelData() {
        if (!isset(self::$level)) {
            $level = new MediaDirectoryLevel(null, null, 1, $this->moduleName);
            $arrLevels = $level->arrLevels;
            $arrAllLevels = array();
            
            while ($arrLevels) {
                $arrLevel = array_pop($arrLevels);
                if ($arrLevel['levelChildren']) {
                    $arrLevels = array_merge($arrLevels, $arrLevel['levelChildren']);
                }
                $arrAllLevels[$arrLevel['levelId']] = $arrLevel;
            }
            self::$level = $arrAllLevels;
        }

        return self::$level;
    }

    public function getCategoryData() {
        if (!isset(self::$category)) {
            $category = new MediaDirectoryCategory(null, null, 1, $this->moduleName);
            $arrCategories = $category->arrCategories;
            $arrAllCategories = array();
            
            while ($arrCategories) {
                $arrCategory = array_pop($arrCategories);
                if ($arrCategory['catChildren']) {
                    $arrCategories = array_merge($arrCategories, $arrCategory['catChildren']);
                }
                $arrAllCategories[$arrCategory['catId']] = $arrCategory;
            }
            self::$category = $arrAllCategories;
        }

        return self::$category;
    }

    public function getFormData() {
        if (!isset(self::$form)) {
            self::$form = new MediaDirectoryForm(null, $this->moduleName);
        }

        return self::$form->arrForms;
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
        if (empty($uploaderId) || empty($fileName)) {
            return false;
        }

        $cx  = \Cx\Core\Core\Controller\Cx::instanciate();
        $sessionObj = $cx->getComponent('Session')->getSession();

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

    protected function setCurrentFetchedEntryDataObject($objEntry) {
        self::$currentFetchedEntryDataObject = $objEntry;
    }

    protected function getCurrentFetchedEntryDataObject() {
        return self::$currentFetchedEntryDataObject;
    }

    protected function parseGoogleMapPlaceholder($template, $placeholder) {
        if (!$template->placeholderExists($placeholder)) {
            return false;
        }

        if (!isset(self::$currentFetchedEntryDataObject)) {
            return false;
        }

        self::$currentFetchedEntryDataObject->listEntries($template, 4, $placeholder);
    }

    /**
     * Identity functional placeholders in the block $block of template
     * $templatet that will act as config options to be applied on the listing
     * of entries. Placeholders can have the following form:
     * - MEDIADIR_CONFIG_LIST_LATEST => Order by latest addition
     * - MEDIADIR_CONFIG_LIST_LIMIT_<limit> => Limit the listing
     * - MEDIADIR_CONFIG_LIST_OFFSET_<offset> => Start listing at offset
     * - MEDIADIR_CONFIG_FILTER_FORM_<id> => filter by form
     * - MEDIADIR_CONFIG_FILTER_CATEGORY_<id> => filter by category
     * - MEDIADIR_CONFIG_FILTER_LEVEL_<id> => filter by level
     * - MEDIADIR_CONFIG_FILTER_AUTO => filter by supplied arguments as default
     * - MEDIADIR_CONFIG_SORT_POPULAR => order entries by popularity
     * - MEDIADIR_CONFIG_SORT_ALPHABETICAL => order entries alphabetically
     *
     * @param   string  $block Name of the template block to look up for
     *                         functional placeholders
     * @param   \Cx\Core\Html\Sigma $template   Template object where the block
     *                                          $block is located in
     * @param   integer $formId If supplied and filter
     *                          MEDIADIR_CONFIG_FILTER_AUTO is present, then do
     *                          set filter 'form' to $formId
     * @param   integer $categoryId If supplied and filter
     *                              MEDIADIR_CONFIG_FILTER_AUTO is present, then
     *                              do set filter 'category' to $categoryId
     * @param   integer $levelId If supplied and filter
     *                           MEDIADIR_CONFIG_FILTER_AUTO is present, then do
     *                           set filter 'level' to $levelId
     * @return  array   2-dimensional array containing the identified config.
     *                  It has the following structure:
     *                  <pre>array(
     *                      'list' => array(
     *                           'latest' => true,
     *                           'limit' => 10,
     *                           'offset' => 3
     *                      ),
     *                      'filter' => array(
     *                           'form' => 3,
     *                           'category' => 4,
     *                           'level' => 5
     *                      ),
     *                      'sort' => array(
     *                          'alphabetical' => true,
     *                      ),
     *                  )</pre>
     *                  Note: the sub entries in array 'list' and array 'filter'
     *                  are optional. They will only be set in case the
     *                  associated placeholder was found in the specified
     *                  template block.
     */
    public static function fetchMediaDirListConfigFromTemplate($block, $template, $formId = null, $categoryId = null, $levelId = null) {
        $config = array(
            'list' => array(),
            'filter' => array(),
            'sort' => array(),
        );

        // abort in case the template is invalid
        if (!$template->blockExists($block)) {
            return $config;
        }

        $placeholderList = $template->getPlaceholderList($block);
        $placeholderListAsString = join("\n", $placeholderList);
        $match = null;
        if (preg_match_all(
                '/MEDIADIR_CONFIG_(FILTER|LIST|SORT)_' // $1
                . '(LATEST|LIMIT|OFFSET|FORM|CATEGORY|LEVEL|ASSOCIATED|POPULAR|ALPHABETICAL)' // $2
                . '(?:_([0-9]+))?/', // $3
                $placeholderListAsString, $match)) {
            foreach ($match[2] as $idx => $key) {
                $configKey = strtolower($match[1][$idx]);
                $option = strtolower($key);

                // check for a specific set option value
                if ($match[3][$idx] !== '') {
                    $value = intval($match[3][$idx]);
                } else {
                    // if no specific option value has been set,
                    // then the option will be set to TRUE
                    $value = true;
                }
                // $configKey: "filter", or "list"
                // $option: "latest", "limit", "offset", "form",
                //      "category", "level", or "associated"
                $config[$configKey][$option] = $value;
            }
        }

        // If filter MEDIADIR_FILTER_AUTO is present, then we will override the
        // filters by the supplied arguments $formId, $categoryId and $levelId.
        // Otherwise, we will ignore any supplied arguments
        if (!in_array('MEDIADIR_CONFIG_FILTER_AUTO', $placeholderList)) {
            return $config;
        }

        // override form filter
        if ($formId) {
            $config['filter']['form'] = $formId;
        }

        // override category filter
        if ($categoryId) {
            $config['filter']['category'] = $categoryId;
        }

        // override level filter
        if ($levelId) {
            $config['filter']['level'] = $levelId;
        }

        return $config;
    }

    /**
     * Slugifies the given string
     * @param $string The string to slugify
     */
    protected function slugify(&$string, $key, $titleData = array()) {
        if (empty($string) && isset($titleData[$key])) {
            $string = $titleData[$key];
        }
        $string = $this->cx->getComponent('Model')->slugify($string);
    }

    /**
     * Get locale in which output shall be parsed for
     *
     * @return \Cx\Core\Locale\Model\Entity\Locale
     */
    public static function getOutputLocale() {
        if (!static::$outputLocale) {
            static::initOutputLocale();
        }
        return static::$outputLocale;
    }

    /**
     * Determine the locale in which the output shall be parsed for.
     *
     * Backend:
     *  1. Matching Locale of Backend language
     *  2. Selected Frontend Locale (of menu / if used by MediaDir)
     *  3. Default Frontend Locale (if used by MediaDir)
     *  4. Any Frontend Locale used by MediaDir
     *
     * Any other mode:
     *  Currently requested frontend Locale
     *
     * @return \Cx\Core\Locale\Model\Entity\Locale
     */
    protected static function initOutputLocale() {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager();

        static::$outputLocale = null;
        $locale = null;
        $cxMode = \Cx\Core\Core\Controller\Cx::instanciate()->getMode();

        // If we are in backend
        // do try to find a matching frontend locale
        if ($cxMode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            try {
                // get ISO-639-1 code of backend language
                $backend = $em->find(
                    'Cx\Core\Locale\Model\Entity\Backend',
                    LANG_ID
                );
                $iso1Code = $backend->getIso1()->getId();

                // find matching frontend locale based on ISO-639-1 code of backend
                // language
                $localeRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Locale');
                $locale = $localeRepo->findOneByCode($iso1Code);
            } catch (\Exception $e) {}
        }

        // If we are in frontend (or in any other mode than backend) or if
        // there does not exist a matching locale for the current backend
        // language, then load the currently set frontend locale
        // (either from the request when in frontend mode or by the selected
        // frontend locale in backend mode)
        if (!$locale) {
            // get currently selected frontend locale
            $locale = $em->find(
                'Cx\Core\Locale\Model\Entity\Locale',
                FRONTEND_LANG_ID
            );
        }

        // This should not happen as FRONTEND_LANG_ID should have been set
        // based on an existing locale
        if (!$locale) {
            throw new \Exception('Unable to initialize frontend locale');
        }

        // If we are in backend, we will check if the locale is actually in
        // use. In all other modes, we won't care about that as the
        // responsible view methods will take care about that themself.
        if ($cxMode != \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            static::$outputLocale = $locale;
            return $locale;
        }

        // If the current locale is not used by MediaDir component,
        // we will try to find the best match from the available locales.
        if (
            !static::$settings['settingsShowEntriesInAllLang'] &&
            !isset(static::$usedFrontendLocales[$locale->getId()])
        ) {
            $locale = null;
        }

        // If we haven't found a matching locale yet,
        // we will try to load the frontend's default locale that,
        // if it is used by the MediaDir component.
        if (
            !$locale &&
            isset(static::$usedFrontendLocales[\FWLanguage::getDefaultLangId()])
        ) {
            $locale = $em->find(
                'Cx\Core\Locale\Model\Entity\Locale',
                \FWLanguage::getDefaultLangId()
            );
        }

        // If we still haven't found a matching locale,
        // we will simply load the first locale that is used by the MediaDir
        // component.
        if (!$locale) {
            reset(static::$usedFrontendLocales);
            $locale = $em->find(
                'Cx\Core\Locale\Model\Entity\Locale',
                key(static::$usedFrontendLocales)
            );
        }

        // Without any locale we have to abort execution
        if (!$locale) {
            throw new \Exception('Unable to initialize frontend locale');
        }

        static::$outputLocale = $locale;
        return $locale;
    }

    /**
     * Get the source locale of a target locale
     *
     * The source locale is the fallback locale of a locale (the target locale)
     * or the system's default locale.
     *
     * @param   integer $localeId   The ID of the target locale
     * @param   array   $usedFrontendLocales List of available locales.
     *                                       One-dimensional array of locale
     *                                       IDs.
     * @return  integer The ID of the source locale
     */
    protected static function getSourceLocaleIdForTargetLocale($localeId, $usedFrontendLocales = array()) {
        if (!$usedFrontendLocales) {
            $usedFrontendLocales = array_keys(static::$usedFrontendLocales);
        }

        // fetch fallback locale of target locale
        $sourceLocaleId = \FWLanguage::getFallbackLanguageIdById($localeId);

        // fetch default locale in case no fallback locale is defined for the
        // target locale
        if (!$sourceLocaleId) {
            $sourceLocaleId = \FWLanguage::getDefaultLangId();
        }

        // If the source locale is not used by the MediaDir component
        // then we shall try the default locale (if not tried already)
        if (
            !in_array($sourceLocaleId, $usedFrontendLocales) &&
            $sourceLocaleId != \FWLanguage::getDefaultLangId()
        ) {
            $sourceLocaleId = \FWLanguage::getDefaultLangId();
        }

        // Fallback to any existing locale used by the MediaDir component
        if (!in_array($sourceLocaleId, $usedFrontendLocales)) {
            reset($usedFrontendLocales);
            $sourceLocaleId = current($usedFrontendLocales);
        }

        return $sourceLocaleId;
    }
}

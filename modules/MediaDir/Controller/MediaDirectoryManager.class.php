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
 * Media  Directory
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryManager extends MediaDirectoryLibrary
{
    public $strErrMessage;

    private $strOkMessage;
    private $pageTitle;

    private $act = '';
    private $limit = 30;
    private $offset = 0;

    /**
     * Constructor
     */
    function __construct($name)
    {
        global  $_ARRAYLANG, $_CORELANG, $objTemplate, $_CONFIG;

        $this->act = !empty($_GET['act']) ? $_GET['act'] : '';
        $this->limit = $_CONFIG['corePagingLimit'];
        $this->offset = !empty($_GET['pos']) ? $_GET['pos'] : 0;

        parent::__construct(\Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath().'/MediaDir/View/Template/Backend', $name);
        parent::getFrontendLanguages();
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG, $_CORELANG;
        $objTemplate->setVariable('CONTENT_NAVIGATION','
            <a href="index.php?cmd='.$this->moduleName.'" class="'.($this->act == '' ? 'active' : '').'">'.$_ARRAYLANG['TXT_MEDIADIR_OVERVIEW'].'</a>
            <a href="index.php?cmd='.$this->moduleName.'&amp;act=modify_entry" class="'.($this->act == 'modify_entry' ? 'active' : '').'">'.$_ARRAYLANG['TXT_MEDIADIR_ADD_ENTRY'].'</a>
            <a href="index.php?cmd='.$this->moduleName.'&amp;act=entries" class="'.($this->act == 'entries' ? 'active' : '').'">'.$_ARRAYLANG['TXT_MEDIADIR_MANAGE_ENTRIES'].'</a>
            <a href="index.php?cmd='.$this->moduleName.'&amp;act=interfaces" class="'.($this->act == 'interfaces' ? 'active' : '').'">'.$_ARRAYLANG['TXT_MEDIADIR_INTERFACES'].'</a>
            <a href="index.php?cmd='.$this->moduleName.'&amp;act=settings" class="'.($this->act == 'settings' ? 'active' : '').'">'.$_CORELANG['TXT_SETTINGS'].'</a>');
    }

    /**
    * get page
    *
    * Reads the act and selects the right action
    *
    * @access   public
    * @return   string  parsed content
    */
    function getPage()
    {
        global  $_ARRAYLANG, $objTemplate;

        switch ($this->act) {
            case 'modify_entry':
                $this->modifyEntry();
                break;
            case 'modify_category':
                $this->modifyCategory();
                break;
            case 'modify_level':
                $this->modifyLevel();
                break;
            case 'entries':
            case 'move_entry':
            case 'delete_entry':
            case 'restore_voting':
            case 'restore_comments':
            case 'confirm_entry':
                $this->manageEntries();
                break;
            case 'interfaces':
                $this->interfaces();
                break;
            case 'settings':
                $this->settings();
                break;
            case 'switchState':
                $this->switchState();
                break;
            case 'delete_comment':
            case 'comments':
                $this->manageComments();
                break;
            case 'delete_level':
            case 'delete_category':
            default:
                $this->overview();
                break;
        }

        if (
            in_array($this->act, array(
                'delete_entry',
                'restore_voting',
                'restore_comments',
                'confirm_entry',
                'switchState',
                'delete_comment',
                'delete_level',
                'order_level',
                'delete_category',
                'order_category',
            )) ||
            (
                $this->act == 'modify_entry' &&
                isset($_POST['submitEntryModfyForm']) &&
                !empty($_POST['formId'])
            ) ||
            (
                $this->act == 'modify_category' &&
                isset($_POST['submitCategoryModfyForm'])
            ) ||
            (
                $this->act == 'modify_level' &&
                isset($_POST['submitLevelModfyForm'])
            ) ||
            (
                $this->act == 'interfaces' &&
                isset($_POST['submitInterfacesForm'])
            ) ||
            (
                $this->act == 'settings' &&
                isset($_POST['submitSettingsForm'])
            )
        ) {
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('MediaDir');
        }

        $objTemplate->setVariable(array(
            'CONTENT_OK_MESSAGE'     => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE' => $this->strErrMessage,
            'CONTENT_TITLE'          => $this->pageTitle,
            'ADMIN_CONTENT'          => $this->_objTpl->get(),
        ));

        $this->setNavigation();

        return $this->_objTpl->get();
    }

    function overview()
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_overview.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_MEDIADIR_OVERVIEW'];
        $act = isset($_GET['act']) ? $_GET['act'] : '';
        switch ($act) {
            case 'delete_level':
                $objLevel = new MediaDirectoryLevel(null, null, 1, $this->moduleName);
                $strStatus = $objLevel->deleteLevel(intval($_GET['id']));

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_LEVEL']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_DELETED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_LEVEL']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_DELETED'];
                }
                break;
            case 'order_level':
                $objLevel = new MediaDirectoryLevel(null, null, 1, $this->moduleName);
                $strStatus = $objLevel->saveOrder($_POST);

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_LEVELS']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_EDITED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_LEVELS']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_EDITED'];
                }
                break;
            case 'delete_category':
                $objCategory = new MediaDirectoryCategory(null, null, 1, $this->moduleName);
                $strStatus = $objCategory->deleteCategory(intval($_GET['id']));

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_DELETED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_DELETED'];
                }
                break;
            case 'order_category':
                $objCategory = new MediaDirectoryCategory(null, null, 1, $this->moduleName);
                $strStatus = $objCategory->saveOrder($_POST);

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_CATEGORIES']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_EDITED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_CATEGORIES']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_EDITED'];
                }
                break;
        }

        //get seting values
        parent::getSettings();

        //get search dropdowns
        $objCategories = new MediaDirectoryCategory(null, null, 1, $this->moduleName);
        $catDropdown = $objCategories->listCategories(null, 3);

        $objLevels = new MediaDirectoryLevel(null, null, 1, $this->moduleName);
        $levelDropdown = $objLevels->listLevels(null, 3);

        $objForms = new MediaDirectoryForm(null, $this->moduleName);
        $formDropdown = $objForms->listForms(null, 4);

        //parse global variables
        $this->_objTpl->setGlobalVariable(array(
            'TXT_CONFIRM' => $_ARRAYLANG['TXT_MEDIADIR_CONFIRM'],
            'TXT_VIEW' => $_ARRAYLANG['TXT_MEDIADIR_VIEW'],
            'TXT_EDIT' => $_ARRAYLANG['TXT_MEDIADIR_EDIT'],
            'TXT_DELETE' => $_ARRAYLANG['TXT_MEDIADIR_DELETE'],
            'TXT_SEARCH' => $_CORELANG['TXT_SEARCH'],
            'TXT_SELECT_ALL' => $_CORELANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL' => $_CORELANG['TXT_DESELECT_ALL'],
            'TXT_SELECT_ACTION' => $_CORELANG['TXT_MULTISELECT_SELECT'],
            'TXT_CONFIRM_ALL' => $_ARRAYLANG['TXT_MEDIADIR_CONFIRM_ALL'],
            'TXT_DELETE_ALL' => $_CORELANG['TXT_MULTISELECT_DELETE'],
            'TXT_ALERT_DELETE_ALL' => $_CORELANG['TXT_DELETE_HISTORY_ALL'],
            'TXT_ALERT_ACTION_IS_IRREVERSIBLE' => $_CORELANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_ALERT_MAKE_SELECTION' => $_ARRAYLANG['TXT_MEDIADIR_ALERT_MAKE_SELECTION'],
            'TXT_FUNCTIONS' => $_ARRAYLANG['TXT_MEDIADIR_FUNCTIONS'],
            'TXT_CONFIRM_LIST' => $_ARRAYLANG['TXT_MEDIADIR_CONFIRM_LIST'],
            'TXT_LATEST_ENTRIES' => $_ARRAYLANG['TXT_MEDIADIR_LATEST_ENTRIES'],
            'TXT_EXPAND_LINK' => $_CORELANG['TXT_EXPAND_LINK'],
            'TXT_COLLAPS_LINK' => $_CORELANG['TXT_COLLAPS_LINK'],
            'TXT_'.$this->moduleLangVar.'_NAME' => $_CORELANG['TXT_NAME'],
            'TXT_'.$this->moduleLangVar.'_DATE' => $_CORELANG['TXT_DATE'],
            'TXT_'.$this->moduleLangVar.'_AUTHOR' => $_ARRAYLANG['TXT_MEDIADIR_AUTHOR'],
            'TXT_'.$this->moduleLangVar.'_HITS' => $_ARRAYLANG['TXT_MEDIADIR_HITS'],
            'TXT_'.$this->moduleLangVar.'_ACTION' => $_CORELANG['TXT_HISTORY_ACTION'],
            'TXT_'.$this->moduleLangVar.'_CONFIRM_DELETE_DATA' => $_ARRAYLANG['TXT_MEDIADIR_CONFIRM_DELETE_DATA'],
            'TXT_'.$this->moduleLangVar.'_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_MEDIADIR_ACTION_IS_IRREVERSIBLE'],
            'TXT_'.$this->moduleLangVar.'_MAKE_SELECTION' => $_ARRAYLANG['TXT_MEDIADIR_MAKE_SELECTION'],
            'TXT_'.$this->moduleLangVar.'_STATUS' => $_CORELANG['TXT_STATUS'],
            'TXT_'.$this->moduleLangVar.'_SUBMIT' =>  $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
            'TXT_'.$this->moduleLangVar.'_ID_OR_SEARCH_TERM' => $_ARRAYLANG['TXT_MEDIADIR_ID_OR_SEARCH_TERM'],
            'TXT_'.$this->moduleLangVar.'_ALL_LEVELS' => $_ARRAYLANG['TXT_MEDIADIR_ALL_LEVELS'],
            'TXT_'.$this->moduleLangVar.'_ALL_CATEGORIES' => $_ARRAYLANG['TXT_MEDIADIR_ALL_CATEGORIES'],
            'TXT_'.$this->moduleLangVar.'_ALL_FORMS' => $_ARRAYLANG['TXT_MEDIADIR_ALL_FORMS'],
            $this->moduleLangVar.'_CATEGORIES_DROPDOWN_OPTIONS' => $catDropdown,
            $this->moduleLangVar.'_LEVELS_DROPDOWN_OPTIONS' => $levelDropdown,
            $this->moduleLangVar.'_FORMS_DROPDOWN_OPTIONS' => $formDropdown,
            'TXT_'.$this->moduleLangVar.'_FORM' => $_ARRAYLANG['TXT_MEDIADIR_FORM'],
        ));

        if(count($objForms->arrForms) > 1) {
            $this->_objTpl->touchBlock($this->moduleNameLC.'FormDropdown');
        } else {
            $this->_objTpl->hideBlock($this->moduleNameLC.'FormDropdown');
        }

        if($this->arrSettings['settingsShowLevels'] == 1) {
            $this->_objTpl->touchBlock($this->moduleNameLC.'LevelDropdown');
        } else {
            $this->_objTpl->hideBlock($this->moduleNameLC.'LevelDropdown');
        }

        //show unconfirmed entries (if activated)
        if($this->arrSettings['settingsConfirmNewEntries'] == 1) {
            $objUnconfirmedEntries = new MediaDirectoryEntry($this->moduleName);

            if($this->arrSettings['settingsReadyToConfirm'] == 1) {
                $objUnconfirmedEntries->getEntries(null,null,null,null, null, 1,null,0,'n',null,null,null,true);
            } else {
                $objUnconfirmedEntries->getEntries(null,null,null,null, null, 1,null,0,'n');
            }
            $objUnconfirmedEntries->listEntries($this->_objTpl, 1);

            if(empty($objUnconfirmedEntries->arrEntries)) {
                $this->_objTpl->hideBlock('confirmBlock');
            }
        } else {
            $this->_objTpl->hideBlock('confirmBlock');
        }

        //show latest entries
        $objLatestEntries = new MediaDirectoryEntry($this->moduleName);
        $objLatestEntries->getEntries(null,null,null,null, 1, null, null, 0, $this->arrSettings['settingsLatestNumBackend']);
        $objLatestEntries->listEntries($this->_objTpl, 1);

        if(empty($objLatestEntries->arrEntries)) {
            $this->_objTpl->hideBlock($this->moduleNameLC.'LatestSelectAction');
        } else {
            $this->_objTpl->touchBlock($this->moduleNameLC.'LatestSelectAction');
        }

        //show levels (if activated)
        if($this->arrSettings['settingsShowLevels'] == 1) {
            $objLevels = new MediaDirectoryLevel(null, null, 1, $this->moduleName);
            $objLevels->listLevels($this->_objTpl, 1, null);


            if(isset($_GET['exp_cat']) || $act == 'order_category' || $act == 'delete_category') {
                $strTabLevelsDisplay = 'none';
                $strTabLevelsActive = '';
                $strTabCategoriesDisplay = 'block';
                $strTabCategoriesActive = 'class="active"';
            } else {
                $strTabLevelsDisplay = 'block';
                $strTabLevelsActive = 'class="active"';
                $strTabCategoriesDisplay = 'none';
                $strTabCategoriesActive = '';
            }

            $this->_objTpl->setVariable(array(
                'TXT_LEVELS' => $_ARRAYLANG['TXT_MEDIADIR_LEVELS'],
                'TAB_CATEGORIES_ACTIVE' => $strTabCategoriesActive,
                'TAB_LEVELS_ACTIVE' => $strTabLevelsActive,
            ));

            $this->_objTpl->parse('tabMenu');

            $this->_objTpl->setVariable(array(
                'TXT_LEVELS' => $_ARRAYLANG['TXT_MEDIADIR_LEVELS'],
                'TXT_ADD_LEVEL' => $_ARRAYLANG['TXT_MEDIADIR_LEVEL']. " ".$_ARRAYLANG['TXT_MEDIADIR_ADD'],
                'TAB_LEVELS_DISPLAY' => $strTabLevelsDisplay,
                'TAB_CATEGORIES_DISPLAY' => $strTabCategoriesDisplay,
            ));

           $this->_objTpl->parse('levelsTab');
        } else {
            $this->_objTpl->setVariable(array(
                'TAB_CATEGORIES_DISPLAY' => "block",
            ));

            $this->_objTpl->hideBlock('tabMenu');
            $this->_objTpl->hideBlock('levelsTab');
            $this->_objTpl->hideBlock($this->moduleNameLC.'LevelsList');
        }


        //show categories
        $objCategories->listCategories($this->_objTpl, 1, null);

        $this->_objTpl->setVariable(array(
            'TXT_CATEGORIES' => $_ARRAYLANG['TXT_MEDIADIR_CATEGORIES'],
            'TXT_ADD_CATEGORY' => $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']. " ".$_ARRAYLANG['TXT_MEDIADIR_ADD'],
        ));
    }



    function modifyEntry()
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        \JS::activate('cx');
        \JS::activate('jqueryui');

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_modify_entry.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_MEDIADIR_ENTRIES'];

         //get seting values
        parent::getSettings();

        $intEntryDourationAlways = '';
        $intEntryDourationPeriod = '';
        $intEntryDourationShowPeriod = 'none';
        $intEntryDourationEnd = 0;
        $intEntryDourationStart = 0;
        $strOnSubmit = '';

        if(!empty($_GET['id']) || !empty($_POST['entryId'])) {
            \Permission::checkAccess(MediaDirectoryAccessIDs::ModifyEntry, 'static');
            $pageTitle = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']. " ".$_ARRAYLANG['TXT_MEDIADIR_EDIT'];
            $intEntryId = intval($_GET['id']);
        } else {
            \Permission::checkAccess(MediaDirectoryAccessIDs::AddEntry, 'static');
            $pageTitle = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']. " ".$_ARRAYLANG['TXT_MEDIADIR_ADD'];
            $intEntryId = null;
        }

        //count forms
        $objForms = new MediaDirectoryForm(null, $this->moduleName);
        $arrActiveForms = array();

        foreach ($objForms->arrForms as $intFormId => $arrForm) {
            if($arrForm['formActive'] == 1) {
                $arrActiveForms[] = $intFormId;
            }
        }

        $intCountForms = count($arrActiveForms);

        $objEntry = null;
        if($intCountForms > 0) {
            if(intval($intEntryId) == 0 && (empty($_POST['selectedFormId']) && empty($_POST['formId'])) && $intCountForms > 1) {
                $intFormId = null;

                //get form selector
                $objForms->listForms($this->_objTpl, 2, $intFormId);

                //parse blocks
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryStatus');
                $this->_objTpl->hideBlock($this->moduleNameLC.'InputfieldList');
                $this->_objTpl->hideBlock($this->moduleNameLC.'SpezfieldList');
            } else {
                //save entry data
                if(isset($_POST['submitEntryModfyForm']) && !empty($_POST['formId'])) {
                    $objEntry = new MediaDirectoryEntry($this->moduleName);
                    $intEntryId = intval($_POST['entryId']);
                    $intEntryId = $objEntry->saveEntry($_POST, $intEntryId);

                    if(!empty($_POST['entryId'])) {
                        if($intEntryId) {
                            $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY'].' '.$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_EDITED'];
                        } else {
                            $intEntryId = intval($_POST['entryId']);
                            $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY'].' '.$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_EDITED'];
                        }
                    } else {
                        if($intEntryId) {
                            $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY'].' '.$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_ADDED'];
                        } else {
                            $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY'].' '.$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_ADDED'];
                        }
                    }
                }

                //get form id
                if(intval($intEntryId) != 0) {
                    //get entry data
                    $objEntry = new MediaDirectoryEntry($this->moduleName);
                    $objEntry->getEntries($intEntryId, null, null, null, null, false,false);

                    if(empty($objEntry->arrEntries)) {
                        $objEntry->getEntries($intEntryId, null, null, null, null, true,false);
                    }

                    $intFormId = $objEntry->arrEntries[$intEntryId]['entryFormId'];

                } else {
                    //set form id
                    if($intCountForms == 1) {
                        $intFormId = intval($arrActiveForms[0]);
                    } else {
                        $intFormId = intval($_POST['selectedFormId']);
                    }

                    if(!empty($_POST['formId'])) {
                        $intFormId = intval($_POST['formId']);
                    }
                }

                //get inputfield object
                $objInputfields = new MediaDirectoryInputfield($intFormId, false, null, $this->moduleName);

                //list inputfields
                $objInputfields->listInputfields($this->_objTpl, 2, $intEntryId);

                //get translation status date
                if($this->arrSettings['settingsTranslationStatus'] == 1) {
                    $ownerRowClass = "row1";

                    foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                        $strLangStatus = '';
                        if($intEntryId != 0) {
                            if(in_array($arrLang['id'], $objEntry->arrEntries[$intEntryId]['entryTranslationStatus'])) {
                                $strLangStatus = 'checked="checked"';
                            }
                        }

                        $this->_objTpl->setVariable(array(
                            'TXT_'.$this->moduleLangVar.'_TRANSLATION_LANG_NAME' => htmlspecialchars($arrLang['name'], ENT_QUOTES, CONTREXX_CHARSET),
                            $this->moduleLangVar.'_TRANSLATION_LANG_ID' => intval($arrLang['id']),
                            $this->moduleLangVar.'_TRANSLATION_LANG_STATUS' => $strLangStatus,
                        ));

                        $this->_objTpl->parse($this->moduleNameLC.'TranslationLangList');
                    }

                    $this->_objTpl->parse($this->moduleNameLC.'TranslationStatus');
                } else {
                    $ownerRowClass = "row2";
                    $this->_objTpl->hideBlock($this->moduleNameLC.'TranslationStatus');
                }

                //get user data
                $objFWUser = \FWUser::getFWUserObject();
                $addedBy   = isset($objEntry) ? $objEntry->arrEntries[$intEntryId]['entryAddedBy'] : '';
                if (!empty($addedBy) && $objUser = $objFWUser->objUser->getUser($addedBy)) {
                    $userId  = $objUser->getId();
                } else {
                    $userId  = $objFWUser->objUser->getId();
                }

                $this->_objTpl->setVariable(array(
                    'TXT_'.$this->moduleLangVar.'_OWNER' => $_ARRAYLANG['TXT_MEDIADIR_OWNER'],
                    $this->moduleLangVar.'_OWNER_ROW'    => $ownerRowClass,
                    $this->moduleLangVar.'_OWNER_ID'     => $userId,
                ));

                \FWUser::getUserLiveSearch();

                if ($intEntryId != 0) {
                    $intEntryDourationStart = 1;
                    $intEntryDourationEnd = 2;

                    //parse contact data
                    $objUser     = $objFWUser->objUser;
                    $intUserId   = intval($objUser->getId());
                    $strUserMail = '<a href="mailto:'.contrexx_raw2xhtml($objUser->getEmail()).'">'.contrexx_raw2xhtml($objUser->getEmail()).'</a>';
                    $intUserLang = intval($objUser->getFrontendLanguage());

                    if ($objUser = $objUser->getUser($id = $intUserId)) {
                        //get lang
                        foreach ($this->arrFrontendLanguages as $intKey => $arrLang) {
                                if($arrLang['id'] == $intUserLang) {
                                        $strUserLang = $arrLang['name'];
                                }
                        }

                        //get country
                        $arrCountry = \Cx\Core\Country\Controller\Country::getById(intval($objUser->getProfileAttribute('country')));
                        $strCountry = $arrCountry['name'];

                        //get title
                        $objTitle = $objDatabase->Execute("SELECT `title` FROM ".DBPREFIX."access_user_title WHERE id = '".intval($objUser->getProfileAttribute('title'))."' LIMIT 1");
                        $strTitle = $objTitle->fields['title'];

                        $this->_objTpl->setVariable(array(
                            'TXT_'.$this->moduleLangVar.'_CONTACT_DATA' => "Kontaktangaben",
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_TITLE' => contrexx_raw2xhtml($strTitle),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_FIRSTNAME' => contrexx_raw2xhtml($objUser->getProfileAttribute('firstname')),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_LASTNAME' => contrexx_raw2xhtml($objUser->getProfileAttribute('lastname')),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_COMPANY' => contrexx_raw2xhtml($objUser->getProfileAttribute('company')),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_ADRESS' => contrexx_raw2xhtml($objUser->getProfileAttribute('address')),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_CITY' => contrexx_raw2xhtml($objUser->getProfileAttribute('city')),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_ZIP' => contrexx_raw2xhtml($objUser->getProfileAttribute('zip')),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_COUNTRY' => contrexx_raw2xhtml($strCountry),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_PHONE' => contrexx_raw2xhtml($objUser->getProfileAttribute('phone_office')),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_FAX' => contrexx_raw2xhtml($objUser->getProfileAttribute('phone_fax')),
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_WEBSITE' => '<a href="'.contrexx_raw2xhtml($objUser->getProfileAttribute('website')).'" target="_blank">'.contrexx_raw2xhtml($objUser->getProfileAttribute('website')).'</a>',
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_MAIL' => $strUserMail,
                            $this->moduleLangVar.'_CONTACT_ATTRIBUT_LANG' => $strUserLang,
                        ));
                    }

                    $this->_objTpl->parse($this->moduleNameLC.'ContactData');
                } else {
                    $intEntryDourationStart = 1;
                    $intEntryDourationEnd = 2;
                    $this->_objTpl->hideBlock($this->moduleNameLC.'ContactData');
                }

                //get display duration  data
                switch($this->arrSettings['settingsEntryDisplaydurationValueType']) {
                    case 1:
                        $intDiffDay = $this->arrSettings['settingsEntryDisplaydurationValue'];
                        $intDiffMonth = 0;
                        $intDiffYear = 0;
                        break;
                    case 2:
                        $intDiffDay = 0;
                        $intDiffMonth = $this->arrSettings['settingsEntryDisplaydurationValue'];
                        $intDiffYear = 0;
                        break;
                    case 3:
                        $intDiffDay = 0;
                        $intDiffMonth = 0;
                        $intDiffYear = $this->arrSettings['settingsEntryDisplaydurationValue'];
                        break;
                }

                if($intEntryId != 0) {
                    if(intval($objEntry->arrEntries[$intEntryId]['entryDurationType']) == 1) {
                        $intEntryDourationAlways = 'selected="selected"';
                        $intEntryDourationStart = date("d.m.Y", time());
                        $intEntryDourationEnd = date("d.m.Y", mktime(0,0,0,date("m")+$intDiffMonth,date("d")+$intDiffDay,date("Y")+$intDiffYear));
                    } else {
                        $intEntryDourationPeriod = 'selected="selected"';
                        $intEntryDourationShowPeriod = 'inline';
                        $intEntryDourationStart = date("d.m.Y", $objEntry->arrEntries[$intEntryId]['entryDurationStart']);
                        $intEntryDourationEnd = date("d.m.Y", $objEntry->arrEntries[$intEntryId]['entryDurationEnd']);
                    }

                    if(intval($objEntry->arrEntries[$intEntryId]['entryDurationNotification']) == 1) {
                        $this->_objTpl->setVariable(array(
                            $this->moduleLangVar.'_DISPLAYDURATION_RESET_NOTIFICATION_STATUS' => '<br /><input type="checkbox" name="durationResetNotification" value="1" />&nbsp;'.$_ARRAYLANG['TXT_MEDIADIR_DISPLAYDURATION_RESET_NOTIFICATION_STATUS'],
                        ));
                    }
                } else {
                    if(intval($this->arrSettings['settingsEntryDisplaydurationType']) == 1) {
                        $intEntryDourationAlways = 'selected="selected"';
                    } else {
                        $intEntryDourationPeriod = 'selected="selected"';
                        $intEntryDourationShowPeriod = 'inline';
                    }

                    $intEntryDourationStart = date("d.m.Y", time());
                    $intEntryDourationEnd = date("d.m.Y", mktime(0,0,0,date("m")+$intDiffMonth,date("d")+$intDiffDay,date("Y")+$intDiffYear));
                }

                if ($intEntryId != 0) {
                    \ContrexxJavascript::getInstance()->setVariable(
                        'slugFieldId',
                        $objEntry->arrEntries[$intEntryId]['slug_field_id'],
                        'Mediadir'
                    );
                }

                //parse spez fields
                $this->_objTpl->touchBlock($this->moduleNameLC.'SpezfieldList');

                //generate javascript
                parent::setJavascript($this->getSelectorJavascript());
                parent::setJavascript($objInputfields->getInputfieldJavascript());

                //get form onsubmit
                $strOnSubmit = parent::getFormOnSubmit($objInputfields->arrJavascriptFormOnSubmit);

                $this->_objTpl->setVariable(array(
                    $this->moduleLangVar.'_ENTRY_STATUS' =>($intEntryId && intval($objEntry->arrEntries[$intEntryId]['entryActive']) ? 'checked="checked"' : ''),
                    $this->moduleLangVar.'_MEDIABROWSER_BUTTON' => $this->getMediaBrowserButton(
                        $_ARRAYLANG['TXT_BROWSE'],
                        array(
                            'type'  => 'button',
                            'id'    => 'mediabrowser_button',
                            'style' => 'display:none;'
                        )
                    ),
                ));

                //parse blocks
                $this->_objTpl->hideBlock($this->moduleNameLC.'FormList');

                if ($objForms->arrForms[$intFormId]['use_associated_entries']) {
                    \JS::activate('chosen-sortable');
                    if (!$objEntry) {
                        $objEntry = new MediaDirectoryEntry($this->moduleName);
                    }
                    $this->_objTpl->setVariable(array(
                        'TXT_' . $this->moduleLangVar . '_ASSOCIATED_ENTRIES' =>
                            $_ARRAYLANG['TXT_MEDIADIR_ASSOCIATED_ENTRIES'],
                        'TXT_' . $this->moduleLangVar . '_PLEASE_CHOOSE' =>
                            $_ARRAYLANG['TXT_MEDIADIR_PLEASE_CHOOSE'],
                        'TXT_' . $this->moduleLangVar . '_SELECT_NO_MATCH' =>
                            $_ARRAYLANG['TXT_MEDIADIR_SELECT_NO_MATCH'],
                        'TXT_' . $this->moduleLangVar . '_ASSOCIATED_ENTRIES_INFO' =>
                            $_ARRAYLANG['TXT_MEDIADIR_ASSOCIATED_ENTRIES_INFO'],
                        $this->moduleLangVar . '_ASSOCIATED_ENTRIES_OPTIONS' =>
                            $objEntry->getAssociatedEntriesOptions(
                                $intFormId, $intEntryId),
                    ));
                }
            }

            //parse global variables
            $this->_objTpl->setGlobalVariable(array(
                'TXT_'.$this->moduleLangVar.'_PAGE_TITLE' => $pageTitle,
                $this->moduleLangVar.'_ENTRY_ID' => $intEntryId,
                $this->moduleLangVar.'_FORM_ID' => $intFormId,
                'TXT_'.$this->moduleLangVar.'_SUBMIT' =>  $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
                $this->moduleLangVar.'_JAVASCRIPT' =>  $this->getJavascript(),
                $this->moduleLangVar.'_FORM_ONSUBMIT' =>  $strOnSubmit,
                'TXT_'.$this->moduleLangVar.'_PLEASE_CHECK_INPUT' =>  $_ARRAYLANG['TXT_MEDIADIR_PLEASE_CHECK_INPUT'],
                $this->moduleLangVar.'_DEFAULT_LANG_ID' =>  static::getOutputLocale()->getId(),
                'TXT_'.$this->moduleLangVar.'_SPEZ_FIELDS' => $_ARRAYLANG['TXT_MEDIADIR_SPEZ_FIELDS'],
                'TXT_'.$this->moduleLangVar.'_DISPLAYDURATION' =>  $_ARRAYLANG['TXT_MEDIADIR_DISPLAYDURATION'],
                'TXT_'.$this->moduleLangVar.'_DISPLAYDURATION_ALWAYS' =>  $_ARRAYLANG['TXT_MEDIADIR_DISPLAYDURATION_ALWAYS'],
                'TXT_'.$this->moduleLangVar.'_DISPLAYDURATION_PERIOD' =>  $_ARRAYLANG['TXT_MEDIADIR_DISPLAYDURATION_PERIOD'],
                'TXT_'.$this->moduleLangVar.'_DISPLAYDURATION_FROM' =>  $_CORELANG['TXT_FROM'],
                'TXT_'.$this->moduleLangVar.'_DISPLAYDURATION_TO' =>  $_CORELANG['TXT_TO'],
                $this->moduleLangVar.'_DISPLAYDURATION_START' =>  $intEntryDourationStart,
                $this->moduleLangVar.'_DISPLAYDURATION_END' =>  $intEntryDourationEnd,
                $this->moduleLangVar.'_DISPLAYDURATION_SELECT_ALWAYS' =>  $intEntryDourationAlways,
                $this->moduleLangVar.'_DISPLAYDURATION_SELECT_PERIOD' =>  $intEntryDourationPeriod,
                $this->moduleLangVar.'_DISPLAYDURATION_SHOW_PERIOD' =>  $intEntryDourationShowPeriod,
                'TXT_'.$this->moduleLangVar.'_TRANSLATION_STATUS' => $_ARRAYLANG['TXT_MEDIADIR_TRANSLATION_STATUS'],
                'TXT_'.$this->moduleLangVar.'_ENTRY_STATUS' => $_ARRAYLANG['TXT_MEDIADIR_ACTIVE'],
            ));
        } else {
            \Cx\Core\Csrf\Controller\Csrf::header("Location: index.php?cmd=".$this->moduleName."&act=settings&tpl=forms");
            exit;
        }
    }



    function modifyCategory()
    {
        \Permission::checkAccess(MediaDirectoryAccessIDs::ManageCategories, 'static');
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_modify_category.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_MEDIADIR_CATEGORIES'];

        //get category object
        $objCategories = new MediaDirectoryCategory(null, null, 1, $this->moduleName);

        //save category data
        if(isset($_POST['submitCategoryModfyForm'])) {
            $status = $objCategories->saveCategory($_POST, intval($_POST['categoryId']));
            $objCategories->loadCategories();

            if(!empty($_POST['categoryId'])) {
                if($status == true) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_EDITED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_EDITED'];
                }
            } else {
                if($status == true) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_ADDED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_ADDED'];
                }
            }
        }

        //load category data
        if(isset($_GET['id']) && $_GET['id'] != 0) {
            $pageTitle = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']. " ".$_ARRAYLANG['TXT_MEDIADIR_EDIT'];
            $intCategoryId = intval($_GET['id']);

            $objCategory = new MediaDirectoryCategory($intCategoryId, null, 0, $this->moduleName);

            if($objCategory->arrCategories[$intCategoryId]['catShowEntries'] == 1) {
                $showEntriesOn = 'checked="checked"';
                $showEntriesOff = '';
            } else {
                $showEntriesOn = '';
                $showEntriesOff = 'checked="checked"';
            }

            if($objCategory->arrCategories[$intCategoryId]['catShowSubcategories'] == 1) {
                $showCategoriesOn = 'checked="checked"';
                $showCategoriesOff = '';
            } else {
                $showCategoriesOn = '';
                $showCategoriesOff = 'checked="checked"';
            }

            if($objCategory->arrCategories[$intCategoryId]['catActive'] == 1) {
                $activeOn = 'checked="checked"';
                $activeOff = '';
            } else {
                $activeOn = '';
                $activeOff = 'checked="checked"';
            }

            $cx         = \Cx\Core\Core\Controller\Cx::instanciate();
            $catPicture =  !empty($objCategory->arrCategories[$intCategoryId]['catPicture'])
                         ? $objCategory->arrCategories[$intCategoryId]['catPicture']
                         : '';
            if(empty($catPicture) || !file_exists($cx->getWebsitePath().$catPicture)) {
                $catImage = '<img src="'. $cx->getCodeBaseOffsetPath() .'images/MediaDir/no_picture.gif" style="border: 1px solid #0A50A1; margin: 0px 0px 3px 0px;" /><br />';
            } else {
                $thumbnail = $this->getThumbImage($catPicture);
                $catImage  = '<img src="'. $thumbnail .'" style="border: 1px solid #0A50A1; margin: 0px 0px 3px 0px;" /><br />';
            }

            //parse data variables
            $this->_objTpl->setGlobalVariable(array(
                $this->moduleLangVar.'_CATEGORY_ID' => $intCategoryId,
                $this->moduleLangVar.'_CATEGORY_NAME_MASTER' => $objCategory->arrCategories[$intCategoryId]['catName'][0],
                $this->moduleLangVar.'_CATEGORY_DESCRIPTION_MASTER' => $objCategory->arrCategories[$intCategoryId]['catDescription'][0],
                $this->moduleLangVar.'_CATEGORY_PICTURE' => $objCategory->arrCategories[$intCategoryId]['catPicture'],
                $this->moduleLangVar.'_CATEGORY_SHOW_ENTRIES_ON' => $showEntriesOn,
                $this->moduleLangVar.'_CATEGORY_SHOW_ENTRIES_OFF' => $showEntriesOff,
                $this->moduleLangVar.'_CATEGORY_ACTIVE_ON' => $activeOn,
                $this->moduleLangVar.'_CATEGORY_ACTIVE_OFF' => $activeOff,
                $this->moduleLangVar.'_CATEGORY_PICTURE_THUMB' => $catImage,
                $this->moduleLangVar.'_CATEGORY_SHOW_SUBCATEGORIES_ON' => $showCategoriesOn,
                $this->moduleLangVar.'_CATEGORY_SHOW_SUBCATEGORIES_OFF' => $showCategoriesOff,
            ));
        } else {
            $pageTitle = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY']. " ".$_ARRAYLANG['TXT_MEDIADIR_ADD'];
            $intCategoryId = null;

            //parse global variables
            $this->_objTpl->setGlobalVariable(array(
                $this->moduleLangVar.'_CATEGORY_SHOW_ENTRIES_ON' => 'checked="checked"',
                $this->moduleLangVar.'_CATEGORY_SHOW_SUBCATEGORIES_ON' => 'checked="checked"',
                $this->moduleLangVar.'_CATEGORY_ACTIVE_ON' => 'checked="checked"',
            ));
        }

        //get category dropdown
        $catDropdown = $objCategories->listCategories($this->_objTpl, 3, $intCategoryId);

        //parse global variables
        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_ACTIVATE' =>  $_ARRAYLANG['TXT_MEDIADIR_ACTIVATE'],
            'TXT_'.$this->moduleLangVar.'_DEACTIVATE' =>  $_ARRAYLANG['TXT_MEDIADIR_DEAVTIVATE'],
            'TXT_'.$this->moduleLangVar.'_NAME' =>  $_CORELANG['TXT_NAME'],
            'TXT_'.$this->moduleLangVar.'_DESCRIPTION' =>  $_CORELANG['TXT_DESCRIPTION'],
            'TXT_'.$this->moduleLangVar.'_META_DESCRIPTION' =>  $_CORELANG['TXT_META_DESCRIPTION'],
            'TXT_'.$this->moduleLangVar.'_PICTURE' =>  $_CORELANG['TXT_IMAGE'],
            'TXT_'.$this->moduleLangVar.'_SHOW_SUBCATEGORIES' =>  $_ARRAYLANG['TXT_MEDIADIR_SHOW_SUBCATEGORIES'],
            'TXT_'.$this->moduleLangVar.'_SHOW_SUBCATEGORIES_INFO' =>  $_ARRAYLANG['TXT_MEDIADIR_SHOW_SUBCATEGORIES_INFO'],
            'TXT_'.$this->moduleLangVar.'_SHOW_ENTRIES' =>  $_ARRAYLANG['TXT_MEDIADIR_SHOW_ENTRIES'],
            'TXT_'.$this->moduleLangVar.'_VISIBLE' =>  $_CORELANG['TXT_VISIBLE'],
            'TXT_'.$this->moduleLangVar.'_CATEGORY' =>  $_ARRAYLANG['TXT_MEDIADIR_CATEGORY'],
            'TXT_'.$this->moduleLangVar.'_PAGE_TITLE' =>  $pageTitle,
            'TXT_'.$this->moduleLangVar.'_BROWSE' =>  $_CORELANG['TXT_BROWSE'],
            'TXT_'.$this->moduleLangVar.'_MORE' =>  $_ARRAYLANG['TXT_MEDIADIR_MORE'],
            'TXT_'.$this->moduleLangVar.'_SUBMIT' =>  $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
            'TXT_'.$this->moduleLangVar.'_NEW_CATEGORY' =>  "--- ".$_ARRAYLANG['TXT_MEDIADIR_NEW_CATEGORY']." ---",
            'TXT_'.$this->moduleLangVar.'_VISIBLE_CATEGORY_INFO' =>  $_ARRAYLANG['TXT_MEDIADIR_VISIBLE_CATEGORY_INFO'],
            $this->moduleLangVar.'_CATEGORIES_DROPDOWN_OPTIONS' => $catDropdown,
            $this->moduleLangVar.'_CATEGORY_DEFAULT_LANG_ID' => static::getOutputLocale()->getId(),
            'TXT_'.$this->moduleLangVar.'_BASIC_DATA' => $_ARRAYLANG['TXT_MEDIADIR_BASIC_DATA'],
            'TXT_'.$this->moduleLangVar.'_CATEGORY_DETAILS' => $_ARRAYLANG['TXT_MEDIADIR_CATEGORY_DETAILS'],
            $this->moduleLangVar.'_CATEGORY_IMAGE_BROWSE' => $this->getMediaBrowserButton(
                $_ARRAYLANG['TXT_BROWSE'],
                array(
                    'views' => 'filebrowser',
                    'type' => 'button',
                    'data-input-id' => 'categoryImage2'
                ),
                'mediaBrowserCallback'
            ),
        ));

        if (count($this->arrFrontendLanguages) == 1) {
            $this->_objTpl->setVariable($this->moduleLangVar.'_HIDE_ON_SINGLE_LANG', "display:none;");
        }
        //category name language block
        $first = true;
        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
            if(isset($intCategoryId)){
                $strCategoryName = empty($objCategory->arrCategories[$intCategoryId]['catName'][$arrLang['id']]) ? $objCategory->arrCategories[$intCategoryId]['catName'][0] : $objCategory->arrCategories[$intCategoryId]['catName'][$arrLang['id']];
            } else {
                $strCategoryName = '';
            }
            //category description language block
            if(isset($intCategoryId)){
                $strCategoryDescription = empty($objCategory->arrCategories[$intCategoryId]['catDescription'][$arrLang['id']]) ? $objCategory->arrCategories[$intCategoryId]['catDescription'][0] : $objCategory->arrCategories[$intCategoryId]['catDescription'][$arrLang['id']];
            } else {
                $strCategoryDescription = '';
            }
            //category meta description
            if (isset($intCategoryId)) {
                $categoryMetaDescription = empty($objCategory->arrCategories[$intCategoryId]['catMetaDesc'][$arrLang['id']]) ? $objCategory->arrCategories[$intCategoryId]['catMetaDesc'][0] : $objCategory->arrCategories[$intCategoryId]['catMetaDesc'][$arrLang['id']];
            } else {
                $categoryMetaDescription = '';
            }

            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_CATEGORY_LANG_ID' => $arrLang['id'],
                $this->moduleLangVar.'_CATEGORY_NAME' => $strCategoryName,
                $this->moduleLangVar.'_CATEGORY_DESCRIPTION' => new \Cx\Core\Wysiwyg\Wysiwyg("categoryDescription[{$arrLang['id']}]", $strCategoryDescription),
                $this->moduleLangVar.'_CATEGORY_META_DESCRIPTION' => $categoryMetaDescription,
                $this->moduleLangVar.'_CATEGORY_BLOCK_DISPLAY' => $first ? 'display:block;' : 'display:none;'
            ));

            $this->_objTpl->parse($this->moduleNameLC.'_category_name_and_description');

            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_CATEGORY_LANG_ID'   => $arrLang['id'],
                $this->moduleLangVar.'_CATEGORY_LANG_NAME' => $arrLang['name'],
                $this->moduleLangVar.'_CATEGORY_LANG_TAB_CLASS' => $first ? 'active' : 'inactive',
            ));
            $this->_objTpl->parse($this->moduleNameLC.'CategoryLanguages');

            $first = false;
        }

    }



    /**
     * Switch the state of an entry (active or inactive)
     * This function is called through ajax, hence the 'die' at the end.
     */
    function switchState()
    {
        \Permission::checkAccess(MediaDirectoryAccessIDs::ModifyEntry, 'static');
        global $objDatabase;

        if (!isset($_GET['id']) && !isset($_GET['state']) && !isset($_GET['type'])) {
            die();
        }

        $intId = intval($_GET['id']);
        $intState = intval($_GET['state']);

        switch ($_GET['type']){
            case 'category':
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_categories SET active = '".$intState."' WHERE id = ".$intId;
                break;
            case 'level':
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_levels SET active = '".$intState."' WHERE id = ".$intId;
                break;
            case 'mail_template':
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_mails SET active = '".$intState."' WHERE id = ".$intId;
                break;
            case 'form_template':
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_forms SET active = '".$intState."' WHERE id = ".$intId;
                break;
            case 'mask':
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_masks SET active = '".$intState."' WHERE id = ".$intId;
                break;
            case 'entry':
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_entries SET active = '".$intState."' WHERE id = ".$intId;
                break;
        }

        $objDatabase->Execute($query);

        die();
    }



    function modifyLevel()
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        \Permission::checkAccess(MediaDirectoryAccessIDs::ManageLevels, 'static');

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_modify_level.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_MEDIADIR_LEVELS'];

        //get level object
        $objLevels = new MediaDirectoryLevel(null, null, 1, $this->moduleName);

        //save level data
        if(isset($_POST['submitLevelModfyForm'])) {
            $status = $objLevels->saveLevel($_POST, intval($_POST['levelId']));

            if(!empty($_POST['levelId'])) {
                if($status == true) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_LEVEL']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_EDITED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_LEVEL']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_EDITED'];
                }
            } else {
                if($status == true) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_LEVEL']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_ADDED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_LEVEL']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_ADDED'];
                }
            }
        }

        //load level dat
        if(isset($_GET['id']) && $_GET['id'] != 0) {
            $pageTitle = $_ARRAYLANG['TXT_MEDIADIR_LEVEL']. " ".$_ARRAYLANG['TXT_MEDIADIR_EDIT'];
            $intLevelId = intval($_GET['id']);

            $objLevel = new MediaDirectoryLevel($intLevelId, null, 0, $this->moduleName);

            if($objLevel->arrLevels[$intLevelId]['levelShowEntries'] == 1) {
                $showEntriesOn = 'checked="checked"';
                $showEntriesOff = '';
            } else {
                $showEntriesOn = '';
                $showEntriesOff = 'checked="checked"';
            }

            if($objLevel->arrLevels[$intLevelId]['levelShowSublevels'] == 1) {
                $showSublevelsOn = 'checked="checked"';
                $showSublevelsOff = '';
            } else {
                $showSublevelsOn = '';
                $showSublevelsOff = 'checked="checked"';
            }

            if($objLevel->arrLevels[$intLevelId]['levelShowCategories'] == 1) {
                $showCategoriesOn = 'checked="checked"';
                $showCategoriesOff = '';
            } else {
                $showCategoriesOn = '';
                $showCategoriesOff = 'checked="checked"';
            }

            if($objLevel->arrLevels[$intLevelId]['levelActive'] == 1) {
                $activeOn = 'checked="checked"';
                $activeOff = '';
            } else {
                $activeOn = '';
                $activeOff = 'checked="checked"';
            }

            $cx           = \Cx\Core\Core\Controller\Cx::instanciate();
            $levelPicture =  !empty($objLevel->arrLevels[$intLevelId]['levelPicture'])
                           ? $objLevel->arrLevels[$intLevelId]['levelPicture']
                           : '';
            if(empty($levelPicture) || !file_exists($cx->getWebsitePath().$levelPicture)) {
                $levelImage = '<img src="'. $cx->getCodeBaseOffsetPath() .'images/MediaDir/no_picture.gif" style="border: 1px solid #0A50A1; margin: 0px 0px 3px 0px;" /><br />';
            } else {
                $thumbnail = $this->getThumbImage($levelPicture);
                $levelImage = '<img src="'. $thumbnail .'" style="border: 1px solid #0A50A1; margin: 0px 0px 3px 0px;" /><br />';
            }

            //parse data variables
            $this->_objTpl->setGlobalVariable(array(
                $this->moduleLangVar.'_LEVEL_ID' => $intLevelId,
                $this->moduleLangVar.'_LEVEL_NAME_MASTER' => contrexx_raw2xhtml($objLevel->arrLevels[$intLevelId]['levelName'][0]),
                $this->moduleLangVar.'_LEVEL_DESCRIPTION_MASTER' => contrexx_raw2xhtml($objLevel->arrLevels[$intLevelId]['levelDescription'][0]),
                $this->moduleLangVar.'_LEVEL_PICTURE' => $objLevel->arrLevels[$intLevelId]['levelPicture'],
                $this->moduleLangVar.'_LEVEL_SHOW_ENTRIES_ON' => $showEntriesOn,
                $this->moduleLangVar.'_LEVEL_SHOW_ENTRIES_OFF' => $showEntriesOff,
                $this->moduleLangVar.'_LEVEL_ACTIVE_ON' => $activeOn,
                $this->moduleLangVar.'_LEVEL_ACTIVE_OFF' => $activeOff,
                $this->moduleLangVar.'_LEVEL_PICTURE_THUMB' => $levelImage,
                $this->moduleLangVar.'_LEVEL_SHOW_SUBLEVELS_ON' => $showSublevelsOn,
                $this->moduleLangVar.'_LEVEL_SHOW_SUBLEVELS_OFF' => $showSublevelsOff,
                $this->moduleLangVar.'_LEVEL_SHOW_CATEGORIES_ON' => $showCategoriesOn,
                $this->moduleLangVar.'_LEVEL_SHOW_CATEGORIES_OFF' => $showCategoriesOff,
            ));
        } else {
            $pageTitle = $_ARRAYLANG['TXT_MEDIADIR_LEVEL']. " ".$_ARRAYLANG['TXT_MEDIADIR_ADD'];
            $intLevelId = null;

            //parse data variables
            $this->_objTpl->setGlobalVariable(array(
                $this->moduleLangVar.'_LEVEL_SHOW_ENTRIES_OFF' => 'checked="checked"',
                $this->moduleLangVar.'_LEVEL_SHOW_SUBLEVELS_ON' => 'checked="checked"',
                $this->moduleLangVar.'_LEVEL_SHOW_CATEGORIES_ON' => 'checked="checked"',
                $this->moduleLangVar.'_LEVEL_ACTIVE_ON' => 'checked="checked"',
            ));
        }

        //parse global variables
        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_ACTIVATE' =>  $_ARRAYLANG['TXT_MEDIADIR_ACTIVATE'],
            'TXT_'.$this->moduleLangVar.'_DEACTIVATE' =>  $_ARRAYLANG['TXT_MEDIADIR_DEAVTIVATE'],
            'TXT_'.$this->moduleLangVar.'_NAME' =>  $_CORELANG['TXT_NAME'],
            'TXT_'.$this->moduleLangVar.'_DESCRIPTION' =>  $_CORELANG['TXT_DESCRIPTION'],
            'TXT_'.$this->moduleLangVar.'_META_DESCRIPTION' =>  $_CORELANG['TXT_META_DESCRIPTION'],
            'TXT_'.$this->moduleLangVar.'_PICTURE' =>  $_CORELANG['TXT_IMAGE'],
            'TXT_'.$this->moduleLangVar.'_SHOW_SUBLEVELS' =>  $_ARRAYLANG['TXT_MEDIADIR_SHOW_SUBLEVELS'],
            'TXT_'.$this->moduleLangVar.'_SHOW_CATEGORIES' =>  $_ARRAYLANG['TXT_MEDIADIR_SHOW_CATEGORIES'],
            'TXT_'.$this->moduleLangVar.'_SHOW_ENTRIES' =>  $_ARRAYLANG['TXT_MEDIADIR_SHOW_ENTRIES'],
            'TXT_'.$this->moduleLangVar.'_VISIBLE' =>  $_CORELANG['TXT_VISIBLE'],
            'TXT_'.$this->moduleLangVar.'_LEVEL' =>  $_ARRAYLANG['TXT_MEDIADIR_LEVEL'],
            'TXT_'.$this->moduleLangVar.'_PAGE_TITLE' =>  $pageTitle,
            'TXT_'.$this->moduleLangVar.'_BROWSE' =>  $_CORELANG['TXT_BROWSE'],
            'TXT_'.$this->moduleLangVar.'_MORE' =>  $_ARRAYLANG['TXT_MEDIADIR_MORE'],
            'TXT_'.$this->moduleLangVar.'_SUBMIT' =>  $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
            'TXT_'.$this->moduleLangVar.'_NEW_LEVEL' =>  "--- ".$_ARRAYLANG['TXT_MEDIADIR_NEW_LEVEL']." ---",
            'TXT_'.$this->moduleLangVar.'_VISIBLE_LEVEL_INFO' =>  $_ARRAYLANG['TXT_MEDIADIR_VISIBLE_LEVEL_INFO'],
            $this->moduleLangVar.'_LEVEL_DEFAULT_LANG_ID' => static::getOutputLocale()->getId(),
            'TXT_'.$this->moduleLangVar.'_BASIC_DATA' => $_ARRAYLANG['TXT_MEDIADIR_BASIC_DATA'],
            'TXT_'.$this->moduleLangVar.'_LEVEL_DETAILS' => $_ARRAYLANG['TXT_MEDIADIR_LEVEL_DETAILS'],
            $this->moduleLangVar.'_LEVEL_IMAGE_BROWSE' => $this->getMediaBrowserButton(
                $_ARRAYLANG['TXT_BROWSE'],
                array(
                    'views' => 'filebrowser',
                    'type' => 'button',
                    'data-input-id' => 'levelImage2'
                ),
                'mediaBrowserCallback'
            ),
        ));

        //get level dropdown
        $levelDropdown = $objLevels->listLevels($this->_objTpl, 3, $intLevelId);

        if (count($this->arrFrontendLanguages) == 1) {
            $this->_objTpl->setVariable($this->moduleLangVar.'_HIDE_ON_SINGLE_LANG', "display:none;");
        }
        //level name language block
        $first = true;
        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
            if(isset($intLevelId)){
                $strLevelName = empty($objLevel->arrLevels[$intLevelId]['levelName'][$arrLang['id']]) ? $objLevel->arrLevels[$intLevelId]['levelName'][0] : $objLevel->arrLevels[$intLevelId]['levelName'][$arrLang['id']];
            } else {
                $strLevelName = '';
            }
            //level description language
            if(isset($intLevelId)){
                $strLevelDescription = empty($objLevel->arrLevels[$intLevelId]['levelDescription'][$arrLang['id']]) ? $objLevel->arrLevels[$intLevelId]['levelDescription'][0] : $objLevel->arrLevels[$intLevelId]['levelDescription'][$arrLang['id']];
            } else {
                $strLevelDescription = '';
            }
            //level meta description
            if (isset($intLevelId)) {
                $levelMetaDescription = empty($objLevel->arrLevels[$intLevelId]['levelMetaDesc'][$arrLang['id']]) ? $objLevel->arrLevels[$intLevelId]['levelMetaDesc'][0] : $objLevel->arrLevels[$intLevelId]['levelMetaDesc'][$arrLang['id']];
            } else {
                $levelMetaDescription = '';
            }

            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_LEVEL_LANG_ID' => $arrLang['id'],
                $this->moduleLangVar.'_LEVEL_NAME' => $strLevelName,
                $this->moduleLangVar.'_LEVELS_DROPDOWN_OPTIONS' => $levelDropdown,
                $this->moduleLangVar.'_LEVEL_DESCRIPTION' => new \Cx\Core\Wysiwyg\Wysiwyg("levelDescription[{$arrLang['id']}]", contrexx_raw2xhtml($strLevelDescription)),
                $this->moduleLangVar.'_LEVEL_META_DESCRIPTION' => $levelMetaDescription,
                $this->moduleLangVar.'_LEVEL_BLOCK_DISPLAY' => $first ? 'display:block;' : 'display:none;'
            ));

            $this->_objTpl->parse($this->moduleNameLC.'_level_name_and_description');

            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_LEVEL_LANG_ID'   => $arrLang['id'],
                $this->moduleLangVar.'_LEVEL_LANG_NAME' => $arrLang['name'],
                $this->moduleLangVar.'_LEVEL_LANG_TAB_CLASS' => $first ? 'active' : 'inactive',
            ));
            $this->_objTpl->parse($this->moduleNameLC.'LevelLanguages');

            $first = false;
        }

    }



    /**
     * Switch the state of an entry (active or inactive)
     * This function is called through ajax, hence the 'die' at the end.
     */
    function switchLevelState()
    {
        global $objDatabase;

        \Permission::checkAccess(MediaDirectoryAccessIDs::ManageLevels, 'static');

        if (!isset($_GET['levelid']) && !isset($_GET['state'])) {
            die();
        }

        $intId = intval($_GET['levelid']);
        $intState = intval($_GET['state']);

        $query = "  UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_levels
                    SET active = '".$intState."'
                    WHERE id = ".$intId;
        $objDatabase->Execute($query);

        die();
    }

    /**
     * check the filter session
     * if the filter session is not set, initially assign the null value
     */
    function initFilterSession() {

        if(!isset($_SESSION[$this->moduleName])){
            $_SESSION[$this->moduleName] = array();
        }

        if (!isset($_SESSION[$this->moduleName]['searchFilter'])) {
            $_SESSION[$this->moduleName]['searchFilter'] = array(
                            'cat_id'    => null,
                            'level_id'  => null,
                            'form_id'   => null,
                            'term'      => null
            );
        }
    }

    function manageEntries()
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_manage_entries.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_MEDIADIR_MANAGE_ENTRIES'];

        $this->initFilterSession();

        if(isset($_REQUEST['cat_id'])) {
            $_SESSION[$this->moduleName]['searchFilter']['cat_id'] = intval($_REQUEST['cat_id']);
        }
        if(isset($_REQUEST['level_id'])) {
            $_SESSION[$this->moduleName]['searchFilter']['level_id'] = intval($_REQUEST['level_id']);
        }

        if(isset($_REQUEST['form_id'])) {
            $_SESSION[$this->moduleName]['searchFilter']['form_id'] = intval($_REQUEST['form_id']);
        }

        if(isset($_REQUEST['term'])){
            $_SESSION[$this->moduleName]['searchFilter']['term'] = ($_REQUEST['term'] != $_ARRAYLANG['TXT_MEDIADIR_ID_OR_SEARCH_TERM']) ?  $_REQUEST['term'] : null;
        }

        //assign the searchFilter session values to corresponding variables
        $intCategoryId = $_SESSION[$this->moduleName]['searchFilter']['cat_id'];
        $intLevelId    = $_SESSION[$this->moduleName]['searchFilter']['level_id'];
        $intFormId     = $_SESSION[$this->moduleName]['searchFilter']['form_id'];
        $strTerm       = $_SESSION[$this->moduleName]['searchFilter']['term'];

        $objCategories = new MediaDirectoryCategory(null, null, 1, $this->moduleName);
        $catDropdown = $objCategories->listCategories(null, 3, $intCategoryId);

        $objLevels = new MediaDirectoryLevel(null, null, 1, $this->moduleName);
        $levelDropdown = $objLevels->listLevels(null, 3, $intLevelId);

        $objForms = new MediaDirectoryForm(null, $this->moduleName);
        $formDropdown = $objForms->listForms(null, 4, $intFormId);

        //parse global variables
        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_PAGE_TITLE' => $this->pageTitle,
            'TXT_'.$this->moduleLangVar.'_SUBMIT' =>  $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
            // TODO: _FORM_ONSUBMIT not used?
            //$this->moduleLangVar.'_FORM_ONSUBMIT' =>  $strOnSubmit,
            'TXT_EDIT' => $_ARRAYLANG['TXT_MEDIADIR_EDIT'],
            'TXT_SEARCH' => $_CORELANG['TXT_SEARCH'],
            'TXT_MEDIADIR_STATUS' => $_CORELANG['TXT_STATUS'],
            'TXT_SELECT_ALL' => $_CORELANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL' => $_CORELANG['TXT_DESELECT_ALL'],
            'TXT_SELECT_ACTION' => $_CORELANG['TXT_MULTISELECT_SELECT'],
            'TXT_FUNCTIONS' => $_ARRAYLANG['TXT_MEDIADIR_FUNCTIONS'],
            'TXT_DELETE' => $_ARRAYLANG['TXT_MEDIADIR_DELETE'],
            'TXT_DELETE_ALL' => $_CORELANG['TXT_MULTISELECT_DELETE'],
            'TXT_'.$this->moduleLangVar.'_VOTING' => $_ARRAYLANG['TXT_MEDIADIR_VOTING'],
            'TXT_'.$this->moduleLangVar.'_COMMENTS' => $_ARRAYLANG['TXT_MEDIADIR_COMMENTS'],
            'TXT_'.$this->moduleLangVar.'_NAME' => $_CORELANG['TXT_NAME'],
            'TXT_'.$this->moduleLangVar.'_DATE' => $_CORELANG['TXT_DATE'],
            'TXT_'.$this->moduleLangVar.'_AUTHOR' => $_ARRAYLANG['TXT_MEDIADIR_AUTHOR'],
            'TXT_'.$this->moduleLangVar.'_HITS' => $_ARRAYLANG['TXT_MEDIADIR_HITS'],
            'TXT_'.$this->moduleLangVar.'_ACTION' => $_CORELANG['TXT_HISTORY_ACTION'],
            $this->moduleLangVar.'_SEARCH_TERM' => $strTerm != null ? $strTerm : $_ARRAYLANG['TXT_MEDIADIR_ID_OR_SEARCH_TERM'],
            'TXT_'.$this->moduleLangVar.'_ID_OR_SEARCH_TERM' => $_ARRAYLANG['TXT_MEDIADIR_ID_OR_SEARCH_TERM'],
            $this->moduleLangVar.'_SEARCH_CATEGORY_ID' => $intCategoryId,
            $this->moduleLangVar.'_SEARCH_LEVEL_ID' => $intLevelId,
            'TXT_'.$this->moduleLangVar.'_MOVE_ALL' => $_ARRAYLANG['TXT_MEDIADIR_MOVE_ALL'],
            'TXT_'.$this->moduleLangVar.'_RESTORE_VOTING_ALL' => $_ARRAYLANG['TXT_MEDIADIR_RESTORE_VOTING_ALL'],
            'TXT_'.$this->moduleLangVar.'_RESTORE_COMMENTS_ALL' => $_ARRAYLANG['TXT_MEDIADIR_RESTORE_COMMENTS_ALL'],
            'TXT_'.$this->moduleLangVar.'_CONFIRM_DELETE_DATA' => $_ARRAYLANG['TXT_MEDIADIR_CONFIRM_DELETE_DATA'],
            'TXT_'.$this->moduleLangVar.'_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_MEDIADIR_ACTION_IS_IRREVERSIBLE'],
            'TXT_'.$this->moduleLangVar.'_MAKE_SELECTION' => $_ARRAYLANG['TXT_MEDIADIR_MAKE_SELECTION'],
            'TXT_SELECT_ALL' => $_CORELANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL' => $_CORELANG['TXT_DESELECT_ALL'],
            'TXT_SELECT_ACTION' => $_CORELANG['TXT_MULTISELECT_SELECT'],
            'TXT_DELETE_ALL' => $_CORELANG['TXT_MULTISELECT_DELETE'],
            'TXT_'.$this->moduleLangVar.'_MOVE_ALL' => $_ARRAYLANG['TXT_MEDIADIR_MOVE_ALL'],
            'TXT_'.$this->moduleLangVar.'_ALL_LEVELS' => $_ARRAYLANG['TXT_MEDIADIR_ALL_LEVELS'],
            'TXT_'.$this->moduleLangVar.'_ALL_CATEGORIES' => $_ARRAYLANG['TXT_MEDIADIR_ALL_CATEGORIES'],
            'TXT_'.$this->moduleLangVar.'_ALL_FORMS' => $_ARRAYLANG['TXT_MEDIADIR_ALL_FORMS'],
            $this->moduleLangVar.'_CATEGORIES_DROPDOWN_OPTIONS' => $catDropdown,
            $this->moduleLangVar.'_LEVELS_DROPDOWN_OPTIONS' => $levelDropdown,
            $this->moduleLangVar.'_FORMS_DROPDOWN_OPTIONS' => $formDropdown,
            'TXT_'.$this->moduleLangVar.'_FORM' => $_ARRAYLANG['TXT_MEDIADIR_FORM'],
        ));

        //get seting values
        parent::getSettings();

        if($this->arrSettings['settingsShowLevels'] == 1) {
            $this->_objTpl->touchBlock($this->moduleNameLC.'LevelDropdown');
        } else {
            $this->_objTpl->hideBlock($this->moduleNameLC.'LevelDropdown');
        }

        if(count($objForms->arrForms) > 1) {
            $this->_objTpl->touchBlock($this->moduleNameLC.'FormDropdown');
        } else {
            $this->_objTpl->hideBlock($this->moduleNameLC.'FormDropdown');
        }

        $objEntries = new MediaDirectoryEntry($this->moduleName);

        if(isset($_POST['submitEntriesOrderForm'])) {
            if($objEntries->saveOrder($_POST)){
                $this->strOkMessage = $_CORELANG['TXT_SETTINGS_UPDATED'];
            } else {
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }

        $objSettings = new MediaDirectorySettings($this->moduleName);
        if ($this->_objTpl->blockExists('mediadirTableHeaderComments')) {
            if ($objSettings->arrSettings['settingsAllowComments']) {
                $this->_objTpl->touchBlock('mediadirTableHeaderComments');
            }
        }
        if ($this->_objTpl->blockExists('mediadirTableHeaderVotes')) {
            if ($objSettings->arrSettings['settingsAllowVotes']) {
                $this->_objTpl->touchBlock('mediadirTableHeaderVotes');
            }
        }

        switch ($_GET['act']) {
            case 'move_entry':
                $this->strErrMessage = "Diese Funktion ist zurzeit noch nicht implementiert.";
                break;
            case 'delete_entry':
                \Permission::checkAccess(MediaDirectoryAccessIDs::ModifyEntry, 'static');
                if (!isset($_GET['id'])) {
                    foreach ($_POST["entriesFormSelected"] as $intEntryId) {
                        $strStatus = $objEntries->deleteEntry(intval($intEntryId));
                    }
                } else {
                    $strStatus = $objEntries->deleteEntry(intval($_GET['id']));
                }

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_DELETED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_DELETED'];
                }
                break;
            case 'restore_voting':
                \Permission::checkAccess(MediaDirectoryAccessIDs::ModifyEntry, 'static');
                $objVotes = new MediaDirectoryVoting($this->moduleName);


                if (!isset($_GET['id'])) {
                    foreach ($_POST["entriesFormSelected"] as $intEntryId) {
                        $strStatus = $objVotes->restoreVoting(intval($intEntryId));
                    }
                } else {
                    $strStatus = $objVotes->restoreVoting(intval($_GET['id']));
                }

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_VOTING']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_DELETED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_VOTING']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_DELETED'];
                }
                break;
            case 'restore_comments':
                \Permission::checkAccess(MediaDirectoryAccessIDs::ModifyEntry, 'static');
                $objComments = new MediaDirectoryComment($this->moduleName);


                if (!isset($_GET['id'])) {
                    foreach ($_POST["entriesFormSelected"] as $intEntryId) {
                        $strStatus = $objComments->restoreComments(intval($intEntryId));
                    }
                } else {
                    $strStatus = $objComments->restoreComments(intval($_GET['id']));
                }

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_COMMENTS']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_DELETED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_COMMENTS']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_DELETED'];
                }
                break;
                break;
            case 'confirm_entry':
                \Permission::checkAccess(MediaDirectoryAccessIDs::ModifyEntry, 'static');
                if (!isset($_GET['id'])) {
                    foreach ($_POST["entriesFormSelected"] as $intEntryId) {
                        $strStatus = $objEntries->confirmEntry(intval($intEntryId));
                    }
                } else {
                    $strStatus = $objEntries->confirmEntry(intval($_GET['id']));
                }

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_CONFIRM'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_CONFIRM'];
                }
                break;
        }

        $objEntries->getEntries(null, $intLevelId, $intCategoryId, $strTerm, null, null, null, null, 'n', null, null, $intFormId, null, $this->limit, $this->offset);
        $objEntries->listEntries($this->_objTpl, 1);

        // Paging
        $count  = $objEntries->countEntries();
        $filter = (!empty($strTerm) ? '&term=' . $strTerm : '') .
                  (!empty($intCategoryId) ? '&cat_id=' . $intCategoryId : '') .
                  (!empty($intFormId) ? '&form_id=' . $intFormId : '') .
                  (!empty($intLevelId) ? '&level_id=' . $intLevelId : '');
        $term   = !empty($strTerm) ? '&term=' . $strTerm : '';
        $paging = getPaging($count, $this->offset, '&cmd='.$this->moduleName.'&act=entries'.$filter, '', true);
        $this->_objTpl->setGlobalVariable($this->moduleLangVar . '_PAGING', $paging);

        if (!empty($strTerm)) {
            $this->_objTpl->setVariable($this->moduleLangVar.'_SEARCH_TERM_PARAMETER', '&term='.$strTerm);
        }

        if (empty($objEntries->arrEntries)) {
             $this->_objTpl->hideBlock($this->moduleNameLC.'EntriesSelectAction');
        } else {
             $this->_objTpl->touchBlock($this->moduleNameLC.'EntriesSelectAction');
        }
    }



    function interfaces()
    {
        global $_ARRAYLANG, $_CORELANG;

        \Permission::checkAccess(MediaDirectoryAccessIDs::Interfaces, 'static');

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_interfaces.html',true,true);

        $this->pageTitle = $_ARRAYLANG['TXT_MEDIADIR_INTERFACES'];

        $objInterfaces = new MediaDirectoryInterfaces($this->moduleName);

        $tpl = isset($_GET['tpl']) ? $_GET['tpl'] : '';
        $step = isset($_GET['step']) ? $_GET['step'] : '';

        if(isset($_POST['submitInterfacesForm'])) {
            $strStatus = null;

            switch ($tpl) {
                case 'import':
                    $objImport = new MediaDirectoryImport($this->moduleName);
                    switch ($step) {
                        case 'insertSQL':
                            $strStatus = $objImport->importSQL(contrexx_addslashes($_POST['interfacesImportSqlTable']),contrexx_addslashes($_POST['pairs_left_keys']),contrexx_addslashes($_POST['pairs_right_keys']),intval($_POST['interfacesImportSqlType']),intval($_POST['interfacesImportSqlForm']),intval($_POST['interfacesImportSqlCategory']),intval($_POST['interfacesImportSqlLevel']));
                            break;
                        case 'insertCSV':
                            $strStatus = $objImport->importCSV();
                            break;
                    }
                    break;
                case 'export':
                    $objExport = new MediaDirectoryExport($this->moduleName);
                    switch ($_POST['step']) {
                        case 'exportCSV':
                            $strStatus = $objExport->exportCSV(
                                isset($_POST['interfacesExportForm']) ? contrexx_input2int($_POST['interfacesExportForm']) : 0,
                                isset($_POST['interfacesExportSelectedCategories']) ? contrexx_input2int($_POST['interfacesExportSelectedCategories']) : array(),
                                isset($_POST['interfacesExportSelectedLevels']) ? contrexx_input2int($_POST['interfacesExportSelectedLevels']) : array(),
                                isset($_POST['interfacesExportMask']) ? contrexx_input2int($_POST['interfacesExportMask']) : 0
                            );
                            break;
                    }
            }

            if($strStatus === true){
                $this->strOkMessage = "Ok";
            } else if($strStatus === false) {
                $this->strErrMessage = "Not Ok";
            }
        }

        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_IMPORT' => $_ARRAYLANG['TXT_MEDIADIR_IMPORT'],
            'TXT_'.$this->moduleLangVar.'_EXPORT' => $_ARRAYLANG['TXT_MEDIADIR_EXPORT'],
            'TXT_'.$this->moduleLangVar.'_SUBMIT' => $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
            'TXT_'.$this->moduleLangVar.'_DO_IMPORT' => $_ARRAYLANG['TXT_MEDIADIR_DO_IMPORT'],
            'TXT_'.$this->moduleLangVar.'_DO_EXPORT' => $_ARRAYLANG['TXT_MEDIADIR_DO_EXPORT'],
            'TXT_'.$this->moduleLangVar.'_SELECT_TABLE' => $_ARRAYLANG['TXT_MEDIADIR_SELECT_TABLE'],
            'TXT_'.$this->moduleLangVar.'_DELETE' => $_CORELANG['TXT_DELETE'],
            'TXT_'.$this->moduleLangVar.'_ACTIVATE' => $_ARRAYLANG['TXT_MEDIADIR_ACTIVATE'],
            'TXT_'.$this->moduleLangVar.'_DEACTIVATE' => $_ARRAYLANG['TXT_MEDIADIR_DEAVTIVATE'],
        ));

        switch ($tpl) {
            case 'import':
                $objInterfaces->showImport($step, $this->_objTpl);
                break;
            case 'export':
            default:
                $objInterfaces->showExport($step, $this->_objTpl);
                break;
        }
    }


    function manageComments()
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        \Permission::checkAccess(MediaDirectoryAccessIDs::ModifyEntry, 'static');

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_manage_comments.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_MEDIADIR_MANAGE_COMMENTS'];

        //parse global variables
        $this->_objTpl->setGlobalVariable(array(
            'TXT_DELETE' => $_ARRAYLANG['TXT_MEDIADIR_DELETE'],
            'TXT_SELECT_ALL' => $_CORELANG['TXT_SELECT_ALL'],
            'TXT_DESELECT_ALL' => $_CORELANG['TXT_DESELECT_ALL'],
            'TXT_SELECT_ACTION' => $_CORELANG['TXT_MULTISELECT_SELECT'],
            'TXT_CONFIRM_ALL' => $_ARRAYLANG['TXT_MEDIADIR_CONFIRM_ALL'],
            'TXT_DELETE_ALL' => $_CORELANG['TXT_MULTISELECT_DELETE'],
            'TXT_'.$this->moduleLangVar.'_NAME' => $_CORELANG['TXT_NAME'],
            'TXT_'.$this->moduleLangVar.'_DATE' => $_CORELANG['TXT_DATE'],
            'TXT_'.$this->moduleLangVar.'_ADDED_BY' => $_ARRAYLANG['TXT_MEDIADIR_ADDED_BY'],
            'TXT_'.$this->moduleLangVar.'_ACTION' => $_CORELANG['TXT_HISTORY_ACTION'],
            'TXT_'.$this->moduleLangVar.'_CONFIRM_DELETE_DATA' => $_ARRAYLANG['TXT_MEDIADIR_CONFIRM_DELETE_DATA'],
            'TXT_'.$this->moduleLangVar.'_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_MEDIADIR_ACTION_IS_IRREVERSIBLE'],
            'TXT_'.$this->moduleLangVar.'_MAKE_SELECTION' => $_ARRAYLANG['TXT_MEDIADIR_MAKE_SELECTION'],
            'TXT_'.$this->moduleLangVar.'_SUBMIT' =>  $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
            'TXT_'.$this->moduleLangVar.'_IP' =>  $_ARRAYLANG['TXT_MEDIADIR_IP'],
            'TXT_'.$this->moduleLangVar.'_COMMENT' =>  $_ARRAYLANG['TXT_MEDIADIR_COMMENT'],
            'TXT_'.$this->moduleLangVar.'_PAGE_TITLE' =>  $_ARRAYLANG['TXT_MEDIADIR_MANAGE_COMMENTS'],
            $this->moduleLangVar.'_ENTRY_ID' =>  intval($_GET['id']),
        ));

        $objComment = new MediaDirectoryComment($this->moduleName);

        switch ($_GET['act']) {
            case 'delete_comment':
                if (!isset($_GET['cid'])) {
                    foreach ($_POST["commentsFormSelected"] as $intCommentId) {
                        $strStatus = $objComment->deleteComment(intval($intCommentId));
                    }
                } else {
                    $strStatus = $objComment->deleteComment(intval($_GET['cid']));
                }

                if($strStatus) {
                    $this->strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_COMMENTS']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_DELETED'];
                } else {
                    $this->strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_COMMENTS']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_DELETED'];
                }
                break;
        }

        //get comments
        $objComment->getComments($this->_objTpl, $_GET['id']);
    }



    function settings()
    {
        global $_ARRAYLANG, $_CORELANG;

        \Permission::checkAccess(MediaDirectoryAccessIDs::Settings, 'static');

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_settings.html',true,true);
        $this->pageTitle = $_CORELANG['TXT_SETTINGS'];

        $objSettings = new MediaDirectorySettings($this->moduleName);
        $tpl = isset($_GET['tpl']) ? $_GET['tpl'] : '';

        //save settings global
        if(isset($_POST['submitSettingsForm'])) {
            switch ($tpl) {
                case 'modify_form':
                    if(intval($_POST['formId']) != 0) {
                        $objInputfields = new MediaDirectoryInputfield(intval($_POST['formId']), false, null, $this->moduleName);
                        $strStatus = $objInputfields->saveInputfields($_POST);
                    }

                    $objForms = new MediaDirectoryForm(null, $this->moduleName);
                    $strStatus = $objForms->saveForm($_POST, intval($_POST['formId']));
                    break;
                case 'forms':
                    $objForms = new MediaDirectoryForm(null, $this->moduleName);
                    $strStatus = $objForms->saveOrder($_POST);
                    break;
                case 'mails':
                    $strStatus = $objSettings->settings_save_mail($_POST);
                    break;
                case 'masks':
                    $strStatus = $objSettings->settings_save_mask($_POST);
                    break;
                case 'map':
                    $strStatus = $objSettings->settings_save_map($_POST);
                    break;
                default:
                    $strStatus = $objSettings->saveSettings($_POST);
            }

            if($strStatus == true){
                $this->strOkMessage = $_CORELANG['TXT_SETTINGS_UPDATED'];
            } else {
                $this->strErrMessage = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
            }
        }

        $this->_objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_ENTRIES' => $_ARRAYLANG['TXT_MEDIADIR_ENTRIES'],
            'TXT_'.$this->moduleLangVar.'_LEVELS_AND_CATEGORIES' => $_ARRAYLANG['TXT_MEDIADIR_LEVELS_AND_CATEGORIES'],
            'TXT_'.$this->moduleLangVar.'_SUBMIT' => $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
            'TXT_'.$this->moduleLangVar.'_DELETE' => $_CORELANG['TXT_DELETE'],
            'TXT_'.$this->moduleLangVar.'_ACTIVATE' => $_ARRAYLANG['TXT_MEDIADIR_ACTIVATE'],
            'TXT_'.$this->moduleLangVar.'_DEACTIVATE' => $_ARRAYLANG['TXT_MEDIADIR_DEAVTIVATE'],
            'TXT_'.$this->moduleLangVar.'_FORMS' => $_ARRAYLANG['TXT_MEDIADIR_FORMS'],
            'TXT_'.$this->moduleLangVar.'_MAIL_TEMPLATES' => $_ARRAYLANG['TXT_MEDIADIR_MAIL_TEMPLATES'],
            'TXT_'.$this->moduleLangVar.'_PICS_AND_FILES' => $_ARRAYLANG['TXT_MEDIADIR_PICS_AND_FILES'],
            'TXT_'.$this->moduleLangVar.'_GOOGLE' => $_ARRAYLANG['TXT_MEDIADIR_GOOGLE'],
            'TXT_'.$this->moduleLangVar.'_HITS_AND_LATEST' => $_ARRAYLANG['TXT_MEDIADIR_HITS_AND_LATEST'],
            'TXT_'.$this->moduleLangVar.'_COMMENTS_AND_VOTING' => $_ARRAYLANG['TXT_MEDIADIR_COMMENTS_AND_VOTING'],
            'TXT_'.$this->moduleLangVar.'_CLASSIFICATION' => $_ARRAYLANG['TXT_MEDIADIR_CLASSIFICATION'],
            'TXT_'.$this->moduleLangVar.'_EXPORT_MASKS' => $_ARRAYLANG['TXT_MEDIADIR_EXPORT_MASKS'],
        ));

        switch ($tpl) {
            case 'delete_mask':
            case 'masks':
                $objSettings->settings_masks($this->_objTpl);
                break;
            case 'modify_mask':
                $objSettings->settings_modify_mask($this->_objTpl);
                break;
            case 'delete_form':
            case 'forms':
                $objSettings->settings_forms($this->_objTpl);
                break;
            case 'modify_form':
                $objSettings->settings_modify_form($this->_objTpl);
                break;
            case 'delete_template':
            case 'mails':
                $objSettings->settings_mails($this->_objTpl);
                break;
            case 'modify_mail':
                $objSettings->settings_modify_mail($this->_objTpl);
                break;
            case 'files':
                $objSettings->settings_files($this->_objTpl);
                break;
            case 'map':
                $objSettings->settings_map($this->_objTpl);
                break;
            case 'votes':
                $objSettings->settings_votes($this->_objTpl);
                break;
            case 'levels_categories':
                $objSettings->settings_levels_categories($this->_objTpl);
                break;
            case 'classification':
                $objSettings->settings_classification($this->_objTpl);
                break;
            case 'entries':
            default:
                $objSettings->settings_entries($this->_objTpl);
        }

        $this->_objTpl->parse('settings_content');
    }
}
?>

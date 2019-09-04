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
class MediaDirectory extends MediaDirectoryLibrary
{

    var $pageTitle;
    var $metaTitle;
    var $metaDescription;
    var $metaImage;
    var $metaKeys;
    var $slug;


    var $arrNavtree = array();

    /**
     * Constructor
     */
    function __construct($pageContent, $name)
    {
        global $_ARRAYLANG, $_CORELANG;

        //globals
        parent::__construct('.', $name);
        parent::getSettings();
        parent::getFrontendLanguages();
        parent::checkDisplayduration();

        $this->pageContent = $pageContent;
    }

    /**
     * get oage
     *
     * Reads the act and selects the right action
     *
     * @access   public
     * @return   string  parsed content
     */
    function getPage()
    {
        \JS::activate('shadowbox');
        \JS::activate('jquery');

        $entryId = 0;
        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
        if (isset($requestParams['eid'])) {
            $entryId = intval($requestParams['eid']);
        }

        if($this->arrSettings['settingsAllowVotes']) {
            $objVoting = new MediaDirectoryVoting($this->moduleName);
            $this->setJavascript($objVoting->getVoteJavascript());

            if(isset($_GET['vote']) && $entryId != 0) {
                $objVoting->saveVote($entryId, intval($_GET['vote']));
            }
        }

        if($this->arrSettings['settingsAllowComments'] == 1) {
            $objComment = new MediaDirectoryComment($this->moduleName);
            $this->setJavascript($objComment->getCommentJavascript());
            $comment = isset($_GET['comment']) ? $_GET['comment'] : '';
            if($comment == 'add' && $entryId != 0) {
                $objComment->saveComment($entryId, $_POST);
            }

            if($comment == 'refresh' && $entryId != 0) {
                $objComment->refreshComments($entryId, $_GET['pageSection'], $_GET['pageCmd']);
            }
        }

        switch ($_REQUEST['cmd']) {
            case 'delete':
                if((!empty($_REQUEST['eid'])) || (!empty($_REQUEST['entryId']))) {
                    parent::checkAccess('delete_entry');
                    $this->deleteEntry();
                } else {
                    header("Location: index.php?section=".$this->moduleName);
                    exit;
                }
                break;
            case 'latest':
                $this->showLatest();
                break;
            case 'popular':
                $this->showPopular();
                break;
            case 'map':
                $this->showMap();
                break;
            case 'myentries':
                parent::checkAccess('my_entries');
                $this->showMyEntries();
                break;
            case 'detail':
                parent::checkAccess('show_entry');
                $this->showEntry();
                break;
            case 'adduser':
                $this->showAddUser();
                break;
            case 'confirm_in_progress':
                $this->_objTpl->setTemplate($this->pageContent);
                break;
            case 'alphabetical':
                $this->showAlphabetical();
                break;
            default:
                if(isset($_REQUEST['check'])) {
                    parent::checkDisplayduration();
                }

                if(substr($_REQUEST['cmd'],0,6) == 'detail'){
                    parent::checkAccess('show_entry');
                    $this->showEntry();
                } else if (substr($_REQUEST['cmd'],0,3) == 'add'){
                    parent::checkAccess('add_entry');
                    $this->modifyEntry();
                } else if (substr($_REQUEST['cmd'], 0, 4) == 'edit') {
                    if (
                        (isset($_REQUEST['eid']) && intval($_REQUEST['eid']) != 0) ||
                        (intval($_REQUEST['entryId']) != 0)
                    ) {
                        parent::checkAccess('edit_entry');
                        $this->modifyEntry();
                    } else {
                        header("Location: index.php?section=".$this->moduleName);
                        exit;
                    }
                } else {
                    if(isset($_REQUEST['search'])) {
                        $this->showSearch();
                    } else {
                        $this->showOverview();
                    }
                }
        }

        $this->_objTpl->setVariable(array(
            $this->moduleLangVar.'_JAVASCRIPT' =>  $this->getJavascript(),
        ));

        return $this->_objTpl->get();
    }

    function showOverview()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $intCmdFormId = 0;
        $showEntriesOfLevel = false;
        $showEntriesOfCategory = false;
        $showLevelDetails = false;
        $showCategoryDetails = false;
        $bolLatest = false;
        $objLevel = null;
        $objCategory = null;

        // whether the category or level list will be displayed
        $listCategoriesAndLevels = false;

        // whether the loaded form (if at all) does use categories or not
        $bolFormUseCategory = false;

        // whether the loaded form (if at all) does use levels or not
        $bolFormUseLevel = false;

        $intLimitStart = isset($_GET['pos']) ? intval($_GET['pos']) : 0;

        // load Default.html application template as fallback
        if ($this->_objTpl->placeholderExists('APPLICATION_DATA')) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('MediaDir');
            // load source code
            $applicationTemplate = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $this->_objTpl->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }

        // fetch filter config from template
        $config = static::fetchMediaDirListConfigFromTemplate('__global__', $this->_objTpl);

        //search existing category&level blocks
        $arrExistingBlocks = array();

        for($i = 1; $i <= 10; $i++){
            if($this->_objTpl->blockExists($this->moduleNameLC.'CategoriesLevels_row_'.$i)){
                array_push($arrExistingBlocks, $i);
            }
        }

        //get ids
        $arrIds = array();
        if(isset($_GET['cmd'])) {
            $arrIds = explode("-", $_GET['cmd']);
        }

        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();

        if($this->arrSettings['settingsShowLevels'] == 1) {
            if (isset($requestParams['lid'])) {
                $intLevelId = intval($requestParams['lid']);
            } elseif (isset($config['filter']['level'])) {
                $intLevelId = $config['filter']['level'];
            } elseif (intval($arrIds[0]) != 0) {
                $intLevelId = intval($arrIds[0]);
                $this->cx->getRequest()->getUrl()->setParam('lid', $intLevelId);
            } else {
                $intLevelId = 0;
            }

            if(!empty($arrIds[1])) {
                $intCategoryCmd = $arrIds[1];
            } else {
                $intCategoryCmd = 0;
            }
        } else {
            $intLevelId = 0;

            if(intval($arrIds[0]) != 0) {
                $intCategoryCmd = $arrIds[0];
            } else {
                $intCategoryCmd = 0;
            }
        }

        // note: when using pretty urls, then the request param cid
        // will be set to the value of the request param cmd in case
        // the request param cmd is set to a category-id
        if (isset($requestParams['cid'])) {
            $intCategoryId = intval($requestParams['cid']);
        } elseif (isset($config['filter']['category'])) {
            $intCategoryId = $config['filter']['category'];
        } elseif ($intCategoryCmd != 0) {
            $intCategoryId = intval($intCategoryCmd);
            $this->cx->getRequest()->getUrl()->setParam('cid', $intCategoryId);
        } else {
            $intCategoryId = 0;
        }

        // show block {$this->moduleNameLC}Overview
        if (empty($intCategoryId) && empty($intLevelId) && $this->_objTpl->blockExists($this->moduleNameLC.'Overview')) {
            $this->_objTpl->touchBlock($this->moduleNameLC.'Overview');
        }

        // check form filter
        $formFilter = false;
        if (isset($config['filter']['form'])) {
            $formFilter = true;
        } elseif (
            !empty($_GET['cmd']) &&
            $arrIds[0] != 'search'
        ) {
            $formFilter = true;
        }
        if ($formFilter) {
            $arrFormCmd = array();

            $objForms = new MediaDirectoryForm(null, $this->moduleName);
            foreach ($objForms->arrForms as $intFormId => $arrForm) {
                // note: in a previous version of Cloudrexx, there was no check
                // if the form was active or not. this caused unexpected
                // behavior
                if (
                    !$this->arrSettings['legacyBehavior'] &&
                    !$arrForm['formActive']
                ) {
                    continue;
                }

                if (
                    isset($config['filter']['form']) &&
                    $config['filter']['form'] == $intFormId
                ) {
                    $intCmdFormId = $intFormId;
                    break;
                }

                if(!empty($arrForm['formCmd'])) {
                    $arrFormCmd[$arrForm['formCmd']] = intval($intFormId);
                }
            }

            if (
                !$intCmdFormId &&
                !empty($arrFormCmd[$_GET['cmd']])
            ) {
                $intCmdFormId = intval($arrFormCmd[$_GET['cmd']]);
            }
        }

        if ($this->_objTpl->blockExists($this->moduleNameLC.'CategoriesLevelsList')) {
            $listCategoriesAndLevels = true;
        }

        // detect if the use of categories and/or levels has been activated
        //
        // note: in a previous version of Cloudrexx, this has only been done
        //       in case the template block mediadirCategoriesLevelsList
        //       was present. Therefore, we have introduced the setting
        //       option 'Legacy behavior' to emulate that previous behavior.
        if (!$this->arrSettings['legacyBehavior'] || $this->_objTpl->blockExists($this->moduleNameLC.'CategoriesLevelsList')) {
            if ($intCmdFormId != 0) {   
                $bolFormUseCategory = $objForms->arrForms[intval($intCmdFormId)]['formUseCategory'];
                $bolFormUseLevel = $objForms->arrForms[intval($intCmdFormId)]['formUseLevel'];
            } else {     
                $bolFormUseCategory = true;
                $bolFormUseLevel = $this->arrSettings['settingsShowLevels'];
            }
        }

        // check if we should parse the latest entries
        // important: in case we will parse the regular list, then the
        //            latest entries won't be parsed. See code below
        if (   $this->arrSettings['showLatestEntriesInOverview']
            && (($intCategoryId == 0 && $bolFormUseCategory) || ($intLevelId == 0  && $bolFormUseLevel)
        )) {
            $bolLatest = true;
            $intLimitEnd = intval($this->arrSettings['settingsLatestNumOverview']);
        } else {
            // fetch paging limit
            $intLimitEnd = intval($this->arrSettings['settingsPagingNumEntries']);
            if (    !empty($intCmdFormId)
                &&  !empty($objForms->arrForms[$intCmdFormId]['formEntriesPerPage'])
            ) {
                $intLimitEnd = $objForms->arrForms[$intCmdFormId]['formEntriesPerPage'];
            }
        }

        //get navtree
        $this->getNavtree($intCategoryId, $intLevelId);

        //get searchform
        if($this->_objTpl->blockExists($this->moduleNameLC.'Searchform')){
            $objSearch = new MediaDirectorySearch($this->moduleName);
            $objSearch->getSearchform($this->_objTpl);
        }

        // note: the initialization of the objects $objLevel and $objCategory
        //       has in a previous version of Cloudrexx only been done, if the
        //       template block mediadirCategoryLevelDetail was present.
        //       This caused the parsing of mediadirCategoriesLevelsList only
        //       to be done when the block mediadirCategoryLevelDetail was present.
        //       Using the setting option 'legacy behavior' we do emulate that
        //       previous behavior.
        if (!$this->arrSettings['legacyBehavior'] || $this->_objTpl->blockExists($this->moduleNameLC.'CategoryLevelDetail')) {
            if ($intCategoryId == 0 && $intLevelId != 0 && $this->arrSettings['settingsShowLevels']) {
                $objLevel = new MediaDirectoryLevel($intLevelId, null, 0, $this->moduleName);
                $showLevelDetails = true;
                $showEntriesOfLevel = $objLevel->arrLevels[$intLevelId]['levelShowEntries'];
            }

            if($intCategoryId != 0) {
                $objCategory = new MediaDirectoryCategory($intCategoryId, null, 0, $this->moduleName);
                $showCategoryDetails = true;
                $showEntriesOfCategory = $objCategory->arrCategories[$intCategoryId]['catShowEntries'];
            }
        }

        // check show entries
        $showEntries = 
               // a level has been selected and it is configured to list entries
               $showEntriesOfLevel
               // a category has been selected and it is configured to list entries
            || $showEntriesOfCategory
               // if neither a level nor a category has been selected and list of latest entries is active
            || $bolLatest
               // if the loaded form does not use categories nor levels
            || (!$bolFormUseCategory && !$bolFormUseLevel);

        // in case a form has been requested, but we're not going to list any categories nor any levels
        // nor the latest entries, then we shall simply parse all form entries
        if (!$showEntries && $intCmdFormId && !$showCategoryDetails && !$showLevelDetails && !$listCategoriesAndLevels) {
            $showEntries = true;
        }

        // fetch entries
        if ($showEntries) {
            $objEntries = new MediaDirectoryEntry($this->moduleName);

            // custom sort order
            $forceAlphabeticalOrder = false;
            $popular = null;
            $forceLatest = false;

            // check for custom sort order
            // but only if option 'legacy behavior' is not set
            if (
                !$this->arrSettings['legacyBehavior'] &&
                $this->_objTpl->blockExists($this->moduleNameLC . 'EntryList')
            ) {
                $config = static::fetchMediaDirListConfigFromTemplate($this->moduleNameLC . 'EntryList', $this->_objTpl);
                if (
                    !empty($config['sort']['alphabetical']) &&
                    $objEntries->arrSettings['settingsIndividualEntryOrder']
                ) {
                    $forceAlphabeticalOrder = true;
                    $objEntries->arrSettings['settingsIndividualEntryOrder'] = false;
                }
                if (isset($config['sort']['popular'])) {
                    $popular = $config['sort']['popular'];
                }
                // note: 'popular' has precedence in
                // MediaDirectoryEntry::getEntries before 'latest'
                if (
                    !$popular &&
                    isset($config['list']['latest'])
                ) {
                    $bolLatest = true;
                    $forceLatest = true;
                }
                if (isset($config['list']['limit'])) {
                    $intLimitEnd = $config['list']['limit'];
                }
                if (isset($config['list']['offset'])) {
                    $intLimitStart = $config['list']['offset'];
                }
            }

            $objEntries->getEntries(null,$intLevelId,$intCategoryId,null,$bolLatest,null,1,$intLimitStart, $intLimitEnd, null, $popular, $intCmdFormId);

            // as we are forcing the latest list within the regular
            // mediadirEntryList template block, we have to reset the latest
            // option to make the regular processing within the template
            // block mediadirEntryList work
            if ($forceLatest) {
                $bolLatest = false;
                $objEntries->setStrBlockName($this->moduleNameLC."EntryList");
            }

            // reset default order behaviour
            if ($forceAlphabeticalOrder) {
                $objEntries->arrSettings['settingsIndividualEntryOrder'] = true;
            }
        }

        // parse the level details
        if ($showLevelDetails && $this->_objTpl->blockExists($this->moduleNameLC.'CategoryLevelDetail')) {
            $objLevel->listLevels($this->_objTpl, 5, $intLevelId);
        }

        $metaTitle = array();
        if ($objLevel) {
            // only set page's title to level's name
            // if not in legacy mode
            if (!$this->arrSettings['legacyBehavior']) {
                $this->pageTitle = $objLevel->arrLevels[$intLevelId]['levelName'][0];
                $metaTitle[] = $objLevel->arrLevels[$intLevelId]['levelName'][0];
            }
            if (empty($objLevel->arrLevels[$intLevelId]['levelMetaDesc'][0])) {
                $this->metaDescription = $objLevel->arrLevels[$intLevelId]['levelDescription'][0];
            } else {
                $this->metaDescription = $objLevel->arrLevels[$intLevelId]['levelMetaDesc'][0];
            }
            $this->metaImage = $objLevel->arrLevels[$intLevelId]['levelPicture'];
        }

        // parse the category details
        if ($showCategoryDetails && $this->_objTpl->blockExists($this->moduleNameLC.'CategoryLevelDetail')) {
            $objCategory->listCategories($this->_objTpl, 5, $intCategoryId);
        }

        if ($objCategory) {
            // only set page's title to category's name
            // if not in legacy mode
            if (!$this->arrSettings['legacyBehavior']) {
                $this->pageTitle = $objCategory->arrCategories[$intCategoryId]['catName'][0];
                $metaTitle[] = $objCategory->arrCategories[$intCategoryId]['catName'][0];
            }
            if (empty($objCategory->arrCategories[$intCategoryId]['catMetaDesc'][0])) {
                $this->metaDescription = $objCategory->arrCategories[$intCategoryId]['catDescription'][0];
            } else {
                $this->metaDescription = $objCategory->arrCategories[$intCategoryId]['catMetaDesc'][0];
            }
            $this->metaImage = $objCategory->arrCategories[$intCategoryId]['catPicture'];
        }
        if (empty($this->arrNavtree) && !empty($metaTitle)) {
            $this->metaTitle .= ' - ' . implode(' - ', $metaTitle);
        }

        //list levels / categories
        if ($this->_objTpl->blockExists($this->moduleNameLC.'CategoriesLevelsList')) {
            // list levels if:
            // - option 'Use levels' is active
            // - and no category has been selected
            // - and optional: in case a FORM has been defined by CMD, if FORM's option 'Use levels' is active
            if($this->arrSettings['settingsShowLevels'] == 1 && $intCategoryId == 0 && $bolFormUseLevel) {
                $objLevels = new MediaDirectoryLevel(null, $intLevelId, 1, $this->moduleName);
                $objCategories = new MediaDirectoryCategory(null, $intCategoryId, 1, $this->moduleName);
                $objLevels->listLevels($this->_objTpl, 2, null, null, null, $arrExistingBlocks, null, $intCmdFormId);
                $this->_objTpl->clearVariables();
                $this->_objTpl->setVariable($this->moduleLangVar.'_CATEGORY_LEVEL_TYPE', 'level');
                $this->_objTpl->parse($this->moduleNameLC.'CategoriesLevelsList');
            }

            // selected level has 'Show Categories' set
            // or no level is selected
            // or listing of levels in general is deactivated
            // or a category is selected
            // or selected form hat option 'Use categories' activ and option 'Use levels' inactive
            if((((isset($objLevel) && $objLevel->arrLevels[$intLevelId]['levelShowCategories'] == 1) || $intLevelId === 0) || $this->arrSettings['settingsShowLevels'] == 0 || $intCategoryId != 0) || ($bolFormUseCategory && !$bolFormUseLevel)) {
                $objCategories = new MediaDirectoryCategory(null, $intCategoryId, 1, $this->moduleName);
                $objCategories->listCategories($this->_objTpl, 2, null, null, null, $arrExistingBlocks, 1, $intCmdFormId);
                $this->_objTpl->clearVariables();
                $this->_objTpl->setVariable($this->moduleLangVar.'_CATEGORY_LEVEL_TYPE', 'category');
                $this->_objTpl->parse($this->moduleNameLC.'CategoriesLevelsList');
            }

            // hide block mediadirCategoriesLevelsList in case no levels nor any categories have benn loaded
            if(empty($objLevel->arrLevels) && empty($objCategories->arrCategories)) {
                $this->_objTpl->hideBlock($this->moduleNameLC.'CategoriesLevelsList');
                $this->_objTpl->clearVariables();
            }
        }

        //latest title
        if($this->_objTpl->blockExists($this->moduleNameLC.'LatestTitle') && $intCategoryId == 0 && $intLevelId == 0){
            $this->_objTpl->touchBlock($this->moduleNameLC.'LatestTitle');
        }

        /**
         * The parsing behavior of the mediadirLatestList template block used to
         * be strange in the former Cloudrexx versions. Setting this variable to TRUE
         * will emulate that dropped strange behavior.
         * The setting of this variable is managed by the backend setting option
         * 'Legacy behavior'.
         * @var boolean
         */
        $legacyLatestMode = false;

        if (!$showEntries) {
            if ($this->_objTpl->blockExists($this->moduleNameLC.'EntryList')) {
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryList');
            }
            if ($this->_objTpl->blockExists($this->moduleNameLC.'LatestList')) {
                $this->_objTpl->hideBlock($this->moduleNameLC.'LatestList');
            }
            return;
        }

        // parse entries (mediadirEntryList)
        if ($this->_objTpl->blockExists($this->moduleNameLC.'EntryList')) {
            // Activate legacy behavior if option 'Legacy behavior' has been activated.
            // The parsing of the latest entries was previously only done
            // if the template block mediadirEntryList was present
            $legacyLatestMode = $this->arrSettings['legacyBehavior'];

            if (!$bolLatest ) {
                $objEntries->listEntries($this->_objTpl, 2);

                // hide block used to display latest entries
                //
                // note: in a previous version of cloudrexx, the template block
                //       mediadirLatestList was wrapped around the template block
                //       mediadirEntryList in the default template. Therefore,
                //       if 'Legacy behavior' option is active, we can not hide
                //       the template block mediadirLatestList
                if (!$this->arrSettings['legacyBehavior'] && $this->_objTpl->blockExists($this->moduleNameLC.'LatestList')){
                    $this->_objTpl->hideBlock($this->moduleNameLC.'LatestList');
                }

                $intNumEntries = intval($objEntries->countEntries());
                if($intNumEntries > $intLimitEnd) {
                    $objUrl           = clone \Env::get('Resolver')->getUrl();                        
                    $currentUrlParams = $objUrl->getSuggestedParams();
                    $strPaging = getPaging($intNumEntries, $intLimitStart, $currentUrlParams, "<b>".$_ARRAYLANG['TXT_MEDIADIR_ENTRIES']."</b>", true, $intLimitEnd);
                    $this->_objTpl->setGlobalVariable(array(
                        $this->moduleLangVar.'_PAGING' =>  $strPaging
                    ));
                }
            }

            //no entries found
            if (!$objEntries || empty($objEntries->arrEntries)) {
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryList');
                $this->_objTpl->clearVariables();
            }
        }

        // parse latest entries (mediadirLatestList)
        if (   $bolLatest
            && ($this->_objTpl->blockExists($this->moduleNameLC.'LatestList') || $legacyLatestMode)
            && $this->arrSettings['showLatestEntriesInOverview']
        ) {

            $objEntries->listEntries($this->_objTpl, 2);

            //no entries found
            if (    (!$objEntries || empty($objEntries->arrEntries))
                 && $this->_objTpl->blockExists($this->moduleNameLC.'LatestList')
            ) {
                $this->_objTpl->hideBlock($this->moduleNameLC.'LatestList');
                $this->_objTpl->clearVariables();
            }

            // hide block used to display all entries
            //
            // note: in a previous version of cloudrexx, the template block
            //       mediadirLatestList was wrapped around the template block
            //       mediadirEntryList in the default template. Therefore,
            //       if 'Legacy behavior' option is active, we can not hide
            //       the template block mediadirEntryList
            if (!$this->arrSettings['legacyBehavior'] && $this->_objTpl->blockExists($this->moduleNameLC.'EntryList')) {
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryList');
            }
        }
    }

    function showAlphabetical()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $formId = 0;
        $categoryId = 0;
        $levelId = 0;

        //get navtree
        $this->getNavtree($categoryId, $levelId);

        //get searchform
        $searchTerm = null;
        if($this->_objTpl->blockExists($this->moduleNameLC.'Searchform')){
            $objSearch = new MediaDirectorySearch($this->moduleName);
            $objSearch->getSearchform($this->_objTpl);
            $searchTerm = isset($_GET['term']) ? contrexx_input2raw($_GET['term']) : null;
        }

        // fetch special config from application template
        $config = static::fetchMediaDirListConfigFromTemplate($this->moduleNameLC . 'EntryList', $this->_objTpl);

        if (isset($config['filter']['form'])) {
            $formId = $config['filter']['form'];
        }
        if (isset($config['filter']['category'])) {
            $categoryId = $config['filter']['category'];
        }
        if (isset($config['filter']['level'])) {
            $levelId = $config['filter']['level'];
        }

        // fetch & list entries
        $objEntry = new MediaDirectoryEntry($this->moduleName);
        $objEntry->getEntries(null, $levelId, $categoryId, $searchTerm, false, null, true, null, 'n', null, null, $formId);
        $objEntry->listEntries($this->_objTpl, 3);
    }

    function showSearch()
    {
        global $_ARRAYLANG, $_CORELANG;

        $showLevelDetails = false;
        $showCategoryDetails = false;
        $objLevel = null;
        $objCategory = null;
        $this->_objTpl->setTemplate($this->pageContent, true, true);

        // load Default.html application template as fallback
        if ($this->_objTpl->placeholderExists('APPLICATION_DATA')) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('MediaDir');
            // load source code
            $applicationTemplate = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $this->_objTpl->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }

        //get searchform
        if($this->_objTpl->blockExists($this->moduleNameLC.'Searchform')){
            $objSearch = new MediaDirectorySearch($this->moduleName);
            $objSearch->getSearchform($this->_objTpl);
        }

        $_GET['term'] = trim($_GET['term']);

        $cmd         = isset($_GET['cmd']) ? contrexx_input2raw($_GET['cmd']) : '';
        $intLimitEnd = intval($this->arrSettings['settingsPagingNumEntries']);
        if (!empty($cmd)) {
            $objForms = new MediaDirectoryForm(null, $this->moduleName);
            foreach ($objForms->arrForms as $intFormId => $arrForm) {
                if (    !empty($arrForm['formCmd'])
                    &&  $arrForm['formCmd'] === $cmd
                    &&  !empty($arrForm['formEntriesPerPage'])
                ) {
                    $intLimitEnd = $arrForm['formEntriesPerPage'];
                    break;
                }
            }
        }

        $intLimitStart = isset($_GET['pos']) ? intval($_GET['pos']) : 0;

        if(!empty($_GET['term']) || $_GET['type'] == 'exp') {
            $objSearch = new MediaDirectorySearch($this->moduleName);
            $objSearch->searchEntries($_GET);

            $objEntries = new MediaDirectoryEntry($this->moduleName);

            if(!empty($objSearch->arrFoundIds)) {
                $intNumEntries = count($objSearch->arrFoundIds);

                for($i=$intLimitStart; $i < ($intLimitStart+$intLimitEnd); $i++) {
                    $intEntryId = isset($objSearch->arrFoundIds[$i]) ? $objSearch->arrFoundIds[$i] : 0;
                    if(intval($intEntryId) != 0) {
                        $objEntries->getEntries($intEntryId, null, null, null, null, null, 1, 0, 1, null, null);
                    }
                }

                $objEntries->listEntries($this->_objTpl, 2);

                // parse GoogleMap
                $this->parseGoogleMapPlaceholder($this->_objTpl, $this->moduleLangVar.'_SEARCH_GOOGLE_MAP');
                
                $urlParams = $_GET;
                unset($urlParams['pos']);
                unset($urlParams['section']);

                if($intNumEntries > $intLimitEnd) {
                    $strPaging = getPaging($intNumEntries, $intLimitStart, $urlParams, "<b>".$_ARRAYLANG['TXT_MEDIADIR_ENTRIES']."</b>", true, $intLimitEnd);
                    $this->_objTpl->setGlobalVariable(array(
                        $this->moduleLangVar.'_PAGING' =>  $strPaging
                    ));
                }
            } else {
                $this->_objTpl->setVariable(array(
                    'TXT_'.$this->moduleLangVar.'_SEARCH_MESSAGE' =>  $_ARRAYLANG['TXT_MEDIADIR_NO_ENTRIES_FOUND'],
                ));
            }
        } else {
            $this->_objTpl->setVariable(array(
                'TXT_'.$this->moduleLangVar.'_SEARCH_MESSAGE' =>  $_ARRAYLANG['TXT_MEDIADIR_NO_SEARCH_TERM'],
            ));
        }

        // get level & category ids
        if (isset($_GET['cmd'])) {
            $arrIds = explode("-", $_GET['cmd']);
        }

        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();

        if ($this->arrSettings['settingsShowLevels'] == 1) {
            if (isset($requestParams['lid'])) {
                $intLevelId = intval($requestParams['lid']);
            } elseif (intval($arrIds[0]) != 0) {
                $intLevelId = intval($arrIds[0]);
                $this->cx->getRequest()->getUrl()->setParam('lid', $intLevelId);
            } else {
                $intLevelId = 0;
            }

            if (!empty($arrIds[1])) {
                $intCategoryCmd = $arrIds[1];
            } else {
                $intCategoryCmd = 0;
            }
        } else {
            $intLevelId = 0;

            if(intval($arrIds[0]) != 0) {
                $intCategoryCmd = $arrIds[0];
            } else {
                $intCategoryCmd = 0;
            }
        }

        if (isset($requestParams['cid'])) {
            $intCategoryId = intval($requestParams['cid']);
        } elseif ($intCategoryCmd != 0) {
            $intCategoryId = intval($intCategoryCmd);
            $this->cx->getRequest()->getUrl()->setParam('cid', $intCategoryId);
        } else {
            $intCategoryId = 0;
        }

        if ($this->_objTpl->blockExists($this->moduleNameLC.'CategoryLevelDetail')) {
            if ($intCategoryId == 0 && $intLevelId != 0 && $this->arrSettings['settingsShowLevels']) {
                $objLevel = new MediaDirectoryLevel($intLevelId, null, 0, $this->moduleName);
                $showLevelDetails = true;
            }

            if($intCategoryId != 0) {
                $objCategory = new MediaDirectoryCategory($intCategoryId, null, 0, $this->moduleName);
                $showCategoryDetails = true;
            }
        }

        // parse the level details
        if ($showLevelDetails && $this->_objTpl->blockExists($this->moduleNameLC.'CategoryLevelDetail')) {
            $objLevel->listLevels($this->_objTpl, 5, $intLevelId);
        }

        $metaTitle = array();
        if ($objLevel) {
            // only set page's title to level's name
            // if not in legacy mode
            if (!$this->arrSettings['legacyBehavior']) {
                $this->pageTitle = $objLevel->arrLevels[$intLevelId]['levelName'][0];
                $metaTitle[] = $objLevel->arrLevels[$intLevelId]['levelName'][0];
            }
            if (empty($objLevel->arrLevels[$intLevelId]['levelMetaDesc'][0])) {
            	$this->metaDescription = $objLevel->arrLevels[$intLevelId]['levelDescription'][0];
            } else {
                $this->metaDescription = $objLevel->arrLevels[$intLevelId]['levelMetaDesc'][0];
            }
            $this->metaImage = $objLevel->arrLevels[$intLevelId]['levelPicture'];
        }

        // parse the category details
        if ($showCategoryDetails && $this->_objTpl->blockExists($this->moduleNameLC.'CategoryLevelDetail')) {
            $objCategory->listCategories($this->_objTpl, 5, $intCategoryId);
        }

        if ($objCategory) {
            // only set page's title to category's name
            // if not in legacy mode
            if (!$this->arrSettings['legacyBehavior']) {
                $this->pageTitle = $objCategory->arrCategories[$intCategoryId]['catName'][0];
                $metaTitle[] = $objCategory->arrCategories[$intCategoryId]['catName'][0];
            }
            if (empty($objCategory->arrCategories[$intCategoryId]['catMetaDesc'][0])) {
            	$this->metaDescription = $objCategory->arrCategories[$intCategoryId]['catDescription'][0];
            } else {
                $this->metaDescription = $objCategory->arrCategories[$intCategoryId]['catMetaDesc'][0];
            }
            $this->metaImage = $objCategory->arrCategories[$intCategoryId]['catPicture'];
        }
        if (empty($this->arrNavtree) && !empty($metaTitle)) {
            $this->metaTitle .= ' - ' . implode(' - ', $metaTitle);
        }
    }




    function showEntry()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get ids
        $intCategoryId = 0;
        $intLevelId = 0;

        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();

        if (isset($requestParams['cid'])) {
            $intCategoryId = intval($requestParams['cid']);
        }

        if (isset($requestParams['lid'])) {
            $intLevelId = intval($requestParams['lid']);
        }

        $intEntryId = intval($this->cx->getRequest()->getUrl()->getParamArray()['eid']);

        // load source code if cmd value is integer
        if ($this->_objTpl->placeholderExists('APPLICATION_DATA')) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('MediaDir');
            $page->setCmd('detail');
            // load source code
            $applicationTemplate = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $this->_objTpl->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }

        //get navtree
        $this->getNavtree($intCategoryId, $intLevelId);

        if (!$intEntryId || !$this->_objTpl->blockExists($this->moduleNameLC.'EntryList')) {
            header("Location: index.php?section=".$this->moduleName);
            exit;
        }

        $objEntry = new MediaDirectoryEntry($this->moduleName);
        $objEntry->getEntries($intEntryId,$intLevelId,$intCategoryId,null,null,null,1,null,1);

        if (empty($objEntry->arrEntries)) {
            $this->_objTpl->hideBlock($this->moduleNameLC.'EntryList');
            $this->_objTpl->clearVariables();

            header("Location: index.php?section=".$this->moduleName);
            exit;
        }

        // parse search form
        if($this->_objTpl->blockExists($this->moduleNameLC.'Searchform')){
            $objSearch = new MediaDirectorySearch($this->moduleName);
            try {
                $objSearch->getSearchform($this->_objTpl, $objEntry->getFormUrl());
            } catch (MediaDirectoryEntryException $e) {
                \DBG::log('Unable to load search form: '.$e->getMessage());
            }
        }

        // parse entry details
        $objEntry->listEntries($this->_objTpl, 2);
        $objEntry->updateHits($intEntryId);

        //set meta attributes
        $entry = $objEntry->arrEntries[$intEntryId];

        $objInputfields = new MediaDirectoryInputfield($entry['entryFormId'], false, $entry['entryTranslationStatus'], $this->moduleName);
        $inputFields = $objInputfields->getInputfields();

        $titleChanged = false;
        $contentChanged = false;

        foreach ($inputFields as $arrInputfield) {
            $contextType = isset($arrInputfield['context_type']) ? $arrInputfield['context_type'] : '';
            if (!in_array($contextType, array('title', 'content', 'image', 'keywords'))) {
                continue;
            }
            $strType = isset($arrInputfield['type_name']) ? $arrInputfield['type_name'] : '';
            $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield" . ucfirst($strType);
            try {
                $objInputfield = safeNew($strInputfieldClass, $this->moduleName);
                $arrTranslationStatus = (contrexx_input2int($arrInputfield['type_multi_lang']) == 1)
                    ? $entry['entryTranslationStatus']
                    : null;
                $arrInputfieldContent = $objInputfield->getContent($entry['entryId'], $arrInputfield, $arrTranslationStatus);
                switch ($contextType) {
                    case 'title':
                        $inputfieldValue = $arrInputfieldContent[$this->moduleLangVar . '_INPUTFIELD_VALUE'];
                        if ($inputfieldValue) {
                            if (!empty($this->metaTitle)) {
                                $this->metaTitle .= ' - ';
                            }
                            $this->metaTitle .= $inputfieldValue;
                            $this->pageTitle = $inputfieldValue;
                        }
                        $titleChanged = true;
                        break;
                    case 'content':
                        $inputfieldValue = $arrInputfieldContent[$this->moduleLangVar . '_INPUTFIELD_VALUE'];
                        if ($inputfieldValue) {
                            $this->metaDescription = $inputfieldValue;
                        }
                        $contentChanged = true;
                        break;
                    case 'image':
                        $inputfieldValue = $arrInputfieldContent[$this->moduleLangVar . '_INPUTFIELD_VALUE_SRC'];
                        if ($inputfieldValue) {
                            $this->metaImage = $inputfieldValue;
                        }
                        break;
                    case 'keywords':
                        $inputfieldValue = $objInputfield->getRawData($entry['entryId'], $arrInputfield, $arrTranslationStatus, true);
                        if ($inputfieldValue) {
                            $this->metaKeys = $inputfieldValue;
                        }
                        break;
                    default:
                        break;
                }
            } catch (\Exception $e) {
                \DBG::log($e->getMessage());
                continue;
            }
        }

        $firstInputfieldValue = $objEntry->arrEntries[$intEntryId]['entryFields'][0];
        if (!$titleChanged && $firstInputfieldValue) {
            $this->pageTitle = $firstInputfieldValue;
            $this->metaTitle = $firstInputfieldValue;
        }
        if (!$contentChanged && $firstInputfieldValue) {
            $this->metaDescription = $firstInputfieldValue;
        }

        // parse related entries
        $this->parseRelatedEntries($this->_objTpl, $objEntry, $intEntryId, $intCategoryId, $intLevelId);

        // parse previous entry
        $this->parsePreviousEntry($objEntry, $intEntryId, $intCategoryId, $intLevelId);

        // parse next entry
        $this->parseNextEntry($objEntry, $intEntryId, $intCategoryId, $intLevelId);
    }

    /**
     * Parse related entries in template block mediadirRelatedList.
     * See (@see fetchMediaDirListConfigFromTemplate) for a list of functional
     * placeholders to be used in the template.
     *
     * @param   \Cx\Core\Html\Sigma $template   Template object to be used for parsing
     * @param   MediaDirectoryEntry $objEntry   Instance of current MediaDirectoryEntry
     * @param   integer $intEntryId ID of the currently processing entry
     * @param   integer $intCategoryId ID of the currently selected category
     * @param   integer $intLevelId ID of the currently selected level
     */
    public function parseRelatedEntries($template, $objEntry, $intEntryId, $intCategoryId = 0, $intLevelId = 0, $templatePrefix = '') {
        // check if we shall parse any related entries
        if (!$template->blockExists($this->moduleNameLC . $templatePrefix . 'RelatedList')) {
            return;
        }

        $latest = null;
        $limit = $this->arrSettings['settingsPagingNumEntries'];
        $offset = null;
        $formId = null;
        $categoryId = null;
        $levelId = null;
        $entryId = null;
        $templatePrefixLC = '';

        $config = MediaDirectoryLibrary::fetchMediaDirListConfigFromTemplate($this->moduleNameLC . $templatePrefix . 'RelatedList', $template, null, $intCategoryId, $intLevelId);

        if (isset($config['list']['latest'])) {
            $latest = $config['list']['latest'];
        }
        if (isset($config['list']['limit'])) {
            $limit = $config['list']['limit'];
        }
        if (isset($config['list']['offset'])) {
            $offset = $config['list']['offset'];
        }
        if (isset($config['filter']['form'])) {
            $formId = $config['filter']['form'];
        }
        if (isset($config['filter']['category'])) {
            $categoryId = $config['filter']['category'];
        }
        if (isset($config['filter']['level'])) {
            $levelId = $config['filter']['level'];
        }
        $associated = false;
        if (isset($config['filter']['associated'])) {
            $associated = true;
            $entryId = $intEntryId;
        }

        // fetch related entries
        $objEntry->resetEntries();
        $objEntry->getEntries($entryId, $levelId, $categoryId, null, $latest, null,
            true, $offset, $limit, null, null, $formId,
            null, 0, 0, $associated);

        // remove currently parsed entry
        unset($objEntry->arrEntries[$intEntryId]);

        // abort in case no related entries are present
        if (empty($objEntry->arrEntries)) {
            // hide block being used to display related entries
            $template->hideBlock($this->moduleNameLC . $templatePrefix . 'RelatedList');
            return;
        }

        // set mediadirRelatedList tempalte block to be parsed
        $objEntry->setStrBlockName($this->moduleNameLC . $templatePrefix . 'RelatedListEntry');

        // prarse related entries
        if (!empty($templatePrefix)) {
            $templatePrefixLC = strtolower($templatePrefix) . '_';
        }
        $objEntry->listEntries($template, 5, $templatePrefixLC . 'related');
    }

    /**
     * Parse previous entry in template block mediadirPreviousEntry
     * See (@see fetchMediaDirListConfigFromTemplate) for a list of functional
     * placeholders to be used in the template.
     *
     * @param   MediaDirectoryEntry $objEntry   Instance of current MediaDirectoryEntry
     * @param   integer $intEntryId ID of the currently processing entry
     * @param   integer $intCategoryId ID of the currently selected category
     * @param   integer $intLevelId ID of the currently selected level
     */
    protected function parsePreviousEntry($objEntry, $intEntryId, $intCategoryId = 0, $intLevelId = 0) {
        // check if we shall parse the previous entry
        if (!$this->_objTpl->blockExists($this->moduleNameLC.'PreviousEntry')) {
            return;
        }

        $latest = null;
        $formId = null;
        $categoryId = null;
        $levelId = null;

        $config = MediaDirectoryLibrary::fetchMediaDirListConfigFromTemplate($this->moduleNameLC.'PreviousEntry', $this->_objTpl, null, $intCategoryId, $intLevelId);

        if (isset($config['list']['latest'])) {
            $latest = $config['list']['latest'];
        }
        if (isset($config['filter']['form'])) {
            $formId = $config['filter']['form'];
        }
        if (isset($config['filter']['category'])) {
            $categoryId = $config['filter']['category'];
        }
        if (isset($config['filter']['level'])) {
            $levelId = $config['filter']['level'];
        }

        // fetch related entries
        $objEntry->resetEntries();
        $objEntry->getEntries(null, $levelId, $categoryId, null, $latest, null, true, null, 'n', null, null, $formId);

        // If the list contains less than two entries, there is no point
        // in proceeding as the previous entry would be the same as the
        // one currently processing.
        // Also, if the currently processing entry is not part of the
        // related list, then there does no real previous entry exists.
        if (
            count($objEntry->arrEntries) < 2 ||
            !isset($objEntry->arrEntries[$intEntryId])
        ) {
            // hide block being used to display previous entry
            $this->_objTpl->hideBlock($this->moduleNameLC.'PreviousEntry');
            return;
        }

        // identify previous entry
        $previousEntryId = 0;
        reset($objEntry->arrEntries);
        while (key($objEntry->arrEntries) !== $intEntryId) {
            $previousEntryId = key($objEntry->arrEntries);
            next($objEntry->arrEntries);
        }

        // In case previousEntryId is not set, then $intEntryId is the first
        // entry in the list ($objEntry->arrEntries).
        // Therefore the previous entry of $intEntryId is the last entry of
        // the list (array)
        if (!$previousEntryId) {
            end($objEntry->arrEntries);
            $previousEntryId = key($objEntry->arrEntries);
        }

        // fetch previous entry
        $objEntry->resetEntries();
        $objEntry->getEntries($previousEntryId, $levelId, $categoryId, null, $latest, null, true, null, 'n', null, null, $formId);

        // set mediadirPreviousEntry tempalte block to be parsed
        $objEntry->setStrBlockName($this->moduleNameLC.'PreviousEntry');

        // parse previous entry
        $objEntry->listEntries($this->_objTpl, 5, 'previous');
    }

    /**
     * Parse next entry in template block mediadirNextEntry
     * See (@see fetchMediaDirListConfigFromTemplate) for a list of functional
     * placeholders to be used in the template.
     *
     * @param   MediaDirectoryEntry $objEntry   Instance of current MediaDirectoryEntry
     * @param   integer $intEntryId ID of the currently processing entry
     * @param   integer $intCategoryId ID of the currently selected category
     * @param   integer $intLevelId ID of the currently selected level
     */
    protected function parseNextEntry($objEntry, $intEntryId, $intCategoryId = 0, $intLevelId = 0) {
        // check if we shall parse the next entry
        if (!$this->_objTpl->blockExists($this->moduleNameLC.'NextEntry')) {
            return;
        }

        $latest = null;
        $formId = null;
        $categoryId = null;
        $levelId = null;

        $config = MediaDirectoryLibrary::fetchMediaDirListConfigFromTemplate($this->moduleNameLC.'NextEntry', $this->_objTpl, null, $intCategoryId, $intLevelId);

        if (isset($config['list']['latest'])) {
            $latest = $config['list']['latest'];
        }
        if (isset($config['filter']['form'])) {
            $formId = $config['filter']['form'];
        }
        if (isset($config['filter']['category'])) {
            $categoryId = $config['filter']['category'];
        }
        if (isset($config['filter']['level'])) {
            $levelId = $config['filter']['level'];
        }

        // fetch related entries
        $objEntry->resetEntries();
        $objEntry->getEntries(null, $levelId, $categoryId, null, $latest, null, true, null, 'n', null, null, $formId);

        // if the list contains less than two entries, there is no point
        // in proceeding as the next entry would be the same as the
        // one currently processing
        // Also, if the currently processing entry is not part of the
        // related list, then there does no real next entry exists.
        if (
            count($objEntry->arrEntries) < 2 ||
            !isset($objEntry->arrEntries[$intEntryId])
        ) {
            // hide block being used to display next entry
            $this->_objTpl->hideBlock($this->moduleNameLC.'NextEntry');
            return;
        }

        // identify next entry
        $nextEntryId = 0;
        end($objEntry->arrEntries);
        while (key($objEntry->arrEntries) !== $intEntryId) {
            $nextEntryId = key($objEntry->arrEntries);
            prev($objEntry->arrEntries);
        }

        // In case nextEntryId is not set, then $intEntryId is the last 
        // entry in the list ($objEntry->arrEntries).
        // Therefore the next entry of $intEntryId is the first entry of
        // the list (array)
        if (!$nextEntryId) {
            reset($objEntry->arrEntries);
            $nextEntryId = key($objEntry->arrEntries);
        }

        // fetch next entry
        $objEntry->resetEntries();
        $objEntry->getEntries($nextEntryId, $levelId, $categoryId, null, $latest, null, true, null, 'n', null, null, $formId);

        // set mediadirNextEntry tempalte block to be parsed
        $objEntry->setStrBlockName($this->moduleNameLC.'NextEntry');

        // parse next entry
        $objEntry->listEntries($this->_objTpl, 5, 'next');
    }


    function showMap()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        // fetch filter config from template
        $config = static::fetchMediaDirListConfigFromTemplate('__global__', $this->_objTpl);
        $intLevelId = null;
        $intCategoryId = null;
        $intCmdFormId = null;
        if (isset($config['filter']['level'])) {
            $intLevelId = $config['filter']['level'];
        }
        if (isset($config['filter']['category'])) {
            $intCategoryId = $config['filter']['category'];
        }
        if (isset($config['filter']['form'])) {
            $intCmdFormId = $config['filter']['form'];
        }

        $objEntry = new MediaDirectoryEntry($this->moduleName);
        $objEntry->getEntries(null,$intLevelId,$intCategoryId,null,null,null,true,null,'n',null,null,$intCmdFormId);
        $objEntry->listEntries($this->_objTpl, 4);
    }



    function showLatest()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get searchform
        $searchTerm = null;
        if ($this->_objTpl->blockExists($this->moduleNameLC.'Searchform')) {
            $objSearch = new MediaDirectorySearch($this->moduleName);
            $objSearch->getSearchform($this->_objTpl);
            $searchTerm = isset($_GET['term']) ? contrexx_input2raw($_GET['term']) : null;
        }

        $objEntry = new MediaDirectoryEntry($this->moduleName);
        $objEntry->getEntries(null, null, null, $searchTerm, true, null, true, null, $this->arrSettings['settingsLatestNumFrontend']);
        $objEntry->listEntries($this->_objTpl, 2);
    }


    function showMyEntries()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get user attributes
        $objFWUser  = \FWUser::getFWUserObject();
        $objUser    = $objFWUser->objUser;
        $intUserId  = intval($objUser->getId());

        //get searchform
        if($this->_objTpl->blockExists($this->moduleNameLC.'Searchform')){
            $objSearch = new MediaDirectorySearch($this->moduleName);
            $objSearch->getSearchform($this->_objTpl);
        }

        $objEntry = new MediaDirectoryEntry($this->moduleName);

        if($this->arrSettings['settingsReadyToConfirm'] == 1) {
            $objEntry->getEntries(null, null, null, null, null, null, true, null, 'n', $intUserId, null, null, true);
        } else {
            $objEntry->getEntries(null, null, null, null, null, null, true, null, 'n', $intUserId);
        }

        $objEntry->listEntries($this->_objTpl, 2);
    }

    /**
     * Show the latest entries
     */
    function getLatestEntries($formId = null, $blockName = null)
    {
        global $objTemplate;

        $blockName = ($blockName == null) ? $this->moduleNameLC.'Latest' : $blockName;
        //If the settings option 'List latest entries in webdesign template' is deactivated
        //then do not parse the latest entries
        if (!$this->arrSettings['showLatestEntriesInWebdesignTmpl']) {
            $objTemplate->hideBlock($blockName);
            return;
        }

        $objEntry = new MediaDirectoryEntry($this->moduleName);
        $objEntry->getEntries(null, null, null, null, true, null, true, null, $this->arrSettings['settingsLatestNumHeadlines'], null, null, $formId);
        $objEntry->setStrBlockName($blockName);

        $objEntry->listEntries($objTemplate, 2);
    }

    function getHeadlines($arrExistingBlocks)
    {
        global $_CORELANG, $objTemplate;

        // only initialize entries in case option 'List latest entries in webdesign template' is active
        if ($this->arrSettings['showLatestEntriesInWebdesignTmpl']) {
            $objEntry = new MediaDirectoryEntry($this->moduleName);
            $objEntry->getEntries(null, null, null, null, null, null, true, null, $this->arrSettings['settingsLatestNumHeadlines']);
        }

        //If the settings option 'List latest entries in webdesign template' is deactivated or no entries found
        //then do not parse the latest entries
        if (!$this->arrSettings['showLatestEntriesInWebdesignTmpl'] || empty($objEntry->arrEntries)) {
            foreach ($arrExistingBlocks as $blockId) {
                $objTemplate->hideBlock($this->moduleNameLC.'Latest_row_' . $blockId);
            }
            return;
        }

        $i=0;
        $r=0;
        $numBlocks = count($arrExistingBlocks);

        foreach ($objEntry->arrEntries as $key => $arrEntry) {
            try {
                $strDetailUrl = $objEntry->getDetailUrlOfEntry($arrEntry, true);
            } catch (MediaDirectoryEntryException $e) {
                $strDetailUrl = '#';
            }

            $objTemplate->setVariable(array(
                $this->moduleLangVar.'_LATEST_ROW_CLASS' =>  $r%2==0 ? 'row1' : 'row2',
                $this->moduleLangVar.'_LATEST_ENTRY_ID' =>  $arrEntry['entryId'],
                $this->moduleLangVar.'_LATEST_ENTRY_VALIDATE_DATE' =>  date("H:i:s - d.m.Y",$arrEntry['entryValdateDate']),
                $this->moduleLangVar.'_LATEST_ENTRY_CREATE_DATE' =>  date("H:i:s - d.m.Y",$arrEntry['entryCreateDate']),
                $this->moduleLangVar.'_LATEST_ENTRY_HITS' =>  $arrEntry['entryHits'],
                $this->moduleLangVar.'_ENTRY_DETAIL_URL' =>  $strDetailUrl,
                'TXT_'.$this->moduleLangVar.'_ENTRY_DETAIL' =>  $_CORELANG['TXT_MEDIADIR_DETAIL'],
            ));

            foreach ($arrEntry['entryFields'] as $key => $strFieldValue) {
                $intPos = $key+1;

                $objTemplate->setVariable(array(
                    $this->moduleLangVar.'_LATEST_ENTRY_FIELD_'.$intPos.'_POS' => $strFieldValue
                ));
            }

            $blockId = $arrExistingBlocks[$i];
            $objTemplate->parse($this->moduleNameLC.'Latest_row_'.$blockId);
            if ($i < $numBlocks-1) {
                ++$i;
            } else {
                $i = 0;
            }
        }
    }

    /**
     * Parse entries in template block mediadirList of supplied template object $template.
     * If $block is set, then the template block identifined by $block will be parsed
     * instead of mediadirList.
     * Using $config the entries to be parsed can be filtered by form, category and/or level
     * association.
     * @param   \Cx\Core\Html\Sigma $template   Template object to be used for parsing
     * @param   string  $block  The template block to be parsed. If not set, the template block mediadirList will be parsed.
     * @param   array   $config Filter the entries to be parsed by form,
     *                          category and/or level association.
     *                          Additionally, the order of the listing can be
     *                          set according to the latest additions.
     *                          Further, the listing can be limited to a
     *                          specific amount of entries. Schema:
     *                  <pre>array(
     *                      'list' => array(
     *                           'latest' => <true|false>,
     *                           'limit' => <limit>,
     *                           'offset' => <offset>
     *                      ),
     *                      'filter' => array(
     *                           'form' => <form-id>,
     *                           'category' => <category-id>,
     *                           'level' => <level-id>
     *                      )
     *                  )</pre>
     */
    public function parseEntries($template, $block = '', $config = array()) {
        $objEntry = new MediaDirectoryEntry($this->moduleName);

        $latest = null;
        $limit = $this->arrSettings['settingsPagingNumEntries'];
        $offset = null;
        $formId = null;
        $categoryId = null;
        $levelId = null;
        $forceAlphabeticalOrder = false;
        $popular = null;

        if (isset($config['list']['latest'])) {
            $latest = $config['list']['latest'];
        }
        if (isset($config['list']['limit'])) {
            $limit = $config['list']['limit'];
        }
        if (isset($config['list']['offset'])) {
            $offset = $config['list']['offset'];
        }
        if (isset($config['filter']['form'])) {
            $formId = $config['filter']['form'];
        }
        if (isset($config['filter']['category'])) {
            $categoryId = $config['filter']['category'];
        }
        if (isset($config['filter']['level'])) {
            $levelId = $config['filter']['level'];
        }
        if (
            !empty($config['sort']['alphabetical']) &&
            $objEntry->arrSettings['settingsIndividualEntryOrder']
        ) {
            $forceAlphabeticalOrder = true;
            $objEntry->arrSettings['settingsIndividualEntryOrder'] = false;
        }
        if (isset($config['sort']['popular'])) {
            $popular = $config['sort']['popular'];
        }

        if (empty($block)) {
            $block = $this->moduleNameLC.'List';
        }

        $objEntry->getEntries(null, $levelId, $categoryId, null, $latest, null, true, $offset, $limit, null, $popular, $formId);
        $objEntry->setStrBlockName($block);
        $objEntry->listEntries($template, 2);

        // reset default order behaviour
        if ($forceAlphabeticalOrder) {
            $objEntry->arrSettings['settingsIndividualEntryOrder'] = true;
        }
    }

    function showPopular()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get searchform
        if($this->_objTpl->blockExists($this->moduleNameLC.'Searchform')){
            $objSearch = new MediaDirectorySearch($this->moduleName);
            $objSearch->getSearchform($this->_objTpl);
        }

        $objEntry = new MediaDirectoryEntry($this->moduleName);
        $objEntry->getEntries(null, null, null, null, null, null, true, null, $this->arrSettings['settingsPopularNumFrontend'], null, true);
        $objEntry->listEntries($this->_objTpl, 2);
    }



    function modifyEntry()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        parent::getSettings();

        $bolFileSizesStatus = true;
        $strOkMessage = '';
        $strErrMessage = '';
        $strOnSubmit = '';
        //count forms
        $objForms = new MediaDirectoryForm(null, $this->moduleName);
        $arrActiveForms = array();

        foreach ($objForms->arrForms as $intFormId => $arrForm) {
            if($arrForm['formActive'] == 1) {
                $arrActiveForms[] = $intFormId;
            }
        }

        //check id and form
        if(!empty($_REQUEST['eid']) || !empty($_REQUEST['entryId'])) {
            if(!empty($_REQUEST['eid'])) {
                $intEntryId = intval($_REQUEST['eid']);
            }
            if(!empty($_REQUEST['entryId'])) {
                $intEntryId = intval($_REQUEST['entryId']);
            }
            $intFormId = intval(substr($_GET['cmd'],4));
        } else {
            $intEntryId = null;
            $intFormId = intval(substr($_GET['cmd'],3));
        }

        $intCountForms = count($arrActiveForms);

        if($intCountForms > 0){
            //check form
            if(intval($intEntryId) == 0 && empty($_REQUEST['selectedFormId']) && empty($_POST['formId']) && $intCountForms > 1 && $intFormId == 0 ) {
                $intFormId = null;

                //get form selector
                $objForms = new MediaDirectoryForm(null, $this->moduleName);
                $objForms->listForms($this->_objTpl, 3, $intFormId);

                //parse blocks
                $this->_objTpl->hideBlock($this->moduleNameLC.'Inputfields');
            } else {
                //save entry data
                if(isset($_POST['submitEntryModfyForm'])) {
                    $objEntry = new MediaDirectoryEntry($this->moduleName);
                    $strStatus = $objEntry->saveEntry($_POST, intval($_POST['entryId']));

                    \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('MediaDir');
                    \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Home');

                    if(!empty($_POST['entryId'])) {
                        $objEntry->getEntries(intval($_POST['entryId']));
                        if($strStatus == true) {
                            if (intval($_POST['readyToConfirm']) == 1) {
                                if($objEntry->arrEntries[intval($_POST['entryId'])]['entryConfirmed'] == 1) {
                                    $bolReadyToConfirmMessage = false;
                                    $bolSaveOnlyMessage = false;
                                } else {
                                    $bolReadyToConfirmMessage = true;
                                    $bolSaveOnlyMessage = false;
                                }
                            } else {
                                $bolReadyToConfirmMessage = false;
                                $bolSaveOnlyMessage = true;
                            }
                            $strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_EDITED'];
                        } else {
                            $strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_EDITED'];
                        }
                    } else {
                        if($strStatus == true) {
                            if (intval($_POST['readyToConfirm']) == 1) {
                                $bolReadyToConfirmMessage = true;
                                $bolSaveOnlyMessage = false;
                            } else {
                                $bolReadyToConfirmMessage = false;
                                $bolSaveOnlyMessage = true;
                            }
                            $strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_ADDED'];
                        } else {
                            $strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_ADDED'];
                        }
                    }

                    if(!empty($_POST['entryId'])) {
                        if($strStatus == true) {
                            $strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_EDITED'];
                        } else {
                            $strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_EDITED'];
                        }
                    } else {
                        if($strStatus == true) {
                            $strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_ADDED'];
                        } else {
                            $strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_ADDED'];
                        }
                    }
                } else {
                    //get form id
                    if(intval($intEntryId) != 0) {
                        //get entry data
                        $objEntry = new MediaDirectoryEntry($this->moduleName);
                        if($this->arrSettings['settingsReadyToConfirm'] == 1) {
                            $objEntry->getEntries($intEntryId,null,null,null,null,null,true,null,'n',null,null,null,true);
                        } else {
                            $objEntry->getEntries($intEntryId);
                        }

                        $intFormId = $objEntry->arrEntries[$intEntryId]['entryFormId'];
                    } else {
                         //set form id
                        if($intCountForms == 1) {
                            $intFormId = intval($arrActiveForms[0]);
                        } else {
                            if($intFormId == 0) {
                                $intFormId = intval($_REQUEST['selectedFormId']);
                            }
                        }
                    }

                    //get inputfield object
                    $objInputfields = new MediaDirectoryInputfield($intFormId, false, null, $this->moduleName);

                    //list inputfields
                    $objInputfields->listInputfields($this->_objTpl, 2, $intEntryId);

                    //get translation status date
                    if($this->arrSettings['settingsTranslationStatus'] == 1) {
                        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                            if ($arrLang['id'] == 2) {
                                $strLangStatus = 'checked="checked" disabled="disabled"';
                            } elseif ($intEntryId != 0) {
                                if(in_array($arrLang['id'], $objEntry->arrEntries[$intEntryId]['entryTranslationStatus'])) {
                                    $strLangStatus = 'checked="checked"';
                                } else {
                                    $strLangStatus = '';
                                }
                            } else {
                                $strLangStatus = '';
                            }

                            $this->_objTpl->setVariable(array(
                                'TXT_'.$this->moduleLangVar.'_TRANSLATION_LANG_NAME' => htmlspecialchars($arrLang['name'], ENT_QUOTES, CONTREXX_CHARSET),
                                $this->moduleLangVar.'_TRANSLATION_LANG_ID' => intval($arrLang['id']),
                                $this->moduleLangVar.'_TRANSLATION_LANG_STATUS' => $strLangStatus,
                            ));

                            $this->_objTpl->parse($this->moduleNameLC.'TranslationLangList');
                        }
                    } else {
                        $this->_objTpl->hideBlock($this->moduleNameLC.'TranslationStatus');
                    }

                    //get ready to confirm
                    if($this->arrSettings['settingsReadyToConfirm'] == 1 && empty($objEntry->arrEntries[$intEntryId]['entryReadyToConfirm']) && empty($objEntry->arrEntries[$intEntryId]['entryConfirmed'])) {
                        $objForm = new MediaDirectoryForm($intFormId, $this->moduleName);
                        if($objForm->arrForms[$intFormId]['formUseReadyToConfirm'] == 1) {
                            $strReadyToConfirm = '<p><input class="'.$this->moduleNameLC.'InputfieldCheckbox" name="readyToConfirm" id="'.$this->moduleNameLC.'Inputfield_ReadyToConfirm" value="1" type="checkbox">&nbsp;'.$_ARRAYLANG['TXT_MEDIADIR_READY_TO_CONFIRM'].'</p>';
                        } else {
                            $strReadyToConfirm = '<input type="hidden" name="readyToConfirm" value="1" />';
                        }
                    } else {
                        $strReadyToConfirm = '<input type="hidden" name="readyToConfirm" value="1" />';
                    }

                    $this->_objTpl->setVariable(array(
                        $this->moduleLangVar.'_READY_TO_CONFIRM' => $strReadyToConfirm,
                    ));

                    //generate javascript
                    parent::setJavascript($this->getSelectorJavascript());
                    parent::setJavascript($objInputfields->getInputfieldJavascript());
                    //parent::setJavascript("\$J().ready(function(){ \$J('.mediadirInputfieldHint').inputHintBox({className:'mediadirInputfieldInfobox',incrementLeft:3,incrementTop:-6}); });");

                    //get form onsubmit
                    $strOnSubmit = parent::getFormOnSubmit($objInputfields->arrJavascriptFormOnSubmit);

                    //parse blocks
                    $this->_objTpl->hideBlock($this->moduleNameLC.'Forms');
                }
            }

            if (!empty($_SESSION[$this->moduleNameLC]) && empty($_SESSION[$this->moduleNameLC]['bolFileSizesStatus'])) {
                $strFileMessage = '<div class="'.$this->moduleNameLC.'FileErrorMessage">'.$_ARRAYLANG['TXT_MEDIADIR_IMAGE_ERROR_MESSAGE'].'</div>';
                unset($_SESSION[$this->moduleNameLC]['bolFileSizesStatus']);
            } else {
                $strFileMessage = '';
            }


            //parse global variables
            $this->_objTpl->setVariable(array(
                $this->moduleLangVar.'_ENTRY_ID' => $intEntryId,
                $this->moduleLangVar.'_FORM_ID' => $intFormId,
                'TXT_'.$this->moduleLangVar.'_SUBMIT' =>  $_ARRAYLANG['TXT_'.$this->moduleLangVar.'_SUBMIT'],
                $this->moduleLangVar.'_FORM_ONSUBMIT' =>  $strOnSubmit,
                'TXT_'.$this->moduleLangVar.'_PLEASE_CHECK_INPUT' =>  $_ARRAYLANG['TXT_MEDIADIR_PLEASE_CHECK_INPUT'],
                'TXT_'.$this->moduleLangVar.'_OK_MESSAGE' =>  $strOkMessage.$strFileMessage,
                'TXT_'.$this->moduleLangVar.'_ERROR_MESSAGE' =>  $strErrMessage.$strFileMessage,
                $this->moduleLangVar.'_MAX_CATEGORY_SELECT' =>  $strErrMessage,
                'TXT_'.$this->moduleLangVar.'_TRANSLATION_STATUS' => $_ARRAYLANG['TXT_MEDIADIR_TRANSLATION_STATUS'],
            ));

            if(!empty($strOkMessage)) {
                $this->_objTpl->touchBlock($this->moduleNameLC.'EntryOkMessage');
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryErrMessage');
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryModifyForm');
                if($bolReadyToConfirmMessage) {
                    $this->_objTpl->touchBlock($this->moduleNameLC.'EntryReadyToConfirmMessage');
                    $this->_objTpl->hideBlock($this->moduleNameLC.'EntryOkMessage');
                }
                if($bolSaveOnlyMessage) {
                    $this->_objTpl->touchBlock($this->moduleNameLC.'EntrySaveOnlyMessage');
                    $this->_objTpl->hideBlock($this->moduleNameLC.'EntryOkMessage');
                }
            } else if(!empty($strErrMessage)) {
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryOkMessage');
                $this->_objTpl->touchBlock($this->moduleNameLC.'EntryErrMessage');
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryModifyForm');
            } else {
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryOkMessage');
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryErrMessage');
                $this->_objTpl->parse($this->moduleNameLC.'EntryModifyForm');
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntryReadyToConfirmMessage');
                $this->_objTpl->hideBlock($this->moduleNameLC.'EntrySaveOnlyMessage');
            }
        } else {
            header("Location: index.php?section=".$_GET['section']);
            exit;
        }

    }

    function deleteEntry()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //save entry data
        $strOkMessage  = '';
        $strErrMessage = '';
        if(isset($_POST['submitEntryModfyForm']) && intval($_POST['entryId'])) {
            $objEntry = new MediaDirectoryEntry($this->moduleName);

            $strStatus = $objEntry->deleteEntry(intval($_POST['entryId']));

            if($strStatus == true) {
                $strOkMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_SUCCESSFULLY_DELETED'];
            } else {
                $strErrMessage = $_ARRAYLANG['TXT_MEDIADIR_ENTRY']." ".$_ARRAYLANG['TXT_MEDIADIR_CORRUPT_DELETED'];
            }
        }

         //check id
        if(intval($_GET['eid']) != 0) {
            $intEntryId = intval($_GET['eid']);
        } else {
            $intEntryId = null;
        }

        $objEntry = new MediaDirectoryEntry($this->moduleName);

        if($this->arrSettings['settingsReadyToConfirm'] == 1) {
            $objEntry->getEntries($intEntryId,null,null,null,null,null,true,null,1,null,null,null,true);
        } else {
            $objEntry->getEntries($intEntryId,null,null,null,null,null,1,null,1);
        }


        $objEntry->listEntries($this->_objTpl, 2);

        //parse global variables
        $this->_objTpl->setVariable(array(
            $this->moduleLangVar.'_ENTRY_ID' => $intEntryId,
            'TXT_'.$this->moduleLangVar.'_DELETE' =>  $_CORELANG['TXT_ACCESS_DELETE_ENTRY'],
            'TXT_'.$this->moduleLangVar.'_ABORT' =>  $_CORELANG['TXT_CANCEL'],
            'TXT_'.$this->moduleLangVar.'_OK_MESSAGE' =>  $strOkMessage,
            'TXT_'.$this->moduleLangVar.'_ERROR_MESSAGE' =>  $strErrMessage,
        ));

        if(!empty($strOkMessage)) {
            $this->_objTpl->parse($this->moduleNameLC.'EntryOkMessage');
            $this->_objTpl->hideBlock($this->moduleNameLC.'EntryErrMessage');
            $this->_objTpl->hideBlock($this->moduleNameLC.'EntryModifyForm');
        } else if(!empty($strErrMessage)) {
            $this->_objTpl->hideBlock($this->moduleNameLC.'EntryOkMessage');
            $this->_objTpl->parse($this->moduleNameLC.'EntryErrMessage');
            $this->_objTpl->parse($this->moduleNameLC.'EntryModifyForm');
        } else {
            $this->_objTpl->hideBlock($this->moduleNameLC.'EntryOkMessage');
            $this->_objTpl->hideBlock($this->moduleNameLC.'EntryErrMessage');
            $this->_objTpl->parse($this->moduleNameLC.'EntryModifyForm');
        }
    }

    /**
     * Parse the component's own breadcrumb
     *
     * @param   integer $intCategoryId  ID of the requested category
     * @param   integer $intLevelId  ID of the requested level
     * @param   \Cx\Core\Html\Sigma $template Optional template object to be used instead of the component's own template object ($this->_objTpl)
     */
    public function getNavtree($intCategoryId, $intLevelId, $template = null) {
        global $_ARRAYLANG;

        // if no specific \Cx\Core\Html\Sigma template is set,
        // do use the currently loaded template
        if (!$template) {
            $template = $this->_objTpl;
        }

        // abort in case the associated template block is missing
        if (!$template->blockExists($this->moduleNameLC.'Navtree') && ($intCategoryId != 0 || $intLevelId != 0)){
            return;
        }

        // abort in case no category or level data is set
        if (!$intCategoryId && !$intLevelId) {
            return;
        }

        // load categories into tree
        if($intCategoryId != 0) {
           $this->getNavtreeCategories($intCategoryId);
        }

        // load levels into tree
        if($intLevelId != 0 && $this->arrSettings['settingsShowLevels'] == 1) {
           $this->getNavtreeLevels($intLevelId);
        }

        //set pagetitle
        krsort($this->arrNavtree);
        $this->metaTitle = $this->pageTitle." - ".strip_tags(join(" - ", $this->arrNavtree));

        if(isset($_GET['cmd'])) {
            $strOverviewCmd = '&amp;cmd='.$_GET['cmd'];
        } else {
            $strOverviewCmd = null;
        }

        $arrEntry = null;
        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
        if (isset($requestParams['eid'])) {
            $arrEntry = $this->getCurrentFetchedEntryDataObject()->arrEntries[$requestParams['eid']];
        }

        // fetch associated overview page of entry
        $url = $this->getAutoSlugPath($arrEntry, null, null, false, false);

        if ($url) {
            $this->arrNavtree[] = '<a href="'.$url.'">'.$_ARRAYLANG['TXT_MEDIADIR_OVERVIEW'].'</a>';
        }
        krsort($this->arrNavtree);

        if(!empty($this->arrNavtree)) {
            $i = 0;
            $count = count($this->arrNavtree);
            foreach ($this->arrNavtree as $key => $strName) {
                $strClass = $i == $count -1 ? 'last' : '';
                $strSeparator = $i == 0 ? '' : '&gt;';
                $url = '';
                $title = '';

                // Note: the following is a workaround as the array
                // $this->arrNavtree does not contain normalized data,
                // but instead already the processed HTML-links.
                //
                // Load HTML code of navtree element into a DOMDocument
                $domDocument = new \DOMDocument();
                $domDocument->loadHTML($strName);
                if ($domDocument) {
                    // fetch link tags
                    $nodeList = $domDocument->getElementsByTagName('a');

                    // check if the navtree element was an actual HTML-link
                    if ($nodeList->length) {
                        // as the HTML-code did only contain one link element,
                        // the first one (index 0) will be our navtree element
                        $item = $nodeList->item(0);
                        if ($item) {
                            $url = $item->getAttribute('href');
                            $title = $item->textContent;
                        }
                    } else {
                        // in case the navtree element was not a HTML-link,
                        // we shall only set the TITLE placeholder
                        $title = $strName;
                    }
                }
                $template->setVariable(array(
                    $this->moduleLangVar.'_NAVTREE_LINK'        =>  $strName,
                    $this->moduleLangVar.'_NAVTREE_LINK_SRC'    =>  $url,
                    $this->moduleLangVar.'_NAVTREE_LINK_TITLE'  =>  $title,
                    $this->moduleLangVar.'_NAVTREE_LINK_CLASS'  =>  $strClass,
                    $this->moduleLangVar.'_NAVTREE_SEPARATOR'   =>  $strSeparator
                ));

                $i++;
                $template->parse($this->moduleNameLC.'NavtreeElement');
            }
            $template->parse($this->moduleNameLC.'Navtree');
        } else {
            $template->hideBlock($this->moduleNameLC.'Navtree');
        }
    }

    function getNavtreeCategories($intCategoryId)
    {
        $objCategory = new MediaDirectoryCategory($intCategoryId, null, 0, $this->moduleName);

        $levelId = null;
        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
        if (isset($requestParams['lid'])) {
            $levelId = intval($requestParams['lid']);
        }

        // link category if an associated application page does exist
        $url = $this->getAutoSlugPath(null, $intCategoryId, $levelId);
        if ($url) {
            $this->arrNavtree[] = '<a href="'.$url.'">'.contrexx_raw2xhtml($objCategory->arrCategories[$intCategoryId]['catName'][0]).'</a>';
        } else {
            $this->arrNavtree[] = contrexx_raw2xhtml($objCategory->arrCategories[$intCategoryId]['catName'][0]);
        }

        if($objCategory->arrCategories[$intCategoryId]['catParentId'] != 0) {
            $this->getNavtreeCategories($objCategory->arrCategories[$intCategoryId]['catParentId']);
        }
    }

    function getNavtreeLevels($intLevelId)
    {
        $objLevel = new MediaDirectoryLevel($intLevelId, null, 0, $this->moduleName);

        // link level if an associated application page does exist
        $url = $this->getAutoSlugPath(null, null, $intLevelId);
        if ($url) {
            $this->arrNavtree[] = '<a href="'.$url.'">'.contrexx_raw2xhtml($objLevel->arrLevels[$intLevelId]['levelName'][0]).'</a>';
        } else {
            $this->arrNavtree[] = contrexx_raw2xhtml($objLevel->arrLevels[$intLevelId]['levelName'][0]);
        }

        if($objLevel->arrLevels[$intLevelId]['levelParentId'] != 0) {
            $this->getNavtreeLevels($objLevel->arrLevels[$intLevelId]['levelParentId']);
        }
    }

    /**
     * Get the page title
     *
     * @return string
     */
    public function getPageTitle() {
        return $this->pageTitle;
    }

    /**
     * Get the meta title
     *
     * @return string
     */
    public function getMetaTitle() {
        return contrexx_html2plaintext($this->metaTitle);
    }

    /**
     * Get the meta description
     *
     * @return string
     */
    public function getMetaDescription() {
        return contrexx_html2plaintext($this->metaDescription);
    }

    /**
     * Get the meta image
     *
     * @return string
     */
    public function getMetaImage() {
        return $this->metaImage;
    }

    /**
     * Returns the metakeys
     * @return string The meta keywords separated by comma
     */
    public function getMetaKeys() {
        return $this->metaKeys;
    }

    public function getSlug() {
        return $this->slug;
    }
}

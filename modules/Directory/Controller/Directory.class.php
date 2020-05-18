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
 * RSS Directory
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_directory
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Directory\Controller;

/**
 * Includes
 */
\Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/PEAR/XML/RSS.class.php');

/**
 * RSS Directory
 *
 * functions for the directory
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @access      public
 * @package     cloudrexx
 * @subpackage  module_directory
 */
class Directory extends DirectoryLibrary
{
    public $ArraySettings;
    public $statusMessage;
    public $folderImageLarge;
    public $folderImageSmall;
    public $latestFeeds = array();
    public $navtree;
    public $navtreeLevels = array();
    public $navtreeCategories = array();
    public $searchResults = array();
    public $searchCategories = array();
    public $searchLevels = array();
    public $settings = array();
    public $arrFeedContent = array();
    public $feedType;
    public $typeSelection;
    public $rssTitle;
    public $rssRefresh;
    public $rssImage;
    public $rssPath;
    public $rssWebPath;
    public $arrClient = array();
    public $arrProxy = array();
    public $count = array();
    public $communityModul;

    //local settings
    public $rows = 2;
    public $subLimit = 5;
    public $rowWidth = "50%";
    public $arrRows = array();
    public $arrRowsIndex = array();

    /**
     * Constructor
     */
    function __construct($pageContent)
    {
        $this->pageContent = $pageContent;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_DOCUMENT_ROOT);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $this->path = ASCMS_DIR_PATH . '/';
        $this->webPath = ASCMS_DIR_WEB_PATH . '/';
        $this->imagePath = ASCMS_DIR_PATH . '/View/Media';
        $this->imageWebPath = ASCMS_DIR_WEB_PATH . '/View/Media';
        $this->mediaPath = $cx->getWebsiteMediaDirectoryPath() . '/';
        $this->mediaWebPath = $cx->getWebsiteMediaDirectoryWebPath() . '/';
        $this->rssPath = ASCMS_DIRECTORY_FEED_PATH . '/';
        $this->rssWebPath = ASCMS_DIRECTORY_FEED_WEB_PATH. '/';

        $this->folderImageLarge = "<img src='../../modules/Directory/View/Media/_folder_24.gif' alt='' />";
        $this->folderImageSmall = "<img src='../../modules/Directory/View/Media/_folder.gif' alt='' />";

        //create latest xml. (Dave, 2009-03-04: This sucks, as it causes the start
        //                    page to break if the FTP server doesn't work. And why
        //                    the hell do we need to re-create the RSS here anyhow?)
        #$this->createRSSlatest();

        //get settings
        $this->settings = $this->getSettings();

        //check community modul
        $objModulManager = new \Cx\Core\ComponentManager\Controller\ComponentManager();
        $arrInstalledModules = $objModulManager->getModules();
        if (in_array(23, $arrInstalledModules)) {
            $this->communityModul = true;
        } else {
            $this->communityModul = false;
        }
    }

    //contrexx_addslashes(

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
        \Cx\Core\Csrf\Controller\Csrf::add_code();
        if (!isset($_REQUEST['cmd'])) {
            $_REQUEST['cmd'] = '';
        }
        if (!isset($_GET['lid'])) {
            $_GET['lid'] = '';
        }
        switch ($_REQUEST['cmd']) {
            case 'detail':
                $this->getHits(intval($_GET['id']));
                $this->feedDetails(intval($_GET['id']), intval($_GET['cid']), intval($_GET['lid']));
                break;
            case 'add':
                $this->newFeed();
                break;
            case 'myfeeds':
                $this->myFeeds();
                break;
            case 'latest':
                $this->latest();
                break;
            case 'edit':
                \Cx\Core\Csrf\Controller\Csrf::check_code();
                $this->editFeed();
                break;
            case 'search':
                $this->searchFeed();
                break;
            case 'vote':
                \Cx\Core\Csrf\Controller\Csrf::check_code();
                $this->voteFeed();
                break;
            default:
                if (isset($_GET['linkid'])) {
                    $this->redirectFeed(intval($_GET['linkid']));
                }
                $this->overview();
        }
        return $this->_objTpl->get();
    }


    /**
     * overview
     *
     * shows all
     *
     * @access   public
     * @param    string  $parentId
     * @global    ADONewConnection
     * @global    array
     */
    function overview()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        if (isset($_GET['lid'])) {
            $lId = intval($_GET['lid']);
        } else {
            $lId = 0;
        }
        if (isset($_GET['cid'])) {
            $cId = intval($_GET['cid']);
        } else {
            $cId = 0;
        }
        //xml link
        $xmlLink = $this->rssWebPath."directory_latest.xml";
        //get navtree
        $this->getNavtree($lId, $cId);
        //get search
        $this->getSearch();
        //select View
        if ($this->settings['levels']['value'] == 1 && $cId == 0) {
            $arrAttributes = $this->showLevels($lId);
        } else {
            $arrAttributes = $this->showCategories($cId);
        }

        if ($this->_objTpl->blockExists('showTitle')) {
            $this->_objTpl->parse('showTitle');
        }

        $insertFeeds = '';
        if ($cId == 0 && $lId == 0) {
            $objResult = $objDatabase->SelectLimit("SELECT COUNT(DISTINCT files.id) AS feedCount FROM ".DBPREFIX."module_directory_dir AS files INNER JOIN ".DBPREFIX."module_directory_rel_dir_cat AS rel ON(rel.dir_id=files.id)
            INNER JOIN ".DBPREFIX."module_directory_categories AS cat ON(cat.id=rel.cat_id) WHERE files.status='1' AND cat.showentries='1'", 1);
            if(!isset($objResult->fields['feedCount']))
                $allFeeds=0;
            else
                $allFeeds = $objResult->fields['feedCount'];

            $insertFeeds = str_replace('%COUNT%', '<b>'.$allFeeds.'</b>', $_ARRAYLANG['TXT_INSERT_FEEDS']);

            if ($this->_objTpl->blockExists('showInsertFeeds'))
                $this->_objTpl->parse('showInsertFeeds');
            if ($this->_objTpl->blockExists('showTitle'))
                $this->_objTpl->hideBlock('showTitle');
        }

        if ($this->settings['description']['value'] == 0) {
            $arrAttributes['description'] = '';
        }

        //select View
       /*if ($this->settings['indexview']['value'] == 1) {
            $this->arrRows ='';
            $i = 0;
            $firstCol = true;
            ksort($this->arrRowsIndex);
            foreach($this->arrRowsIndex as $rowKey => $rowName){
                if ($index != substr($rowName, 0, 1)) {
                    $index = substr($rowName, 0, 1);
                    if($i%$this->rows==0){
                        $i=1;
                    }else{
                        $i++;
                    }

                    $this->arrRows[$i] .= (!$firstCol ? "<br />" : "")."<b>".$index."</b><br />".substr($rowName,1);
                    if ($i == $this->rows && $firstCol) {
                        $firstCol = false;
                    }
                } else {
                    $this->arrRows[$i] .= substr($rowName,1);
                }
            }
        }*/


        //select View
        if ($this->settings['indexview']['value'] == 1) {
            $i = 0;
            $firstCol = true;
            ksort($this->arrRowsIndex);
// TODO: $index is not defined
$index = null;
// $this->arrRows needs to be initialized for all rows needed
// (currently, indices 1 and 2 are displayed below, 0 might get appended to)
$this->arrRows[0] = '';
$this->arrRows[1] = '';
$this->arrRows[2] = '';
            foreach ($this->arrRowsIndex as $rowName) {
                if ($index != substr($rowName, 0, 1)) {
                    $index = substr($rowName, 0, 1);
                    if ($i % $this->rows == 0) {
                        $i = 1;
                    } else {
                        $i++;
                    }
                    $this->arrRows[$i] .= (!$firstCol ? "<br />" : "")."<b>".$index."</b><br />".substr($rowName, 1);
                    if ($i == $this->rows && $firstCol) {
                        $firstCol = false;
                    }
                } else {
                    $this->arrRows[$i] .= substr($rowName, 1);
                }
            }
        }

        // set variables
        $description    = isset($arrAttributes['description']) ? $arrAttributes['description'] : '';
        $directoryTitle = '';
        if (isset($arrAttributes['title'])) {
            $directoryTitle = contrexx_raw2xhtml($arrAttributes['title']);
        }

        $this->_objTpl->setVariable(array(
            'DIRECTORY_TREE' => $this->navtree,
            'DIRECTORY_DESCRIPTION' => "<br />". $description,
            'TYPE_SELECTION' => $this->typeSelection,
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
// TODO: Not defined
//            'DIRECTORY_SEARCH_PAGING' => $paging,
            'DIRECTORY_ROW_WIDTH' => $this->rowWidth,
            'DIRECTORY_ROW1' => $this->arrRows[1]."<br />",
            'DIRECTORY_ROW2' => $this->arrRows[2]."<br />",
            'DIRECTORY_TITLE' => $directoryTitle,
            'DIRECTORY_XML_LINK' => $xmlLink,
            'DIRECTORY_INSERT_FEEDS' => $insertFeeds,
        ));
    }


    function showLevels($parentId)
    {
        global $objDatabase, $_ARRAYLANG;

// TODO: $showlevels is not defined
        if (!isset($showlevels)) {
           $arrLevel['showlevels'] = 1;
        }

        //get levels
        $objResult = $objDatabase->Execute("
            SELECT id, parentid, name
              FROM ".DBPREFIX."module_directory_levels
              WHERE status='1'
                AND parentid='$parentId'
              ORDER BY displayorder
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->levels['name'][$objResult->fields['id']] = $objResult->fields['name'];
                $this->levels['parentid'][$objResult->fields['id']] = $objResult->fields['parentid'];
                $objResult->MoveNext();
            }
        }

        //get level attributes
        $query = "
            SELECT name, description, showcategories, showlevels, onlyentries
              FROM ".DBPREFIX."module_directory_levels
             WHERE status='1'
               AND id='$parentId'
        ";
        $objResult = $objDatabase->Execute($query);
        if($objResult !== false){
            while(!$objResult->EOF){
                $arrLevel['title'] = $objResult->fields['name'];
                $arrLevel['description'] = $objResult->fields['description'];
                $arrLevel['showentries'] = $objResult->fields['showentries'];
                $arrLevel['showcategories'] = $objResult->fields['showcategories'];
                $arrLevel['showlevels'] = $objResult->fields['showlevels'];
                $arrLevel['onlyentries'] = $objResult->fields['onlyentries'];
                $objResult->MoveNext();
            }
        }


        //show level
        $i = 1;
        if (!empty($this->levels) && $arrLevel['showlevels'] == 1 && !isset($_GET['cid'])) {
            foreach ($this->levels['name'] as $levelKey => $levelName) {
                //count entries
                $count = $this->count($levelKey, '');
// TODO: Never used
//                $class = $parentId == 0 ? "catLink" : "subcatLink";
                $this->arrRows[$i] .= "<a class='catLink' href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;lid=".$levelKey."'>".htmlentities($levelName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />";
                $this->arrRowsIndex[strtoupper(htmlentities($levelName, ENT_QUOTES, CONTREXX_CHARSET).$levelKey)] = strtoupper(substr(htmlentities($levelName, ENT_QUOTES, CONTREXX_CHARSET), 0, 1))."<a class='catLink' href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;lid=".$levelKey."'>".htmlentities($levelName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />";

                //get sublevel
                if ($this->levels['parentid'][$levelKey] == 0) {
                    $objResult = $objDatabase->Execute("SELECT id, name FROM ".DBPREFIX."module_directory_levels WHERE status = '1' AND parentid =".contrexx_addslashes($levelKey)." ORDER BY displayorder LIMIT ".contrexx_addslashes($this->subLimit)."");
                    if ($objResult !== false) {
                        while (!$objResult->EOF) {
                            $this->arrRows[$i] .= "<a class='subcatLink' href='".CONTREXX_DIRECTORY_INDEX."?section=Directory&amp;lid=".$objResult->fields['id']."''>".htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)."</a>, ";
                            $objResult->MoveNext();
                        }
                    }

                    if ($objResult->RecordCount() != 0) {
                        $this->arrRows[$i] .= "<br />";
                    }
                }

                if ($i%$this->rows==0) {
                    $i=1;
                } else {
                    $i++;
                }
            }
        }

        if (!isset($parentId) || $parentId == 0) {
            if ($this->_objTpl->blockExists('showLatest')) {
                $this->_objTpl->touchBlock('showLatest');
                $this->getLatest();
            }
        }

        //get feeds
        if ($arrLevel['onlyentries'] == 1) {
            $this->getFeeds('', $_GET['lid']);
            $this->_objTpl->hideBlock('showCategories');
            if ($this->_objTpl->blockExists('showLatest')) {
                $this->_objTpl->hideBlock('showLatest');
            }
        }

        if ($arrLevel['showcategories'] == 1 || empty($this->levels)) {
            if (isset($_GET['cid'])) {
                $arrCategories = $this->showCategories(intval($_GET['cid']));
                $arrLevel['title'] = $arrCategories['title'];
                $arrLevel['description'] = $arrCategories['description'];
                $arrLevel['showentries'] = $arrCategories['showentries'];
            } else {
                $this->showCategories(0);
            }
        }
        return $arrLevel;
    }


    function showCategories($parentId)
    {
        global $objDatabase, $_ARRAYLANG;

        if (!empty($_GET['lid'])) {
            $levelLink = "&amp;lid=".intval($_GET['lid']);
        } else {
            $levelLink = "";
        }

        //get categories
        $objResult = $objDatabase->Execute("
            SELECT id, parentid, name, showentries
              FROM ".DBPREFIX."module_directory_categories
             WHERE status='1'
               AND parentid=$parentId
             ORDER BY displayorder ASC
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->categories['name'][$objResult->fields['id']] = $objResult->fields['name'];
                $this->categories['parentid'][$objResult->fields['id']] = $objResult->fields['parentid'];
                $objResult->MoveNext();
            }
        }
        //get categorie attributes
        $arrCategories = array();
        $objResult = $objDatabase->Execute("
            SELECT id, name, description, showentries
              FROM ".DBPREFIX."module_directory_categories
             WHERE status='1'
               AND id=$parentId
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $arrCategories['title'] = $objResult->fields['name'];
                $arrCategories['description'] = $objResult->fields['description'];
                $arrCategories['showentries'] = $objResult->fields['showentries'];
                $objResult->MoveNext();
            }
        }

        //show categories
        $i = 1;
        if (!empty($this->categories)) {
            foreach ($this->categories['name'] as $catKey => $catName) {
                //count entries
                $count = $this->count($_GET['lid'], $catKey);
// TODO: Never used
//                $class = $parentId == 0 ? "catLink" : "subcatLink";
                if (!isset($this->arrRows[$i])) $this->arrRows[$i] = '';
                $this->arrRows[$i] .=
                    "<a class='catLink' href='".CONTREXX_SCRIPT_PATH."?section=Directory".
                    $levelLink."&amp;cid=".$catKey."'>".
                    htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET).
                    "</a>&nbsp;(".$count.")<br />";
                $this->arrRowsIndex[strtoupper(htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET).$catKey)] = strtoupper(substr(htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET), 0, 1))."<a class='catLink' href='".CONTREXX_SCRIPT_PATH."?section=Directory".$levelLink."&amp;cid=".$catKey."'>".htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />";

                //get subcategories
                if ($this->categories['parentid'][$catKey] == 0) {
                    $objResult = $objDatabase->SelectLimit("
                        SELECT id, name
                          FROM ".DBPREFIX."module_directory_categories
                         WHERE status='1'
                           AND parentid=$catKey
                         ORDER BY displayorder",
                        $this->subLimit
                    );
                    if ($objResult !== false) {
                        $strSubCategories = '';
                        while (!$objResult->EOF) {
                            $strSubCategories .=
                                (empty($strSubCategories) ? '' : ', ').
                                "<a class='subcatLink' href='".
                                CONTREXX_SCRIPT_PATH."?section=Directory".
                                $levelLink."&amp;cid=".$objResult->fields['id']."''>".
                                htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)."</a>";
                            $objResult->MoveNext();
                        }
                        $this->arrRows[$i] .= $strSubCategories;
                    }
                    if ($objResult->RecordCount() != 0) {
                        $this->arrRows[$i] .= "<br />";
                    }
                }

                if ($i % $this->rows == 0) {
                    $i = 1;
                } else {
                    ++$i;
                }
            }
        } else {
            $this->_objTpl->hideBlock('showCategories');
        }
        if ((!isset($parentId) || $parentId == 0) && $this->settings['levels']['value'] != 1) {
            if ($this->_objTpl->blockExists('showLatest')) {
                $this->_objTpl->touchBlock('showLatest');
                $this->getLatest();
            }
        }
        //get feeds
        if ($parentId != 0 && $arrCategories['showentries'] == '1') {
            $this->getFeeds($parentId, $_GET['lid']);
        }
        return $arrCategories;
    }



    /**
     * get navigation
     *
     * get categories navigation
     *
     * @access   public
     * @param    string  $id
     * @global    ADONewConnection
     * @global    array
     */
    function getFeeds($cid, $lid)
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($cid)) {
            $catLink = "&cid=".$cid;
        }

        if (isset($lid)) {
            $levelLink = "&lid=".$lid;
            $lid = intval($lid);
        }

        if ($this->settings['sortOrder']['value'] == 1) {
                $order = "files.title";
        } else {
            $order = "files.id DESC";
        }

        if (!empty($lid) && !empty($cid)) {
            $where = "WHERE (rel_cat.cat_id='".$cid."' AND rel_cat.dir_id=files.id)
                      AND   (rel_level.level_id='".$lid."' AND rel_level.dir_id=files.id) ";
            $db = DBPREFIX."module_directory_rel_dir_cat AS rel_cat, ".DBPREFIX."module_directory_rel_dir_level AS rel_level,";
        } elseif (!empty($cid)) {
            $where = "WHERE (rel_cat.cat_id='".$cid."' AND rel_cat.dir_id=files.id)";
            $db = DBPREFIX."module_directory_rel_dir_cat AS rel_cat,";
        } else {
            $where = "WHERE (rel_level.level_id='".$lid."' AND rel_level.dir_id=files.id) ";
            $db = DBPREFIX."module_directory_rel_dir_level AS rel_level,";
        }

        //create query
        $query = "
            SELECT files.id AS id
              FROM $db ".DBPREFIX."module_directory_dir AS files
                   $where AND status='1'
             GROUP BY files.id
             ORDER BY files.spezial DESC, $order
        ";

        ////// paging start /////////
        $pagingLimit = intval($this->settings['pagingLimit']['value']);
        $objResult = $objDatabase->Execute($query);
        $count = $objResult->RecordCount();
        $pos = (isset($_GET['pos']) ? intval($_GET['pos']) : 0);
        $paging = getPaging($count, $pos, "&section=Directory".$levelLink.$catLink, "<b>".$_ARRAYLANG['TXT_DIRECTORY_FEEDS']."</b>", true, $pagingLimit);
        ////// paging end /////////

        $objResult = $objDatabase->SelectLimit($query, $pagingLimit, $pos);
        if ($objResult !== false) {
            $i = 0;
            while (!$objResult->EOF) {
                //get content
                $this->getContent($objResult->fields['id'], $cid, $lid);
                //get voting
                $this->getVoting($objResult->fields['id'], $cid, $lid);
                //get votes
                $this->getVotes($objResult->fields['id']);
                //get attributes
                $this->getAttributes($objResult->fields['id']);
                //row class
                $this->_objTpl->setVariable(
                    'DIRECTORY_FEED_ROW', (++$i % 2 ? 'row1' : 'row2')
                );

                //check paging
                if ($count<$pagingLimit) {
                    $paging = "";
                }

                $this->_objTpl->parse('showFeeds');

                $objResult->MoveNext();
            }
        }

        // set variables
        $this->_objTpl->setGlobalVariable(array(
            'DIRECTORY_CAT_ID'            => $cid,
            'DIRECTORY_LEVEL_ID'           => $lid
        ));

        // set variables
        $this->_objTpl->setVariable(array(
            'SEARCH_PAGING' => $paging,
            'DIRECTORY_MAIN_CAT_ID' => $cid,
            'DIRECTORY_MAIN_LEVEL_ID' => $lid
        ));


        if ($count == 0) {
            // set variables
            $this->_objTpl->setVariable(array(
                'DIRECTORY_NO_FEEDS_FOUND' => $_ARRAYLANG['DIRECTORY_NO_FEEDS_FOUND'],
            ));

            $this->_objTpl->parse('noFeeds');
            $this->_objTpl->hideBlock('showFeeds');
        }
    }



    function getLatest() {

        global $objDatabase, $_ARRAYLANG;

        $objResult = $objDatabase->Execute("SELECT DISTINCT files.id FROM ".DBPREFIX."module_directory_dir AS files
        INNER JOIN ".DBPREFIX."module_directory_rel_dir_cat AS rel ON(rel.dir_id=files.id)
        INNER JOIN ".DBPREFIX."module_directory_categories AS cat ON(cat.id=rel.cat_id)
        WHERE files.status = '1' AND cat.showentries='1' ORDER BY id DESC LIMIT 5");
        if ($objResult !== false) {
            $i = 0;
            while (!$objResult->EOF) {
                //get content
                $this->getContent($objResult->fields['id'], "");
                //get votes
                $this->getVotes($objResult->fields['id']);
                //get voting
                $this->getVoting($objResult->fields['id']);
                //row class
                $this->_objTpl->setVariable(
                    'DIRECTORY_FEED_ROW', (++$i % 2 ? 'row1' : 'row2')
                );
                $this->_objTpl->parse('showFeeds');
                $objResult->MoveNext();
            }
        }
    }


    /**
     * get search
     *
     * @access   public
     * @param    string  $id
     */
    function getSearch()
    {
        global $objDatabase, $_ARRAYLANG;

        $language = (isset($_REQUEST['language']) ? $_REQUEST['language'] : '');
        $platform = (isset($_REQUEST['platform']) ? $_REQUEST['platform'] : '');
        $canton = (isset($_REQUEST['canton'])   ? $_REQUEST['canton']   : '');
        $spezField21 = (isset($_REQUEST['spez_field_21']) ? $_REQUEST['spez_field_21'] : '');
        $spezField22 = (isset($_REQUEST['spez_field_22']) ? $_REQUEST['spez_field_22'] : '');
        $spezField23 = (isset($_REQUEST['spez_field_23']) ? $_REQUEST['spez_field_23'] : '');
        $spezField24 = (isset($_REQUEST['spez_field_24']) ? $_REQUEST['spez_field_24'] : '');

        $arrDropdown['language'] = $this->getLanguages(contrexx_addslashes($language));
        $arrDropdown['platform'] = $this->getPlatforms(contrexx_addslashes($platform));
        $arrDropdown['canton'] = $this->getCantons(contrexx_addslashes($canton));
        $arrDropdown['spez_field_21'] = $this->getSpezDropdown(contrexx_addslashes($spezField21),'spez_field_21');
        $arrDropdown['spez_field_22'] = $this->getSpezDropdown(contrexx_addslashes($spezField22),'spez_field_22');
        $arrDropdown['spez_field_23'] = $this->getSpezVotes(contrexx_addslashes($spezField23),'spez_field_23');
        $arrDropdown['spez_field_24'] = $this->getSpezVotes(contrexx_addslashes($spezField24),'spez_field_24');

        $expSearch = '';
        $javascript =
            '<script type="text/javascript">'."\n".
            '// <![CDATA['."\n".
            'function toggle(target) {'."\n".
            '  obj = document.getElementById(target);'."\n".
            '  obj.style.display = (obj.style.display == "none" ? "inline" : "none");'."\n".
            '  if (obj.style.display == "none" && target == "hiddenSearch") {'."\n".
            '    document.getElementById("searchCheck").value = "norm";'."\n".
            '  } else if (obj.style.display == "inline" && target == "hiddenSearch") {'."\n".
            '    document.getElementById("searchCheck").value = "exp";'."\n".
            '  }'."\n".
            '}'."\n".
            '// ]]>'."\n".
            '</script>'."\n";

        //get levels
        if ($this->settings['levels']['value'] == 1) {
            $lid = (isset($_REQUEST['lid']) ? intval($_REQUEST['lid']) : 0);
            $options = $this->getSearchLevels($lid);
            $name = $_ARRAYLANG['TXT_LEVEL'];
            $field = '<select name="lid" ><option value="">&nbsp;</option>'.$options.'</select>';

            // set variables
            $expSearch .=
                '<tr>'."\n".
                '    <td width="100" height="20" style="border: 0px solid #ff0000;">'.$name.'</td>'."\n".
                '    <td style="border: 0px solid #ff0000;">'.$field.'</td>'."\n".
                '</tr>'."\n";
        }

        //get categories
        $cid = (isset($_REQUEST['cid']) ? intval($_REQUEST['cid']) : 0);
        $options = $this->getSearchCategories($cid);
        $name = $_ARRAYLANG['TXT_DIR_F_CATEGORIE'];
        $field = '<select name="cid" ><option value="">&nbsp;</option>'.$options.'</select>';

        // set variables
        $expSearch .=
            '<tr>'."\n".
            '  <td width="100" height="20" style="border: 0px solid #ff0000;">'.$name.'</td>'."\n".
            '  <td style="border: 0px solid #ff0000;">'.$field.'</td>'."\n".
            '</tr>'."\n";

        //get exp search fields
        $objResult = $objDatabase->Execute("
            SELECT id, name, title, typ
              FROM ".DBPREFIX."module_directory_inputfields
             WHERE exp_search='1'
               AND is_search='1'
             ORDER BY sort
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                if ($objResult->fields['typ'] == 5 || $objResult->fields['typ'] == 6) {
                    $name = $objResult->fields['title'];
                } else {
                    if (!empty($_ARRAYLANG[$objResult->fields['title']])) {
                        $name = $_ARRAYLANG[$objResult->fields['title']];
                    } else {
                        $name = $objResult->fields['title'];
                    }
                }
                if (   $objResult->fields['typ'] == 1
                    || $objResult->fields['typ'] == 2
                    || $objResult->fields['typ'] == 5
                    || $objResult->fields['typ'] == 6) {
                    $field =
                        '<input maxlength="100" size="30" name="'.
                        $objResult->fields['name'].'" value="'.
                          (empty($_REQUEST[$objResult->fields['name']])
                            ? ''
                            : contrexx_addslashes($_REQUEST[$objResult->fields['name']])).
                        '" />';
                } else {
                    $field =
                        '<select name="'.$objResult->fields['name'].
                        '" style="width:194px;">'.
                        $arrDropdown[$objResult->fields['name']].'</select>';
                }
                // set variables
                $expSearch .=
                    '<tr>'."\n".
                    '  <td width="100" height="20">'.$name.'</td>'."\n".
                    '  <td>'.$field.'</td>'."\n".
                    '</tr>'."\n";
                $objResult->MoveNext();
            }
        }

        $html =
            '<div class="directorySearch">'."\n".
            '  <form action="index.php?" method="get" name="directorySearch" id="directorySearch">'."\n".
            '    <input name="term" value="'.(!empty($_GET['term']) ? htmlentities($_GET['term'], ENT_QUOTES, CONTREXX_CHARSET) : '').'" size="25" maxlength="100" />'."\n".
            '    <input id="searchCheck" type="hidden" name="check" value="norm" size="10" />'."\n".
            '    <input type="hidden" name="section" value="Directory" size="10" />'."\n".
            '    <input type="hidden" name="cmd" value="search" size="10" />'."\n".
            '    <input type="submit" value="'.$_ARRAYLANG['TXT_DIR_F_SEARCH'].'" name="search" /> <span class="addir-search"> &raquo; <a href="javascript:toggle(\'hiddenSearch\')" >'.$_ARRAYLANG['TXT_DIRECTORY_EXP_SEARCH'].'</a><br /></span>'."\n".
            '    <div style="display: none;" id="hiddenSearch">'."\n".
            '    '."\n".
            '    <table width="100%" cellspacing="0" cellpadding="0" border="0">'."\n".
            '    '.$expSearch."\n".
            '    </table>'."\n".
            '    </div>'."\n".
            '  </form>'."\n".
            '</div>'."\n";

        // set variables
        $this->_objTpl->setVariable(
            'DIRECTORY_SEARCH',  $javascript.$html
        );
    }


    /**
     * refresh xml from selected feed
     * @access   public
     * @param    string  $id
     */
    function refreshFeed($id)
    {
        $this->refreshXML($id);
    }


    /**
     * show feed
     * @access   public
     * @param    string  $id
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function feedDetails($id, $cid, $lid)
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

// TODO: Never used
//        $setVariable = array();

        //check popular & hits
        $this->checkPopular();
        //get search
        $this->getSearch();
        //get navtree
        $this->getNavtree($lid, $cid);

       $this->_objTpl->setVariable(array(
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
            'DIRECTORY_CATEGORY_NAVI' => $this->navtree,
        ));

        $objResult = $objDatabase->Execute("
            SELECT `country`
              FROM ".DBPREFIX."module_directory_dir
             WHERE id='".intval($id)."'");
        $country = $objResult->fields['country'];

        if ($this->_isGoogleMapEnabled('frontend')) {
            $this->_objTpl->addBlockFile('DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK', 'direcoryGoogleMapJavascript','modules/Directory/View/Template/Backend/module_directory_googlemap_include.html');
            $this->_objTpl->setVariable(array(
                'DIRECTORY_GOOGLE_API_KEY' => $_CONFIG["googleMapsAPIKey"],
                'TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY' => $_ARRAYLANG['TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY'],
                'TXT_DIR_GEO_TOO_MANY_QUERIES' => $_ARRAYLANG['TXT_DIR_GEO_TOO_MANY_QUERIES'],
                'TXT_DIR_GEO_SERVER_ERROR' => $_ARRAYLANG['TXT_DIR_GEO_SERVER_ERROR'],
                'TXT_DIR_GEO_NOT_FOUND' => $_ARRAYLANG['TXT_DIR_GEO_NOT_FOUND'],
                'TXT_DIR_GEO_SUCCESS' => $_ARRAYLANG['TXT_DIR_GEO_SUCCESS'],
                'TXT_DIR_GEO_MISSING' => $_ARRAYLANG['TXT_DIR_GEO_MISSING'],
                'TXT_DIR_GEO_UNKNOWN' => $_ARRAYLANG['TXT_DIR_GEO_UNKNOWN'],
                'TXT_DIR_GEO_UNAVAILABLE' => $_ARRAYLANG['TXT_DIR_GEO_UNAVAILABLE'],
                'TXT_DIR_GEO_BAD_KEY' => $_ARRAYLANG['TXT_DIR_GEO_BAD_KEY'],
                'DIRECTORY_START_X' => 'null',
                'DIRECTORY_START_Y' => 'null',
                'DIRECTORY_START_ZOOM' => 'null',
                'DIRECTORY_ENTRY_NAME' => 'null',
                'DIRECTORY_ENTRY_COMPANY' => 'null',
                'DIRECTORY_ENTRY_STREET' => 'null',
                'DIRECTORY_ENTRY_ZIP' => 'null',
                'DIRECTORY_ENTRY_LOCATION' => 'null',
                'DIRECTORY_MAP_LON_BACKEND' => $this->googleMapStartPoint['lon'],
                'DIRECTORY_MAP_LAT_BACKEND' => $this->googleMapStartPoint['lat'],
                'DIRECTORY_MAP_ZOOM_BACKEND' => $this->googleMapStartPoint['zoom'],
                'IS_BACKEND' => 'false',
            ));
            if ($this->_objTpl->blockExists('direcoryGoogleMapJavascript')) {
                $this->_objTpl->parse('direcoryGoogleMapJavascript');
            }
        }

        //get content
        $this->getContent($id, $cid, $lid);

        //get attributes
        $this->getAttributes($id);

        //get voting
        $this->getVoting($id, $cid, $lid);

        //get votes
        $this->getVotes($id);

        //parse block
        $this->_objTpl->parse('feedDetails');
    }


    function getAttributes($id)
    {
        global $objDatabase, $_ARRAYLANG;

        //get attributes
        $objResult = $objDatabase->Execute("
            SELECT id, validatedate, date, hits
              FROM ".DBPREFIX."module_directory_dir
             WHERE id=$id");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $validatedate = $objResult->fields['validatedate'];
                $date = $objResult->fields['date'];
                $hits = $objResult->fields['hits'];
                $objResult->MoveNext();
            }
        }

        //get categories
        $objResult = $objDatabase->Execute("
            SELECT cat_id
              FROM ".DBPREFIX."module_directory_rel_dir_cat
             WHERE dir_id=$id");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrCatId[] = $objResult->fields['cat_id'];
                $objResult->MoveNext();
            }
        }

        if (!empty($arrCatId)) {
            $categories = "<ul>";
            foreach ($arrCatId as $catId) {
                $objResult = $objDatabase->Execute("
                    SELECT name
                      FROM ".DBPREFIX."module_directory_categories
                     WHERE id=$catId");
                $categories .= "<li>".$objResult->fields['name']."</li>";
            }
            $categories .= "</ul>";

            $this->_objTpl->setVariable(array(
                'TXT_DIRECTORY_FEED_CATEGORIES' => $_ARRAYLANG['TXT_DIR_CATEGORIE'],
                'DIRECTORY_FEED_CATEGORIES' => $categories,
            ));
        }

        //get levels
        if ($this->settings['levels']['value'] == 1) {
            $objResult = $objDatabase->Execute("
                SELECT level_id
                  FROM ".DBPREFIX."module_directory_rel_dir_level
                 WHERE dir_id=$id");
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    $arrLevelId[] = $objResult->fields['level_id'];
                    $objResult->MoveNext();
                }
            }

            if (!empty($arrLevelId)) {
                $levels = "<ul>";
                foreach ($arrLevelId as $levelId) {
                    $objResult = $objDatabase->Execute("
                        SELECT name
                          FROM ".DBPREFIX."module_directory_levels
                         WHERE id=$levelId");
                    $levels .= "<li>".$objResult->fields['name']."</li>";
                }
                $levels .= "</ul>";

                $this->_objTpl->setVariable(array(
                    'TXT_DIRECTORY_FEED_LEVELS' => $_ARRAYLANG['TXT_LEVELS'],
                    'DIRECTORY_FEED_LEVELS' => $levels,
                ));
            }
        }

        // set variables
        $formattedValidateDate = '';
        if (!empty($validatedate)) {
            $formattedValidateDate = date('d. M Y', $validatedate);
        }
        $this->_objTpl->setVariable(array(
            'DIRECTORY_FEED_VALIDATE_DATE' => $formattedValidateDate,
            'DIRECTORY_FEED_DATE' => date("d. M Y", $date),
            'DIRECTORY_FEED_HITS' => $hits,
        ));
    }


    /**
     * get Feed content
     * @access   public
     * @param    string  $id
     * @global    ADONewConnection
     * @global    array
     */
    function getContent($id, $cid=0, $lid=0)
    {
        global $objDatabase, $_ARRAYLANG;

        //get feed content
        $objResult = $objDatabase->Execute("
            SELECT *
              FROM ".DBPREFIX."module_directory_dir
             WHERE id=$id
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $arrFeedContent['id'] = stripslashes($objResult->fields['id']);
                $arrFeedContent['title'] = stripslashes($objResult->fields['title']);
                $arrFeedContent['date'] = $objResult->fields['date'];
                $arrFeedContent['description'] = stripslashes($objResult->fields['description']);
                $arrFeedContent['relatedlinks'] = $objResult->fields['relatedlinks'];
                $arrFeedContent['status'] = $objResult->fields['status'];
                $arrFeedContent['addedby'] = $objResult->fields['addedby'];
                $arrFeedContent['validatedate'] = $objResult->fields['validatedate'];
                $arrFeedContent['link'] = $objResult->fields['link'];
                $arrFeedContent['rss_link'] = $objResult->fields['rss_link'];
                $rss_link = $objResult->fields['rss_file'];
                $arrFeedContent['attachment'] = $objResult->fields['attachment'];
                $arrFeedContent['platform'] = $objResult->fields['platform'];
                $arrFeedContent['language'] = $objResult->fields['language'];
                $arrFeedContent['canton'] = $objResult->fields['canton'];
                $arrFeedContent['searchkeys'] = $objResult->fields['searchkeys'];
                $arrFeedContent['company_name'] = $objResult->fields['company_name'];
                $arrFeedContent['street'] = $objResult->fields['street'];
                $arrFeedContent['zip'] = $objResult->fields['zip'];
                $arrFeedContent['phone'] = $objResult->fields['phone'];

                $arrFeedContent['longitude'] = $objResult->fields['longitude'];
                $arrFeedContent['latitude'] = $objResult->fields['latitude'];
                $arrFeedContent["lon"] = substr($objResult->fields['longitude'], 0, strpos($objResult->fields['longitude'], '.'));
                $arrFeedContent["lon_fraction"] = substr($objResult->fields['longitude'],      strpos($objResult->fields['longitude'], '.')+1);
                $arrFeedContent["lat"] = substr($objResult->fields['latitude'],  0, strpos($objResult->fields['latitude'], '.'));
                $arrFeedContent["lat_fraction"] = substr($objResult->fields['latitude'],      strpos($objResult->fields['latitude'], '.')+1);

                $arrFeedContent['zoom'] = $objResult->fields['zoom'];
                $arrFeedContent['country'] = $objResult->fields['country'];
                $arrFeedContent['googlemap'] = "googlemap";

                $arrFeedContent['contact'] = $objResult->fields['contact'];
                $arrFeedContent['hits'] = $objResult->fields['hits'];
                $arrFeedContent['xml_refresh'] = $objResult->fields['xml_refresh'];
// TODO: Field does not exist
//                $arrFeedContent['checksum'] = $objResult->fields['checksum'];
                $arrFeedContent['city'] = $objResult->fields['city'];
                $arrFeedContent['information'] = $objResult->fields['information'];
                $arrFeedContent['fax'] = $objResult->fields['fax'];
                $arrFeedContent['mobile'] = $objResult->fields['mobile'];
                $arrFeedContent['mail'] = $objResult->fields['mail'];
                $arrFeedContent['homepage'] = $objResult->fields['homepage'];
                $arrFeedContent['industry'] = $objResult->fields['industry'];
                $arrFeedContent['legalform'] = $objResult->fields['legalform'];
                $arrFeedContent['conversion'] = $objResult->fields['conversion'];
                $arrFeedContent['employee'] = $objResult->fields['employee'];
                $arrFeedContent['foundation'] = $objResult->fields['foundation'];
                $arrFeedContent['mwst'] = $objResult->fields['mwst'];
                $arrFeedContent['opening'] = $objResult->fields['opening'];
                $arrFeedContent['holidays'] = $objResult->fields['holidays'];
                $arrFeedContent['places'] = $objResult->fields['places'];
                $arrFeedContent['logo'] = $objResult->fields['logo'];
                $arrFeedContent['team'] = $objResult->fields['team'];
                $arrFeedContent['portfolio'] = $objResult->fields['portfolio'];
                $arrFeedContent['offers'] = $objResult->fields['offers'];
                $arrFeedContent['concept'] = $objResult->fields['concept'];
                $arrFeedContent['map'] = $objResult->fields['map'];
                $arrFeedContent['premium'] = $objResult->fields['premium'];
                $arrFeedContent['lokal'] = $objResult->fields['lokal'];
                $arrFeedContent['spez_field_1'] = $objResult->fields['spez_field_1'];
                $arrFeedContent['spez_field_2'] = $objResult->fields['spez_field_2'];
                $arrFeedContent['spez_field_3'] = $objResult->fields['spez_field_3'];
                $arrFeedContent['spez_field_4'] = $objResult->fields['spez_field_4'];
                $arrFeedContent['spez_field_5'] = $objResult->fields['spez_field_5'];
                $arrFeedContent['spez_field_6'] = $objResult->fields['spez_field_6'];
                $arrFeedContent['spez_field_7'] = $objResult->fields['spez_field_7'];
                $arrFeedContent['spez_field_8'] = $objResult->fields['spez_field_8'];
                $arrFeedContent['spez_field_9'] = $objResult->fields['spez_field_9'];
                $arrFeedContent['spez_field_10'] = $objResult->fields['spez_field_10'];
                $arrFeedContent['spez_field_11'] = $objResult->fields['spez_field_11'];
                $arrFeedContent['spez_field_12'] = $objResult->fields['spez_field_12'];
                $arrFeedContent['spez_field_13'] = $objResult->fields['spez_field_13'];
                $arrFeedContent['spez_field_14'] = $objResult->fields['spez_field_14'];
                $arrFeedContent['spez_field_15'] = $objResult->fields['spez_field_15'];
                $arrFeedContent['spez_field_16'] = $objResult->fields['spez_field_16'];
                $arrFeedContent['spez_field_17'] = $objResult->fields['spez_field_17'];
                $arrFeedContent['spez_field_18'] = $objResult->fields['spez_field_18'];
                $arrFeedContent['spez_field_19'] = $objResult->fields['spez_field_19'];
                $arrFeedContent['spez_field_20'] = $objResult->fields['spez_field_20'];
                $arrFeedContent['spez_field_21'] = $objResult->fields['spez_field_21'];
                $arrFeedContent['spez_field_22'] = $objResult->fields['spez_field_22'];
                $arrFeedContent['spez_field_23'] = $objResult->fields['spez_field_23'];
                $arrFeedContent['spez_field_24'] = $objResult->fields['spez_field_24'];
                $arrFeedContent['spez_field_25'] = $objResult->fields['spez_field_25'];
                $arrFeedContent['spez_field_26'] = $objResult->fields['spez_field_26'];
                $arrFeedContent['spez_field_27'] = $objResult->fields['spez_field_27'];
                $arrFeedContent['spez_field_28'] = $objResult->fields['spez_field_28'];
                $arrFeedContent['spez_field_29'] = $objResult->fields['spez_field_29'];
                $arrFeedContent['youtube'] = $objResult->fields['youtube'];
                $objResult->MoveNext();
            }
        }

        //get active fields
        $objResult = $objDatabase->Execute("SELECT id, title, name FROM ".DBPREFIX."module_directory_inputfields WHERE active_backend='1' ORDER BY sort");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrFieldsActive['title'][$objResult->fields['id']] = $objResult->fields['title'];
                $arrFieldsActive['name'][$objResult->fields['id']] = $objResult->fields['name'];
// TODO: Fields do not exist in this table, but in module_directory_dir!
//                $arrFieldsActive['validatedate'][$objResult->fields['id']] = $objResult->fields['validatedate'];
//                $arrFieldsActive['hits'][$objResult->fields['id']] = $objResult->fields['hits'];
                $objResult->MoveNext();
            }
        }

        $arrSettings = $this->getSettings();
        //check fields
        if ($arrFieldsActive != "") {
            $fieldsList = '';
            foreach ($arrFieldsActive['name'] as $fieldKey => $fieldName) {
                if ($arrFeedContent[$fieldName] != "") {
                    // set variables
                    $content = contrexx_strip_tags($arrFeedContent[$fieldName]);
                    $name = (isset($_ARRAYLANG[$arrFieldsActive['title'][$fieldKey]])
                        ? $_ARRAYLANG[$arrFieldsActive['title'][$fieldKey]]
                        : ''
                    );

                    //youtube
                    if ($fieldName == "youtube") {
                        $youTubeIdRegex = "#.*[\?&/]v[=/]([a-zA-Z0-9_-]{11}).*#";
                        preg_match($youTubeIdRegex, $arrFeedContent[$fieldName], $youTubeArray);
                        $youTubeID = $youTubeArray[1];

                        $content = '<iframe width="'.$arrSettings['youtubeWidth']['value'].'" height="'.$arrSettings['youtubeHeight']['value'].'" src="//www.youtube.com/embed/'.$youTubeID.'" frameborder="0" allowfullscreen></iframe>';
                    }

                    //get pics
                    if ($fieldName == "logo") {
                        $content = '<img src="'.$this->mediaWebPath.'images/'.$arrFeedContent[$fieldName].'" border="0" alt="'.$arrFeedContent['title'].'" />&nbsp;&nbsp;';
                        $info = getimagesize($this->mediaPath."images/".$arrFeedContent[$fieldName]);
                        $width = $info[0]+20;
                        $height = $info[1]+20;
                        if (!file_exists($this->mediaPath.'thumbs/'.$arrFeedContent[$fieldName])) {
                            $path = "images/";
                        } else {
                            $path = "thumbs/";
                        }
                        $setVariable["DIRECTORY_FEED_LOGO_THUMB"] = '<a href="'.$this->mediaWebPath."images/".$arrFeedContent[$fieldName].'" onclick="window.open(this.href,\'\',\'resizable=no,location=no,menubar=no,scrollbars=no,status=no,toolbar=no,fullscreen=no,dependent=no,width='.$width.',height='.$height.',status\'); return false"><img src="'.$this->mediaWebPath.$path.$arrFeedContent[$fieldName].'"  width="'.$arrSettings['thumbSize']['value'].'" border="0" alt="'.$arrFeedContent['title'].'" /></a>&nbsp;&nbsp;';
                    }
                    //rss link
                    if ($fieldName == "rss_link") {
                        //refresh
                        $refreshTime = $this->settings['refreshfeeds']['value'];
                        $now = mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"));
                        $d = date("d",$arrFeedContent['xml_refresh']);
                        $m = date("m",$arrFeedContent['xml_refresh']);
                        $Y = date("Y",$arrFeedContent['xml_refresh']);
                        $G = date("G",$arrFeedContent['xml_refresh']);
                        $i = date("i",$arrFeedContent['xml_refresh']);
                        $s = date("s",$arrFeedContent['xml_refresh']);
                        $s = $s+$refreshTime;
                        $xml_refresh = mktime($G,  $i, $s, $m, $d, $Y);
                        if ($now >= $xml_refresh) {
                            $this->refreshFeed($id);
                        }
                        $content = $this->parseRSS($rss_link, 1, 0, "ext_feeds/");
                    }
                    //get pics
                    if ($fieldName == "lokal" ||
                        $fieldName == "map" ||
                        $fieldName == "spez_field_11" ||
                        $fieldName == "spez_field_12" ||
                        $fieldName == "spez_field_13" ||
                        $fieldName == "spez_field_14" ||
                        $fieldName == "spez_field_15" ||
                        $fieldName == "spez_field_16" ||
                        $fieldName == "spez_field_17" ||
                        $fieldName == "spez_field_18" ||
                        $fieldName == "spez_field_19" ||
                        $fieldName == "spez_field_20") {

                        $info = getimagesize($this->mediaPath."images/".$arrFeedContent[$fieldName]);
                        $width = $info[0]+20;
                        $height = $info[1]+20;

                        if (!file_exists($this->mediaPath.'thumbs/'.$arrFeedContent[$fieldName])) {
                            $path = "images/";
                        } else {
                            $path = "thumbs/";
                        }
                        $content = '<a href="'.$this->mediaWebPath."images/".$arrFeedContent[$fieldName].'" onclick="window.open(this.href,\'\',\'resizable=no,location=no,menubar=no,scrollbars=no,status=no,toolbar=no,fullscreen=no,dependent=no,width='.$width.',height='.$height.',status\'); return false"><img src="'.$this->mediaWebPath.$path.$arrFeedContent[$fieldName].'" border="0" width="'.$arrSettings['thumbSize']['value'].'" alt="'.$arrFeedContent['title'].'" /></a>&nbsp;&nbsp;';
                    }

                    //get uploads
                    if ($fieldName == "attachment" ||
                        $fieldName == "spez_field_25" ||
                        $fieldName == "spez_field_26" ||
                        $fieldName == "spez_field_27" ||
                        $fieldName == "spez_field_28" ||
                        $fieldName == "spez_field_29") {
                        $info = (filesize($this->mediaPath."uploads/".$arrFeedContent[$fieldName]))/1000;
                        $content = '<a href="'.$this->mediaWebPath."uploads/".$arrFeedContent[$fieldName].'" target="_blank">'.$arrFeedContent[$fieldName].'</a>&nbsp;<i>('.$info.' KB)</i>';
                    }

                    if (strtolower($fieldName) == "googlemap") {
                        $inputValueField = '<input type="hidden" name="inputValue[lon]" value="'.$arrFeedContent["lon"].'" style="width:22px;" maxlength="3" />';
                        $inputValueField .= '<input type="hidden" name="inputValue[lon_fraction]" value="'.$arrFeedContent["lon_fraction"].'" style="width:92px;" maxlength="15" />';
                        $inputValueField .= '<input type="hidden" name="inputValue[lat]" value="'.$arrFeedContent["lat"].'" style="width:22px;" maxlength="15" />';
                        $inputValueField .= '<input type="hidden" name="inputValue[lat_fraction]" value="'.$arrFeedContent["lat_fraction"].'" style="width:92px;" maxlength="15" />';
                        $inputValueField .= '<input type="hidden" name="inputValue[zoom]" value="'.$arrFeedContent["zoom"].'" style="width:15px;" maxlength="2" />';
                        $inputValueField .= '<div id="gmap" style="margin:2px; border:1px solid;width: 400px; height: 300px;"></div>';
                        $content = $inputValueField;
                    }

                    //get author
                    if ($fieldName == "addedby") {
                        $content = $this->getAuthor($arrFeedContent[$fieldName]);
                    }

                    //get mail
                    if ($fieldName =="mail") {
                        $content = "<a href='mailto:".$arrFeedContent[$fieldName]."' target='_blank'>".$arrFeedContent[$fieldName]."</a>";
                    }

                    //get spez voting
                    if ($fieldName == "spez_field_23" || $fieldName == "spez_field_24" ) {
                        $content = "";
                        for($i=0; $i < $arrFeedContent[$fieldName]; $i++) {
                            $content .= "<img src='".$this->imageWebPath."/star_on.gif' border='0' alt='' />";
                        }
                    }

                    //get homepage, relatedlinks
                    if ($fieldName == "homepage" || $fieldName == "relatedlinks" || $fieldName == "link") {
                        $varLinks = "";

                        //explode links
                        $arrLinks = explode(", ", $arrFeedContent[$fieldName]);

                        //make links
                        foreach ($arrLinks as $link) {
                            if (substr($link, 0,7) != "http://") {
                                $linkUrl = "http://".$link;
                            } else {
                                $linkUrl = $link;
                            }

                            if (strlen($link) >= 55 ) {
/*
                                $arrLink = explode("/", $link);
                                $lastElement = count($arrLink)-1;
                                $lastElementLength = strlen($arrLink[$lastElement]);
                                $firstElementLength = 49-$lastElementLength;
                                $linkName = substr($link, 0, $firstElementLength)."...../".$arrLink[$lastElement];
*/
                                $linkName = substr($link, 0, 55)."[...]";
                            } else {
                                $linkName = $link;
                            }
                            $varLinks .= "<a href='".$linkUrl."' class='out' target='_blank'>".$linkName."</a><br />";
                        }
                        $content = $varLinks;
                    }

                    //check spez
                    if (substr($fieldName,0, 10) == "spez_field") {
                        $name = $arrFieldsActive['title'][$fieldKey];
                    }

                    //get title
                    if ($fieldName =="title") {
                        $content = $arrFeedContent[$fieldName];
                        if (!empty($arrFeedContent['validatedate'])) {
                            $newTime = $this->settings['mark_new_entrees']['value'];
                            $now = mktime(date("G"),  date("i"), date("s"), date("m"), date("d"), date("Y"));
                            $d = date("d",$arrFeedContent['validatedate']);
                            $m = date("m",$arrFeedContent['validatedate']);
                            $Y = date("Y",$arrFeedContent['validatedate']);
                            $d = $d+$newTime;
                            $newFeed = mktime(0, 0, 0, $m, $d, $Y);
                            if ($now <= $newFeed) {
                                $content = $arrFeedContent[$fieldName]."&nbsp;<img src='".$this->imageWebPath."/new.gif' border='0' alt='' />";
                            }
                        }
                    }
                    $setVariable["DIRECTORY_FEED_".strtoupper($fieldName)] = nl2br($content);

                    // we need a plain-URL variant too
                    if ($fieldName == "homepage" || $fieldName == "relatedlinks" || $fieldName == "link") {
                        $setVariable["DIRECTORY_FEED_".strtoupper($fieldName)."_URL"] = $arrLinks[0];
                    }

                    $setVariable["TXT_DIRECTORY_FEED_".strtoupper($fieldName)] = $name;
                    $fieldsList .= '<div class="fieldsList"><div class="fieldDesc">'.nl2br($name).'</div><div class="fieldContent">'.nl2br($content).'</div></div>';
                }
            }
            $setVariable["DIRECTORY_FIELDS_LIST"] = $fieldsList;
        }

        $cid = ($cid > 0 ? "&amp;cid=$cid" : '');
        $lid = ($lid > 0 ? "&amp;lid=$lid" : '');
        $points = (strlen($arrFeedContent['description']) > 400 ? '...' : '');
        $parts = explode("\n", wordwrap($arrFeedContent['description'], 400, "\n"));
        $setVariable["DIRECTORY_FEED_SHORT_DESCRIPTION"] = $parts[0].$points;
        $setVariable["DIRECTORY_FEED_ID"] = $arrFeedContent['id'];
        $setVariable["DIRECTORY_FEED_DETAIL"] = $_ARRAYLANG['TXT_DIRECTORY_DETAIL'];
        $setVariable["DIRECTORY_FEED_DETAIL_LINK"] = CONTREXX_SCRIPT_PATH."?section=Directory&amp;cmd=detail&amp;id=".$arrFeedContent['id'].$lid.$cid;
        $setVariable["DIRECTORY_FEED_EDIT"] = $_ARRAYLANG['TXT_DIRECTORY_EDIT'];
        $setVariable["DIRECTORY_FEED_EDIT_LINK"] = CONTREXX_SCRIPT_PATH."?section=Directory&amp;cmd=edit&amp;id=".$arrFeedContent['id'];
        $setVariable["DIRECTORY_FEED_HITS"] = $arrFeedContent['hits'];

        if ($arrFeedContent['premium'] == '1') {
            $content = 'class="premium"';
        } else {
            $content = 'class="normal"';
        }
        $setVariable["DIRECTORY_FEED_PREMIUM"] = $content;

        //metatitle
        $cmd = (isset($_GET['cmd']) ? $_GET['cmd'] : '');
        if ($cmd == 'detail') {
            $this->pageTitle .= $arrFeedContent['title'];
        }

        // set variables
        $this->_objTpl->setVariable($setVariable);
    }


    function parseRSS($filename, $showDetails, $showDescription, $path)
    {
        global $objDatabase, $_ARRAYLANG;

        if ($showDetails == 1) {
            $objResult = $objDatabase->Execute("SELECT id, FROM_UNIXTIME(xml_refresh, '%d. %M %Y %H:%i:%s') AS xml_refresh FROM ".DBPREFIX."module_directory_dir WHERE rss_file='".contrexx_addslashes($filename)."'");
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    $this->rssRefresh = $objResult->fields['xml_refresh'];
// Not used -- see below
//                    $feedId = $objResult->fields['id'];
                    $objResult->MoveNext();
                };
            }
        }
        $filename = $this->mediaPath.$path.$filename;
        //rss class
        $rss = new \XML_RSS($filename);
        $rss->parse();
        if ($showDetails == 1) {
            //channel info
            $info = $rss->getChannelInfo();
            $this->rssTitle = $info['title'];
            //image
            foreach ($rss->getImages() as $img) {
                $this->rssImage = "<img src=".$img['url']." alt='' /><br />";
            }
// TODO: Never used
//            $image = "<a href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;linkid=$feedId' target='_blank'><img src='/images/modules/directory/rss.gif' border='0' alt='Source' /></a>&nbsp;";
            $feeds = "<b>".$this->rssTitle."</b><br />".$_ARRAYLANG['TXT_DIR_LAST_UPDATE'].": ".$this->rssRefresh."<br />";
        }

        //items
        $feeds .= "<ul>";
        $x = 0;
        $limit = $this->settings['xmlLimit']['value'];
        foreach ($rss->getItems() as $value) {
            if ($x < $limit) {
                $feeds .= "<li><a href='".$value['link']."' target='_blank'>".$value['title']."</a><br />";
                if ($showDescription == 1) {
                    $feeds .= substr($value['description'],0, 200);
                }
                $feeds .= "</li>";
                $x++;
            }
        }
        $feeds .= "</ul>";


        return $feeds;
    }



    /**
     * Add a new entry
     * @access   public
     * @param    string  $parentId
     * @global    ADONewConnection
     * @global    array
     * @global    array
     */
    function newFeed()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $status="error";

        if (!$this->settings['addFeed']['value'] == '1' || (!$this->communityModul && $this->settings['addFeed_only_community']['value'] == '1')) {
           \Cx\Core\Csrf\Controller\Csrf::header('Location: '.CONTREXX_SCRIPT_PATH.'?section=Directory');
            exit;
        } elseif ($this->settings['addFeed_only_community']['value'] == '1') {
            $objFWUser = \FWUser::getFWUserObject();
            if ($objFWUser->objUser->login()) {
                if (!\Permission::checkAccess(96, 'static', true)) {
                    \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Login&cmd=noaccess");
                    exit;
                }
            }else {
                $link = base64_encode(CONTREXX_SCRIPT_PATH.'?'.$_SERVER['QUERY_STRING']);
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Login&redirect=".$link);
                exit;
            }
        } else {
            $objFWUser = \FWUser::getFWUserObject();
        }

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //set navigation
        $verlauf = "&nbsp;&raquo;&nbsp;<a href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;cmd=add'>".$_ARRAYLANG['TXT_DIR_F_NEW_ENTREE']."</a>";

        //get search
        $this->getSearch();

        //get categories, languages, platforms and username
        $catId = 0;
        $levelId = 0;
        $categories = $this->getCategories($catId, 1);
        $levels = $this->getLevels($levelId, 1);
// TODO: $osId is not defined
//$osId = 0;
// TODO: Never used
//        $platforms = $this->getPlatforms($osId);
// TODO: $langId is not defined
//$langId = 0;
// TODO: Never used
//        $languages = $this->getLanguages($langId);

        //get inputfields
        $this->getInputfields(
            ($objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0),
            "add", "", "frontend");

        //add feed
        if (isset($_POST['addSubmit'])) {
            \Cx\Core\Csrf\Controller\Csrf::check_code();
            $status = $this->addFeed();
        }

        $this->_objTpl->setVariable(array(
            'DIRECTORY_CATEGORY_NAVI' => $verlauf,
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
        ));

        if ($status != "error") {
            //send mail
            if ($this->settings['adminMail']['value'] != '') {
                $this->sendMail($status, $this->settings['adminMail']['value']);
            }

            // set variables
            $this->_objTpl->setVariable(array(
                'DIRECTORY_FEED_ADDED' => $_ARRAYLANG['DIRECTORY_FEED_ADDED'],
                'TXT_DIRECTORY_BACK' => '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Directory">'.$_ARRAYLANG['TXT_DIRECTORY_BACK'].'</a>',
            ));


            $this->_objTpl->parse('directoryMessage');
            $this->_objTpl->hideBlock('directoryInputFields');
        } else {
            // set variables
            $this->_objTpl->setVariable(array(
                'TXT_DIRECTORY_ADD' => $_ARRAYLANG['TXT_DIR_F_ADD'],
                'TXT_DIRECTORY_RSSLINK' => $_ARRAYLANG['TXT_DIRECTORY_RSS'],
                'TXT_DIRECTORY_FILE' => $_ARRAYLANG['TXT_DIRECTORY_UPLOAD'],
                'TXT_DIRECTORY_LINK' => $_ARRAYLANG['TXT_DIRECTORY_LINK'],
                'TXT_DIRECTORY_ATTACHMENT' => $_ARRAYLANG['TXT_DIRECTORY_ATTACHMENT'],
                'TXT_DIRECTORY_MAKE_SELECTION' => $_ARRAYLANG['TXT_DIRECTORY_PLEASE_CHOSE'],
                'TXT_DIRECTORY_FILETYPE' => $_ARRAYLANG['TXT_DIRECTORY_FILETYP'],
// TODO: Not defined
//                'DIRECTORY_CHECK' => $check,
                'TXT_FIELDS_REQUIRED' => $_ARRAYLANG['DIRECTORY_CHECK_REQIERED'],
                'TXT_DIRECTORY_LEVEL' => $_ARRAYLANG['TXT_LEVEL'],
                'TXT_DIRECTORY_CATEGORY' => $_ARRAYLANG['TXT_DIR_F_CATEGORIE'],
                'DIRECTORY_CATEGORIES_DESELECTED' => $categories,
                'DIRECTORY_LEVELS_DESELECTED' => $levels,
            ));

            if ($this->settings['levels']['value']=='0') {
                $this->_objTpl->hideBlock('directoryLevels');
            }

            if ($this->_isGoogleMapEnabled('frontend')) {
                $this->_objTpl->addBlockFile('DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK', 'direcoryGoogleMapJavascript', 'modules/Directory/View/Template/Backend/module_directory_googlemap_include.html');
                $this->_objTpl->setVariable(array(
                    'DIRECTORY_GOOGLE_API_KEY' => $_CONFIG["googleMapsAPIKey"],
                    'TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY' => $_ARRAYLANG['TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY'],
                    'TXT_DIR_GEO_TOO_MANY_QUERIES' => $_ARRAYLANG['TXT_DIR_GEO_TOO_MANY_QUERIES'],
                    'TXT_DIR_GEO_SERVER_ERROR' => $_ARRAYLANG['TXT_DIR_GEO_SERVER_ERROR'],
                    'TXT_DIR_GEO_NOT_FOUND' => $_ARRAYLANG['TXT_DIR_GEO_NOT_FOUND'],
                    'TXT_DIR_GEO_SUCCESS' => $_ARRAYLANG['TXT_DIR_GEO_SUCCESS'],
                    'TXT_DIR_GEO_MISSING' => $_ARRAYLANG['TXT_DIR_GEO_MISSING'],
                    'TXT_DIR_GEO_UNKNOWN' => $_ARRAYLANG['TXT_DIR_GEO_UNKNOWN'],
                    'TXT_DIR_GEO_UNAVAILABLE' => $_ARRAYLANG['TXT_DIR_GEO_UNAVAILABLE'],
                    'TXT_DIR_GEO_BAD_KEY' => $_ARRAYLANG['TXT_DIR_GEO_BAD_KEY'],
                    'DIRECTORY_START_X' => 'null',
                    'DIRECTORY_START_Y' => 'null',
                    'DIRECTORY_START_ZOOM' => 'null',
                    'DIRECTORY_ENTRY_NAME' => 'null',
                    'DIRECTORY_ENTRY_COMPANY' => 'null',
                    'DIRECTORY_ENTRY_STREET' => 'null',
                    'DIRECTORY_ENTRY_ZIP' => 'null',
                    'DIRECTORY_ENTRY_LOCATION' => 'null',
                    'DIRECTORY_MAP_LON_BACKEND' => $this->googleMapStartPoint['lon'],
                    'DIRECTORY_MAP_LAT_BACKEND' => $this->googleMapStartPoint['lat'],
                    'DIRECTORY_MAP_ZOOM_BACKEND' => $this->googleMapStartPoint['zoom'],
                    'IS_BACKEND' => 'true',
                ));
                if ($this->_objTpl->blockExists('direcoryGoogleMapJavascript')) {
                    $this->_objTpl->parse('direcoryGoogleMapJavascript');
                }
            }
            $this->_objTpl->hideBlock('directoryMessage');
            $this->_objTpl->parse('directoryInputFields');
        }
    }


    function myFeeds()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        if (!$this->communityModul && $this->settings['addFeed_only_community']['value'] == '1') {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: '.CONTREXX_SCRIPT_PATH.'?section=Directory');
            exit;
        }

        $objFWUser = \FWUser::getFWUserObject();
        if ($objFWUser->objUser->login()) {
            if (!\Permission::checkAccess(94, 'static', true)) {
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Login&cmd=noaccess");
                exit;
            }
        }else {
            $link = base64_encode($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Login&redirect=".$link);
            exit;
        }

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get navigation
        $verlauf = "&nbsp;&raquo;&nbsp;<a href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;cmd=myfeeds'>".$_ARRAYLANG['TXT_DIRECTORY_MY_FEEDS']."</a>";

        //get search
        $this->getSearch();

        $objCount = $objDatabase->Execute("SELECT COUNT(1) AS entryCount FROM ".DBPREFIX."module_directory_dir WHERE status = '1' AND addedby = ".$objFWUser->objUser->getId()." ORDER BY spezial DESC");

        ////// paging start /////////
        $pagingLimit = intval($this->settings['pagingLimit']['value']);
        $count = $objCount->fields['entryCount'];
        $pos = intval($_GET['pos']);
        $paging = getPaging($count, $pos, "&section=Directory&cmd=myfeeds", "<b>".$_ARRAYLANG['TXT_DIRECTORY_FEEDS']."</b>", true, $pagingLimit);
        ////// paging end /////////

        if ($count < $pagingLimit) {
            $paging = "";
        }

        // set variables
        $this->_objTpl->setVariable(array(
            'DIRECTORY_CATEGORY_NAVI' => $verlauf,
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
            'SEARCH_PAGING' => $paging
        ));

        $id = $objFWUser->objUser->getId();
        $objResult = $objDatabase->SelectLimit("SELECT id FROM ".DBPREFIX."module_directory_dir WHERE status = '1' AND addedby = ".contrexx_addslashes($id)." ORDER BY spezial DESC", $pagingLimit, $pos);
        $count = $objResult->RecordCount();
        if ($objResult !== false) {
            $i = 0;
            while (!$objResult->EOF) {

                //get content
                $this->getContent($objResult->fields['id'], "");

                //get votes
                $this->getVotes($objResult->fields['id']);

                //row class
                $this->_objTpl->setVariable(
                    'DIRECTORY_FEED_ROW', (++$i % 2 ? 'row1' : 'row2')
                );
                $this->_objTpl->parse('showFeeds');
                $objResult->MoveNext();
            }
        }

        if ($count == 0) {
            // set variables
            $this->_objTpl->setVariable(
                'DIRECTORY_NO_FEEDS_FOUND', $_ARRAYLANG['DIRECTORY_NO_FEEDS_FOUND']
            );
            $this->_objTpl->parse('noFeeds');
            $this->_objTpl->hideBlock('showFeeds');
        }
    }


    function editFeed()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $status = "error";

        if (!$this->settings['editFeed']['value'] == '1' || (!$this->communityModul && $this->settings['addFeed_only_community']['value'] == '1')) {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: '.CONTREXX_SCRIPT_PATH.'?section=Directory&cmd=myfeeds');
            exit;
        }

        $objFWUser = \FWUser::getFWUserObject();
        if ($objFWUser->objUser->login()) {
            if (!\Permission::checkAccess(94, 'static', true)) {
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Login&cmd=noaccess");
                exit;
            }
        }else {
            $link = base64_encode($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Login&redirect=".$link);
            exit;
        }

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
        } else {
            $id = intval($_POST['edit_id']);
        }

        if ($_GET['id'] == '' && $_POST['edit_id'] == '') {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: '.CONTREXX_SCRIPT_PATH.'?section=Directory&cmd=myfeeds');
            exit;
        }

        $objResult = $objDatabase->Execute("SELECT spezial, addedby FROM ".DBPREFIX."module_directory_dir WHERE id = '".contrexx_addslashes($id)."'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
// TODO: Never used
//                $spezSort = $objResult->fields['spezial'];
                $author = $objResult->fields['addedby'];
                $objResult->MoveNext();
            }
        }

        if ($author != $objFWUser->objUser->getId()) {
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_SCRIPT_PATH."?section=Directory&cmd=myfeeds");
        }

        //get navigation
        $verlauf = "&nbsp;&raquo;&nbsp;<a href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;cmd=myfeeds'>".$_ARRAYLANG['TXT_DIRECTORY_MY_FEEDS']."</a>&nbsp;&raquo;&nbsp;<a href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;cmd=edit&amp;id=".$id."'>".$_ARRAYLANG['TXT_DIRECTORY_EDIT_FEED']."</a>";
        //get search
        $this->getSearch();
        //get category
        $categorieDe = $this->getCategories($id, 1);
        $categorieSe = $this->getCategories($id, 2);
        $levelsDe = $this->getLevels($id, 1);
        $levelsSe = $this->getLevels($id, 2);
        //get inputfields
        $this->getInputfields($objFWUser->objUser->getId(), "edit", $id, "frontend");

        //update feed
        if (isset($_POST['edit_submit'])) {
            $status = $this->updateFile($objFWUser->objUser->getId());
        }

        // set variables
        $this->_objTpl->setVariable(array(
            'DIRECTORY_CATEGORY_NAVI' => $verlauf,
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
        ));

        if ($status != "error") {
            //send mail
            if ($this->settings['adminMail']['value'] != '') {
                $this->sendMail($status, $this->settings['adminMail']['value']);
            }
            $this->_objTpl->setVariable(array(
                'DIRECTORY_FEED_UPDATED' => $_ARRAYLANG['TXT_DIRECTORY_UPDATE_SUCCESSFULL'],
                'TXT_DIRECTORY_BACK' => '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Directory&cmd=myfeeds">'.$_ARRAYLANG['TXT_DIRECTORY_BACK'].'</a>',
            ));
            $this->_objTpl->parse('directoryMessage');
            $this->_objTpl->hideBlock('directoryInputFields');
        } else {
            // set variables
            $this->_objTpl->setVariable(array(
                'DIRECTORY_CATEGORY_DESELECTED' => $categorieDe,
                'DIRECTORY_CATEGORY_SELECTED' => $categorieSe,
                'DIRECTORY_LEVELS_DESELECTED' => $levelsDe,
                'DIRECTORY_LEVELS_SELECTED' => $levelsSe,
                'DIRECTORY_ID' => $id,
// TODO: Not defined
//                'DIRECTORY_EDIT_FILE' => $filename,
                'DIRECTORY_LINK' => $link,
// TODO: Not defined
//                'DIRECTORY_ATTACHMENT' => $attachment,
                'TXT_DIRECTORY_LEVEL' => $_ARRAYLANG['TXT_LEVEL'],
                'TXT_DIRECTORY_RSSLINK' => $_ARRAYLANG['TXT_DIRECTORY_RSS'],
                'TXT_DIRECTORY_FILE' => $_ARRAYLANG['TXT_DIRECTORY_UPLOAD'],
                'TXT_DIRECTORY_LINK' => $_ARRAYLANG['TXT_DIRECTORY_LINK'],
                'TXT_DIRECTORY_ATTACHMENT' => $_ARRAYLANG['TXT_DIRECTORY_ATTACHMENT'],
                'TXT_DIRECTORY_MAKE_SELECTION' => $_ARRAYLANG['TXT_DIRECTORY_PLEASE_CHOSE'],
                'TXT_DIRECTORY_FILETYPE' => $_ARRAYLANG['TXT_DIRECTORY_FILETYP'],
                'TXT_DIRECTORY_CATEGORY' => $_ARRAYLANG['TXT_DIR_F_CATEGORIE'],
                'TXT_DIRECTORY_UPDATE' => $_ARRAYLANG['TXT_DIRECTORY_SAVE'],
                'TXT_FIELDS_REQUIRED' => $_ARRAYLANG['DIRECTORY_CHECK_REQIERED'],
            ));
            if ($this->settings['levels']['value']=='0') {
                $this->_objTpl->hideBlock('directoryLevels');
            }

            if ($this->_isGoogleMapEnabled('frontend')) {
                $this->_objTpl->addBlockFile('DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK', 'direcoryGoogleMapJavascript', 'modules/Directory/Template/View/Backend/module_directory_googlemap_include.html');
                $this->_objTpl->setVariable(array(
                    'DIRECTORY_GOOGLE_API_KEY' => $_CONFIG["googleMapsAPIKey"],
                    'TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY' => $_ARRAYLANG['TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY'],
                    'TXT_DIR_GEO_TOO_MANY_QUERIES' => $_ARRAYLANG['TXT_DIR_GEO_TOO_MANY_QUERIES'],
                    'TXT_DIR_GEO_SERVER_ERROR' => $_ARRAYLANG['TXT_DIR_GEO_SERVER_ERROR'],
                    'TXT_DIR_GEO_NOT_FOUND' => $_ARRAYLANG['TXT_DIR_GEO_NOT_FOUND'],
                    'TXT_DIR_GEO_SUCCESS' => $_ARRAYLANG['TXT_DIR_GEO_SUCCESS'],
                    'TXT_DIR_GEO_MISSING' => $_ARRAYLANG['TXT_DIR_GEO_MISSING'],
                    'TXT_DIR_GEO_UNKNOWN' => $_ARRAYLANG['TXT_DIR_GEO_UNKNOWN'],
                    'TXT_DIR_GEO_UNAVAILABLE' => $_ARRAYLANG['TXT_DIR_GEO_UNAVAILABLE'],
                    'TXT_DIR_GEO_BAD_KEY' => $_ARRAYLANG['TXT_DIR_GEO_BAD_KEY'],
                    'DIRECTORY_START_X' => 'null',
                    'DIRECTORY_START_Y' => 'null',
                    'DIRECTORY_START_ZOOM' => 'null',
                    'DIRECTORY_ENTRY_NAME' => 'null',
                    'DIRECTORY_ENTRY_COMPANY' => 'null',
                    'DIRECTORY_ENTRY_STREET' => 'null',
                    'DIRECTORY_ENTRY_ZIP' => 'null',
                    'DIRECTORY_ENTRY_LOCATION' => 'null',
                    'DIRECTORY_MAP_LON_BACKEND' => $this->googleMapStartPoint['lon'],
                    'DIRECTORY_MAP_LAT_BACKEND' => $this->googleMapStartPoint['lat'],
                    'DIRECTORY_MAP_ZOOM_BACKEND' => $this->googleMapStartPoint['zoom'],
                    'IS_BACKEND' => 'true',
                ));
                if ($this->_objTpl->blockExists('direcoryGoogleMapJavascript')) {
                    $this->_objTpl->parse('direcoryGoogleMapJavascript');
                }
            }
            $this->_objTpl->hideBlock('directoryMessage');
            $this->_objTpl->parse('directoryInputFields');
        }
    }


    /**
     * search feed with fulltext
     * @access   public
     * @return   string  $status
     * @global    ADONewConnection
     * @global    array
     */
    function searchFeed()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get position for paging
        if (!isset($_GET['pos'])) {
            $_GET['pos']='0';
        }

        $pos = intval($_GET['pos']);
// TODO: Never used
//        $searchTermOrg = contrexx_addslashes($_GET['term']);
        $searchTerm = contrexx_addslashes($_GET['term']);
        $searchTermGoogle = $searchTerm;
        $array = explode(' ', $searchTerm);
        $tmpTerm = '';
        for($x = 0; $x < count($array); $x++) {
            $tmpTerm .= (empty($tmpTerm) ? '' : '%').$array[$x];
        }

        //set tree
        $tree = "&nbsp;&raquo;&nbsp;<a href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;cmd=search'>".$_ARRAYLANG['TXT_DIR_F_SEARCH']."</a>";
        //get search
        $this->getSearch();

        $searchTermExp = '';
        $objResult = $objDatabase->Execute("SELECT id, name, title FROM ".DBPREFIX."module_directory_inputfields WHERE exp_search='1' ORDER BY sort");
        if ($objResult) {
            while (!$objResult->EOF) {
                if (!empty($_GET[$objResult->fields['name']]) && $_GET['check'] == 'exp') {
                    $query_search .= "AND ".$objResult->fields['name']." LIKE ('%".contrexx_addslashes($_GET[$objResult->fields['name']])."%') ";
                    $searchTermGoogle .= "+".contrexx_addslashes($_GET[$objResult->fields['name']]);
                    $searchTermExp .= "&amp;".$objResult->fields['name']."=".contrexx_addslashes($_GET[$objResult->fields['name']]);
                }
                $objResult->MoveNext();
            }
        }

        if ($_GET['cid'] != "" && $_GET['check'] == 'exp') {
            array_push($this->searchCategories, intval($_GET['cid']));
            $this->getCatIds(intval($_GET['cid']));

            if (!empty($this->searchCategories)) {
                foreach ($this->searchCategories as $catId) {
                    $categories .= "(rel_cat.cat_id='".$catId."' AND rel_cat.dir_id=files.id) OR ";
                }
            }
            $query_search .= " AND (".$categories."  (rel_cat.cat_id='".intval($_GET['cid'])."' AND rel_cat.dir_id=files.id))";
            $searchTermExp .= "&amp;cid=".intval($_GET['cid']);
            $db .= DBPREFIX."module_directory_rel_dir_cat AS rel_cat, ";
        }

        if ($_GET['lid'] != "" && $_GET['check'] == 'exp') {
            array_push($this->searchLevels, intval($_GET['lid']));
            $this->getLevelIds(intval($_GET['lid']));
            if (!empty($this->searchLevels)) {
                foreach ($this->searchLevels as $levelId) {
                    $levels .= "(rel_level.level_id='".$levelId."' AND rel_level.dir_id=files.id) OR ";
                }
            }
            $query_search .=" AND (".$levels."  (rel_level.level_id='".intval($_GET['lid'])."' AND rel_level.dir_id=files.id))";
            $searchTermExp .= "&amp;lid=".intval($_GET['lid']);
            $db .= DBPREFIX."module_directory_rel_dir_level AS rel_level, ";
        }

        if ($_GET['check'] == 'norm') {
            $query_search = "
                OR files.date LIKE ('%$searchTerm%')
                OR files.relatedlinks LIKE ('%$searchTerm%')
                OR files.link LIKE ('%$searchTerm%')
                OR files.platform LIKE ('%$searchTerm%')
                OR files.language LIKE ('%$searchTerm%')
                OR files.canton LIKE ('%$searchTerm%')
                OR files.company_name LIKE ('%$searchTerm%')
                OR files.street LIKE ('%$searchTerm%')
                OR files.zip LIKE ('%$searchTerm%')
                OR files.city LIKE ('%$searchTerm%')
                OR files.phone LIKE ('%$searchTerm%')
                OR files.contact LIKE ('%$searchTerm%')
                OR files.information LIKE ('%$searchTerm%')
                OR files.fax LIKE ('%$searchTerm%')
                OR files.mobile LIKE ('%$searchTerm%')
                OR files.mail LIKE ('%$searchTerm%')
                OR files.homepage LIKE ('%$searchTerm%')
                OR files.industry LIKE ('%$searchTerm%')
                OR files.legalform LIKE ('%$searchTerm%')
                OR files.employee LIKE ('%$searchTerm%')
                OR files.foundation LIKE ('%$searchTerm%')
                OR files.mwst LIKE ('%$searchTerm%')
            ";
        }

        //internal search
        /*if ($searchTerm != "" && $_GET['check'] == 'norm') {*/
            //get feeds by searchterm
            $query = "
                SELECT files.id, files.title, files.description, files.link,
                 MATCH (files.description) AGAINST ('%$searchTerm%') AS score
                  FROM ".$db." ".DBPREFIX."module_directory_dir AS files
                 WHERE ((files.title LIKE ('%$searchTerm%') OR files.description LIKE ('%$searchTerm%') OR files.searchkeys LIKE ('%$searchTerm%'))
                        $query_search)
                   AND files.status != 0
                 GROUP BY files.id
                 ORDER BY files.spezial DESC, score DESC
            ";

            ////// paging start /////////
            $pagingLimit = intval($this->settings['pagingLimit']['value']);
            $objResult = $objDatabase->Execute($query);
            $count = $objResult->RecordCount();
            $pos = intval($_GET['pos']);

// TODO: $term is not defined, possibly $searchTerm?
// TODO: $check is not defined
//            $paging = getPaging($count, $pos, "&amp;section=Directory&amp;cmd=search&amp;term=".$term."&amp;check=".$check.$searchTermExp, "<b>".$_ARRAYLANG['TXT_DIRECTORY_FEEDS']."</b>", true, $pagingLimit);
            $paging = getPaging($count, $pos, "&section=Directory&cmd=search&term=".$searchTerm.$searchTermExp."&check=".$_GET['check'], "<b>".$_ARRAYLANG['TXT_DIRECTORY_FEEDS']."</b>", true, $pagingLimit);
            ////// paging end /////////
            $objResult = $objDatabase->SelectLimit($query, $pagingLimit, $pos);

            //show Feeds
            if ($objResult) {
                $i = 0;
                while (!$objResult->EOF) {
                    //get score
// TODO: Never used
//                    $score = $objResult->fields['score'];
// TODO: Never used
//                    $scorePercent = ($score >= 1 ? 100 : intval($score*100));
                    //get votes
                    $this->getVotes($objResult->fields['id']);
                    //get voting
                    $this->getVoting($objResult->fields['id']);
                    //get content
                    $this->getContent($objResult->fields['id'], intval($_GET['cid']), intval($_GET['lid']));
                    //row class
                    $this->_objTpl->setVariable(
                        'DIRECTORY_FEED_ROW', (++$i % 2 ? 'row1' : 'row2')
                    );
                    $this->_objTpl->parse('showResults');
                    //check paging
                    if ($count < $pagingLimit) {
                        $paging = "";
                    }
                    $objResult->MoveNext();
                }
            }

            if ($count == 0) {
                $this->_objTpl->hideBlock('showResults');
                // set variables
                $this->_objTpl->setVariable(
                    'DIRECTORY_NO_FEEDS_FOUND', $_ARRAYLANG['DIRECTORY_NO_FEEDS_FOUND']
                );
                $this->_objTpl->parse('noResults');
            }
/*
        } else {
            $this->_objTpl->hideBlock('showResults');
            // set variables
            $this->_objTpl->setVariable(
                'DIRECTORY_NO_FEEDS_FOUND', $_ARRAYLANG['DIRECTORY_NO_FEEDS_FOUND']
            );
            $this->_objTpl->parse('noResults');
        }
  */
        // set variables
        $this->_objTpl->setVariable(array(
            'DIRECTORY_CATEGORY_NAVI' => $tree,
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
            'TXT_DIRECTORY_SEARCHTERM' => str_replace('%', ' ', $searchTerm),
            'SEARCH_PAGING' => $paging,
        ));
    }


    function getCatIds($catId)
    {
        global $objDatabase;

        //get all categories
        $objResultCat = $objDatabase->Execute("SELECT id, parentid, name FROM ".DBPREFIX."module_directory_categories WHERE parentid='".$catId."'");
        if ($objResultCat) {
            while (!$objResultCat->EOF) {
                if (!empty($objResultCat->fields['id'])) {
                    array_push($this->searchCategories, $objResultCat->fields['id']);
                }
                $this->getCatIds($objResultCat->fields['id']);
                $objResultCat->MoveNext();
            }
        }
    }


    function getLevelIds($levelId)
    {
        global $objDatabase;

        //get all categories
        $objResultLevel = $objDatabase->Execute("
            SELECT id, parentid, name
              FROM ".DBPREFIX."module_directory_levels
             WHERE parentid='$levelId'
        ");
        if ($objResultLevel) {
            while (!$objResultLevel->EOF) {
                if (!empty($objResultLevel->fields['id'])) {
                    array_push($this->searchLevels, $objResultLevel->fields['id']);
                }
                $this->getLevelIds($objResultLevel->fields['id']);
                $objResultLevel->MoveNext();
            }
        }
    }

    /**
     * redirect feed
     * @access   public
     * @return   string  $status
     * @global    ADONewConnection
     * @global    array
     * @param    int        $id
     */
    function redirectFeed($id)
    {
        global $objDatabase, $_ARRAYLANG;

        //crate latest and popular xml
        $this->createRSSlatest();
        //redirect link
        if (isset($id)) {
            $this->getHits($id);
            $objResult = $objDatabase->Execute("
                SELECT  link, typ, filename
                  FROM ".DBPREFIX."module_directory_dir
                 WHERE status='1'
                   AND id='$id'
                 ORDER BY id DESC
            ");
            if ($objResult) {
                while (!$objResult->EOF) {
                    if ($objResult->fields['typ'] == "file") {
                        $link = $this->mediaWebPath."uploads/".$objResult->fields['filename'];
                    } else {
                        if (substr($objResult->fields['link'], 0,7) != "http://" && $objResult->fields['link'] != "") {
                            $link = "http://".$objResult->fields['link'];
                        } else {
                            $link=$objResult->fields['link'];
                        }
                    }
                    $objResult->MoveNext();
                }
            }
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".$link);
            exit;
        }
    }


    /**
     * Get latest directory entries
     * @access    public
     * @param    string $pageContent
     * @param     string
     */
    function getBlockLatest($arrBlocks)
    {
        global $objDatabase, $objTemplate;

        $i = 0;
        $numBlocks = count($arrBlocks);
        //get latest
        $query = "
            SELECT id, title, description, logo, `date`
              FROM ".DBPREFIX."module_directory_dir
             WHERE status != 0
             ORDER BY id DESC
        ";
        $objResult = $objDatabase->SelectLimit($query, $this->settings['latest_content']['value']);
        if ($objResult) {
            while (!$objResult->EOF) {
                if (!empty($objResult->fields['logo'])) {
                    $logo =
                        '<img src="'.$this->mediaWebPath.'thumbs/'.
                        $objResult->fields['logo'].'" border="0" alt="'.
                        stripslashes($objResult->fields['title']).'" />';
                } else {
                    $logo = '';
                }
                if (strlen($objResult->fields['description']) > 60) {
                    $points = "...";
                } else {
                    $points = "";
                }
                $parts= explode("\n", wordwrap($objResult->fields['description'], 60, "\n"));

                // set variables
                $objTemplate->setVariable(array(
                    'DIRECTORY_DATE' => date("d.m.Y", $objResult->fields['date']),
                    'DIRECTORY_TITLE' => stripslashes($objResult->fields['title']),
                    'DIRECTORY_DESC' => $parts[0].$points,
                    'DIRECTORY_LOGO' => $logo,
                    'DIRECTORY_ID' => $objResult->fields['id'],
                ));
                $blockId = $arrBlocks[$i];
                $objTemplate->parse('directoryLatest_row_'.$blockId);
                if ($i < $numBlocks-1) {
                    ++$i;
                } else {
                    $i = 0;
                }
                $objResult->MoveNext();
            }
        }
    }


    /**
     * votes for feeds
     * @access    public
     */
    function voteFeed()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);
        $client = "";
        //client/proxy info
        $this->arrClient['useragent'] = htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, CONTREXX_CHARSET);
        if (stristr($this->arrClient['useragent'],"phpinfo")) {
            $this->arrClient['useragent'] = "<b>p_h_p_i_n_f_o() Possible Hacking Attack</b>";
        }
        $this->arrClient['language'] = htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE'], ENT_QUOTES, CONTREXX_CHARSET);
        $this->_getProxyInformations();
        $client = md5($this->arrClient['ip'].$this->arrClient['useragent'].$this->arrClient['language'].$this->arrProxy['ip'].$this->arrProxy['host']);
        $time = time();
        $voteNEW = isset($_GET['vote']) ? contrexx_input2int($_GET['vote']) : 0;
        $id      = isset($_GET['id']) ? contrexx_input2int($_GET['id']) : 0;
        $cid     = isset($_GET['cid']) ? contrexx_input2int($_GET['cid']) : 0;
        $lid     = intval($_GET['lid']);

        //get clients
        $objResult = $objDatabase->SelectLimit("
            SELECT client, vote, count
              FROM ".DBPREFIX."module_directory_vote
             WHERE feed_id='$id'
        ", 1);
        if ($objResult) {
            while (!$objResult->EOF) {
                $clientOLD = $objResult->fields['client'];
                $voteOLD = $objResult->fields['vote'];
                $countOLD = $objResult->fields['count'];
                $objResult->MoveNext();
            }
        }

        $feedTitle = '';
        if (!checkForSpider() && $id && $voteNEW && $client != $clientOLD) {
            if ($voteNEW > 10) {
                $voteNEW = 10;
            } elseif ($voteNEW < 1) {
                $voteNEW = 1;
            }
            if (id !== "") {
                //insert votes
                if ($objResult->RecordCount() != 0) {
                    $vote = $voteNEW+$voteOLD;
                    $count = $countOLD+1;
                    $objResult = $objDatabase->Execute("
                        UPDATE ".DBPREFIX."module_directory_vote
                           SET vote='".contrexx_addslashes($vote)."',
                               count='".contrexx_addslashes($count)."',
                               client='".contrexx_addslashes($client)."',
                               time='".contrexx_addslashes($time)."'
                         WHERE feed_id='".contrexx_addslashes($id)."'
                    ");
                } else {
                    $objResult = $objDatabase->Execute("
                        INSERT INTO ".DBPREFIX."module_directory_vote
                           SET feed_id=".contrexx_addslashes($id).",
                               count='1',
                               vote='".contrexx_addslashes($voteNEW)."',
                               client='".contrexx_addslashes($client)."',
                               time='".contrexx_addslashes($time)."'
                    ");
                }
            }
            $title = $_ARRAYLANG['TXT_DIRECTORY_VOTING_SUCCESFULL'];
// TODO: $feedTitle is not defined!  No idea on what to place there.
            $link = '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Directory&cmd=detail&id='.$id.'" target="_blank">'.$feedTitle.'</a>';
//            $link = '<a href="'.CONTREXX_SCRIPT_PATH.'?section=Directory&cmd=detail&id='.$id.'" target="_blank">???</a>';
            $text = str_replace('%LINK%', $link, $_ARRAYLANG['TXT_DIRECTORY_VOTING_SUCCESFULL_TEXT']);
            $text = str_replace('%VOTE%', $voteNEW, $text);
        } else {
            $title = $_ARRAYLANG['TXT_DIRECTORY_VOTING_FAILED'];
            $text = $_ARRAYLANG['TXT_DIRECTORY_VOTING_FAILED_TEXT'];
        }
        //get navtree
        $this->getNavtree($lid, $cid);
        //get search
        $this->getSearch();
        // set variables
        $this->_objTpl->setVariable(array(
            'DIRECTORY_CATEGORY_NAVI' => $this->navtree,
            'DIRECTORY_VOTE_TITLE' => $title,
            'DIRECTORY_VOTE_TEXT' => $text,
            'DIRECTORY_BACK' => '<a href="javascript:history.go(-1);">'.$_ARRAYLANG['TXT_DIRECTORY_BACK'].'</a>',
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
        ));
    }


    /**
     * get voting fields
     * @access    public
     * @param     int
     * @param     int
     * @param     int
     */
    function getVoting($id, $cid=0, $lid=0)
    {
        global $_ARRAYLANG;

        $voteImg = '';
        for ($x = 1; $x <= 10; ++$x) {
            $voteImg .=
                '<a href="index.php?section=Directory&amp;cmd=vote&amp;id='.$id.
                '&amp;cid='.$cid.'&amp;lid='.$lid.'&amp;vote='.$x.
                '"><img src="'.$this->imageWebPath.'/'.$x.
                '.gif" border="0" alt="'.$x.'" /></a>'."\n";
        }
        // set variables
        $this->_objTpl->setVariable(array(
            'DIRECTORY_FEED_VOTE_ID' => $id,
            'DIRECTORY_FEED_VOTE' => $_ARRAYLANG['TXT_DIRECTORY_YOUR_VOTE'],
            'DIRECTORY_FEED_VOTE_IMG' => $voteImg,
            'DIRECTORY_FEED_VOTE_LINK' => "javascript:toggle('voting_$id');",
        ));
    }


    /**
     * Get proxy informations
     *
     * Determines if a proxy is used or not.
     * If so, then proxy information is collected.
     */
    function _getProxyInformations()
    {
        if (isset($_SERVER['HTTP_VIA']) && $_SERVER['HTTP_VIA']) { // client does use a proxy
            $this->arrProxy['ip'] = $_SERVER['REMOTE_ADDR'];
            $net = \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Net');
            $this->arrProxy['host'] = $net->getHostByAddr($this->arrProxy['ip']);
            $proxyUseragent = trim(addslashes(urldecode(strstr($_SERVER['HTTP_VIA'],' '))));
            $startPos = strpos($proxyUseragent, '(');
            $this->arrProxy['useragent'] = substr($proxyUseragent,$startPos+1);
            $endPos = strpos($this->arrProxy['useragent'], ')');
            if ($this->arrProxy['host'] == $this->arrProxy['ip']) { // no hostname found, try to take it out from useragent-infos
                $endPos = strpos($proxyUseragent,"(");
                $this->arrProxy['host'] = substr($proxyUseragent,0,$endPos);
            }
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->arrClient['ip'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                if (isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $this->arrClient['ip'] = $_SERVER['HTTP_CLIENT_IP'];
                } else {
                    $this->arrClient['ip'] = $_SERVER['REMOTE_ADDR'];
                }
            }
        } else { // Client does not use proxy
            $this->arrClient['ip'] = $_SERVER['REMOTE_ADDR'];
            $this->arrProxy['ip'] = '';
            $this->arrProxy['host'] = '';
        }
    }


    function latest()
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);
        //get navigation
        $verlauf = "&nbsp;&raquo;&nbsp;<a href='".CONTREXX_SCRIPT_PATH."?section=Directory&amp;cmd=latest'>".$_ARRAYLANG['TXT_DIRECTORY_LATEST_FEEDS']."</a>";
        //get search
        $this->getSearch();
        // set variables
        $this->_objTpl->setVariable(array(
            'DIRECTORY_CATEGORY_NAVI' => $verlauf,
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
        ));
        $objResult = $objDatabase->SelectLimit("
            SELECT id
              FROM ".DBPREFIX."module_directory_dir
             WHERE status='1'
             ORDER BY id DESC
        ", 10);
// TODO: Never used
//        $count = $objResult->RecordCount();
        if ($objResult) {
            $i = 0;
            while (!$objResult->EOF) {
                //get content
                $this->getContent($objResult->fields['id'], "");
                //get votes
                $this->getVotes($objResult->fields['id']);
                //get voting
                $this->getVoting($objResult->fields['id']);
                //row class
                $this->_objTpl->setVariable(
                    'DIRECTORY_FEED_ROW', (++$i % 2 ? 'row1' : 'row2')
                );
                $this->_objTpl->parse('showFeeds');
                $objResult->MoveNext();
            }
        }
        $this->_objTpl->setVariable(array(
            'DIRECTORY_CATEGORY_NAVI' => $verlauf,
            'TXT_DIRECTORY_DIR' => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
        ));
    }


    function getPageTitle()
    {
        global $objDatabase, $_ARRAYLANG;

        $lid = (isset($_GET['lid']) ? intval($_GET['lid']) : 0);
        $cid = (isset($_GET['cid']) ? intval($_GET['cid']) : 0);
        $this->getNavtreeLevels($lid);
        $this->getNavtreeCategories($cid);
        $navtree = '';

        $arr_levels = $this->navtreeLevels;
        $arr_cats = $this->navtreeCategories;
        ksort($arr_levels);
        ksort($arr_cats);

        $page_title_level = join(" - ", $arr_levels);
        $page_title_cat = join(" - ", $arr_cats);

        if(!empty($arr_levels) && !empty($this->navtreeCategories)) {
            $navtree = $page_title_level." - ".$page_title_cat;
        } else {
            $navtree = $page_title_level.$page_title_cat;
        }

        if(!empty($navtree) && !empty($this->pageTitle)) {
            $navtree .= "&nbsp;-&nbsp;";
        }
        $this->pageTitle = $navtree.$this->pageTitle;
        return $this->pageTitle;
    }


    /**
     * get Navtree
     * @access   public
     * @param    string    $lid
     * @param    string    $cid
     */
    function getNavtree($lid, $cid)
    {
        $this->getNavtreeLevels($lid);
        $this->getNavtreeCategories($cid);
        $navTreeLevel = '';
        foreach ($this->navtreeLevels as $levelKey => $levelName) {
            $navTreeLevel =
                "&nbsp;&raquo;&nbsp;<a href='".CONTREXX_SCRIPT_PATH.
                "?section=Directory&amp;lid=".$levelKey."'>".$levelName."</a>".
                $navTreeLevel;
        }
        $navTreeCat = '';
        $levelLink = '';
        foreach ($this->navtreeCategories as $catKey => $catName) {
            if ($lid != 0) {
                $levelLink = "&amp;lid=".$lid;
            }
            $navTreeCat = "&nbsp;&raquo;&nbsp;<a href='".CONTREXX_SCRIPT_PATH."?section=Directory".$levelLink."&amp;cid=".$catKey."'>".$catName."</a>".$navTreeCat;
        }
        $this->navtree = $navTreeLevel.$navTreeCat;
    }


    function getNavtreeLevels($lid)
    {
        global $objDatabase, $_ARRAYLANG;

        $objResult = $objDatabase->Execute("
            SELECT id, name, parentid
              FROM ".DBPREFIX."module_directory_levels
             WHERE status='1'
               AND id='$lid'
             ORDER BY id DESC
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $tempId = $objResult->fields['parentid'];
                $this->navtreeLevels[$objResult->fields['id']] = $objResult->fields['name'];
                $this->getNavtreeLevels($tempId);
                $objResult->MoveNext();
            }
        }
    }


    function getNavtreeCategories($cid)
    {
        global $objDatabase, $_ARRAYLANG;

        $objResult = $objDatabase->Execute("
            SELECT id, name, parentid
              FROM ".DBPREFIX."module_directory_categories
             WHERE status='1'
               AND id='$cid'
             ORDER BY id DESC
        ");

        if ($objResult) {
            while (!$objResult->EOF) {
                $tempId = $objResult->fields['parentid'];
                $this->navtreeCategories[$objResult->fields['id']] = $objResult->fields['name'];
                $this->getNavtreeCategories($tempId);
                $objResult->MoveNext();
            }
        }
    }

}

?>

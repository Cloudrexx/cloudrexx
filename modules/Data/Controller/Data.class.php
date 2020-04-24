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
 * Data
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_data
 */
namespace Cx\Modules\Data\Controller;

$_ARRAYLANG['TXT_DATA_DOWNLOAD_ATTACHMENT'] = "Anhang herunterladen";

/**
 * DataAdmin
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version        $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_data
 */
class Data extends \Cx\Modules\Data\Controller\DataLibrary
{
    /**
     * @var   \Cx\Core\Html\Sigma
     */
    public $_objTpl;
    public $_strStatusMessage = '';
    public $_strErrorMessage = '';
    public $curCmd;


   /**
    * Constructor
    *
    * Call parent constructor, set language id and create local template object
    * @global    integer
    */
    function __construct($strPageContent)
    {
        global $_LANGID;

        parent::__construct();
        $this->_intLanguageId = intval($_LANGID);
        $this->_intCurrentUserId = (isset($_SESSION['auth']['userid'])) ? intval($_SESSION['auth']['userid']) : 0;
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($strPageContent);
    }


    /**
     * Reads $_GET['cmd'] and selects (depending on the value) an action
     */
    function getPage()
    {
        if (isset($_GET['act'])) {
            if ($_GET['act'] == "shadowbox") {
                $this->shadowbox();
            }
        }
        if (!isset($_GET['cmd'])) {
            $_GET['cmd'] = '';
        } else {
            $this->curCmd = $_GET['cmd'];
        }
        if (isset($_GET['cid'])) {
            $this->showCategory(intval($_GET['cid']));
        } elseif (isset($_GET['id'])) {
            $this->showDetails(intval($_GET['id']));
        } elseif ($this->curCmd == 'search') {
            $this->showSearch(isset($_POST['term']) ? contrexx_stripslashes($_POST['term']) : '');
        } else {
            $this->showCategoryOverview();
        }
        return $this->_objTpl->get();
    }


    function showSearch($keyword)
    {
        global $objDatabase, $_LANGID, $_ARRAYLANG;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if (   !empty($keyword)
            && ($objResult = $objDatabase->Execute("
                SELECT tblM.message_id, tblL.subject, tblL.content,
                       tblL.image, tblL.thumbnail, tblL.mode,
                       tblL.forward_url, tblL.forward_target
                  FROM `".DBPREFIX."module_data_messages` AS tblM
                 INNER JOIN `".DBPREFIX."module_data_messages_lang` AS tblL
                 USING (message_id)
                 WHERE tblM.active = '1'
                   AND (tblM.release_time = 0 or tblM.release_time <= ".time().")
                   AND (tblM.release_time_end = 0 or tblM.release_time_end >= ".time().")
                   AND tblL.is_active = '1'
                   AND tblL.lang_id = ".$_LANGID."
                   AND (tblL.subject LIKE '%".addslashes($keyword)."%'
                    OR tblL.content LIKE '%".addslashes($keyword)."%'
                    OR tblL.tags LIKE '%".addslashes($keyword)."%')"))
            && $objResult
            && $objResult->RecordCount()) {
            while (!$objResult->EOF) {
                $image = "";
                if ($objResult->fields['image']) {
                    if ($objResult->fields['thumbnail']) {
                        $thumb_name = \ImageManager::getThumbnailFilename(
                            $objResult->fields['thumbnail']);
                        if (file_exists(ASCMS_PATH.$thumb_name)) {
                            $image = "<img src=\"".$thumb_name."\" alt=\"\" border=\"1\" style=\"float: left; width:100px;\"/>";
                        } else {
                            $image = "<img src=\"".$objResult->fields['thumbnail']."\" alt=\"\" border=\"1\" style=\"float: left; width: 80px;\" />";
                        }
                    } elseif (file_exists($cx->getWebsiteImagesDataPath() . '/' . $objResult->fields['message_id'] . '_' . $_LANGID . '_' . basename($objResult->fields['image']))) {
                        $image = "<img src=\"".$cx->getWebsiteImagesDataWebPath().'/'.$objResult->fields['message_id'].'_'.$_LANGID.'_'.basename($objResult->fields['image'])."\" alt=\"\" border=\"1\" style=\"float: left; width:100px;\"/>";
                    } elseif (file_exists(
                        ASCMS_PATH.
                        \ImageManager::getThumbnailFilename(
                            $objResult->fields['image']))) {
                        $image =
                            "<img src=\"".
                            \ImageManager::getThumbnailFilename(
                                $objResult->fields['image']).
                                "\" alt=\"\" border=\"1\" style=\"float: left; width:100px;\"/>";
                    } else {
                        $image = "<img src=\"".$objResult->fields['image']."\" alt=\"\" border=\"1\" style=\"float: left; width: 80px;\" />";
                    }
                }
                $lang = $_LANGID;
                $width = $this->_arrSettings['data_shadowbox_width'];
                $height = $this->_arrSettings['data_shadowbox_height'];
                if ($objResult->fields['mode'] == "normal") {
                    if ($this->_arrSettings['data_entry_action'] == "content") {
                        $cmd = $this->_arrSettings['data_target_cmd'];
                        $url = "index.php?section=Data&amp;cmd=".$cmd;
                    } else {
                        $url = "index.php?section=Data&amp;act=shadowbox&amp;height=".$height."&amp;width=".$width."&amp;lang=".$lang;
                    }
                } else {
                    $url = $objResult->fields['forward_url'];
                }
                $this->_objTpl->setVariable(array(
                    'ENTRY_HREF'  => $url."&amp;id=".$objResult->fields['message_id'],
                    'ENTRY_IMAGE' => $image,
                    'ENTRY_TITLE' => $objResult->fields['subject'],
                    'TXT_MORE'    => $this->langVars['TXT_DATA_MORE'],
                ));
                $this->_objTpl->parse('single_entry');
                $objResult->MoveNext();
            }
            $this->_objTpl->parse('datalist_single_category');
        } else {
            $this->_objTpl->hideBlock('datalist_single_category');
        }
        $this->_objTpl->setVariable(array(
            'DATA_SEARCH_TERM' => htmlentities($keyword, ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_DATA_SEARCH'  => $_ARRAYLANG['TXT_DATA_SEARCH'],
        ));
    }


    /**
     * Show the list of categories
     */
    function showCategoryOverview()
    {
        $arrCategories = $this->createCategoryArray();
        $catTree = $this->buildCatTree($arrCategories);
        $catList = $this->parseCategoryView($catTree, $arrCategories);
        $this->_objTpl->setVariable("CATEGORIES", $catList);
        if ($this->_objTpl->blockExists("showDataCategories"))
            $this->_objTpl->parse("showDataCategories");
    }


    /**
     * Generate the category tree recursively
     * @param array $catTree The categories sorted as tree
     * @param array $arrCategories
     * @param int $level
     * @return string
     */
    function parseCategoryView($catTree, $arrCategories, $level=0)
    {
        $parsed = false;
        $catList = str_repeat("\t", $level)."<ul>\n";
        foreach ($catTree as $key => $value) {
            if ($arrCategories[$key]['active']) {
                $catName = $arrCategories[$key][$this->_intLanguageId]['name'];
                $indent = $level * 10;

                $catList .= str_repeat("\t", $level+1)."<li style=\"padding-left: ".$indent."px\">\n";
                $catList .= str_repeat("\t", $level+1)."<a href=\"index.php?section=Data&amp;cmd=".contrexx_raw2xhtml($this->curCmd)."&amp;cid=".$key."\">".$catName."</a>\n";
                if (count($value) > 0) {
                    $catList .= $this->parseCategoryView($value, $arrCategories, $level+1);
                }
                $catList .= str_repeat("\t", $level+1)."</li>\n";
                $parsed = true;
            }
        }
        $catList .= str_repeat("\t", $level)."</ul>\n";
        if ($parsed) {
            return $catList;
        }
        return "";
    }


    /**
     * Show one category
     * @param unknown_type $id
     */
    function showCategory($id)
    {
        global $_ARRAYLANG;

        $arrEntries = $this->createEntryArray($this->_intLanguageId);
        $this->createSettingsArray();
        foreach ($arrEntries as $key => $value) {
            if ($value['active']) {
                // check date
                if ($value['release_time'] != 0) {
                   if ($value['release_time'] > time()) {
                       // too old
                       continue;
                   }
                   // if it is not endless (0), check if 'now' is past the given date
                   if ($value['release_time_end'] !=0 && time() > $value['release_time_end']) {
                       continue;
                   }
                }
                if ($this->categoryMatches($id, $value['categories'][$this->_intLanguageId])) {
                    $this->_objTpl->setVariable(array(
                       "ENTRY_TITLE"   => $value['translation'][$this->_intLanguageId]['subject'],
                       "ENTRY_CONTENT" => $this->getIntroductionText($value['translation'][$this->_intLanguageId]['content']),
                       "ENTRY_ID"      => $key,
                       "ENTRY_HREF"    => \Cx\Core\Routing\Url::fromModuleAndCmd('Data', $this->curCmd, '', array('id' => $key)),
                       "TXT_MORE"      => $_ARRAYLANG['TXT_DATA_MORE'],
                       "CMD"           => contrexx_raw2xhtml($this->curCmd),
                    ));
                    $this->_objTpl->parse("entry");
                }
            }
        }
        $this->_objTpl->parse("showDataCategory");
    }


    /**
     * Shows all existing entries of the data in descending order.
     * @global     array
     */
    function showEntries()
    {
        global $_ARRAYLANG;

        $arrEntries = $this->createEntryArray($this->_intLanguageId);
        foreach ($arrEntries as $intEntryId => $arrEntryValues) {
            $this->_objTpl->setVariable(array(
                'TXT_DATA_CATEGORIES' => $_ARRAYLANG['TXT_DATA_FRONTEND_SEARCH_RESULTS_CATEGORIES'],
                'TXT_DATA_TAGS'       => $_ARRAYLANG['TXT_DATA_FRONTEND_SEARCH_RESULTS_KEYWORDS'],
                'TXT_DATA_VOTING'     => $_ARRAYLANG['TXT_DATA_FRONTEND_OVERVIEW_VOTING'],
                'TXT_DATA_VOTING_DO'  => $_ARRAYLANG['TXT_DATA_FRONTEND_OVERVIEW_VOTING_DO'],
                'TXT_DATA_COMMENTS'   => $_ARRAYLANG['TXT_DATA_FRONTEND_OVERVIEW_COMMENTS'],
            ));

            $this->_objTpl->setVariable(array(
                'DATA_ENTRIES_ID'           => $intEntryId,
                'DATA_ENTRIES_TITLE'        => $arrEntryValues['subject'],
                //'DATA_ENTRIES_POSTED'       => $this->getPostedByString($arrEntryValues['user_name'],$arrEntryValues['time_created']),
                'DATA_ENTRIES_CONTENT'      => $arrEntryValues['translation'][$this->_intLanguageId]['content'],
                'DATA_ENTRIES_INTRODUCTION' => $this->getIntroductionText($arrEntryValues['translation'][$this->_intLanguageId]['content']),
                'DATA_ENTRIES_IMAGE'        => ($arrEntryValues['translation'][$this->_intLanguageId]['image'] != '') ? '<img src="'.$arrEntryValues['translation'][$this->_intLanguageId]['image'].'" title="'.$arrEntryValues['subject'].'" alt="'.$arrEntryValues['subject'].'" />' : '',
                'DATA_ENTRIES_VOTING'       => '&#216;&nbsp;'.$arrEntryValues['votes_avg'],
                //'DATA_ENTRIES_VOTING_STARS' => $this->getRatingBar($intEntryId),
                'DATA_ENTRIES_COMMENTS'     => $arrEntryValues['comments_active'].' '.$_ARRAYLANG['TXT_DATA_FRONTEND_OVERVIEW_COMMENTS'].'&nbsp;',
                'DATA_ENTRIES_CATEGORIES'   => $this->getCategoryString($arrEntryValues['categories'][$this->_intLanguageId], true),
                'DATA_ENTRIES_TAGS'         => $this->getLinkedTags($arrEntryValues['translation'][$this->_intLanguageId]['tags']),
                'DATA_ENTRIES_SPACER'       => ($this->_arrSettings['data_voting_activated'] && $this->_arrSettings['data_comments_activated']) ? '&nbsp;&nbsp;|&nbsp;&nbsp;' : '',
            ));
            if (!$this->_arrSettings['data_voting_activated']) {
                $this->_objTpl->hideBlock('showVotingPart');
            }
            if (!$this->_arrSettings['data_comments_activated']) {
                $this->_objTpl->hideBlock('showCommentPart');
            }
            $this->_objTpl->parse('showDataEntries');
        }
    }


    /**
     * Show a single entry
     * @param unknown_type $intMessageId
     */
    function showDetails($intMessageId)
    {
        global $_ARRAYLANG;

        $arrEntries = $this->createEntryArray();
        $entry = $arrEntries[$intMessageId];

        $image = "";
        if ($entry['translation'][$this->_intLanguageId]['image']) {
            $image = "<img src=\"".$entry['translation'][$this->_intLanguageId]['image']."\" alt=\"\" style=\"float: left; \"/>";
        }
        if ($entry['translation'][$this->_intLanguageId]['attachment']) {
            $this->_objTpl->setVariable(array(
                "HREF"          => $entry['translation'][$this->_intLanguageId]['attachment'],
                "TXT_DOWNLOAD"  =>
                    (empty($entry['translation'][$this->_intLanguageId]['attachment_desc'])
                        ? $_ARRAYLANG['TXT_DATA_DOWNLOAD_ATTACHMENT']
                        : $entry['translation'][$this->_intLanguageId]['attachment_desc']),
            ));
            $this->_objTpl->parse("attachment");
        }

        $this->_objTpl->setVariable(array(
           "ENTRY_SUBJECT" => $entry['translation'][$this->_intLanguageId]['subject'],
           "ENTRY_CONTENT" => $entry['translation'][$this->_intLanguageId]['content'],
           "IMAGE"         => $image,
        ));
        $this->_objTpl->parse("showDataDetails");
    }


    /**
     * Show the shadowbox
     */
    function shadowbox()
    {
        global $objDatabase, $_ARRAYLANG, $objInit;

        $id = intval($_GET['id']);
        $lang = intval($_GET['lang']);
        $entries = $this->createEntryArray();
        $entry  = $entries[$id];
        $settings = $this->createSettingsArray();
        $title = $entry['translation'][$lang]['subject'];
        $content = $entry['translation'][$lang]['content'];
        $picture = (!empty($entry['translation'][$lang]['image'])) ? $entry['translation'][$lang]['image'] : "none";

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_THEMES_PATH);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setCurrentBlock("shadowbox");

        $objResult = $objDatabase->SelectLimit("
            SELECT foldername
              FROM ".DBPREFIX."skins
             WHERE id='$objInit->currentThemesId'", 1);
        if ($objResult !== false) {
            $themesPath = $objResult->fields['foldername'];
        }

        $template = preg_replace('/\[\[([A-Z_]+)\]\]/', '{$1}', $settings['data_template_shadowbox']);
        $this->_objTpl->setTemplate($template);

        if ($entry['translation'][$lang]['attachment']) {
            $this->_objTpl->setVariable(array(
                "HREF"          => $entry['translation'][$lang]['attachment'],
                "TXT_DOWNLOAD"  => empty($entry['translation'][$lang]['attachment_desc']) ? $_ARRAYLANG['TXT_DATA_DOWNLOAD_ATTACHMENT'] : $entry['translation'][$lang]['attachment_desc']
            ));
            $this->_objTpl->parse("attachment");
        }

        $this->_objTpl->setVariable(array(
            "TITLE"         => $title,
            "CONTENT"       => $content,
            "PICTURE"       => $picture,
            "THEMES_PATH"   => $themesPath,
        ));
        if ($picture != "none") {
            $this->_objTpl->parse("image");
        } else {
            $this->_objTpl->hideBlock("image");
        }
        $this->_objTpl->parse("shadowbox");
        $this->_objTpl->show();
        die();
    }

}

?>

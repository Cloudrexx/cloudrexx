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
 * Gallery
 *
 * This class is used to publish the pictures of the gallery on the frontend.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.2
 * @package     cloudrexx
 * @subpackage  module_gallery
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Gallery\Controller;
/**
 * Gallery
 *
 * This class is used to publish the pictures of the gallery on the frontend.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.2
 * @package     cloudrexx
 * @subpackage  module_gallery
 * @todo        Edit PHP DocBlocks!
 */
class Gallery
{
    public $_objTpl;
    public $pageContent;
    public $arrSettings;
    public $strImagePath;
    public $strImageWebPath;
    public $strThumbnailPath;
    public $strThumbnailWebPath;
    public $langId;
    public $strCmd = '';


    /**
     * Constructor
     * @global ADONewConnection
     * @global array
     * @global integer
     */
    function __construct($pageContent)
    {
        global $objDatabase, $_LANGID;

        $this->pageContent = $pageContent;
        $this->langId= $_LANGID;

        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->strImagePath = ASCMS_GALLERY_PATH . '/';
        $this->strImageWebPath = ASCMS_GALLERY_WEB_PATH . '/';
        $this->strThumbnailPath = ASCMS_GALLERY_THUMBNAIL_PATH . '/';
        $this->strThumbnailWebPath = ASCMS_GALLERY_THUMBNAIL_WEB_PATH . '/';

        $objResult = $objDatabase->Execute('SELECT name, value FROM '.DBPREFIX.'module_gallery_settings');
        while (!$objResult->EOF) {
            $this->arrSettings[$objResult->fields['name']] = $objResult->fields['value'];
            $objResult->MoveNext();
        }
    }

    /**
    * Reads the act and selects the right action
    */
    function getPage()
    {
        if (empty($_GET['cmd'])) {
            $_GET['cmd'] = '';
        } else {
            $this->strCmd = '&amp;cmd='.intval($_GET['cmd']);
        }

        \JS::activate('shadowbox');

        if (isset($_GET['pId']) && !empty($_GET['pId'])) {
            if (isset($_POST['frmGalComAdd_PicId'])) {
                $this->addComment();
                \Cx\Core\Csrf\Controller\Csrf::header('location:'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery'.html_entity_decode($this->strCmd, ENT_QUOTES, CONTREXX_CHARSET).'&cid='.
                    intval($_POST['frmGalComAdd_GalId']).'&pId='.
                    intval($_POST['frmGalComAdd_PicId']));
                exit;
            }

            if (isset($_GET['mark'])) {
                $this->countVoting($_GET['pId'],$_GET['mark']);
                \Cx\Core\Csrf\Controller\Csrf::header('location:'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery'.html_entity_decode($this->strCmd, ENT_QUOTES, CONTREXX_CHARSET).'&cid='.
                    intval($_GET['cid']).'&pId='.intval($_GET['pId']));
                exit;
            }

            if ($this->arrSettings['enable_popups'] == "on" ) {
                $this->showPicture(intval($_GET['pId']));
            } else {
                $this->showPictureNoPop(intval($_GET['pId']));
            }
        } else {
            $_GET['cid'] = isset($_GET['cid']) ? intval($_GET['cid']) : intval($_GET['cmd']);
            $this->showCategoryOverview($_GET['cid']);
        }
        return $this->_objTpl->get();
    }


    /**
     * Show picture in [[CONTENT]] (no popup is used)
     *
     * @param integer $intPicId
     */
    function showPictureNoPop($intPicId)
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG, $_CORELANG;

        $arrPictures = array();
        $intPicId    = intval($intPicId);
// Never used
//        $intCatId    = intval($_GET['cid']);
        $this->_objTpl->setTemplate($this->pageContent);


        // we need to read the category id out of the database to prevent abusement
        $intCatId = $this->getCategoryId($intPicId);
        $categoryProtected = $this->categoryIsProtected($intCatId);
        if ($categoryProtected > 0) {
            if (!\Permission::checkAccess($categoryProtected, 'dynamic', true)) {
                    $link=base64_encode($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
                    \Cx\Core\Csrf\Controller\Csrf::header ("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&cmd=noaccess&redirect=".$link);
                    exit;
            }
        }

        // hide category list
        $this->_objTpl->hideBlock('galleryCategories');

        $boolComment = $this->categoryAllowsComments($intCatId);
        $boolVoting = $this->categoryAllowsVoting($intCatId);

        // get picture informations
        $objResult = $objDatabase->Execute(
            "SELECT id, path, link, size_show FROM ".DBPREFIX."module_gallery_pictures ".
            "WHERE id=$intPicId");

        $query = "SELECT p.name, p.desc FROM ".DBPREFIX."module_gallery_language_pics p ".
            "WHERE picture_id=$intPicId AND lang_id=$this->langId LIMIT 1";
        $objSubResult = $objDatabase->Execute($query);
// while? -> if!
        while (!$objResult->EOF) {
            $imageReso = getimagesize($this->strImagePath.$objResult->fields['path']);
            $strImagePath = $this->strImageWebPath.$objResult->fields['path'];
            $imageName = $objSubResult->fields['name'];
            $imageDesc = $objSubResult->fields['desc'];
            //show image size based on the settings of "Show image size"
            $showImageSize   = $this->arrSettings['show_image_size'] == 'on' && $objResult->fields['size_show'];
            $imageSize       = ($showImageSize) ? round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2) : '';
            $strImageWebPath = ASCMS_PROTOCOL .'://'.$_CONFIG['domainUrl'].CONTREXX_SCRIPT_PATH.'?section=Gallery'.$this->strCmd.'&amp;cid='.$intCatId.'&amp;pId='.$intPicId;
            $objResult->MoveNext();
        }

        // set requested page's meta data
        if ($imageDesc) {
            $metaDescription = $imageDesc;
        } else {
            $metaDescription = $imageName;
        }
        $page = \Cx\Core\Core\Controller\Cx::instanciate()->getPage();
        $page->setTitle($imageName);
        $page->setContentTitle($imageName);
        $page->setMetaTitle($imageName);
        $page->setMetadesc($metaDescription);

        // get pictures of the current category
        $objResult = $objDatabase->Execute(
            "SELECT id FROM ".DBPREFIX."module_gallery_pictures ".
            "WHERE status='1' AND validated='1' AND catid=$intCatId ".
            "ORDER BY sorting, id");
        while (!$objResult->EOF) {
            array_push($arrPictures,$objResult->fields['id']);
            $objResult->MoveNext();
        }

        // get next picture id
        if (array_key_exists(array_search($intPicId,$arrPictures)+1,$arrPictures)) {
            $intPicIdNext = $arrPictures[array_search($intPicId,$arrPictures)+1];
        } else {
            $intPicIdNext = $arrPictures[0];
        }

        // get previous picture id
        if (array_key_exists(array_search($intPicId,$arrPictures)-1,$arrPictures)) {
            $intPicIdPrevious = $arrPictures[array_search($intPicId,$arrPictures)-1];
        } else {
            $intPicIdPrevious = end($arrPictures);
        }

        // set language variables
        $this->_objTpl->setVariable(array(
            'TXT_GALLERY_PREVIOUS_IMAGE'        => $_ARRAYLANG['TXT_PREVIOUS_IMAGE'],
            'TXT_GALLERY_NEXT_IMAGE'            => $_ARRAYLANG['TXT_NEXT_IMAGE'],
            'TXT_GALLERY_BACK_OVERVIEW'         => $_ARRAYLANG['TXT_GALLERY_BACK_OVERVIEW'],
            'TXT_GALLERY_CURRENT_IMAGE'         => $_ARRAYLANG['TXT_GALLERY_CURRENT_IMAGE'],
        ));

        $intImageWidth  = '';
        $intImageHeigth = '';
        if ($this->arrSettings['image_width'] < $imageReso[0]) {
            $resizeFactor = $this->arrSettings['image_width'] / $imageReso[0];
            $intImageWidth = $imageReso[0] * $resizeFactor;
            $intImageHeigth = $imageReso[1] * $resizeFactor;

        }
        if (empty($imageDesc)) {
            $imageDesc = '-';
        }

        $strImageTitle = substr(strrchr($strImagePath, '/'), 1);
        // chop the file extension if the settings tell us to do so
        if ($this->arrSettings['show_ext'] == 'off') {
            $strImageTitle = substr($strImageTitle, 0, strrpos($strImageTitle, '.'));
        }

        if ($this->arrSettings['show_file_name'] == 'off') {
            $strImageTitle = "";
            $imageSize="";
            $kB="";
            $openBracket="";
            $closeBracket="";
            //substr($strImageTitle, 0, strrpos($strImageTitle, '.'));
        }
        else {
            $openBracket="(";
            $closeBracket=")";
            $kB=" kB";
        }

        // set variables
        $this->_objTpl->setVariable(array(
            'GALLERY_PICTURE_ID'        => $intPicId,
            'GALLERY_CATEGORY_ID'       => $intCatId,
            'GALLERY_IMAGE_TITLE'       => $strImageTitle,
            'GALLERY_IMAGE_PATH'        => $strImagePath,
            'GALLERY_IMAGE_PREVIOUS'    => '?section=Gallery'.$this->strCmd.'&amp;cid='.$intCatId.'&amp;pId='.$intPicIdPrevious,
            'GALLERY_IMAGE_NEXT'        => '?section=Gallery'.$this->strCmd.'&amp;cid='.$intCatId.'&amp;pId='.$intPicIdNext,
            'GALLERY_IMAGE_WIDTH'       => $intImageWidth,
            'GALLERY_IMAGE_HEIGHT'      => $intImageHeigth,
            'GALLERY_IMAGE_LINK'        => $strImageWebPath,
            'GALLERY_IMAGE_NAME'        => $imageName,
            'GALLERY_IMAGE_DESCRIPTION' => $imageDesc,
            'GALLERY_IMAGE_FILESIZE'    => ($showImageSize) ? $openBracket.$imageSize.$kB.$closeBracket : '',
        ));

        if ($this->arrSettings['header_type'] == 'hierarchy') {
            $this->_objTpl->setVariable(array(
                'GALLERY_CATEGORY_TREE'     => $this->getCategoryTree(),
                'TXT_GALLERY_CATEGORY_HINT' => $_ARRAYLANG['TXT_GALLERY_CATEGORY_HINT_HIERARCHY'],
            ));
        } else {
            $this->_objTpl->setVariable(array(
                'GALLERY_CATEGORY_TREE'     => $this->getSiblingList(),
                'TXT_GALLERY_CATEGORY_HINT' => $_ARRAYLANG['TXT_GALLERY_CATEGORY_HINT_FLAT'],
            ));
        }

        //voting
        if ($this->_objTpl->blockExists('votingTab')) {
            if ($this->arrSettings['show_voting'] == 'on' && $boolVoting) {
                $this->_objTpl->setVariable(array(
                    'TXT_VOTING_TITLE'        => $_ARRAYLANG['TXT_VOTING_TITLE'],
                    'TXT_VOTING_STATS_ACTUAL' => $_ARRAYLANG['TXT_VOTING_STATS_ACTUAL'],
                    'TXT_VOTING_STATS_WITH'   => $_ARRAYLANG['TXT_VOTING_STATS_WITH'],
                    'TXT_VOTING_STATS_VOTES'  => $_ARRAYLANG['TXT_VOTING_STATS_VOTES'],
                ));
                if (isset($_COOKIE['Gallery_Voting_'.$intPicId])) {
                    $this->_objTpl->hideBlock('showVotingBar');
                    $this->_objTpl->setVariable(array(
                        'TXT_VOTING_ALREADY_VOTED'  => $_ARRAYLANG['TXT_VOTING_ALREADY_VOTED'],
                        'VOTING_ALREADY_VOTED_MARK' => intval($_COOKIE['Gallery_Voting_'.$intPicId])
                    ));
                } else {
                    $this->_objTpl->setVariable(array(
                        'TXT_VOTING_ALREADY_VOTED'  => '',
                        'VOTING_ALREADY_VOTED_MARK' => ''
                    ));
                    for ($i=1;$i<=10;$i++) {
                        $this->_objTpl->setVariable(array(
                            'VOTING_BAR_SRC'   => ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/voting/'.$i.'.gif',
                            'VOTING_BAR_ALT'   => $_ARRAYLANG['TXT_VOTING_RATE'].': '.$i,
                            'VOTING_BAR_MARK'  => $i,
                            'VOTING_BAR_CID'   => $intCatId,
                            'VOTING_BAR_PICID' => $intPicId
                        ));
                        $this->_objTpl->parse('showVotingBar');
                    }
                }

                $objResult = $objDatabase->Execute(
                    "SELECT mark FROM ".DBPREFIX."module_gallery_votes ".
                    "WHERE picid=$intPicId");
                if ($objResult->RecordCount() > 0) {
                    $intCount = 0;
                    $intMark  = 0;
                    while (!$objResult->EOF) {
                        $intCount++;
                        $intMark = $intMark + intval($objResult->fields['mark']);
                        $objResult->MoveNext();
                    }
                    $this->_objTpl->setVariable(array(
                        'VOTING_STATS_MARK'  => number_format(round($intMark / $intCount,1),1,'.','\''),
                        'VOTING_STATS_VOTES' => $intCount
                    ));
                } else {
                    $this->_objTpl->setVariable(array(
                        'VOTING_STATS_MARK'  => 0,
                        'VOTING_STATS_VOTES' => 0
                    ));
                }
            } else {
                $this->_objTpl->hideBlock('votingTab');
            }
        }

        // comments
        if ($this->arrSettings['show_comments'] == 'on' && $boolComment) {
            $objResult = $objDatabase->Execute(
                "SELECT date, name, email, www, comment FROM ".DBPREFIX."module_gallery_comments ".
                "WHERE picid=$intPicId ORDER BY date ASC");

            $this->_objTpl->setVariable(array(
                'TXT_COMMENTS_TITLE'        => $objResult->RecordCount().'&nbsp;'.$_ARRAYLANG['TXT_COMMENTS_TITLE'],
                'TXT_COMMENTS_ADD_TITLE'    => $_ARRAYLANG['TXT_COMMENTS_ADD_TITLE'],
                'TXT_COMMENTS_ADD_NAME'     => $_ARRAYLANG['TXT_COMMENTS_ADD_NAME'],
                'TXT_COMMENTS_ADD_EMAIL'    => $_ARRAYLANG['TXT_COMMENTS_ADD_EMAIL'],
                'TXT_COMMENTS_ADD_HOMEPAGE' => $_ARRAYLANG['TXT_COMMENTS_ADD_HOMEPAGE'],
                'TXT_COMMENTS_ADD_TEXT'     => $_ARRAYLANG['TXT_COMMENTS_ADD_TEXT'],
                'TXT_COMMENTS_ADD_SUBMIT'   => $_ARRAYLANG['TXT_COMMENTS_ADD_SUBMIT'],
            ));

//            $this->_objTpl->setVariable(array(
//                'TXT_COMMENTS_ADD_CAPTCHA'   => $_CORELANG['TXT_CORE_CAPTCHA'],
//                'GALLERY_COMMENTS_ADD_CAPTCHA_CODE'  => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
//            ));

            if ($objResult->RecordCount() == 0) { // no comments, hide the block
                $this->_objTpl->hideBlock('showComments');
            } else {
                $i=0;
                while (!$objResult->EOF) {
                    if ($i % 2 == 0) {
                        $intRowClass = '1';
                    } else {
                        $intRowClass = '2';
                    }

                    if ($objResult->fields['www'] != '') {
                        $strWWW = '<a href="'.$objResult->fields['www'].'"><img alt="'.$objResult->fields['www'].'" src="'.ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/www.gif" align="baseline" border="0" /></a>';
                    } else {
                        $strWWW = '<img src="'.ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/pixel.gif" width="16" height="16" alt="" align="baseline" border="0" />';
                    }
                    if ($objResult->fields['email'] != '') {
                        $strEmail = '<a href="mailto:'.$objResult->fields['email'].'"><img alt="'.$objResult->fields['email'].'" src="'.ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/email.gif" align="baseline" border="0" /></a>';
                    } else {
                        $strEmail = '<img src="'.ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/pixel.gif" width="16" height="16" alt="" align="baseline" border="0" />';
                    }
                    $this->_objTpl->setVariable(array(
                        'COMMENTS_NAME'     => html_entity_decode($objResult->fields['name']),
                        'COMMENTS_DATE'     => date($_ARRAYLANG['TXT_COMMENTS_DATEFORMAT'],$objResult->fields['date']),
                        'COMMENTS_WWW'      => $strWWW,
                        'COMMENTS_EMAIL'    => $strEmail,
                        'COMMENTS_TEXT'     => nl2br($objResult->fields['comment']),
                        'COMMENTS_ROWCLASS' => $intRowClass
                    ));

                    $this->_objTpl->parse('showComments');
                    $objResult->MoveNext();
                    $i++;
                }
            }
        } else {
            $this->_objTpl->hideBlock('commentTab');
        }

// Undefined
//        if($_CONFIG['corePagingLimit'] < $count) {
//          $this->_objTpl->setVariable("GALLERY_FRONTEND_PAGING", $paging);
//        }
        //$this->_objTpl->parse('galleryImage');
    }

    /**
    * Show the picture with the id $intPicId (with popup)
    *
    * @global    ADONewConnection
    * @global    array
    * @param     integer        $intPicId: The id of the picture which should be shown
    */
    function showPicture($intPicId)
    {
        global $objDatabase, $_ARRAYLANG;

        $arrPictures = array();
        $intPicId    = intval($intPicId);
// Never used
//        $intCatId    = intval($_GET['cid']);

        // we need to read the category id out of the database to prevent abusement
        $intCatId = $this->getCategoryId($intPicId);
        $categoryProtected = $this->categoryIsProtected($intCatId);
        if ($categoryProtected > 0) {
            if (!\Permission::checkAccess($categoryProtected, 'dynamic', true)) {
                    $link=base64_encode($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
                    \Cx\Core\Csrf\Controller\Csrf::header ("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&cmd=noaccess&redirect=".$link);
                    exit;
            }
        }

        // POPUP Code
        $objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Gallery/View/Template/Backend');
        $objTpl->loadTemplateFile('module_gallery_show_picture.html',true,true);

        // get category description
        $objResult = $objDatabase->Execute(
            "SELECT value FROM ".DBPREFIX."module_gallery_language ".
            "WHERE gallery_id=$intCatId AND lang_id=$this->langId ".
            "AND name='desc' LIMIT 1");
        $strCategoryComment = '';
        if ($objResult && $objResult->RecordCount()) {
            $strCategoryComment = $objResult->fields['value'];
        }

        $objResult = $objDatabase->Execute(
            "SELECT comment, voting ".
            "FROM ".DBPREFIX."module_gallery_categories ".
            "WHERE id=$intCatId");
        $boolComment = '';
        $boolVoting = '';
        if ($objResult && $objResult->RecordCount()) {
            $boolComment = $objResult->fields['comment'];
            $boolVoting = $objResult->fields['voting'];
        }

        // get picture informations
        $objResult = $objDatabase->Execute(
            "SELECT id, path, link, size_show ".
            "FROM ".DBPREFIX."module_gallery_pictures ".
            "WHERE id=$intPicId");
        $objSubResult = $objDatabase->Execute(
            "SELECT p.name, p.desc FROM ".DBPREFIX."module_gallery_language_pics p ".
            "WHERE picture_id=$intPicId AND lang_id=$this->langId LIMIT 1");
        while (!$objResult->EOF) {
            $imageReso = getimagesize($this->strImagePath.$objResult->fields['path']);
            $strImagePath = $this->strImageWebPath.$objResult->fields['path'];
            $imageName = $objSubResult->fields['name'];
            $imageDesc = $objSubResult->fields['desc'];
            //show image size based on the settings of "Show image size"
            $showImageSize = $this->arrSettings['show_image_size'] == 'on' && $objResult->fields['size_show'];
            $imageSize = ($showImageSize) ? round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2) : '';
            $strImageWebPath = ASCMS_PROTOCOL .'://'.$_SERVER['SERVER_NAME'].CONTREXX_SCRIPT_PATH.'?section=Gallery'.$this->strCmd.'&amp;cid='.$intCatId.'&amp;pId='.$intPicId;
            $objResult->MoveNext();
        }

        // set requested page's meta data
        if ($imageDesc) {
            $metaDescription = $imageDesc;
        } else {
            $metaDescription = $imageName;
        }
        $page = \Cx\Core\Core\Controller\Cx::instanciate()->getPage();
        $page->setTitle($imageName);
        $page->setContentTitle($imageName);
        $page->setMetaTitle($imageName);
        $page->setMetadesc($metaDescription);

        // get pictures of the current category
        $objResult = $objDatabase->Execute(
            "SELECT id FROM ".DBPREFIX."module_gallery_pictures ".
            "WHERE status='1' AND validated='1' AND catid=$intCatId ".
            "ORDER BY sorting, id");
        if ($objResult && $objResult->RecordCount()) {
            while (!$objResult->EOF) {
                array_push($arrPictures,$objResult->fields['id']);
                $objResult->MoveNext();
            }
        }

        // get next picture id
        if (array_key_exists(array_search($intPicId,$arrPictures)+1,$arrPictures)) {
            $intPicIdNext = $arrPictures[array_search($intPicId,$arrPictures)+1];
        } else {
            $intPicIdNext = $arrPictures[0];
        }

        // get previous picture id
        if (array_key_exists(array_search($intPicId,$arrPictures)-1,$arrPictures)) {
            $intPicIdPrevious = $arrPictures[array_search($intPicId,$arrPictures)-1];
        } else {
            $intPicIdPrevious = end($arrPictures);
        }

        $strImageTitle = substr(strrchr($strImagePath, '/'), 1);
        // chop the file extension if the settings tell us to do so
        if ($this->arrSettings['show_ext'] == 'off') {
            $strImageTitle = substr($strImageTitle, 0, strrpos($strImageTitle, '.'));
        }

        // set language variables
        $objTpl->setVariable(array(
            'TXT_CLOSE_WINDOW'    => $_ARRAYLANG['TXT_CLOSE_WINDOW'],
            'TXT_ZOOM_OUT'        => $_ARRAYLANG['TXT_ZOOM_OUT'],
            'TXT_ZOOM_IN'         => $_ARRAYLANG['TXT_ZOOM_IN'],
            'TXT_CHANGE_BG_COLOR' => $_ARRAYLANG['TXT_CHANGE_BG_COLOR'],
            'TXT_PRINT'           => $_ARRAYLANG['TXT_PRINT'],
            'TXT_PREVIOUS_IMAGE'  => $_ARRAYLANG['TXT_PREVIOUS_IMAGE'],
            'TXT_NEXT_IMAGE'      => $_ARRAYLANG['TXT_NEXT_IMAGE'],
            'TXT_USER_DEFINED'    => $_ARRAYLANG['TXT_USER_DEFINED']
        ));

        $imageSize     = ($showImageSize) ? $_ARRAYLANG['TXT_FILESIZE'].': '.$imageSize.' kB<br />' : '';
        // set variables
        $objTpl->setVariable(array(
            'CONTREXX_CHARSET'        => CONTREXX_CHARSET,
            'GALLERY_WINDOW_WIDTH'  => $imageReso[0] < 420 ? 500 : $imageReso[0]+80,
            'GALLERY_WINDOW_HEIGHT' => $imageReso[1]+120,
            'GALLERY_PICTURE_ID'    => $intPicId,
            'GALLERY_CATEGORY_ID'   => $intCatId,
            'GALLERY_TITLE'         => $strCategoryComment,
            'IMAGE_THIS'            => $strImagePath,
            'IMAGE_PREVIOUS'        => '?section=Gallery'.$this->strCmd.'&amp;cid='.$intCatId.'&amp;pId='.$intPicIdPrevious,
            'IMAGE_NEXT'            => '?section=Gallery'.$this->strCmd.'&amp;cid='.$intCatId.'&amp;pId='.$intPicIdNext,
            'IMAGE_WIDTH'           => $imageReso[0],
            'IMAGE_HEIGHT'          => $imageReso[1],
            'IMAGE_LINK'            => $strImageWebPath,
            'IMAGE_NAME'            => $strImageTitle, //$imageName,
            'IMAGE_DESCRIPTION'     => $_ARRAYLANG['TXT_IMAGE_NAME'].': '.$imageName.'<br />'
                                       . $imageSize
                                       . $_ARRAYLANG['TXT_RESOLUTION'].': '.$imageReso[0].'x'.$imageReso[1].' Pixel',
            'IMAGE_DESC'            => (!empty($imageDesc)) ? $imageDesc.'<br /><br />' : '',
        ));

        $objTpl->setGlobalVariable('CONTREXX_DIRECTORY_INDEX', CONTREXX_DIRECTORY_INDEX);

        //voting
        if ($objTpl->blockExists('votingTab')) {
            if ($this->arrSettings['show_voting'] == 'on'    && $boolVoting) {
                $objTpl->setVariable(array(
                    'TXT_VOTING_TITLE'        => $_ARRAYLANG['TXT_VOTING_TITLE'],
                    'TXT_VOTING_STATS_ACTUAL' => $_ARRAYLANG['TXT_VOTING_STATS_ACTUAL'],
                    'TXT_VOTING_STATS_WITH'   => $_ARRAYLANG['TXT_VOTING_STATS_WITH'],
                    'TXT_VOTING_STATS_VOTES'  => $_ARRAYLANG['TXT_VOTING_STATS_VOTES'],
                ));
                if (isset($_COOKIE["Gallery_Voting_$intPicId"])) {
                    $objTpl->hideBlock('showVotingBar');

                    $objTpl->setVariable(array(
                        'TXT_VOTING_ALREADY_VOTED'  => $_ARRAYLANG['TXT_VOTING_ALREADY_VOTED'],
                        'VOTING_ALREADY_VOTED_MARK' => intval($_COOKIE['Gallery_Voting_'.$intPicId])
                    ));
                } else {
                    $objTpl->setVariable(array(
                        'TXT_VOTING_ALREADY_VOTED'  => '',
                        'VOTING_ALREADY_VOTED_MARK' => ''
                    ));
                    for ($i=1;$i<=10;$i++) {
                            $objTpl->setVariable(array(
                                'VOTING_BAR_SRC'   => ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/voting/'.$i.'.gif',
                                'VOTING_BAR_ALT'   => $_ARRAYLANG['TXT_VOTING_RATE'].': '.$i,
                                'VOTING_BAR_MARK'  => $i,
                                'VOTING_BAR_CID'   => $intCatId,
                                'VOTING_BAR_PICID' => $intPicId
                            ));
                        $objTpl->parse('showVotingBar');
                    }
                }

                $objResult = $objDatabase->Execute(
                    "SELECT mark FROM ".DBPREFIX."module_gallery_votes ".
                    "WHERE picid=$intPicId");
                if ($objResult->RecordCount() > 0) {
                    $intCount = 0;
                    $intMark = 0;
                    while (!$objResult->EOF) {
                        $intCount++;
                        $intMark = $intMark + intval($objResult->fields['mark']);
                        $objResult->MoveNext();
                    }
                    $objTpl->setVariable(array(
                        'VOTING_STATS_MARK'  => number_format(round($intMark / $intCount,1),1,'.','\''),
                        'VOTING_STATS_VOTES' => $intCount
                    ));
                } else {
                    $objTpl->setVariable(array(
                        'VOTING_STATS_MARK'  => 0,
                        'VOTING_STATS_VOTES' => 0
                    ));
                }
            } else {
                $objTpl->hideBlock('votingTab');
            }
        }
        //comments
        if ($this->arrSettings['show_comments'] == 'on' && $boolComment) {
            $objResult = $objDatabase->Execute(
                "SELECT date, name, email, www, comment FROM ".DBPREFIX."module_gallery_comments ".
                "WHERE picid=$intPicId ORDER BY date ASC");

            $objTpl->setVariable(array(
                'TXT_COMMENTS_TITLE'        => $objResult->RecordCount().'&nbsp;'.$_ARRAYLANG['TXT_COMMENTS_TITLE'],
                'TXT_COMMENTS_ADD_TITLE'    => $_ARRAYLANG['TXT_COMMENTS_ADD_TITLE'],
                'TXT_COMMENTS_ADD_NAME'     => $_ARRAYLANG['TXT_COMMENTS_ADD_NAME'],
                'TXT_COMMENTS_ADD_EMAIL'    => $_ARRAYLANG['TXT_COMMENTS_ADD_EMAIL'],
                'TXT_COMMENTS_ADD_HOMEPAGE' => $_ARRAYLANG['TXT_COMMENTS_ADD_HOMEPAGE'],
                'TXT_COMMENTS_ADD_TEXT'     => $_ARRAYLANG['TXT_COMMENTS_ADD_TEXT'],
                'TXT_COMMENTS_ADD_SUBMIT'   => $_ARRAYLANG['TXT_COMMENTS_ADD_SUBMIT'],
            ));

            if ($objResult->RecordCount() == 0) { // no comments, hide the block
                $objTpl->hideBlock('showComments');
            } else {
                $i=0;
                while (!$objResult->EOF) {
                    if ($i % 2 == 0) {
                        $intRowClass = '1';
                    } else {
                        $intRowClass = '2';
                    }

                    if ($objResult->fields['www'] != '') {
                        $strWWW = '<a href="'.$objResult->fields['www'].'"><img alt="'.$objResult->fields['www'].'" src="'.ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/www.gif" align="baseline" border="0" /></a>';
                    } else {
                        $strWWW = '<img src="'.ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/pixel.gif" width="16" height="16" alt="" align="baseline" border="0" />';
                    }
                    if ($objResult->fields['email'] != '') {
                        $strEmail = '<a href="mailto:'.$objResult->fields['email'].'"><img alt="'.$objResult->fields['email'].'" src="'.ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/email.gif" align="baseline" border="0" /></a>';
                    } else {
                        $strEmail = '<img src="'.ASCMS_MODULE_WEB_PATH.'/Gallery/View/Media/pixel.gif" width="16" height="16" alt="" align="baseline" border="0" />';
                    }
                    $objTpl->setVariable(array(
                        'COMMENTS_NAME'     => html_entity_decode($objResult->fields['name']),
                        'COMMENTS_DATE'     => date($_ARRAYLANG['TXT_COMMENTS_DATEFORMAT'],$objResult->fields['date']),
                        'COMMENTS_WWW'      => $strWWW,
                        'COMMENTS_EMAIL'    => $strEmail,
                        'COMMENTS_TEXT'     => nl2br($objResult->fields['comment']),
                        'COMMENTS_ROWCLASS' => $intRowClass
                    ));

                    $objTpl->parse('showComments');
                    $objResult->MoveNext();
                    $i++;
                }
            }
        } else {
            $objTpl->hideBlock('commentTab');
        }
        $objTpl->show();
        die;
    }

    /**
     * Shows the Category-Tree
     *
     * @global  array
     * @global  ADONewConnection
     * @return  string                      The category tree
     */
    function getCategoryTree()
    {
        global $_ARRAYLANG, $objDatabase;

        $strOutput = '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery" target="_self">'.$_ARRAYLANG['TXT_GALLERY'].'</a>';

        if (isset($_GET['cid'])) {
            $intCatId = intval($_GET['cid']);

            $objResult = $objDatabase->Execute(
                "SELECT value FROM ".DBPREFIX."module_gallery_language ".
                "WHERE gallery_id=$intCatId AND lang_id=$this->langId ".
                "AND name='name' LIMIT 1");
            $strCategory1 = $objResult->fields['value'];

            $objResult = $objDatabase->Execute(
                "SELECT pid FROM ".DBPREFIX."module_gallery_categories WHERE id=$intCatId");

            if ($objResult->fields['pid'] != 0) {
                $intParentId = $objResult->fields['pid'];
                $objResult = $objDatabase->Execute(
                    "SELECT value FROM ".DBPREFIX."module_gallery_language ".
                    "WHERE gallery_id=$intParentId AND lang_id=$this->langId ".
                    "AND name='name' LIMIT 1");
                $strCategory2 = $objResult->fields['value'];
            }

            if (isset($strCategory2)) { // this is a subcategory
                $strOutput .= ' / <a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery&amp;cid='.$intParentId.'" title="'.$strCategory2.'" target="_self">'.$strCategory2.'</a>';
                $strOutput .= ' / <a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery&amp;cid='.$intCatId.'" title="'.$strCategory1.'" target="_self" rel="nofollow">'.$strCategory1.'</a>';
            } else {
                $strOutput .= ' / <a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery&amp;cid='.$intCatId.'" title="'.$strCategory1.'" target="_self" rel="nofollow">'.$strCategory1.'</a>';
            }
        }
        return $strOutput;
    }

    /**
     * Not unlike {@link getCategoryTree()}, but instead of a tree, this returns
     * a list of siblings of the current gallery
     */
    function getSiblingList()
    {
        global $objDatabase;

        if (isset($_GET['cid'])) {
            $intCatId = intval($_GET['cid']);
            $objResult = $objDatabase->Execute(
                "SELECT pid FROM ".DBPREFIX."module_gallery_categories ".
                "WHERE id=$intCatId");
            if ($objResult) {
                $intParentId = intval($objResult->fields['pid']);
                $query = "SELECT id, value FROM ".DBPREFIX."module_gallery_categories ".
                    "INNER JOIN ".DBPREFIX."module_gallery_language ON id=gallery_id ".
                    "WHERE lang_id=$this->langId AND name='name' AND pid=$intParentId";
                $objResult = $objDatabase->Execute($query);
                if ($objResult) {
                    $strOutput = '| ';
                    do {
                        $strOutput .= "<a href='".CONTREXX_DIRECTORY_INDEX."?section=Gallery&amp;cid=".
                            $objResult->fields['id'].
                            "' title='".$objResult->fields['value'].
                            "' target='_self'>".$objResult->fields['value']."</a> | ";
                    } while ($objResult->MoveNext());
                    return $strOutput;
                }
            }
        }
        return '';
    }


    /**
     * Shows the Overview of categories
     *
     * @global  ADONewConnection
     * @global  array
     * @global  array
     * @param   var     $intParentId
     */
    function showCategoryOverview($intParentId=0)
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG, $_CORELANG;

        $intParentId = intval($intParentId);

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        // load source code if cmd value is integer
        if ($this->_objTpl->placeholderExists('APPLICATION_DATA')) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('Gallery');
            // load source code
            $applicationTemplate = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $this->_objTpl->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }

        $categoryProtected = $this->categoryIsProtected($intParentId);
        if ($categoryProtected > 0) {
            if (!\Permission::checkAccess($categoryProtected, 'dynamic', true)) {
                    $link=base64_encode($_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
                    \Cx\Core\Csrf\Controller\Csrf::header ("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&cmd=noaccess&redirect=".$link);
                    exit;
            }
        }

        // hide image detail block
        // $this->_objTpl->hideBlock('galleryImage');

        if ($this->arrSettings['header_type'] == 'hierarchy') {
            $this->_objTpl->setVariable(array(
                'GALLERY_CATEGORY_TREE'     => $this->getCategoryTree(),
                'TXT_GALLERY_CATEGORY_HINT' => $_ARRAYLANG['TXT_GALLERY_CATEGORY_HINT_HIERARCHY'],
            ));
        } else {
            $this->_objTpl->setVariable(array(
                'GALLERY_CATEGORY_TREE'     => $this->getSiblingList(),
                'TXT_GALLERY_CATEGORY_HINT' => $_ARRAYLANG['TXT_GALLERY_CATEGORY_HINT_FLAT'],
            ));
        }

        $objResult = $objDatabase->Execute(
            "SELECT id, catid, path FROM ".DBPREFIX."module_gallery_pictures ".
            "ORDER BY catimg ASC, sorting ASC, id ASC");

        $showImageSizeOverview   = $this->arrSettings['show_image_size'] == 'on';
        while (!$objResult->EOF) {
            $arrImageSizes[$objResult->fields['catid']][$objResult->fields['id']] = ($showImageSizeOverview) ? round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2) : '';
            $arrstrImagePaths[$objResult->fields['catid']][$objResult->fields['id']] = $this->strThumbnailWebPath.$objResult->fields['path'];
            $objResult->MoveNext();
        }

        if (isset($arrImageSizes) && isset($arrstrImagePaths)) {
            foreach ($arrImageSizes as $keyCat => $valueCat) {
                $arrCategorySizes[$keyCat] = 0;
                foreach ($valueCat as $valueImageSize) {
                    $arrCategorySizes[$keyCat] = $arrCategorySizes[$keyCat] + $valueImageSize;
                }
            }
            foreach ($arrstrImagePaths as $keyCat => $valueCat) {
                $arrCategoryImages[$keyCat] = 0;
                $arrCategoryImageCounter[$keyCat] = 0;
                foreach ($valueCat as $valuestrImagePath) {
                    $arrCategoryImages[$keyCat]    = $valuestrImagePath;
                    $arrCategoryImageCounter[$keyCat] = $arrCategoryImageCounter[$keyCat] + 1;
                }
            }
        }
        //$arrCategorySizes            ->        Sizes of all Categories
        //$arrCategoryImages        ->        The First Picture of each category
        //$arrCategoryImageCounter    ->        Counts all images in one group

        //begin category-paging
        $intPos = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $objResult = $objDatabase->Execute('SELECT    count(id) AS countValue
                                            FROM     '.DBPREFIX.'module_gallery_categories
                                            WHERE     pid='.$intParentId.' AND
                                                    status="1"
                                        ');
        $this->_objTpl->setVariable(array(
            'GALLERY_CATEGORY_PAGING'     => getPaging($objResult->fields['countValue'], $intPos, '&cid='.$intParentId.$this->strCmd, '<b>'.$_ARRAYLANG['TXT_GALLERY'].'</b>',false,intval($_CONFIG['corePagingLimit']))
            ));
        //end category-paging

        $objResult = $objDatabase->SelectLimit('SELECT         *
                                                FROM         '.DBPREFIX.'module_gallery_categories
                                                WHERE         pid='.$intParentId.' AND
                                                            status="1"
                                                ORDER BY    sorting ASC',
                                                intval($_CONFIG['corePagingLimit']),
                                                $intPos
                                            );

        if ($objResult->RecordCount() == 0) {

            // no categories in the database, hide the output
            //$this->_objTpl->hideBlock('galleryCategoryList');
        } else {
            $i = 1;
            while (!$objResult->EOF) {
                $objSubResult = $objDatabase->Execute(
                    "SELECT name, value FROM ".DBPREFIX."module_gallery_language ".
                    "WHERE gallery_id=".$objResult->fields['id']." AND ".
                    "lang_id=".intval($this->langId)." ORDER BY name ASC");
                unset($arrCategoryLang);
                while (!$objSubResult->EOF) {
                    $arrCategoryLang[$objSubResult->fields['name']] = $objSubResult->fields['value'];
                    $objSubResult->MoveNext();
                }

                if (empty($arrCategoryImages[$objResult->fields['id']])) {
                    // no pictures in this gallery, show the empty-image
                    $strName     = $arrCategoryLang['name'];
                    $strDesc    = $arrCategoryLang['desc'];
                    $strImage     = '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery&amp;cid='.$objResult->fields['id'].$this->strCmd.'" target="_self">';
                    $strImage     .= '<img border="0" alt="'.$arrCategoryLang['name'].'" src="modules/Gallery/View/Media/no_images.gif" /></a>';
                    $strInfo     = $_ARRAYLANG['TXT_IMAGE_COUNT'].': 0';
                    $strInfo    .= $showImageSizeOverview ? '<br />'.$_CORELANG['TXT_SIZE'].': 0kB' : '';
                } else {
                    $strName    = $arrCategoryLang['name'];
                    $strDesc    = $arrCategoryLang['desc'];
                    $strImage     = '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery&amp;cid='.$objResult->fields['id'].$this->strCmd.'" target="_self">';
                    $strImage     .= '<img border="0" alt="'.$arrCategoryLang['name'].'" src="'.$arrCategoryImages[$objResult->fields['id']].'" /></a>';
                    $strInfo     = $_ARRAYLANG['TXT_IMAGE_COUNT'].': '.$arrCategoryImageCounter[$objResult->fields['id']];
                    $strInfo    .= $showImageSizeOverview ? '<br />'.$_CORELANG['TXT_SIZE'].': '.$arrCategorySizes[$objResult->fields['id']].'kB' : '';
                }

                $this->_objTpl->setVariable(array(
                    'GALLERY_STYLE'                => ($i % 2)+1,
                    'GALLERY_CATEGORY_NAME'        => $strName,
                    'GALLERY_CATEGORY_IMAGE'       => $strImage,
                    'GALLERY_CATEGORY_INFO'        => $strInfo,
                    'GALLERY_CATEGORY_DESCRIPTION' => nl2br($strDesc)
                ));
                $this->_objTpl->parse('galleryCategoryList');
                $i++;

                $objResult->MoveNext();
            }
        }

        //images
        $this->_objTpl->setVariable(array(
            'GALLERY_JAVASCRIPT'    =>    $this->getJavascript()
            ));

        $strCategoryComment = '';

        // set requested page's meta data based on requested category
        if ($intParentId) {
            // name of requested category
            $objResult = $objDatabase->SelectLimit(
                "SELECT value FROM ".DBPREFIX."module_gallery_language ".
                "WHERE gallery_id=$intParentId AND lang_id=$this->langId AND name='name'", 1);
            $name = $objResult->fields['value'];

            // description of requested category
            $objResult = $objDatabase->SelectLimit(
                "SELECT value FROM ".DBPREFIX."module_gallery_language ".
                "WHERE gallery_id=$intParentId AND lang_id=$this->langId AND name='desc'", 1);
            $description = $objResult->fields['value'];
            $strCategoryComment = nl2br($description);

            if ($description) {
                $metaDescription = $description;
            } else {
                $metaDescription = $name;
            }

            // only overwrite requested page's meta data if the requested
            // category does have a name or description set
            $page = \Cx\Core\Core\Controller\Cx::instanciate()->getPage();
            if (!empty($name)) {
                $page->setTitle($name);
                $page->setContentTitle($name);
                $page->setMetaTitle($name);
            }
            if (!empty($metaDescription)) {
                $page->setMetadesc($metaDescription);
            }
        }

        $objResult = $objDatabase->Execute(
            "SELECT comment,voting FROM ".DBPREFIX."module_gallery_categories ".
            "WHERE id=".intval($intParentId));
        $boolComment = $objResult->fields['comment'];
        $boolVoting = $objResult->fields['voting'];

        // paging
        $intPos = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $objResult = $objDatabase->Execute(
            "SELECT id, path, link, size_show FROM ".DBPREFIX."module_gallery_pictures ".
            "WHERE status='1' AND validated='1' AND catid=$intParentId ".
            "ORDER BY sorting");
        $intCount = $objResult->RecordCount();
        $this->_objTpl->setVariable(array(
            'GALLERY_PAGING'     => getPaging($intCount, $intPos, '&cid='.$intParentId.$this->strCmd, '<b>'.$_ARRAYLANG['TXT_IMAGES'].'</b>', false, intval($this->arrSettings["paging"]))
        ));
        // end paging

        $objResult = $objDatabase->SelectLimit(
            "SELECT id, path, link, size_show FROM ".DBPREFIX."module_gallery_pictures ".
            "WHERE status='1' AND validated='1' AND catid=$intParentId ".
            "ORDER BY sorting", intval($this->arrSettings["paging"]), $intPos);
        if ($objResult->RecordCount() == 0) {
            // No images in the category
            if (empty($strCategoryComment)) {
                $this->_objTpl->hideBlock('galleryImageBlock');
            } else {
                $this->_objTpl->setVariable(array('GALLERY_CATEGORY_COMMENT' =>    $strCategoryComment));
            }
        } else {
            $this->_objTpl->setVariable(array('GALLERY_CATEGORY_COMMENT' =>    $strCategoryComment));
            $intFillLastRow = 1;
            while (!$objResult->EOF) {
                $imageVotingOutput = '';
                $imageCommentOutput = '';
                $objSubResult = $objDatabase->Execute(
                    "SELECT p.name, p.desc FROM ".DBPREFIX."module_gallery_language_pics p ".
                    "WHERE picture_id=".$objResult->fields['id']." AND lang_id=$this->langId LIMIT 1");

// Never used
//                $imageReso = getimagesize($this->strImagePath.$objResult->fields['path']);
                $strImagePath = $this->strImageWebPath.$objResult->fields['path'];
                $imageThumbPath = $this->strThumbnailWebPath.$objResult->fields['path'];
                $imageFileName = $this->arrSettings['show_file_name'] == 'on' ? $objResult->fields['path'] : '';
                $imageName = $this->arrSettings['show_names'] == 'on' ? $objSubResult->fields['name'] : '';
                $imageTitle = $this->arrSettings['show_names'] == 'on' ? $objSubResult->fields['name'] : ($this->arrSettings['show_file_name'] == 'on' ? $objResult->fields['path'] : '');
                $imageLinkName = $objSubResult->fields['desc'];
                $imageLink = $objResult->fields['link'];
                $showImageSize = $this->arrSettings['show_image_size'] == 'on' && $objResult->fields['size_show'];
                $imageFileSize = ($showImageSize) ? round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2) : '';
                $imageLinkOutput = '';
                $imageSizeOutput = '';
                $imageTitleTag = '';

                // chop the file extension if the settings tell us to do so
                if ($this->arrSettings['show_ext'] == 'off') {
                    $imageFileName = substr($imageFileName, 0, strrpos($imageFileName, '.'));
                }

                  if ($this->arrSettings['slide_show'] == 'slideshow') {
                      $optionValue="slideshowDelay:".$this->arrSettings['slide_show_seconds'];
                }
                else {
                    $optionValue="counterType:'skip',continuous:true,animSequence:'sync'";
                }
                //calculation starts here
                $numberOfChars="60";
                if($imageLinkName!="") {
                    if(strlen($imageLinkName) > $numberOfChars) {
                        $descriptionString="&nbsp;&nbsp;&nbsp;".substr($imageLinkName,0,$numberOfChars);
                        $descriptionString.=" ...";
                    }
                    else {
                        $descriptionString="&nbsp;&nbsp;&nbsp;".$imageLinkName;
                    }
                }
                else {
                    $descriptionString="";
                }
                //Ends here

                if ($this->arrSettings['show_names'] == 'on' || $this->arrSettings['show_file_name'] == 'on') {
                    $imageSizeOutput = $imageName;
                    $imageTitleTag   = $imageName;
                    if ($this->arrSettings['show_file_name'] == 'on' || $showImageSize) {
                        $imageData = array();
                        if ($this->arrSettings['show_file_name'] == 'on') {
                            if ($this->arrSettings['show_names'] == 'off') {
                                $imageSizeOutput .= $imageFileName;
                                $imageTitleTag   .= $imageFileName;
                            } else {
                                $imageData[] = $imageFileName;
                            }
                        }

                        if (!empty($imageData)) {
                            $imageTitleTag .= ' ('.join(' ', $imageData).')';
                        }
                        if ($showImageSize) {
                            // the size of the file has to be shown
                            $imageData[] = $imageFileSize.' kB';
                        }
                        if (!empty($imageData)) {
                            $imageSizeOutput .= ' ('.join(' ', $imageData).')<br />';
                        }
                    }
                }

                if ($this->arrSettings['enable_popups'] == "on") {

                        $strImageOutput =
                        '<a rel="shadowbox['.$intParentId.'];options={'.$optionValue.
                        '}"  title="'.$imageTitleTag.'" href="'.
                        $strImagePath.'"><img title="'.$imageTitleTag.'" src="'.
                        $imageThumbPath.'" alt="'.$imageTitleTag.'" /></a>';
                    /*
                    $strImageOutput =
                        '<a rel="shadowbox['.$intParentId.'];options={'.$optionValue.
                        '}" description="'.$imageLinkName.'" title="'.$titleLink.'" href="'.
                        $strImagePath.'"><img title="'.$imageName.'" src="'.
                        $imageThumbPath.'" alt="'.$imageName.'" /></a>';
                        */
                } else {
                    $strImageOutput =
                        '<a href="'.CONTREXX_DIRECTORY_INDEX.'?section=Gallery'.
                        $this->strCmd.'&amp;cid='.$intParentId.'&amp;pId='.
                        $objResult->fields['id'].'">'.'<img  title="'.
                        $imageTitleTag.'" src="'.$imageThumbPath.'"'.
                        'alt="'.$imageTitleTag.'" /></a>';
                }

                if ($this->arrSettings['show_comments'] == 'on' && $boolComment) {
                    $objSubResult = $objDatabase->Execute(
                        "SELECT id FROM ".DBPREFIX."module_gallery_comments ".
                        "WHERE picid=".$objResult->fields['id']);
                    if ($objSubResult->RecordCount() > 0) {
                        if ($objSubResult->RecordCount() == 1) {
                            $imageCommentOutput = '1 '.$_ARRAYLANG['TXT_COMMENTS_ADD_TEXT'].'<br />';
                        } else {
                            $imageCommentOutput = $objSubResult->RecordCount().' '.$_ARRAYLANG['TXT_COMMENTS_ADD_COMMENTS'].'<br />';
                        }
                    }
                }

                if ($this->arrSettings['show_voting'] == 'on' && $boolVoting) {
                    $objSubResult = $objDatabase->Execute(
                        "SELECT mark FROM ".DBPREFIX."module_gallery_votes ".
                        "WHERE picid=".$objResult->fields["id"]);
                    if ($objSubResult->RecordCount() > 0) {
                        $intMark = 0;
                        while (!$objSubResult->EOF) {
                            $intMark = $intMark + $objSubResult->fields['mark'];
                            $objSubResult->MoveNext();
                        }
                        $imageVotingOutput = $_ARRAYLANG['TXT_VOTING_SCORE'].'&nbsp;&Oslash;'.number_format(round($intMark / $objSubResult->RecordCount(),1),1,'.','\'').'<br />';
                    }
                }

                if (!empty($imageLinkName)) {
                    if (!empty($imageLink)) {
                        $imageLinkOutput = '<a href="'.$imageLink.'" target="_blank">'.$imageLinkName.'</a>';
                    } else {
                        $imageLinkOutput = $imageLinkName;
                    }
                } else {
                    if (!empty($imageLink)) {
                        $imageLinkOutput = '<a href="'.$imageLink.'" target="_blank">'.$imageLink.'</a>';
                    }
                }

                $this->_objTpl->setVariable(array(
                    'GALLERY_IMAGE_LINK'.$intFillLastRow => $imageSizeOutput.$imageCommentOutput.$imageVotingOutput.$imageLinkOutput,
                    'GALLERY_IMAGE'.$intFillLastRow      => $strImageOutput
                    ));

                if ($intFillLastRow == 3) {
                    // Parse the data after every third image
                    $this->_objTpl->parse('galleryShowImages');
                    $intFillLastRow = 1;
                } else {
                    $intFillLastRow++;
                }
                $objResult->MoveNext();
            }
            if ($intFillLastRow == 2) {
                $this->_objTpl->setVariable(array(
                    'GALLERY_IMAGE'.$intFillLastRow      => '',
                    'GALLERY_IMAGE_LINK'.$intFillLastRow => ''
                ));
                $intFillLastRow++;
            }
            if ($intFillLastRow == 3) {
                $this->_objTpl->setVariable(array(
                    'GALLERY_IMAGE'.$intFillLastRow      => '',
                    'GALLERY_IMAGE_LINK'.$intFillLastRow => ''
                ));
                $this->_objTpl->parse('galleryShowImages');
            }
        }

        $this->_objTpl->parse('galleryCategories');
    }

    /**
     * Check category authorisation
     *
     * Check if the user is permitted to access the
     * current category
     * @param unknown_type $id
     * @return unknown
     */
    function checkAuth($id)
    {
        global $objDatabase;

        if ($id == 0) {
            return true;
        }

        $objFWUser = \FWUser::getFWUserObject();
        if ($objFWUser->objUser->login() && $objFWUser->objUser->getAdminStatus()) {
            return true;
        }

        $query = "  SELECT protected
                    FROM ".DBPREFIX."module_gallery_categories
                    WHERE id = ".$id;
        $objRs = $objDatabase->Execute($query);
        if ($objRs === false) {
            return false;
        }
        if (intval($objRs->fields['protected']) === 1) {
            // it's a protected category. check auth
            if ($objFWUser->objUser->login()) {
                $userGroups = $objFWUser->objUser->getAssociatedGroupIds();
            } else {
                return false;
            }

            $query = "  SELECT groupid
                        FROM ".DBPREFIX."module_gallery_categories_access
                        WHERE catid = ".$id;
            $objRs = $objDatabase->Execute($query);
            if ($objRs === false) {
                return false;
            }
            while (!$objRs->EOF) {
                if (array_search($objRs->fields['groupid'], $userGroups) !== false) {
                    return true;
                }
                $objRs->MoveNext();
            }
        } else {
            return true;
        }
        return false;
    }


    /**
    * Writes the javascript-function into the template
    *
    */
    function getJavascript()
    {
        $javascript = <<<END
<script language="JavaScript" type="text/JavaScript">
function openWindow(theURL,winName,features) {
    galleryPopup = window.open(theURL,"gallery",features);
    galleryPopup.focus();
}
</script>
END;
        return $javascript;
    }


    /**
    * Add a new comment to database
    * @global     ADONewConnection
    * @global     Cache
    */
    function addComment()
    {
        global $objDatabase, $_ARRAYLANG;

        $intPicId    = intval($_POST['frmGalComAdd_PicId']);
        $categoryId = $this->getCategoryId($intPicId);
        $boolComment = $this->categoryAllowsComments($categoryId);

        if (
            checkForSpider() ||
            $this->arrSettings['show_comments'] == 'off' ||
            !$boolComment /*||
            !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()*/
        ) {
            return;
        }

        $strName     = htmlspecialchars(strip_tags($_POST['frmGalComAdd_Name']), ENT_QUOTES, CONTREXX_CHARSET);
        $strEmail    = $_POST['frmGalComAdd_Email'];
        $strWWW        = htmlspecialchars(strip_tags($_POST['frmGalComAdd_Homepage']), ENT_QUOTES, CONTREXX_CHARSET);
        $strComment = htmlspecialchars(strip_tags($_POST['frmGalComAdd_Text']), ENT_QUOTES, CONTREXX_CHARSET);

        if (!empty($strWWW) && $strWWW != 'http://') {
            if (substr($strWWW,0,7) != 'http://') {
                $strWWW = 'http://'.$strWWW;
            }
        } else {
            $strWWW = '';
        }

        if (!preg_match("/^.+@.+\\..+$/", $strEmail)) {
            $strEmail = '';
        } else {
            $strEmail = htmlspecialchars(strip_tags($strEmail), ENT_QUOTES, CONTREXX_CHARSET);
        }

        if ($intPicId != 0 &&
            !empty($strName) &&
            !empty($strComment))
        {
            $objDatabase->Execute(
                'INSERT INTO '.DBPREFIX.'module_gallery_comments '.
                'SET picid='.$intPicId.', date='.time().', ip="'.$_SERVER['REMOTE_ADDR'].'", '.
                'name="'.$strName.'", email="'.$strEmail.'", www="'.$strWWW.'", comment="'.$strComment.'"');
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteAllFiles();
        }
    }


    /**
    * Add a new voting to database
    * @global     ADONewConnection
    * @global     Cache
    * @param     integer        $intPicId: The picture with this id will be rated
    * @param     integer        $intMark: This mark will be set for the picture
    */
    function countVoting($intPicId,$intMark)
    {
        global $objDatabase;

        $intPicId = intval($intPicId);
        $categoryId = $this->getCategoryId($intPicId);
        $boolVoting = $this->categoryAllowsVoting($categoryId);

        if (
            checkForSpider() ||
            $this->arrSettings['show_voting'] == 'off' ||
            !$boolVoting
        ) {
            return;
        }

        $intMark = intval($intMark);
        $strMd5 = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);

        $intCookieTime = time()+7*24*60*60;
        $intVotingCheckTime = time()-(12*60*60);

        $objResult = $objDatabase->Execute(
            "SELECT id FROM ".DBPREFIX."module_gallery_votes ".
            "WHERE ip='".$_SERVER['REMOTE_ADDR']."' AND md5='".$strMd5.
            "' AND date > $intVotingCheckTime AND picid=$intPicId LIMIT 1");
        if ($objResult->RecordCount() == 1) {
            $boolIpCheck = false;
            setcookie('Gallery_Voting_'.$intPicId,$intMark,$intCookieTime, ASCMS_PATH_OFFSET.'/');
        } else {
            $boolIpCheck = true;
        }

        if ($intPicId != 0    &&
            $intMark >= 1     &&
            $intMark <= 10    &&
            $boolIpCheck    &&
            !isset($_COOKIE['Gallery_Voting_'.$intPicId])) {
            $objDatabase->Execute(
                "INSERT INTO ".DBPREFIX."module_gallery_votes ".
                "SET picid=$intPicId, date=".time().", ip='".$_SERVER['REMOTE_ADDR']."', ".
                "md5='".$strMd5."', mark=$intMark");
            setcookie('Gallery_Voting_'.$intPicId,$intMark,$intCookieTime, ASCMS_PATH_OFFSET.'/');
            $pageId = \Cx\Core\Core\Controller\Cx::instanciate()->getPage()->getId();
            $cacheManager = new \Cx\Core_Modules\Cache\Controller\CacheManager();
            $cacheManager->deleteSingleFile($pageId);
        }
    }

    /**
     * Are comments activated for the given category
     *
     * @param int $categoryId the category id
     * @return bool comments are activated
     */
    protected function categoryAllowsComments($categoryId) {
        global $objDatabase;
        $objResult = $objDatabase->Execute(
            "SELECT `comment` FROM `".DBPREFIX."module_gallery_categories` WHERE id=" . intval($categoryId)
        );
        return $objResult->fields['comment'];
    }

    /**
     * Are comments activated for the given category
     *
     * @param int $categoryId the category id
     * @return bool comments are activated
     */
    protected function categoryAllowsVoting($categoryId) {
        global $objDatabase;
        $objResult = $objDatabase->Execute(
            "SELECT `voting` FROM `".DBPREFIX."module_gallery_categories` WHERE id=" . intval($categoryId)
        );
        return $objResult->fields['voting'];
    }

    /**
     * Check if a category is marked 'protected'. Return the access id
     *
     * @param unknown_type $id
     * @return unknown
     */
    private function categoryIsProtected($id, $type="frontend")
    {
        if ($id == 0) {
            // top category
            return 0;
        }

        global $objDatabase;
        $query = "  SELECT  ".$type."Protected as protected,
                            ".$type."_access_id as access_id
                    FROM ".DBPREFIX."module_gallery_categories
                    WHERE id = ".$id;
        $objRs = $objDatabase->Execute($query);
        if ($objRs) {
            if ($objRs->fields['protected']) {
                return $objRs->fields['access_id'];
            } else {
                return 0;
            }
        } else {
            // the check didn't work. hide
            return 0;
        }

    }

    private function getCategoryId($id)
    {
        global $objDatabase;

        $query = "  SELECT catid FROM ".DBPREFIX."module_gallery_pictures
                    WHERE id = ".$id;
        $objRs = $objDatabase->Execute($query);
        return $objRs->fields['catid'];
    }
}

?>

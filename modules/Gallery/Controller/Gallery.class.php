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
        global $objDatabase, $_ARRAYLANG;

        $intPicId    = intval($intPicId);
        $this->_objTpl->setTemplate($this->pageContent);

        // we need to read the category id out of the database to prevent abusement
        $intCatId = $this->getCategoryId($intPicId);
        if (!$intCatId) {
            return;
        }
        $this->checkAccessToCategory($intCatId);

        // hide category list
        $this->_objTpl->hideBlock('galleryCategories');

        // get picture informations
        $picture = $objDatabase->Execute('SELECT
                                            p.`id`,
                                            p.`path`,
                                            p.`link`,
                                            p.`size_show`,
                                            pl.`name`,
                                            pl.`desc`
                                        FROM
                                            `'. DBPREFIX .'module_gallery_pictures` AS p
                                        LEFT JOIN
                                            `'. DBPREFIX .'module_gallery_language_pics` AS pl
                                        ON
                                            p.`id` = pl.`picture_id` AND pl.`lang_id` = '. $this->langId .'
                                        WHERE
                                            `id` = '. $intPicId);
        if (!$picture) {
            return;
        }
        $imagePath       = $this->strImagePath . $picture->fields['path'];
        $imageReso       = getimagesize($imagePath);
        $strImagePath    = $this->strImageWebPath.$picture->fields['path'];
        $imageName       = $picture->fields['name'];
        $imageDesc       = $picture->fields['desc'];
        //show image size based on the settings of "Show image size"
        $showImageSize   = $this->arrSettings['show_image_size'] == 'on' && $picture->fields['size_show'];
        $imageSize       = ($showImageSize) ? round(filesize($imagePath)/1024, 2) : '';

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

        list($previousPicture, $nextPicture) = $this->getPreviousAndNextPicture($intCatId, $intPicId);
        $intImageWidth  = '';
        $intImageHeigth = '';
        if ($this->arrSettings['image_width'] < $imageReso[0]) {
            $resizeFactor   = $this->arrSettings['image_width'] / $imageReso[0];
            $intImageWidth  = $imageReso[0] * $resizeFactor;
            $intImageHeigth = $imageReso[1] * $resizeFactor;
        }
        if (empty($imageDesc)) {
            $imageDesc = '-';
        }

        $strImageTitle = '';
        $showFileName  = $this->arrSettings['show_file_name'] == 'on';
        if ($showFileName) {
            $strImageTitle = substr(strrchr($strImagePath, '/'), 1);
            // chop the file extension if the settings tell us to do so
            if ($this->arrSettings['show_ext'] == 'off') {
                $strImageTitle = substr($strImageTitle, 0, strrpos($strImageTitle, '.'));
            }
        }

        $this->_objTpl->setVariable(array(
            'GALLERY_PICTURE_ID'        => $intPicId,
            'GALLERY_CATEGORY_ID'       => $intCatId,
            'GALLERY_IMAGE_TITLE'       => $strImageTitle,
            'GALLERY_IMAGE_PATH'        => $strImagePath,
            'GALLERY_IMAGE_PREVIOUS'    => $this->getPictureDetailLink($intCatId, $previousPicture),
            'GALLERY_IMAGE_NEXT'        => $this->getPictureDetailLink($intCatId, $nextPicture),
            'GALLERY_IMAGE_WIDTH'       => $intImageWidth,
            'GALLERY_IMAGE_HEIGHT'      => $intImageHeigth,
            'GALLERY_IMAGE_LINK'        => $this->getPictureDetailLink($intCatId, $intPicId),
            'GALLERY_IMAGE_NAME'        => $imageName,
            'GALLERY_IMAGE_DESCRIPTION' => $imageDesc,
            'GALLERY_IMAGE_FILESIZE'    => ($showImageSize && $showFileName) ? '('. $imageSize .' kB)' : '',

            'TXT_GALLERY_PREVIOUS_IMAGE' => $_ARRAYLANG['TXT_PREVIOUS_IMAGE'],
            'TXT_GALLERY_NEXT_IMAGE'     => $_ARRAYLANG['TXT_NEXT_IMAGE'],
            'TXT_GALLERY_BACK_OVERVIEW'  => $_ARRAYLANG['TXT_GALLERY_BACK_OVERVIEW'],
            'TXT_GALLERY_CURRENT_IMAGE'  => $_ARRAYLANG['TXT_GALLERY_CURRENT_IMAGE'],
        ));

        $this->parseCategoryTree($this->_objTpl);

        //voting
        $this->parsePictureVotingTab($this->_objTpl, $intCatId, $intPicId);

        // comments
        $this->parsePictureCommentsTab($this->_objTpl, $intCatId, $intPicId);
    }

    /**
    * Show the picture with the id $intPicId (with popup)
    *
    * @param     integer        $intPicId The id of the picture which should be shown
    */
    function showPicture($intPicId)
    {
        global $objDatabase, $_ARRAYLANG;

        $intPicId    = intval($intPicId);

        // we need to read the category id out of the database to prevent abusement
        $intCatId = $this->getCategoryId($intPicId);
        if (!$intCatId) {
            die();
        }
        $this->checkAccessToCategory($intCatId);

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

        // get picture informations
        $picture = $objDatabase->Execute('SELECT
                                            p.`id`,
                                            p.`path`,
                                            p.`link`,
                                            p.`size_show`,
                                            pl.`name`,
                                            pl.`desc`
                                        FROM
                                            `'. DBPREFIX .'module_gallery_pictures` AS p
                                        LEFT JOIN
                                            `'. DBPREFIX .'module_gallery_language_pics` AS pl
                                        ON
                                            p.`id` = pl.`picture_id` AND pl.`lang_id` = '. $this->langId .'
                                        WHERE
                                            `id` = '. $intPicId);
        if (!$picture) {
            die;
        }
        $imagePath       = $this->strImagePath . $picture->fields['path'];
        $imageReso       = getimagesize($imagePath);
        $strImagePath    = $this->strImageWebPath.$picture->fields['path'];
        $imageName       = $picture->fields['name'];
        $imageDesc       = $picture->fields['desc'];
        //show image size based on the settings of "Show image size"
        $showImageSize   = $this->arrSettings['show_image_size'] == 'on' && $picture->fields['size_show'];
        $imageSize       = ($showImageSize) ? round(filesize($imagePath)/1024, 2) : '';

        // get pictures of the current category
        list($previousPicture, $nextPicture) = $this->getPreviousAndNextPicture($intCatId, $intPicId);

        $strImageTitle = substr(strrchr($strImagePath, '/'), 1);
        // chop the file extension if the settings tell us to do so
        if ($this->arrSettings['show_ext'] == 'off') {
            $strImageTitle = substr($strImageTitle, 0, strrpos($strImageTitle, '.'));
        }

        // set variables
        $objTpl->setVariable(array(
            'CONTREXX_CHARSET'      => CONTREXX_CHARSET,
            'GALLERY_WINDOW_WIDTH'  => $imageReso[0] < 420 ? 500 : $imageReso[0]+80,
            'GALLERY_WINDOW_HEIGHT' => $imageReso[1]+120,
            'GALLERY_PICTURE_ID'    => $intPicId,
            'GALLERY_CATEGORY_ID'   => $intCatId,
            'GALLERY_TITLE'         => $strCategoryComment,
            'IMAGE_THIS'            => $strImagePath,
            'IMAGE_PREVIOUS'        => $this->getPictureDetailLink($intCatId, $previousPicture),
            'IMAGE_NEXT'            => $this->getPictureDetailLink($intCatId, $nextPicture),
            'IMAGE_WIDTH'           => $imageReso[0],
            'IMAGE_HEIGHT'          => $imageReso[1],
            'IMAGE_LINK'            => $this->getPictureDetailLink($intCatId, $intPicId),
            'IMAGE_NAME'            => $strImageTitle, //$imageName,
            'IMAGE_DESCRIPTION'     => $_ARRAYLANG['TXT_IMAGE_NAME'].': '.$imageName.'<br />'
                                       . (($showImageSize) ? $_ARRAYLANG['TXT_FILESIZE'].': '.$imageSize.' kB<br />' : '')
                                       . $_ARRAYLANG['TXT_RESOLUTION'].': '.$imageReso[0].'x'.$imageReso[1].' Pixel',
            'IMAGE_DESC'            => (!empty($imageDesc)) ? $imageDesc.'<br /><br />' : '',

            'TXT_CLOSE_WINDOW'      => $_ARRAYLANG['TXT_CLOSE_WINDOW'],
            'TXT_ZOOM_OUT'          => $_ARRAYLANG['TXT_ZOOM_OUT'],
            'TXT_ZOOM_IN'           => $_ARRAYLANG['TXT_ZOOM_IN'],
            'TXT_CHANGE_BG_COLOR'   => $_ARRAYLANG['TXT_CHANGE_BG_COLOR'],
            'TXT_PRINT'             => $_ARRAYLANG['TXT_PRINT'],
            'TXT_PREVIOUS_IMAGE'    => $_ARRAYLANG['TXT_PREVIOUS_IMAGE'],
            'TXT_NEXT_IMAGE'        => $_ARRAYLANG['TXT_NEXT_IMAGE'],
            'TXT_USER_DEFINED'      => $_ARRAYLANG['TXT_USER_DEFINED']
        ));
        
        $objTpl->setGlobalVariable('CONTREXX_DIRECTORY_INDEX', CONTREXX_DIRECTORY_INDEX);

        //voting
        $this->parsePictureVotingTab($objTpl, $intCatId, $intPicId);
        // comments
        $this->parsePictureCommentsTab($objTpl, $intCatId, $intPicId);

        $objTpl->show();
        die;
    }

    /**
     * Get Page title and description
     *
     * @return array returns pagetitle and metadescription as array
     */
    public function getPageAttributes()
    {
        global $objDatabase;

        $picId = 0;
        if (!empty($_GET['pId'])) {
            $picId = contrexx_input2int($_GET['pId']);
            $catId = $this->getCategoryId($picId);
        } else {
            $catId = contrexx_input2int($_GET['cmd']);
            if (isset($_GET['cid'])) {
                $catId = contrexx_input2int($_GET['cid']);
            }
        }

        if (!$catId) {
            return;
        }
        //check category protected or not
        $categoryProtected = $this->categoryIsProtected($catId);
        if (
            $categoryProtected &&
            !\Permission::checkAccess($categoryProtected, 'dynamic', true)
        ) {
            return;
        }

        if ($picId) {
            $picture = $objDatabase->Execute('
                SELECT
                        `name`,
                        `desc`
                    FROM ' . DBPREFIX . 'module_gallery_language_pics
                    WHERE
                        `picture_id`=' . $picId . ' AND
                        `lang_id`   =' . $this->langId . '
                    LIMIT 1
            ');
            $title = $picture->fields['name'];
            $desc  = $picture->fields['desc'];
        } else {
            $query = '
                SELECT
                        `value`
                    FROM ' . DBPREFIX . 'module_gallery_language
                    WHERE
                        `gallery_id`=' . $catId . ' AND
                        `lang_id`   =' . $this->langId . ' AND
                        `name`      =';
            // name of requested category
            $nameResult = $objDatabase->SelectLimit($query . '\'name\'', 1);
            $title = html_entity_decode($nameResult->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);

            // description of requested category
            $descResult = $objDatabase->SelectLimit($query . '\'desc\'', 1);
            $desc = html_entity_decode($descResult->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
        }
        //Consider title as description, if description is empty
        if (empty($desc)) {
            $desc = $title;
        }
        return array('title' => $title, 'desc' => $desc);
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
     * @param integer $intParentId
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

        $this->checkAccessToCategory($intParentId);
        $this->parseCategoryTree($this->_objTpl);

        $objResult = $objDatabase->Execute(
            "SELECT id, catid, path FROM ".DBPREFIX."module_gallery_pictures ".
            "ORDER BY catimg ASC, sorting ASC, id ASC");
        
        $showImageSizeOverview   = $this->arrSettings['show_image_size'] == 'on';
        while (!$objResult->EOF) {
            $arrImageSizes[$objResult->fields['catid']][$objResult->fields['id']] = ($showImageSizeOverview) ? round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2) : '';
            $arrstrImagePaths[$objResult->fields['catid']][$objResult->fields['id']] = $objResult->fields['path'];
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

                // set default category image
                $imageSrc =
                    $imageThumbnailSrc =
                        'modules/Gallery/View/Media/no_images.gif';
                $imageCount = 0;
                $size = 0;
                if (!empty($arrCategoryImages[$objResult->fields['id']])) {
                    $imageSrc =
                        $this->strImageWebPath .
                        $arrCategoryImages[$objResult->fields['id']];
                    $imageThumbnailSrc =
                        $this->strThumbnailWebPath .
                        $arrCategoryImages[$objResult->fields['id']];
                    $imageCount =
                        $arrCategoryImageCounter[$objResult->fields['id']];
                    $size = $arrCategorySizes[$objResult->fields['id']];
                }

                $image = new \Cx\Core\Html\Model\Entity\HtmlElement('img');
                $image->setAttributes(array(
                    'border'    => '0',
                    'alt'       => $arrCategoryLang['name'],
                    'src'       => $imageThumbnailSrc,
                ));

                $cmd = '';
                if (!empty($_GET['cmd'])) {
                    $cmd = intval($_GET['cmd']);
                }
                $url = \Cx\Core\Routing\Url::fromModuleAndCmd(
                    'Gallery',
                    $cmd,
                    '',
                    array('cid' => $objResult->fields['id'])
                );

                $categoryLink = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
                $categoryLink->setAttributes(array(
                    'href'    => $url,
                    'target'  => '_self',
                ));
                $categoryImageLink = clone $categoryLink;
                $categoryImageLink->addChild($image);
                $categoryTitle = new \Cx\Core\Html\Model\Entity\TextElement(
                    $arrCategoryLang['name']
                );
                $categoryLink->addChild($categoryTitle);

                $strInfo  = $_ARRAYLANG['TXT_IMAGE_COUNT'] . ': ' . $imageCount;
                if ($showImageSizeOverview) {
                    $strInfo .= '<br />' . $_CORELANG['TXT_SIZE'] .
                        ': ' . $size . 'kB';
                }

                $this->_objTpl->setVariable(array(
                    'GALLERY_STYLE'                => ($i % 2)+1,
                    'GALLERY_CATEGORY_NAME'        => $arrCategoryLang['name'],
                    'GALLERY_CATEGORY_IMAGE'       => $categoryImageLink,
                    'GALLERY_CATEGORY_IMAGE_PATH'  => $imageSrc,
                    'GALLERY_CATEGORY_IMAGE_THUMBNAIL_PATH'=> $imageThumbnailSrc,
                    'GALLERY_CATEGORY_INFO'        => $strInfo,
                    'GALLERY_CATEGORY_DESCRIPTION' => nl2br($arrCategoryLang['desc']),
                    'GALLERY_CATEGORY_LINK'        => $categoryLink,
                    'GALLERY_CATEGORY_LINK_SRC'    => $url,
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
            // description of requested category
            $objResult = $objDatabase->SelectLimit(
                "SELECT value FROM ".DBPREFIX."module_gallery_language ".
                "WHERE gallery_id=$intParentId AND lang_id=$this->langId AND name='desc'", 1);
            $description = $objResult->fields['value'];
            $strCategoryComment = nl2br($description);
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
        $this->_objTpl->setVariable('GALLERY_CATEGORY_COMMENT', $strCategoryComment);

        $objResult = $objDatabase->SelectLimit(
                        'SELECT
                            p.`id`,
                            p.`path`,
                            p.`link`,
                            p.`size_show`,
                            pl.`name`,
                            pl.`desc`
                        FROM
                            `'.DBPREFIX.'module_gallery_pictures` AS p
                        LEFT JOIN
                            `'. DBPREFIX .'module_gallery_language_pics` AS pl
                        ON
                            p.`id` = pl.`picture_id` AND pl.`lang_id` = '. $this->langId .'
                        WHERE
                            p.`status`="1" AND p.`validated`="1" AND p.`catid`=' .$intParentId .'
                        ORDER BY sorting',
                        intval($this->arrSettings["paging"]),
                        $intPos);
        if ($objResult->RecordCount() == 0) {
            // No images in the category
            if (empty($strCategoryComment)) {
                $this->_objTpl->hideBlock('galleryImageBlock');
            }
            return;
        }

        $placeholders = array(
            'GALLERY_IMAGE',
            'GALLERY_IMAGE_LINK',
            'GALLERY_IMAGE_ID',
            'GALLERY_IMAGE_TITLE',
            'GALLERY_IMAGE_PATH',
            'GALLERY_IMAGE_WIDTH',
            'GALLERY_IMAGE_HEIGHT',
            'GALLERY_IMAGE_DETAIL_LINK',
            'GALLERY_IMAGE_NAME',
            'GALLERY_IMAGE_DESCRIPTION',
            'GALLERY_IMAGE_FILESIZE'
        );
        $availableImagePlaceholders = array();
        for ($intPlaceholder = 1;$intPlaceholder <= 10;$intPlaceholder++) {
            foreach ($placeholders as $placeholder) {
                if ($this->_objTpl->placeholderExists($placeholder . $intPlaceholder)) {
                    $availableImagePlaceholders[] = $intPlaceholder;
                    continue 2;
                }
            }
        }
        if (!$availableImagePlaceholders) {
            return;
        }
        $intFillPlaceholder   = 1;
        $fillPlaceholderCount = count($availableImagePlaceholders);
        while (!$objResult->EOF) {
            $imageVotingOutput = '';
            $imageCommentOutput = '';

            $imageReso       = getimagesize($this->strImagePath.$objResult->fields['path']);
            $strImagePath    = $this->strImageWebPath.$objResult->fields['path'];
            $imageThumbPath  = $this->strThumbnailWebPath.$objResult->fields['path'];
            $imageFileName   = $this->arrSettings['show_file_name'] == 'on' ? $objResult->fields['path'] : '';
            $imageName       = $this->arrSettings['show_names'] == 'on' ? $objResult->fields['name'] : '';
            $imageLinkName   = $objResult->fields['desc'];
            $imageDesc       = !empty($objResult->fields['desc']) ? $objResult->fields['desc'] : '-';
            $imageLink       = $objResult->fields['link'];
            $showImageSize   = $this->arrSettings['show_image_size'] == 'on' && $objResult->fields['size_show'];
            $imageFileSize   = ($showImageSize) ? round(filesize($this->strImagePath.$objResult->fields['path'])/1024,2) : '';
            $imageSizeOutput = '';
            $imageTitleTag   = '';

            $intImageHeigth = $intImageWidth  = '';
            if ($this->arrSettings['image_width'] < $imageReso[0]) {
                $resizeFactor   = $this->arrSettings['image_width'] / $imageReso[0];
                $intImageWidth  = $imageReso[0] * $resizeFactor;
                $intImageHeigth = $imageReso[1] * $resizeFactor;
            }

            $strImageTitle = '';
            $showFileName  = $this->arrSettings['show_file_name'] == 'on';
            if ($showFileName) {
                $strImageTitle = substr(strrchr($strImagePath, '/'), 1);
                // chop the file extension if the settings tell us to do so
                if ($this->arrSettings['show_ext'] == 'off') {
                    $strImageTitle = substr($strImageTitle, 0, strrpos($strImageTitle, '.'));
                }
            }

            // chop the file extension if the settings tell us to do so
            if ($this->arrSettings['show_ext'] == 'off') {
                $imageFileName = substr($imageFileName, 0, strrpos($imageFileName, '.'));
            }

            if ($this->arrSettings['slide_show'] == 'slideshow') {
                $optionValue = 'slideshowDelay:'. $this->arrSettings['slide_show_seconds'];
            } else {
                $optionValue = 'counterType:\'skip\',continuous:true,animSequence:\'sync\'';
            }

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
            $imageLinkOutput = '';
            if (!empty($imageLink)) {
                $imageLinkOutput = '<a href="'.$imageLink.'" target="_blank">'. (!empty($imageLinkName) ? $imageLinkName : $imageLink) .'</a>';
            } elseif (!empty($imageLinkName)) {
                $imageLinkOutput = $imageLinkName;
            }

            if (!$availableImagePlaceholders) {
                $objResult->MoveNext();
                continue;
            }

            $placeholderNumber = $availableImagePlaceholders[$intFillPlaceholder - 1];
            $this->_objTpl->setVariable(array(
                'GALLERY_IMAGE_LINK'.$placeholderNumber         => $imageSizeOutput.$imageCommentOutput.$imageVotingOutput.$imageLinkOutput,
                'GALLERY_IMAGE'.$placeholderNumber              => $strImageOutput,

                'GALLERY_IMAGE_ID'.$placeholderNumber           => contrexx_raw2xhtml($objResult->fields['id']),
                'GALLERY_IMAGE_TITLE'.$placeholderNumber        => $strImageTitle,
                'GALLERY_IMAGE_PATH'.$placeholderNumber         => contrexx_raw2xhtml($strImagePath),
                'GALLERY_IMAGE_THUMBNAIL_PATH'.$placeholderNumber=>contrexx_raw2xhtml($imageThumbPath),
                'GALLERY_IMAGE_WIDTH'.$placeholderNumber        => $intImageWidth,
                'GALLERY_IMAGE_HEIGHT'.$placeholderNumber       => $intImageHeigth,
                'GALLERY_IMAGE_DETAIL_LINK'.$placeholderNumber  => $this->getPictureDetailLink($intParentId, $objResult->fields['id']),
                'GALLERY_IMAGE_NAME'.$placeholderNumber         => contrexx_raw2xhtml($objResult->fields['name']),
                'GALLERY_IMAGE_DESCRIPTION'.$placeholderNumber  => contrexx_raw2xhtml($imageDesc),
                'GALLERY_IMAGE_FILESIZE'.$placeholderNumber     => ($showImageSize && $showFileName) ? '('. $imageFileSize .' kB)' : '',
            ));

            if ($intFillPlaceholder == $fillPlaceholderCount) {
                // Parse the data after current increment reaches placeholder count
                $this->_objTpl->parse('galleryShowImages');
                $intFillPlaceholder = 1;
            } else {
                $intFillPlaceholder++;
            }
            $objResult->MoveNext();
        }

        if (   $intFillPlaceholder != 1 // $intFillPlaceholder == 1, when image count equals to placeholder count
            && $intFillPlaceholder <= $fillPlaceholderCount
        ) {
            // The galleryShowImages block not parsed for the last entry,
            // so we are calling parse function here
            $this->_objTpl->parse('galleryShowImages');
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
        global $objDatabase;

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
                'SET picid='.$intPicId.', date='.time().', '.
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
            "WHERE md5='".$strMd5.
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
                "SET picid=$intPicId, date=".time().", ".
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

    /**
     * Parse the voting details of the given picture
     *
     * @param \Cx\Core\Html\Sigma   $template       Template instance
     * @param integer               $categoryId     Category id
     * @param integer               $pictureId      Picture id
     *
     * @return null
     */
    public function parsePictureVotingTab(\Cx\Core\Html\Sigma $template, $categoryId, $pictureId)
    {
        global $_ARRAYLANG, $objDatabase;

        if (!$template->blockExists('votingTab')) {
            return;
        }
        $boolVoting = $this->categoryAllowsVoting($categoryId);
        if ($this->arrSettings['show_voting'] != 'on' || !$boolVoting) {
            $template->hideBlock('votingTab');
            return;
        }

        $isAlreadyVoted = isset($_COOKIE['Gallery_Voting_'.$pictureId]);
        if (!$isAlreadyVoted) {
            for ($i=1;$i<=10;$i++) {
                $template->setVariable(array(
                    'VOTING_BAR_SRC'   => \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModuleWebPath() . '/Gallery/View/Media/voting/'.$i.'.gif',
                    'VOTING_BAR_ALT'   => $_ARRAYLANG['TXT_VOTING_RATE'].': '.$i,
                    'VOTING_BAR_MARK'  => $i,
                    'VOTING_BAR_CID'   => $categoryId,
                    'VOTING_BAR_PICID' => $pictureId
                ));
                $template->parse('showVotingBar');
            }
        } else {
            $template->hideBlock('showVotingBar');
        }
        $template->setVariable(array(
            'TXT_VOTING_ALREADY_VOTED'  => $isAlreadyVoted ? $_ARRAYLANG['TXT_VOTING_ALREADY_VOTED'] : '',
            'VOTING_ALREADY_VOTED_MARK' => $isAlreadyVoted ? contrexx_input2int($_COOKIE['Gallery_Voting_'.$pictureId]) : '',
        ));

        $objResult = $objDatabase->Execute('SELECT
                                               `mark`
                                            FROM
                                                `'. DBPREFIX .'module_gallery_votes`
                                            WHERE
                                                `picid` = '.$pictureId);
        $intCount = 0;
        $intMark  = 0;
        if ($objResult && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                $intCount++;
                $intMark = $intMark + intval($objResult->fields['mark']);
                $objResult->MoveNext();
            }
        }
        $template->setVariable(array(
            'VOTING_STATS_MARK'  => $intCount
                                      ? number_format(round($intMark / $intCount, 1) ,1, '.', '\'')
                                      : 0,
            'VOTING_STATS_VOTES' => $intCount
        ));

        $template->setVariable(array(
            'TXT_VOTING_TITLE'        => $_ARRAYLANG['TXT_VOTING_TITLE'],
            'TXT_VOTING_STATS_ACTUAL' => $_ARRAYLANG['TXT_VOTING_STATS_ACTUAL'],
            'TXT_VOTING_STATS_WITH'   => $_ARRAYLANG['TXT_VOTING_STATS_WITH'],
            'TXT_VOTING_STATS_VOTES'  => $_ARRAYLANG['TXT_VOTING_STATS_VOTES'],
        ));
    }

    /**
     * Parse the comment details of the given picture
     *
     * @param \Cx\Core\Html\Sigma   $template       Template instance
     * @param integer               $categoryId     Category id
     * @param integer               $pictureId      Picture id
     *
     * @return null
     */
    public function parsePictureCommentsTab(\Cx\Core\Html\Sigma $template, $categoryId, $pictureId)
    {
        global $_ARRAYLANG, $objDatabase;

        if (!$template->blockExists('commentTab')) {
            return;
        }
        $boolComment = $this->categoryAllowsComments($categoryId);
        if ($this->arrSettings['show_comments'] != 'on' || !$boolComment) {
            $template->hideBlock('commentTab');
        }
        $objResult = $objDatabase->Execute('SELECT
                                                `date`,
                                                `name`,
                                                `email`,
                                                `www`,
                                                `comment`
                                            FROM
                                                `'. DBPREFIX .'module_gallery_comments`
                                            WHERE
                                                `picid` = '. contrexx_input2int($pictureId) .'
                                            ORDER BY `date` ASC');

        if (!$objResult) {
            return;
        }
        $commentsCount = $objResult->RecordCount();
        $template->setVariable(array(
            'TXT_COMMENTS_TITLE'        => $commentsCount .'&nbsp;'. $_ARRAYLANG['TXT_COMMENTS_TITLE'],
            'TXT_COMMENTS_ADD_TITLE'    => $_ARRAYLANG['TXT_COMMENTS_ADD_TITLE'],
            'TXT_COMMENTS_ADD_NAME'     => $_ARRAYLANG['TXT_COMMENTS_ADD_NAME'],
            'TXT_COMMENTS_ADD_EMAIL'    => $_ARRAYLANG['TXT_COMMENTS_ADD_EMAIL'],
            'TXT_COMMENTS_ADD_HOMEPAGE' => $_ARRAYLANG['TXT_COMMENTS_ADD_HOMEPAGE'],
            'TXT_COMMENTS_ADD_TEXT'     => $_ARRAYLANG['TXT_COMMENTS_ADD_TEXT'],
            'TXT_COMMENTS_ADD_SUBMIT'   => $_ARRAYLANG['TXT_COMMENTS_ADD_SUBMIT'],
        ));

        if (!$commentsCount) { // no comments, hide the block
            $template->hideBlock('showComments');
            return;
        }
        $i     = 0;
        $cx    = \Cx\Core\Core\Controller\Cx::instanciate();
        $image = '<img alt="%1$s" src="'. $cx->getCodeBaseModuleWebPath() .'/Gallery/View/Media/%2$s" width="16" height="16" alt="" align="baseline" border="0" />';
        $pixelImage = sprintf($image, '', 'pixel.gif');
        while (!$objResult->EOF) {
            $strWWW   = !empty($objResult->fields['www'])
                          ? '<a href="'.$objResult->fields['www'].'">'. (sprintf($image, $objResult->fields['www'], 'www.gif')) .'</a>'
                          : $pixelImage;
            $strEmail = !empty($objResult->fields['email'])
                          ? '<a href="mailto:'.$objResult->fields['email'].'">'. (sprintf($image, $objResult->fields['email'], 'email.gif')) .'</a>'
                          : $pixelImage;
            $template->setVariable(array(
                'COMMENTS_NAME'     => html_entity_decode($objResult->fields['name']),
                'COMMENTS_DATE'     => date($_ARRAYLANG['TXT_COMMENTS_DATEFORMAT'], $objResult->fields['date']),
                'COMMENTS_WWW'      => $strWWW,
                'COMMENTS_EMAIL'    => $strEmail,
                'COMMENTS_TEXT'     => nl2br($objResult->fields['comment']),
                'COMMENTS_ROWCLASS' => ($i % 2 == 0) ? 1 : 2,
            ));

            $template->parse('showComments');
            $objResult->MoveNext();
            $i++;
        }
    }

    /**
     * Check whether logged user has access to te given category, Redirect to no access when not having access
     */
    public function checkAccessToCategory($categoryId)
    {
        $categoryProtected = $this->categoryIsProtected($categoryId);
        if (!$categoryProtected) {
            return;
        }
        if (!\Permission::checkAccess($categoryProtected, 'dynamic', true)) {
            $link = base64_encode($_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING']);
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&cmd=noaccess&redirect=".$link);
            exit;
        }
    }

    /**
     * Get the previous and next picture id's
     *
     * @param integer $categoryId Category Id
     * @param integer $pictureId  Picture Id
     *
     * @return array Return's the previous and next picture of the given id
     */
    public function getPreviousAndNextPicture($categoryId, $pictureId)
    {
        $db = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
        $arrPictures = array();

        // get pictures of the current category
        $objResult = $db->Execute('
            SELECT id FROM '.DBPREFIX.'module_gallery_pictures
            WHERE status=\'1\' AND validated=\'1\' AND catid=' . intval($categoryId) . '
            ORDER BY sorting, id
		');
        while (!$objResult->EOF) {
            array_push($arrPictures, $objResult->fields['id']);
            $objResult->MoveNext();
        }
        // get next picture id
        if (array_key_exists(array_search($pictureId, $arrPictures) + 1, $arrPictures)) {
            $next = $arrPictures[array_search($pictureId, $arrPictures) + 1];
        } else {
            $next = $arrPictures[0];
        }
        // get previous picture id
        if (array_key_exists(array_search($pictureId, $arrPictures) - 1, $arrPictures)) {
            $previous = $arrPictures[array_search($pictureId, $arrPictures) - 1];
        } else {
            $previous = end($arrPictures);
        }

        return array($previous, $next);
    }

    /**
     * Parse the category tree into the given page template
     *
     * @param \Cx\Core\Html\Sigma $template Template instance
     */
    public function parseCategoryTree(\Cx\Core\Html\Sigma $template)
    {
        global $_ARRAYLANG;

        if ($this->arrSettings['header_type'] == 'hierarchy') {
            $categoryTree    = $this->getCategoryTree();
            $txtCategoryHint = $_ARRAYLANG['TXT_GALLERY_CATEGORY_HINT_HIERARCHY'];
        } else {
            $categoryTree    = $this->getSiblingList();
            $txtCategoryHint = $_ARRAYLANG['TXT_GALLERY_CATEGORY_HINT_FLAT'];
        }
        $template->setVariable(array(
            'GALLERY_CATEGORY_TREE'     => $categoryTree,
            'TXT_GALLERY_CATEGORY_HINT' => $txtCategoryHint,
        ));
    }

    /**
     * Get picture details link of the picture
     *
     * @param integer $categoryId Category id
     * @param integer $pictureId  Picture id
     *
     * @return string Link to the picture detail
     */
    public function getPictureDetailLink($categoryId, $pictureId)
    {
        return \Cx\Core\Routing\Url::fromModuleAndCmd(
                     'Gallery',
                     $this->strCmd,
                     '',
                     array('cid' => $categoryId, 'pId' => $pictureId)
                 )->toString();
    }
}


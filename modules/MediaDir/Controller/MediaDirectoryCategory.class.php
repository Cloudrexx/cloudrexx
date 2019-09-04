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
 * Media  Directory Category Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Category Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryCategory extends MediaDirectoryLibrary
{
    private $intCategoryId;
    private $intParentId;
    private $intNumEntries;
    private $bolGetChildren;
    private $intRowCount;
    private $arrExpandedCategoryIds = array();

    private $strSelectedOptions;
    private $strNotSelectedOptions;
    private $arrSelectedCategories;
    private $intCategoriesSortCounter = 0;
    private $strNavigationPlaceholder;

    public $arrCategories = array();


    /**
     * Constructor
     */
    function __construct($intCategoryId=null, $intParentId=null, $bolGetChildren=1, $name)
    {
        $this->intCategoryId = intval($intCategoryId);
        $this->intParentId = intval($intParentId);
        $this->bolGetChildren = intval($bolGetChildren);
        parent::__construct('.', $name);    
        parent::getSettings();
        parent::getFrontendLanguages();
        $this->loadCategories();
    }

    public function loadCategories() {
        $this->arrCategories = self::getCategories($this->intCategoryId, $this->intParentId);
    }

    function getCategories($intCategoryId=null, $intParentId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $objInit;

        $arrCategories = array();

        if(!empty($intCategoryId)) {
            $whereCategoryId = "cat.id='".$intCategoryId."' AND";
            $whereParentId = '';
        } else {
            if(!empty($intParentId)) {
                $whereParentId = "AND (cat.parent_id='".$intParentId."') ";
            } else {
                $whereParentId = "AND (cat.parent_id='0') ";
            }

            $whereCategoryId = null;
        }

        if($objInit->mode == 'frontend') {
            $whereActive = "AND (cat.active='1') ";
        } else {
			$whereActive = '';
		}

        switch($this->arrSettings['settingsCategoryOrder']) {
            case 0;
                //custom order
                $sortOrder = "cat.`order` ASC";
                break;
            case 1;
            case 2;
                //abc order
                $sortOrder = "cat_names.`category_name`";
                break;
        }

        $langId = static::getOutputLocale()->getId();

        $objCategories = $objDatabase->Execute("
            SELECT
                cat.`id` AS `id`,
                cat.`parent_id` AS `parent_id`,
                cat.`order` AS `order`,
                cat.`show_entries` AS `show_entries`,
                cat.`show_subcategories` AS `show_subcategories`,
                cat.`picture` AS `picture`,
                cat.`active` AS `active`,
                cat_names.`category_name` AS `name`,
                cat_names.`category_description` AS `description`,
                cat_names.`category_metadesc` AS `metadesc`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_categories AS cat,
                ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names AS cat_names
            WHERE
                ($whereCategoryId cat_names.category_id=cat.id)
                $whereParentId
                $whereActive
                AND (cat_names.lang_id='".$langId."')
            ORDER BY
                ".$sortOrder."
        ");

        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
        $levelId = null;
        if (isset($requestParams['lid'])) {
            $levelId = intval($requestParams['lid']);
        }

        if ($objCategories !== false) {
            while (!$objCategories->EOF) {
                $arrCategory = array();
                $arrCategoryName = array();
                $arrCategoryDesc = array();
                $arrCategoryMetaDesc = array();
                $this->intNumEntries = 0;
                $arrCategory['catNumEntries'] = 0;

                //get lang attributes
                $arrCategoryName[0] = $objCategories->fields['name'];
                $arrCategoryDesc[0] = $objCategories->fields['description'];
                $arrCategoryMetaDesc[0] = $objCategories->fields['metadesc'];

                $objCategoryAttributes = $objDatabase->Execute("
                    SELECT
                        `lang_id` AS `lang_id`,
                        `category_name` AS `name`,
                        `category_description` AS `description`,
                        `category_metadesc` AS `metadesc`
                    FROM
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names
                    WHERE
                        category_id=".$objCategories->fields['id']."
                ");

                if ($objCategoryAttributes !== false) {
                    while (!$objCategoryAttributes->EOF) {
                        $arrCategoryName[$objCategoryAttributes->fields['lang_id']] = htmlspecialchars($objCategoryAttributes->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
                        $arrCategoryDesc[$objCategoryAttributes->fields['lang_id']] = htmlspecialchars($objCategoryAttributes->fields['description'], ENT_QUOTES, CONTREXX_CHARSET);
                        $arrCategoryMetaDesc[$objCategoryAttributes->fields['lang_id']] = htmlspecialchars($objCategoryAttributes->fields['metadesc'], ENT_QUOTES, CONTREXX_CHARSET);

                        $objCategoryAttributes->MoveNext();
                    }
                }

                $arrCategory['catId'] = intval($objCategories->fields['id']);
                $arrCategory['catParentId'] = intval($objCategories->fields['parent_id']);
                $arrCategory['catOrder'] = intval($objCategories->fields['order']);
                $arrCategory['catName'] = $arrCategoryName;
                $arrCategory['catDescription'] = $arrCategoryDesc;
                $arrCategory['catMetaDesc'] = $arrCategoryMetaDesc;
                $arrCategory['catPicture'] = htmlspecialchars($objCategories->fields['picture'], ENT_QUOTES, CONTREXX_CHARSET);
                if($this->arrSettings['settingsCountEntries'] == 1 || $objInit->mode == 'backend') {
                    $arrCategory['catNumEntries'] = $this->countEntries(intval($objCategories->fields['id']), $levelId);
                }
                $arrCategory['catShowEntries'] = intval($objCategories->fields['show_entries']);
                $arrCategory['catShowSubcategories'] = intval($objCategories->fields['show_subcategories']);
                $arrCategory['catActive'] = intval($objCategories->fields['active']);

                if($this->bolGetChildren){
                    $arrCategory['catChildren'] = self::getCategories(null, $objCategories->fields['id']);
                }

                $arrCategories[$objCategories->fields['id']] = $arrCategory;
                $objCategories->MoveNext();
            }
        }

        return $arrCategories;
    }

    public function findOneBySlug($slug) {
        return $this->findOneByName($this->getNameFromSlug($slug));
    }

    public function findOneByName($name) {
        $arrCategories = $this->getCategoryData();
        foreach ($arrCategories as $arrCategory) {
            if ($arrCategory['catName'][0] == $name) {
                return $arrCategory['catId'];
            }
        }
    }

    function listCategories($objTpl, $intView, $intCategoryId=null, $arrParentIds=null, $intEntryId=null, $arrExistingBlocks=null, $intStartLevel=1, $cmd = null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $objInit;

        if(!isset($arrParentIds)) {
            $arrCategories = $this->arrCategories;
        } else {
            $arrCategoryChildren = $this->arrCategories;

            foreach ($arrParentIds as $intParentId) {
                $arrCategoryChildren = $arrCategoryChildren[$intParentId]['catChildren'];
            }
            $arrCategories = $arrCategoryChildren;
        }

        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();

        switch ($intView) {
            case 1:
                //Backend View
                $exp_cat = isset($_GET['exp_cat']) ? $_GET['exp_cat'] : '';
                foreach ($arrCategories as $arrCategory) {
                    //generate space
                    $spacer = null;
                    $intSpacerSize = null;
                    $intSpacerSize = (count($arrParentIds)*21);
                    $spacer .= '<img src="../core/Core/View/Media/icons/pixel.gif" border="0" width="'.$intSpacerSize.'" height="11" alt="" />';

                    //check expanded categories
                    if($exp_cat == 'all') {
                        $bolExpandCategory = true;
                    } else {
                        $this->arrExpandedCategoryIds = array();
                        $bolExpandCategory = $this->getExpandedCategories($exp_cat, array($arrCategory));
                    }

                    if(!empty($arrCategory['catChildren'])) {
                        if((in_array($arrCategory['catId'], $this->arrExpandedCategoryIds) && $bolExpandCategory) || $exp_cat == 'all'){
                            $strCategoryIcon = '<a href="index.php?cmd='.$this->moduleName.'&amp;exp_cat='.$arrCategory['catParentId'].'"><img src="../core/Core/View/Media/icons/minuslink.gif" border="0" alt="{'.$this->moduleLangVar.'_CATEGORY_NAME}" title="{'.$this->moduleLangVar.'_CATEGORY_NAME}" /></a>';
                        } else {
                            $strCategoryIcon = '<a href="index.php?cmd='.$this->moduleName.'&amp;exp_cat='.$arrCategory['catId'].'"><img src="../core/Core/View/Media/icons/pluslink.gif" border="0" alt="{'.$this->moduleLangVar.'_CATEGORY_NAME}" title="{'.$this->moduleLangVar.'_CATEGORY_NAME}" /></a>';
                        }
                    } else {
                        $strCategoryIcon = '<img src="../core/Core/View/Media/icons/pixel.gif" border="0" width="11" height="11" alt="{'.$this->moduleLangVar.'_CATEGORY_NAME}" title="{'.$this->moduleLangVar.'_CATEGORY_NAME}" />';
                    }

                    //parse variables
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_CATEGORY_ROW_CLASS' =>  $this->intRowCount%2==0 ? 'row1' : 'row2',
                        $this->moduleLangVar.'_CATEGORY_ID' => $arrCategory['catId'],
                        $this->moduleLangVar.'_CATEGORY_ORDER' => $arrCategory['catOrder'],
                        $this->moduleLangVar.'_CATEGORY_NAME' => contrexx_raw2xhtml($arrCategory['catName'][0]),
                        $this->moduleLangVar.'_CATEGORY_DESCRIPTION' => $arrCategory['catDescription'][0],
                        $this->moduleLangVar.'_CATEGORY_DESCRIPTION_ESCAPED' => strip_tags($arrCategory['catDescription'][0]),
                        $this->moduleLangVar.'_CATEGORY_PICTURE' => $arrCategory['catPicture'],
                        $this->moduleLangVar.'_CATEGORY_NUM_ENTRIES' => $arrCategory['catNumEntries'],
                        $this->moduleLangVar.'_CATEGORY_ICON' => $spacer.$strCategoryIcon,
                        $this->moduleLangVar.'_CATEGORY_VISIBLE_STATE_ACTION' => $arrCategory['catActive'] == 0 ? 1 : 0,
                        $this->moduleLangVar.'_CATEGORY_VISIBLE_STATE_IMG' => $arrCategory['catActive'] == 0 ? 'off' : 'on',
                    ));

                    $objTpl->parse($this->moduleNameLC.'CategoriesList');
                    $arrParentIds[] = $arrCategory['catId'];
                    $this->intRowCount++;

                    //get children
                    if(!empty($arrCategory['catChildren'])){
                        if($bolExpandCategory) {
                            self::listCategories($objTpl, 1, $intCategoryId, $arrParentIds);
                        }
                    }

                    @array_pop($arrParentIds);
                }
                break;
            case 2:
                //Frontend View
                $intNumBlocks = count($arrExistingBlocks);
                $strIndexHeader = '';


                if($this->arrSettings['settingsCategoryOrder'] == 2) {
                    $i = $intNumBlocks-1;
                } else {
                    $i = 0;
                }

                //set first index header
                if($this->arrSettings['settingsCategoryOrder'] == 2) {
                    $strFirstIndexHeader = null;
                }

                $intNumCategories = count($arrCategories);

                if($intNumBlocks && $intNumCategories%$intNumBlocks != 0) {
                	$intNumCategories = $intNumCategories+($intNumCategories%$intNumBlocks);
                }

                $intNumPerRow = intval($intNumCategories/$intNumBlocks);
                $x=0;

                $levelId = null;
                if (isset($requestParams['lid'])) {
                    $levelId = intval($requestParams['lid']);
                }

                $thumbnailFormats = $this->cx->getMediaSourceManager()->getThumbnailGenerator()->getThumbnails();

                foreach ($arrCategories as $arrCategory) {
                    $intBlockId = $arrExistingBlocks[$i];

                    if($this->arrSettings['settingsCategoryOrder'] == 2) {
                        $strIndexHeader = strtoupper(substr($arrCategory['catName'][0],0,1));

                        if($strFirstIndexHeader != $strIndexHeader) {
                            if ($i < $intNumBlocks-1) {
                                ++$i;
                            } else {
                                $i = 0;
                            }
                            $strIndexHeaderTag = '<span class="'.$this->moduleNameLC.'LevelCategoryIndexHeader">'.$strIndexHeader.'</span><br />';
                        } else {
                            $strIndexHeaderTag = null;
                        }
                    } else {
                        if($x == $intNumPerRow) {
                            ++$i;

                            if($i == $intNumBlocks) {
                                $i = 0;
                            }

                            $x = 1;
                        } else {
                            $x++;
                        }

                        $strIndexHeaderTag = null;
                    }

                    // parse entries
                    if (
                        $objTpl->blockExists($this->moduleNameLC.'CategoriesLevels_row_' . $intBlockId . '_entries') &&
                        $objTpl->blockExists($this->moduleNameLC.'CategoriesLevels_row_' . $intBlockId . '_entry')
                    ) {
                        $objEntry = new MediaDirectoryEntry($this->moduleName);
                        $objEntry->getEntries(null, $levelId, $arrCategory['catId'], null, false, null, true, null, 'n', null, null, $cmd);
                        if ($objEntry->countEntries()) {
                            // set mediadirCategoriesLevels_row_N_entry tempalte block to be parsed
                            $objEntry->setStrBlockName($this->moduleNameLC.'CategoriesLevels_row_'. $intBlockId . '_entry');

                            // prarse related entries
                            $objEntry->listEntries($objTpl, 5, 'category_level');
                            $objTpl->parse($this->moduleNameLC.'CategoriesLevels_row_' . $intBlockId . '_entries');
                        } else {
                            $objTpl->hideBlock($this->moduleNameLC.'CategoriesLevels_row_' . $intBlockId . '_entries');
                        }
                    }

                    $childrenString = $this->createCategorieTree($arrCategory, $levelId);

                    //parse variables
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_CATEGORY_LEVEL_ID' => $arrCategory['catId'],
                        $this->moduleLangVar.'_CATEGORY_LEVEL_NAME' => contrexx_raw2xhtml($arrCategory['catName'][0]),
                        $this->moduleLangVar.'_CATEGORY_LEVEL_LINK' => $strIndexHeaderTag.'<a href="'.$this->getAutoSlugPath(null, $arrCategory['catId'], $levelId, true).'">'.contrexx_raw2xhtml($arrCategory['catName'][0]).'</a>',
                        $this->moduleLangVar.'_CATEGORY_LEVEL_LINK_SRC' => $this->getAutoSlugPath(null, $arrCategory['catId'], $levelId, true),
                        $this->moduleLangVar.'_CATEGORY_LEVEL_DESCRIPTION' => $arrCategory['catDescription'][0],
                        $this->moduleLangVar.'_CATEGORY_LEVEL_PICTURE' => '<img src="'.$arrCategory['catPicture'].'" border="0" alt="'.contrexx_raw2xhtml($arrCategory['catName'][0]).'" />',
                        $this->moduleLangVar.'_CATEGORY_LEVEL_PICTURE_SOURCE' => $arrCategory['catPicture'],
                        $this->moduleLangVar.'_CATEGORY_LEVEL_NUM_ENTRIES' => $arrCategory['catNumEntries'],
                        $this->moduleLangVar.'_CATEGORY_LEVEL_CHILDREN' => $childrenString,
                    ));

                    // parse thumbnails
                    if (!empty($arrCategory['catPicture'])) {
                        $arrThumbnails = array();
                        $imagePath = pathinfo($arrCategory['catPicture'], PATHINFO_DIRNAME);
                        $imageFilename = pathinfo($arrCategory['catPicture'], PATHINFO_BASENAME);
                        $thumbnails = $this->cx->getMediaSourceManager()->getThumbnailGenerator()->getThumbnailsFromFile($imagePath, $imageFilename, true);
                        foreach ($thumbnailFormats as $thumbnailFormat) {
                            if (!isset($thumbnails[$thumbnailFormat['size']])) {
                                continue;
                            }
                            $format = strtoupper($thumbnailFormat['name']);
                            $thumbnail = $thumbnails[$thumbnailFormat['size']];
                            $objTpl->setVariable(
                                $this->moduleLangVar.'_CATEGORY_LEVEL_THUMBNAIL_FORMAT_' . $format, $thumbnail
                            );
                        }
                    }

                    $objTpl->parse($this->moduleNameLC.'CategoriesLevels_row_'.$intBlockId);
                    $objTpl->clearVariables();

                    $strFirstIndexHeader = $strIndexHeader;
                }
                break;
            case 3:
                //Category Dropdown Menu
				$strDropdownOptions = '';
                foreach ($arrCategories as $arrCategory) {
                    $spacer = null;
                    $intSpacerSize = null;

                    if($arrCategory['catId'] == $intCategoryId) {
                        $strSelected = 'selected="selected"';
                    } else {
                        $strSelected = '';
                    }

                    //generate space
                    $intSpacerSize = (count($arrParentIds));
                    for($i = 0; $i < $intSpacerSize; $i++) {
                        $spacer .= "----";
                    }

                    if($spacer != null) {
                    	$spacer .= "&nbsp;";
                    }

                    $strDropdownOptions .= '<option value="'.$arrCategory['catId'].'" '.$strSelected.' >'.$spacer.contrexx_raw2xhtml($arrCategory['catName'][0]).'</option>';

                    if(!empty($arrCategory['catChildren'])) {
                        $arrParentIds[] = $arrCategory['catId'];
                        $strDropdownOptions .= self::listCategories($objTpl, 3, $intCategoryId, $arrParentIds);
                        @array_pop($arrParentIds);
                    }
                }

                return $strDropdownOptions;
                break;
            case 4:
                //Category Selector (modify view)
                if(!isset($this->arrSelectedCategories) && $intEntryId!=null) {
                    $this->arrSelectedCategories = array();

                    $objCategorySelector = $objDatabase->Execute("
                        SELECT
                            `category_id`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories
                        WHERE
                            `entry_id` = '".$intEntryId."'
                    ");

                    if ($objCategorySelector !== false) {
                        while (!$objCategorySelector->EOF) {
                            $this->arrSelectedCategories[] = intval($objCategorySelector->fields['category_id']);
                            $objCategorySelector->MoveNext();
                        }
                    }
                }

                foreach ($arrCategories as $arrCategory) {
                    $spacer = null;
                    $intSpacerSize = null;
                    $strOptionId = $arrCategory['catId'];

                     //generate space
                    $intSpacerSize = (count($arrParentIds));
                    for($i = 0; $i < $intSpacerSize; $i++) {
                        $spacer .= "----";
                    }

                    if($spacer != null) {
                        $spacer .= "&nbsp;";
                    }

                    if (
                        $this->arrSelectedCategories &&
                        in_array($arrCategory['catId'], $this->arrSelectedCategories)
                    ) {
                      $this->strSelectedOptions .= '<option name="'.$strOptionId.'" value="'.$arrCategory['catId'].'">'.$spacer.contrexx_raw2xhtml($arrCategory['catName'][0]).'</option>';
                    } else {
                      $this->strNotSelectedOptions .= '<option name="'.$strOptionId.'" value="'.$arrCategory['catId'].'">'.$spacer.contrexx_raw2xhtml($arrCategory['catName'][0]).'</option>';
                    }

                    $this->intCategoriesSortCounter++;
                    if(!empty($arrCategory['catChildren'])) {
                        $arrParentIds[] = $arrCategory['catId'];
                        self::listCategories($objTpl, 4, $intCategoryId, $arrParentIds, $intEntryId);
                        @array_pop($arrParentIds);
                    }
                }

                $arrSelectorOptions['selected'] = $this->strSelectedOptions;
                $arrSelectorOptions['not_selected'] = $this->strNotSelectedOptions;

                return $arrSelectorOptions;
                
                break;
            case 5:
                //Frontend View Detail
                $levelId = null;
                if (isset($requestParams['lid'])) {
                    $levelId = intval($requestParams['lid']);
                }
                
                $thumbImage = $this->getThumbImage($arrCategories[$intCategoryId]['catPicture']);
                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_CATEGORY_LEVEL_TYPE' => 'category',
                    $this->moduleLangVar.'_CATEGORY_LEVEL_ID' => $arrCategories[$intCategoryId]['catId'],
                    $this->moduleLangVar.'_CATEGORY_LEVEL_NAME' => contrexx_raw2xhtml($arrCategories[$intCategoryId]['catName'][0]),
                    $this->moduleLangVar.'_CATEGORY_LEVEL_LINK' => '<a href="'.$this->getAutoSlugPath(null, $intCategoryId, $levelId).'">'.contrexx_raw2xhtml($arrCategories[$intCategoryId]['catName'][0]).'</a>',
                    $this->moduleLangVar.'_CATEGORY_LEVEL_LINK_SRC' => $this->getAutoSlugPath(null, $intCategoryId, $levelId),
                    $this->moduleLangVar.'_CATEGORY_LEVEL_DESCRIPTION' => $arrCategories[$intCategoryId]['catDescription'][0],
                    $this->moduleLangVar.'_CATEGORY_LEVEL_PICTURE' => '<img src="'. $thumbImage .'" border="0" alt="'.$arrCategories[$intCategoryId]['catName'][0].'" />',
                    $this->moduleLangVar.'_CATEGORY_LEVEL_PICTURE_SOURCE' => $arrCategories[$intCategoryId]['catPicture'],
                    $this->moduleLangVar.'_CATEGORY_LEVEL_NUM_ENTRIES' => $arrCategories[$intCategoryId]['catNumEntries'],
                ));

                // parse thumbnails
                if ($thumbImage) {
                    $thumbnailFormats = $this->cx->getMediaSourceManager()->getThumbnailGenerator()->getThumbnails();
                    $arrThumbnails = array();
                    $imagePath = pathinfo($arrCategories[$intCategoryId]['catPicture'], PATHINFO_DIRNAME);
                    $imageFilename = pathinfo($arrCategories[$intCategoryId]['catPicture'], PATHINFO_BASENAME);
                    $thumbnails = $this->cx->getMediaSourceManager()->getThumbnailGenerator()->getThumbnailsFromFile($imagePath, $imageFilename, true);
                    foreach ($thumbnailFormats as $thumbnailFormat) {
                        if (!isset($thumbnails[$thumbnailFormat['size']])) {
                            continue;
                        }
                        $format = strtoupper($thumbnailFormat['name']);
                        $thumbnail = $thumbnails[$thumbnailFormat['size']];
                        $objTpl->setVariable(
                            $this->moduleLangVar.'_CATEGORY_LEVEL_THUMBNAIL_FORMAT_' . $format, $thumbnail
                        );
                    }
                }

                // parse GoogleMap
                $this->parseGoogleMapPlaceholder($objTpl, $this->moduleLangVar.'_CATEGORY_LEVEL_GOOGLE_MAP');

                if(!empty($arrCategories[$intCategoryId]['catPicture']) && $this->arrSettings['settingsShowCategoryImage'] == 1) {
                    $objTpl->parse($this->moduleNameLC.'CategoryLevelPicture');
                } else {
                    $objTpl->hideBlock($this->moduleNameLC.'CategoryLevelPicture');
                }

                if(!empty($arrCategories[$intCategoryId]['catDescription'][0]) && $this->arrSettings['settingsShowCategoryDescription'] == 1) {
                    $objTpl->parse($this->moduleNameLC.'CategoryLevelDescription');
                } else {
                    $objTpl->hideBlock($this->moduleNameLC.'CategoryLevelDescription');
                }

                if(!empty($arrCategories)) {
                    $objTpl->parse($this->moduleNameLC.'CategoryLevelDetail');
                } else {
                    $objTpl->hideBlock($this->moduleNameLC.'CategoryLevelDetail');
                }

                break;
            case 6:
                //Frontend Tree Placeholder

                $levelId = null;
                if (isset($requestParams['lid'])) {
                    $levelId = intval($requestParams['lid']);
                }
                foreach ($arrCategories as $arrCategory) {
                	$this->arrExpandedCategoryIds = array();
                    $bolExpandCategory = $this->getExpandedCategories($intCategoryId, array($arrCategory));
                    $strLinkClass = $bolExpandCategory ? 'active' : 'inactive';
                    $strListClass = 'level_'.intval(count($arrParentIds)+$intStartLevel);
                    
                    $this->strNavigationPlaceholder .= '<li class="'.$strListClass.'"><a href="'.$this->getAutoSlugPath(null, $arrCategory['catId'], $levelId).'" class="'.$strLinkClass.'">'.contrexx_raw2xhtml($arrCategory['catName'][0]).'</a></li>';
            
                    $arrParentIds[] = $arrCategory['catId'];

                    //get children
                    if(!empty($arrCategory['catChildren']) && $arrCategory['catShowSubcategories'] == 1){
                    	if($bolExpandCategory) {
                            self::listCategories($objTpl, 6, $intCategoryId, $arrParentIds, null, null, $intStartLevel);
                    	}                    
                    }
                    @array_pop($arrParentIds);
                }
                
                return $this->strNavigationPlaceholder;
                
                break;
        }
    }



    function getExpandedCategories($intExpand, $arrData)
    {
        foreach ($arrData as $arrCategory) {
            if ($arrCategory['catId'] != $intExpand) {
                if(!empty($arrCategory['catChildren'])) {
                    $this->arrExpandedCategoryIds[] = $arrCategory['catId'];
                    $this->getExpandedCategories($intExpand, $arrCategory['catChildren']);
                }
            } else {
                $this->arrExpandedCategoryIds[] = $arrCategory['catId'];
                $this->arrExpandedCategoryIds[] = "found";
            }
        }

        if(in_array("found", $this->arrExpandedCategoryIds)) {
            return true;
        } else {
           return false;
        }


    }



    function saveCategory($arrData, $intCategoryId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        //get data
        $intId = intval($intCategoryId);
        $intParentId = intval($arrData['categoryPosition']);
        $intShowEntries = intval($arrData['categoryShowEntries']);
        $intShowCategories = isset($arrData['categoryShowSubcategories']) ? contrexx_input2int($arrData['categoryShowSubcategories']) : 0;
        $intActive = intval($arrData['categoryActive']);
        $strPicture = contrexx_addslashes(contrexx_strip_tags($arrData['categoryImage']));
        
        $arrName = $arrData['categoryName'];
        
        $arrDescription = $arrData['categoryDescription'];

        $arrMetaDesc = $arrData['categoryMetaDesc'];

        // set default values taken from output locale
        if (empty($arrName[0])) {
            $arrName[0] = '[[' . $_ARRAYLANG['TXT_MEDIADIR_NEW_CATEGORY'] . ']]';
        }
        if (
            empty($arrMetaDesc[0]) &&
            isset($arrMetaDesc[static::getOutputLocale()->getId()])
        ) {
            $arrMetaDesc[0] = $arrMetaDesc[static::getOutputLocale()->getId()];
        }
        if (empty($arrMetaDesc[0])) {
            $arrMetaDesc[0] = '';
        }
                        
        if(empty($intId)) {
            //insert new category
            $objInsertAttributes = $objDatabase->Execute("
                INSERT INTO
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_categories
                SET
                    `parent_id`='".$intParentId."',
                    `order`= 0,
                    `show_entries`='".$intShowEntries."',
                    `show_subcategories`='".$intShowCategories."',
                    `picture`='".$strPicture."',
                    `active`='".$intActive."'
            ");

            if($objInsertAttributes !== false) {
                $intId = $objDatabase->Insert_ID();

                foreach ($this->arrFrontendLanguages as $arrLang) {
                    $strName = $arrName[$arrLang['id']];
                    $strDescription = $arrDescription[$arrLang['id']];
                    $metaDesc = $arrMetaDesc[$arrLang['id']];

                    if(empty($strName)) $strName = $arrName[0];
                    if(empty($metaDesc)) $metaDesc = $arrMetaDesc[0];

                    $objInsertNames = $objDatabase->Execute("
                        INSERT INTO
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names
                        SET
                            `lang_id`='".intval($arrLang['id'])."',
                            `category_id`='".intval($intId)."',
                            `category_name`='".contrexx_raw2db($strName)."',
                            `category_description`='".contrexx_raw2db($strDescription)."',
                            `category_metadesc`='".contrexx_input2db($metaDesc)."'
                    ");
                }

                if($objInsertNames !== false) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            //update category
            if($intParentId == $intCategoryId) {
                $parentSql = null;
            } else {
                $parentSql = "`parent_id`='".$intParentId."',";
            }

            $objUpdateAttributes = $objDatabase->Execute("
                UPDATE
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_categories
                SET
                    ".$parentSql."
                    `show_entries`='".$intShowEntries."',
                    `show_subcategories`='".$intShowCategories."',
                    `picture`='".$strPicture."',
                    `active`='".$intActive."'
                WHERE
                    `id`='".$intId."'
            ");

            if($objUpdateAttributes !== false) {
                
                $objDeleteNames = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names WHERE category_id='".$intId."'");

                if($objInsertNames !== false) {
                    foreach ($this->arrFrontendLanguages as $arrLang) {
                        $strName = $arrName[$arrLang['id']];
                        $strDescription = $arrDescription[$arrLang['id']];
                        $metaDesc = $arrMetaDesc[$arrLang['id']];

                        if(empty($strName)) $strName = $arrName[0];
                        if(empty($metaDesc)) $metaDesc = $arrMetaDesc[0];

                        $objInsertNames = $objDatabase->Execute("
                            INSERT INTO
                                ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names
                            SET
                                `lang_id`='".intval($arrLang['id'])."',
                                `category_id`='".intval($intId)."',
                                `category_name`='".contrexx_raw2db(contrexx_input2raw($strName))."',
                                `category_description`='".contrexx_raw2db(contrexx_input2raw($strDescription))."',
                                `category_metadesc`='".contrexx_input2db($metaDesc)."'
                        ");
                    }

                    if($objInsertNames !== false) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }




    function deleteCategory($intCategoryId=null)
    {
        global $objDatabase;

        $intCategoryId = intval($intCategoryId);

        $objSubCategoriesRS = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_categories WHERE parent_id='".$intCategoryId."'");
        if ($objSubCategoriesRS !== false) {
            while (!$objSubCategoriesRS->EOF) {
                $intSubCategoryId = $objSubCategoriesRS->fields['id'];
                $this->deleteCategory($intSubCategoryId);
                $objSubCategoriesRS->MoveNext();
            };
        }

        $objDeleteCategoryRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_categories WHERE id='$intCategoryId'");
        $objDeleteCategoryRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names WHERE category_id='$intCategoryId'");
        $objDeleteCategoryRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories WHERE category_id='$intCategoryId'");

        if ($objDeleteCategoryRS !== false) {
            return true;
        } else {
            return false;
        }
    }



    function countEntries($intCategoryId=null, $intLevelId=null)
    {
        global $objDatabase;

        $intCategoryId = intval($intCategoryId);
        $intLevelId = intval($intLevelId);

        $objSubCategoriesRS = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_categories WHERE parent_id='".$intCategoryId."'");
        if ($objSubCategoriesRS !== false) {
            while (!$objSubCategoriesRS->EOF) {
                $intSubCategoryId = $objSubCategoriesRS->fields['id'];
                $this->countEntries($intSubCategoryId, $intLevelId);
                $objSubCategoriesRS->MoveNext();
            };
        }
        
        $whereCategory = '';
        if ($intCategoryId && $intCategoryId > 0) {
            $whereCategory = " AND `rel_categories`.`category_id` = " . intval($intCategoryId);
        }
        $objCountEntriesRS = $objDatabase->Execute("
                                                SELECT COUNT(*) as c
                                                FROM
                                                        `" . DBPREFIX . "module_".$this->moduleTablePrefix."_entries` AS `entry`
                                                INNER JOIN
                                                    `".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories` AS `rel_categories`
                                                ON
                                                    `rel_categories`.`entry_id` = `entry`.`id`
                                                LEFT JOIN 
                                                    `".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields` AS rel_inputfield
                                                ON  
                                                    rel_inputfield.`entry_id` = `entry`.`id`
                                                
                                                WHERE 
                                                    `entry`.`active` = 1
                                                AND 
                                                    (rel_inputfield.`form_id` = entry.`form_id`)
                                                AND 
                                                    (rel_inputfield.`field_id` = (".$this->getQueryToFindPrimaryInputFieldId()."))
                                                AND
                                                    (rel_inputfield.`lang_id` = '" . static::getOutputLocale()->getId() . "')
                                                AND ((`entry`.`duration_type`=2 AND `entry`.`duration_start` <= ".time()." AND `entry`.`duration_end` >= ".time().") OR (`entry`.`duration_type`=1))
                                                    " . $whereCategory . "
                                                GROUP BY
                                                    `rel_categories`.`category_id`");

        $this->intNumEntries += $objCountEntriesRS->fields['c'];

        return intval($this->intNumEntries);
    }



    function saveOrder($arrData) {
        global $objDatabase;

        foreach($arrData['catOrder'] as $intCatId => $intCatOrder) {
            $objRSCatOrder = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_categories SET `order`='".intval($intCatOrder)."' WHERE `id`='".intval($intCatId)."'");

            if ($objRSCatOrder === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $arrCategory
     *
     * @return array
     */
    public function createCategorieTree($arrCategory, $levelId) {
        $childrenString = '<ul>';
        if (!empty($arrCategory['catChildren'])) {
            foreach ($arrCategory['catChildren'] as $children) {
                $childrenString .= '<li><a href="'.$this->getAutoSlugPath(null, $children['catId'], $levelId).'">' . $children['catName'][0] .'</a>';
                if (!empty($children['catChildren'])) {
                    $childrenString .= $this->createCategorieTree($children, $levelId);
                }
                $childrenString .= '</li>';
            }
        }
        $childrenString .= '</ul>';
        return $childrenString;
    }
}

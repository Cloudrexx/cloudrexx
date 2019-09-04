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
 * Media  Directory Level Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Level Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryLevel extends MediaDirectoryLibrary
{
    private $intLevelId;
    private $intParentId;
    private $intNumEntries;
    private $bolGetChildren;
    private $intRowCount;
    private $arrExpandedLevelIds = array();

    private $arrSelectedLevels;
    private $strNavigationPlaceholder;

    public $arrLevels = array();

    private $strNotSelectedOptions = '';
    private $strSelectedOptions = '';

    /**
     * Constructor
     */
    function __construct($intLevelId=null, $intParentId=null, $bolGetChildren=1, $name)
    {
        $this->intLevelId = intval($intLevelId);
        $this->intParentId = intval($intParentId);
        $this->bolGetChildren = intval($bolGetChildren);

        parent::__construct('.', $name);
        parent::getSettings();
        parent::getFrontendLanguages();

        $this->arrLevels = self::getLevels($this->intLevelId, $this->intParentId);
    }

    function getLevels($intLevelId=null, $intParentId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $arrLevels = array();

        if(!empty($intLevelId)) {
            $whereLevelId = "level.id='".$intLevelId."' AND";
            $whereParentId = '';
        } else {
            if(!empty($intParentId)) {
                $whereParentId = "AND (level.parent_id='".$intParentId."') ";
            } else {
                $whereParentId = "AND (level.parent_id='0') ";
            }

            $whereLevelId = null;
        }

        $langId = static::getOutputLocale()->getId();
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            $whereActive = "AND (level.active='1') ";
        } else {
            $whereActive = '';
        }

        switch($this->arrSettings['settingsLevelOrder']) {
            case 0;
                //custom order
                $sortOrder = "level.`order` ASC";
                break;
            case 1;
            case 2;
                //abc order
                $sortOrder = "level_names.`level_name`";
                break;
        }

        $objLevels = $objDatabase->Execute("
            SELECT
                level.`id` AS `id`,
                level.`parent_id` AS `parent_id`,
                level.`order` AS `order`,
                level.`show_sublevels` AS `show_sublevels`,
                level.`show_categories` AS `show_categories`,
                level.`show_entries` AS `show_entries`,
                level.`picture` AS `picture`,
                level.`active` AS `active`,
                level_names.`level_name` AS `name`,
                level_names.`level_description` AS `description`,
                level_names.`level_metadesc` AS `metadesc`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_levels AS level,
                ".DBPREFIX."module_".$this->moduleTablePrefix."_level_names AS level_names
            WHERE
                ($whereLevelId level_names.level_id=level.id)
                $whereParentId
                $whereActive
                AND (level_names.lang_id='".$langId."')
            ORDER BY
                ".$sortOrder."
        ");

        //fetch entry counts if needed
        $weAreCountingEntries = ($this->arrSettings['settingsCountEntries'] == 1 || $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND);
        $arrEntryCounts = array();
        if($weAreCountingEntries) {
            $query = "
                SELECT
                    `rel_levels`.`level_id`, count(*) AS `c`
                FROM
                    `" . DBPREFIX . "module_".$this->moduleTablePrefix."_entries` AS `entry`
                INNER JOIN
                    `" . DBPREFIX . "module_".$this->moduleTablePrefix."_rel_entry_levels` AS `rel_levels`
                ON
                    `rel_levels`.`entry_id` = `entry`.`id`
                LEFT JOIN
                    `".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields` AS rel_inputfield
                ON
                    rel_inputfield.`entry_id` = `entry`.`id`
                WHERE
                    `entry`.`active` = 1
                AND
                    rel_inputfield.`form_id` = `entry`.`form_id`
                AND
                    rel_inputfield.`field_id` = (".$this->getQueryToFindPrimaryInputFieldId().")
                AND
                    (rel_inputfield.`lang_id` = '".$langId."')
                AND
                    ((`entry`.`duration_type`=2 AND `entry`.`duration_start` <= ".time()." AND `entry`.`duration_end` >= ".time().") OR (`entry`.`duration_type`=1))

                GROUP BY
                    `rel_levels`.`level_id`";

            $rs = $objDatabase->Execute($query);
            while(!$rs->EOF) {
                $arrEntryCounts[$rs->fields['level_id']] = $rs->fields['c'];
                $rs->MoveNext();
            }
        }

        if ($objLevels !== false) {
            while (!$objLevels->EOF) {
                $arrLevel = array();
                $arrLevelName = array();
                $arrLevelDesc = array();
                $arrLevelMetaDesc = array();
                $this->intNumEntries = 0;

                //get lang attributes
                $arrLevelName[0] = $objLevels->fields['name'];
                $arrLevelDesc[0] = $objLevels->fields['description'];
                $arrLevelMetaDesc[0] = $objLevels->fields['metadesc'];

                $objLevelAttributes = $objDatabase->Execute("
                    SELECT
                        `lang_id` AS `lang_id`,
                        `level_name` AS `name`,
                        `level_description` AS `description`,
                        `level_metadesc` AS `metadesc`
                    FROM
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_level_names
                    WHERE
                        level_id=".$objLevels->fields['id']."
                ");

                if ($objLevelAttributes !== false) {
                    while (!$objLevelAttributes->EOF) {
                        $arrLevelName[$objLevelAttributes->fields['lang_id']] = htmlspecialchars($objLevelAttributes->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
                        $arrLevelDesc[$objLevelAttributes->fields['lang_id']] = $objLevelAttributes->fields['description'];
                        $arrLevelMetaDesc[$objLevelAttributes->fields['lang_id']] = $objLevelAttributes->fields['metadesc'];

                        $objLevelAttributes->MoveNext();
                    }
                }

                $arrLevel['levelId'] = intval($objLevels->fields['id']);
                $arrLevel['levelOrder'] = intval($objLevels->fields['order']);
                $arrLevel['levelParentId'] = intval($objLevels->fields['parent_id']);
                $arrLevel['levelName'] = $arrLevelName;
                $arrLevel['levelDescription'] = $arrLevelDesc;
                $arrLevel['levelMetaDesc'] = $arrLevelMetaDesc;
                $arrLevel['levelPicture'] = htmlspecialchars($objLevels->fields['picture'], ENT_QUOTES, CONTREXX_CHARSET);
                if($weAreCountingEntries) {
                    $arrLevel['levelNumEntries'] = isset($arrEntryCounts[$arrLevel['levelId']]) ? $arrEntryCounts[$arrLevel['levelId']] : 0;
                }
                $arrLevel['levelShowEntries'] = intval($objLevels->fields['show_entries']);
                $arrLevel['levelShowSublevels'] = intval($objLevels->fields['show_sublevels']);
                $arrLevel['levelShowCategories'] = intval($objLevels->fields['show_categories']);
                $arrLevel['levelActive'] = intval($objLevels->fields['active']);

                if($this->bolGetChildren){
                    $arrLevel['levelChildren'] = self::getLevels(null, $objLevels->fields['id']);
                }

                $arrLevels[$objLevels->fields['id']] = $arrLevel;
                $objLevels->MoveNext();
            }
        }

        return $arrLevels;
    }

    public function findOneBySlug($slug) {
        return $this->findOneByName($this->getNameFromSlug($slug));
    }

    public function findOneByName($name) {
        $arrLevels = $this->getLevelData();
        foreach ($arrLevels as $arrLevel) {
            if ($arrLevel['levelName'][0] == $name) {
                return $arrLevel['levelId'];
            }
        }
    }

    function listLevels($objTpl, $intView, $intLevelId=null, $arrParentIds=null, $intEntryId=null, $arrExistingBlocks=null, $strClass=null, $cmd = null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        if(!isset($arrParentIds)) {
            $arrLevels = $this->arrLevels;
        } else {
            $arrLevelChildren = $this->arrLevels;

            foreach ($arrParentIds as $key => $intParentId) {
                $arrLevelChildren = $arrLevelChildren[$intParentId]['levelChildren'];
            }
            $arrLevels = $arrLevelChildren;
        }

        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();

        switch ($intView) {
            case 1:
                //Backend View
                $expandLevel = isset($_GET['exp_level']) ? $_GET['exp_level'] : null;
                foreach ($arrLevels as $key => $arrLevel) {
                    //generate space
                    $spacer = null;
                    $intSpacerSize = null;
                    $intSpacerSize = (count($arrParentIds)*21);
                    $spacer .= '<img src="../core/Core/View/Media/icons/pixel.gif" border="0" width="'.$intSpacerSize.'" height="11" alt="" />';

                    //check expanded categories
                    if($expandLevel == 'all') {
                        $bolExpandLevel = true;
                    } else {
                        $this->arrExpandedLevelIds = array();
                        $bolExpandLevel = $this->getExpandedLevels($expandLevel, array($arrLevel));
                    }

                    if(!empty($arrLevel['levelChildren'])) {
                        if((in_array($arrLevel['levelId'], $this->arrExpandedLevelIds) && $bolExpandLevel) || $expandLevel == 'all'){
                            $strLevelIcon = '<a href="index.php?cmd='.$this->moduleName.'&amp;exp_level='.$arrLevel['levelParentId'].'"><img src="../core/Core/View/Media/icons/minuslink.gif" border="0" alt="{'.$this->moduleLangVar.'_LEVEL_NAME}" title="{'.$this->moduleLangVar.'_LEVEL_NAME}" /></a>';
                        } else {
                            $strLevelIcon = '<a href="index.php?cmd='.$this->moduleName.'&amp;exp_level='.$arrLevel['levelId'].'"><img src="../core/Core/View/Media/icons/pluslink.gif" border="0" alt="{'.$this->moduleLangVar.'_LEVEL_NAME}" title="{'.$this->moduleLangVar.'_LEVEL_NAME}" /></a>';
                        }
                    } else {
                        $strLevelIcon = '<img src="../core/Core/View/Media/icons/pixel.gif" border="0" width="11" height="11" alt="{'.$this->moduleLangVar.'_LEVEL_NAME}" title="{'.$this->moduleLangVar.'_LEVEL_NAME}" />';
                    }

                    //parse variables
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_LEVEL_ROW_CLASS' =>  $this->intRowCount%2==0 ? 'row1' : 'row2',
                        $this->moduleLangVar.'_LEVEL_ID' => $arrLevel['levelId'],
                        $this->moduleLangVar.'_LEVEL_ORDER' => $arrLevel['levelOrder'],
                        $this->moduleLangVar.'_LEVEL_NAME' => contrexx_raw2xhtml($arrLevel['levelName'][0]),
                        $this->moduleLangVar.'_LEVEL_DESCRIPTION' => $arrLevel['levelDescription'][0],
                        $this->moduleLangVar.'_LEVEL_DESCRIPTION_ESCAPED' => strip_tags($arrLevel['levelDescription'][0]),
                        $this->moduleLangVar.'_LEVEL_PICTURE' => $arrLevel['levelPicture'],
                        $this->moduleLangVar.'_LEVEL_NUM_ENTRIES' => $arrLevel['levelNumEntries'],
                        $this->moduleLangVar.'_LEVEL_ICON' => $spacer.$strLevelIcon,
                        $this->moduleLangVar.'_LEVEL_VISIBLE_STATE_ACTION' => $arrLevel['levelActive'] == 0 ? 1 : 0,
                        $this->moduleLangVar.'_LEVEL_VISIBLE_STATE_IMG' => $arrLevel['levelActive'] == 0 ? 'off' : 'on',
                    ));

                    $objTpl->parse($this->moduleNameLC.'LevelsList');
                    $arrParentIds[] = $arrLevel['levelId'];
                    $this->intRowCount++;

                    //get children
                    if(!empty($arrLevel['levelChildren'])){
                        if($bolExpandLevel) {
                            self::listLevels($objTpl, 1, $intLevelId, $arrParentIds);
                        }
                    }

                    @array_pop($arrParentIds);
                }
                break;
            case 2:
                //Frontend View
                $intNumBlocks = count($arrExistingBlocks);
                $i = $intNumBlocks-1;
                $strIndexHeader = '';

                //set first index header
                if($this->arrSettings['settingsLevelOrder'] == 2) {
                    $strFirstIndexHeader = null;
                }

                $categoryId = null;
                if (isset($requestParams['cid'])) {
                    $categoryId = intval($requestParams['cid']);
                }

                $thumbnailFormats = $this->cx->getMediaSourceManager()->getThumbnailGenerator()->getThumbnails();

                foreach ($arrLevels as $key => $arrLevel) {
                    $intBlockId = $arrExistingBlocks[$i];

                    if($this->arrSettings['settingsLevelOrder'] == 2) {
                        $strIndexHeader = strtoupper(substr($arrLevel['levelName'][0],0,1));

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
                        if ($i < $intNumBlocks-1) {
                            ++$i;
                        } else {
                            $i = 0;
                        }
                        $strIndexHeaderTag = null;
                    }

                    // parse entries
                    if (
                        $objTpl->blockExists($this->moduleNameLC.'CategoriesLevels_row_' . $intBlockId . '_entries') &&
                        $objTpl->blockExists($this->moduleNameLC.'CategoriesLevels_row_' . $intBlockId . '_entry')
                    ) {
                        $objEntry = new MediaDirectoryEntry($this->moduleName);
                        $objEntry->getEntries(null, $arrLevel['levelId'], $categoryId, null, false, null, true, null, 'n', null, null, $cmd);
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

                    //parse variables
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_CATEGORY_LEVEL_ID' => $arrLevel['levelId'],
                        $this->moduleLangVar.'_CATEGORY_LEVEL_NAME' => contrexx_raw2xhtml($arrLevel['levelName'][0]),
                        $this->moduleLangVar.'_CATEGORY_LEVEL_LINK' => $strIndexHeaderTag.'<a href="'.$this->getAutoSlugPath(null, $categoryId, $arrLevel['levelId'], true).'">'.contrexx_raw2xhtml($arrLevel['levelName'][0]).'</a>',
                        $this->moduleLangVar.'_CATEGORY_LEVEL_LINK_SRC' => $this->getAutoSlugPath(null, $categoryId, $arrLevel['levelId'], true),
                        $this->moduleLangVar.'_CATEGORY_LEVEL_DESCRIPTION' => $arrLevel['levelDescription'][0],
                        $this->moduleLangVar.'_CATEGORY_LEVEL_PICTURE' => '<img src="'.$arrLevel['levelPicture'].'" border="0" alt="'.contrexx_raw2xhtml($arrLevel['levelName'][0]).'" />',
                        $this->moduleLangVar.'_CATEGORY_LEVEL_PICTURE_SOURCE' => $arrLevel['levelPicture'],
                        $this->moduleLangVar.'_CATEGORY_LEVEL_NUM_ENTRIES' => isset($arrLevel['levelNumEntries']) ? $arrLevel['levelNumEntries'] : '',
                    ));

                    // parse thumbnails
                    if (!empty($arrLevel['levelPicture'])) {
                        $arrThumbnails = array();
                        $imagePath = pathinfo($arrLevel['levelPicture'], PATHINFO_DIRNAME);
                        $imageFilename = pathinfo($arrLevel['levelPicture'], PATHINFO_BASENAME);
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
                //Dropdown Menu
                $strDropdownOptions = '';
                foreach ($arrLevels as $key => $arrLevel) {
                    $spacer = null;
                    $intSpacerSize = null;

                    if($arrLevel['levelId'] == $intLevelId) {
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

                    $strDropdownOptions .= '<option value="'.$arrLevel['levelId'].'" '.$strSelected.' >'.$spacer.contrexx_raw2xhtml($arrLevel['levelName'][0]).'</option>';

                    if(!empty($arrLevel['levelChildren'])) {
                        $arrParentIds[] = $arrLevel['levelId'];
                        $strDropdownOptions .= self::listLevels($objTpl, 3, $intLevelId, $arrParentIds);
                        @array_pop($arrParentIds);
                    }
                }

                return $strDropdownOptions;
                break;
            case 4:
                //level Selector (modify view)
                if(!isset($this->arrSelectedLevels) && $intEntryId!=null) {
                    $this->arrSelectedLevels = array();

                    $objLevelSelector = $objDatabase->Execute("
                        SELECT
                            `level_id`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels
                        WHERE
                            `entry_id` = '".$intEntryId."'
                    ");

                    if ($objLevelSelector !== false) {
                        while (!$objLevelSelector->EOF) {
                            $this->arrSelectedLevels[] = intval($objLevelSelector->fields['level_id']);
                            $objLevelSelector->MoveNext();
                        }
                    }
                }

                foreach ($arrLevels as $key => $arrLevel) {

                    $spacer = null;
                    $intSpacerSize = null;

                     //generate space
                    $intSpacerSize = (count($arrParentIds));
                    for($i = 0; $i < $intSpacerSize; $i++) {
                        $spacer .= "----";
                    }

                    if($spacer != null) {
                        $spacer .= "&nbsp;";
                    }

                    if (
                        $this->arrSelectedLevels &&
                        in_array($arrLevel['levelId'], $this->arrSelectedLevels)
                    ) {
                        $this->strSelectedOptions .= '<option value="'.$arrLevel['levelId'].'">'.$spacer.contrexx_raw2xhtml($arrLevel['levelName'][0]).'</option>';
                    } else {
                        $this->strNotSelectedOptions .= '<option value="'.$arrLevel['levelId'].'">'.$spacer.contrexx_raw2xhtml($arrLevel['levelName'][0]).'</option>';
                    }

                    if(!empty($arrLevel['levelChildren'])) {
                        $arrParentIds[] = $arrLevel['levelId'];
                        self::listLevels($objTpl, 4, $intLevelId, $arrParentIds, $intEntryId);
                        @array_pop($arrParentIds);
                    }
                }

                $arrSelectorOptions['selected'] = $this->strSelectedOptions;
                $arrSelectorOptions['not_selected'] = $this->strNotSelectedOptions;

                return $arrSelectorOptions;
                break;
            case 5:
                //Frontend View Detail

                $categoryId = null;
                if (isset($requestParams['cid'])) {
                    $categoryId = intval($requestParams['cid']);
                }

                $thumbImage = $this->getThumbImage($arrLevels[$intLevelId]['levelPicture']);
                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_CATEGORY_LEVEL_TYPE' => 'level',
                    $this->moduleLangVar.'_CATEGORY_LEVEL_ID' => $arrLevels[$intLevelId]['levelId'],
                    $this->moduleLangVar.'_CATEGORY_LEVEL_NAME' => contrexx_raw2xhtml($arrLevels[$intLevelId]['levelName'][0]),
                    $this->moduleLangVar.'_CATEGORY_LEVEL_LINK' => '<a href="'.$this->getAutoSlugPath(null, $categoryId, $intLevelId).'">'.contrexx_raw2xhtml($arrLevels[$intLevelId]['levelName'][0]).'</a>',
                    $this->moduleLangVar.'_CATEGORY_LEVEL_LINK_SRC' => $this->getAutoSlugPath(null, $categoryId, $intLevelId),
                    $this->moduleLangVar.'_CATEGORY_LEVEL_DESCRIPTION' => $arrLevels[$intLevelId]['levelDescription'][0],
                    $this->moduleLangVar.'_CATEGORY_LEVEL_PICTURE' => '<img src="'.$thumbImage.'" border="0" alt="'.contrexx_raw2xhtml($arrLevels[$intLevelId]['levelName'][0]).'" />',
                    $this->moduleLangVar.'_CATEGORY_LEVEL_PICTURE_SOURCE' => $arrLevels[$intLevelId]['levelPicture'],
                    $this->moduleLangVar.'_CATEGORY_LEVEL_NUM_ENTRIES' => isset($arrLevels[$intLevelId]['levelNumEntries']) ? $arrLevels[$intLevelId]['levelNumEntries'] : 0,
                ));

                // parse thumbnails
                if ($thumbImage) {
                    $thumbnailFormats = $this->cx->getMediaSourceManager()->getThumbnailGenerator()->getThumbnails();
                    $arrThumbnails = array();
                    $imagePath = pathinfo($arrLevels[$intLevelId]['levelPicture'], PATHINFO_DIRNAME);
                    $imageFilename = pathinfo($arrLevels[$intLevelId]['levelPicture'], PATHINFO_BASENAME);
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

                if(!empty($arrLevels[$intLevelId]['levelPicture']) && $this->arrSettings['settingsShowLevelImage'] == 1) {
                    $objTpl->parse($this->moduleNameLC.'CategoryLevelPicture');
                } else {
                    $objTpl->hideBlock($this->moduleNameLC.'CategoryLevelPicture');
                }

                if(!empty($arrLevels[$intLevelId]['levelDescription'][0]) && $this->arrSettings['settingsShowLevelDescription'] == 1) {
                    $objTpl->parse($this->moduleNameLC.'CategoryLevelDescription');
                } else {
                    $objTpl->hideBlock($this->moduleNameLC.'CategoryLevelDescription');
                }

                if(!empty($arrLevels)) {
                    $objTpl->parse($this->moduleNameLC.'CategoryLevelDetail');
                } else {
                    $objTpl->hideBlock($this->moduleNameLC.'CategoryLevelDetail');
                }

                break;
            case 6:
                //Frontend Tree Placeholder

                $categoryId = null;
                if (isset($requestParams['cid'])) {
                    $categoryId = intval($requestParams['cid']);
                }
                if (isset($requestParams['lid'])) {
                    $levelId = intval($requestParams['lid']);
                }

                foreach ($arrLevels as $key => $arrLevel) {
                    $this->arrExpandedLevelIds = array();
                    $bolExpandLevel = $this->getExpandedLevels($intLevelId, array($arrLevel));
                    $strLinkClass = $bolExpandLevel ? 'active' : 'inactive';
                    $strListClass = 'level_'.intval(count($arrParentIds)+1);

                    $this->strNavigationPlaceholder .= '<li class="'.$strListClass.'"><a href="'.$this->getAutoSlugPath(null, $categoryId, $arrLevel['levelId']).'" class="'.$strLinkClass.'">'.contrexx_raw2xhtml($arrLevel['levelName'][0]).'</a></li>';

                    $arrParentIds[] = $arrLevel['levelId'];

                    //get children
                    if(!empty($arrLevel['levelChildren']) && $arrLevel['levelShowSublevels'] == 1){
                        if($bolExpandLevel) {
                            self::listLevels($objTpl, 6, $intLevelId, $arrParentIds);
                        }
                    }

                    if($arrLevel['levelShowCategories'] == 1){
                        if($bolExpandLevel) {
                            $objCategories = new MediaDirectoryCategory(null, null, 0, $this->moduleName);
	                        $intCategoryId = null;
                            if (isset($requestParams['cid'])) {
                                $intCategoryId = intval($requestParams['cid']);
                            }
	                        if ($levelId == $arrLevel['levelId']) {
                               $this->strNavigationPlaceholder .= $objCategories->listCategories($this->_objTpl, 6, $intCategoryId, null, null, null, intval(count($arrParentIds)+1));
                            }
                        }
                    }

                    @array_pop($arrParentIds);
                }

                return $this->strNavigationPlaceholder;

                break;
        }
    }



    function getExpandedLevels($intExpand, $arrData)
    {
        foreach ($arrData as $key => $arrLevel) {
            if ($arrLevel['levelId'] != $intExpand) {
                if(!empty($arrLevel['levelChildren'])) {
                    $this->arrExpandedLevelIds[] = $arrLevel['levelId'];
                    $this->getExpandedLevels($intExpand, $arrLevel['levelChildren']);
                }
            } else {
                $this->arrExpandedLevelIds[] = $arrLevel['levelId'];
                $this->arrExpandedLevelIds[] = "found";
            }
        }

        if(in_array("found", $this->arrExpandedLevelIds)) {
            return true;
        } else {
           return false;
        }


    }




    function saveLevel($arrData, $intLevelId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        //get data
        $intId = intval($intLevelId);
        $intParentId = intval($arrData['levelPosition']);
        $intShowEntries = intval($arrData['levelShowEntries']);
        $intShowSublevels = isset($arrData['levelShowSublevels']) ? contrexx_input2int($arrData['levelShowSublevels']) : 0;
        $intShowCategories = intval($arrData['levelShowCategories']);
        $intActive = intval($arrData['levelActive']);
        $strPicture = contrexx_addslashes(contrexx_strip_tags($arrData['levelImage']));

        $arrName = $arrData['levelName'];
        $arrDescription = $arrData['levelDescription'];
        $arrMetaDesc = $arrData['levelMetaDesc'];

        if (empty($arrName[0])) {
            $arrName[0] = '[[' . $_ARRAYLANG['TXT_MEDIADIR_NEW_LEVEL'] . ']]';
        }
        if (
            empty($arrDescription[0]) &&
            isset($arrDescription[static::getOutputLocale()->getId()])
        ) {
            $arrDescription[0] = $arrDescription[static::getOutputLocale()->getId()];
        }
        if (empty($arrDescription[0])) {
            $arrDescription[0] = '';
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
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_levels
                SET
                    `parent_id`='".$intParentId."',
                    `order`=0,
                    `show_entries`='".$intShowEntries."',
                    `show_sublevels`='".$intShowSublevels."',
                    `show_categories`='".$intShowCategories."',
                    `picture`='".$strPicture."',
                    `active`='".$intActive."'
            ");

            if($objInsertAttributes !== false) {
                $intId = $objDatabase->Insert_ID();

                foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                    $strName = $arrName[$arrLang['id']];
                    $strDescription = $arrDescription[$arrLang['id']];
                    $metaDesc = $arrMetaDesc[$arrLang['id']];

                    if(empty($strName)) $strName = $arrName[0];
                    if(empty($strDescription)) $strDescription = $arrDescription[0];
                    if(empty($metaDesc)) $metaDesc = $arrMetaDesc[0];

                    $objInsertNames = $objDatabase->Execute("
                        INSERT INTO
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_level_names
                        SET
                            `lang_id`='".intval($arrLang['id'])."',
                            `level_id`='".intval($intId)."',
                            `level_name`='".contrexx_raw2db(contrexx_input2raw($strName))."',
                            `level_description`='".contrexx_raw2db(contrexx_input2raw($strDescription))."',
                            `level_metadesc`='".contrexx_input2db($metaDesc)."'
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
            if($intParentId == $intLevelId) {
                $parentSql = null;
            } else {
                $parentSql = "`parent_id`='".$intParentId."',";
            }

            $objUpdateAttributes = $objDatabase->Execute("
                UPDATE
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_levels
                SET
                    ".$parentSql."
                    `show_entries`='".$intShowEntries."',
                    `show_sublevels`='".$intShowSublevels."',
                    `show_categories`='".$intShowCategories."',
                    `picture`='".$strPicture."',
                    `active`='".$intActive."'
                WHERE
                    `id`='".$intId."'
            ");

            if($objUpdateAttributes !== false) {

                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_level_names WHERE level_id='".$intId."'");

                foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                    $strName = $arrName[$arrLang['id']];
                    $strDescription = $arrDescription[$arrLang['id']];
                    $metaDesc = $arrMetaDesc[$arrLang['id']];

                    if(empty($strName)) $strName = $arrName[0];
                    if(empty($strDescription)) $strDescription = $arrDescription[0];
                    if(empty($metaDesc)) $metaDesc = $arrMetaDesc[0];

                    $objInsertNames = $objDatabase->Execute("
                        INSERT INTO
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_level_names
                        SET
                            `lang_id`='".intval($arrLang['id'])."',
                            `level_id`='".intval($intId)."',
                            `level_name`='".contrexx_raw2db(contrexx_input2raw($strName))."',
                            `level_description`='".contrexx_raw2db(contrexx_input2raw($strDescription))."',
                            `level_metadesc`='".contrexx_input2db($metaDesc)."'
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
        }
    }



    function deleteLevel($intLevelId=null)
    {
        global $objDatabase;

        $intLevelId = intval($intLevelId);

        $objSubLevelsRS = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_levels WHERE parent_id='".$intLevelId."'");
        if ($objSubLevelsRS !== false) {
            while (!$objSubLevelsRS->EOF) {
                $intSubLevelId = $objSubLevelsRS->fields['id'];
                $this->deleteLevel($intSubLevelId);
                $objSubLevelsRS->MoveNext();
            };
        }

        $objDeleteLevelRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_levels WHERE id='$intLevelId'");
        $objDeleteLevelRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_level_names WHERE level_id='$intLevelId'");
        $objDeleteLevelRS = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels WHERE level_id='$intLevelId'");

        if ($objDeleteLevelRS !== false) {
            return true;
        } else {
            return false;
        }
    }

    function saveOrder($arrData) {
        global $objDatabase;

        foreach($arrData['levelOrder'] as $intLevelId => $intLevelOrder) {
            $objRSLevelOrder = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_levels SET `order`='".intval($intLevelOrder)."' WHERE `id`='".intval($intLevelId)."'");

            if ($objRSLevelOrder === false) {
                return false;
            }
        }

        return true;
    }
}


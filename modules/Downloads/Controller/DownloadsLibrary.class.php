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
 * Digital Asset Management
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 * @version     1.0.0
 */
namespace Cx\Modules\Downloads\Controller;
/**
 * Digital Asset Management Library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 * @version     1.0.0
 */
class DownloadsLibrary
{
    protected $defaultCategoryImage = array();
    protected $defaultDownloadImage = array();
    protected $arrPermissionTypes = array(
        'getReadAccessId'                   => 'read',
        'getAddSubcategoriesAccessId'       => 'add_subcategories',
        'getManageSubcategoriesAccessId'    => 'manage_subcategories',
        'getAddFilesAccessId'               => 'add_files',
        'getManageFilesAccessId'            => 'manage_files'
    );
    protected $searchKeyword;
    protected $arrConfig = array(
        'overview_cols_count'           => 2,
        'overview_max_subcats'          => 5,
        'use_attr_metakeys'             => 1,
        'use_attr_size'                 => 1,
        'use_attr_license'              => 1,
        'use_attr_version'              => 1,
        'use_attr_author'               => 1,
        'use_attr_website'              => 1,
        'most_viewed_file_count'        => 5,
        'most_downloaded_file_count'    => 5,
        'most_popular_file_count'       => 5,
        'newest_file_count'             => 5,
        'updated_file_count'            => 5,
        'new_file_time_limit'           => 604800,
        'updated_file_time_limit'       => 604800,
        'associate_user_to_groups'      => ''
    );

    /**
     * Downloads setting option
     *
     * @var array
     */
    protected $downloadsSortingOptions = array(
        'custom' => array(
            'order' => 'ASC',
            'name'  => 'ASC',
            'id'    => 'ASC'
        ),
        'alphabetic' => array(
            'name' => 'ASC',
            'id'   => 'ASC'
        ),
        'newestToOldest' => array(
            'ctime' => 'DESC',
            'id'    => 'ASC'
        ),
        'oldestToNewest' => array(
            'ctime' => 'ASC',
            'id'    => 'ASC'
        )
    );

    /**
     * Categories setting option
     *
     * @var array
     */
    protected $categoriesSortingOptions = array(
        'custom' => array(
            'order' => 'ASC',
            'name'  => 'ASC',
            'id'    => 'ASC'
        ),
        'alphabetic' => array(
            'name' => 'ASC',
            'id'   => 'ASC'
        )
    );

    public function __construct()
    {
        $this->initSettings();
        $this->initSearch();
        $this->initDefaultCategoryImage();
        $this->initDefaultDownloadImage();
    }


    private function initSettings()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute('SELECT `name`, `value` FROM `'.DBPREFIX.'module_downloads_settings`');
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrConfig[$objResult->fields['name']] = $objResult->fields['value'];
                $objResult->MoveNext();
            }
        }
    }


    protected function initSearch()
    {
        $this->searchKeyword = empty($_REQUEST['downloads_search_keyword']) ? '' : $_REQUEST['downloads_search_keyword'];
    }


    protected function initDefaultCategoryImage()
    {
        $this->defaultCategoryImage['src'] = \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()->getWebFilePath(\Cx\Core\Core\Controller\Cx::instanciate()->getModuleFolderName() . '/Downloads/View/Media/no_picture.gif');
        $imageSize = getimagesize(\Cx\Core\Core\Controller\Cx::instanciate()->getCodeBasePath().$this->defaultCategoryImage['src']);

        $this->defaultCategoryImage['width'] = $imageSize[0];
        $this->defaultCategoryImage['height'] = $imageSize[1];
    }


    protected function initDefaultDownloadImage()
    {
        $this->defaultDownloadImage = $this->defaultCategoryImage;
    }


    protected function updateSettings()
    {
        global $objDatabase;

        foreach ($this->arrConfig as $key => $value) {
            $objDatabase->Execute("UPDATE `".DBPREFIX."module_downloads_settings` SET `value` = '".addslashes($value)."' WHERE `name` = '".$key."'");
        }
    }

    public function getSettings()
    {
        return $this->arrConfig;
    }

    protected function getCategoryMenu(
        $accessType, $selectedCategory, $selectionText,
        $attrs=null, $categoryId=null)
    {
        global $_LANGID;

        $sortOrder   = $this->categoriesSortingOptions[$this->arrConfig['categories_sorting_order']];
        $objCategory = Category::getCategories(null, null, $sortOrder);
        $arrCategories = array();

        switch ($accessType) {
            case 'read':
                $accessCheckFunction = 'getReadAccessId';
                break;

            case 'add_subcategory':
                $accessCheckFunction = 'getAddSubcategoriesAccessId';
                break;
        }

        while (!$objCategory->EOF) {
            // TODO: getVisibility() < should only be checked if the user isn't an admin or so
            if ($objCategory->getVisibility() || \Permission::checkAccess($objCategory->getReadAccessId(), 'dynamic', true)) {
                $arrCategories[$objCategory->getParentId()][] = array(
                    'id'        => $objCategory->getId(),
                    'name'      => $objCategory->getName($_LANGID),
                    'owner_id'  => $objCategory->getOwnerId(),
                    'access_id' => $objCategory->{$accessCheckFunction}(),
                    'is_child'  => $objCategory->check4Subcategory($categoryId)
                );
            }

            $objCategory->next();
        }

        $menu = '<select name="downloads_category_parent_id"'.(!empty($attrs) ? ' '.$attrs : '').'>';
        $menu .= '<option value="0"'.(!$selectedCategory ? ' selected="selected"' : '').($accessType != 'read' && !\Permission::checkAccess(143, 'static', true) ? ' disabled="disabled"' : '').' style="border-bottom:1px solid #000;">'.$selectionText.'</option>';

        $menu .= $this->parseCategoryTreeForMenu($arrCategories, $selectedCategory, $categoryId);

        while (count($arrCategories)) {
            reset($arrCategories);
            $menu .= $this->parseCategoryTreeForMenu($arrCategories, $selectedCategory, $categoryId, key($arrCategories));
        }
        $menu .= '</select>';

        return $menu;
    }


    private function parseCategoryTreeForMenu(&$arrCategories, $selectedCategory, $categoryId = null, $parentId = 0, $level = 0)
    {
        $options = '';

        if (!isset($arrCategories[$parentId])) {
            return $options;
        }

        $length = count($arrCategories[$parentId]);
        for ($i = 0; $i < $length; $i++) {
            $options .= '<option value="'.$arrCategories[$parentId][$i]['id'].'"'
                    .($arrCategories[$parentId][$i]['id'] == $selectedCategory ? ' selected="selected"' : '')
                    .($arrCategories[$parentId][$i]['id'] == $categoryId || $arrCategories[$parentId][$i]['is_child'] ? ' disabled="disabled"' : (
                        // managers are allowed to see the content of every category
                        \Permission::checkAccess(143, 'static', true)
                        // the category isn't protected => everyone is allowed to the it's content
                        || !$arrCategories[$parentId][$i]['access_id']
                        // the category is protected => only those who have the sufficent permissions are allowed to see it's content
                        || \Permission::checkAccess($arrCategories[$parentId][$i]['access_id'], 'dynamic', true)
                        // the owner is allowed to see the content of the category
                        || ($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login() && $arrCategories[$parentId][$i]['owner_id'] == $objFWUser->objUser->getId() ? '' : ' disabled="disabled"')
                    )
                .'>'
                    .str_repeat('&nbsp;', $level * 4).htmlentities($arrCategories[$parentId][$i]['name'], ENT_QUOTES, CONTREXX_CHARSET)
                .'</option>';
            if (isset($arrCategories[$arrCategories[$parentId][$i]['id']])) {
                $options .= $this->parseCategoryTreeForMenu($arrCategories, $selectedCategory, $categoryId, $arrCategories[$parentId][$i]['id'], $level + 1);
            }
        }

        unset($arrCategories[$parentId]);

        return $options;
    }


    protected function getParsedCategoryListForDownloadAssociation( )
    {
        global $_LANGID;

        $sortOrder   = $this->categoriesSortingOptions[$this->arrConfig['categories_sorting_order']];
        $objCategory = Category::getCategories(null, null, $sortOrder);
        $arrCategories = array();

        while (!$objCategory->EOF) {
                $arrCategories[$objCategory->getParentId()][] = array(
                    'id'                    => $objCategory->getId(),
                    'name'                  => $objCategory->getName($_LANGID),
                    'owner_id'              => $objCategory->getOwnerId(),
                    'add_files_access_id'     => $objCategory->getAddFilesAccessId(),
                    'manage_files_access_id'  => $objCategory->getManageFilesAccessId()
                );

            $objCategory->next();
        }

       $arrParsedCategories = $this->parseCategoryTreeForDownloadAssociation($arrCategories);

        while (count($arrCategories)) {
            reset($arrCategories);
            $arrParsedCategories = array_merge($arrParsedCategories, $this->parseCategoryTreeForDownloadAssociation($arrCategories, key($arrCategories)));
        }

        return $arrParsedCategories;
    }


    private function parseCategoryTreeForDownloadAssociation(&$arrCategories, $parentId = 0, $level = 0, $parentName = '')
    {
        $arrParsedCategories = array();

        if (!isset($arrCategories[$parentId])) {
            return $arrParsedCategories;
        }

        $length = count($arrCategories[$parentId]);
        for ($i = 0; $i < $length; $i++) {
            $arrCategories[$parentId][$i]['name'] = $parentName.$arrCategories[$parentId][$i]['name'];
            $arrParsedCategories[] = array_merge($arrCategories[$parentId][$i], array('level' => $level));
            if (isset($arrCategories[$arrCategories[$parentId][$i]['id']])) {
                $arrParsedCategories = array_merge($arrParsedCategories, $this->parseCategoryTreeForDownloadAssociation($arrCategories, $arrCategories[$parentId][$i]['id'], $level + 1, $arrCategories[$parentId][$i]['name'].'\\'));
            }
        }

        unset($arrCategories[$parentId]);

        return $arrParsedCategories;
    }


    protected function getParsedUsername($userId)
    {
        global $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser->getUser($userId);
        if ($objUser) {
            if ($objUser->getProfileAttribute('firstname') || $objUser->getProfileAttribute('lastname')) {
                $author = $objUser->getProfileAttribute('firstname').' '.$objUser->getProfileAttribute('lastname').' ('.$objUser->getUsername().')';
            } else {
                $author = $objUser->getUsername();
            }
            $author = htmlentities($author, ENT_QUOTES, CONTREXX_CHARSET);
        } else {
            $author = $_ARRAYLANG['TXT_DOWNLOADS_UNKNOWN'];
        }
        return $author;
    }


    protected function getUserDropDownMenu($selectedUserId, $userId)
    {
        $menu = '<select name="downloads_category_owner_id" onchange="document.getElementById(\'downloads_category_owner_config\').style.display = this.value == '.$userId.' ? \'none\' : \'\'" style="width:300px;">';
        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser->getUsers(null, null, null, array('id', 'username', 'firstname', 'lastname'));
        while (!$objUser->EOF) {
            $menu .= '<option value="'.$objUser->getId().'"'.($objUser->getId() == $selectedUserId ? ' selected="selected"' : '').'>'.$this->getParsedUsername($objUser->getId()).'</option>';
            $objUser->next();
        }
        $menu .= '</select>';

        return $menu;
    }


    protected function getDownloadMimeTypeMenu($selectedType)
    {
        global $_ARRAYLANG;

        $menu = '<select name="downloads_download_mime_type" id="downloads_download_mime_type" style="width:300px;">';
        $arrMimeTypes = Download::$arrMimeTypes;
        foreach ($arrMimeTypes as $type => $arrMimeType) {
            $menu .= '<option value="'.$type.'"'.($type == $selectedType ? ' selected="selected"' : '').'>'.$_ARRAYLANG[$arrMimeType['description']].'</option>';
        }

        return $menu;
    }

    protected function getValidityMenu($validity, $expirationDate)
    {
//TODO:Use own methods instead of \FWUser::getValidityString() and \FWUser::getValidityMenuOptions()
        $menu = '<select name="downloads_download_validity" '.($validity && $expirationDate < time() ? 'onchange="this.style.color = this.value == \'current\' ? \'#f00\' : \'#000\'"' : null).' style="width:300px;'.($validity && $expirationDate < time() ? 'color:#f00;font-weight:normal;' : 'color:#000;').'">';
        if ($validity) {
            $menu .= '<option value="current" selected="selected" style="border-bottom:1px solid #000;'.($expirationDate < time() ? 'color:#f00;font-weight:normal;' : null).'">'.\FWUser::getValidityString($validity).' ('.date(ASCMS_DATE_FORMAT_DATE, $expirationDate).')</option>';
        }
        $menu .= \FWUser::getValidityMenuOptions(null, 'style="color:#000; font-weight:normal;"');
        $menu .= '</select>';
        return $menu;
    }

    public function setGroups($arrGroups, &$page_content)
    {
        global $_LANGID;

        $objGroup  = Group::getGroups(array('id' => $arrGroups));
        $sortOrder = $this->categoriesSortingOptions[$this->arrConfig['categories_sorting_order']];
        while (!$objGroup->EOF) {
            $output = "<ul>\n";
            $objCategory = Category::getCategories(array('id' => $objGroup->getAssociatedCategoryIds()), null, $sortOrder);
            while (!$objCategory->EOF) {
                $output .= '<li><a href="'.CONTREXX_SCRIPT_PATH.'?section=Downloads&amp;category='.$objCategory->getId().'" title="'.htmlentities($objCategory->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET).'">'.htmlentities($objCategory->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET)."</a></li>\n";
                $objCategory->next();
            }
            $output .= "</ul>\n";

            $page_content = str_replace('{'.$objGroup->getPlaceholder().'}', $output, $page_content);
            $objGroup->next();
        }
    }

    /**
     * parse the settings dropdown
     *
     * @param object $objTemplate   template object
     * @param array  $settingValues array of setting values
     * @param string $selected      selected dropdown value
     * @param string $blockName     block name for template parsing
     *
     * @return null
     */
    public function parseSettingsDropDown(
        \Cx\Core\Html\Sigma $objTemplate,
        $settingValues,
        $selected,
        $blockName
    ) {
        global $_ARRAYLANG;

        if (empty($settingValues)) {
            return;
        }

        foreach (array_keys($settingValues) as $key) {
            $selectedOption = ($selected == $key) ? 'selected="selected"' : '';
            $objTemplate->setVariable(array(
                'DOWNLOADS_SETTINGS_DROPDOWN_OPTION_VALUE'    => $key,
                'DOWNLOADS_SETTINGS_DROPDOWN_OPTION_NAME'     => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_'.  strtoupper($key).'_LABEL'],
                'DOWNLOADS_SETTINGS_DROPDOWN_SELECTED_OPTION' => $selectedOption,
            ));
            $objTemplate->parse('downloads_settings_sorting_dropdown_' . $blockName);
        }
    }
}

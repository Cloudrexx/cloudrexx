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
 * Digital Asset Management Library Exception
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 * @version     1.0.0
 */
class DownloadsLibraryException extends \Exception {};

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
        'associate_user_to_groups'      => '',
        'list_downloads_current_lang'   => 1,
        'integrate_into_search_component'=> 1,
        'auto_file_naming'     => 'off',
        'pretty_regex_pattern' => '',
        'global_search_linking'         => 'detail',
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

    /**
     * The locale in which the output shall be parsed for
     *
     * @var \Cx\Core\Locale\Model\Entity\Locale
     */
    protected static $outputLocale;

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
     */
    protected static function initOutputLocale() {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager();

        $locale = null;
        if (\Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
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
            } catch (\Doctrine\ORM\NoResultException $e) {}
        }

        if (!$locale) {
            // get currently selected frontend locale
            $locale = $em->find(
                'Cx\Core\Locale\Model\Entity\Locale',
                FRONTEND_LANG_ID
            );
        }

        if (!$locale) {
            throw new DownloadsLibraryException('Unable to initialize frontend locale');
        }

        static::$outputLocale = $locale;
    }

    protected function updateSettings()
    {
        global $objDatabase;

        foreach ($this->arrConfig as $key => $value) {
            $objDatabase->Execute('
                UPDATE
                    `' . DBPREFIX . 'module_downloads_settings`
                SET
                    `value` = "' . contrexx_input2db($value) . '"
                WHERE
                    `name` = "' . $key . '"
            ');
        }
        //clear Esi Cache
        static::clearEsiCache();
    }

    public function getSettings()
    {
        return $this->arrConfig;
    }

    protected function getCategoryMenu(
        $accessType, $selectedCategory, $selectionText,
        $attrs=null, $categoryId=null)
    {
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
                    'name'      => $objCategory->getName(),
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
        $sortOrder   = $this->categoriesSortingOptions[$this->arrConfig['categories_sorting_order']];
        $objCategory = Category::getCategories(null, null, $sortOrder);
        $arrCategories = array();

        while (!$objCategory->EOF) {
                $arrCategories[$objCategory->getParentId()][] = array(
                    'id'                    => $objCategory->getId(),
                    'name'                  => $objCategory->getName(),
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
        } else {
            $author = $_ARRAYLANG['TXT_DOWNLOADS_UNKNOWN'];
        }
        return $author;
    }


    protected function getUserDropDownMenu($selectedUserId, $userId)
    {
        $menu = '<select name="downloads_category_owner_id" onchange="document.getElementById(\'downloads_category_owner_config\').style.display = this.value == '.$userId.' ? \'none\' : \'\'" style="width:300px;">';
        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser->getUsers(null, null, null, array('id', 'username', 'firstname', 'lastname', 'email'));
        while (!$objUser->EOF) {
            $menu .= '<option value="'.$objUser->getId().'"'.($objUser->getId() == $selectedUserId ? ' selected="selected"' : '').'>'.contrexx_raw2xhtml($this->getParsedUsername($objUser->getId())).'</option>';
            $objUser->next();
        }
        $menu .= '</select>';

        return $menu;
    }


    protected function getDownloadMimeTypeMenu($selectedType)
    {
        global $_ARRAYLANG;

        $menu = '<select name="downloads_download_mime_type" id="downloads_download_mime_type" style="width:300px;display:block;">';
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

      /**
     * Get Group content by group id
     *
     * @param integer $id     group id
     * @param integer $langId Language id
     *
     * @return string
     */
    public function getGroupById($id, $langId)
    {
        if (empty($id)) {
            return '';
        }
        $group = Group::getGroup($id);

        if (!$group->getActiveStatus()) {
            return '';
        }

        $sortOrder = $this->categoriesSortingOptions[$this->arrConfig['categories_sorting_order']];
        $ulTag     = new \Cx\Core\Html\Model\Entity\HtmlElement('ul');
        $category  = Category::getCategories(
            array('id' => $group->getAssociatedCategoryIds()),
            null,
            $sortOrder
        );
        while (!$category->EOF) {
            $url = \Cx\Core\Routing\Url::fromModuleAndCmd(
                'Downloads',
                '',
                '',
                array('category' => $category->getId())
            )->toString();
            $linkText = contrexx_raw2xhtml($category->getName($langId));
            //Generate anchor tag
            $linkTag     = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
            $linkTag->setAttributes(array(
                'href'  => $url,
                'title' => $linkText
            ));
            $linkTag->addChild(new \Cx\Core\Html\Model\Entity\TextElement($linkText));
            $liTag       = new \Cx\Core\Html\Model\Entity\HtmlElement('li');
            $liTag->addChild($linkTag);
            $ulTag->addChild($liTag);
            $category->next();
        }

        return $ulTag;
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

    /**
     * Clear Esi Cache content
     */
    public static function clearEsiCache()
    {
        // clear ESI cache
        $groups       = Group::getGroups();
        $categories   = Category::getCategories();
        $widgetNames  = array_merge(
            $groups->getGroupsPlaceholders(),
            Category::getCategoryWidgetNames()
        );
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->getEvents()->triggerEvent(
            'clearEsiCache',
            array('Widget', $widgetNames)
        );

        // clear contrexx cache
        \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Downloads');

        // clear search cache
        \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Search');
    }

    /**
     * Get URL pointing to an application page of this component
     *
     * If one or more IDs of categories are supplied, then it will try to
     * point to an application identified by an ID of a category as its CMD.
     * 
     * @param   array   $categoryIds    Array of category IDs to look for a
     *                                  matching application page for.
     * @throws  DownloadsLibraryException   In case no valid application page
     *                                  is found, DownloadsLibraryException is
     *                                  thrown
     * @return  \Cx\Core\Routing\Url    URL pointing to an application page of
     *                                  this component
     */
    public static function getApplicationUrl($categories = array()) {
        try {
            $page = static::getApplicationPage($categories);
        } catch (DownloadsLibraryException $e) {
            throw $e;
        }

        $url = \Cx\Core\Routing\Url::fromPage($page);
        if (!$page->getCmd()) {
            $url->setParam('category', current($categories));
        }

        return $url;
    }

    /**
     * Find best matching application
     *
     * If one or more IDs of categories are supplied, then it will try to
     * find an application identified by an ID of a category as its CMD.
     *
     * @param   array   $categoryIds    Array of category IDs to look for a
     *                                  matching application page for.
     * @throws  DownloadsLibraryException   In case no valid application page
     *                                  is found, DownloadsLibraryException is
     *                                  thrown
     * @return  \Cx\Core\ContentManager\Model\Entity\Page   An application page
     *                                  of this component.
     */
    protected static function getApplicationPage($categoryIds = array()) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $pageRepo = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Core\ContentManager\Model\Entity\Page'
        );

        $langId = static::getOutputLocale()->getId();
        $page = null;

        while ($categoryId = array_shift($categoryIds)) {
            // fetch category specific application page
            // (i.e. section=Downloads&cmd=1337)
            $page = $pageRepo->findOneByModuleCmdLang(
                'Downloads',
                $categoryId,
                $langId
            );

            // verify that page is active
            if ($page && $page->isActive()) {
                return $page;
            }

            // add parent category ID to the list of possible
            // application pages
            $category = Category::getCategory($categoryId);
            if ($category->getParentId()) {
                $categoryIds[] = $category->getParentId();
            }
        }

        // fetch generic application page (section=Downloads)
        $page = $pageRepo->findOneByModuleCmdLang('Downloads', '', $langId);
        if ($page && $page->isActive()) {
            return $page;
        }

        throw new DownloadsLibraryException('No active application page found');
    }

    /**
     * Format a filename according to configuration option 'Pretty format'
     * of currently loaded downloads file.
     *
     * @param string $fileName  The filename to pretty format
     * @return string The pretty formatted filename. In case of any error
     *                 or if the function to pretty format is disabled,
     *                 then the original $filename is being returned.
     */
    public function getPrettyFormatFileName($fileName)
    {
        if (empty($fileName)) {
            return '';
        }

        // return original filename in case pretty format function is disabled
        if ($this->arrConfig['auto_file_naming'] == 'off') {
            return $fileName;
        }

        // check if a regexp is set
        $regexpConf = $this->arrConfig['pretty_regex_pattern'];

        // generate pretty formatted filename
        try {
            $regularExpression = new \Cx\Lib\Helpers\RegularExpression($regexpConf);
            $prettyFileName = $regularExpression->replace($fileName);

            // return pretty filename if conversion was successful
            if (!is_null($prettyFileName)) {
                return $prettyFileName;
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }

        // return original filename in case anything
        // didn't work out as expected
        return $fileName;
    }
}

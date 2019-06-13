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
 * DownloadsManager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 * @version     1.0.0
 * @todo        Document methods in PHPdoc blocks
 */
namespace Cx\Modules\Downloads\Controller;
/**
 * DownloadsManager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 * @version     1.0.0
 */
class DownloadsManager extends DownloadsLibrary
{
    /**
     * Template object
     * @access  private
     * @var     \Cx\Core\Html\Sigma
     */
    public $objTemplate;
   /**
     * Page title
     * @access private
     * @var string
     */
    public $_pageTitle;
    /**
     * Contains the info messages about done operations
     * @var array
     * @access private
     */
    private $arrStatusMsg = array('ok' => array(), 'error' => array());
    private $arrPermissionDependencies = array(
        'read' => array(
            'add_subcategories' => array(
                'manage_subcategories' => null
            ),
            'add_files' => array(
                'manage_files' => null
            )
        )
    );
    private $parentCategoryId = 0;

    private $act = '';

    /**
     * PHP5 constructor
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG;

        $this->objTemplate = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Downloads/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->objTemplate);
        $this->objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        parent::__construct();
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable('CONTENT_NAVIGATION',
            '<a href="index.php?cmd=Downloads" class="'.($this->act == '' ? 'active' : '').'">'.$_ARRAYLANG['TXT_DOWNLOADS_OVERVIEW'].'</a>'.
            '<a href="index.php?cmd=Downloads&amp;act=downloads" class="'.(($this->act == 'downloads' || $this->act == 'download') ? 'active' : '').'">'.$_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS'].'</a>'.
            '<a href="index.php?cmd=Downloads&amp;act=categories" class="'.(($this->act == 'categories' || $this->act == 'category') ? 'active' : '').'">'.$_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES'].'</a>'.
            (\Permission::checkAccess(142, 'static', true) ?
            '<a href="index.php?cmd=Downloads&amp;act=groups" class="'.(($this->act == 'groups' || $this->act == 'group') ? 'active' : '').'">'.$_ARRAYLANG['TXT_DOWNLOADS_GROUPS'].'</a>'.
            '<a href="index.php?cmd=Downloads&amp;act=settings" class="'.($this->act == 'settings' ? 'active' : '').'">'.$_ARRAYLANG['TXT_DOWNLOADS_SETTINGS'].'</a>' : '')
        );
    }


    /**
     * Set the backend page
     * @access public
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    public function getPage()
    {
        global $objTemplate, $_ARRAYLANG;

        \Permission::checkAccess(141, 'static');

        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        }
        $this->act = $_REQUEST['act'];

        if (!empty($_REQUEST['parent_id'])) {
            $this->parentCategoryId = intval($_REQUEST['parent_id']);
        } elseif (!empty($_REQUEST['downloads_category_parent_id'])) {
            $this->parentCategoryId = intval($_REQUEST['downloads_category_parent_id']);
        } else {
            $this->parentCategoryId = 0;
        }

        switch ($_REQUEST['act']) {
            case 'get':
                $this->getDownload();
                break;
            case 'delete_group':
                $this->deleteGroup();
                $this->loadGroupNavigation();
                $this->groups();
                $this->parseGroupNavigation();
                break;
            case 'switch_group_status':
                $this->switchGroupStatus();
                $this->loadGroupNavigation();
                $this->groups();
                $this->parseGroupNavigation();
                break;
            case 'groups':
                $this->loadGroupNavigation();
                $this->groups();
                $this->parseGroupNavigation();
                break;
            case 'group':
                $this->loadGroupNavigation();
                $this->group();
                $this->parseGroupNavigation();
                break;
            case 'delete_category':
                $this->deleteCategory();
                $this->loadCategoryNavigation();
                $this->categories();
                $this->parseCategoryNavigation();
                break;
            case 'switch_category_status':
                $this->switchCategoryStatus();
                $this->loadCategoryNavigation();
                $this->categories();
                $this->parseCategoryNavigation();
                break;
            case 'categories':
                $this->loadCategoryNavigation();
                $this->categories();
                $this->parseCategoryNavigation();
                break;
            case 'category':
                $this->loadCategoryNavigation();
                $this->category();
                $this->parseCategoryNavigation();
                break;
            case 'downloads':
                $this->loadDownloadNavigation();
                $this->downloads();
                $this->parseDownloadNavigation();
                break;
            case 'download':
                $this->loadDownloadNavigation();
                $this->download();
                $this->parseDownloadNavigation();
                break;
            case 'delete_download':
                $this->loadDownloadNavigation();
                $this->deleteDownload();
                $this->downloads();
                $this->parseDownloadNavigation();
                break;
            case 'switch_download_status':
                $this->switchDownloadStatus();
                if (!empty($_GET['parent_id'])) {
                    $this->loadCategoryNavigation();
                    $this->categories();
                    $this->parseCategoryNavigation();
                } else {
                    $this->loadDownloadNavigation();
                    $this->downloads();
                    $this->parseDownloadNavigation();
                }
                break;
            case 'unlink_download':
                $this->unlinkDownloadFromCategory();
                $this->loadCategoryNavigation();
                $this->categories();
                $this->parseCategoryNavigation();
                break;
            case 'add_new_download_to_category':
                $this->loadDownloadNavigation();
                $this->download();
                $this->parseDownloadNavigation();
                break;
            case 'add_downloads_to_category':
                if ($this->addDownloadsToCategory()) {
                    $this->loadCategoryNavigation();
                    $this->categories();
                    $this->parseCategoryNavigation();
                }
                break;
            case 'mailtemplate_edit':
            case 'mailtemplate_overview':
            case 'settings':
                $this->settings();
                break;
            case 'overview':
            default:
                $this->overview();
                break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->_pageTitle,
            'CONTENT_OK_MESSAGE'        => implode("<br />\n", $this->arrStatusMsg['ok']),
            'CONTENT_STATUS_MESSAGE'    => implode("<br />\n", $this->arrStatusMsg['error']),
            'ADMIN_CONTENT'             => $this->objTemplate->get()
        ));

        $this->setNavigation();
    }

    private function overview()
    {
        global $_ARRAYLANG;

        $objCategory = Category::getCategory($this->parentCategoryId);

        $this->_pageTitle = $_ARRAYLANG['TXT_DOWNLOADS_OVERVIEW'];
        $this->objTemplate->loadTemplateFile('module_downloads_overview.html');

        // get passed parameters
        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;

        $categoryLimitOffset = isset($_GET['category_pos']) ? intval($_GET['category_pos']) : $pos;
        $categoryOrderDirection = !empty($_GET['category_sort']) ? $_GET['category_sort'] : 'asc';
        $categoryOrderBy = !empty($_GET['category_by']) ? $_GET['category_by'] : '';

        $downloadLimitOffset = isset($_GET['download_pos']) ? intval($_GET['download_pos']) : $pos;
        $downloadOrderDirection = !empty($_GET['download_sort']) ? $_GET['download_sort'] : 'asc';
        $downloadOrderBy = !empty($_GET['download_by']) ? $_GET['download_by'] : 'order';

        $searchTerm = !empty($_REQUEST['search_term']) ? contrexx_input2raw($_REQUEST['search_term']) : '';

        // downloads
        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_DOWNLOADS'       => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS'],
            'TXT_DOWNLOADS_SEARCH_DOWNLOAD' => $_ARRAYLANG['TXT_DOWNLOADS_SEARCH_DOWNLOAD'],
            'DOWNLOADS_SEARCH_TERM'         => contrexx_raw2xhtml($searchTerm),
            'DOWNLOADS_CATEGORY_MENU'       => $this->getCategoryMenu('read', $objCategory->getId(), $_ARRAYLANG['TXT_DOWNLOADS_ALL_CATEGORIES']),
            'TXT_DOWNLOADS_SEARCH'          => $_ARRAYLANG['TXT_DOWNLOADS_SEARCH'],
        ));

        // categories
        $this->parseCategories($objCategory, $downloadOrderBy, $downloadOrderDirection, $downloadLimitOffset, $categoryOrderBy, $categoryOrderDirection, $categoryLimitOffset);

        if (!$objCategory->getId()) {
            $this->objTemplate->setVariable('TXT_DOWNLOADS_CATEGORIES', $_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES']);
        }

        $this->objTemplate->setGlobalVariable(array(
            'TXT_DOWNLOADS_EDIT'    => $_ARRAYLANG['TXT_DOWNLOADS_EDIT'],
            'TXT_DOWNLOADS_DELETE'  => $_ARRAYLANG['TXT_DOWNLOADS_DELETE']
        ));
    }

    private function getDownload()
    {
        $objDownload = new Download($this->arrConfig);
        $objDownload->load(!empty($_GET['id']) ? intval($_GET['id']) : 0);
        if (!$objDownload->EOF) {
            if (// download is protected
                $objDownload->getAccessId()
                // the user isn't a admin
                && !\Permission::checkAccess(143, 'static', true)
                // the user doesn't has access to this download
                && !\Permission::checkAccess($objDownload->getAccessId(), 'dynamic', true)
                // the user isn't the owner of the download
                && $objDownload->getOwnerId() != $this->userId
            ) {
                \Permission::noAccess();
            }

            $langId = DownloadsLibrary::getOutputLocale()->getId();
            if ($objDownload->getType() == 'file') {
                $objDownload->send($langId);
            } else {
                // add socket -> prevent to hide the source from the customer
                \Cx\Core\Csrf\Controller\Csrf::header('Location: '.$objDownload->getSource($langId));
            }
        }
    }

    private function switchGroupStatus()
    {
        $objGroup = Group::getGroup(isset($_GET['id']) ? intval($_GET['id']) : 0);
        if (!$objGroup->EOF) {
            $objGroup->setActiveStatus(!$objGroup->getActiveStatus());
            $objGroup->store();
        }
    }

    private function deleteGroup()
    {
        global $_ARRAYLANG;

        $objGroup = Group::getGroup(isset($_GET['id']) ? $_GET['id'] : 0);

        if (!$objGroup->EOF) {
            $name = '<strong>'.htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</strong>';
            if ($objGroup->delete()) {
                $this->arrStatusMsg['ok'][] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_GROUP_DELETE_SUCCESS'], $name);
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objGroup->getErrorMsg());
            }
        }
    }

    private function deleteGroups($arrGroupsIds)
    {
        global $_ARRAYLANG;

        $succeded = true;

        foreach ($arrGroupsIds as $groupId) {
            $objGroup = Group::getGroup($groupId);

            if (!$objGroup->EOF) {
                if (!$objGroup->delete()) {
                    $succeded = false;
                    $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objGroup->getErrorMsg());
                }
            }
        }

        if ($succeded) {
            $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_DOWNLOADS_GROUPS_DELETE_SUCCESS'];
        }
    }

    private function groups()
    {
        global $_ARRAYLANG, $_CONFIG;

        \Permission::checkAccess(142, 'static');

        $this->_pageTitle = $_ARRAYLANG['TXT_DOWNLOADS_GROUPS'];
        $this->objTemplate->addBlockFile('DOWNLOADS_GROUP_TEMPLATE', 'module_downloads_groups', 'module_downloads_groups.html');

        // parse groups multi action
        if (isset($_POST['downloads_group_select_action'])) {
            switch ($_POST['downloads_group_select_action']) {
                case 'delete':
                    $this->deleteGroups(isset($_POST['downloads_group_id']) && is_array($_POST['downloads_group_id']) ? $_POST['downloads_group_id'] : array());
                    break;
            }
        }

        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;

        $groupLimitOffset = isset($_GET['group_pos']) ? intval($_GET['group_pos']) : $pos;
        $groupOrderDirection = !empty($_GET['group_sort']) ? $_GET['group_sort'] : 'asc';
        $groupOrderBy = !empty($_GET['group_by']) ? $_GET['group_by'] : 'order';

        if ($groupOrderBy == 'placeholder') {
            $arrGroupOrder['id'] = $groupOrderDirection;
        } else {
            $arrGroupOrder[$groupOrderBy] = $groupOrderDirection;
        }

        if ($groupOrderBy != 'name') {
            $arrGroupOrder['name'] = 'asc';
        }
        if ($groupOrderBy != 'id' && $groupOrderBy != 'placeholder') {
            $arrGroupOrder['id'] = 'asc';
        }

        $objGroup= Group::getGroups(null, null, $arrGroupOrder, null, $_CONFIG['corePagingLimit'], $groupLimitOffset);

        $this->objTemplate->setGlobalVariable(array(
            'TXT_DOWNLOADS_EDIT'    => $_ARRAYLANG['TXT_DOWNLOADS_EDIT'],
            'TXT_DOWNLOADS_DELETE'  => $_ARRAYLANG['TXT_DOWNLOADS_DELETE']
        ));

        $nr = 0;
        if ($objGroup->EOF) {
            $this->objTemplate->setVariable('TXT_DOWNLOADS_NO_GROUPS_AVAILABLE', $_ARRAYLANG['TXT_DOWNLOADS_NO_GROUPS_AVAILABLE']);
            $this->objTemplate->parse('downloads_group_no_data');
            $this->objTemplate->hideBlock('downloads_group_data');
        } else {
            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_CHECK_ALL'       => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
                'TXT_DOWNLOADS_UNCHECK_ALL'     => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
                'TXT_DOWNLOADS_SELECT_ACTION'   => $_ARRAYLANG['TXT_DOWNLOADS_SELECT_ACTION'],
                'TXT_DOWNLOADS_DELETE'          => $_ARRAYLANG['TXT_DOWNLOADS_DELETE']
            ));

            // parse paging
            $groupCount = $objGroup->getFilteredSearchGroupCount();
            if ($groupCount > $_CONFIG['corePagingLimit']) {
                $pagingLink = "&cmd=Downloads&act=groups"
                    ."&group_sort=".htmlspecialchars($groupOrderDirection)
                    ."&group_by=".htmlspecialchars($groupOrderBy);
                $this->objTemplate->setVariable('DOWNLOADS_GROUP_PAGING', getPaging($groupCount, $groupLimitOffset, $pagingLink, "<b>".$_ARRAYLANG['TXT_DOWNLOADS_GROUPS']."</b>"));
            }

            while (!$objGroup->EOF) {
                // parse status link and modify button
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_GROUP_ID'                     => $objGroup->getId(),
                    'DOWNLOADS_GROUP_SWITCH_STATUS_DESC'     => $objGroup->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_DEACTIVATE_GROUP_DESC'] : $_ARRAYLANG['TXT_DOWNLOADS_ACTIVATE_GROUP_DESC'],
                    'DOWNLOADS_GROUP_SWITCH_STATUS_IMG_DESC' => $objGroup->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_DEACTIVATE_GROUP_DESC'] : $_ARRAYLANG['TXT_DOWNLOADS_ACTIVATE_GROUP_DESC']
                ));


                // parse delete button
                $this->objTemplate->setVariable(array(
                    'TXT_DOWNLOADS_DELETE'               => $_ARRAYLANG['TXT_DOWNLOADS_DELETE'],
                    'DOWNLOADS_GROUP_NAME_JS'            => htmlspecialchars($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET)
                ));

                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_GROUP_ROW_CLASS'          => $nr++ % 2 ? 'row1' : 'row2',
                    'DOWNLOADS_GROUP_STATUS_LED'         => $objGroup->getActiveStatus() ? 'led_green.gif' : 'led_red.gif',
                    'DOWNLOADS_GROUP_NAME'               => htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET),
                    'DOWNLOADS_GROUP_PLACEHOLDER'        => $objGroup->getPlaceholder()
                ));
                $this->objTemplate->parse('downloads_group_list');
                $objGroup->next();
            }

            $this->objTemplate->setVariable(array(
                // parse sorting
                'DOWNLOADS_GROUP_SORT_DIRECTION'         => $groupOrderDirection,
                'DOWNLOADS_GROUP_SORT_BY'                => $groupOrderBy,
                'DOWNLOADS_GROUP_SORT_ID'                => ($groupOrderBy == 'id' && $groupOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_GROUP_SORT_STATUS'            => ($groupOrderBy == 'is_active' && $groupOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_GROUP_SORT_NAME'              => ($groupOrderBy == 'name' && $groupOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_GROUP_SORT_PLACEHOLDER'       => ($groupOrderBy == 'placeholder' && $groupOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_GROUP_SORT_ID_LABEL'          => $_ARRAYLANG['TXT_DOWNLOADS_ID'].($groupOrderBy == 'id' ? $groupOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_GROUP_SORT_STATUS_LABEL'      => $_ARRAYLANG['TXT_DOWNLOADS_STATUS'].($groupOrderBy == 'is_active' ? $groupOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_GROUP_SORT_NAME_LABEL'        => $_ARRAYLANG['TXT_DOWNLOADS_NAME'].($groupOrderBy == 'name' ? $groupOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_GROUP_SORT_PLACEHOLDER_LABEL' => $_ARRAYLANG['TXT_DOWNLOADS_PLACEHOLDER'].($groupOrderBy == 'placeholder' ? $groupOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : '')
            ));

            $this->objTemplate->touchBlock('downloads_group_data');
            $this->objTemplate->hideBlock('downloads_group_no_data');

            $this->objTemplate->setVariable(array(
                'DOWNLOADS_GROUP_GROUP_SORT'             => $groupOrderDirection,
                'DOWNLOADS_GROUP_GROUP_SORT_BY'          => $groupOrderBy,
                'DOWNLOADS_GROUP_GROUP_OFFSET'           => $groupLimitOffset
            ));
        }

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_GROUPS'                      => $_ARRAYLANG['TXT_DOWNLOADS_GROUPS'],
            'TXT_DOWNLOADS_PLACEHOLDER'                 => $_ARRAYLANG['TXT_DOWNLOADS_PLACEHOLDER'],
            'TXT_DOWNLOADS_FUNCTIONS'                   => $_ARRAYLANG['TXT_DOWNLOADS_FUNCTIONS'],
            'TXT_DOWNLOADS_OPERATION_IRREVERSIBLE'      => $_ARRAYLANG['TXT_DOWNLOADS_OPERATION_IRREVERSIBLE'],
            'DOWNLOADS_CONFIRM_DELETE_GROUP_TXT'        => preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_DELETE_GROUP'])),
            'DOWNLOADS_CONFIRM_DELETE_GROUPS_TXT'       => preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_DELETE_GROUPS']))
        ));

        return true;
    }


    private function group()
    {
        global $_ARRAYLANG;

        \Permission::checkAccess(142, 'static');

        $arrAssociatedCategoryOptions = array();
        $arrNotAssociatedCategoryOptions = array();

        $objGroup = Group::getGroup(isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);

        if (isset($_POST['downloads_group_save'])) {
            $objGroup->setActiveStatus(isset($_POST['downloads_group_active']) && $_POST['downloads_group_active']);
            $objGroup->setNames(isset($_POST['downloads_group_name']) ? array_map('trim', array_map('contrexx_stripslashes', $_POST['downloads_group_name'])) : array());
            $objGroup->setType(isset($_POST['downloads_group_type']) ? contrexx_stripslashes($_POST['downloads_group_type']) : '');
            $objGroup->setInfoPage(isset($_POST['downloads_group_'.$objGroup->getType().'_source']) ? contrexx_stripslashes($_POST['downloads_group_'.$objGroup->getType().'_source']) : '');
            $objGroup->setCategories(isset($_POST['downloads_group_associated_categories']) ? array_map('intval', $_POST['downloads_group_associated_categories']) : array());

            if ($objGroup->store()) {
                return $this->groups();
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objGroup->getErrorMsg());
            }
        }

        $this->_pageTitle = $objGroup->getId() ? $_ARRAYLANG['TXT_DOWNLOADS_EDIT_GROUP'] : $_ARRAYLANG['TXT_DOWNLOADS_ADD_GROUP'];
        $this->objTemplate->addBlockFile('DOWNLOADS_GROUP_TEMPLATE', 'module_downloads_groups', 'module_downloads_group_modify.html');

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_ACTIVE'                                      => $_ARRAYLANG['TXT_DOWNLOADS_ACTIVE'],
            'TXT_DOWNLOADS_ASSIGNED_CATEGORIES'                         => $_ARRAYLANG['TXT_DOWNLOADS_ASSIGNED_CATEGORIES'],
            'TXT_DOWNLOADS_AVAILABLE_CATEGORIES'                        => $_ARRAYLANG['TXT_DOWNLOADS_AVAILABLE_CATEGORIES'],
            'TXT_DOWNLOADS_BROWSE'                                      => $_ARRAYLANG['TXT_DOWNLOADS_BROWSE'],
            'TXT_DOWNLOADS_CANCEL'                                      => $_ARRAYLANG['TXT_DOWNLOADS_CANCEL'],
            'TXT_DOWNLOADS_CATEGORIES'                                  => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES'],
            'TXT_DOWNLOADS_CHECK_ALL'                                   => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
            'TXT_DOWNLOADS_DETAIL_PAGE'                                 => $_ARRAYLANG['TXT_DOWNLOADS_DETAIL_PAGE'],
            'TXT_DOWNLOADS_EXTENDED'                                    => $_ARRAYLANG['TXT_DOWNLOADS_EXTENDED'],
            'TXT_DOWNLOADS_LOCAL_FILE'                                  => $_ARRAYLANG['TXT_DOWNLOADS_LOCAL_FILE'],
            'TXT_DOWNLOADS_NAME'                                        => $_ARRAYLANG['TXT_DOWNLOADS_NAME'],
            'TXT_DOWNLOADS_SAVE'                                        => $_ARRAYLANG['TXT_DOWNLOADS_SAVE'],
            'TXT_DOWNLOADS_STATUS'                                      => $_ARRAYLANG['TXT_DOWNLOADS_STATUS'],
            'TXT_DOWNLOADS_UNCHECK_ALL'                                 => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
            'TXT_DOWNLOADS_URL'                                         => $_ARRAYLANG['TXT_DOWNLOADS_URL']
        ));

        // parse sorting & paging of the groups overview section
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_GROUP_GROUP_SORT'          => !empty($_GET['group_sort']) ? $_GET['group_sort'] : '',
            'DOWNLOADS_GROUP_GROUP_SORT_BY'       => !empty($_GET['group_by']) ? $_GET['group_by'] : '',
            'DOWNLOADS_GROUP_GROUP_OFFSET'        => !empty($_GET['group_pos']) ? intval($_GET['group_pos']) : 0,
        ));

        // parse general attributes
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_GROUP_ID'                         => $objGroup->getId(),
            'DOWNLOADS_GROUP_OPERATION_TITLE'            => $objGroup->getId() ? $_ARRAYLANG['TXT_DOWNLOADS_EDIT_GROUP'] : $_ARRAYLANG['TXT_DOWNLOADS_ADD_GROUP'],
            'DOWNLOADS_GROUP_ACTIVE_CHECKED'             => $objGroup->getActiveStatus() ? 'checked="checked"' : ''
        ));

        // parse type
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_GROUP_TYPE_FILE_CHECKED'          => $objGroup->getType() == 'file' ? 'checked="checked"' : '',
            'DOWNLOADS_GROUP_TYPE_URL_CHECKED'           => $objGroup->getType() == 'url' ? 'checked="checked"' : '',
            'DOWNLOADS_GROUP_FILE_SOURCE'                => $objGroup->getType() == 'file' ? htmlentities($objGroup->getInfoPage(), ENT_QUOTES, CONTREXX_CHARSET) : '',
            'DOWNLOADS_GROUP_URL_SOURCE'                 => $objGroup->getType() == 'url' ? htmlentities($objGroup->getInfoPage(), ENT_QUOTES, CONTREXX_CHARSET) : 'http://',
            'DOWNLOADS_GROUP_TYPE_FILE_CONFIG_DISPLAY'   => $objGroup->getType() == 'file' ? '' : 'none',
            'DOWNLOADS_GROUP_TYPE_URL_CONFIG_DISPLAY'    => $objGroup->getType() == 'url' ? '' : 'none'
        ));

        // parse name and description attributres
        $arrLanguages = \FWLanguage::getLanguageArray();
        foreach ($arrLanguages as $langId => $arrLanguage) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_GROUP_NAME'       => htmlentities($objGroup->getName($langId), ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_GROUP_LANG_ID'    => $langId,
                'DOWNLOADS_GROUP_LANG_NAME'  => htmlentities($arrLanguage['name'], ENT_QUOTES, CONTREXX_CHARSET)
            ));
            $this->objTemplate->parse('downloads_group_name_list');
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_GROUP_NAME'   => htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_DOWNLOADS_EXTENDED' => $_ARRAYLANG['TXT_DOWNLOADS_EXTENDED']
        ));
        $this->objTemplate->parse('downloads_group_name');

        // parse associated categories
        $arrCategories = $this->getParsedCategoryListForDownloadAssociation();
        $arrAssociatedCategories = $objGroup->getAssociatedCategoryIds();
        $length = count($arrCategories);
        for ($i = 0; $i < $length; $i++) {
            $option = '<option value="'.$arrCategories[$i]['id'].'">'.htmlentities($arrCategories[$i]['name'], ENT_QUOTES, CONTREXX_CHARSET).'</option>';

            if (in_array($arrCategories[$i]['id'], $arrAssociatedCategories)) {
                $arrAssociatedCategoryOptions[] = $option;
            } else {
                $arrNotAssociatedCategoryOptions[] = $option;
            }
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_GROUP_ASSOCIATED_CATEGORIES'      => implode("\n", $arrAssociatedCategoryOptions),
            'DOWNLOADS_GROUP_NOT_ASSOCIATED_CATEGORIES'  => implode("\n", $arrNotAssociatedCategoryOptions),
            'DOWNLOADS_MEDIA_BROWSER_BUTTON'             => self::getMediaBrowserButton(null, 'sitestructure')
        ));

        return true;
    }


    private function deleteCategory()
    {
        global $_ARRAYLANG;

        $objCategory = Category::getCategory(isset($_GET['id']) ? $_GET['id'] : 0);

        if (!$objCategory->EOF) {
            $name = '<strong>'.htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</strong>';
            if ($objCategory->delete(isset($_GET['subcategories']) && $_GET['subcategories'] == 'true')) {
                $this->arrStatusMsg['ok'][] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_CATEGORY_DELETE_SUCCESS'], $name);
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objCategory->getErrorMsg());
            }
        }
    }


    private function deleteCategories($arrCategoryIds, $recursive = false)
    {
        global $_ARRAYLANG;

        $succeded = true;

        foreach ($arrCategoryIds as $categoryId) {
            $objCategory = Category::getCategory($categoryId);

            if (!$objCategory->EOF) {
                if (!$objCategory->delete($recursive)) {
                    $succeded = false;
                    $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objCategory->getErrorMsg());
                }
            }
        }

        if ($succeded) {
            $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES_DELETE_SUCCESS'];
        }
    }


    private function switchCategoryStatus()
    {
        $objCategory = Category::getCategory(isset($_GET['id']) ? intval($_GET['id']) : 0);
        if (!$objCategory->EOF) {
            $objCategory->setActiveStatus(!$objCategory->getActiveStatus());
            $objCategory->store();
        }
    }


    /**
     * category edit
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     */
    private function category()
    {
        global $_ARRAYLANG;

        $status = true;
        $objFWUser = \FWUser::getFWUserObject();
        $objCategory = Category::getCategory(isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);
        //$arrCategory = $this->getCategory(isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0);

        if (isset($_POST['downloads_category_save'])) {
            // check if user is allowed to change that stuff
            // check if the user is allowed to create a category within the selected parentId
            $status = $objCategory->setParentId(isset($_POST['downloads_category_parent_id']) ? intval($_POST['downloads_category_parent_id']) : 0);
            $objCategory->setActiveStatus(isset($_POST['downloads_category_active']) && $_POST['downloads_category_active']);
            $objCategory->setImage(isset($_POST['downloads_category_image']) ? contrexx_stripslashes($_POST['downloads_category_image']) : '');
            $objCategory->setVisibility((!isset($_POST['downloads_category_read']) || !$_POST['downloads_category_read']) || isset($_POST['downloads_category_visibility']) && $_POST['downloads_category_visibility']);
            $objCategory->setNames(isset($_POST['downloads_category_name']) ? array_map('trim', array_map('contrexx_stripslashes', $_POST['downloads_category_name'])) : array());
            $objCategory->setDescriptions(isset($_POST['downloads_category_description']) ? array_map('trim', array_map('contrexx_stripslashes', $_POST['downloads_category_description'])) : array());
            $objCategory->setDownloads(isset($_POST['downloads_category_associated_downloads']) ? array_map('intval', $_POST['downloads_category_associated_downloads']) : array());

            if (\Permission::checkAccess(143, 'static', true)) {
                $objCategory->setOwner(isset($_POST['downloads_category_owner_id']) ? intval($_POST['downloads_category_owner_id']) : $objFWUser->objUser->getId());
                $objCategory->setDeletableByOwner($objCategory->getOwnerId() == $objFWUser->objUser->getId() || isset($_POST['downloads_category_deletable_by_owner']) && $_POST['downloads_category_deletable_by_owner']);
                $objCategory->setModifyAccessByOwner($objCategory->getOwnerId() == $objFWUser->objUser->getId() || isset($_POST['downloads_category_manage_by_owner']) && $_POST['downloads_category_manage_by_owner']);
            }

            foreach ($this->arrPermissionTypes as $protectionType) {
                $arrCategoryPermissions[$protectionType] = array();
                $arrCategoryPermissions[$protectionType]['protected'] = isset($_POST['downloads_category_'.$protectionType]) && $_POST['downloads_category_'.$protectionType];
                $arrCategoryPermissions[$protectionType]['groups'] = !empty($_POST['downloads_category_'.$protectionType.'_associated_groups']) ? array_map('intval', $_POST['downloads_category_'.$protectionType.'_associated_groups']) : array();
            }

            $objCategory->setPermissionsRecursive(!empty($_POST['downloads_category_apply_recursive']));
            $objCategory->setPermissions($arrCategoryPermissions);

            if ($status && $objCategory->store()) {
                $this->parentCategoryId = $objCategory->getParentId();
                $this->loadCategoryNavigation();
                $this->categories();
                $this->parseCategoryNavigation();
                return;
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objCategory->getErrorMsg());
            }
        } else {
            $objCategory->setParentId($this->parentCategoryId);
        }

        $this->_pageTitle = $objCategory->getId() ? $_ARRAYLANG['TXT_DOWNLOADS_EDIT_CATEGORY'] : $_ARRAYLANG['TXT_DOWNLOADS_ADD_CATEGORY'];
        $this->objTemplate->addBlockFile('DOWNLOADS_CATEGORY_TEMPLATE', 'module_downloads_categories', 'module_downloads_category_modify.html');

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_GENERAL'                                     => $_ARRAYLANG['TXT_DOWNLOADS_GENERAL'],
            'TXT_DOWNLOADS_PERMISSIONS'                                 => $_ARRAYLANG['TXT_DOWNLOADS_PERMISSIONS'],
            'TXT_DOWNLOADS_NAME'                                        => $_ARRAYLANG['TXT_DOWNLOADS_NAME'],
            'TXT_DOWNLOADS_DESCRIPTION'                                 => $_ARRAYLANG['TXT_DOWNLOADS_DESCRIPTION'],
            'TXT_DOWNLOADS_CATEGORY'                                    => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORY'],
            'TXT_DOWNLOADS_ACTIVE'                                      => $_ARRAYLANG['TXT_DOWNLOADS_ACTIVE'],
            'TXT_DOWNLOADS_STATUS'                                      => $_ARRAYLANG['TXT_DOWNLOADS_STATUS'],
            'TXT_DOWNLOADS_OWNER'                                       => $_ARRAYLANG['TXT_DOWNLOADS_OWNER'],
            'TXT_DOWNLOADS_IMAGE'                                       => $_ARRAYLANG['TXT_DOWNLOADS_IMAGE'],
            'TXT_DOWNLOADS_CATEGORY_IMAGE'                              => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORY_IMAGE'],
            'TXT_DOWNLOADS_REMOVE_IMAGE'                                => $_ARRAYLANG['TXT_DOWNLOADS_REMOVE_IMAGE'],
            'TXT_DOWNLOADS_FILES'                                       => $_ARRAYLANG['TXT_DOWNLOADS_FILES'],
            'TXT_DOWNLOADS_AVAILABLE_USER_GROUPS'                       => $_ARRAYLANG['TXT_DOWNLOADS_AVAILABLE_USER_GROUPS'],
            'TXT_DOWNLOADS_ASSIGNED_USER_GROUPS'                        => $_ARRAYLANG['TXT_DOWNLOADS_ASSIGNED_USER_GROUPS'],
            'TXT_DOWNLOADS_CHECK_ALL'                                   => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
            'TXT_DOWNLOADS_UNCHECK_ALL'                                 => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
            'TXT_DOWNLOADS_VIEW_CONTENT'                                => $_ARRAYLANG['TXT_DOWNLOADS_VIEW_CONTENT'],
            'TXT_DOWNLOADS_SUBCATEGORIES'                               => $_ARRAYLANG['TXT_DOWNLOADS_SUBCATEGORIES'],
            'TXT_DOWNLOADS_ADD'                                         => $_ARRAYLANG['TXT_DOWNLOADS_ADD'],
            'TXT_DOWNLOADS_MANAGE'                                      => $_ARRAYLANG['TXT_DOWNLOADS_MANAGE'],
            'TXT_DOWNLOADS_CATEGORY_DELETABLE_BY_OWNER'                 => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORY_DELETABLE_BY_OWNER'],
            'TXT_DOWNLOADS_CATEGORY_MANAGE_BY_OWNER'                    => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORY_MANAGE_BY_OWNER'],
//            'TXT_DOWNLOADS_ADD_SUBCATEGORIES'                           => $_ARRAYLANG['TXT_DOWNLOADS_ADD_SUBCATEGORIES'],
//            'TXT_DOWNLOADS_MANAGE_SUBCATEGORIES'                        => $_ARRAYLANG['TXT_DOWNLOADS_MANAGE_SUBCATEGORIES'],
//            'TXT_DOWNLOADS_ADD_FILES'                                   => $_ARRAYLANG['TXT_DOWNLOADS_ADD_FILES'],
//            'TXT_DOWNLOADS_MANAGE_FILES'                                => $_ARRAYLANG['TXT_DOWNLOADS_MANAGE_FILES'],
            'TXT_DOWNLOADS_CANCEL'                                      => $_ARRAYLANG['TXT_DOWNLOADS_CANCEL'],
            'TXT_DOWNLOADS_SAVE'                                        => $_ARRAYLANG['TXT_DOWNLOADS_SAVE'],
            'TXT_DOWNLOADS_BROWSE'                                      => $_ARRAYLANG['TXT_DOWNLOADS_BROWSE'],
            'TXT_DOWNLOADS_CATEG0RY_VISIBILITY_DESC'                    => $_ARRAYLANG['TXT_DOWNLOADS_CATEG0RY_VISIBILITY_DESC'],
            'TXT_DOWNLOADS_READ_ALL_ACCESS_DESC'                        => $_ARRAYLANG['TXT_DOWNLOADS_READ_ALL_ACCESS_DESC'],
            'TXT_DOWNLOADS_READ_SELECTED_ACCESS_DESC'                   => $_ARRAYLANG['TXT_DOWNLOADS_READ_SELECTED_ACCESS_DESC'],
            'TXT_DOWNLOADS_ADD_SUBCATEGORIES_ALL_ACCESS_DESC'           => $_ARRAYLANG['TXT_DOWNLOADS_ADD_SUBCATEGORIES_ALL_ACCESS_DESC'],
            'TXT_DOWNLOADS_ADD_SUBCATEGORIES_SELECTED_ACCESS_DESC'      => $_ARRAYLANG['TXT_DOWNLOADS_ADD_SUBCATEGORIES_SELECTED_ACCESS_DESC'],
            'TXT_DOWNLOADS_MANAGE_SUBCATEGORIES_ALL_ACCESS_DESC'        => $_ARRAYLANG['TXT_DOWNLOADS_MANAGE_SUBCATEGORIES_ALL_ACCESS_DESC'],
            'TXT_DOWNLOADS_MANAGE_SUBCATEGORIES_SELECTED_ACCESS_DESC'   => $_ARRAYLANG['TXT_DOWNLOADS_MANAGE_SUBCATEGORIES_SELECTED_ACCESS_DESC'],
            'TXT_DOWNLOADS_ADD_FILES_ALL_ACCESS_DESC'                   => $_ARRAYLANG['TXT_DOWNLOADS_ADD_FILES_ALL_ACCESS_DESC'],
            'TXT_DOWNLOADS_ADD_FILES_SELECTED_ACCESS_DESC'              => $_ARRAYLANG['TXT_DOWNLOADS_ADD_FILES_SELECTED_ACCESS_DESC'],
            'TXT_DOWNLOADS_MANAGE_FILES_ALL_ACCESS_DESC'                => $_ARRAYLANG['TXT_DOWNLOADS_MANAGE_FILES_ALL_ACCESS_DESC'],
            'TXT_DOWNLOADS_MANAGE_FILES_SELECTED_ACCESS_DESC'           => $_ARRAYLANG['TXT_DOWNLOADS_MANAGE_FILES_SELECTED_ACCESS_DESC'],
            'TXT_DOWNLOADS_APPLY_PERMISSIONS_RECURSIVEJ'                => $_ARRAYLANG['TXT_DOWNLOADS_APPLY_PERMISSIONS_RECURSIVEJ'],
            'TXT_DOWNLOADS_DOWNLOADS'                                   => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS'],
            'TXT_DOWNLOADS_AVAILABLE_DOWNLOADS'                         => $_ARRAYLANG['TXT_DOWNLOADS_AVAILABLE_DOWNLOADS'],
            'TXT_DOWNLOADS_ASSIGNED_DOWNLOADS'                          => $_ARRAYLANG['TXT_DOWNLOADS_ASSIGNED_DOWNLOADS']
        ));
        // parse sorting & paging of the categories overview section
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CATEGORY_CATEGORY_SORT'          => !empty($_GET['category_sort']) ? $_GET['category_sort'] : '',
            'DOWNLOADS_CATEGORY_CATEGORY_SORT_BY'       => !empty($_GET['category_by']) ? $_GET['category_by'] : '',
            'DOWNLOADS_CATEGORY_DOWNLOAD_SORT'          => !empty($_GET['download_sort']) ? $_GET['download_sort'] : '',
            'DOWNLOADS_CATEGORY_DOWNLOAD_BY'            => !empty($_GET['download_by']) ? $_GET['download_by'] : '',
            'DOWNLOADS_CATEGORY_CATEGORY_OFFSET'        => !empty($_GET['category_pos']) ? intval($_GET['category_pos']) : 0,
            'DOWNLOADS_CATEGORY_DOWNLOAD_OFFSET'        => !empty($_GET['download_pos']) ? intval($_GET['download_pos']) : 0
        ));
        // parse general attributes
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CATEGORY_ID'                         => $objCategory->getId(),
            'DOWNLOADS_CATEGORY_PARENT_ID'                  => $objCategory->getParentId(),
            'DOWNLOADS_CATEGORY_OPERATION_TITLE'            => $objCategory->getId() ? $_ARRAYLANG['TXT_DOWNLOADS_EDIT_CATEGORY'] : $_ARRAYLANG['TXT_DOWNLOADS_ADD_CATEGORY'],
            'DOWNLOADS_CATEGORY_OWNER'                      => \Permission::checkAccess(143, 'static', true) ? $this->getUserDropDownMenu($objCategory->getOwnerId(), $objFWUser->objUser->getId()) : contrexx_raw2xhtml($this->getParsedUsername($objCategory->getOwnerId())),
            'DOWNLOADS_CATEGORY_OWNER_CONFIG_DISPLAY'       => \Permission::checkAccess(143, 'static', true) && $objCategory->getOwnerId() != $objFWUser->objUser->getId() ? '' : 'none',
            'DOWNLOADS_CATEGORY_DELETABLE_BY_OWNER_CHECKED' => $objCategory->getDeletableByOwner() ? 'checked="checked"' : '',
            'DOWNLOADS_CATEGORY_MANAGE_BY_OWNER_CHECKED'    => $objCategory->getModifyAccessByOwner() ? 'checked="checked"' : '',
            'DOWNLOADS_CATEGORY_ACTIVE_CHECKED'             => $objCategory->getActiveStatus() ? 'checked="checked"' : '',
            'DOWNLOADS_CATEGORY_VISIBILITY_CHECKED'         => $objCategory->getVisibility() ? 'checked="checked"' : ''
        ));

        // parse image attribute
        $image = $objCategory->getImage();
        if (!empty($image) && file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().'/'.$image)) {
            $thumb_name = \ImageManager::getThumbnailFilename($image);
            if (file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().'/'.$thumb_name)) {
                $imageSrc = $thumb_name;
            } else {
                $imageSrc = $image;
            }
        } else {
            $image = '';
            $imageSrc = $this->defaultCategoryImage['src'];
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CATEGORY_IMAGE'                  => $image,
            'DOWNLOADS_CATEGORY_IMAGE_SRC'              => $imageSrc,
            'DOWNLOADS_DEFAULT_CATEGORY_IMAGE'          => $this->defaultCategoryImage['src'],
            'DOWNLOADS_DEFAULT_CATEGORY_IMAGE_WIDTH'    => $this->defaultCategoryImage['width'].'px',
            'DOWNLOADS_DEFAULT_CATEGORY_IMAGE_HEIGHT'   => $this->defaultCategoryImage['height'].'px',
            'DOWNLOADS_CATEGORY_IMAGE_REMOVE_DISPLAY'   => empty($image) ? 'none' : '',
        ));

        // parse name and description attributres
        $arrLanguages = \FWLanguage::getLanguageArray();
        foreach ($arrLanguages as $langId => $arrLanguage) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_CATEGORY_NAME'       => htmlentities($objCategory->getName($langId), ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_CATEGORY_LANG_ID'    => $langId,
                'DOWNLOADS_CATEGORY_LANG_NAME'  => htmlentities($arrLanguage['name'], ENT_QUOTES, CONTREXX_CHARSET)
            ));
            $this->objTemplate->parse('downloads_category_name_list');

            $this->objTemplate->setVariable(array(
                'DOWNLOADS_CATEGORY_DESCRIPTION'        => htmlentities($objCategory->getDescription($langId), ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_CATEGORY_LANG_ID'            => $langId,
                'DOWNLOADS_CATEGORY_LANG_DESCRIPTION'   => htmlentities($arrLanguage['name'], ENT_QUOTES, CONTREXX_CHARSET)
            ));
            $this->objTemplate->parse('downloads_category_description_list');
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CATEGORY_NAME'   => htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_DOWNLOADS_EXTENDED'    => $_ARRAYLANG['TXT_DOWNLOADS_EXTENDED']
        ));
        $this->objTemplate->parse('downloads_category_name');

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CATEGORY_DESCRIPTION'    => htmlentities($objCategory->getDescription(), ENT_QUOTES, CONTREXX_CHARSET),
            'TXT_DOWNLOADS_EXTENDED'            => $_ARRAYLANG['TXT_DOWNLOADS_EXTENDED']
        ));
        $this->objTemplate->parse('downloads_category_description');

        // parse parent category menu
        $this->objTemplate->setVariable('DOWNLOADS_CATEGORY_PARENT_CATEGORY_MENU', $this->getCategoryMenu('add_subcategory', $objCategory->getParentId(), $_ARRAYLANG['TXT_DOWNLOADS_MAIN_CATEGORY'], 'style="width:300px;"', $objCategory->getId()));

        // parse download associations
        $arrAssociatedDownloads = array_keys($objCategory->getAssociatedDownloadIds());
        $associatedDownloads = '';
        $notAssociatedDownloads = '';
        $objDownload = new Download($this->arrConfig);
        $sortOrder   = $this->downloadsSortingOptions[$this->arrConfig['downloads_sorting_order']];
        $objDownload->loadDownloads(null, null, $sortOrder);
        while (!$objDownload->EOF) {
            $option = '<option value="'.$objDownload->getId().'">'.htmlentities($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</option>';
            if (in_array($objDownload->getId(), $arrAssociatedDownloads)) {
                $associatedDownloads .= $option;
            } else {
                $notAssociatedDownloads .= $option;
            }
            $objDownload->next();
        }
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CATEGORY_NOT_ASSOCIATED_DOWNLOADS'   => $notAssociatedDownloads,
            'DOWNLOADS_CATEGORY_ASSOCIATED_DOWNLOADS'       => $associatedDownloads
        ));

        // parse access permissions
        $arrPermissions = $objCategory->getPermissions();

        $objGroup = $objFWUser->objGroup->getGroups();
        while (!$objGroup->EOF) {
            $option = '<option value="'.$objGroup->getId().'">'.htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET).' ['.$objGroup->getType().']</option>';

            foreach ($this->arrPermissionTypes as $permissionType) {
                if (in_array($objGroup->getId(), $arrPermissions[$permissionType]['groups'])) {
                    $arrPermissions[$permissionType]['associated_groups'][] = $option;
                } else {
                    $arrPermissions[$permissionType]['not_associated_groups'][] = $option;
                }
            }
            $objGroup->next();
        }

        foreach ($arrPermissions as $permissionType => $arrPermissionType) {
            $permissionTypeUC = strtoupper($permissionType);
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_CATEGORY_'.$permissionTypeUC.'_ALL_CHECKED'              => !$arrPermissionType['protected'] ? 'checked="checked"' : '',
                'DOWNLOADS_CATEGORY_'.$permissionTypeUC.'_SELECTED_CHECKED'         => $arrPermissionType['protected'] ? 'checked="checked"' : '',
                'DOWNLOADS_CATEGORY_'.$permissionTypeUC.'_DISPLAY'                  => $arrPermissionType['protected'] ? '' : 'none',
                'DOWNLOADS_CATEGORY_'.$permissionTypeUC.'_NOT_ASSOCIATED_GROUPS'    => implode("\n", $arrPermissionType['not_associated_groups']),
                'DOWNLOADS_CATEGORY_'.$permissionTypeUC.'_ASSOCIATED_GROUPS'        => implode("\n", $arrPermissionType['associated_groups'])
            ));
        }
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CATEGORY_APPLY_RECURSIVE_CHECKED' => $objCategory->hasToSetPermissionsRecursive() ? 'checked="checked"' : '',
            'DOWNLOADS_MEDIA_BROWSER_BUTTON'             => self::getMediaBrowserButton(null, 'filebrowser')
        ));
        return true;
    }


    private function downloads()
    {
        global $_ARRAYLANG, $_CONFIG;

        $this->_pageTitle = $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS'];
        $this->objTemplate->addBlockFile('DOWNLOADS_DOWNLOAD_TEMPLATE', 'module_downloads_downloads', 'module_downloads_downloads_overview.html');
        $objFWUser = \FWUser::getFWUserObject();

        $limitOffset = isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $orderDirection = !empty($_GET['sort']) ? $_GET['sort'] : 'asc';
        $orderBy = !empty($_GET['by']) ? $_GET['by'] : '';
        $arrOrder = $this->downloadsSortingOptions[$this->arrConfig['downloads_sorting_order']];
        if (!empty($orderBy)) {
            $arrOrder = array_merge(
                // add custom sort flag as first element to array (to ensure
                // it has the highest precedence)
                array($orderBy => $orderDirection),
                // add default sort flags
                $arrOrder,
                // finnaly, do add the custom sort flag again. Adding it again
                // after the default sort flags will ensure that the custom
                // sort flags will overwrite the default sort flags
                array($orderBy => $orderDirection)
            );
        }

        //$categoryId = !empty($_REQUEST['category_id']) ? intval($_REQUEST['category_id']) : 0;
        $searchTerm = !empty($_GET['search_term']) ? contrexx_input2raw($_GET['search_term']) : '';
        $pagingLink = !empty($searchTerm) ? '&search_term='.contrexx_raw2encodedUrl($searchTerm, true) : '';
        $pagingLink .= (!empty($_GET['act']) && $_GET['act'] == 'downloads') ? '&downloads_category_parent_id=' . $this->parentCategoryId : '';
        if (isset($_POST['downloads_download_select_action'])) {
            switch ($_POST['downloads_download_select_action']) {
                case 'order':
                    $this->updateDownloadOrder(isset($_POST['downloads_download_order']) && is_array($_POST['downloads_download_order']) ? $_POST['downloads_download_order'] : array());
                    break;

                case 'delete':
                    $this->deleteDownloads(isset($_POST['downloads_download_id']) && is_array($_POST['downloads_download_id']) ? $_POST['downloads_download_id'] : array());
                    break;
            }
        }

        $objCategory = Category::getCategory($this->parentCategoryId);

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_FILTER'          => $_ARRAYLANG['TXT_DOWNLOADS_FILTER'],
            'TXT_DOWNLOADS_SEARCH_DOWNLOAD' => $_ARRAYLANG['TXT_DOWNLOADS_SEARCH_DOWNLOAD'],
            'DOWNLOADS_SEARCH_TERM'         => contrexx_raw2xhtml($searchTerm),
            'DOWNLOADS_CATEGORY_MENU'       => $this->getCategoryMenu('read', $objCategory->getId(), $_ARRAYLANG['TXT_DOWNLOADS_ALL_CATEGORIES']),
            'TXT_DOWNLOADS_SEARCH'          => $_ARRAYLANG['TXT_DOWNLOADS_SEARCH'],
        ));

        $filter = null;
        if($objCategory->getId() > 0) {
            $filter = array('category_id' => $objCategory->getId());
        }

        $objDownload = new Download($this->arrConfig);
        $objDownload->loadDownloads(
            $filter, $searchTerm, $arrOrder, null,
            $_CONFIG['corePagingLimit'], $limitOffset, true
        );
        if ($objDownload->EOF) {
            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_DOWNLOADS'               => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS'],
                'TXT_DOWNLOADS_NO_DOWNLOADS_ENTERED'    => $_ARRAYLANG['TXT_DOWNLOADS_NO_DOWNLOADS_ENTERED'],
                'TXT_DOWNLOADS_ADD_NEW_DOWNLOAD'        => $_ARRAYLANG['TXT_DOWNLOADS_ADD_NEW_DOWNLOAD']
            ));
            $this->objTemplate->parse('downloads_download_no_data');
            $this->objTemplate->hideBlock('downloads_download_data');
            return;
        } else {
            $this->objTemplate->hideBlock('downloads_download_no_data');
        }

        $this->objTemplate->setGlobalVariable(array(
            'TXT_DOWNLOADS_EDIT'    => $_ARRAYLANG['TXT_DOWNLOADS_EDIT'],
            'TXT_DOWNLOADS_DELETE'  => $_ARRAYLANG['TXT_DOWNLOADS_DELETE']
        ));

        // parse sorting
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_SORT_DIRECTION'      => $orderDirection,
            'DOWNLOADS_SORT_BY'             => $orderBy,
            'DOWNLOADS_SORT_ID'             => ($orderBy == 'id' && $orderDirection == 'asc') ? 'desc' : 'asc',
            'DOWNLOADS_SORT_STATUS'         => ($orderBy == 'is_active' && $orderDirection == 'asc') ? 'desc' : 'asc',
            'DOWNLOADS_SORT_ORDER'          => ($orderBy == 'order' && $orderDirection == 'asc') ? 'desc' : 'asc',
            'DOWNLOADS_SORT_NAME'           => ($orderBy == 'name' && $orderDirection == 'asc') ? 'desc' : 'asc',
            'DOWNLOADS_SORT_DESCRIPTION'    => ($orderBy == 'description' && $orderDirection == 'asc') ? 'desc' : 'asc',
            'DOWNLOADS_SORT_DOWNLOADED'     => ($orderBy == 'download_count' && $orderDirection == 'desc') ? 'asc' : 'desc',
            'DOWNLOADS_SORT_VIEWED'         => ($orderBy == 'views' && $orderDirection == 'desc') ? 'asc' : 'desc',
            //'DOWNLOADS_SORT_SOURCE'         => ($orderBy == 'source' && $orderDirection == 'asc') ? 'desc' : 'asc',
            'DOWNLOADS_ID'                  => $_ARRAYLANG['TXT_DOWNLOADS_ID'].($orderBy == 'id' ? $orderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
            'DOWNLOADS_STATUS'              => $_ARRAYLANG['TXT_DOWNLOADS_STATUS'].($orderBy == 'is_active' ? $orderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
            'DOWNLOADS_ORDER'               => $_ARRAYLANG['TXT_DOWNLOADS_ORDER'].($orderBy == 'order' ? $orderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
            'DOWNLOADS_NAME'                => $_ARRAYLANG['TXT_DOWNLOADS_NAME'].($orderBy == 'name' ? $orderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
            'DOWNLOADS_DESCRIPTION'         => $_ARRAYLANG['TXT_DOWNLOADS_DESCRIPTION'].($orderBy == 'description' ? $orderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
            'DOWNLOADS_DOWNLOADED'          => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADED'].($orderBy == 'download_count' ? $orderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
            'DOWNLOADS_VIEWED'              => $_ARRAYLANG['TXT_DOWNLOADS_VIEWED'].($orderBy == 'views' ? $orderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
            //'DOWNLOADS_SOURCE'              => $_ARRAYLANG['TXT_DOWNLOADS_SOURCE'].($orderBy == 'source' ? $orderDirection == 'asc' ? ' &uarr;' : ' &darr;' : '')
        ));

        $this->objTemplate->setVariable(array(
            'TXT_SAVE_CHANGES'                      => $_ARRAYLANG['TXT_SAVE_CHANGES'],
            'TXT_DOWNLOADS_OWNER'                   => $_ARRAYLANG['TXT_DOWNLOADS_OWNER'],
            'TXT_DOWNLOADS_CHANGE_SORT_DIRECTION'   => $_ARRAYLANG['TXT_DOWNLOADS_CHANGE_SORT_DIRECTION'],
            'TXT_DOWNLOADS_DOWNLOADS'               => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS'],
            'TXT_DOWNLOADS_FUNCTIONS'               => $_ARRAYLANG['TXT_DOWNLOADS_FUNCTIONS'],
            //'TXT_DOWNLOADS_DOWNLOAD'                => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD'],
            'TXT_DOWNLOADS_CHECK_ALL'               => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
            'TXT_DOWNLOADS_UNCHECK_ALL'             => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
            'TXT_DOWNLOADS_SELECT_ACTION'           => $_ARRAYLANG['TXT_DOWNLOADS_SELECT_ACTION'],
            'DOWNLOADS_CONFIRM_DELETE_DOWNLOAD_TXT' => preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_DELETE_DOWNLOAD'])),
            'DOWNLOADS_CONFIRM_DELETE_DOWNLOADS_TXT'    => preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_DELETE_DOWNLOADS'])),
            'TXT_DOWNLOADS_OPERATION_IRREVERSIBLE'  => $_ARRAYLANG['TXT_DOWNLOADS_OPERATION_IRREVERSIBLE']
        ));


        //$this->parseLetterIndexList('index.php?cmd=Access&amp;act=user&amp;groupId='.$groupId.'&amp;user_status_filter='.$userStatusFilter.'&amp;user_role_filter='.$userRoleFilter, 'username_filter', $usernameFilter);

        $downloadCount = $objDownload->getFilteredSearchDownloadCount();
        if ($downloadCount > $_CONFIG['corePagingLimit']) {
            $sorting = '';
            if (!$searchTerm){
                $sorting = "&sort=".htmlspecialchars($orderDirection)."&by=".htmlspecialchars($orderBy);
        }

            $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_PAGING', getPaging($downloadCount, $limitOffset, '&cmd=Downloads&act=downloads'.$sorting.$pagingLink, "<b>".$_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS']."</b>"));

        }


        if (// managers are allowed to change the sort order
            \Permission::checkAccess(143, 'static', true)
        ) {
            $changeOrderAllowed = true;
            $this->objTemplate->setVariable('TXT_DOWNLOADS_ORDER', $_ARRAYLANG['TXT_DOWNLOADS_ORDER']);
            $this->objTemplate->parse('downloads_download_change_order_action');
        } else {
            $changeOrderAllowed = false;
            $this->objTemplate->hideBlock('downloads_download_change_order_action');
        }


        $nr = 0;
        while (!$objDownload->EOF) {
            $description = $objDownload->getDescription();
            if (strlen($description) > 100) {
                $description = substr($description, 0, 97).'...';
            }

//            $source = $objDownload->getSource();
//            if (strlen($source) > 100) {
//                $source = substr($source, 0, 97).'...';
//            }

            // parse order nr
            if ($changeOrderAllowed) {
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_ID'     => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_ORDER'  => $objDownload->getOrder(),
                ));
                $this->objTemplate->parse('downloads_download_order_modify');
                $this->objTemplate->hideBlock('downloads_download_order_no_modify');
            } else {
                    $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_ORDER', $objDownload->getOrder());
                    $this->objTemplate->parse('downloads_download_order_no_modify');
                    $this->objTemplate->hideBlock('downloads_download_order_modify');
            }

            if (// managers are allowed to modify/delete downloads
                \Permission::checkAccess(143, 'static', true)
                // to owner of the download is allowed to modify/delete it
                || $objDownload->getOwnerId() == $objFWUser->objUser->getId()
            ) {
                // parse select checkbox
                $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_ID', $objDownload->getId());
                $this->objTemplate->parse('downloads_download_checkbox');

                // parse status link
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_ID'                 => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_SWITCH_STATUS_DESC' => $objDownload->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_DEACTIVATE_DOWNLOAD_DESC'] : $_ARRAYLANG['TXT_DOWNLOADS_ACTIVATE_DOWNLOAD_DESC']
                ));
                $this->objTemplate->parse('downloads_download_status_link_open');
                $this->objTemplate->touchBlock('downloads_download_status_link_close');

                // parse modify link
                $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_ID', $objDownload->getId());
                $this->objTemplate->parse('downloads_download_modify_link_open');
                $this->objTemplate->touchBlock('downloads_download_modify_link_close');

                // parse functions
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_ID'                 => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_NAME_JS'            => htmlspecialchars($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET)
                ));

                $this->objTemplate->parse('downloads_download_functions');
                $this->objTemplate->hideBlock('downloads_download_no_functions');
            } else {
                $this->objTemplate->hideBlock('downloads_download_checkbox');
                $this->objTemplate->hideBlock('downloads_download_status_link_open');
                $this->objTemplate->hideBlock('downloads_download_status_link_close');
                $this->objTemplate->hideBlock('downloads_download_modify_link_open');
                $this->objTemplate->hideBlock('downloads_download_modify_link_close');
                $this->objTemplate->hideBlock('downloads_download_functions');
                $this->objTemplate->touchBlock('downloads_download_no_functions');
            }

            if (// download isn't protected -> everyone is allowed to download it
                !$objDownload->getAccessId()
                // the owner of the download is allowed to download it
                || $objDownload->getOwnerId() == $objFWUser->objUser->getId()
                // the download is protected -> only those who have the sufficent permissions are allowed to download it
                || \Permission::checkAccess($objDownload->getAccessId(), 'dynamic', true)
                // managers are allowed to download every download
                || \Permission::checkAccess(143, 'static', true)
            ) {
                // parse download function
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_ID'                 => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_ICON'      => $objDownload->getIcon(true),
                    'DOWNLOADS_DOWNLOAD_SOURCE'             => htmlentities($objDownload->getSource(), ENT_QUOTES, CONTREXX_CHARSET)
                ));

                $this->objTemplate->parse('downloads_download_function_download');
                $this->objTemplate->hideBlock('downloads_download_no_function_download');
            } else {
                $this->objTemplate->hideBlock('downloads_download_function_download');
                $this->objTemplate->touchBlock('downloads_download_no_function_download');
            }

            $this->objTemplate->setVariable(array(
                'DOWNLOADS_DOWNLOAD_ROW_CLASS'              => $nr++ % 2 ? 'row1' : 'row2',
                'DOWNLOADS_DOWNLOAD_ID'                     => $objDownload->getId(),
                'DOWNLOADS_DOWNLOAD_SWITCH_STATUS_IMG_DESC' => $objDownload->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_ACTIVE'] : $_ARRAYLANG['TXT_DOWNLOADS_INACTIVE'],
                'DOWNLOADS_DOWNLOAD_STATUS_LED'             => $objDownload->getActiveStatus() ? 'led_green.gif' : 'led_red.gif',
                //'DOWNLOADS_DOWNLOAD_ICON'                   => $objDownload->getIcon(),
                'DOWNLOADS_DOWNLOAD_NAME'                   => htmlentities($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_DOWNLOAD_DESCRIPTION'            => htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_DOWNLOAD_OWNER'                  => contrexx_raw2xhtml($this->getParsedUsername($objDownload->getOwnerId())),
                'DOWNLOADS_DOWNLOAD_DOWNLOADED'             => $objDownload->getDownloadCount(),
                'DOWNLOADS_DOWNLOAD_VIEWED'                 => $objDownload->getViewCount()
                //'DOWNLOADS_DOWNLOAD_SOURCE'                 => htmlentities($source, ENT_QUOTES, CONTREXX_CHARSET),
            ));
            $this->objTemplate->parse('downloads_download_list');
            $objDownload->next();
        }
    }

    private function loadDownloadNavigation()
    {
        $this->objTemplate->loadTemplateFile('module_downloads_downloads.html');
    }

    private function parseDownloadNavigation()
    {
        global $_ARRAYLANG;

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_OVERVIEW'        => $_ARRAYLANG['TXT_DOWNLOADS_OVERVIEW'],
            'TXT_DOWNLOADS_ADD_DOWNLOAD'    => $_ARRAYLANG['TXT_DOWNLOADS_ADD_DOWNLOAD'],
        ));

        $this->objTemplate->parse('module_downloads_downloads');
    }

    private function deleteDownload()
    {
        global $_ARRAYLANG;

        $objDownload = new Download($this->arrConfig);
        $objDownload->load(isset($_GET['id']) ? $_GET['id'] : 0);

        if (!$objDownload->EOF) {
            $name = '<strong>'.htmlentities($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</strong>';
            if ($objDownload->delete()) {
                $this->arrStatusMsg['ok'][] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_DELETE_SUCCESS'], $name);
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objDownload->getErrorMsg());
            }
        }
    }


    private function deleteDownloads($arrDownloadIds)
    {
        global $_ARRAYLANG;

        $succeded = true;

        $objDownload = new Download($this->arrConfig);
        foreach ($arrDownloadIds as $downloadId) {
            $objDownload->load($downloadId);

            if (!$objDownload->EOF) {
                if (!$objDownload->delete()) {
                    $succeded = false;
                    $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objDownload->getErrorMsg());
                }
            }
        }

        if ($succeded) {
            $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS_DELETE_SUCCESS'];
        }
    }


    private function switchDownloadStatus()
    {
        $objDownload = new Download($this->arrConfig);
        $objDownload->load(isset($_GET['id']) ? intval($_GET['id']) : 0);
        if (!$objDownload->EOF) {
            $objDownload->setActiveStatus(!$objDownload->getActiveStatus());
            $objDownload->store();
        }
    }


    private function unlinkDownloadFromCategory()
    {
        $categoryId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
        $objDownload = new Download($this->arrConfig);
        $objDownload->load(isset($_GET['id']) ? intval($_GET['id']) : 0);
        if (!$objDownload->EOF) {
            $arrCategoryAssociations = $objDownload->getAssociatedCategoryIds();
            unset($arrCategoryAssociations[array_search($categoryId, $arrCategoryAssociations)]);
            $objDownload->setCategories($arrCategoryAssociations);
            $objDownload->store();
        }
    }


    private function unlinkDownloadsFromCategory($objCategory, $arrUnlinkDownloadIds)
    {
        $arrDownloadIds = array_keys($objCategory->getAssociatedDownloadIds());
        $objCategory->setDownloads(array_diff($arrDownloadIds, $arrUnlinkDownloadIds));

        if ($objCategory->storeDownloadAssociations()) {
            //clear Esi Cache
            static::clearEsiCache();
            return true;
        } else {
            $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objCategory->getErrorMsg());
            return false;
        }
    }


    private function addDownloadsToCategory()
    {
        global $_ARRAYLANG;

        $objCategory = Category::getCategory(!empty($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : 0);
        if ($objCategory->EOF) {
            return true;
        }

        $objFWUser = \FWUser::getFWUserObject();

        // check permissions
        if (!\Permission::checkAccess(143, 'static', true)
            && ($objCategory->getReadAccessId() && !\Permission::checkAccess($objCategory->getReadAccessId(), 'dynamic', true))
            && $objCategory->getOwnerId() != $objFWUser->objUser->getId()
        ) {
            return \Permission::noAccess();
        }

        if (isset($_POST['downloads_category_save_downloads'])) {
            $objCategory->setDownloads(isset($_POST['downloads_category_associated_downloads']) ? array_map('intval', $_POST['downloads_category_associated_downloads']) : array());

            if ($objCategory->storeDownloadAssociations()) {
                //clear Esi Cache
                static::clearEsiCache();
                return true;
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objCategory->getErrorMsg());
            }
        }

        $pageTitle = sprintf($_ARRAYLANG['TXT_DOWNLOADS_ADD_DOWNLOADS_TO_CATEGORY'], htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET));
        $this->_pageTitle = $pageTitle;
        $this->objTemplate->loadTemplateFile('module_downloads_category_add_downloads.html');

        // parse download associations
        $arrAssociatedDownloads = array_keys($objCategory->getAssociatedDownloadIds());
        $hasRemoveRight = \Permission::checkAccess(143, 'static', true) || $objCategory->getId() && (!$objCategory->getManageFilesAccessId() || \Permission::checkAccess($objCategory->getManageFilesAccessId(), 'dynamic', true) || $objCategory->getOwnerId() == $objFWUser->objUser->getId());
        $associatedDownloads = '';
        $notAssociatedDownloads = '';
        $objDownload = new Download($this->arrConfig);
        $sortOrder   = $this->downloadsSortingOptions[$this->arrConfig['downloads_sorting_order']];
        $objDownload->loadDownloads(null, null, $sortOrder);
        while (!$objDownload->EOF) {
            if (!\Permission::checkAccess(143, 'static', true) && !$objDownload->getVisibility() && $objDownload->getOwnerId() != $objFWUser->objUser->getId()) {
                $objDownload->next();
                continue;
            }

            if (in_array($objDownload->getId(), $arrAssociatedDownloads)) {
                $associatedDownloads .= '<option value="'.$objDownload->getId().'"'.($hasRemoveRight || $objDownload->getOwnerId() == $objFWUser->objUser->getId() ? '' : ' disabled="disabled"').'>'.htmlentities($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</option>';
            } else {
                $notAssociatedDownloads .= '<option value="'.$objDownload->getId().'">'.htmlentities($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</option>';
            }

            $objDownload->next();
        }
        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_CANCEL'                          => $_ARRAYLANG['TXT_DOWNLOADS_CANCEL'],
            'TXT_DOWNLOADS_SAVE'                            => $_ARRAYLANG['TXT_DOWNLOADS_SAVE'],
            'TXT_DOWNLOADS_AVAILABLE_DOWNLOADS'             => $_ARRAYLANG['TXT_DOWNLOADS_AVAILABLE_DOWNLOADS'],
            'TXT_DOWNLOADS_ASSIGNED_DOWNLOADS'              => $_ARRAYLANG['TXT_DOWNLOADS_ASSIGNED_DOWNLOADS'],
            'TXT_DOWNLOADS_CHECK_ALL'                       => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
            'TXT_DOWNLOADS_UNCHECK_ALL'                     => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
            'DOWNLOADS_CATEGORY_ID'                         => $objCategory->getId(),
            'DOWNLOADS_ADD_DOWNLOADS_TO_CATEGORY_TXT'       => $pageTitle,
            'DOWNLOADS_CATEGORY_NOT_ASSOCIATED_DOWNLOADS'   => $notAssociatedDownloads,
            'DOWNLOADS_CATEGORY_ASSOCIATED_DOWNLOADS'       => $associatedDownloads,
            // parse sorting & paging of the categories overview section
            'DOWNLOADS_CATEGORY_SORT'                       => !empty($_GET['category_sort']) ? $_GET['category_sort'] : '',
            'DOWNLOADS_CATEGORY_SORT_BY'                    => !empty($_GET['category_by']) ? $_GET['category_by'] : '',
            'DOWNLOADS_DOWNLOAD_SORT'                       => !empty($_GET['download_sort']) ? $_GET['download_sort'] : '',
            'DOWNLOADS_DOWNLOAD_BY'                         => !empty($_GET['download_by']) ? $_GET['download_by'] : '',
            'DOWNLOADS_CATEGORY_OFFSET'                     => !empty($_GET['category_pos']) ? intval($_GET['category_pos']) : 0,
            'DOWNLOADS_DOWNLOAD_OFFSET'                     => !empty($_GET['download_pos']) ? intval($_GET['download_pos']) : 0
        ));
        return false;
    }


    private function updateDownloadOrder($arrDownloadOrder)
    {
        global $_ARRAYLANG;

        $arrFailedDownloads = array();

        $objDownload = new Download($this->arrConfig);
        foreach ($arrDownloadOrder as $downloadId => $orderNr) {
            $objDownload->load($downloadId);
            if (!$objDownload->EOF) {
                $objDownload->setOrder($orderNr);
                if (!$objDownload->store()) {
                    $arrFailedDownloads[] = htmlentities($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET);
                }
            }
        }

        if (count($arrFailedDownloads)) {
            $this->arrStatusMsg['error'][] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_ORDER_SET_FAILED'], implode(', ', $arrFailedDownloads));
        } else {
            $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_ORDER_SET_SUCCESS'];
        }
    }


    private function download()
    {
        global $_ARRAYLANG;

        $id = isset($_REQUEST['id']) ? contrexx_input2int($_REQUEST['id']) : 0;
        $objFWUser = \FWUser::getFWUserObject();
        $objDownload = new Download($this->arrConfig);
        $objDownload->load($id);

        \ContrexxJavascript::getInstance()->setVariable(
            array('autoFileNaming' => $this->arrConfig['auto_file_naming']),
            'downloads'
        );
        if ($objDownload->getId()
            && !\Permission::checkAccess(143, 'static', true)
            && (($objFWUser = \FWUser::getFWUserObject()) == false || !$objFWUser->objUser->login() || $objDownload->getOwnerId() != $objFWUser->objUser->getId())
        ) {
            $this->arrStatusMsg['error'][] = $_ARRAYLANG['TXT_DOWNLOADS_MODIFY_DOWNLOAD_PROHIBITED'];
            return $this->downloads();
        }

        $arrAssociatedGroupOptions = array();
        $arrNotAssociatedGroupOptions = array();
        $arrAssociatedGroups = array();
        $arrAssociatedCategoryOptions = array();
        $arrNotAssociatedCategoryOptions = array();
        $arrAssociatedCategories = array();
        $arrAssociatedDownloadOptions = array();
        $arrNotAssociatedDownloadOptions = array();

        if (isset($_POST['downloads_download_save'])) {
            // language logic
            $db = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
            $selectedLanguages = isset($_POST['downloads_download_language']) ? array_keys($_POST['downloads_download_language']) : array();
            $availableLanguages = \FWLanguage::getActiveFrontendLanguages();
            $unselectedLanguages = array();
            foreach ($availableLanguages as $language) {
                if (in_array($language['id'], $selectedLanguages)) {
                    continue;
                }
                $query = 'DELETE FROM `' . DBPREFIX . '_module_downloads_download_locale` WHERE `download_id` = ' . $objDownload->getId() . ' AND `lang_id` = ' . $language['id'];
                $db->Execute($query);
                array_push($unselectedLanguages, intval($language['id']));
            }

            // language specific attributes
            $objDownload->setNames(isset($_POST['downloads_download_name']) ? $this->filterArray($unselectedLanguages, array_map('trim', array_map('contrexx_stripslashes', $_POST['downloads_download_name']))) : array());
            $objDownload->setDescriptions(isset($_POST['downloads_download_description']) ? $this->filterArray($unselectedLanguages, array_map('trim', array_map('contrexx_stripslashes', $_POST['downloads_download_description']))) : array());
            $this->arrConfig['use_attr_metakeys'] ? $objDownload->setMetakeys(isset($_POST['downloads_download_metakeys']) ? $this->filterArray($unselectedLanguages, array_map('trim', array_map('contrexx_stripslashes', $_POST['downloads_download_metakeys']))) : array()) : null;
            $objDownload->setType(isset($_POST['downloads_download_type']) ? contrexx_stripslashes($_POST['downloads_download_type']) : '');
            $objDownload->setSources(isset($_POST['downloads_download_' . $objDownload->getType() . '_source']) ? $this->filterArray($unselectedLanguages, array_map('trim', array_map('contrexx_stripslashes', $_POST['downloads_download_' . $objDownload->getType() . '_source']))) : array());

            // language non-specific attributes
            $objDownload->setActiveStatus(!empty($_POST['downloads_download_is_active']));
            $objDownload->setMimeType(isset($_POST['downloads_download_mime_type']) ? contrexx_stripslashes($_POST['downloads_download_mime_type']) : '');
            $this->arrConfig['use_attr_size'] ? $objDownload->setSize(isset($_POST['downloads_download_size']) ? intval($_POST['downloads_download_size']) : '') : null;
            $this->arrConfig['use_attr_license'] ? $objDownload->setLicense(isset($_POST['downloads_download_license']) ? contrexx_stripslashes($_POST['downloads_download_license']) : '') : null;
            $this->arrConfig['use_attr_version'] ? $objDownload->setVersion(isset($_POST['downloads_download_version']) ? contrexx_stripslashes($_POST['downloads_download_version']) : '') : null;
            $this->arrConfig['use_attr_author'] ? $objDownload->setAuthor(isset($_POST['downloads_download_author']) ? contrexx_stripslashes($_POST['downloads_download_author']) : '') : null;
            $this->arrConfig['use_attr_website'] ? $objDownload->setWebsite(isset($_POST['downloads_download_website']) ? contrexx_stripslashes($_POST['downloads_download_website']) : '') : null;
            $objDownload->setImage(isset($_POST['downloads_download_image']) ? contrexx_stripslashes($_POST['downloads_download_image']) : '');
            $objDownload->setValidityTimePeriod(!empty($_POST['downloads_download_validity']) ? intval($_POST['downloads_download_validity']) : 0);
            $objDownload->setVisibility(!empty($_POST['downloads_download_visibility']));
            $objDownload->setProtection(!empty($_POST['downloads_download_access']));
            $objDownload->setGroups(
                   $objDownload->getProtection()
                && !empty($_POST['downloads_download_access_associated_groups'])
                      ? array_map('intval', $_POST['downloads_download_access_associated_groups'])
                      : array()
            );
            $objDownload->setCategories(!empty($_POST['downloads_download_associated_categories']) ? array_map('intval', $_POST['downloads_download_associated_categories']) : array(0));
            $objDownload->setDownloads(!empty($_POST['downloads_download_associated_downloads']) ? array_map('intval', $_POST['downloads_download_associated_downloads']) : array());

            $objDownload->updateMTime();
            if ($objDownload->store(null, $selectedLanguages)) {
                if (!empty($this->parentCategoryId)) {
                    header('location: '.\Cx\Core\Csrf\Controller\Csrf::enhanceURI('index.php?cmd=Downloads&act=categories&parent_id='.$this->parentCategoryId));
                } else {
                    return $this->downloads();
                }
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objDownload->getErrorMsg());
            }
        }

        $this->_pageTitle = $objDownload->getId() ? $_ARRAYLANG['TXT_DOWNLOADS_EDIT_DOWNLOAD'] : $_ARRAYLANG['TXT_DOWNLOADS_ADD_DOWNLOAD'];
        $this->objTemplate->addBlockFile('DOWNLOADS_DOWNLOAD_TEMPLATE', 'module_downloads_downloads', 'module_downloads_download_modify.html');

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_GENERAL'                         => $_ARRAYLANG['TXT_DOWNLOADS_GENERAL'],
            'TXT_DOWNLOADS_ADDITIONAL_OPTIONS'              => $_ARRAYLANG['TXT_DOWNLOADS_ADDITIONAL_OPTIONS'],
            'TXT_DOWNLOADS_PERMISSIONS'                     => $_ARRAYLANG['TXT_DOWNLOADS_PERMISSIONS'],
            'TXT_DOWNLOADS_DOWNLOAD_VISIBILITY_DESC'        => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_VISIBILITY_DESC'],
            'TXT_DOWNLOADS_BROWSE'                          => $_ARRAYLANG['TXT_DOWNLOADS_BROWSE'],
            'TXT_DOWNLOADS_STATUS'                          => $_ARRAYLANG['TXT_DOWNLOADS_STATUS'],
            'TXT_DOWNLOADS_VALIDITY_EXPIRATION'             => $_ARRAYLANG['TXT_DOWNLOADS_VALIDITY_EXPIRATION'],
            'TXT_DOWNLOADS_ACTIVE'                          => $_ARRAYLANG['TXT_DOWNLOADS_ACTIVE'],
            'TXT_DOWNLOADS_TYPE'                            => $_ARRAYLANG['TXT_DOWNLOADS_TYPE'],
            'TXT_DOWNLOADS_SIZE'                            => $_ARRAYLANG['TXT_DOWNLOADS_SIZE'],
            'TXT_DOWNLOADS_LICENSE'                         => $_ARRAYLANG['TXT_DOWNLOADS_LICENSE'],
            'TXT_DOWNLOADS_VERSION'                         => $_ARRAYLANG['TXT_DOWNLOADS_VERSION'],
            'TXT_DOWNLOADS_AUTHOR'                          => $_ARRAYLANG['TXT_DOWNLOADS_AUTHOR'],
            'TXT_DOWNLOADS_WEBSITE'                         => $_ARRAYLANG['TXT_DOWNLOADS_WEBSITE'],
            'TXT_DOWNLOADS_IMAGE'                           => $_ARRAYLANG['TXT_DOWNLOADS_IMAGE'],
            'TXT_DOWNLOADS_CATEGORIES'                      => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES'],
            'TXT_DOWNLOADS_AVAILABLE_CATEGORIES'            => $_ARRAYLANG['TXT_DOWNLOADS_AVAILABLE_CATEGORIES'],
            'TXT_DOWNLOADS_ASSIGNED_CATEGORIES'             => $_ARRAYLANG['TXT_DOWNLOADS_ASSIGNED_CATEGORIES'],
            'TXT_DOWNLOADS_RELATED_DOWNLOADS'               => $_ARRAYLANG['TXT_DOWNLOADS_RELATED_DOWNLOADS'],
            'TXT_DOWNLOADS_AVAILABLE_DOWNLOADS'             => $_ARRAYLANG['TXT_DOWNLOADS_AVAILABLE_DOWNLOADS'],
            'TXT_DOWNLOADS_ASSIGNED_DOWNLOADS'              => $_ARRAYLANG['TXT_DOWNLOADS_ASSIGNED_DOWNLOADS'],
            'TXT_DOWNLOADS_DOWNLOAD_ALL_ACCESS_DESC'        => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_ALL_ACCESS_DESC'],
            'TXT_DOWNLOADS_DOWNLOAD_SELECTED_ACCESS_DESC'   => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_SELECTED_ACCESS_DESC'],
            'TXT_DOWNLOADS_AVAILABLE_USER_GROUPS'           => $_ARRAYLANG['TXT_DOWNLOADS_AVAILABLE_USER_GROUPS'],
            'TXT_DOWNLOADS_ASSIGNED_USER_GROUPS'            => $_ARRAYLANG['TXT_DOWNLOADS_ASSIGNED_USER_GROUPS'],
            'TXT_DOWNLOADS_CHECK_ALL'                       => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
            'TXT_DOWNLOADS_UNCHECK_ALL'                     => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
            'TXT_DOWNLOADS_CANCEL'                          => $_ARRAYLANG['TXT_DOWNLOADS_CANCEL'],
            'TXT_DOWNLOADS_SAVE'                            => $_ARRAYLANG['TXT_DOWNLOADS_SAVE'],
            'TXT_DOWNLOADS_LANGUAGE'                        => $_ARRAYLANG['TXT_DOWNLOADS_LANGUAGE'],
            'TXT_DOWNLOADS_CONTENT'                         => $_ARRAYLANG['TXT_DOWNLOADS_CONTENT'],
        ));

        // parse sorting & paging of the categories overview section
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_DOWNLOAD_CATEGORY_SORT'          => !empty($_GET['category_sort']) ? $_GET['category_sort'] : '',
            'DOWNLOADS_DOWNLOAD_CATEGORY_SORT_BY'       => !empty($_GET['category_by']) ? $_GET['category_by'] : '',
            'DOWNLOADS_DOWNLOAD_DOWNLOAD_SORT'          => !empty($_GET['download_sort']) ? $_GET['download_sort'] : '',
            'DOWNLOADS_DOWNLOAD_DOWNLOAD_BY'            => !empty($_GET['download_by']) ? $_GET['download_by'] : '',
            'DOWNLOADS_DOWNLOAD_CATEGORY_OFFSET'        => !empty($_GET['category_pos']) ? intval($_GET['category_pos']) : 0,
            'DOWNLOADS_DOWNLOAD_DOWNLOAD_OFFSET'        => !empty($_GET['download_pos']) ? intval($_GET['download_pos']) : 0
        ));

        // parse id
        $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_ID', $objDownload->getId());

        // parse name and description attributres
        $arrLanguages = \FWLanguage::getActiveFrontendLanguages();
        foreach ($arrLanguages as $langId => $arrLanguage) {
            $isSelectedLang = !empty($objDownload->getName($langId, true));
            if (empty($id) && $langId == DownloadsLibrary::getOutputLocale()->getId()) {
                $isSelectedLang = true;
            }

            $this->objTemplate->setVariable(array(
                'DOWNLOADS_DOWNLOAD_LANGUAGE_ID' => $langId,
                'DOWNLOADS_DOWNLOAD_LANGUAGE_NAME' => contrexx_raw2xhtml($arrLanguage['name']),
                'DOWNLOADS_DOWNLOAD_LANGUAGE_SHORT' => contrexx_raw2xhtml($arrLanguage['lang']),
                'DOWNLOADS_DOWNLOAD_LANGUAGE_CHECKED' => $isSelectedLang ? 'checked="checked"' : '',
            ));
            $this->objTemplate->parse('downloads_download_language_list');

            $this->objTemplate->setVariable(array(
                'DOWNLOADS_DOWNLOAD_LANGUAGE_ID' => $langId,
                'DOWNLOADS_DOWNLOAD_LANGUAGE_NAME' => contrexx_raw2xhtml($arrLanguage['name']),
                'DOWNLOADS_DOWNLOAD_LANGUAGE_ACTIVE' => !$isSelectedLang ? 'style="display:none;"' : '',
            ));
            $this->objTemplate->parse('downloads_download_language_tab');

            if ($this->arrConfig['use_attr_metakeys']) {
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_METAKEYS' => htmlentities($objDownload->getMetakeys($langId), ENT_QUOTES, CONTREXX_CHARSET),
                    'DOWNLOADS_DOWNLOAD_LANG_METAKEYS' => htmlentities($arrLanguage['name'], ENT_QUOTES, CONTREXX_CHARSET)
                ));
            }

            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_LOCAL_FILE' => $_ARRAYLANG['TXT_DOWNLOADS_LOCAL_FILE'],
                'TXT_DOWNLOADS_URL' => $_ARRAYLANG['TXT_DOWNLOADS_URL'],
                'TXT_DOWNLOADS_BROWSE' => $_ARRAYLANG['TXT_DOWNLOADS_BROWSE'],
                'TXT_DOWNLOADS_NAME' => $_ARRAYLANG['TXT_DOWNLOADS_NAME'],
                'TXT_DOWNLOADS_DESCRIPTION' => $_ARRAYLANG['TXT_DOWNLOADS_DESCRIPTION'],
                'TXT_DOWNLOADS_SOURCE' => $_ARRAYLANG['TXT_DOWNLOADS_SOURCE'],
                'TXT_DOWNLOADS_METAKEYS' => $_ARRAYLANG['TXT_DOWNLOADS_METAKEYS'],
                'DOWNLOADS_DOWNLOAD_LANG_ID' => $langId,
                'DOWNLOADS_DOWNLOAD_DESCRIPTION' => htmlentities($objDownload->getDescription($langId), ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_DOWNLOAD_LANG_DESCRIPTION' => htmlentities($arrLanguage['name'], ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_DOWNLOAD_NAME' => htmlentities($objDownload->getName($langId), ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_DOWNLOAD_LANG_NAME' => htmlentities($arrLanguage['name'], ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_DOWNLOAD_FILE_SOURCE' => $objDownload->getType() == 'file' ? htmlentities($objDownload->getSource($langId), ENT_QUOTES, CONTREXX_CHARSET) : '',
                'DOWNLOADS_DOWNLOAD_URL_SOURCE' => $objDownload->getType() == 'url' ? htmlentities($objDownload->getSource($langId), ENT_QUOTES, CONTREXX_CHARSET) : 'http://',
                'DOWNLOADS_DOWNLOAD_TYPE_FILE_CONFIG_DISPLAY' => $objDownload->getType() == 'file' ? 'block' : 'none',
                'DOWNLOADS_DOWNLOAD_TYPE_URL_CONFIG_DISPLAY' => $objDownload->getType() == 'url' ? 'block' : 'none',
                'DOWNLOADS_DOWNLOAD_TYPE_FILE_CHECKED' => $objDownload->getType() == 'file' ? 'checked="checked"' : '',
                'DOWNLOADS_DOWNLOAD_TYPE_URL_CHECKED' => $objDownload->getType() == 'url' ? 'checked="checked"' : '',
            ));

            // parse metakeys
            if ($this->arrConfig['use_attr_metakeys']) {
                $this->objTemplate->touchBlock('downloads_download_metakeys');
            } else {
                $this->objTemplate->hideBlock('downloads_download_metakeys');
            }

            $this->objTemplate->parse('downloads_download_language_content');
        }

        // parse type
        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_BROWSE'                          => $_ARRAYLANG['TXT_DOWNLOADS_BROWSE'],
            'TXT_DOWNLOADS_EXTENDED'                        => $_ARRAYLANG['TXT_DOWNLOADS_EXTENDED'],
        ));

        foreach (Download::$arrMimeTypes as $mimeType => $arrMimeType) {
            if (!count($arrMimeType['extensions'])) {
                continue;
            }
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_MIME_TYPE'               => $mimeType,
                'DOWNLOADS_FILE_EXTENSION_REGEXP'   => implode('|', $arrMimeType['extensions'])
            ));
            $this->objTemplate->parse('downloads_download_file_ext_regexp');
        }

        // parse mime type
        $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_MIME_TYPE_MENU', $this->getDownloadMimeTypeMenu($objDownload->getMimeType()));

        $attrRow = 0;

        // parse size
        if ($this->arrConfig['use_attr_size']) {
            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_BYTES'               => $_ARRAYLANG['TXT_DOWNLOADS_BYTES'],
                'DOWNLOADS_DOWNLOAD_ATTRIBUTE_ROW'  => $attrRow++ % 2 + 1,
                'DOWNLOADS_DOWNLOAD_SIZE'           => $objDownload->getSize()
            ));
            $this->objTemplate->parse('downloads_download_attr_size');
        } else {
            $this->objTemplate->hideBlock('downloads_download_attr_size');
        }

        // parse license
        if ($this->arrConfig['use_attr_license']) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_DOWNLOAD_ATTRIBUTE_ROW'  => $attrRow++ % 2 + 1,
                'DOWNLOADs_DOWNLOAD_LICENSE'        => htmlentities($objDownload->getLicense(), ENT_QUOTES, CONTREXX_CHARSET)
            ));
            $this->objTemplate->parse('downloads_download_attr_license');
        } else {
            $this->objTemplate->hideBlock('downloads_download_attr_license');
        }

        // parse version
        if ($this->arrConfig['use_attr_version']) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_DOWNLOAD_ATTRIBUTE_ROW'  => $attrRow++ % 2 + 1,
                'DOWNLOADS_DOWNLOAD_VERSION'        => htmlentities($objDownload->getVersion(), ENT_QUOTES, CONTREXX_CHARSET)
            ));
            $this->objTemplate->parse('downloads_download_attr_version');
        } else {
            $this->objTemplate->hideBlock('downloads_download_attr_version');
        }

        // parse author
        if ($this->arrConfig['use_attr_author']) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_DOWNLOAD_ATTRIBUTE_ROW'  => $attrRow++ % 2 + 1,
                'DOWNLOADS_DOWNLOAD_AUTHOR'         => htmlentities($objDownload->getAuthor(), ENT_QUOTES, CONTREXX_CHARSET)
            ));
            $this->objTemplate->parse('downloads_download_attr_author');
        } else {
            $this->objTemplate->hideBlock('downloads_download_attr_author');
        }

        // parse website
        if ($this->arrConfig['use_attr_website']) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_DOWNLOAD_ATTRIBUTE_ROW'  => $attrRow++ % 2 + 1,
                'DOWNLOADS_DOWNLOAD_WEBSITE'        => htmlentities($objDownload->getWebsite(), ENT_QUOTES, CONTREXX_CHARSET)
            ));
            $this->objTemplate->parse('downloads_download_attr_website');
        } else {
            $this->objTemplate->hideBlock('downloads_download_attr_website');
        }

        // parse validity expiration menu
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_DOWNLOAD_ATTRIBUTE_ROW'              => $attrRow++ % 2 + 1,
            'DOWNLOADS_DOWNLOAD_VALIDITY_EXPIRATION_MENU'   => $this->getValidityMenu($objDownload->getValidityTimePeriod(), $objDownload->getExpirationDate())
        ));

        // parse active status
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_DOWNLOAD_IS_ACTIVE_CHECKED'  => $objDownload->getActiveStatus() ? 'checked="checked"' : ''
        ));

        // parse image attribute
        $image = $objDownload->getImage();
        if (!empty($image) && file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().'/'.$image)) {
            $thumb_name = \ImageManager::getThumbnailFilename($image);
            if (file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().'/'.$thumb_name)) {
                $imageSrc = $thumb_name;
            } else {
                $imageSrc = $image;
            }
        } else {
            $image = '';
            $imageSrc = $this->defaultDownloadImage['src'];
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_DOWNLOAD_IMAGE'                  => $image,
            'DOWNLOADS_DOWNLOAD_IMAGE_SRC'              => $imageSrc,
            'DOWNLOADS_DEFAULT_DOWNLOAD_IMAGE'          => $this->defaultDownloadImage['src'],
            'DOWNLOADS_DEFAULT_DOWNLOAD_IMAGE_WIDTH'    => $this->defaultDownloadImage['width'].'px',
            'DOWNLOADS_DEFAULT_DOWNLOAD_IMAGE_HEIGHT'   => $this->defaultDownloadImage['height'].'px',
            'DOWNLOADS_DOWNLOAD_IMAGE_REMOVE_DISPLAY'   => empty($image) ? 'none' : ''
        ));

        // parse associated categories
        $arrCategories = $this->getParsedCategoryListForDownloadAssociation();
        $arrAssociatedCategories = $objDownload->getAssociatedCategoryIds();
        $length = count($arrCategories);
        for ($i = 0; $i < $length; $i++) {
            if (// managers are allowed to change the category association
                \Permission::checkAccess(143, 'static', true)
                // the download isn't associated with the category
                || !in_array($arrCategories[$i]['id'], $arrAssociatedCategories) && (
                    // everyone is allowed to associate new files with this category
                    !$arrCategories[$i]['add_files_access_id']
                    // only those who have the sufficent permissions are allowed to add new files to this category
                    || \Permission::checkAccess($arrCategories[$i]['add_files_access_id'], 'dynamic', true)
                )
                // the download is associated with the category
                || in_array($arrCategories[$i]['id'], $arrAssociatedCategories) && (
                    // every body is allowd to delete file associations of this category
                    !$arrCategories[$i]['manage_files_access_id']
                    // only those with sufficent permissions are allowed to delete file associations of this category
                    || \Permission::checkAccess($arrCategories[$i]['manage_files_access_id'], 'dynamic', true)
                )
                // the owner is allowed to change the file associations of the category
                || $objFWUser->objUser->login() && $arrCategories[$i]['owner_id'] == $objFWUser->objUser->getId()
            ) {
                $disabled = false;
            } else {
                $disabled = true;
            }
            $option = '<option value="'.$arrCategories[$i]['id'].'"'.($disabled ? ' disabled="disabled"' : '').'>'.htmlentities($arrCategories[$i]['name'], ENT_QUOTES, CONTREXX_CHARSET).'</option>';

            if (   in_array($arrCategories[$i]['id'], $arrAssociatedCategories)
                   // automatically assign a new download to the currently viewed category
                || (!$objDownload->getId() && $arrCategories[$i]['id'] == $this->parentCategoryId)
            ) {
                $arrAssociatedCategoryOptions[] = $option;
            } else {
                $arrNotAssociatedCategoryOptions[] = $option;
            }
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_DOWNLOAD_ASSOCIATED_CATEGORIES'  => implode("\n", $arrAssociatedCategoryOptions),
            'DOWNLOADS_DOWNLOAD_NOT_ASSOCIATED_CATEGORIES'  => implode("\n", $arrNotAssociatedCategoryOptions)
        ));

        // parse related downloads
        $arrRelatedDownloads = $objDownload->getRelatedDownloadIds();
        $objAvailableDownload = new Download($this->arrConfig);
        $sortOrder = $this->downloadsSortingOptions[$this->arrConfig['downloads_sorting_order']];
        $objAvailableDownload->loadDownloads(null, null, $sortOrder);
        while (!$objAvailableDownload->EOF) {
            if ($objAvailableDownload->getId() == $objDownload->getId()) {
                $objAvailableDownload->next();
                continue;
            }

            $option = '<option value="'.$objAvailableDownload->getId().'">'.htmlentities($objAvailableDownload->getName(DownloadsLibrary::getOutputLocale()->getId()), ENT_QUOTES, CONTREXX_CHARSET).' ('.htmlentities($objAvailableDownload->getDescription(DownloadsLibrary::getOutputLocale()->getId()), ENT_QUOTES, CONTREXX_CHARSET).')</option>';

            if (in_array($objAvailableDownload->getId(), $arrRelatedDownloads)) {
                $arrAssociatedDownloadOptions[] = $option;
            } else {
                $arrNotAssociatedDownloadOptions[] = $option;
            }

            $objAvailableDownload->next();
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_DOWNLOAD_ASSOCIATED_DOWNLOADS'  => implode("\n", $arrAssociatedDownloadOptions),
            'DOWNLOADS_DOWNLOAD_NOT_ASSOCIATED_DOWNLOADS'  => implode("\n", $arrNotAssociatedDownloadOptions)
        ));

        // parse access permissions
        if ($objDownload->getAccessId()) {
            $objGroup = $objFWUser->objGroup->getGroups(
                array('dynamic' => $objDownload->getAccessId())
            );
            $arrAssociatedGroups = $objGroup->getLoadedGroupIds();
        } elseif ($objDownload->getProtection()) {
            $arrAssociatedGroups = $objDownload->getAccessGroupIds();
        } else {
            //$arrAssociatedCategories = $objDownload->getAssociatedCategoryIds();
            if (count($arrAssociatedCategories)) {
                $objCategory = Category::getCategories(
                    array('id' => $arrAssociatedCategories), null, null,
                    array('id', 'read_access_id')
                );
                while (!$objCategory->EOF) {
                    if ($objCategory->getReadAccessId()) {
                        $objGroup = $objFWUser->objGroup->getGroups(array('dynamic' => $objCategory->getReadAccessId()));
                        $arrAssociatedGroups = array_merge($arrAssociatedGroups, $objGroup->getLoadedGroupIds());
                    }
                    $objCategory->next();
                }
            } else {
                // TODO: WHY THAT?
                $objGroup = $objFWUser->objGroup->getGroups();
                $arrAssociatedGroups = $objGroup->getLoadedGroupIds();
            }
        }

        $objGroup = $objFWUser->objGroup->getGroups();
        while (!$objGroup->EOF) {
            $option = '<option value="'.$objGroup->getId().'">'.htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET).' ['.$objGroup->getType().']</option>';

            if (/*$objDownload->getProtection() || */in_array($objGroup->getId(), $arrAssociatedGroups)) {
                $arrAssociatedGroupOptions[] = $option;
            } else {
                $arrNotAssociatedGroupOptions[] = $option;
            }

            $objGroup->next();
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_DOWNLOAD_ACCESS_ALL_CHECKED'              => !$objDownload->getProtection() ? 'checked="checked"' : '',
            'DOWNLOADS_DOWNLOAD_ACCESS_SELECTED_CHECKED'         => $objDownload->getProtection() ? 'checked="checked"' : '',
            'DOWNLOADS_DOWNLOAD_ACCESS_DISPLAY'                  => $objDownload->getProtection() ? '' : 'none',
            'DOWNLOADS_DOWNLOAD_ACCESS_ASSOCIATED_GROUPS'        => implode("\n", $arrAssociatedGroupOptions),
            'DOWNLOADS_DOWNLOAD_ACCESS_NOT_ASSOCIATED_GROUPS'    => implode("\n", $arrNotAssociatedGroupOptions),
            'DOWNLOADS_DOWNLOAD_VISIBILITY_CHECKED'              => $objDownload->getVisibility() ? 'checked="checked"' : ''
        ));

        // parse cancel link
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_DOWNLOAD_CANCEL_LINK_SECITON'    => $this->parentCategoryId ? 'categories' : 'downloads',
            'DOWNLOADS_PARENT_CATEGORY_ID'              => $this->parentCategoryId,
            'DOWNLOADS_MEDIA_BROWSER_BUTTON'            => self::getMediaBrowserButton('mediabrowser_button', 'filebrowser')
        ));

        // sets javascript variable for current language
        \ContrexxJavascript::getInstance()->setVariable('currentLanguage', DownloadsLibrary::getOutputLocale()->getId(), 'Downloads');

        return true;
    }

    /**
     * Removes given array elements from provided array
     *
     * @param array $filter Array of element to delete from $unfilteredArray
     * @param array $unfilteredArray Array to be filtered
     * @return array
     */
    protected function filterArray($filter, $unfilteredArray)
    {
        foreach ($unfilteredArray as $key => $value) {
            if (in_array($key, $filter)) {
                unset($unfilteredArray[$key]);
            }
        }
        $filteredArray = $unfilteredArray;
        return $filteredArray;
    }

    /**
     * Mediabrowser integrated into the downloads modules.
     *
     * @global array $_ARRAYLANG
     * @param string $view optional argument
     */
    public static function getMediaBrowserButton($id, $type = 'filebrowser')
    {
        global $_ARRAYLANG;

        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setCallback('mbCallback');
        $options = array(
            'type'      => 'button',
            'views'     => $type,
            'startview' => $type,
        );

        if (!empty($id)) {
            $options['id']    = $id;
            $options['style'] = 'display:none;';
        }

        $mediaBrowser->setOptions($options);

        return $mediaBrowser->getXHtml($_ARRAYLANG['TXT_DOWNLOADS_BROWSE']);
    }

    /**
     * @todo  Documentation
     */
    private function updateCategoryOrder($arrCategoryOrder)
    {
        global $_ARRAYLANG;

        // TODO: check subcategory manage access permission of $parentCategoryId

        $arrFailedCategories = array();
        foreach ($arrCategoryOrder as $categoryId => $orderNr) {
            $objCategory = Category::getCategory($categoryId);
            if (!$objCategory->EOF) {
                $objCategory->setOrder($orderNr);
                if (!$objCategory->store()) {
                    $arrFailedCategories[] = htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET);
                }
            }
        }

        if (count($arrFailedCategories)) {
            $this->arrStatusMsg['error'][] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_CATEGORY_ORDER_SET_FAILED'], '<strong>'.implode(', ', $arrFailedCategories).'</strong>');
        } else {
            $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_DOWNLOADS_CATEGORY_ORDER_SET_SUCCESS'];
        }
    }


    private function loadGroupNavigation()
    {
        $this->objTemplate->loadTemplateFile('module_downloads_group.html');
    }


    private function parseGroupNavigation()
    {
        global $_ARRAYLANG;

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_OVERVIEW'    => $_ARRAYLANG['TXT_DOWNLOADS_OVERVIEW'],
            'TXT_DOWNLOADS_ADD_GROUP'   => $_ARRAYLANG['TXT_DOWNLOADS_ADD_GROUP']
        ));

        $this->objTemplate->parse('module_downloads_groups');
    }


    private function loadCategoryNavigation()
    {
        $this->objTemplate->loadTemplateFile('module_downloads_category.html');
    }


    private function parseCategoryNavigation()
    {
        global $_ARRAYLANG;

        $this->objTemplate->setVariable('TXT_DOWNLOADS_OVERVIEW', $_ARRAYLANG['TXT_DOWNLOADS_OVERVIEW']);
        $objFWUser = \FWUser::getFWUserObject();

        $objCategory = Category::getCategory($this->parentCategoryId);
        if (\Permission::checkAccess(143, 'static', true)
            || !$objCategory->EOF && (
                !$objCategory->getAddSubcategoriesAccessId()
                || \Permission::checkAccess($objCategory->getAddSubcategoriesAccessId(), 'dynamic', true)
                || $objCategory->getOwnerId() == $objFWUser->objUser->getId()
            )
        ) {
            $this->objTemplate->setVariable(array(
                'TXT_ADD_CATEGORY'          => $_ARRAYLANG['TXT_ADD_CATEGORY'],
                'DOWNLOADS_NAV_CATEGORY_ID' => $this->parentCategoryId
            ));
            $this->objTemplate->parse('downloads_category_add_link');
        } else {
            $this->objTemplate->hideBlock('downloads_category_add_link');
        }

        $this->objTemplate->parse('module_downloads_categories');
    }


    /**
     * categories list
     * @global array
     * @global integer
     * @global array
     * @global object
     * @global object
     */
    private function categories()
    {
        global $_ARRAYLANG, $_CONFIG, $objInit;

        $objCategory = Category::getCategory($this->parentCategoryId);
        $objFWUser = \FWUser::getFWUserObject();

        $this->_pageTitle = $_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES'];
        $this->objTemplate->addBlockFile('DOWNLOADS_CATEGORY_TEMPLATE', 'module_downloads_categories', 'module_downloads_categories.html');

        // check access permission
        if (// managers are allowed to see the content of every category
            !\Permission::checkAccess(143, 'static', true)
            && $objCategory->getReadAccessId()
            && !\Permission::checkAccess($objCategory->getReadAccessId(), 'dynamic', true)
            && $objCategory->getOwnerId() != $objFWUser->objUser->getId()
        ) {
            return \Permission::noAccess();
        }

        // get passed parameters
        $pos = isset($_GET['pos']) ? intval($_GET['pos']) : 0;

        $categoryLimitOffset = isset($_GET['category_pos']) ? intval($_GET['category_pos']) : $pos;
        $categoryOrderDirection = !empty($_GET['category_sort']) ? $_GET['category_sort'] : 'asc';
        $categoryOrderBy = !empty($_GET['category_by']) ? $_GET['category_by'] : '';

        $downloadLimitOffset = isset($_GET['download_pos']) ? intval($_GET['download_pos']) : $pos;
        $downloadOrderDirection = !empty($_GET['download_sort']) ? $_GET['download_sort'] : 'asc';
        $downloadOrderBy = !empty($_GET['download_by']) ? $_GET['download_by'] : '';

        $searchTerm = !empty($_GET['search_term']) ? contrexx_input2raw($_GET['search_term']) : '';

        // parse categories multi action
        if (isset($_POST['downloads_category_select_action'])) {
            switch ($_POST['downloads_category_select_action']) {
                case 'order':
                    $this->updateCategoryOrder(
                           isset($_POST['downloads_category_order'])
                        && is_array($_POST['downloads_category_order'])
                            ? $_POST['downloads_category_order'] : array());
                    break;
                case 'delete':
                    $this->deleteCategories(isset($_POST['downloads_category_id']) && is_array($_POST['downloads_category_id']) ? $_POST['downloads_category_id'] : array(), isset($_POST['downloads_category_delete_recursive']) && $_POST['downloads_category_delete_recursive']);
                    break;
            }
        }

        // process downloads multi action
        if (isset($_POST['downloads_download_select_action'])) {
            if (// managers are allowed to manage to downloads
                !\Permission::checkAccess(143, 'static', true)
                && $objCategory->getManageFilesAccessId()
                && !\Permission::checkAccess($objCategory->getManageFilesAccessId(), 'dynamic', true)
                && $objCategory->getOwnerId() != $objFWUser->objUser->getId()
            ) {
                return \Permission::noAccess();
            }

            switch ($_POST['downloads_download_select_action']) {
                case 'order':
                    if ($objCategory->updateDownloadOrder(isset($_POST['downloads_download_order']) && is_array($_POST['downloads_download_order']) ? $_POST['downloads_download_order'] : array())) {
                        $this->arrStatusMsg['ok'][] = $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_ORDER_SET_SUCCESS'];
                    } else {
                        $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objCategory->getErrorMsg());
                    }
                    break;
                case 'unlink':
                    $this->unlinkDownloadsFromCategory($objCategory, isset($_POST['downloads_download_id']) && is_array($_POST['downloads_download_id']) ? $_POST['downloads_download_id'] : array());
                    break;
            }
        }

        $this->objTemplate->setGlobalVariable(array(
            'TXT_DOWNLOADS_EDIT'    => $_ARRAYLANG['TXT_DOWNLOADS_EDIT'],
            'TXT_DOWNLOADS_DELETE'  => $_ARRAYLANG['TXT_DOWNLOADS_DELETE']
        ));

//        // check if user is allowed to add a subcategory
//        if (// managers are allowed to add subcategories
//            \Permission::checkAccess(143, 'static', true)
//            // the selected category must be valid to proceed future permission checks.
//            // this is required to protect the overview section from non-admins
//            || $objCategory->getId() && (
//                // the category isn't protected => everyone is allowed to add subcategories
//                !$objCategory->getAddSubcategoriesAccessId()
//                // the category is protected => only those who have the sufficent permissions are allowed to add subcategories
//                || \Permission::checkAccess($objCategory->getAddSubcategoriesAccessId(), 'dynamic', true)
//                // the owner is allowed to add subcategories
//                || ($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login() && $objCategory->getOwnerId() == $objFWUser->objUser->getId()
//            )
//        ) {
//            $this->objTemplate->setVariable(array(
//                'DOWNLOADS_CATEGORY_ID' => $objCategory->getId(),
//                // TODO: rename
//                //'TXT_ADD_CATEGORY'      => $_ARRAYLANG['TXT_ADD_CATEGORY']
//            ));
//            $this->objTemplate->parse('downloads_category_add_buttom');
//        } else {
//            $this->objTemplate->hideBlock('downloads_category_add_buttom');
//        }

        // parse categories
        $this->parseCategories($objCategory, $downloadOrderBy, $downloadOrderDirection, $downloadLimitOffset, $categoryOrderBy, $categoryOrderDirection, $categoryLimitOffset);

        if (!$objCategory->getId()) {
            $this->objTemplate->setVariable('TXT_DOWNLOADS_ALL_CATEGORIES', $_ARRAYLANG['TXT_DOWNLOADS_ALL_CATEGORIES']);
        }

        // parse frontend preview link
        if ($objCategory->getId()) {
            try {
                $categoryFrontendURI = DownloadsLibrary::getApplicationUrl(array($objCategory->getId()));
                $this->objTemplate->setVariable(array(
                    'TXT_DOWNLOADS_OPEN_CATEGORY_FRONTEND'      => $_ARRAYLANG['TXT_DOWNLOADS_OPEN_CATEGORY_FRONTEND'],
                    'DOWNLOADS_CATEGORY_FRONTEND_URI'           => $categoryFrontendURI
                ));
                $this->objTemplate->parse('downloads_category_frontend_link');
            } catch (DownloadsLibraryException $e) {
                $this->objTemplate->hideBlock('downloads_category_frontend_link');
            }
        } else {
            $this->objTemplate->hideBlock('downloads_category_frontend_link');
        }

        // parse downloads
        $this->parseCategoryDownloads($objCategory, $downloadOrderBy, $downloadOrderDirection, $downloadLimitOffset, $categoryOrderBy, $categoryOrderDirection, $categoryLimitOffset, $searchTerm);

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CONFIRM_UNLINK_DOWNLOAD_TXT' => preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_UNLINK_DOWNLOAD'])),
        ));

        // parse add downloads buttons
        if (// we can only add downloads to a category
            $objCategory->getId() && (
                // managers are allowed to add new downloads
                \Permission::checkAccess(143, 'static', true)
                // the category isn't protected => everyone is allowed to add new downloads
                || !$objCategory->getAddFilesAccessId()
                // the category is protected => only those who have the sufficent permissions are allowed to add new downloads
                || \Permission::checkAccess($objCategory->getAddFilesAccessId(), 'dynamic', true)
            )
        ) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_CATEGORY_ID'                     => $objCategory->getId(),
                'TXT_DOWNLOADS_ADD_NEW_DOWNLOAD_TO_CATEGORY'=> sprintf($_ARRAYLANG['TXT_DOWNLOADS_ADD_NEW_DOWNLOAD_TO_CATEGORY'], htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
                'TXT_DOWNLOADS_ADD_DOWNLOADS_TO_CATEGORY'   => sprintf($_ARRAYLANG['TXT_DOWNLOADS_ADD_DOWNLOADS_TO_CATEGORY'], htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
                'DOWNLOADS_DOWNLOAD_CATEGORY_SORT'          => $categoryOrderDirection,
                'DOWNLOADS_DOWNLOAD_CATEGORY_SORT_BY'       => $categoryOrderBy,
                'DOWNLOADS_DOWNLOAD_DOWNLOAD_SORT'          => $downloadOrderDirection,
                'DOWNLOADS_DOWNLOAD_DOWNLOAD_BY'            => $downloadOrderBy,
                'DOWNLOADS_DOWNLOAD_CATEGORY_OFFSET'        => $categoryLimitOffset,
                'DOWNLOADS_DOWNLOAD_DOWNLOAD_OFFSET'        => $downloadLimitOffset
            ));
            $this->objTemplate->parse('downloads_add_downloads_button');
        } else {
            $this->objTemplate->hideBlock('downloads_add_downloads_button');
        }

        // parse category menu
        // parse category id (will be used as the parent_id when creating a new directory
        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_CATEGORY'            => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORY'],
            'DOWNLOADS_CATEGORY_ID'             => $objCategory->getId(),
            'DOWNLOADS_CATEGORY_MENU'           => $this->getCategoryMenu('read', $objCategory->getId(), $_ARRAYLANG['TXT_DOWNLOADS_ALL_CATEGORIES']),
            'DOWNLOADS_SEARCH_TERM'             => contrexx_raw2xhtml($searchTerm),
            'TXT_DOWNLOADS_SEARCH_DOWNLOAD'     => $_ARRAYLANG['TXT_DOWNLOADS_SEARCH_DOWNLOAD'],
            'TXT_DOWNLOADS_FILTER'              => $_ARRAYLANG['TXT_DOWNLOADS_FILTER'],
            'TXT_DOWNLOADS_SEARCH'              => $_ARRAYLANG['TXT_DOWNLOADS_SEARCH'],
        ));

        return true;
    }

    private function parseCategories($objCategory, $downloadOrderBy, $downloadOrderDirection, $downloadLimitOffset, $categoryOrderBy, $categoryOrderDirection, $categoryLimitOffset)
    {
        global $_ARRAYLANG, $_CONFIG;

        $objFWUser = \FWUser::getFWUserObject();

        $arrOrder = $this->categoriesSortingOptions[$this->arrConfig['categories_sorting_order']];
        if (!empty($categoryOrderBy)) {
            $arrOrder = array_merge(
                // add custom sort flag as first element to array (to ensure
                // it has the highest precedence)
                array($categoryOrderBy => $categoryOrderDirection),
                // add default sort flags
                $arrOrder,
                // finnaly, do add the custom sort flag again. Adding it again
                // after the default sort flags will ensure that the custom
                // sort flags will overwrite the default sort flags
                array($categoryOrderBy => $categoryOrderDirection)
            );
        }

        $minColspan = 7;

        // check of it is allowed to change the sort order
        if (// managers are allowed to manage every subcategory
            \Permission::checkAccess(143, 'static', true)
            // the selected category must be valid to proceed future permission checks.
            // this is required to protect the overview section from non-admins
            || $objCategory->getId() && (
                // the category isn't protected => everyone is allowed to modify subcategories
                !$objCategory->getManageSubcategoriesAccessId()
                // the category is protected => only those who have the sufficent permissions are allowed to modify subcategories
                || \Permission::checkAccess($objCategory->getManageSubcategoriesAccessId(), 'dynamic', true)
            )
        ) {
            $changeSortOrder = true;
        } else {
            $changeSortOrder = false;
        }

        // parse select posibilities and dropdown action menu
        if (// managers are allowed to operate on subcategories
            \Permission::checkAccess(143, 'static', true)
            // the selected category must be valid to proceed future permission checks.
            // this is required to protect the overview section from non-admins
            || $objCategory->getId() && (
                // the category isn't protected => everyone is allowed to operate on subcategories
                !$objCategory->getManageSubcategoriesAccessId()
                // the category is protected => only those who have the sufficent permissions are allowed to operate on subcategories
                || \Permission::checkAccess($objCategory->getManageSubcategoriesAccessId(), 'dynamic', true)
            )
        ) {
            $operateOnSubcategories = true;
        } else {
            $operateOnSubcategories = false;
        }

        $objSubcategory = Category::getCategories(array('parent_id' => $objCategory->getId()), null, $arrOrder, null, $_CONFIG['corePagingLimit'], $categoryLimitOffset);

        $nr = 0;
        if ($objSubcategory->EOF) {
            $this->objTemplate->setVariable('TXT_DOWNLOADS_NO_CATEGORIES_AVAILABLE', $_ARRAYLANG['TXT_DOWNLOADS_NO_CATEGORIES_AVAILABLE']);
            $this->objTemplate->parse('downloads_category_no_data');
            $this->objTemplate->hideBlock('downloads_category_data');
        } else {
            if ($operateOnSubcategories) {
                $this->objTemplate->setVariable(array(
                    'TXT_SAVE_CHANGES_CATEGORIES'   => $_ARRAYLANG['TXT_SAVE_CHANGES'],
                    'TXT_SAVE_CHANGES_DOWNLOADS'    => $_ARRAYLANG['TXT_SAVE_CHANGES'],
                    'TXT_DOWNLOADS_CHECK_ALL'       => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
                    'TXT_DOWNLOADS_UNCHECK_ALL'     => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
                    'TXT_DOWNLOADS_SELECT_ACTION'   => $_ARRAYLANG['TXT_DOWNLOADS_SELECT_ACTION'],
                    'TXT_DOWNLOADS_ORDER'           => $_ARRAYLANG['TXT_DOWNLOADS_ORDER'],
                    'TXT_DOWNLOADS_DELETE'          => $_ARRAYLANG['TXT_DOWNLOADS_DELETE']
                ));
                $this->objTemplate->parse('downloads_category_action_dropdown');
                $this->objTemplate->touchBlock('downloads_category_select_label');
            } else {
                $this->objTemplate->hideBlock('downloads_category_action_dropdown');
                $this->objTemplate->hideBlock('downloads_category_select_label');
            }

            // parse paging
            $categoryCount = $objSubcategory->getFilteredSearchCategoryCount();
            if ($categoryCount > $_CONFIG['corePagingLimit']) {
                $pagingLink = "&cmd=Downloads&act=categories&parent_id=".$objCategory->getId()
                    ."&category_sort=".htmlspecialchars($categoryOrderDirection)
                    ."&category_by=".htmlspecialchars($categoryOrderBy)
                    ."&download_sort=".htmlspecialchars($downloadOrderDirection)
                    ."&download_by=".htmlspecialchars($downloadOrderBy)
                    ."&download_pos=".$downloadLimitOffset;
                $this->objTemplate->setVariable('DOWNLOADS_CATEGORY_PAGING', getPaging($categoryCount, $categoryLimitOffset, $pagingLink, "<b>".$_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES']."</b>"));
            }

            while (!$objSubcategory->EOF) {
//                if (// subcategory is hidden -> check if the user is allowed to see it listed anyways
//                    !$objSubcategory->getVisibility()
//                    // non managers are not allowed to see hidden subcategories
//                    && !\Permission::checkAccess(143, 'static', true)
//                    // those who have read access permission to the subcategory are allowed to see it listed
//                    && !\Permission::checkAccess($objSubcategory->getReadAccessId(), 'dynamic', true)
//                    // the owner is allowed to see its own categories
//                    && (!$objSubcategory->getOwnerId() || $objSubcategory->getOwnerId() != $objFWUser->objUser->getId())
//                ) {
//                    $objSubcategory->next();
//                    continue;
//                }

                // parse order input box
                if ($changeSortOrder) {
                    $this->objTemplate->setVariable(array(
                        'DOWNLOADS_CATEGORY_ID'     => $objSubcategory->getId(),
                        'DOWNLOADS_CATEGORY_ORDER'  => $objSubcategory->getOrder()
                    ));
                    $this->objTemplate->parse('downloads_category_orderbox');
                    $this->objTemplate->hideBlock('downloads_category_no_orderbox');
                } else {
                    $this->objTemplate->setVariable('DOWNLOADS_CATEGORY_ORDER', $objSubcategory->getOrder());
                    $this->objTemplate->hideBlock('downloads_category_orderbox');
                    $this->objTemplate->parse('downloads_category_no_orderbox');
                }

                // parse status link and modify button
                if (// managers are allowed to manage every subcategory
                    \Permission::checkAccess(143, 'static', true)
                    // the selected category must be valid to proceed future permission checks.
                    // this is required to protect the overview section from non-admins
                    || $objCategory->getId() && (
                        // the category isn't protected => everyone is allowed to modify subcategories
                        !$objCategory->getManageSubcategoriesAccessId()
                        // the category is protected => only those who have the sufficent permissions are allowed to modify subcategories
                        || \Permission::checkAccess($objCategory->getManageSubcategoriesAccessId(), 'dynamic', true)
                    )
                    // the owner is allowed to manage its subcategories
                    || $objSubcategory->getOwnerId() && $objSubcategory->getModifyAccessByOwner() && $objSubcategory->getOwnerId() == $objFWUser->objUser->getId()
                ) {
                    $this->objTemplate->setVariable(array(
                        'DOWNLOADS_CATEGORY_ID'                     => $objSubcategory->getId(),
                        'DOWNLOADS_CATEGORY_PARENT_ID'              => $objCategory->getId(),
                        'DOWNLOADS_CATEGORY_CATEGORY_SORT'          => $categoryOrderDirection,
                        'DOWNLOADS_CATEGORY_CATEGORY_SORT_BY'       => $categoryOrderBy,
                        'DOWNLOADS_CATEGORY_DOWNLOAD_SORT'          => $downloadOrderDirection,
                        'DOWNLOADS_CATEGORY_DOWNLOAD_BY'            => $downloadOrderBy,
                        'DOWNLOADS_CATEGORY_CATEGORY_OFFSET'        => $categoryLimitOffset,
                        'DOWNLOADS_CATEGORY_DOWNLOAD_OFFSET'        => $downloadLimitOffset,
                        //'DOWNLOADS_CATEGORY_STATUS_JS'           => $objSubcategory->getActiveStatus(),
                        //'DOWNLOADS_CATEGORY_NAME_JS'             => htmlspecialchars($objSubcategory->getName(), ENT_QUOTES, CONTREXX_CHARSET),
                        'DOWNLOADS_CATEGORY_SWITCH_STATUS_DESC'     => $objSubcategory->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_DEACTIVATE_CATEGORY_DESC'] : $_ARRAYLANG['TXT_DOWNLOADS_ACTIVATE_CATEGORY_DESC'],
                        'DOWNLOADS_CATEGORY_SWITCH_STATUS_IMG_DESC' => $objSubcategory->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_DEACTIVATE_CATEGORY_DESC'] : $_ARRAYLANG['TXT_DOWNLOADS_ACTIVATE_CATEGORY_DESC']
                    ));
                    $this->objTemplate->parse('downloads_category_status_link_open');
                    $this->objTemplate->touchBlock('downloads_category_status_link_close');

                    // parse modify icon
                    $this->objTemplate->setVariable(array(
                        'DOWNLOADS_CATEGORY_ID'                 => $objSubcategory->getId(),
                        'DOWNLOADS_CATEGORY_PARENT_ID'          => $objCategory->getId(),
                        'DOWNLOADS_CATEGORY_CATEGORY_SORT'      => $categoryOrderDirection,
                        'DOWNLOADS_CATEGORY_CATEGORY_SORT_BY'   => $categoryOrderBy,
                        'DOWNLOADS_CATEGORY_DOWNLOAD_SORT'      => $downloadOrderDirection,
                        'DOWNLOADS_CATEGORY_DOWNLOAD_BY'        => $downloadOrderBy,
                        'DOWNLOADS_CATEGORY_CATEGORY_OFFSET'    => $categoryLimitOffset,
                        'DOWNLOADS_CATEGORY_DOWNLOAD_OFFSET'    => $downloadLimitOffset,
                    ));
                    $this->objTemplate->parse('downloads_category_function_modify_link');
                    $this->objTemplate->hideBlock('downloads_category_function_no_modify_link');
                } else {
                    $this->objTemplate->setVariable(array(
                        'DOWNLOADS_CATEGORY_SWITCH_STATUS_DESC'     => $objSubcategory->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_ACTIVE'] : $_ARRAYLANG['TXT_DOWNLOADS_INACTIVE'],
                        'DOWNLOADS_CATEGORY_SWITCH_STATUS_IMG_DESC' => $objSubcategory->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_ACTIVE'] : $_ARRAYLANG['TXT_DOWNLOADS_INACTIVE']
                    ));
                    $this->objTemplate->hideBlock('downloads_category_status_link_open');
                    $this->objTemplate->hideBlock('downloads_category_status_link_close');

                    // hide modify icon
                    $this->objTemplate->touchBlock('downloads_category_function_no_modify_link');
                    $this->objTemplate->hideBlock('downloads_category_function_modify_link');
                }

                // parse delete button
                if (// managers are allowed to see delete every category
                    \Permission::checkAccess(143, 'static', true)
                    // the selected category must be valid to proceed future permission checks.
                    // this is required to protect the overview section from non-admins
                    || $objCategory->getId() && (
                        // the category isn't protected => everyone is allowed to delete its subcategories
                        !$objCategory->getManageSubcategoriesAccessId()
                        // the category is protected => only those who have the sufficent permissions are allowed to delete its subcategories
                        || \Permission::checkAccess($objCategory->getManageSubcategoriesAccessId(), 'dynamic', true)
                        // the owner is allowed to delete the subcategory
                        || $objSubcategory->getDeletableByOwner() && $objSubcategory->getOwnerId() == $objFWUser->objUser->getId()
                    )
                ) {
                    $this->objTemplate->setVariable(array(
                        'TXT_DOWNLOADS_DELETE'                  => $_ARRAYLANG['TXT_DOWNLOADS_DELETE'],
                        'DOWNLOADS_CATEGORY_NAME_JS'            => htmlspecialchars($objSubcategory->getName(), ENT_QUOTES, CONTREXX_CHARSET),
                        'DOWNLOADS_CATEGORY_HAS_SUBCATEGORIES'  => $objSubcategory->hasSubcategories()
                    ));

                    // parse delete icon
                    $this->objTemplate->parse('downloads_category_function_delete_link');
                    $this->objTemplate->hideBlock('downloads_category_function_no_delete_link');
                } else {
                    // hide delete icon
                    $this->objTemplate->touchBlock('downloads_category_function_no_delete_link');
                    $this->objTemplate->hideBlock('downloads_category_function_delete_link');
                }

                //parse select checkbox
                if ($operateOnSubcategories) {
                    $this->objTemplate->setVariable('DOWNLOADS_CATEGORY_ID', $objSubcategory->getId());
                    $this->objTemplate->parse('downloads_category_checkbox');
                } else {
                    $this->objTemplate->hideBlock('downloads_category_checkbox');
                }

                // parse detail link
                if (// managers are allowed to see the content of every category
                    \Permission::checkAccess(143, 'static', true)
                    // the category isn't protected => everyone is allowed to the it's content
                    || !$objSubcategory->getReadAccessId()
                    // the category is protected => only those who have the sufficent permissions are allowed to see it's content
                    || \Permission::checkAccess($objSubcategory->getReadAccessId(), 'dynamic', true)
                    // the owner is allowed to see the content of the category
                    || $objSubcategory->getOwnerId() == $objFWUser->objUser->getId()
                ) {
                    $this->objTemplate->setVariable('DOWNLOADS_CATEGORY_ID', $objSubcategory->getId());
                    //$this->objTemplate->parse('downloads_category_name_link_open');
                    $this->objTemplate->touchBlock('downloads_category_name_link_close');
                } else {
                    $this->objTemplate->hideBlock('downloads_category_name_link_open');
                    $this->objTemplate->hideBlock('downloads_category_name_link_close');
                }

                $description = $objSubcategory->getDescription();
                if (strlen($description) > 100) {
                    $description = substr($description, 0, 97).'...';
                }

                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_CATEGORY_ROW_CLASS'          => $nr++ % 2 ? 'row1' : 'row2',
                    'DOWNLOADS_CATEGORY_ID'                 => $objSubcategory->getId(),
                    'DOWNLOADS_CATEGORY_STATUS_LED'         => $objSubcategory->getActiveStatus() ? 'led_green.gif' : 'led_red.gif',
                    'DOWNLOADS_OPEN_CATEGORY_DESC'          => sprintf($_ARRAYLANG['TXT_DOWNLOADS_SHOW_CATEGORY_CONTENT'], htmlentities($objSubcategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
                    'DOWNLOADS_CATEGORY_NAME'               => htmlentities($objSubcategory->getName(), ENT_QUOTES, CONTREXX_CHARSET),
                    'DOWNLOADS_CATEGORY_PROTECTED'          => $objSubcategory->getReadAccessId() ? '_locked' : '',
                    'DOWNLOADS_CATEGORY_DOWNLOADS_COUNT'    => intval($objSubcategory->getAssociatedDownloadsCount()),
                    'DOWNLOADS_CATEGORY_DESCRIPTION'        => htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET),
                    'DOWNLOADS_CATEGORY_AUTHOR'             => contrexx_raw2xhtml($this->getParsedUsername($objSubcategory->getOwnerId()))
                ));
                $this->objTemplate->parse('downloads_category_list');
                $objSubcategory->next();
            }

            $this->objTemplate->setVariable(array(
                // parse sorting
                'DOWNLOADS_CATEGORY_SORT_PARENT_ID'         => $objCategory->getId(),
                'DOWNLOADS_CATEGORY_SORT_DIRECTION'         => $categoryOrderDirection,
                'DOWNLOADS_CATEGORY_SORT_BY'                => $categoryOrderBy,
                'DOWNLOADS_CATEGORY_SORT_ID'                => ($categoryOrderBy == 'id' && $categoryOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_CATEGORY_SORT_ORDER'             => ($categoryOrderBy == 'order' && $categoryOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_CATEGORY_SORT_STATUS'            => ($categoryOrderBy == 'is_active' && $categoryOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_CATEGORY_SORT_NAME'              => ($categoryOrderBy == 'name' && $categoryOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_CATEGORY_SORT_DESCRIPTION'       => ($categoryOrderBy == 'description' && $categoryOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_CATEGORY_SORT_ID_LABEL'          => $_ARRAYLANG['TXT_DOWNLOADS_ID'].($categoryOrderBy == 'id' ? $categoryOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_CATEGORY_SORT_STATUS_LABEL'      => $_ARRAYLANG['TXT_DOWNLOADS_STATUS'].($categoryOrderBy == 'is_active' ? $categoryOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_CATEGORY_SORT_ORDER_LABEL'       => $_ARRAYLANG['TXT_DOWNLOADS_ORDER'].($categoryOrderBy == 'order' ? $categoryOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_CATEGORY_SORT_NAME_LABEL'        => $_ARRAYLANG['TXT_DOWNLOADS_NAME'].($categoryOrderBy == 'name' ? $categoryOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_CATEGORY_SORT_DESCRIPTION_LABEL' => $_ARRAYLANG['TXT_DOWNLOADS_DESCRIPTION'].($categoryOrderBy == 'description' ? $categoryOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_CATEGORY_DOWNLOAD_SORT'          => $downloadOrderDirection,
                'DOWNLOADS_CATEGORY_DOWNLOAD_BY'            => $downloadOrderBy,
                'DOWNLOADS_CATEGORY_DOWNLOAD_OFFSET'        => $downloadLimitOffset,

                // parse table header
                'TXT_DOWNLOADS_OWNER'                       => $_ARRAYLANG['TXT_DOWNLOADS_OWNER']
            ));

            $this->objTemplate->touchBlock('downloads_category_data');
            $this->objTemplate->hideBlock('downloads_category_no_data');

            $this->objTemplate->setVariable(array(
                'DOWNLOADS_CATEGORY_CATEGORY_SORT'          => $categoryOrderDirection,
                'DOWNLOADS_CATEGORY_CATEGORY_SORT_BY'       => $categoryOrderBy,
                'DOWNLOADS_CATEGORY_DOWNLOAD_SORT'          => $downloadOrderDirection,
                'DOWNLOADS_CATEGORY_DOWNLOAD_BY'            => $downloadOrderBy,
                'DOWNLOADS_CATEGORY_CATEGORY_OFFSET'        => $categoryLimitOffset,
                'DOWNLOADS_CATEGORY_DOWNLOAD_OFFSET'        => $downloadLimitOffset
            ));
        }


        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_OPERATION_IRREVERSIBLE'      => $_ARRAYLANG['TXT_DOWNLOADS_OPERATION_IRREVERSIBLE'],
            'TXT_DOWNLOADS_DELETE_SUBCATEGORIES'        => $_ARRAYLANG['TXT_DOWNLOADS_DELETE_SUBCATEGORIES'],
            'TXT_DOWNLOADS_DELETE_SUBCATEGORIES_MULTI'  => $_ARRAYLANG['TXT_DOWNLOADS_DELETE_SUBCATEGORIES_MULTI'],
            'DOWNLOADS_CONFIRM_DELETE_CATEGORY_TXT'     => preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_DELETE_CATEGORY'])),
            'DOWNLOADS_CONFIRM_DELETE_CATEGORIES_TXT'   => preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_DELETE_CATEGORIES'])),
            'TXT_DOWNLOADS_FUNCTIONS'                   => $_ARRAYLANG['TXT_DOWNLOADS_FUNCTIONS'],
            'DOWNLOADS_CATEGORY_COLSPAN'                => $minColspan + $operateOnSubcategories,
        ));


        if ($objCategory->getId()) {
            $this->objTemplate->setVariable('TXT_DOWNLOADS_CATEGORIES_OF_CATEGORY', sprintf($_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES_OF_CATEGORY'], '&bdquo;'.htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET).'&ldquo;'));
        }
    }


    private function parseCategoryDownloads($objCategory, $downloadOrderBy, $downloadOrderDirection, $downloadLimitOffset, $categoryOrderBy, $categoryOrderDirection, $categoryLimitOffset, $searchTerm)
    {
        global $_ARRAYLANG, $_CONFIG;

        $nr = 0;
        $arrDownloadOrder = $objCategory->getAssociatedDownloadIds();
        $objFWUser = \FWUser::getFWUserObject();

        $arrOrder = $this->downloadsSortingOptions[$this->arrConfig['downloads_sorting_order']];
        if (!empty($downloadOrderBy)) {
            $arrOrder = array_merge(
                // add custom sort flag as first element to array (to ensure
                // it has the highest precedence)
                array($downloadOrderBy => $downloadOrderDirection),
                // add default sort flags
                $arrOrder,
                // finnaly, do add the custom sort flag again. Adding it again
                // after the default sort flags will ensure that the custom
                // sort flags will overwrite the default sort flags
                array($downloadOrderBy => $downloadOrderDirection)
            );
        }

        $objDownload = new Download($this->arrConfig);
        $objDownload->loadDownloads(array('category_id' => $objCategory->getId()), $searchTerm, $arrOrder, null, $_CONFIG['corePagingLimit'], $downloadLimitOffset, true);
        $downloadsAvailable = $objDownload->EOF ? false : true;
        while (!$objDownload->EOF) {
//            if (!\Permission::checkAccess(143, 'static', true) && !$objDownload->getVisibility() && $objDownload->getOwnerId() != $objFWUser->objUser->getId()) {
//                $objDownload->next();
//                continue;
//            }

            // parse select checkbox & order box
            if (// managers are allowed to manage every download
                (
                    \Permission::checkAccess(143, 'static', true)
                    || !$objCategory->getManageFilesAccessId()
                    || \Permission::checkAccess($objCategory->getManageFilesAccessId(), 'dynamic', true)
                    || $objCategory->getOwnerId() == $objFWUser->objUser->getId()
                )
                && $objCategory->getId()
            ) {
                // select checkbox
                $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_ID', $objDownload->getId());
                $this->objTemplate->parse('downloads_download_checkbox');

                // order box
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_ID'     => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_ORDER'  => $arrDownloadOrder[$objDownload->getId()]
                ));
                $this->objTemplate->parse('downloads_download_orderbox');
                $this->objTemplate->hideBlock('downloads_download_no_orderbox');
                $this->objTemplate->parse('downloads_download_no_save_button');
            } else {
                // select checkbox
                $this->objTemplate->hideBlock('downloads_download_checkbox');
                $this->objTemplate->hideBlock('downloads_download_action_dropdown');

                // order box
                $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_ORDER', $objCategory->getId() ? $arrDownloadOrder[$objDownload->getId()] : $objDownload->getOrder());
                $this->objTemplate->parse('downloads_download_no_orderbox');
                $this->objTemplate->hideBlock('downloads_download_orderbox');
                $this->objTemplate->hideBlock('downloads_download_no_save_button');
            }

            // parse status link and modify button
            if (// managers are allowed to manage every download
                \Permission::checkAccess(143, 'static', true)
                // the selected category must be valid to proceed future permission checks.
                // this is required to protect the overview section from non-admins
                || $objCategory->getId() && (
                    // the category isn't protected => everyone is allowed to modify downloads
                    !$objCategory->getManageFilesAccessId()
                    // the category is protected => only those who have the sufficent permissions are allowed to modify downloads
                    || \Permission::checkAccess($objCategory->getManageFilesAccessId(), 'dynamic', true)
                    // the owner of the category is allowed to manage its downloads
                    || $objCategory->getModifyAccessByOwner() && $objCategory->getOwnerId() == $objFWUser->objUser->getId()
                )
                // the owner of the download is allowed to manage it
                || $objDownload->getOwnerId() == $objFWUser->objUser->getId()
            ) {
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_CATEGORY_SORT'          => $categoryOrderDirection,
                    'DOWNLOADS_DOWNLOAD_CATEGORY_SORT_BY'       => $categoryOrderBy,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_SORT'          => $downloadOrderDirection,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_BY'            => $downloadOrderBy,
                    'DOWNLOADS_DOWNLOAD_CATEGORY_OFFSET'        => $categoryLimitOffset,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_OFFSET'        => $downloadLimitOffset,
                    'DOWNLOADS_DOWNLOAD_ID'                     => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_CATEGORY_PARENT_ID'     => $objCategory->getId(),
                    'DOWNLOADS_DOWNLOAD_SWITCH_STATUS_DESC'     => $objDownload->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_DEACTIVATE_DOWNLOAD_DESC'] : $_ARRAYLANG['TXT_DOWNLOADS_ACTIVATE_DOWNLOAD_DESC'],
                    'DOWNLOADS_DOWNLOAD_SWITCH_STATUS_IMG_DESC' => $objDownload->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_DEACTIVATE_DOWNLOAD_DESC'] : $_ARRAYLANG['TXT_DOWNLOADS_ACTIVATE_DOWNLOAD_DESC']
                ));
                $this->objTemplate->parse('downloads_download_status_link_open');
                $this->objTemplate->touchBlock('downloads_download_status_link_close');

                // parse modify icon
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_CATEGORY_SORT'      => $categoryOrderDirection,
                    'DOWNLOADS_DOWNLOAD_CATEGORY_SORT_BY'   => $categoryOrderBy,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_SORT'      => $downloadOrderDirection,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_BY'        => $downloadOrderBy,
                    'DOWNLOADS_DOWNLOAD_CATEGORY_OFFSET'    => $categoryLimitOffset,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_OFFSET'    => $downloadLimitOffset,
                    'DOWNLOADS_DOWNLOAD_ID'                 => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_CATEGORY_PARENT_ID' => $objCategory->getId()
                ));
                $this->objTemplate->parse('downloads_download_function_modify_link');
                $this->objTemplate->hideBlock('downloads_download_function_no_modify_link');

                // parse modify link on name attribute
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_CATEGORY_SORT'      => $categoryOrderDirection,
                    'DOWNLOADS_DOWNLOAD_CATEGORY_SORT_BY'   => $categoryOrderBy,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_SORT'      => $downloadOrderDirection,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_BY'        => $downloadOrderBy,
                    'DOWNLOADS_DOWNLOAD_CATEGORY_OFFSET'    => $categoryLimitOffset,
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_OFFSET'    => $downloadLimitOffset,
                    'DOWNLOADS_DOWNLOAD_ID'                 => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_CATEGORY_PARENT_ID' => $objCategory->getId()
                ));
                $this->objTemplate->parse('downloads_download_modify_link_open');
                $this->objTemplate->touchBlock('downloads_download_modify_link_close');

            } else {
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_SWITCH_STATUS_DESC'     => $objDownload->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_ACTIVE'] : $_ARRAYLANG['TXT_DOWNLOADS_INACTIVE'],
                    'DOWNLOADS_DOWNLOAD_SWITCH_STATUS_IMG_DESC' => $objDownload->getActiveStatus() ? $_ARRAYLANG['TXT_DOWNLOADS_ACTIVE'] : $_ARRAYLANG['TXT_DOWNLOADS_INACTIVE']
                ));
                $this->objTemplate->hideBlock('downloads_download_status_link_open');
                $this->objTemplate->hideBlock('downloads_download_status_link_close');

                // hide modify icon
                $this->objTemplate->touchBlock('downloads_download_function_no_modify_link');
                $this->objTemplate->hideBlock('downloads_download_function_modify_link');

                // hide modify linke on name attribute
                $this->objTemplate->hideBlock('downloads_download_modify_link_open');
                $this->objTemplate->hideBlock('downloads_download_modify_link_close');
            }


            // parse download link
            if (// download isn't protected -> everyone is allowed to download it
                !$objDownload->getAccessId()
                // the owner of the download is allowed to download it
                || $objDownload->getOwnerId() == $objFWUser->objUser->getId()
                // the download is protected -> only those who have the sufficent permissions are allowed to download it
                || \Permission::checkAccess($objDownload->getAccessId(), 'dynamic', true)
                // managers are allowed to download every download
                || \Permission::checkAccess(143, 'static', true)
                // the selected category must be valid to proceed future permission checks.
                // this is required to protect the overview section from non-admins
                || $objCategory->getId() && (
                    // the category isn't protected => everyone is allowed to delete downloads
                    !$objCategory->getManageFilesAccessId()
                    // the category is protected => only those who have the sufficent permissions are allowed to delete downloads
                    || \Permission::checkAccess($objCategory->getManageFilesAccessId(), 'dynamic', true)
                    // the owner of the category is allowed to download its downloads
                    || $objCategory->getModifyAccessByOwner() && $objCategory->getOwnerId() == $objFWUser->objUser->getId()
                )
            ) {
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_DOWNLOAD_ID'             => $objDownload->getId(),
                    'DOWNLOADS_DOWNLOAD_DOWNLOAD_ICON'  => $objDownload->getIcon(true),
                    'DOWNLOADS_DOWNLOAD_SOURCE'         => htmlentities($objDownload->getSource(), ENT_QUOTES, CONTREXX_CHARSET)
                ));

                $this->objTemplate->parse('downloads_download_function_download_link');
                $this->objTemplate->hideBlock('downloads_download_function_no_download_link');
            } else {
                $this->objTemplate->hideBlock('downloads_download_function_download_link');
                $this->objTemplate->touchBlock('downloads_download_function_no_download_link');
            }


            // parse unlink button
            if (// managers are allowed to unlink every download
                \Permission::checkAccess(143, 'static', true)
                // the selected category must be valid to proceed future permission checks.
                // this is required to protect the overview section from non-admins
                || $objCategory->getId() && (
                    // the category isn't protected => everyone is allowed to unlink downloads
                    !$objCategory->getManageFilesAccessId()
                    // the category is protected => only those who have the sufficent permissions are allowed to unlink downloads
                    || \Permission::checkAccess($objCategory->getManageFilesAccessId(), 'dynamic', true)
                    // the owner of the category is allowed to unlink all downloads
                    || $objCategory->getOwnerId() == $objFWUser->objUser->getId()
                )
                // the owner of the download is allowed to unlink it
                || $objDownload->getOwnerId() == $objFWUser->objUser->getId()
            ) {
                $this->objTemplate->setVariable(array(
                    'TXT_DOWNLOADS_UNLINK'                  => $_ARRAYLANG['TXT_DOWNLOADS_UNLINK'],
                    'DOWNLOADS_DOWNLOAD_NAME_JS'            => htmlspecialchars($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET),
                ));

                // parse delete icon
                $this->objTemplate->parse('downloads_download_function_unlink_link');
                $this->objTemplate->hideBlock('downloads_download_function_no_unlink_link');
            } else {
                // hide delete icon
                $this->objTemplate->touchBlock('downloads_download_function_no_unlink_link');
                $this->objTemplate->hideBlock('downloads_download_function_unlink_link');
            }


            $description = $objDownload->getDescription();
            if (strlen($description) > 100) {
                $description = substr($description, 0, 97).'...';
            }

            $this->objTemplate->setVariable(array(
            // parse download id
                'DOWNLOADS_DOWNLOAD_ID'             => $objDownload->getId(),
                'DOWNLOADS_DOWNLOAD_NAME'           => htmlentities($objDownload->getName(), ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_DOWNLOAD_DESCRIPTION'    => htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET),
                'DOWNLOADS_DOWNLOAD_OWNER'          => contrexx_raw2xhtml($this->getParsedUsername($objDownload->getOwnerId())),
                'DOWNLOADS_DOWNLOAD_DOWNLOADED'     => $objDownload->getDownloadCount(),
                'DOWNLOADS_DOWNLOAD_VIEWED'         => $objDownload->getViewCount(),
                'DOWNLOADS_DOWNLOAD_STATUS_LED'     => $objDownload->getActiveStatus() ? 'led_green.gif' : 'led_red.gif',
                //'DOWNLOADS_DOWNLOAD_ICON'           => $objDownload->getIcon(),
                'DOWNLOADS_DOWNLOAD_ROW_CLASS'      => $nr++ % 2 ? 'row1' : 'row2'
            ));

            $this->objTemplate->parse('downloads_download_list');

            $objDownload->next();
        }

        if ($downloadsAvailable && !empty($this->parentCategoryId)) {
            $this->objTemplate->setVariable('TXT_DOWNLOADS_OF_CATEGORY', sprintf($_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS_OF_CATEGORY'], '&bdquo;'.htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET).'&ldquo;'));
        } else {
            $this->objTemplate->setVariable('TXT_DOWNLOADS_ALL_DOWNLOADS', $_ARRAYLANG['TXT_DOWNLOADS_ALL_DOWNLOADS']);
        }

        if ($downloadsAvailable) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_CONFIRM_UNLINK_DOWNLOADS_TXT'    => preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_UNLINK_DOWNLOADS'])),
                'TXT_SAVE_CHANGES_DOWNLOADS'                => $_ARRAYLANG['TXT_SAVE_CHANGES'],
                'TXT_DOWNLOADS_CHECK_ALL'                   => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
                'TXT_DOWNLOADS_UNCHECK_ALL'                 => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
                'TXT_DOWNLOADS_SELECT_ACTION'               => $_ARRAYLANG['TXT_DOWNLOADS_SELECT_ACTION'],
                'TXT_DOWNLOADS_ORDER'                       => $_ARRAYLANG['TXT_DOWNLOADS_ORDER'],
                'TXT_DOWNLOADS_UNLINK_MULTI'                => $_ARRAYLANG['TXT_DOWNLOADS_UNLINK_MULTI']
            ));

            $this->objTemplate->setVariable(array(
                 // parse table header
                'TXT_DOWNLOADS_OWNER'                       => $_ARRAYLANG['TXT_DOWNLOADS_OWNER'],
                'TXT_DOWNLOADS_FUNCTIONS'                   => $_ARRAYLANG['TXT_DOWNLOADS_FUNCTIONS'],
                'DOWNLOADS_DOWNLOAD_CATEGORY_ID'            => $objCategory->getId(),

                // parse sorting
                'DOWNLOADS_DOWNLOAD_SORT_DIRECTION'         => $downloadOrderDirection,
                'DOWNLOADS_DOWNLOAD_SORT_BY'                => $downloadOrderBy,
                'DOWNLOADS_DOWNLOAD_SORT_ID'                => ($downloadOrderBy == 'id' && $downloadOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_DOWNLOAD_SORT_STATUS'            => ($downloadOrderBy == 'is_active' && $downloadOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_DOWNLOAD_SORT_ORDER'             => ($downloadOrderBy == 'order' && $downloadOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_DOWNLOAD_SORT_NAME'              => ($downloadOrderBy == 'name' && $downloadOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_DOWNLOAD_SORT_DESCRIPTION'       => ($downloadOrderBy == 'description' && $downloadOrderDirection == 'asc') ? 'desc' : 'asc',
                'DOWNLOADS_DOWNLOAD_SORT_DOWNLOADED'        => ($downloadOrderBy == 'download_count' && $downloadOrderDirection == 'desc') ? 'asc' : 'desc',
                'DOWNLOADS_DOWNLOAD_SORT_VIEWED'            => ($downloadOrderBy == 'views' && $downloadOrderDirection == 'desc') ? 'asc' : 'desc',
                'DOWNLOADS_DOWNLOAD_SORT_ID_LABEL'          => $_ARRAYLANG['TXT_DOWNLOADS_ID'].($downloadOrderBy == 'id' ? $downloadOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_DOWNLOAD_SORT_STATUS_LABEL'      => $_ARRAYLANG['TXT_DOWNLOADS_STATUS'].($downloadOrderBy == 'is_active' ? $downloadOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_DOWNLOAD_SORT_ORDER_LABEL'       => $_ARRAYLANG['TXT_DOWNLOADS_ORDER'].($downloadOrderBy == 'order' ? $downloadOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_DOWNLOAD_SORT_NAME_LABEL'        => $_ARRAYLANG['TXT_DOWNLOADS_NAME'].($downloadOrderBy == 'name' ? $downloadOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_DOWNLOAD_SORT_DESCRIPTION_LABEL' => $_ARRAYLANG['TXT_DOWNLOADS_DESCRIPTION'].($downloadOrderBy == 'description' ? $downloadOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_DOWNLOAD_SORT_DOWNLOADED_LABEL'  => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADED'].($downloadOrderBy == 'download_count' ? $downloadOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_DOWNLOAD_SORT_VIEWED_LABEL'      => $_ARRAYLANG['TXT_DOWNLOADS_VIEWED'].($downloadOrderBy == 'views' ? $downloadOrderDirection == 'asc' ? ' &uarr;' : ' &darr;' : ''),
                'DOWNLOADS_DOWNLOAD_CATEGORY_SORT'          => $categoryOrderDirection,
                'DOWNLOADS_DOWNLOAD_CATEGORY_BY'            => $categoryOrderBy,
                'DOWNLOADS_DOWNLOAD_CATEGORY_OFFSET'        => $categoryLimitOffset,
            ));

            // parse paging
            $downloadCount = $objDownload->getFilteredSearchDownloadCount();
            if ($downloadCount > $_CONFIG['corePagingLimit']) {
                $pagingLink = "&cmd=Downloads&act=categories&parent_id=".$objCategory->getId()
                    ."&category_sort=".htmlspecialchars($categoryOrderDirection)
                    ."&category_by=".htmlspecialchars($categoryOrderBy)
                    ."&download_sort=".htmlspecialchars($downloadOrderDirection)
                    ."&download_by=".htmlspecialchars($downloadOrderBy)
                    ."&category_pos=".$categoryLimitOffset;
                $this->objTemplate->setVariable('DOWNLOADS_DOWNLOAD_PAGING', getPaging($downloadCount, $downloadLimitOffset, $pagingLink, "<b>".$_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS']."</b>").'<br />');
            }

            $this->objTemplate->hideBlock('downloads_no_data');
            $this->objTemplate->parse('downloads_category_downloads');
            $this->objTemplate->parse('downloads_download_action_dropdown');
        } else {
            $this->objTemplate->hideBlock('downloads_category_downloads');
            $this->objTemplate->hideBlock('downloads_download_action_dropdown');
            $this->objTemplate->parse('downloads_no_data');
        }
    }


    /**
     * Settings page
     * @global array $_ARRAYLANG
     */
    private function settings()
    {
        global $_ARRAYLANG;

        \Permission::checkAccess(142, 'static');


        $this->_pageTitle = $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS'];
        $this->objTemplate->loadTemplateFile('module_downloads_settings.html');

        /**
         * @ignore
         */
        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/PEAR/Download.php');

        $linkMethods = array(
            'detail',
            HTTP_DOWNLOAD_ATTACHMENT,
            HTTP_DOWNLOAD_INLINE,
        );

        if (isset($_POST['downloads_settings_save'])) {
            $this->arrConfig['overview_cols_count']         = !empty($_POST['downloads_settings_col_count']) ? intval($_POST['downloads_settings_col_count']) : $this->arrConfig['overview_cols_count'];
            $this->arrConfig['overview_max_subcats']        = !empty($_POST['downloads_settings_subcat_count']) ? intval($_POST['downloads_settings_subcat_count']) : $this->arrConfig['overview_max_subcats'];
            $this->arrConfig['use_attr_metakeys']           = !empty($_POST['downloads_settings_attribute_metakeys']) ? intval($_POST['downloads_settings_attribute_metakeys']) : 0;
            $this->arrConfig['use_attr_size']               = !empty($_POST['downloads_settings_attribute_size']) ? intval($_POST['downloads_settings_attribute_size']) : 0;
            $this->arrConfig['use_attr_license']            = !empty($_POST['downloads_settings_attribute_license']) ? intval($_POST['downloads_settings_attribute_license']) : 0;
            $this->arrConfig['use_attr_version']            = !empty($_POST['downloads_settings_attribute_version']) ? intval($_POST['downloads_settings_attribute_version']) : 0;
            $this->arrConfig['use_attr_author']             = !empty($_POST['downloads_settings_attribute_author']) ? intval($_POST['downloads_settings_attribute_author']) : 0;
            $this->arrConfig['use_attr_website']            = !empty($_POST['downloads_settings_attribute_website']) ? intval($_POST['downloads_settings_attribute_website']) : 0;
            $this->arrConfig['list_downloads_current_lang'] = !empty($_POST['downloads_settings_list_downloads_current_lang']) ? contrexx_input2int($_POST['downloads_settings_list_downloads_current_lang']) : 0;
            $this->arrConfig['integrate_into_search_component'] = !empty($_POST['downloads_settings_integrate_into_search_component']) ? contrexx_input2int($_POST['downloads_settings_integrate_into_search_component']) : 0;

            if (
                !empty(
                    $_POST['downloads_settings_global_search_linking']
                ) &&
                in_array(
                    $_POST['downloads_settings_global_search_linking'],
                    $linkMethods
                )
            ) {
                $this->arrConfig['global_search_linking'] =
                    $_POST['downloads_settings_global_search_linking'];
            } else {
                $this->arrConfig['global_search_linking'] =
                    current($linkMethods);
            }

            $this->arrConfig['auto_file_naming']            = isset($_POST['downloads_setting_auto_naming']) ? contrexx_input2raw($_POST['downloads_setting_auto_naming']) : $this->arrConfig['auto_file_naming'];
            $this->arrConfig['pretty_regex_pattern']        = isset($_POST['downloads_setting_pretty_format']) ? contrexx_input2raw($_POST['downloads_setting_pretty_format']) : $this->arrConfig['pretty_regex_pattern'];
            $this->arrConfig['most_viewed_file_count']      = !empty($_POST['downloads_settings_most_viewed_file_count']) ? intval($_POST['downloads_settings_most_viewed_file_count']) : $this->arrConfig['most_viewed_file_count'];
            $this->arrConfig['most_downloaded_file_count']  = !empty($_POST['downloads_settings_most_downloaded_file_count']) ? intval($_POST['downloads_settings_most_downloaded_file_count']) : $this->arrConfig['most_downloaded_file_count'];
            $this->arrConfig['most_popular_file_count']     = !empty($_POST['downloads_settings_most_popular_file_count']) ? intval($_POST['downloads_settings_most_popular_file_count']) : $this->arrConfig['most_popular_file_count'];
            $this->arrConfig['newest_file_count']           = !empty($_POST['downloads_settings_newest_file_count']) ? intval($_POST['downloads_settings_newest_file_count']) : $this->arrConfig['newest_file_count'];
            $this->arrConfig['updated_file_count']          = !empty($_POST['downloads_settings_updated_file_count']) ? intval($_POST['downloads_settings_updated_file_count']) : $this->arrConfig['updated_file_count'];
            $this->arrConfig['new_file_time_limit']         = !empty($_POST['downloads_settings_new_file_time_limit']) ? intval($_POST['downloads_settings_new_file_time_limit']) : $this->arrConfig['new_file_time_limit'];
            $this->arrConfig['updated_file_time_limit']     = !empty($_POST['downloads_settings_updated_file_time_limit']) ? intval($_POST['downloads_settings_updated_file_time_limit']) : $this->arrConfig['updated_file_time_limit'];
            $this->arrConfig['associate_user_to_groups']    = !empty($_POST['downloads_settings_associate_user_to_groups_associated_groups']) ? implode(',', array_map('intval', $_POST['downloads_settings_associate_user_to_groups_associated_groups'])) : $this->arrConfig['associate_user_to_groups'];
            $this->arrConfig['downloads_sorting_order']     = (     !empty($_POST['downloads_settings_sorting_downloads'])
                                                                && array_key_exists($_POST['downloads_settings_sorting_downloads'], $this->downloadsSortingOptions)
                                                              ) ? $_POST['downloads_settings_sorting_downloads'] : $this->arrConfig['downloads_sorting_order'];
            $this->arrConfig['categories_sorting_order']    = (     !empty($_POST['downloads_settings_sorting_categories'])
                                                                &&  array_key_exists($_POST['downloads_settings_sorting_categories'], $this->categoriesSortingOptions)
                                                              ) ? $_POST['downloads_settings_sorting_categories'] : $this->arrConfig['categories_sorting_order'];

            $this->updateSettings();
        }

        $objFWUser = \FWUser::getFWUserObject();
        $objGroup = $objFWUser->objGroup->getGroups();
        $arrGroups = explode(',', $this->arrConfig['associate_user_to_groups']);
        $associatedGroups = '';
        $notAssociatedGroups = '';

        while (!$objGroup->EOF) {
            $option = '<option value="'.$objGroup->getId().'">'.htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET).' ['.$objGroup->getType().']</option>';
            if (in_array($objGroup->getId(), $arrGroups)) {
                $associatedGroups .= $option;
            } else {
                $notAssociatedGroups .= $option;
            }

            $objGroup->next();
        }

        //Parse the option 'Downloads' and 'Categories' dropdown in the 'Sorting' section
        $this->parseSettingsDropDown($this->objTemplate, $this->downloadsSortingOptions, $this->arrConfig['downloads_sorting_order'], 'downloads');
        $this->parseSettingsDropDown($this->objTemplate, $this->categoriesSortingOptions, $this->arrConfig['categories_sorting_order'], 'categories');

        $linkMethodOptions = '';
        foreach ($linkMethods as $linkMethod) {
            $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
            $option->setAttribute('value', $linkMethod);
            if (
                $this->arrConfig['global_search_linking'] ==
                $linkMethod
            ) {
                $option->setAttribute('selected', 'selected');
            }
            $optionText = new \Cx\Core\Html\Model\Entity\TextElement(
                sprintf(
                    $_ARRAYLANG[
                        'TXT_DOWNLOADS_SEARCH_LINK_METHOD_' .
                        strtoupper($linkMethod)
                    ],
                    $linkMethod
                )
            );
            $option->addChild($optionText);
            $linkMethodOptions .= $option;
        }

        $action = '';
        if (isset($_GET['act'])) {
            $action = $_GET['act'];
        }
        $isEmailTemplateTab =
            ($action == 'mailtemplate_overview' || $action == 'mailtemplate_edit');
        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_SETTINGS_LISTING'                => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_LISTING'],
            'TXT_DOWNLOADS_SETTINGS_SEARCH'                 => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_SEARCH'],
            'TXT_DOWNLOADS_INTEGRATE_INTO_SEARCH_COMPONENT' => $_ARRAYLANG['TXT_DOWNLOADS_INTEGRATE_INTO_SEARCH_COMPONENT'],
            'TXT_DOWNLOADS_INTEGRATE_INTO_SEARCH_COMPONENT_TXT'=> $_ARRAYLANG['TXT_DOWNLOADS_INTEGRATE_INTO_SEARCH_COMPONENT_TXT'],
            'TXT_DOWNLOADS_SEARCH_LINK_METHOD'              => $_ARRAYLANG['TXT_DOWNLOADS_SEARCH_LINK_METHOD'],
            'TXT_DOWNLOADS_OVERVIEW_PAGE'                   => $_ARRAYLANG['TXT_DOWNLOADS_OVERVIEW_PAGE'],
            'TXT_DOWNLOADS_COL_COUNT'                       => $_ARRAYLANG['TXT_DOWNLOADS_COL_COUNT'],
            'TXT_DOWNLOADS_COL_COUNT_DESC'                  => $_ARRAYLANG['TXT_DOWNLOADS_COL_COUNT_DESC'],
            'TXT_DOWNLOADS_SUBCAT_COUNT'                    => $_ARRAYLANG['TXT_DOWNLOADS_SUBCAT_COUNT'],
            'TXT_DOWNLOADS_SUBCAT_COUNT_DESC'               => $_ARRAYLANG['TXT_DOWNLOADS_SUBCAT_COUNT_DESC'],
            'TXT_DOWNLOADS_BLOCKS'                          => $_ARRAYLANG['TXT_DOWNLOADS_BLOCKS'],
            'TXT_DOWNLOADS_MOST_VIEWED_FILE_COUNT'          => $_ARRAYLANG['TXT_DOWNLOADS_MOST_VIEWED_FILE_COUNT'],
            'TXT_DOWNLOADS_MOST_VIEWED_FILE_COUNT_DESC'     => $_ARRAYLANG['TXT_DOWNLOADS_MOST_VIEWED_FILE_COUNT_DESC'],
            'TXT_DOWNLOADS_MOST_DOWNLOADED_FILE_COUNT'      => $_ARRAYLANG['TXT_DOWNLOADS_MOST_DOWNLOADED_FILE_COUNT'],
            'TXT_DOWNLOADS_MOST_DOWNLOADED_FILE_COUNT_DESC' => $_ARRAYLANG['TXT_DOWNLOADS_MOST_DOWNLOADED_FILE_COUNT_DESC'],
            'TXT_DOWNLOADS_MOST_POPULAR_FILE_COUNT'         => $_ARRAYLANG['TXT_DOWNLOADS_MOST_POPULAR_FILE_COUNT'],
            'TXT_DOWNLOADS_MOST_POPULAR_FILE_COUNT_DESC'    => $_ARRAYLANG['TXT_DOWNLOADS_MOST_POPULAR_FILE_COUNT_DESC'],
            'TXT_DOWNLOADS_NEWEST_FILE_COUNT'               => $_ARRAYLANG['TXT_DOWNLOADS_NEWEST_FILE_COUNT'],
            'TXT_DOWNLOADS_NEWEST_FILE_COUNT_DESC'          => $_ARRAYLANG['TXT_DOWNLOADS_NEWEST_FILE_COUNT_DESC'],
            'TXT_DOWNLOADS_UPDATE_FILE_COUNT'               => $_ARRAYLANG['TXT_DOWNLOADS_UPDATE_FILE_COUNT'],
            'TXT_DOWNLOADS_UPDATE_FILE_COUNT_DESC'          => $_ARRAYLANG['TXT_DOWNLOADS_UPDATE_FILE_COUNT_DESC'],
            'TXT_DOWNLOADS_SETTINGS_STATISTICS'             => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_STATISTICS'],
            'TXT_DOWNLOADS_NEW_FILE_TIME_LIMIT'             => $_ARRAYLANG['TXT_DOWNLOADS_NEW_FILE_TIME_LIMIT'],
            'TXT_DOWNLOADS_NEW_FILE_TIME_LIMIT_DESC'        => $_ARRAYLANG['TXT_DOWNLOADS_NEW_FILE_TIME_LIMIT_DESC'],
            'TXT_DOWNLOADS_UPDATED_FILE_TIME_LIMIT'         => $_ARRAYLANG['TXT_DOWNLOADS_UPDATED_FILE_TIME_LIMIT'],
            'TXT_DOWNLOADS_UPDATED_FILE_TIME_LIMIT_DESC'    => $_ARRAYLANG['TXT_DOWNLOADS_UPDATED_FILE_TIME_LIMIT_DESC'],
            'TXT_DOWNLOADS_SECONDS_COMB_EXAMPLES'           => $_ARRAYLANG['TXT_DOWNLOADS_SECONDS_COMB_EXAMPLES'],
            'TXT_DOWNLOADS_SETTINGS_ATTRIBUTES'             => $_ARRAYLANG['TXT_DOWNLOADS_ATTRIBUTES'],
            'TXT_DOWNLOADS_ATTRIBUTES_DESC'                 => $_ARRAYLANG['TXT_DOWNLOADS_ATTRIBUTES_DESC'],
            'TXT_DOWNLOADS_METAKEYS'                        => $_ARRAYLANG['TXT_DOWNLOADS_METAKEYS'],
            'TXT_DOWNLOADS_SIZE'                            => $_ARRAYLANG['TXT_DOWNLOADS_SIZE'],
            'TXT_DOWNLOADS_LICENSE'                         => $_ARRAYLANG['TXT_DOWNLOADS_LICENSE'],
            'TXT_DOWNLOADS_VERSION'                         => $_ARRAYLANG['TXT_DOWNLOADS_VERSION'],
            'TXT_DOWNLOADS_AUTHOR'                          => $_ARRAYLANG['TXT_DOWNLOADS_AUTHOR'],
            'TXT_DOWNLOADS_WEBSITE'                         => $_ARRAYLANG['TXT_DOWNLOADS_WEBSITE'],
            'TXT_DOWNLOADS_SAVE'                            => $_ARRAYLANG['TXT_DOWNLOADS_SAVE'],
            'TXT_DOWNLOADS_UNCHECK_ALL'                     => $_ARRAYLANG['TXT_DOWNLOADS_UNCHECK_ALL'],
            'TXT_DOWNLOADS_CHECK_ALL'                       => $_ARRAYLANG['TXT_DOWNLOADS_CHECK_ALL'],
            'TXT_DOWNLOADS_GENERAL'                         => $_ARRAYLANG['TXT_DOWNLOADS_GENERAL'],
            'TXT_DOWNLOADS_INTERFACES'                      => $_ARRAYLANG['TXT_DOWNLOADS_INTERFACES'],
            'TXT_DOWNLOADS_SETTINGS_ASSET_SELECTION'        => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_ASSET_SELECTION'],
            'TXT_DOWNLOADS_SETTINGS_AUTO_NAMING'            => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_AUTO_NAMING'],
            'TXT_DOWNLOADS_SETTINGS_AUTO_NAMING_DISABLE'    => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_AUTO_NAMING_DISABLE'],
            'TXT_DOWNLOADS_SETTINGS_AUTO_NAMING_ENABLE'     => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_AUTO_NAMING_ENABLE'],
            'TXT_DOWNLOADS_SETTINGS_PRETTY_FORMAT_TOOLTIP'  => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_PRETTY_FORMAT_TOOLTIP'],
            'TXT_DOWNLOADS_EMAIL_TEMPLATES'                 => $_ARRAYLANG['TXT_DOWNLOADS_EMAIL_TEMPLATES'],
            'TXT_DOWNLOADS_USER_ADMIN'                      => $_ARRAYLANG['TXT_DOWNLOADS_USER_ADMIN'],
            'TXT_DOWNLOADS_AUTOMATIC_CATEGORY_CREATION'     => $_ARRAYLANG['TXT_DOWNLOADS_AUTOMATIC_CATEGORY_CREATION'],
            'TXT_DOWNLOADS_AUTOMATIC_CATEGORY_CREATION_DESC'=> $_ARRAYLANG['TXT_DOWNLOADS_AUTOMATIC_CATEGORY_CREATION_DESC'],
            'TXT_DOWNLOADS_AVAILABLE_USER_GROUPS'           => $_ARRAYLANG['TXT_DOWNLOADS_AVAILABLE_USER_GROUPS'],
            'TXT_DOWNLOADS_ASSIGNED_USER_GROUPS'            => $_ARRAYLANG['TXT_DOWNLOADS_ASSIGNED_USER_GROUPS'],
            'TXT_DOWNLOADS_SETTINGS_SORTING'                => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_SORTING'],
            'TXT_DOWNLOADS_SETTINGS_OPTION_DOWNLOADS'       => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_OPTION_DOWNLOADS'],
            'TXT_DOWNLOADS_SETTINGS_OPTION_CATEGORIES'      => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES'],
            'TXT_DOWNLOADS_SETTINGS_OPTION_SORTING_DESC'    => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_OPTION_SORTING_DESC'],
            'TXT_DOWNLOADS_SETTINGS_CUSTOM_LABEL'           => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_CUSTOM_LABEL'],
            'TXT_DOWNLOADS_SETTINGS_ALPHABETIC_LABEL'       => $_ARRAYLANG['TXT_DOWNLOADS_SETTINGS_ALPHABETIC_LABEL'],
            'TXT_DOWNLOADS_LIST_DOWNLOADS_CURRENT_LANG'     => $_ARRAYLANG['TXT_DOWNLOADS_LIST_DOWNLOADS_CURRENT_LANG'],
            'TXT_DOWNLOADS_LIST_DOWNLOADS_CURRENT_LANG_DESC'=> $_ARRAYLANG['TXT_DOWNLOADS_LIST_DOWNLOADS_CURRENT_LANG_DESC'],
            'DOWNLOADS_SETTINGS_COL_COUNT'                  => $this->arrConfig['overview_cols_count'],
            'DOWNLOADS_SETTINGS_SUBCAT_COUNT'               => $this->arrConfig['overview_max_subcats'],
            'DOWNLOADS_SETTINGS_ATTRIBUTE_METAKEYS_CHECKED' => $this->arrConfig['use_attr_metakeys'] ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_ATTRIBUTE_SIZE_CHECKED'     => $this->arrConfig['use_attr_size'] ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_ATTRIBUTE_LICENSE_CHECKED'  => $this->arrConfig['use_attr_license'] ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_ATTRIBUTE_VERSION_CHECKED'  => $this->arrConfig['use_attr_version'] ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_ATTRIBUTE_AUTHOR_CHECKED'   => $this->arrConfig['use_attr_author'] ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_ATTRIBUTE_WEBSITE_CHECKED'  => $this->arrConfig['use_attr_website'] ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_LIST_DOWNLOADS_CURRENT_LANG'=> $this->arrConfig['list_downloads_current_lang'] ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_INTEGRATE_INTO_SEARCH_COMPONENT'=> $this->arrConfig['integrate_into_search_component'] ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_AUTO_NAMING_DISABLE_CHECKED' => $this->arrConfig['auto_file_naming'] == 'off' ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_AUTO_NAMING_ENABLE_CHECKED' => $this->arrConfig['auto_file_naming'] == 'on' ? 'checked="checked"' : '',
            'DOWNLOADS_SETTINGS_PRETTY_FORMAT'              => contrexx_raw2xhtml($this->arrConfig['pretty_regex_pattern']),
            'DOWNLOADS_SETTINGS_SEARCH_LINK_OPTIONS'        => $linkMethodOptions,
            'DOWNLOADS_SETTINGS_MOST_VIEWED_FILE_COUNT'     => $this->arrConfig['most_viewed_file_count'],
            'DOWNLOADS_SETTINGS_MOST_DOWNLOADED_FILE_COUNT' => $this->arrConfig['most_downloaded_file_count'],
            'DOWNLOADS_SETTINGS_MOST_POPULAR_FILE_COUNT'    => $this->arrConfig['most_popular_file_count'],
            'DOWNLOADS_SETTINGS_NEWEST_FILE_COUNT'          => $this->arrConfig['newest_file_count'],
            'DOWNLOADS_SETTINGS_UPDATED_FILE_COUNT'         => $this->arrConfig['updated_file_count'],
            'DOWNLOADS_SETTINGS_NEW_FILE_TIME_LIMIT'        => $this->arrConfig['new_file_time_limit'],
            'DOWNLOADS_SETTINGS_UPDATEDED_FILE_TIME_LIMIT'  => $this->arrConfig['updated_file_time_limit'],
            'DOWNLOADS_SETTINGS_NOT_ASSOCIATED_GROUPS'      => $notAssociatedGroups,
            'DOWNLOADS_SETTINGS_ASSOCIATED_GROUPS'          => $associatedGroups,
            'DOWNLOADS_SORTING_TAB_STATUS'                  => $isEmailTemplateTab ? 'inactive' : 'active',
            'DOWNLOADS_EMAIL_TEMPLATE_TAB_STATUS'           => $isEmailTemplateTab ? 'active' : 'inactive',
            'DOWNLOADS_SORTING_TAB_DIV_STATUS'              => $isEmailTemplateTab ? 'none' : 'block',
            'DOWNLOADS_EMAIL_TEMPLATE_DIV_STATUS'           => $isEmailTemplateTab ? 'block' : 'none'
        ));

        $this->parseEmailTemplates();
    }

    /**
     * Parse Email Templates
     */
    public function parseEmailTemplates()
    {
        global $_CORELANG, $_ARRAYLANG;

        $_REQUEST['active_tab'] = 1;
        if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'mailtemplate_edit') {
                $_REQUEST['active_tab'] = 2;
        }
        \Cx\Core\MailTemplate\Controller\MailTemplate::deleteTemplate('Downloads');
        // If there is anything to be stored, and if that fails, return to
        // the edit view in order to save the posted form content
        $result_store =
            \Cx\Core\MailTemplate\Controller\MailTemplate::storeFromPost(
                'Downloads'
            );
        if ($result_store === false) {
            $_REQUEST['active_tab'] = 2;
        }

        // reinitialize MailTemplates to fetch the current number of MailTemplates
        $count = 0;
        \Cx\Core\MailTemplate\Controller\MailTemplate::init('Downloads', null, '', 0, -1, $count);

        $objTemplate = null;

        //Parse Email template overview tab (if any exist)
        if ($count) {
            \Cx\Core\Setting\Controller\Setting::show_external(
                $objTemplate,
                $_CORELANG['TXT_CORE_MAILTEMPLATES'],
                \Cx\Core\MailTemplate\Controller\MailTemplate::overview(
                    'Downloads',
                    'config',
                    \Cx\Core\Setting\Controller\Setting::getValue(
                        'numof_mailtemplate_per_page_backend',
                        'Downloads'
                    )
                )->get()
            );
        }

        //Parse Add/Edit Mail Template tab
        $tabName = $_CORELANG['TXT_CORE_MAILTEMPLATE_EDIT'];
        if (empty($_REQUEST['key'])) {
            $tabName = $_CORELANG['TXT_CORE_MAILTEMPLATE_ADD'];
        }
        \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            $tabName,
            \Cx\Core\MailTemplate\Controller\MailTemplate::edit(
                'Downloads'
            )->get()
        );

        //Parse Placeholder tab
        $template = new \Cx\Core\Html\Sigma(
            \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath() .
            '/Downloads/View/Template/Backend'
        );
        $template->setErrorHandling(PEAR_ERROR_DIE);
        $template->loadTemplateFile(
            'module_downloads_settings_placeholders.html'
        );
        $template->setGlobalVariable($_ARRAYLANG);
        \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            $_ARRAYLANG['TXT_DOWNLOADS_PLACEHOLDERS'],
            $template->get()
        );

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_EMAIL_TEMPLATE_SETTINGS' => $objTemplate->get()
        ));
    }

    /**
     * placeholder
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     */
    function _placeholder()
    {
        global $_ARRAYLANG;

        $this->_pageTitle = $_ARRAYLANG['TXT_PLACEHOLDER'];
        $this->objTemplate->loadTemplateFile('placeholder.html');
        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_DOWNLOADS' => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS'],
            'TXT_DOWNLOADS_ICONS' => $_ARRAYLANG['TXT_DOWNLOADS_ICONS'],
            'TXT_DOWNLOADS_CATEGORIES' => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES'],
            'TXT_PLACEHOLDER_FILE_ID' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_ID'],
            'TXT_PLACEHOLDER_FILE_NAME' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_NAME'],
            'TXT_PLACEHOLDER_FILE_DESC' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_DESC'],
            'TXT_PLACEHOLDER_FILE_TYPE' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_TYPE'],
            'TXT_PLACEHOLDER_FILE_SIZE' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_SIZE'],
            'TXT_PLACEHOLDER_FILE_IMG' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_IMG'],
            'TXT_PLACEHOLDER_FILE_AUTHOR' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_AUTHOR'],
            'TXT_PLACEHOLDER_FILE_CREATED' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_CREATED'],
            'TXT_PLACEHOLDER_FILE_LICENSE' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_LICENSE'],
            'TXT_PLACEHOLDER_FILE_VERSION' => $_ARRAYLANG['TXT_PLACEHOLDER_FILE_VERSION'],
            'TXT_PLACEHOLDER_CATEGORY_ID' => $_ARRAYLANG['TXT_PLACEHOLDER_CATEGORY_ID'],
            'TXT_PLACEHOLDER_CATEGORY_NAME' => $_ARRAYLANG['TXT_PLACEHOLDER_CATEGORY_NAME'],
            'TXT_PLACEHOLDER_CATEGORY_DESC' => $_ARRAYLANG['TXT_PLACEHOLDER_CATEGORY_DESC'],
            'TXT_PLACEHOLDER_ICON_DISPLAY' => $_ARRAYLANG['TXT_PLACEHOLDER_ICON_DISPLAY'],
            'TXT_PLACEHOLDER_ICON_FILTERS' => $_ARRAYLANG['TXT_PLACEHOLDER_ICON_FILTERS'],
            'TXT_PLACEHOLDER_ICON_CATEGORY' => $_ARRAYLANG['TXT_PLACEHOLDER_ICON_CATEGORY'],
            'TXT_PLACEHOLDER_ICON_FILE' => $_ARRAYLANG['TXT_PLACEHOLDER_ICON_FILE'],
            'TXT_PLACEHOLDER_ICON_DOWNLOAD' => $_ARRAYLANG['TXT_PLACEHOLDER_ICON_DOWNLOAD'],
            'TXT_PLACEHOLDER_ICON_INFO' => $_ARRAYLANG['TXT_PLACEHOLDER_ICON_INFO'],
        ));
    }

}

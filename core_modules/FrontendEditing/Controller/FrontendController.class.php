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
 * Class FrontendController
 *
 * This is the frontend controller for the frontend editing.
 * This adds the necessary javascripts and toolbars
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_frontendediting
 * @version     1.0.0
 */

namespace Cx\Core_Modules\FrontendEditing\Controller;

/**
 * Class FrontendController
 *
 * This is the frontend controller for the frontend editing.
 * This adds the necessary javascripts and toolbars
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_frontendediting
 * @version     1.0.0
 */
class FrontendController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * Init the frontend editing.
     *
     * Register the javascripts and css files
     * Adds the used language variables to cloudrexx-js variables, so the toolbar has access to these variables
     *
     * @param ComponentController $componentController
     */
    public function initFrontendEditing(\Cx\Core_Modules\FrontendEditing\Controller\ComponentController $componentController)
    {
        global $_ARRAYLANG;

        // get necessary objects
        $objInit = \Env::get('init');
        $page = $this->cx->getPage();

        // add css and javascript file
        $jsFilesRoot = substr(ASCMS_CORE_MODULE_FOLDER . '/' . $componentController->getName() . '/View/Script', 1);

        \JS::registerCSS(substr(ASCMS_CORE_MODULE_FOLDER . '/' . $componentController->getName() . '/View/Style/Main.css', 1));
        \JS::registerJS($jsFilesRoot . '/Main.js');
        \JS::activate('cx');
        // not used for contrexx version 3.1
//        \JS::registerJS($jsFilesRoot . '/CKEditorPlugins.js');

        // activate ckeditor
        \JS::activate('ckeditor');
        \JS::activate('js-cookie');

        // load language data
        $_ARRAYLANG = $objInit->loadLanguageData('FrontendEditing');
        $langVariables = array(
            'TXT_FRONTEND_EDITING_SHOW_TOOLBAR' => $_ARRAYLANG['TXT_FRONTEND_EDITING_SHOW_TOOLBAR'],
            'TXT_FRONTEND_EDITING_HIDE_TOOLBAR' => $_ARRAYLANG['TXT_FRONTEND_EDITING_HIDE_TOOLBAR'],
            'TXT_FRONTEND_EDITING_PUBLISH' => $_ARRAYLANG['TXT_FRONTEND_EDITING_PUBLISH'],
            'TXT_FRONTEND_EDITING_SUBMIT_FOR_RELEASE' => $_ARRAYLANG['TXT_FRONTEND_EDITING_SUBMIT_FOR_RELEASE'],
            'TXT_FRONTEND_EDITING_REFUSE_RELEASE' => $_ARRAYLANG['TXT_FRONTEND_EDITING_REFUSE_RELEASE'],
            'TXT_FRONTEND_EDITING_SAVE' => $_ARRAYLANG['TXT_FRONTEND_EDITING_SAVE'],
            'TXT_FRONTEND_EDITING_EDIT' => $_ARRAYLANG['TXT_FRONTEND_EDITING_EDIT'],
            'TXT_FRONTEND_EDITING_CANCEL_EDIT' => $_ARRAYLANG['TXT_FRONTEND_EDITING_CANCEL_EDIT'],
            'TXT_FRONTEND_EDITING_FINISH_EDIT_MODE' => $_ARRAYLANG['TXT_FRONTEND_EDITING_FINISH_EDIT_MODE'],
            'TXT_FRONTEND_EDITING_THE_DRAFT' => $_ARRAYLANG['TXT_FRONTEND_EDITING_THE_DRAFT'],
            'TXT_FRONTEND_EDITING_SAVE_CURRENT_STATE' => $_ARRAYLANG['TXT_FRONTEND_EDITING_SAVE_CURRENT_STATE'],
            'TXT_FRONTEND_EDITING_CONFIRM_BLOCK_SAVE' => $_ARRAYLANG['TXT_FRONTEND_EDITING_CONFIRM_BLOCK_SAVE'],
            'TXT_FRONTEND_EDITING_MODULE_PAGE' => $_ARRAYLANG['TXT_FRONTEND_EDITING_MODULE_PAGE'],
            'TXT_FRONTEND_EDITING_NO_TITLE_AND_CONTENT' => $_ARRAYLANG['TXT_FRONTEND_EDITING_NO_TITLE_AND_CONTENT'],
            'TXT_FRONTEND_EDITING_CONFIRM_UNSAVED_EXIT' => $_ARRAYLANG['TXT_FRONTEND_EDITING_CONFIRM_UNSAVED_EXIT'],
            'TXT_FRONTEND_EDITING_DRAFT' => $_ARRAYLANG['TXT_FRONTEND_EDITING_DRAFT'],
            'TXT_FRONTEND_EDITING_PUBLISHED' => $_ARRAYLANG['TXT_FRONTEND_EDITING_PUBLISHED'],
        );

        // add toolbar to html
        $this->prepareTemplate($componentController);

        // assign js variables
        $contrexxJavascript = \ContrexxJavascript::getInstance();
        $contrexxJavascript->setVariable('langVars', $langVariables, 'FrontendEditing');
        $contrexxJavascript->setVariable('pageId', $page->getId(), 'FrontendEditing');
        $contrexxJavascript->setVariable('hasPublishPermission', \Permission::checkAccess(78, 'static', true), 'FrontendEditing');
        $contrexxJavascript->setVariable('contentTemplates', $this->getCustomContentTemplates(), 'FrontendEditing');
        $contrexxJavascript->setVariable('defaultTemplate', $this->getDefaultTemplate(), 'FrontendEditing');

        $configPath = $this->cx->getComponent('Wysiwyg')->getConfigPath();
        $contrexxJavascript->setVariable('configPath', $configPath, 'FrontendEditing');
    }

    /**
     * Adds the toolbar to the current html structure (after the starting body tag)
     *
     * @param ComponentController $componentController
     */
    private function prepareTemplate(\Cx\Core_Modules\FrontendEditing\Controller\ComponentController $componentController)
    {
        global $_ARRAYLANG, $objTemplate;

        // get necessary objects
        $objInit = \Env::get('init');
        $page = $this->cx->getPage();

        // init component template object
        $componentTemplate = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH . '/' . $componentController->getName() . '/View/Template/Generic');
        $componentTemplate->setErrorHandling(PEAR_ERROR_DIE);

        // add div for toolbar after starting body tag
        $componentTemplate->loadTemplateFile('Toolbar.html');

        // @author: Michael Ritter
        // not used for cloudrexx 3.1
//        global $_CORELANG;
//        $template = $objTemplate;
//        $root = $componentTemplate->fileRoot;
//        $componentTemplate->setRoot(ASCMS_ADMIN_TEMPLATE_PATH);
//        $objTemplate = $componentTemplate;
//        \Env::get('ClassLoader')->loadFile(ASCMS_DOCUMENT_ROOT . '/lang/en/backend.php');
//        $langCode = \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID);
//        if ($langCode != 'en') {
//            \Env::get('ClassLoader')->loadFile(ASCMS_DOCUMENT_ROOT . '/lang/' . $langCode . '/backend.php');
//        }
//        $_CORELANG = array_merge($_CORELANG, $_ARRAYLANG);
//        $menu = new \adminMenu('fe');
//        $menu->getAdminNavbar();
//        $componentTemplate->setRoot($root);
//        $objTemplate = $template;
        // end code from Michael Ritter

        $objUser = $this->cx->getUser()->objUser;
        $firstname = $objUser->getProfileAttribute('firstname');
        $lastname = $objUser->getProfileAttribute('lastname');
        $componentTemplate->setGlobalVariable(array(
            'LOGGED_IN_USER' => !empty($firstname) && !empty($lastname) ? $firstname . ' ' . $lastname : $objUser->getUsername(),
            'TXT_LOGOUT' => $_ARRAYLANG['TXT_FRONTEND_EDITING_TOOLBAR_LOGOUT'],
            'TXT_FRONTEND_EDITING_TOOLBAR_OPEN_CM' => $_ARRAYLANG['TXT_FRONTEND_EDITING_TOOLBAR_OPEN_CM'],
            'TXT_FRONTEND_EDITING_HISTORY' => $_ARRAYLANG['TXT_FRONTEND_EDITING_HISTORY'],
            'TXT_FRONTEND_EDITING_OPTIONS' => $_ARRAYLANG['TXT_FRONTEND_EDITING_OPTIONS'],
            'TXT_FRONTEND_EDITING_ADMINMENU' => $_ARRAYLANG['TXT_FRONTEND_EDITING_ADMINMENU'],
            'TXT_FRONTEND_EDITING_CSS_CLASS' => $_ARRAYLANG['TXT_FRONTEND_EDITING_CSS_CLASS'],
            'TXT_FRONTEND_EDITING_CUSTOM_CONTENT' => $_ARRAYLANG['TXT_FRONTEND_EDITING_CUSTOM_CONTENT'],
            'TXT_FRONTEND_EDITING_THEMES' => $_ARRAYLANG['TXT_FRONTEND_EDITING_THEMES'],
            'TXT_FRONTEND_EDITING_TOOLBAR_SAVE_BLOCK' => $_ARRAYLANG['TXT_FRONTEND_EDITING_TOOLBAR_SAVE_BLOCK'],
            'SKIN_OPTIONS' => $this->getSkinOptions(),
            'LINK_LOGOUT' => $objInit->getUriBy('section', 'logout'),
            'LINK_PROFILE' => ASCMS_PATH_OFFSET . '/cadmin/index.php?cmd=Access&amp;act=user&amp;tpl=modify&amp;id=' . $objUser->getId(),
            'LINK_CM' => ASCMS_PATH_OFFSET . '/cadmin/index.php?cmd=ContentManager&amp;page=' . $page->getId() . '&amp;tab=content',
        ));
        $objTemplate->_blocks['__global__'] = preg_replace('/<body[^>]*>/', '\\0' . $componentTemplate->get(), $objTemplate->_blocks['__global__']);
    }

    /**
     * Returns the html code for the select element for the skin option
     *
     * @return string html for select element
     */
    private function getSkinOptions()
    {
        $options = '';
        foreach ($this->getThemes() as $id => $name) {
            $options .= '<option value="' . $id . '">' . $name . '</option>' . "\n";
        }

        return $options;
    }

    /**
     * Returns all themes which are defined in the backend
     *
     * @return array all available themes
     */
    private function getThemes()
    {
        $query = 'SELECT `id`, `themesname` FROM `' . DBPREFIX . 'skins` ORDER BY `id`';
        $result = $this->cx->getDb()->getAdoDb()->Execute($query);

        $themes = array();
        while (!$result->EOF) {
            $themes[$result->fields['id']] = $result->fields['themesname'];
            $result->MoveNext();
        }

        return $themes;
    }

    /**
     * Get all custom content templates by template id
     *
     * @return array all custom content files
     */
    private function getCustomContentTemplates()
    {
        $templates = array();
        $objInit = \Env::get('init');
        // foreach theme
        foreach ($this->getThemes() as $id => $name) {
            $templates[$id] = $objInit->getCustomContentTemplatesForTheme($id);
        }

        return $templates;
    }

    /**
     * Get the default template for the current frontend language
     *
     * @return mixed default theme id
     */
    private function getDefaultTemplate()
    {
        $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
        $theme = $themeRepo->getDefaultTheme(
            \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB,
            FRONTEND_LANG_ID);
        return $theme->getId();
    }
}

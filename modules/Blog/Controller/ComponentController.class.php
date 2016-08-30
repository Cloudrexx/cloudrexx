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
 * Main controller for Blog
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_blog
 */

namespace Cx\Modules\Blog\Controller;
use Cx\Modules\Blog\Model\Event\BlogEventListener;

/**
 * Main controller for Blog
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_blog
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

     /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $subMenuTitle, $objTemplate;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objBlog = new \Cx\Modules\Blog\Controller\Blog(\Env::get('cx')->getPage()->getContent());
                \Env::get('cx')->getPage()->setContent($objBlog->getPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(119, 'static');
                $subMenuTitle = $_CORELANG['TXT_BLOG_MODULE'];
                $objBlog = new \Cx\Modules\Blog\Controller\BlogManager();
                $objBlog->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $objBlogHome, $themesPages, $page_template, $_ARRAYLANG, $objInit,
               $blogHomeContentInContent, $blogHomeContentInTemplate, $blogHomeContentInTheme, $blogHomeContentInSidebar, $strContentSource,
               $blogHomeCalendarInContent, $blogHomeCalendarInTemplate, $blogHomeCalendarInTheme, $blogHomeCalendarInSidebar, $strCalendarSource,
               $blogHomeTagCloudInContent, $blogHomeTagCloudInTemplate, $blogHomeTagCloudInTheme, $blogHomeTagCloudInSidebar, $strTagCloudSource,
               $blogHomeTagHitlistInContent, $blogHomeTagHitlistInTemplate, $blogHomeTagHitlistInTheme, $blogHomeTagHitlistInSidebar, $strTagHitlistSource,
               $blogHomeCategorySelectInContent, $blogHomeCategorySelectInTemplate, $blogHomeCategorySelectInTheme, $blogHomeCategorySelectInSidebar, $strCategoriesSelect,
               $blogHomeCategoryListInContent, $blogHomeCategoryListInTemplate, $blogHomeCategoryListInTheme, $blogHomeCategoryListInSidebar, $strCategoriesList;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                // Get content for the blog-module.
                $objBlogHome = new \Cx\Modules\Blog\Controller\BlogHomeContent($themesPages['blog_content']);
                if ($objBlogHome->blockFunktionIsActivated()) {
                    //Blog-File
                    $blogHomeContentInContent  = $objBlogHome->searchKeywordInContent('BLOG_FILE', \Env::get('cx')->getPage()->getContent());
                    $blogHomeContentInTemplate = $objBlogHome->searchKeywordInContent('BLOG_FILE', $page_template);
                    $blogHomeContentInTheme    = $objBlogHome->searchKeywordInContent('BLOG_FILE', $themesPages['index']);
                    $blogHomeContentInSidebar  = $objBlogHome->searchKeywordInContent('BLOG_FILE', $themesPages['sidebar']);
                    if ($blogHomeContentInContent || $blogHomeContentInTemplate || $blogHomeContentInTheme || $blogHomeContentInSidebar) {
                        $_ARRAYLANG       = array_merge($_ARRAYLANG, $objInit->loadLanguageData('Blog'));
                        $strContentSource = $objBlogHome->getLatestEntries();
                        \Env::get('cx')->getPage()->setContent($objBlogHome->fillVariableIfActivated('BLOG_FILE', $strContentSource, \Env::get('cx')->getPage()->getContent(), $blogHomeContentInContent));
                        $page_template    = $objBlogHome->fillVariableIfActivated('BLOG_FILE', $strContentSource, $page_template, $blogHomeContentInTemplate);
                        $themesPages['index']   = $objBlogHome->fillVariableIfActivated('BLOG_FILE', $strContentSource, $themesPages['index'], $blogHomeContentInTheme);
                        $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_FILE', $strContentSource, $themesPages['sidebar'], $blogHomeContentInSidebar);
                    }
                    //Blog-Calendar
                    $blogHomeCalendarInContent  = $objBlogHome->searchKeywordInContent('BLOG_CALENDAR', \Env::get('cx')->getPage()->getContent());
                    $blogHomeCalendarInTemplate = $objBlogHome->searchKeywordInContent('BLOG_CALENDAR', $page_template);
                    $blogHomeCalendarInTheme    = $objBlogHome->searchKeywordInContent('BLOG_CALENDAR', $themesPages['index']);
                    $blogHomeCalendarInSidebar  = $objBlogHome->searchKeywordInContent('BLOG_CALENDAR', $themesPages['sidebar']);
                    if ($blogHomeCalendarInContent || $blogHomeCalendarInTemplate || $blogHomeCalendarInTheme || $blogHomeCalendarInSidebar) {
                        $strCalendarSource      = $objBlogHome->getHomeCalendar();
                        \Env::get('cx')->getPage()->setContent($objBlogHome->fillVariableIfActivated('BLOG_CALENDAR', $strCalendarSource, \Env::get('cx')->getPage()->getContent(), $blogHomeCalendarInContent));
                        $page_template          = $objBlogHome->fillVariableIfActivated('BLOG_CALENDAR', $strCalendarSource, $page_template, $blogHomeCalendarInTemplate);
                        $themesPages['index']   = $objBlogHome->fillVariableIfActivated('BLOG_CALENDAR', $strCalendarSource, $themesPages['index'], $blogHomeCalendarInTheme);
                        $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_CALENDAR', $strCalendarSource, $themesPages['sidebar'], $blogHomeCalendarInSidebar);
                    }
                    //Blog-TagCloud
                    $blogHomeTagCloudInContent  = $objBlogHome->searchKeywordInContent('BLOG_TAG_CLOUD', \Env::get('cx')->getPage()->getContent());
                    $blogHomeTagCloudInTemplate = $objBlogHome->searchKeywordInContent('BLOG_TAG_CLOUD', $page_template);
                    $blogHomeTagCloudInTheme    = $objBlogHome->searchKeywordInContent('BLOG_TAG_CLOUD', $themesPages['index']);
                    $blogHomeTagCloudInSidebar  = $objBlogHome->searchKeywordInContent('BLOG_TAG_CLOUD', $themesPages['sidebar']);
                    if ($blogHomeTagCloudInContent || $blogHomeTagCloudInTemplate || $blogHomeTagCloudInTheme || $blogHomeTagCloudInSidebar) {
                        $strTagCloudSource      = $objBlogHome->getHomeTagCloud();
                        \Env::get('cx')->getPage()->setContent($objBlogHome->fillVariableIfActivated('BLOG_TAG_CLOUD', $strTagCloudSource, \Env::get('cx')->getPage()->getContent(), $blogHomeTagCloudInContent));
                        $page_template          = $objBlogHome->fillVariableIfActivated('BLOG_TAG_CLOUD', $strTagCloudSource, $page_template, $blogHomeTagCloudInTemplate);
                        $themesPages['index']   = $objBlogHome->fillVariableIfActivated('BLOG_TAG_CLOUD', $strTagCloudSource, $themesPages['index'], $blogHomeTagCloudInTheme);
                        $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_TAG_CLOUD', $strTagCloudSource, $themesPages['sidebar'], $blogHomeTagCloudInSidebar);
                    }
                    //Blog-TagHitlist
                    $blogHomeTagHitlistInContent  = $objBlogHome->searchKeywordInContent('BLOG_TAG_HITLIST', \Env::get('cx')->getPage()->getContent());
                    $blogHomeTagHitlistInTemplate = $objBlogHome->searchKeywordInContent('BLOG_TAG_HITLIST', $page_template);
                    $blogHomeTagHitlistInTheme    = $objBlogHome->searchKeywordInContent('BLOG_TAG_HITLIST', $themesPages['index']);
                    $blogHomeTagHitlistInSidebar  = $objBlogHome->searchKeywordInContent('BLOG_TAG_HITLIST', $themesPages['sidebar']);
                    if ($blogHomeTagHitlistInContent || $blogHomeTagHitlistInTemplate || $blogHomeTagHitlistInTheme || $blogHomeTagHitlistInSidebar) {
                        $strTagHitlistSource    = $objBlogHome->getHomeTagHitlist();
                        \Env::get('cx')->getPage()->setContent($objBlogHome->fillVariableIfActivated('BLOG_TAG_HITLIST', $strTagHitlistSource, \Env::get('cx')->getPage()->getContent(), $blogHomeTagHitlistInContent));
                        $page_template          = $objBlogHome->fillVariableIfActivated('BLOG_TAG_HITLIST', $strTagHitlistSource, $page_template, $blogHomeTagHitlistInTemplate);
                        $themesPages['index']   = $objBlogHome->fillVariableIfActivated('BLOG_TAG_HITLIST', $strTagHitlistSource, $themesPages['index'], $blogHomeTagHitlistInTheme);
                        $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_TAG_HITLIST', $strTagHitlistSource, $themesPages['sidebar'], $blogHomeTagHitlistInSidebar);
                    }
                    //Blog-Categories (Select)
                    $blogHomeCategorySelectInContent  = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_SELECT', \Env::get('cx')->getPage()->getContent());
                    $blogHomeCategorySelectInTemplate = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_SELECT', $page_template);
                    $blogHomeCategorySelectInTheme    = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_SELECT', $themesPages['index']);
                    $blogHomeCategorySelectInSidebar  = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_SELECT', $themesPages['sidebar']);
                    if ($blogHomeCategorySelectInContent || $blogHomeCategorySelectInTemplate || $blogHomeCategorySelectInTheme || $blogHomeCategorySelectInSidebar) {
                        $strCategoriesSelect    = $objBlogHome->getHomeCategoriesSelect();
                        \Env::get('cx')->getPage()->setContent($objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_SELECT', $strCategoriesSelect, \Env::get('cx')->getPage()->getContent(), $blogHomeCategorySelectInContent));
                        $page_template          = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_SELECT', $strCategoriesSelect, $page_template, $blogHomeCategorySelectInTemplate);
                        $themesPages['index']   = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_SELECT', $strCategoriesSelect, $themesPages['index'], $blogHomeCategorySelectInTheme);
                        $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_SELECT', $strCategoriesSelect, $themesPages['sidebar'], $blogHomeCategorySelectInSidebar);
                    }
                    //Blog-Categories (List)
                    $blogHomeCategoryListInContent  = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_LIST', \Env::get('cx')->getPage()->getContent());
                    $blogHomeCategoryListInTemplate = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_LIST', $page_template);
                    $blogHomeCategoryListInTheme    = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_LIST', $themesPages['index']);
                    $blogHomeCategoryListInSidebar  = $objBlogHome->searchKeywordInContent('BLOG_CATEGORIES_LIST', $themesPages['sidebar']);
                    if ($blogHomeCategoryListInContent || $blogHomeCategoryListInTemplate || $blogHomeCategoryListInTheme || $blogHomeCategoryListInSidebar) {
                        $strCategoriesList      = $objBlogHome->getHomeCategoriesList();
                        \Env::get('cx')->getPage()->setContent($objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_LIST', $strCategoriesList, \Env::get('cx')->getPage()->getContent(), $blogHomeCategoryListInContent));
                        $page_template          = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_LIST', $strCategoriesList, $page_template, $blogHomeCategoryListInTemplate);
                        $themesPages['index']   = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_LIST', $strCategoriesList, $themesPages['index'], $blogHomeCategoryListInTheme);
                        $themesPages['sidebar'] = $objBlogHome->fillVariableIfActivated('BLOG_CATEGORIES_LIST', $strCategoriesList, $themesPages['sidebar'], $blogHomeCategoryListInSidebar);
                    }
                }
                break;
        }
    }

    /**
     * Register your event listeners here
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * Keep in mind, that you can also register your events later.
     * Do not do anything else here than initializing your event listeners and
     * list statements like
     * $this->cx->getEvents()->addEventListener($eventName, $listener);
     */
    public function registerEventListeners() {
        $eventListener = new BlogEventListener($this->cx);
        $this->cx->getEvents()->addEventListener('mediasource.load', $eventListener);
    }
}

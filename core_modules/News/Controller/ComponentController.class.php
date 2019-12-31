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
 * Main controller for News
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */

namespace Cx\Core_Modules\News\Controller;

/**
 * Main controller for News
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array('JsonNews', 'EsiWidget');
    }

     /**
     * {@inheritdoc}
     */
    public function getControllersAccessableByJson() {
        return array('JsonNewsController', 'EsiWidgetController');
    }

    /**
     * Returns a list of command mode commands provided by this component
     *
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array('News');
    }

    /**
     * Execute api command
     *
     * @param string $command Name of command to execute
     * @param array  $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {
        $subcommand = null;
        if (!empty($arguments[0])) {
            $subcommand = $arguments[0];
        }

        // define frontend language
        if (!defined('FRONTEND_LANG_ID')) {
            define('FRONTEND_LANG_ID', 1);
        }

        switch ($command) {
            case 'News':
                switch ($subcommand) {
                    case 'Cron':
                        $objNews = new NewsManager();
                        $objNews->createRSS();
                        break;
                }
                break;
            default:
                break;
        }
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $objTemplate, $subMenuTitle;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $newsObj = new News($page->getContent());
                $page->setContent($newsObj->getNewsPage());
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(10, 'static');
                $subMenuTitle = $_CORELANG['TXT_NEWS_MANAGER'];
                $objNews      = new NewsManager();
                $objNews->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * Do something after system initialization
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     *
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $widgetController = $this->getComponent('Widget');
        // Get Headlines
        for ($i = 1; $i <= 20; $i++) {
            $id = '';
            if ($i > 1) {
                $id = $i;
            }
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                'HEADLINES' . $id . '_FILE'
            );
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_THEME |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_CHANNEL
            );
            $widgetController->registerWidget(
                $widget
            );
        }

        // Get Top news, News categories, News Archives, recent News Comments
        $widgetNames = array(
            'TOP_NEWS_FILE' => 
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER,
            'NEWS_CATEGORIES' => 
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_QUERY,
            'NEWS_ARCHIVES' => 
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER,
            'NEWS_RECENT_COMMENTS_FILE' => 0,
        );
        foreach ($widgetNames as $widgetName => $esiVariables) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                $widgetName
            );

            // set common esi variables
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_THEME |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_CHANNEL |
                $esiVariables
            );

            $widgetController->registerWidget(
                $widget
            );
        }

        // news category block widget
        $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
            $this,
            'news_category_widget',
            \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_BLOCK
        );
        $widget->setEsiVariable(
            \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_QUERY
        );
        $widgetController->registerWidget(
            $widget
        );

        // Register tag-cloud widget
        $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
            $this,
            'news_tag_cloud',
            \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_BLOCK
        );
        $widget->setEsiVariable(
            \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
        );
        $widgetController->registerWidget(
            $widget
        );

        // Set news teasers
        list($arrTeaserFrames, $arrTeaserFrameNames) = Teasers::getTeaserFrames();
        if (empty($arrTeaserFrames)) {
            return;
        }
        foreach ($arrTeaserFrames as $arrTeaser) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                'TEASERS_' . strtoupper($arrTeaser['name'])
            );
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_THEME |
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_CHANNEL
            );
            $widgetController->registerWidget(
                $widget
            );
        }
    }

    /**
     * Called for additional, component specific resolving
     * 
     * If /en/Path/to/Page is the path to a page for this component
     * a request like /en/Path/to/Page/with/some/parameters will
     * give an array like array('with', 'some', 'parameters') for $parts
     * 
     * This may be used to redirect to another page
     * @todo filter arguments as in adjustResponse()
     * @param array $parts List of additional path parts
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved virtual page
     */
    public function resolve($parts, $page) {
        $canonicalUrl = \Cx\Core\Routing\Url::fromPage($page, $this->cx->getRequest()->getUrl()->getParamArray());
        header('Link: <' . $canonicalUrl->toString() . '>; rel="canonical"');
    }

    /**
     * Do something with a Response object
     * You may do page alterations here (like changing the metatitle)
     * You may do response alterations here (like set headers)
     * PLEASE MAKE SURE THIS METHOD IS MOCKABLE. IT MAY ONLY INTERACT WITH
     * resolve() HOOK.
     *
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object to adjust
     */
    public function adjustResponse(
        \Cx\Core\Routing\Model\Entity\Response $response
    ) {
        // TODO: migrate to use additional path arguments
        // in case of an ESI request, the request URL will be set through Referer-header
        $headers = $response->getRequest()->getHeaders();
        if (isset($headers['Referer'])) {
            $refUrl = new \Cx\Lib\Net\Model\Entity\Url($headers['Referer']);
        } else {
            $refUrl = new \Cx\Lib\Net\Model\Entity\Url($response->getRequest()->getUrl()->toString());
        }

        $newsId = 0;
        if ($refUrl->hasParam('newsid')) {
            $newsId = $refUrl->getParam('newsid');
            $canonicalUrlArguments = array('newsid');
        } else {
            $canonicalUrlArguments = array('category', 'tag', 'pos');
        }

        $page   = $response->getPage();
        $params = $refUrl->getParamArray();

        // filter out all non-relevant URL arguments
        $params = array_filter(
            $refUrl->getParamArray(),
            function($key) use ($canonicalUrlArguments) {return in_array($key, $canonicalUrlArguments);},
            \ARRAY_FILTER_USE_KEY
        );

        $canonicalUrl = \Cx\Core\Routing\Url::fromPage($page, $params);
        $response->setHeader(
            'Link',
            '<' . $canonicalUrl->toString() . '>; rel="canonical"'
        );

        if (
            !$page ||
            $page->getModule() !== $this->getName() ||
            substr($page->getCmd(), 0, 7) != 'details'
        ) {
            return;
        }

        $news = new News('');
        $news->getDetails($newsId);

        //Set title's, if news title is not empty
        if (!empty($news->newsTitle)) {
            $page->setTitle($news->newsTitle);
            $page->setContentTitle($news->newsTitle);
            $page->setMetatitle($news->newsTitle);
        }

        //Set meta description, if news teaser text is not empty
        $metaDesc = $news->newsText;
        if (!empty($news->getTeaser())) {
            $metaDesc = $news->getTeaser();
        }
        $page->setMetadesc(
            contrexx_strip_tags(
                html_entity_decode($metaDesc, ENT_QUOTES, CONTREXX_CHARSET)
            )
        );

        //Set meta image, if news thumbnail is not empty
        if (!empty($news->newsThumbnail)) {
            $page->setMetaimage($news->newsThumbnail);
        }

        $page->setMetakeys($news->newsMetaKeys);
    }

    /**
     * Register the Event listeners
     */
    public function registerEventListeners() {
        $evm = $this->cx->getEvents();
        $evm->addEventListener(
            'SearchFindContent',
            new \Cx\Core_Modules\News\Model\Event\NewsEventListener()
        );

        // locale event listener
        $localeLocaleEventListener = new \Cx\Core_Modules\News\Model\Event\LocaleLocaleEventListener($this->cx);
        $evm->addModelListener(
            'postPersist',
            'Cx\\Core\\Locale\\Model\\Entity\\Locale',
            $localeLocaleEventListener
        );
        $evm->addModelListener(
            'preRemove',
            'Cx\\Core\\Locale\\Model\\Entity\\Locale',
            $localeLocaleEventListener
        );
    }
}

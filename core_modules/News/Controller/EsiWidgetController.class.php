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
 * Class EsiWidgetController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 * @version     1.0.0
 */

namespace Cx\Core_Modules\News\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 * @version     1.0.0
 */

class EsiWidgetController extends \Cx\Core_Modules\Widget\Controller\EsiWidgetController {
    /**
     * Parses a widget
     *
     * @param string                                 $name     Widget name
     * @param \Cx\Core\Html\Sigma                    $template Widget Template
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object
     * @param array                                  $params   Get parameters
     */
    public function parseWidget($name, $template, $response, $params)
    {
        global $_CORELANG, $_ARRAYLANG;

        $langId = $params['locale']->getId();
        $theme  = $params['theme'];

        //The globals $_CORELANG and $_ARRAYLANG are required in the following methods
        //NewsHeadlines::getHomeHeadlines(), NewsTop::getHomeTopNews() and
        //NewsLibrary::getNewsArchiveList()
        $_CORELANG = array_merge(
            $_CORELANG,
            \Env::get('init')->getComponentSpecificLanguageData('Core', true, $langId)
        );
        $_ARRAYLANG = array_merge(
            $_ARRAYLANG,
            \Env::get('init')->getComponentSpecificLanguageData('News', true, $langId)
        );

        if ($name == 'news_tag_cloud') {
            $newsLib = new NewsLibrary();
            $newsLib->parseTagCloud($template, $langId);
            return;
        }

        // Parse Headlines
        $matches = null;
        if (preg_match('/^HEADLINES(\d{1,2}|)_FILE/', $name, $matches)) {
            $category   = null;
            $incSubCategories = false;
            $catMatches = null;

            $templateContent = $this->getFileContent(
                $theme,
                'headlines' . $matches[1] . '.html'
            );
            if (!$templateContent) {
                return;
            }

            if (
                preg_match(
                    '/\{CATEGORY_([0-9]+)(_FULL)?\}/',
                    $templateContent,
                    $catMatches
                )
            ) {
                $category = $catMatches[1];
                $incSubCategories = !empty($catMatches[2]);
            }
            $newsHeadlines = new NewsHeadlines($templateContent);
            $nextUpdateDate = null;
            $content       = $newsHeadlines->getHomeHeadlines(
                $category,
                $langId,
                $incSubCategories,
                $nextUpdateDate
            );
            if ($nextUpdateDate) {
                $response->setExpirationDate($nextUpdateDate);
            }
            $template->setVariable($name, $content);
            return;
        }

        // Parse Top news
        if ($name == 'TOP_NEWS_FILE') {
            $templateContent = $this->getFileContent($theme, 'top_news.html');
            if (!$templateContent) {
                return;
            }
            $newsTop = new NewsTop($templateContent);
            $nextUpdateDate = null;
            $content = $newsTop->getHomeTopNews(0, $langId, $nextUpdateDate);
            if ($nextUpdateDate) {
                $response->setExpirationDate($nextUpdateDate);
            }
            $template->setVariable($name, $content);
            return;
        }

        // Parse News categories
        switch ($name) {
            case 'NEWS_CATEGORIES':
                // manually load template (instead of using
                // \Cx\Core\Html\Sigma::loadTemplate()) to be able to strip
                // of any whitespaces to maintain backwards compatibility
                $widgetTemplate = $this->cx->getClassLoader()->getFilePath(
                    $this->getDirectory(false) .
                    '/View/Template/Frontend/Categories.html'
                );

                // TODO: migrate to MediaSource once CLX-1896 has been completed
                $templateContent = file_get_contents($widgetTemplate);
                $templateContent = preg_replace(
                    '/<!--\s+(BEGIN|END)\s+news_category_widget\s+-->/',
                    '',
                    $templateContent
                );

                // Legacy implementation did not contain any whitespaces.
                // Therefore, we have to remove them to maintain backwards
                // compatibility.
                $templateContent = preg_replace(
                    '/([\n\r]\s*)+/',
                    '',
                    $templateContent
                );

                // replace placeholder NEWS_CATEGORIES by template block
                $template->addBlock(
                    $name,
                    'news_category_widget',
                    $templateContent
                );

                // intentionally no break here
            case 'news_category_widget':
                // fetch category-ID from page request
                $categoryId = 0;
                if (isset($params['query']['category'])) {
                    $categoryId = intval($params['query']['category']);
                } elseif (isset($params['query']['filterCategory'])) {
                    $categoryId = intval($params['query']['filterCategory']);
                }
                $placeholders = $template->getPlaceholderList('news_category_widget');
                $categoryFilter = preg_grep('/^NEWS_CATEGORY_\d+$/', $placeholders);
                $rootCategoryId = 0;
                if (
                    count($categoryFilter) &&
                    preg_match('/NEWS_CATEGORY_(\d+)/', current($categoryFilter), $match)
                ) {
                    $rootCategoryId = $match[1];
                }
                $newsLib = new NewsLibrary();
                $newsLib->getNewsCategories($template, $langId, $categoryId, $rootCategoryId);
                return;
                break;

            default:
                break;
        }

        // Parse News Archives
        if ($name == 'NEWS_ARCHIVES') {
            $newsLib = new NewsLibrary();
            $nextUpdateDate = null;
            $content = $newsLib->getNewsArchiveList($langId, $nextUpdateDate);
            if ($nextUpdateDate) {
                $response->setExpirationDate($nextUpdateDate);
            }
            $template->setVariable($name, $content);
            return;
        }

        // Parse recent News Comments
        if ($name == 'NEWS_RECENT_COMMENTS_FILE') {
            $pageContent = $this->getFileContent(
                $theme,
                'news_recent_comments.html'
            );
            if (!$pageContent) {
                return;
            }
            $newsLib = new NewsRecentComments($pageContent);
            $content = $newsLib->getRecentNewsComments($langId);
            $template->setVariable($name, $content);
            return;
        }

        // Parse news teasers
        if (!\Cx\Core\Setting\Controller\Setting::getValue(
            'newsTeasersStatus',
            'Config')
        ) {
            return;
        }

        if (preg_match('/TEASERS_([0-9a-zA-Z_-]+)/', $name, $matches)) {
            $nextUpdateDate = null;
            $teasers = new Teasers(false, $langId, $nextUpdateDate);
            $code    = '{' . $name . '}';
            $teasers->setTeaserFrames(array($matches[1]), $code);
            if ($nextUpdateDate) {
                $response->setExpirationDate($nextUpdateDate);
            }
            $template->setVariable($name, $code);
        }
    }

    /**
     * Get file content
     *
     * @param \Cx\Core\View\Model\Entity\Theme $theme    Theme object
     * @param string                           $fileName Name of the file
     *
     * @return string
     */
    protected function getFileContent($theme, $fileName)
    {
        if (!($theme instanceof \Cx\Core\View\Model\Entity\Theme)) {
            return;
        }

        return file_get_contents(
            $theme->getFilePath($theme->getFolderName() . '/' . $fileName)
        );
    }
}

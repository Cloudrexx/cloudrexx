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
 * Search
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @version     1.1.0
 * @package     cloudrexx
 * @subpackage  coremodule_search
 */

namespace Cx\Core_Modules\Search\Controller;

/**
 * Search manager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @access      public
 * @version     1.1.0
 * @package     cloudrexx
 * @subpackage  coremodule_search
 */
class SearchManager
{
    /**
     * Doctrine entity manager
     * @var    object
     * @access private
     */
    private $em = null;
    /**
     * Search term
     * @var    string
     * @access private
     */
    private $term = '';
    /**
     * Position for paging
     * @var    int
     * @access private
     */
    private $pos = 0;
    /**
     * License object
     * @var \Cx\Core_Modules\License\License
     */
    private $license = null;
    /**
     * Template object
     * @var \Cx\Core\Html\Sigma $template
     */
    private $template = null;

    /**
     * Constructor
     *
     * @param string $act
     * @param \Cx\Core\Html\Sigma $tpl
     * @param \Cx\Core_Modules\License\License $license
     */
    public function __construct(&$act, \Cx\Core\Html\Sigma $tpl, \Cx\Core_Modules\License\License $license)
    {
        global $_ARRAYLANG;
        $this->defaultAct = 'getSearchResults';

        $this->em       = \Env::get('em');
        $this->act      = $act;
        $this->template = $tpl;
        $this->license  = $license;

        $this->term     = !empty($_GET['term']) ? contrexx_input2raw($_GET['term']) : '';
        $this->pos      = !empty($_GET['pos'])  ? contrexx_input2raw($_GET['pos'])  : 0;

        $this->template->setVariable(array(
            'CONTENT_TITLE'      => $_ARRAYLANG['TXT_OVERVIEW'],
            'CONTENT_NAVIGATION' => '<a href="index.php?cmd=Search" class="active">'.$_ARRAYLANG['TXT_OVERVIEW'].'</a>',
        ));
    }

    /**
     * Parse page
     */
    public function getPage() {
        $this->getSearchResults();
    }

    /**
     * Gets the search results.
     *
     * @return  mixed  Parsed content.
     */
    public function getSearchResults()
    {
        global $_ARRAYLANG;

        $this->template->addBlockfile('ADMIN_CONTENT', 'search', 'Default.html');

        if (!empty($this->term)) {
            $pages      = $this->getSearchedPages();
            $countPages = $this->countSearchedPages();

            usort($pages, array($this, 'sortPages'));

            if ($countPages > 0) {
                $parameter = '&cmd=Search' . (empty($this->term) ? '' : '&term=' . contrexx_raw2encodedUrl($this->term));
                $paging = \Paging::get($parameter, '', $countPages, 0, true, null, 'pos');

                $this->template->setVariable(array(
                    'TXT_SEARCH_RESULTS_COMMENT' => sprintf($_ARRAYLANG['TXT_SEARCH_RESULTS_COMMENT'], contrexx_raw2xhtml($this->term), $countPages),
                    'TXT_SEARCH_TITLE'           => $_ARRAYLANG['TXT_NAVIGATION_TITLE'],
                    'TXT_SEARCH_CONTENT_TITLE'   => $_ARRAYLANG['TXT_PAGETITLE'],
                    'TXT_SEARCH_SLUG'            => $_ARRAYLANG['TXT_CORE_CM_SLUG'],
                    'TXT_SEARCH_LANG'            => $_ARRAYLANG['TXT_LANGUAGE'],
                    'SEARCH_PAGING'              => $paging,
                ));

                foreach ($pages as $page) {
                    // used for alias pages, because they have no language
                    if ($page->getLang() == "") {
                        $languages = "";
                        foreach (\FWLanguage::getIdArray('frontend') as $langId) {
                            $languages[] = \FWLanguage::getLanguageCodeById($langId);
                        }
                    } else {
                        $languages = array(
                            \FWLanguage::getLanguageCodeById($page->getLang())
                        );
                    }

                    $aliasLanguages = implode(', ', $languages);

                    $originalPage = $page;
                    $link = 'index.php?cmd=ContentManager&amp;page=' . $page->getId();
                    if ($page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS) {
                        $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
                        if ($originalPage->isTargetInternal()) {
                            // is internal target, get target page
                            $originalPage = $pageRepo->getTargetPage($page);
                        } else {
                            // is an external target, set the link to the external targets url
                            $originalPage = new \Cx\Core\ContentManager\Model\Entity\Page();
                            $originalPage->setTitle($page->getTarget());
                            $link = $page->getTarget();
                        }
                    }

                    $this->template->setVariable(array(
                        'SEARCH_RESULT_BACKEND_LINK'  => $link,
                        'SEARCH_RESULT_TITLE'         => $originalPage->getTitle(),
                        'SEARCH_RESULT_CONTENT_TITLE' => $originalPage->getContentTitle(),
                        'SEARCH_RESULT_SLUG'          => substr($page->getPath(), 1),
                        'SEARCH_RESULT_LANG'          => $aliasLanguages,
                        'SEARCH_RESULT_FRONTEND_LINK' => \Cx\Core\Routing\Url::fromPage($page),
                    ));

                    $this->template->parse('search_result_row');
                }
            } else {
                $this->template->setVariable(array(
                    'TXT_SEARCH_NO_RESULTS' => sprintf($_ARRAYLANG['TXT_SEARCH_NO_RESULTS'], contrexx_raw2xhtml($this->term)),
                ));
            }
        } else {
            $this->template->setVariable(array(
                'TXT_SEARCH_NO_TERM' => $_ARRAYLANG['TXT_SEARCH_NO_TERM'],
            ));
        }
    }

    /**
     * Gets the search query builder.
     * Searches for slug, title and content title by the given search term.
     *
     * @return  \Doctrine\ORM\QueryBuilder  $qb
     */
    private function getSearchQueryBuilder()
    {
        $qb = $this->em->createQueryBuilder();
        // build query
        $qb->from('Cx\Core\ContentManager\Model\Entity\Page', 'p')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->like('p.slug', ':searchTerm'),
                        $qb->expr()->like('p.title', ':searchTerm'),
                        $qb->expr()->like('p.contentTitle', ':searchTerm'),

                        // search for content pages which have search term in content
                        $qb->expr()->andX(
                            $qb->expr()->like('p.content', ':searchTerm'),
                            'p.type = \'' . \Cx\Core\ContentManager\Model\Entity\Page::TYPE_CONTENT . '\''
                        ),

                        // search for application pages which have the search term as module name or cmd
                        $qb->expr()->andX(
                            $qb->expr()->orX(
                                $qb->expr()->like('p.module', ':searchTerm'),
                                $qb->expr()->like('p.cmd', ':searchTerm')
                            ),
                            'p.type = \'' . \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION . '\''
                        )
                    ),

                    // only show module pages which are legal components
                    $qb->expr()->orX(
                        'p.module = \'\'',
                        'p.module IS NULL',
                        $qb->expr()->in(
                            'p.module',
                            $this->license->getLegalComponentsList()
                        )
                    ),

                    $qb->expr()->orX(
                        $qb->expr()->in(
                            'p.lang',
                            \FWLanguage::getIdArray('frontend')
                        ),
                        // aliases are not specific per language, so we have to search for aliases with this case
                        $qb->expr()->eq('p.lang', "''")
                    )
                )
            )
            ->setParameter('searchTerm', '%'.$this->term.'%')
            ->orderBy('p.title');

        return $qb;
    }

    /**
     * Gets the searched pages as array.
     *
     * @return  array  $pages  \Cx\Core\ContentManager\Model\Entity\Page
     */
    private function getSearchedPages()
    {
        global $_CONFIG;
        // select the whole page object
        $pages = $this->getSearchQueryBuilder()->select('p')->setFirstResult($this->pos)->setMaxResults($_CONFIG['corePagingLimit'])->getQuery()->getResult();
        return $pages;
    }

    /**
     * Get amount of pages with search term in slug, title, content title, module name, command name or content
     *
     * @return int $countPages
     */
    private function countSearchedPages()
    {
        // only select the count
        return $this->getSearchQueryBuilder()->select('count(p.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * sort function
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $pageA
     * @param \Cx\Core\ContentManager\Model\Entity\Page $pageB
     * @return int
     */
    private function sortPages($pageA, $pageB) {
        $pageATermOnlyInContent = (
            preg_match('#(' . $this->term . ')#i', $pageA->getContent()) &&
            !preg_match('#(' . $this->term . ')#i', $pageA->getTitle()) &&
            !preg_match('#(' . $this->term . ')#i', $pageA->getContentTitle()) &&
            !preg_match('#(' . $this->term . ')#i', $pageA->getSlug()) &&
            !preg_match('#(' . $this->term . ')#i', $pageA->getModule()) &&
            !preg_match('#(' . $this->term . ')#i', $pageA->getCmd())
        );
        $pageBTermOnlyInContent = (
            preg_match('#(' . $this->term . ')#i', $pageB->getContent()) &&
            !preg_match('#(' . $this->term . ')#i', $pageB->getTitle()) &&
            !preg_match('#(' . $this->term . ')#i', $pageB->getContentTitle()) &&
            !preg_match('#(' . $this->term . ')#i', $pageB->getSlug()) &&
            !preg_match('#(' . $this->term . ')#i', $pageB->getModule()) &&
            !preg_match('#(' . $this->term . ')#i', $pageB->getCmd())
        );
        if($pageATermOnlyInContent == $pageBTermOnlyInContent) {
            return 0;
        }
        if ($pageATermOnlyInContent && !$pageBTermOnlyInContent) {
            return 1;
        }
        return -1;
    }
}

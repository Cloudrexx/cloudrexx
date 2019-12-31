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
 * Search and view results from the DB
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_search
 * @todo: add namespace
 */

namespace Cx\Core_Modules\Search\Controller;

/**
 * Search and view results from the DB
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @version     3.1.0
 * @package     cloudrexx
 * @subpackage  coremodule_search
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Reto Kohli <reto.kohli@comvation.com> (class)
 */
class Search
{
    /**
     * The term used to find content by
     * @var string
     */
    private $term = '';

    /**
     * DataSet collection containing the result of the search operation
     * @var \Cx\Core_Modules\Listing\Model\Entity\DataSet
     */
    private $result;

    /**
     * Root page for search. If specified only results from this page's branch
     * are delivered
     * @var \Cx\Core\ContentManger\Model\Entity\Page $page
     */
    protected $rootPage = null;

    /**
     * List of valid options that can be applied to the search algorithm
     * @var array
     */
    protected $validOptions = array(
        'zipLookup',
    );

    /**
     * Set options (of $validOptions) to be applied to the search algorithm
     * @var array
     */
    protected $options = array();

    /**
     * Resolves cmd. If it's a node placeholder, search is limited to the node's
     * branch
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Current page
     */
    public function __construct($page = null) {
        if (!$page) {
            $page = \Cx\Core\Core\Controller\Cx::instanciate()->getPage();
        }
        if (!empty($page->getCmd())) {
            // Try to resolve node placeholder
            try {
                $nodePlaceholder = \Cx\Core\Routing\NodePlaceholder::fromPlaceholder(
                    $page->getCmd()
                );
                $this->rootPage = $nodePlaceholder->getPage();
            } catch (\Cx\Core\Routing\NodePlaceholderException $e) {}
        }
    }

    /**
     * Returns the specified root page (if any)
     * @return \Cx\Core\ContentManger\Model\Entity\Page The specified root page
     */
    public function getRootPage() {
        return $this->rootPage;
    }

    /**
     * Return the term to search by
     * @return string
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * Set the term to search by
     * @param string Term to search by
     */
    private function setTerm($term)
    {
        $this->term = $term;
    }

    /**
     * Return the set options
     * @return array List of set options
     */
    public function getOptions() {
        return $this->options;
    }

    /**
     * Set the options to be applied on the search algorithms
     * @param array Options to set
     */
    protected function setOptions($options) {
        $this->options = array_intersect_key(
            $options,
            array_flip($this->validOptions)
        );
    }

    /**
     * Add new set of results to the DataSet collection
     * @param \Cx\Core_Modules\Listing\Model\Entity\DataSet Set of search results to be added
     */
    public function appendResult(\Cx\Core_Modules\Listing\Model\Entity\DataSet $result)
    {
        if (isset($this->result)) {
            $this->result->join($result);
        } else {
            $this->result = $result;
        }
    }

    public function getPage($pos, $page_content)
    {
        global $_CONFIG, $_ARRAYLANG;


        $objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($objTpl);
        $objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $objTpl->setTemplate($page_content);
        $objTpl->setGlobalVariable($_ARRAYLANG);
        // Load main template even if we have a cmd set
        if ($objTpl->placeholderExists('APPLICATION_DATA')) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('Search');
            // load source code
            $applicationTemplate = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $objTpl->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }
        $term = (isset($_REQUEST['term'])
            ? trim(contrexx_input2raw($_REQUEST['term'])) : '');
        if (strlen($term) >= 3) {
            $term = trim(contrexx_input2raw($_REQUEST['term']));

            $arraySearchResults = $this->getSearchResult($term);
            $countResults = sizeof($arraySearchResults);
            if (!is_numeric($pos)) {
                $pos = 0;
            }
            $paging = getPaging(
                $countResults, $pos,
                '&amp;section=Search&amp;term='.contrexx_raw2encodedUrl(
                    $term), '<b>'.$_ARRAYLANG['TXT_SEARCH_RESULTS'].'</b>', true);
            $objTpl->setVariable('SEARCH_PAGING', $paging);
            $objTpl->setVariable('SEARCH_TERM', contrexx_raw2xhtml($term));
            if ($countResults > 0) {
                $searchComment = sprintf(
                    $_ARRAYLANG['TXT_SEARCH_RESULTS_ORDER_BY_RELEVANCE'],
                    contrexx_raw2xhtml($term), $countResults);
                $objTpl->setVariable('SEARCH_TITLE', $searchComment);
                $arraySearchOut = array_slice($arraySearchResults, $pos,
                                              $_CONFIG['corePagingLimit']);
                foreach ($arraySearchOut as $details) {
                    // append search term to result link
                    $link = $details['Link'];
                    if (strpos($link, '?') === false) {
                        $link .= '?';
                    } else {
                        $link .= '&';
                    }
                    $link .= 'searchTerm='.urlencode($term);

                    // parse result into template
                    $objTpl->setVariable(array(
                        'COUNT_MATCH'             =>
                            $_ARRAYLANG['TXT_RELEVANCE'].' '.$details['Score'].'%',
                        'LINK'                    => '<b><a href="'.$link.
                            '" title="'.contrexx_raw2xhtml($details['Title']).'">'.
                            contrexx_raw2xhtml($details['Title']).'</a></b>',
                        'TARGET_PATH' => contrexx_raw2xhtml($details['Link']),
                        'SHORT_CONTENT'           => contrexx_raw2xhtml($details['Content']),
                        'SEARCH_RESULT_SRC'       => $link,
                        'SEARCH_RESULT_TITLE'     => contrexx_raw2xhtml($details['Title']),
                        'SEARCH_RESULT_COMPONENT' => $details['Component'],
                    ));
                    $objTpl->parse('search_result');
                }
                return $objTpl->get();
            }
        }
        $noresult = $_ARRAYLANG['TXT_PLEASE_ENTER_SEARCHTERM'];
        if (!empty($term)) {
            $noresult = sprintf(
                $_ARRAYLANG['TXT_NO_SEARCH_RESULTS'],
                contrexx_raw2xhtml($term)
            );
        }
        $objTpl->setVariable('SEARCH_TITLE', $noresult);
        return $objTpl->get();
    }

    /**
     * Returns an accessable page of this module (if any)
     *
     * This should be called by all search event handlers that do not call
     * getResultArray(). If it returns null, no search results should be
     * returned!
     * @param string $module Module name to find page for
     * @param string $command (optional) Command limitation
     * @return \Cx\Core\ContentManager\Model\Entity\Page Page of module
     */
    public function getAccessablePage($module, $command = '') {
        $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $criteria = array(
            'module' => $module,
            'lang'   => FRONTEND_LANG_ID,
            'type'   => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
        );
        if (!empty($command)) {
            $criteria['cmd'] = $command;
        }

        // only list results in case the associated page of the module is active
        $page = $pageRepo->findOneBy($criteria);
        if (!$page || !$page->isActive()) {
            return null;
        }

        // don't list results in case the user doesn't have sufficient rights to access the page
        // and the option to list only unprotected pages is set (coreListProtectedPages)
        $config = \Env::get('config');
        if (
            $config['coreListProtectedPages'] == 'off' &&
            $page->isFrontendProtected() &&
            $page->getComponent('Session')->getSession() &&
            !\Permission::checkAccess($page->getFrontendAccessId(), 'dynamic', true)
        ) {
            return null;
        }

        // In case a root node was specified, we have to check if the page is in
        // the root page's branch.
        if ($this->rootPage) {
            if (strpos($page->getPath(), $this->rootPage->getPath()) !== 0) {
                return null;
            }
        }
        // In case we are handling the search result of a module ($module is not empty),
        // we have to check if we are allowed to list the results even when the associated module
        // page is invisible.
        // We don't have to check for regular pages ($module is empty) here, because they
        // are handled by an other method than this one.
        if ($config['searchVisibleContentOnly'] == 'on' && !empty($module)) {
            if (!$page->isVisible()) {
                // If $command is set, then this would indicate that we have
                // checked the visibility of the detail view page of the module.
                // Those pages are almost always invisible.
                // Therefore, we shall make the decision if we are allowed to list
                // the results based on the visibility of the main module page
                // (empty $command).
                if (!empty($command)) {
                    $mainModulePage = $pageRepo->findOneBy(array(
                        'module' => $module,
                        'lang' => FRONTEND_LANG_ID,
                        'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
                        'cmd' => '',
                    ));
                    if (   !$mainModulePage
                        || !$mainModulePage->isActive()
                        || !$mainModulePage->isVisible()) {
                        // main module page is also invisible
                        return null;
                    }
                } else {
                    // page is invisible
                    return null;
                }
            }
        }
        return $page;
    }


    /**
     * Returns search results
     *
     * The entries in the array returned contain the following indices:
     *  'Score':    The matching score ([0..100])
     *  'Title':    The object or content title
     *  'Content':  The content
     *  'Link':     The link to the (detailed) view of the result
     *  'Date':     The change date, optional
     * Mind that the date is not available for all types of results.
     * Note that the $term parameter is not currently used, but may be useful
     * i.e. for hilighting matches in the results.
     * @author  Christian Wehrli <christian.wehrli@astalavista.ch>
     * @param   string  $query          The query
     * @param   string  $module_var     The module (empty for core/content?)
     * @param   string  $cmd_var        The cmd (or empty)
     * @param   string  $pagevar        The ID parameter name for referencing
     *                                  found objects in the URL
     * @param   string  $term           The search term
     * @return  array                   The search results array
     */
    public function getResultArray($query, $module, $command, $pagevar, $term, $parseSearchData = null)
    {
        global $_ARRAYLANG;

        $page = $this->getAccessablePage($module, $command);
        if (!$page) {
            return array();
        }
        $pagePath = \Cx\Core\Routing\Url::fromPage($page);
        $objDatabase = \Env::get('db');
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) {
            return array();
        }
        $config = \Env::get('config');
        $max_length = intval($config['searchDescriptionLength']);
        $arraySearchResults = array();
        while (!$objResult->EOF) {
            if (is_callable($pagevar)) {
                $temp_pagelink = $pagevar($pagePath, $objResult->fields);
            } else {
                $temp_pagelink = $pagePath.'?'.$pagevar.$objResult->fields['id'];
            }

            if (is_callable($parseSearchData)) {
                $parseSearchData($objResult->fields);
            }
            $content = (isset($objResult->fields['content'])
                ? trim($objResult->fields['content']) : '');
            $content = \Cx\Core_Modules\Search\Controller\Search::shortenSearchContent($content, $max_length);
            $score = $objResult->fields['score'];
            $scorePercent = ($score >= 1 ? 100 : intval($score * 100));
//TODO: Muss noch geändert werden, sobald das Ranking bei News funktioniert
            $scorePercent = ($score == 0 ? 25 : $scorePercent);
            $date = empty($objResult->fields['date'])
                ? NULL : $objResult->fields['date'];
            $searchtitle = empty($objResult->fields['title'])
                ? $_ARRAYLANG['TXT_UNTITLED'] : $objResult->fields['title'];
            $arraySearchResults[] = array(
                'Score'     => $scorePercent,
                'Title'     => $searchtitle,
                'Content'   => $content,
                'Link'      => $temp_pagelink,
                'Date'      => $date,
                'Component' => $module,
            );
            $objResult->MoveNext();
        }
        return $arraySearchResults;
    }


    /**
     * Shorten and format the search result content
     *
     * Strips template placeholders and blocks, as well as certain tags,
     * and fixes the character encoding
     * @param   string  $content        The content
     * @param   integer $max_length     The maximum allowed length of the
     *                                  preview content, in characters(*)
     * @return  string                  The formatted content
     * @todo    (*) I think these are actually bytes.
     */
    public static function shortenSearchContent($content, $max_length=NULL)
    {
        $content = contrexx_html2plaintext($content);

        // Omit the content when there is no letter in it
        if (!preg_match('/\w/', $content)) return '';

        $max_length = intval($max_length);
        if (strlen($content) > $max_length) {
            $content = substr($content, 0, $max_length);
            $arrayContent = explode(' ', $content);
            array_pop($arrayContent);
            $content = join(' ', $arrayContent).' ...';
        }
        return $content;
    }

    /**
     * Get a result for search term
     *
     * @param string $term Search value
     * @param array $options Options to be applied on the search algorithms
     * @return array Return a array of result
     */
    public function getSearchResult($term, $options = array())
    {
        $this->setTerm($term);
        $this->setOptions($options);
        $eventHandlerInstance = \Env::get('cx')->getEvents();
        $eventHandlerInstance->triggerEvent('SearchFindContent', array($this));
        if ($this->result->size() == 1) {
            $arraySearchResults[] = $this->result->toArray();
        } else {
            $arraySearchResults = $this->result->toArray();
        }

        usort($arraySearchResults,
            /**
             * Compares scores (and dates, if available) of two result array elements
             *
             * Compares the scores first; when equal, compares the dates, if available.
             * Returns
             *  -1 if $a  > $b
             *   0 if $a == $b
             *  +1 if $a  < $b
             * Used for ordering search results.
             * @author  Christian Wehrli <christian.wehrli@astalavista.ch>
             * @param      string  $a      The first element
             * @param      string  $b      The second element
             * @return     integer         The comparison result
             */
            function($a, $b) {
                if ($a['Score'] == $b['Score']) {
                    if (isset($a['Date'])) {
                        if (!isset($b['Date'])) {
                            return -1;
                        }
                        if ($a['Date'] == $b['Date']) {
                            return 0;
                        }
                        if ($a['Date'] > $b['Date']) {
                            return -1;
                        }
                        return 1;
                    }
                    return 0;
                }
                if ($a['Score'] > $b['Score']) {
                    return -1;
                }
                return 1;
            }
        );

        return $arraySearchResults;
    }
}

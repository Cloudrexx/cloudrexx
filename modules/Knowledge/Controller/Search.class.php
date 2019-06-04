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
 * Contains the search object
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Search object
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */
class Search {
    /**
     * An array of objects, containing all interfaces
     *
     * @var array
     */
    private $interfaces = array();

    /**
     * The maximum amount of search results to return
     *
     * Return only the given amount of search results. This is
     * for the ajax on-the-fly search.
     * @var int
     */
    private $maxSearchResults = 6;

    /**
     * Path to the template file
     *
     * @var string
     */
    private $templateFile;

    /**
     * Template object
     *
     * @var object
     */
    private $tpl;

    /**
     * Initialise the whole stuff
     *
     */
    public function __construct()
    {
        // should change when this class is used globally
        $this->templateFile = ASCMS_MODULE_PATH."/Knowledge/Data/searchTemplate.html";

        // the template system
        $this->tpl = new \Cx\Core\Html\Sigma('');
        $this->tpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->tpl->loadTemplateFile($this->templateFile);

        $this->interfaces[] = new SearchKnowledge();
    }

    /**
     * Get search results
     *
     * @return array Array of search status and result in HTML content
     */
    public function performSearch()
    {
        $status  = 1;
        $content = '';
        if (empty($_GET['searchterm'])) {
            // no search term given
            $status = 2;
        } else {
            $searchterm = $_GET['searchterm'];
            $results = $this->getResults($searchterm);

            if (count($results) == 0) {
                // nothing found
                $status = 0;
            } else {
                foreach ($results as $result) {
                    $this->tpl->setVariable(array(
                        'URI'   => $this->makeURI($result['uri']),
                        'TITLE' => $this->formatTitle($result['title'])
                    ));
                    $this->tpl->parse("result");
                }
                $content = $this->tpl->get();
            }
        }

        return array('status' => $status, 'content' => $content);
    }

    /**
     * Get the results from the interfaces
     *
     * Get the results from every interface. Only take as many
     * as given by the $maxSearchResults variable.
     * @param string $searchterm
     * @return array
     */
    private function getResults($searchterm)
    {
        $results = array();
        $amount = 0;
        $endResult = array();

        $searchterm = $this->formatSearchString($searchterm);
        foreach ($this->interfaces as $interface) {
            $trove = $interface->search($searchterm);
            $amount = $amount + count($trove);
            $results[] = array_reverse($trove);
        }

        $j = 0;
        for ($i = 0; $i < $this->maxSearchResults; $i++) {
            if (!empty($results[$j])) {
                $endResult[] = array_pop($results[$j]);
                $j = (count($results == $j)) ? 0 : $j + 1;
            }
        }

        return $endResult;
    }

    /**
     * Format the URI if needed
     *
     * Not implemented yet
     * @param string $uri
     * @return string
     */
    private function makeURI($uri)
    {
        return $uri;
    }

    /**
     * Format the search string
     *
     * Not implemented yet.
     * Format the search string, e.g. remove unecessary
     * characters.
     * @param string $string
     * @return string
     */
    private function formatSearchString($string)
    {
        return $string;
    }

    private function formatTitle($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, CONTREXX_CHARSET);
    }
}

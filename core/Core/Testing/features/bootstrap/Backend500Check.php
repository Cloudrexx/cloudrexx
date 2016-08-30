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



use Behat\Behat\Context\BehatContext;
use Behat\MinkExtension\Context\MinkContext;

class Backend500CheckContext extends BehatContext
{
    private $failedLinks = array();

    /**
     * @Then /^I don\'t get the http status code "([^"]*)"$/
     */
    public function iDonTGetTheHttpStatusCode($httpStatusCode)
    {
        if (!empty($this->failedLinks)) {
            $message = "The following links failed:\r\n";
            foreach ($this->failedLinks as $failedLink) {
                $message .= "* " . $failedLink . "\r\n";
            }
            throw new \Exception($message);
        }
//        $errorOutput = "";
//        foreach($this->resultStatusCodes as $i => $statusCodes) {
//            if ($statusCodes[0] === true) { // exception ($i != 1) for Website Ansicht Link
//                $errorOutput .= "The link " . $statusCodes[1] . " returns a " . $httpStatusCode . " http status code\r\n";
//            }
//        }
//        if (empty($errorOutput)) {
//            print 'YESS!!!! EVERYTHING IS FINE HERE!';
//        } else {
//            print $errorOutput;
//        }
    }

    /**
     * @When /^I visit all links I find on each page$/
     */
    public function iVisitAllLinksIFindOnEachPage()
    {
        // wait otherwise the page is not loaded
        $this->waitForBackend();
        $lastLinkIndex = count($this->getLinkTagsOfNavigation(1)) - 1;
        // visit first link
        // skip the first two links
        $linkTags = array();
        $this->visitNextLink(2, $lastLinkIndex, $linkTags);
    }

    private function currentPageIsMaintenance()
    {
        $html = $this->getMainContext()->page->find('css', 'body')->getHtml();
        if (preg_match('/maintenance mode/', $this->getMainContext()->page->find('css', 'body')->getHtml()) || empty($html)) {
            return true;
        }
        return false;
    }

    private function visitNextLink($currentLinkIndex, $lastLinkIndex, &$linkTags)
    {
        if ($currentLinkIndex > $lastLinkIndex) {
            return;
        }

        $linkTags = $this->getLinkTagsOfNavigation(1);

        $url = $linkTags[$currentLinkIndex]->getAttribute('href');
        $this->getMainContext()->iAmOn($url);

        $this->waitForBackend();

        if (!$this->currentPageIsMaintenance()) {
            // refresh current site

            $subNavigationLinksCount = count($this->getLinkTagsOfNavigation(2));
            for ($i = 0; $i < $subNavigationLinksCount; $i++) {
                $subNavigationLinks = $this->getLinkTagsOfNavigation(2);
                $url                = $subNavigationLinks[$i]->getAttribute('href');
                $this->getMainContext()->iAmOn($url);
                $this->waitForBackend();

                if ($this->currentPageIsMaintenance()) {
                    $this->failedLinks[] = $url;
                    $this->session->back();
                    $this->waitForBackend();
                } else {
                    $subNavigation2LinksCount = count($this->getLinkTagsOfNavigation(3));
                    for ($j = 0; $j < $subNavigation2LinksCount; $j++) {
                        $subNavigation2Links = $this->getLinkTagsOfNavigation(3);
                        if (!isset($subNavigation2Links[$j])) {
                            continue;
                        }
                        $url = $subNavigation2Links[$j]->getAttribute('href');
                        $this->getMainContext()->iAmOn($url);
                        $this->waitForBackend();

                        if ($this->currentPageIsMaintenance()) {
                            $this->failedLinks[] = $url;
                            $this->session->back();
                            $this->waitForBackend();
                        }
                    }
                }
            }

            $this->visitNextLink(++$currentLinkIndex, $lastLinkIndex, $linkTags);
            return;
        }


        // failed

        $this->failedLinks[] = $url;

//        $this->resultStatusCodes[] = array(
//            $this->currentPageIsMaintenance(),
//            $linkTags[$currentLinkIndex]->getAttribute('href'),
//        );

        // reload backend
        // due to csrf
        $this->getMainContext()->iAmInBackend();
        $this->waitForBackend();

        $this->visitNextLink(++$currentLinkIndex, $lastLinkIndex, $linkTags);
    }

    private function getLinkTagsOfNavigation($level)
    {
        switch ($level) {
            case 1:
                return $this->getMainContext()->session->getPage()->findAll('css', '.navigation_level_2 > li > a');
                break;
            case 2:
                return $this->getMainContext()->session->getPage()->findAll('css', '#subnavbar_level1 td.navi > a');
                break;
            case 3:
            default:
                return $this->getMainContext()->session->getPage()->findAll('css', '#subnavbar_level2 a');
                break;
        }
    }

    private function waitForBackend()
    {
        $this->getMainContext()->session->wait(2000, 'document.getElementsByTagName("body")[0] !== undefined');
    }
}

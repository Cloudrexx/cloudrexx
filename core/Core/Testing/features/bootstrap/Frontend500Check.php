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

class Frontend500CheckContext extends MinkContext
{
    private $visitedLinks = array();
    private $failedLinks = array();

    /**
     * @Then /^I don\'t get the http status code "([^"]*)" in frontend$/
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
     * @When /^I visit all links I find on each page in frontend$/
     */
    public function iVisitAllLinksIFindOnEachPage()
    {
        // wait otherwise the page is not loaded
        $this->waitForFrontend();
        $linkTags = $this->getLinkTagsOfNavigation();
        $links = array();
        foreach ($linkTags as $linkTag) {
            $links[] = $linkTag->getAttribute('href');
        }
        foreach ($links as $baseurl) {
           // $baseurl = $linkTag->getAttribute('href');
            echo "\n".$baseurl;
            if (preg_match('/(\.xml)/', $baseurl)) {
                continue;
            }
            if (!$this->testLink($baseurl)) {
//                $this->getMainContext()->session->back();
//                $this->waitForFrontend();
            } else {
                // check links of content
                try {
                    $contentLinksElement = $this->getMainContext()->page->findAll('css', '#content2 a');
                    $contentlinks = array();
                    foreach ($contentLinksElement as $contentlink) {
                        $contentlinks[] = $contentlink->getAttribute('href');
                    }
                    foreach ($contentlinks as $url) {

                        if (!preg_match('/http/', $url)) {
                            continue;
                        }
                        echo "\n".$url;
                        if (!$this->testLink($url)) {
                            echo 'Failed';
                        }
//                        $this->getMainContext()->session->back();
//                        $this->waitForFrontend();
                    }
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function testLink($url)
    {
        if (in_array($url, $this->visitedLinks)){
            echo ' (Duplicate)';
            if (in_array($url, $this->failedLinks)){
                return false;
            }
            return true;
        }
        $this->visitedLinks[] = $url;
        $this->getMainContext()->iAmOn($url);
        if (preg_match('/(http)/', $url)) {
            return true;
        }
        $this->waitForFrontend();
        if ($this->currentPageIsMaintenance()) {
            $this->failedLinks[] = $url;
            return false;
        }
        return true;
    }

    private function currentPageIsMaintenance()
    {
        $html = $this->getMainContext()->page->find('css', 'body')->getHtml();
        if (preg_match('/maintenance mode/', $this->getMainContext()->page->find('css', 'body')->getHtml()) || empty($html)) {
            return true;
        }
        return false;
    }

    private function getLinkTagsOfNavigation()
    {
        try {
            echo "If this test doesn't check all links change the css selector in features/bootstrap/Frontend500Check.php:118 to find your navigation links.";
            return $this->getMainContext()->page->findAll('css', '#navigation a');
        } catch (\Exception $e) {
            return array();
        }
    }

    private function waitForFrontend()
    {
        $this->getMainContext()->session->wait(2000, 'document.getElementsByTagName("body")[0] !== undefined');
    }
}

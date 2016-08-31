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


define('BEHAT_ERROR_REPORTING', E_ERROR | E_WARNING | E_PARSE);
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    /**
     * @var \Behat\Mink\Session
     */
    public $session;

    /**
     * @var \Behat\Mink\Element\DocumentElement
     */
    public $page;

    /**
     * @var array cloudrexx`s configuration array
     */
    public $config;


    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {

        require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config/configuration.php';
        require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config/settings.php';
        require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config/set_constants.php';
        ini_set('xdebug.max_nesting_level', 1000);
        global $_CONFIG;
        $this->config = $_CONFIG;

        // check whether the backend has 500 server errors on links
        $this->useContext('frontend500Check', new Frontend500CheckContext());
        $this->useContext('backend500Check', new Backend500CheckContext());
    }

    /**
     * @Given /^I am logged in to backend with login "([^"]*)" and password "([^"]*)"$/
     */
    public function iAmLoggedInToBackendWithLoginAndPassword($username, $password)
    {
        $this->iOpenBrowser();
        $this->iAmInBackend();
        $this->iEnterTheUsername($username);
        $this->iEnterThePassword($password);
        $this->iPressTheSubmitButton();
    }

    /**
     * @Given /^I am in backend$/
     */
    public function iAmInBackend()
    {
        $this->iAmOn('http://' . $this->config['domainUrl'] . '/' . ASCMS_INSTANCE_OFFSET . ASCMS_BACKEND_PATH);
//        $this->iAmOn('http://' . $this->config['domainUrl'] . ASCMS_INSTANCE_OFFSET . ASCMS_BACKEND_PATH);
    }

    /**
     * @Given /^I am in frontend$/
     */
    public function iAmInFrontend()
    {
        $this->iAmOn('http://' . $this->config['domainUrl'] . '/' .ASCMS_INSTANCE_OFFSET);
//        $this->iAmOn('http://' . $this->config['domainUrl'] . ASCMS_INSTANCE_OFFSET);
    }

    public function iOpenBrowser()
    {
        $driver        = new \Behat\Mink\Driver\Selenium2Driver(
            'firefox',
            array(
                'javascriptEnabled' => true,
            )
        );
        $this->session = new \Behat\Mink\Session($driver);
        $this->session->start();
    }

    /**
     * @Given /^I am on "([^"]*)"$/
     */
    public function iAmOn($arg1)
    {
        if (!$this->session) {
            $this->iOpenBrowser();
        }
        $url = $arg1;
        $this->session->visit($url);
        $this->page = $this->session->getPage();
    }

    /**
     * @Given /^I enter the username "([^"]*)"$/
     */
    public function iEnterTheUsername($username)
    {
        $input = $this->page->findById('username');
        $input->setValue($username);
    }

    /**
     * @Given /^I enter the password "([^"]*)"$/
     */
    public function iEnterThePassword($password)
    {
        $input = $this->page->findById('password');
        $input->setValue($password);
    }

    /**
     * @Given /^I press the submit button$/
     */
    public function iPressTheSubmitButton()
    {

        $button = $this->page->findById('login_button');

        $button->click();
    }

    /**
     * @Then /^I close the session$/
     */
    public function iCloseTheSession()
    {
        $this->session->stop();
    }

    /**
     * Get a html element by id attribute value
     * @param string $tag the tag name of the element, e.g. input, div, span
     * @param string $id the id attribute value of the field to get
     * @return mixed
     * @throws Behat\Mink\Exception\ElementNotFoundException Element not found
     */
    public function objectById($tag, $id)
    {
        return $this->objectByCssSelector($tag . '#' . $id);
    }

    /**
     * Get a html element by css selector notation
     * @param string $cssSelector the css selector path
     * @return mixed
     * @throws Behat\Mink\Exception\ElementNotFoundException
     */
    public function objectByCssSelector($cssSelector)
    {
        $element = $this->page->find('css', $cssSelector);
        try {
            if ($element == null) {
                // @todo: add cloudrexx exception
                throw new \Behat\Mink\Exception\ElementNotFoundException($this->session, 'input', 'css', 'input[type="submit"]');
            }
        } catch (Exception $e) {
            echo $e->getLine();
        }
        return $element;
    }
}

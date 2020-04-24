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
 * Handling Request
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */

namespace Cx\Core\Routing\Model\Entity;

/**
 * Handling Request
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */

class Request {

    /**
     * HTTP requested method
     *
     * @var String
     */
    protected $httpRequestMethod;

    /**
     * Resolved url object
     *
     * @var \Cx\Core\Routing\Url
     */
    protected $url;

    /**
     * Request headers
     * @var array Key=>value type array
     */
    protected $headers = array();

    /**
     * POST data of request
     *
     * @var array Sanitized POST data
     */
    protected $postData = array();

    /**
     * COOKIE data of request
     *
     * @var array Sanitized COOKIE data
     */
    protected $cookieData = array();

    /**
     * Constructor to initialize the $httpRequestMethod and $url
     *
     * @param String $method
     * @param Object $resolvedUrl
     */
    public function __construct($method, \Cx\Core\Routing\Url $resolvedUrl, $headers = array()) {
        $this->httpRequestMethod = strtolower($method);
        $this->url = $resolvedUrl;
        $this->headers = $headers;
        $this->postData = contrexx_input2raw($_POST);
        $this->cookieData = contrexx_input2raw($_COOKIE);
    }

    /**
     * Get the httpRequest method
     *
     * @return String
     */
    public function getHttpRequestMethod() {
        return $this->httpRequestMethod;
    }

    /**
     * Get the httpRequest method
     * 
     * @param String $method
     */
    public function setHttpRequestMethod($method) {
        $this->httpRequestMethod = $method;
    }
    
    /**
     * Get the resolved url object
     *
     * @return \Cx\Core\Routing\Url
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Tells whether a GET or POST parameter is set
     * @param string $name Name of the param to check
     * @param boolean $get (optional) Set to false to check POST
     * @return boolean True of param is set, false otherwise
     */
    public function hasParam($name, $get = true) {
        if ($get) {
            return isset($this->getUrl()->getParamArray()[$name]);
        }

        return isset($this->postData[$name]);
    }

    /**
     * Returns the param identified by $name
     * @param string $name Name of the param to return value of
     * @param boolean $get (optional) Set to false to check POST
     * @throws \Exception If a param is requested that is not set
     * @return string Parameter value
     */
    public function getParam($name, $get = true) {
        if (!$this->hasParam($name, $get)) {
            throw new \Exception('Param not set');
        }

        // return data from GET
        if ($get) {
            return $this->getUrl()->getParamArray()[$name];
        }

        // return data from POST
        return $this->postData[$name];
    }

    /**
     * Returns all params
     * @param boolean $get (optional) Set to false to check POST
     * @return array Parameters values
     */
    public function getParams($get = true)
    {
        // return data from GET
        if ($get) {
            return $this->getUrl()->getParamArray();
        }

        // return data from POST
        return $this->postData;
    }

    /**
     * Tells whether a cookie is set
     * @todo This should be based on a member variable instead of superglobal
     * @param string $name Name of the param to check
     * @return boolean True of param is set, false otherwise
     */
    public function hasCookie($name) {
        return isset($this->cookieData[$name]);
    }

    /**
     * Returns the value of the cookie identified by $name
     * @todo This should be based on a member variable instead of superglobal
     * @param string $name Name of the cookie to return value of
     * @throws \Exception If a cookie is requested that is not set
     * @return string Cookie value
     */
    public function getCookie($name) {
        if (!$this->hasCookie($name)) {
            throw new \Exception('Cookie not set');
        }
        return $this->cookieData[$name];
    }

    /**
     * Returns the headers
     * @return array Key=>value type list of headers
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Returns true if the user agent is a mobile device (smart phone, PDA etc.)
     * @todo Code copied from Init.class.php, there might be a better way to detect this
     * @return boolean True for mobile phones, false otherwise
     */
    public function isMobilePhone() {
        $isMobile = false;
        $op = isset($_SERVER['HTTP_X_OPERAMINI_PHONE']) ? strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']) : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        $ac = isset($_SERVER['HTTP_ACCEPT']) ? strtolower($_SERVER['HTTP_ACCEPT']) : '';

        $isMobile = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
            || $op != ''
            || strpos($ua, 'htc') !== false
            || strpos($ua, 'iphone') !== false
            || strpos($ua, 'sony') !== false
            || strpos($ua, 'symbian') !== false
            || strpos($ua, 'nokia') !== false
            || strpos($ua, 'samsung') !== false
            || strpos($ua, 'mobile') !== false
            || strpos($ua, 'windows ce') !== false
            || strpos($ua, 'epoc') !== false
            || strpos($ua, 'opera mini') !== false
            || strpos($ua, 'nitro') !== false
            || strpos($ua, 'j2me') !== false
            || strpos($ua, 'midp-') !== false
            || strpos($ua, 'cldc-') !== false
            || strpos($ua, 'netfront') !== false
            || strpos($ua, 'mot') !== false
            || strpos($ua, 'up.browser') !== false
            || strpos($ua, 'up.link') !== false
            || strpos($ua, 'audiovox') !== false
            || strpos($ua, 'blackberry') !== false
            || strpos($ua, 'ericsson,') !== false
            || strpos($ua, 'panasonic') !== false
            || strpos($ua, 'philips') !== false
            || strpos($ua, 'sanyo') !== false
            || strpos($ua, 'sharp') !== false
            || strpos($ua, 'sie-') !== false
            || strpos($ua, 'portalmmm') !== false
            || strpos($ua, 'blazer') !== false
            || strpos($ua, 'avantgo') !== false
            || strpos($ua, 'danger') !== false
            || strpos($ua, 'palm') !== false
            || strpos($ua, 'series60') !== false
            || strpos($ua, 'palmsource') !== false
            || strpos($ua, 'pocketpc') !== false
            || strpos($ua, 'smartphone') !== false
            || strpos($ua, 'rover') !== false
            || strpos($ua, 'ipaq') !== false
            || strpos($ua, 'au-mic,') !== false
            || strpos($ua, 'alcatel') !== false
            || strpos($ua, 'ericy') !== false
            || strpos($ua, 'up.link') !== false
            || strpos($ua, 'vodafone/') !== false
            || strpos($ua, 'wap1.') !== false
            || strpos($ua, 'wap2.') !== false;
        return $isMobile;
    }

    /**
     * Returns true if the user agent is a tablet
     * @todo Code copied from Init.class.php, there might be a better way to detect this
     * @return boolean True for tablets, false otherwise
     */
    public function isTablet() {
        $isTablet = false;
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        $isTablet = strpos($ua, 'tablet') !== false
            || strpos($ua, 'ipad') !== false
            || strpos($ua, 'sch-i800') !== false
            || strpos($ua, 'gt-p1000') !== false
            || strpos($ua, 'a500') !== false
            || strpos($ua, 'gt-p7100') !== false
            || strpos($ua, 'gt-p1000') !== false
            || strpos($ua, 'at100') !== false
            || (strpos($ua, 'a43') !== false && strpos($ua, 'iphone') === false);
        return $isTablet;
    }
}

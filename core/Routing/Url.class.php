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
 * An URL container
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_routing
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core\Routing;

/**
 * URL Exception
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_routing
 * @todo        Edit PHP DocBlocks!
 */
class UrlException extends \Exception {};

/**
 * An URL container
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_routing
 * @todo        Edit PHP DocBlocks!
 */
class Url {

    /**
     * frontend or backend
     * @var string  Mode needed for generating the url
     */
    protected $mode = 'frontend';

    /**
     * http or https
     * @todo Implement protocol support (at the moment only http is supported)
     * @var String Containing the URL protocol
     */
    protected $protocol = 'http';
    /**
     * example.com
     * @var string
     */
    protected $domain = null;
    /**
     * The/Module?a=10&b=foo
     * index.php?section=x&cmd=y
     * The/Special/Module/With/Params
     * @var string
     */
    protected $path = null;
    /**
     * Virtual language directory, like 'de' or 'en'
     * @var string
     */
    protected $langDir = '';
    /**
     * The/Module
     * index.php
     * The/Special/Module/With/Params
     * @var string
     */
    protected $suggestedTargetPath = '';
    /**
     * ?a=10&b=foo
     * ?section=x&cmd=y
     * @var string
     */
    protected $suggestedParams = '';

    /**
     * The/Module
     * Found/Path/To/Module
     * The/Special/Module
     * @var string
     */
    protected $targetPath = null;

    //the different states of an url
    const SUGGESTED = 1;
    const ROUTED = 2;

    protected $state = 0;
    
    /**
     * The port of the URL
     * @var int
     */
    protected $port = 0;
    
    /**
     * The fragment (after #) part of the URL
     * @var string
     */
    protected $fragment = '';

    /**
     * Initializes $domain, $protocol, $port and $path.
     * @param string $url http://example.com/Test
     * @param bool $replacePorts - indicates if we need to replace ports with default ones
     */
    public function __construct($url, $replacePorts = false) {
        
        $data = parse_url($url);
        if (isset($data['host'])) {
            $this->domain   = $data['host'];
        }
        if (empty($this->domain)) {
            $this->domain = \Env::get('config')['domainUrl'];
        }
        $this->protocol = $data['scheme'];
        if (empty($this->protocol)) {
            $this->protocol = 'http';
        }
        if ($this->protocol == 'file') {
            // we don't want virtual language dir in file URLs
            $this->setMode('backend');
        }
        if (isset($data['port'])) {
            $this->port = $data['port'];
        }
        if ($replacePorts) {
            $this->port = $this->getDefaultPort();
        }
        if (!$this->port) {
            $this->port = getservbyname($this->protocol, 'tcp');
        }
        $path = '';
        if (isset($data['path'])) {
            $path = $data['path'];
        }
        $path = ltrim($path, '/');
        
        // do not add virtual language dir for files
        $fileName = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsitePath() . '/' . $path;
        if (file_exists($fileName)) {
            $this->setMode('backend');
        }
        $this->realPath = '/' . $path;

        // do not add virtual language dir in backend
        if (strpos($this->realPath, \Cx\Core\Core\Controller\Cx::instanciate()->getBackendFolderName()) === 0) {
            $this->setMode('backend');
        }
        
        if(!empty($data['query'])) {
            $path .= '?' . $data['query'];
        }
        if (!empty($path)) {
            $this->setPath($path);
        } else {
            $this->suggest();
        }
        
        if (!empty($data['fragment'])) {
            $this->fragment = $data['fragment'];
        }

        $this->addPassedParams();
    }

    public function setMode($mode) {
        if (($mode == 'frontend') || ($mode == 'backend')) {
            $this->mode = $mode;
        } else {
            \DBG::msg('URL: Invalid url mode "'.$mode.'"');
        }
    }
    
    /**
     * Checks wheter this Url points to a location within this installation
     * @todo This does not work correctly if setPath() is called from outside
     * @return boolean True for internal URL, false otherwise
     */
    public function isInternal() {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        
        // check domain
        $domainRepo = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Core\Net\Model\Entity\Domain'
        );
        if (!$domainRepo->findOneBy(array('name' => $this->getDomain()))) {
            return false;
        }
        
        // check offset
        $installationOffset = \Env::get('cx')->getWebsiteOffsetPath();
        $providedOffset = $this->realPath;
        if (
            $installationOffset !=
            substr($providedOffset, 0, strlen($installationOffset))
        ) {
            return false;
        }
        return true;
    }

    /**
     * Get the protocol
     * 
     * @return String
     */
    public function getProtocol() {
        return $this->protocol;
    }
    
    public function getMode() {
        return $this->mode;
    }

    /**
     * Whether the routing already treated this url
     */
    public function isRouted() {
        return $this->state >= self::ROUTED;
    }
    
    /**
     * gets port of URL;
     */
    function getPort() {
        return $this->port;
    }

    /**
     * sets port of URL;
     */
    function setPort($port) {
        $this->port = $port;
    }
    
    /** 
     * gets default port from settings
     */
    function getDefaultPort() {
        $mode = $this->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND ? 'Backend' : 'Frontend';
        \Cx\Core\Setting\Controller\Setting::init('Config', null, 'Yaml', null, \Cx\Core\Setting\Controller\Setting::NOT_POPULATE);
        $protocol = strtoupper($this->getProtocol());
        $port  =  \Cx\Core\Setting\Controller\Setting::getValue('port' . $mode . $protocol, 'Config');
        return $port;
    }    

    /**
     * sets $this->suggestedParams and $this->suggestedTargetPath
     */
    public function suggest() {
        if ($this->state == self::SUGGESTED) {
            return;
        }
        $matches = array();
        $matchCount = preg_match('/([^\?]+)(.*)/', $this->path, $matches);

        if ($matchCount == 0) {//seemingly, no parameters are set.
            $this->suggestedTargetPath = $this->path;
            $this->suggestedParams = '';
        } else {
            $parts = explode('?', $this->path);
            if ($parts[0] == '') { // we have no path or filename, just set parameters
                $this->suggestedTargetPath = '';
                $this->suggestedParams = $this->path;
            } else {
                $this->suggestedTargetPath = $matches[1];
                $this->suggestedParams = $matches[2];
            }
        }
        

        $this->state = self::SUGGESTED;
    }

    public function getDomain() {
        return $this->domain;
    }

    public function setDomain(\Cx\Core\Net\Model\Entity\Domain $domain) {
        $this->domain =  $domain->getName();
    }

    public function getPath() {
        return $this->path;
    }

    public function setPath($path) {
        $pathOffset = substr(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath(), 1);
        if (!empty($pathOffset) && substr($path, 0, strlen($pathOffset)) == $pathOffset) {
            $path = substr($path, strlen($pathOffset) + 1);
        }
        $path = explode('/', $path);
        if (\FWLanguage::getLanguageIdByCode($path[0]) !== false) {
            $this->langDir = $path[0];
            unset($path[0]);
        }

        //keep parameters to append them after setting the new path (since parameters are stored in path)
        $params = '';
        if (strpos($this->path, '?') !== false) {
            $params = explode('?', $this->path);
            $params = $params[1];
        }

        $path = implode('/', $path);
        $this->path = $path;
        $this->path .= !empty($params) ? '?'.$params : '';
        $this->suggest();
    }

    public function setTargetPath($path) {
        $this->state = self::ROUTED;
        $this->targetPath = $path;
    }

    /**
     * Add all passed parameters which are skin related.
     *
     * @access  private
     */
    private function addPassedParams() {
        $existingParams = $this->getParamArray();

        if (!empty($_GET['preview']) && !isset($existingParams['preview'])) {
            $this->setParam('preview', $_GET['preview']);
        }
        if ((isset($_GET['appview']) && ($_GET['appview'] == 1)) && !isset($existingParams['appview'])) {
            $this->setParam('appview', $_GET['appview']);
        }
    }

    /**
     * Set a single parameter.
     *
     * @access  public
     * @param   mixed       $key
     * @param   mixed       $value
     */
    public function setParam($key, $value) {
        if ($value === null) {
            $params = $this->getParamArray();
            if (isset($params[$key])) {
                unset($params[$key]);
                $this->removeAllParams();
                $this->addParamsToPath($params);
            }
            return;
        }
        if (!empty($key)) {
            $this->setParams(array($key => $value));
        }
    }

    /**
     * Set multiple parameters.
     *
     * @access  public
     * @param   array or string     $params
     */
    public function setParams($params) {
        if (!is_array($params)) {
            $params = self::params2array($params);
        }

        if (!empty($params)) {
            $this->addParamsToPath($params);
        }
    }

    /**
     * Add new parameters to path:
     * - Existing parameters (having not an array as value) will be overwritten by the value of the new parameter (having the same key).
     * - Existing parameters (having an array as value) will be merged with the value of the new parameter.
     * - New parameters will simply be added.
     *
     * @access  private
     * @param   array       $paramsToAdd
     */
    private function addParamsToPath($paramsToAdd) {
        $paramsFromPath = $this->splitParamsFromPath();
        $params = array_replace_recursive($paramsFromPath, $paramsToAdd);
        $this->writeParamsToPath($params);
    }

    /**
     * Split parameters from path.
     *
     * @access  private
     * @return  array       $params
     */
    private function splitParamsFromPath() {
        $params = array();

        if (strpos($this->path, '?') !== false) {
            list($path, $query) = explode('?', $this->path);
            if (!empty($query)) {
                $params = self::params2array($query);
            }
        }

        return $params;
    }

    /**
     * Remove all params from path
     */
    public function removeAllParams() {
        $path = explode('?', $this->path);
        $this->path = $path[0];
    }

    /**
     * Write parameters to path.
     *
     * @access  private
     * @param   array       $params
     */
    private function writeParamsToPath($params) {
        $path = explode('?', $this->path);
        $path[1] = self::array2params($params);
        $this->path = implode('?', $path);
    }

    /**
     * Convert parameter string to array.
     *
     * @access  public
     * @param   string      $params
     * @return  array       $array
     */
    public static function params2array($params = '') {
        $array = array();
        if (strpos($params, '?') !== false) {
            list($path, $params) = explode('?', $params);
        }
        if (!empty($params)) {
            $params = html_entity_decode($params, ENT_QUOTES, CONTREXX_CHARSET);
            parse_str($params, $array);
            if (isset($array['csrf'])) {
                unset($array['csrf']);
            }
            $array = self::encodeParams($array);
        }
        return $array;
    }

    /**
     * Convert array to parameter string.
     *
     * @access  public
     * @param   array       $array
     * @return  string
     */
    public static function array2params($array = array()) {
        if (isset($array['csrf'])) {
            unset($array['csrf']);
        }

        // Decode parameters since http_build_query() encodes them by default.
        // Otherwise the percent (which acts as escape character) of the already encoded string would be encoded again.
        $array = self::decodeParams($array);

        return http_build_query($array, null, '&');
    }

    /**
     * Url encode passed array (key and value).
     *
     * @access  public
     * @param   array       $input
     * @return  array       $output
     */
    public static function encodeParams($input = array()) {
        $output = array();

        foreach ($input as $key => $value) {
            // First decode url before encode them (in case that the given string is already encoded).
            // Otherwise the percent (which acts as escape character) of the already encoded string would be encoded again.
            $key = urlencode(urldecode($key));

            if (is_array($value)) {
                $output[$key] = self::encodeParams($value);
            } else {
                $output[$key] = urlencode(urldecode($value));
            }
        }

        return $output;
    }

    /**
     * Url decode passed array (key and value).
     *
     * @access  public
     * @param   array       $input
     * @return  array       $output
     */
    public static function decodeParams($input = array()) {
        $output = array();

        foreach ($input as $key => $value) {
            $key = urldecode($key);

            if (is_array($value)) {
                $output[$key] = self::decodeParams($value);
            } else {
                $output[$key] = urldecode($value);
            }
        }

        return $output;
    }

    public function getTargetPath() {
        return $this->targetPath;
    }

    /**
     * @author Michael Ritter <michael.ritter@comvation.com>
     * @return array
     */
    public function getParamArray() {
        return $this->splitParamsFromPath();
    }

    public function getSuggestedTargetPath() {
        return $this->suggestedTargetPath;
    }

    public function setSuggestedTargetPath($path) {
        $this->suggestedTargetPath = $path;
    }

    public function setSuggestedParams($params) {
        $this->suggestedParams = $params;
    }

    public function getSuggestedParams() {
        return $this->suggestedParams;
    }
    
    
    public static function fromRequest() {
        if (php_sapi_name() === 'cli') {
            return new Url('file://' . getcwd());
        }
        $s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
        $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . $s;
        $port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':'.$_SERVER['SERVER_PORT']);
        return new Url($protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'], true);
    }

    /**
     * @param $string request the captured request
     * @param $string pathOffset ASCMS_PATH_OFFSET
     */
    public static function fromCapturedRequest($request, $pathOffset, $get) {
        global $_CONFIG;

        if(substr($request, 0, strlen($pathOffset)) != $pathOffset)
            throw new UrlException("'$request' doesn't seem to start with provided offset '$pathOffset'");

        //cut offset
        $request = substr($request, strlen($pathOffset)+1);
        $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_CONFIG['domainUrl'];
        $protocol = ASCMS_PROTOCOL;


        //skip captured request from mod_rewrite
        unset($get['__cap']);

        // workaround for legacy ?page=123 requests by routing to an alias like /legacy_page_123
        $additionalParams = '';
        if (
            isset($get['page']) && preg_match('/^\d+$/', $get['page']) &&
            \Env::get('cx')->getMode() != \Cx\Core\Core\Controller\Cx::MODE_BACKEND
        ) {
            $request = 'legacy_page_'.$get['page'];
            $additionalParams = 'external=permanent';
            unset($get['page']);
        }

        if (($params = self::array2params($get)) && (strlen($params) > 0)) {
            $params = '?'.$params . ($additionalParams != '' ? '&' . $additionalParams : '');
        } else {
            $params = ($additionalParams != '' ? '?' . $additionalParams : '');
        }
        $request = preg_replace('/index.php/', '', $request);

        return new Url($protocol.'://'.$host.'/'.$request.$params, true);
    }


    /**
     * Returns an Url object for module, cmd and lang
     * @todo There could be more than one page using the same module and cmd per lang
     * @param string $module Module name
     * @param string $cmd (optional) Module command, default is empty string
     * @param int $lang (optional) Language to use, default is FRONTENT_LANG_ID
     * @param array $parameters (optional) HTTP GET parameters to append
     * @param string $protocol (optional) The protocol to use
     * @param boolean $returnErrorPageOnError (optional) If set to TRUE, this method will return an URL object that point to the error page of Cloudrexx. Defaults to TRUE.
     * @return \Cx\Core\Routing\Url Url object for the supplied module, cmd and lang
     */
    public static function fromModuleAndCmd($module, $cmd = '', $lang = '', $parameters = array(), $protocol = '', $returnErrorPageOnError = true) {
        if ($lang == '') {
            $lang = FRONTEND_LANG_ID;
        }
        $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $page = $pageRepo->findOneByModuleCmdLang($module, $cmd, $lang);

        // In case we were unable to locate the requested page, we shall
        // return the URL to the error page
        if (!$page && $returnErrorPageOnError && $module != 'Error') {
            $page = $pageRepo->findOneByModuleCmdLang('Error', '', $lang);
        }

        // In case we were unable to locate the requested page
        // and were also unable to locate the error page, we shall
        // return the URL to the Homepage
        if (!$page && $returnErrorPageOnError) {
            return static::fromDocumentRoot(null, $lang, $protocol);
        }

        // Throw an exception if we still were unable to locate
        // any usfull page till now
        if (!$page) {
            throw new UrlException("Unable to find a page with MODULE:$module and CMD:$cmd in language:$lang!");
        }

        return static::fromPage($page, $parameters, $protocol, true);
    }
    
    /**
     * This returns an Url object for an absolute or relative url or an Url object
     * @author Michael Ritter <michael.ritter@comvation.com>
     * @todo This method does what the constructor of a clean Url class should do!
     * @param mixed $url Url object or absolute or relative url as string
     * @return \Cx\Core\Routing\self|\Cx\Core\Routing\Url Url object representing $url
     */
    public static function fromMagic($url) {
        // if an Url object is provided, return
        if (is_object($url) && $url instanceof self) {
            return $url;
        }
        
        $matches = array();
        preg_match('#(http(s)?|file)://#', $url, $matches);
        
        // relative URL
        if (!count($matches)) {
            
            $absoluteUrl = self::fromRequest();
            preg_match('#((?:http(?:s)?|file)://)((?:[^/]*))([/$](?:.*)/)?#', $absoluteUrl->toString(true), $matches);
            
            // starting with a /?
            if (substr($url, 0, 1) == '/') {
                $url = $matches[1] . $matches[2] . $url;
            } else {
                $url = $matches[1] . $matches[2] . $matches[3] . $url;
            }
            $url = new static($url);
            
        // absolute URL
        } else {
            $url = new static($url);
        }
        
        return $url;
    }

    /**
     * Returns an Url object pointing to the documentRoot of the website
     * @param int $lang (optional) Language to use, default is FRONTEND_LANG_ID
     * @param string $protocol (optional) The protocol to use
     * @return \Cx\Core\Routing\Url Url object for the documentRoot of the website
     */
    public static function fromDocumentRoot($arrParameters = array(), $lang = '', $protocol = '')
    {
        if ($lang == '') {
            $lang = FRONTEND_LANG_ID;
        }
        if ($protocol == '') {
            $protocol = ASCMS_PROTOCOL;
        }
        $host = \Env::get('config')['domainUrl'];
        $offset = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath();
        $langDir = \FWLanguage::getLanguageCodeById($lang);
        $parameters = '';
        if (count($arrParameters)) {
            $arrParams = array();
            foreach ($arrParameters as $key => $value) {
                $arrParams[] = $key.'='.$value;
            }
            $parameters = '?'.implode('&', $arrParams);
        }

        return new Url($protocol.'://'.$host.$offset.'/'.$langDir.'/'.$parameters, true);
    }

    /**
     * Returns an Url object for node and language
     * @param int $nodeId Node id
     * @param int $lang (optional) Language to use, default is FRONTEND_LANG_ID
     * @param array $parameters (optional) HTTP GET parameters to append
     * @param string $protocol (optional) The protocol to use
     * @return \Cx\Core\Routing\Url Url object for the supplied module, cmd and lang
     */
    public static function fromNodeId($nodeId, $lang = '', $parameters = array(), $protocol = '') {
        if ($lang == '') {
            $lang = FRONTEND_LANG_ID;
        }
        $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $page = $pageRepo->findOneBy(array(
            'node' => $nodeId,
            'lang' => $lang,
        ));
        return static::fromPage($page, $parameters, $protocol);
    }

    /**
     * Returns an Url object for node and language
     * @param \Cx\Core\ContentManager\Model\Entity\Node $node Node to get the Url of
     * @param int $lang (optional) Language to use, default is FRONTENT_LANG_ID
     * @param array $parameters (optional) HTTP GET parameters to append
     * @param string $protocol (optional) The protocol to use
     * @return \Cx\Core\Routing\Url Url object for the supplied module, cmd and lang
     */
    public static function fromNode($node, $lang = '', $parameters = array(), $protocol = '') {
        if ($lang == '') {
            $lang = FRONTEND_LANG_ID;
        }
        $page = $node->getPage($lang);
        return static::fromPage($page, $parameters, $protocol);
    }

    /**
     * Returns the URL object for a page id
     * @param int $pageId ID of the page you'd like the URL to
     * @param array $parameters (optional) HTTP GET parameters to append
     * @param string $protocol (optional) The protocol to use
     * @return \Cx\Core\Routing\Url Url object for the supplied page id
     */
    public static function fromPageId($pageId, $parameters = array(), $protocol = '') {
        $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $page = $pageRepo->findOneBy(array(
            'id' => $pageId,
        ));
        return static::fromPage($page, $parameters, $protocol);
    }

    /**
     * Returns the URL object for a page
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page to get the URL to
     * @param array $parameters (optional) HTTP GET parameters to append
     * @param string $protocol (optional) The protocol to use
     * @return \Cx\Core\Routing\Url Url object for the supplied page
     */
    public static function fromPage($page, $parameters = array(), $protocol = '') {
        if ($protocol == '') {
            $protocol = ASCMS_PROTOCOL;
        }
        $host = \Env::get('config')['domainUrl'];
        $offset = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath();
        $path = $page->getPath();
        $langDir = \FWLanguage::getLanguageCodeById($page->getLang());
        $getParams = '';
        if (count($parameters)) {
            $paramArray = array();
            foreach ($parameters as $key=>$value) {
                $paramArray[] = $key . '=' . $value;
            }
            $getParams = '?' . implode('&', $paramArray);
        }
        return new Url($protocol.'://'.$host.$offset.'/'.$langDir.$path.$getParams, true);
    }

    /**
     * Returns an absolute or relative link
     * @param boolean $absolute (optional) set to false to return a relative URL
     * @return type 
     */
    public function toString($absolute = true, $forcePort = false) {
        if(!$absolute) {
            return \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath()  . '/' .
                ($this->getMode() != 'backend' ? $this->getLangDir().'/' : '') . 
                $this->path . (empty($this->fragment) ? '' : '#' . $this->fragment);
        }
        $defaultPort = getservbyname($this->protocol, 'tcp');
        $portPart = '';
        if ($this->port && (!$defaultPort || $this->port != $defaultPort || $forcePort)) {
            $portPart = ':' . $this->port;
        }
        return $this->protocol . '://' .
            $this->domain .
            $portPart .
            $this->toString(false);
    }

    public function getLangDir() {
        $lang_dir = '';

        if ($this->langDir == '' && defined('FRONTEND_LANG_ID')) {
            $lang_dir = \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID);
        } else {
            $lang_dir = $this->langDir;
        }

        return $lang_dir;
    }

    public function setLangDir($langDir, $page = null) {
        $this->langDir = $langDir;

        if ($page) {
            $langId = \FWLanguage::getLanguageIdByCode($langDir);
            $page = $page->getNode()->getPage($langId);
            if ($page) {
                $this->setPath(substr($page->getPath(), 1));
            }
        }
    }

    /**
     * Returns an absolute link;
     * @return type
     */
    public function __toString()
    {
        return $this->toString(false);
    }


    /**
     * Returns the given string with any ampersands ("&") replaced by "&amp;"
     *
     * Any "&amp;"s already present in the string won't be changed;
     * no double encoding takes place.
     * @param   string  $url    The URL to be encoded
     * @return  string          The URL with ampersands encoded
     */
    static function encode_amp($url)
    {
        return preg_replace('/&(?!amp;)/', '&amp;', $url);
    }

}

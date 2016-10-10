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
 * Cache
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     3.1.2
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */
namespace Cx\Core_Modules\Cache\Controller;
/**
 * Cache
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     3.1.2
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */
class Cache extends \Cx\Core_Modules\Cache\Controller\CacheLib
{
    var $boolIsEnabled = false; //Caching enabled?
    var $intCachingTime; //Expiration time for cached file

    var $strCachePath; //Path to cache-directory
    var $strCacheFilename; //Name of the current cache-file

    var $arrPageContent = array(); //array containing $_SERVER['REQUEST_URI'] and $_REQUEST

    var $arrCacheablePages = array(); //array of all pages with activated caching

    /**
     * Constructor
     *
     * @global array $_CONFIG
     */
    public function __construct()
    {
        $this->initContrexxCaching();
        parent::__construct();
    }

    protected function initContrexxCaching()
    {
        global $_CONFIG;

        // check the cache directory
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if (!is_dir($cx->getWebsiteCachePath())) {
            \Cx\Lib\FileSystem\FileSystem::make_folder($cx->getWebsiteCachePath());
        }
        if (!is_writable($cx->getWebsiteCachePath())) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($cx->getWebsiteCachePath());
        }
        $this->strCachePath = $cx->getWebsiteCachePath() . '/';

        // in case the request's origin is from a mobile devie
        // and this is the first request (the InitCMS object wasn't yet
        // able to determine of the mobile device wishes to be served
        // with the system's mobile view), we shall deactivate the caching system
        if (\InitCMS::_is_mobile_phone()
            && !\InitCMS::_is_tablet()
            && !isset($_REQUEST['smallscreen'])
        ) {
            $this->boolIsEnabled = false;
            return;
        }

        if ($_CONFIG['cacheEnabled'] == 'off') {
            $this->boolIsEnabled = false;
            return;
        }

        if (isset($_REQUEST['caching']) && $_REQUEST['caching'] == '0') {
            $this->boolIsEnabled = false;
            return;
        }

// TODO: Reimplement - see #1205
        /*if ($this->isException()) {
            $this->boolIsEnabled = false;
            return;
        }*/
        
        if (\Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_MINIMAL) {
            $this->boolIsEnabled = false;
            return;
        }

        $this->boolIsEnabled = true;

        $this->intCachingTime = intval($_CONFIG['cacheExpiration']);

        // Use data of $_GET and $_POST to uniquely identify a request.
        // Important: You must not use $_REQUEST instead. $_REQUEST also contains
        //            the data of $_COOKIE. Whereas the cookie information might
        //            change in each request, which might break the caching-
        //            system.
        $request = array_merge_recursive($_GET, $_POST);
        ksort($request);
        $this->arrPageContent = array(
            'url' => $_SERVER['REQUEST_URI'],
            'request' => $request,
        );
        $this->strCacheFilename = md5(serialize($this->arrPageContent));
    }


    /**
     * Start caching functions. If this page is already cached, load it, otherwise create new file
     */
    public function startContrexxCaching()
    {
        if (!$this->boolIsEnabled) {
            return null;
        }
        $files = glob($this->strCachePath . $this->strCacheFilename . "*");

        foreach ($files as $file) {
            if (filemtime($file) > (time() - $this->intCachingTime)) {
                //file was cached before, load it
                $endcode = file_get_contents($file);

                echo $this->internalEsiParsing($endcode, true);
                exit;
            } else {
                $File = new \Cx\Lib\FileSystem\File($file);
                $File->delete();
            }
        }
    }


    /**
     * End caching functions. Check for a sessionId: if not set, write pagecontent to a file.
     */
    public function endContrexxCaching($page, $endcode)
    {
        if (!$this->boolIsEnabled) {
            return $this->internalEsiParsing($endcode);
        }
        if (session_id() != '' && \FWUser::getFWUserObject()->objUser->login()) {
            return $this->internalEsiParsing($endcode);
        }
        if (!$page->getCaching()) {
            return $this->internalEsiParsing($endcode);
        }
        $handleFile = $this->strCachePath . $this->strCacheFilename . "_" . $page->getId();
        $File = new \Cx\Lib\FileSystem\File($handleFile);
        $File->write($endcode);
        return $this->internalEsiParsing($endcode);
    }

    /**
     * Parses ESI directives internally if configured to do so
     * @param string $htmlCode HTML code to replace ESI directives in
     * @return string Parsed HTML code
     */
    public function internalEsiParsing($htmlCode, $cxNotYetInitialized = false) {
        
        if (!is_a($this->getSsiProxy(), '\\Cx\\Core_Modules\\Cache\\Model\\Entity\\ReverseProxyCloudrexx')) {
            return $htmlCode;
        }
        
        // Cannot be initialized yet, if Contrexx caching is active DB is not
        // present yet, which means that Url::from* does not work
        $apiUrlString = '';
        
        // Random include tags
        $htmlCode = preg_replace_callback(
            '#<!-- ESI_RANDOM_START -->[\s\S]*<esi:assign name="content_list">\s*\[([^\]]+)\]\s*</esi:assign>[\s\S]*<!-- ESI_RANDOM_END -->#',
            function($matches) {
                $uris = explode('\',\'', substr($matches[1], 1, -1));
                $randomNumber = rand(0, count($uris) - 1);
                $uri = $uris[$randomNumber];
                
                // this needs to match the format below!
                return '<esi:include src="' . $uri . '" onerror="continue"/>';
            },
            $htmlCode
        );
        
        // Replace include tags
        $settings = $this->getSettings();
        $htmlCode = preg_replace_callback(
            '#<esi:include src="([^"]+)" onerror="continue"/>#',
            function($matches) use (&$apiUrlString, &$cxNotYetInitialized, $settings) {
                // return cached content if available
                $cacheFile = $this->getCacheFileNameFromUrl($matches[1]);
                if ($settings['internalSsiCache'] == 'on' && file_exists($this->strCachePath . $cacheFile)) {
                    if (filemtime($this->strCachePath . $cacheFile) > (time() - $this->intCachingTime)) {
                        return file_get_contents($this->strCachePath . $cacheFile);
                    } else {
                        $file = new \Cx\Lib\FileSystem\File($this->strCachePath . $cacheFile);
                        $file->delete();
                    }
                }
                
                if ($cxNotYetInitialized) {
                    \Cx\Core\Core\Controller\Cx::instanciate(
                        \Cx\Core\Core\Controller\Cx::MODE_MINIMAL,
                        true,
                        null,
                        true
                    );
                    $cxNotYetInitialized = false;
                }
                
                // Initialize only when needed, we need DB for this!
                if (empty($apiUrlString)) {
                    $apiUrlString = substr(\Cx\Core\Routing\Url::fromApi('', '', array()), 0, -1);
                }
                
                $query = parse_url($matches[1], PHP_URL_QUERY);
                $path = parse_url($matches[1], PHP_URL_PATH);
                $params = array();
                parse_str($query, $params);
                
                $pathParts = explode('/', str_replace($apiUrlString, '', $path));
                if (
                    count($pathParts) != 4 ||
                    $pathParts[0] != 'Data' ||
                    $pathParts[1] != 'Plain'
                ) {
                    return $matches[0];
                }
                $adapter = contrexx_input2raw($pathParts[2]);
                $method = contrexx_input2raw($pathParts[3]);
                unset($params['cmd']);
                unset($params['object']);
                unset($params['act']);
                $arguments = array('get' => contrexx_input2raw($params));
                
                $json = new \Cx\Core\Json\JsonData();
                $response = $json->data($adapter, $method, $arguments);
                if (
                    !isset($response['status']) ||
                    $response['status'] != 'success' ||
                    !isset($response['data']) ||
                    !isset($response['data']['content'])
                ) {
                    return $matches[0];
                }
                $content = $response['data']['content'];
                
                if ($settings['internalSsiCache'] == 'on') {
                    $file = new \Cx\Lib\FileSystem\File($this->strCachePath . $cacheFile);
                    $file->write($content);
                }

                return $content;
            },
            $htmlCode
        );
        
        return $htmlCode;
    }

    /**
     * Check the exception-list for this site
     *
     * @global     array        $_EXCEPTIONS
     * @return     boolean        true: Site has been found in exception list
     * @todo    Reimplement! Use for restricting caching-option in CM - see #1205
     */
    public function isException()
    {
        global $_EXCEPTIONS;

        if (is_array($_EXCEPTIONS)) {
            foreach ($_EXCEPTIONS as $intKey => $arrInner) {
                if (count($arrInner) == 1) {
                    //filter a complete module
                    if ($_REQUEST['section'] == $arrInner['section']) {
                        return true;
                    }
                } else {
                    //filter a specific part of a module
                    $intArrLength = count($arrInner);
                    $intHits = 0;

                    foreach ($arrInner as $strKey => $strValue) {
                        if ($strKey == 'section') {
                            if ($_REQUEST['section'] == $strValue) {
                                ++$intHits;
                            }
                        } else {
                            if (isset($_REQUEST[$strKey]) && preg_match($strValue, $_REQUEST[$strKey])) {
                                ++$intHits;
                            }
                        }
                    }

                    if ($intHits == $intArrLength) {
                        //all fields have been found, don't cache
                        return true;
                    }
                }
            }
        }

        return false; //if we are coming to this line, no exception has been found
    }

    /**
     * Delete all cache files from tmp directory
     */
    public function cleanContrexxCaching()
    {
        $this->_deleteAllFiles();
    }
}

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
     * @var string $apiUrlString
     * This cannot be set to it's value until DB is initialized (since Url::from* needs DB)
     */
    protected $apiUrlString = '';

    /**
     * @var array List of exceptions which will not be cached
     * For format see isException()
     */
    protected $exceptions = array();

    /**
     * @var boolean Whether to store page cache user based or not
     */
    protected $forceUserbased = false;

    /**
     * Constructor
     *
     * @global array $_CONFIG
     */
    public function __construct()
    {
        parent::__construct();
        $this->initContrexxCaching();
    }

    protected function initContrexxCaching()
    {
        global $_CONFIG;

        if ($_CONFIG['cacheEnabled'] == 'off') {
            $this->boolIsEnabled = false;
            return;
        }

        // in case the request's origin is from a mobile devie
        // and this is the first request (the InitCMS object wasn't yet
        // able to determine of the mobile device wishes to be served
        // with the system's mobile view), we shall cache the request separately
        $isMobile = (
            \InitCMS::_is_mobile_phone() &&
            !\InitCMS::_is_tablet() &&
            !isset($_REQUEST['smallscreen'])
        );

        if (isset($_REQUEST['caching']) && $_REQUEST['caching'] == '0') {
            $this->boolIsEnabled = false;
            return;
        }

        // @todo: A component should have a possibility to add caching exceptions
        if (isset($_GET['templateEditor']) && $_GET['templateEditor'] == 1) {
            $this->boolIsEnabled = false;
            return;
        }

        // @todo: A component should have a possibility to add caching exceptions
        if (isset($_GET['pagePreview'])) {
            $this->boolIsEnabled = false;
            return;
        }

        // Since FE does not yet support caching, we disable it when FE is active
        if (isset($_COOKIE['fe_toolbar']) && $_COOKIE['fe_toolbar'] == 'true') {
            $this->boolIsEnabled = false;
            return;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if ($cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_MINIMAL) {
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
        $this->currentUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' .
            $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $country = '';
        $geoIp = $cx->getComponent('GeoIp');
        if ($geoIp) {
            $countryInfo = $geoIp->getCountryCode(array());
            if (!empty($countryInfo['content'])) {
                $country = $countryInfo['content'];
            }
        }
        $this->arrPageContent = array(
            'url' => $this->currentUrl,
            'request' => $request,
            'isMobile' => $isMobile,
            'country' => $country,
        );
        // since crawlers do not send accept language header, we make it optional
        // in order to keep the logs clean
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->arrPageContent['accept_language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        $this->strCacheFilename = md5(serialize($this->arrPageContent));
    }


    /**
     * Start caching functions. If this page is already cached, load it, otherwise create new file
     */
    public function startContrexxCaching($cx)
    {
        // TODO: $dynVars needs to be built dynamically (via event handler)
        $this->dynVars = array(
            'GEO' => array(
                // This is not specified by W3C but by Akamai
                'country_code' => function() use ($cx) {
                    return $cx->getComponent('GeoIp')->getCountryCode(array())['content'];
                },
            ),
            'HTTP_COOKIE' => array(
                // This only supports PHPSESSID instead of full cookie support
                // as specified by W3C
                'PHPSESSID' => function() {
                    $sessId = 0;
                    if (!empty($_COOKIE[session_name()])) {
                        $sessId = $_COOKIE[session_name()];
                    }
                    return $sessId;
                },
            ),
            // HTTP_ACCEPT_LANGUAGE
            // HTTP_HOST
            // HTTP_USER_AGENT
            'HTTP_REFERER' => function() {
                if (!isset($_SERVER['HTTP_REFERER'])) {
                    return '';
                }
                return $_SERVER['HTTP_REFERER'];
            },
            'QUERY_STRING' => function () {
                // This is not according to W3C specifications since it
                // includes the leading "?" if there are params. This is due
                // to backwards compatibility
                $parameters = array();
                parse_str($_SERVER['QUERY_STRING'], $parameters);
                if (isset($parameters['__cap'])) {
                    unset($parameters['__cap']);
                }
                $queryString = http_build_query($parameters, null, '&');
                if (!empty($queryString)) {
                    return '?' . $queryString;
                }
            },
        );

        if (!$this->boolIsEnabled) {
            return null;
        }
        $files = glob($this->strCachePath . $this->strCacheFilename . "*");

        // sort out false-positives (header and ESI cache files)
        $cacheFileUserRegex = '';
        if (isset($_COOKIE[session_name()])) {
            $cacheFileUserRegex = '(?:_u(?:' . preg_quote($_COOKIE[session_name()]) . ')?)';
        } else {
            $cacheFileUserRegex = '(?:_u0|)';
        }
        $cacheFileRegex = '/([0-9a-f]{32})_(([0-9]+)' . $cacheFileUserRegex . ')?$/';
        $files = preg_grep(
            $cacheFileRegex,
            $files
        );
        if (!count($files)) {
            // no file found, request is not cached yet
            return;
        }
        if (count($files) > 1) {
            // more than one file, there's something strange in the neighborhood
            // bust that ghost:
            \DBG::log('Cache: More than one cache file found:', 'error');
            \DBG::dump($files);
            return;
        }
        $file = current($files);

        if (filemtime($file) > (time() - $this->intCachingTime)) {
            // load headers
            $matches = array();
            preg_match($cacheFileRegex, $file, $matches);
            // @todo: Make header cache user based
            $headerFile = $this->strCachePath . $matches[1] . '_h' . $matches[2];
            if (file_exists($headerFile)) {
                $headers = unserialize(file_get_contents($headerFile));
                if (is_array($headers)) {
                    foreach ($headers as $name=>$value) {
                        if (is_numeric($name)) {
                            // This allows headers without a ':'
                            header($value);
                            continue;
                        }
                        // If expire header is set, check if the cache
                        // is still valid
                        if ($name == 'Expires') {
                            $expireDate = new \DateTime($value);
                            if ($expireDate < new \DateTime()) {
                                // cache is no longer valid
                                $headerFile = new \Cx\Lib\FileSystem\File(
                                    $headerFile
                                );
                                $headerFile->delete();
                                $file = new \Cx\Lib\FileSystem\File($file);
                                $file->delete();
                                return;
                            }
                        }
                        header($name . ': ' . $value);
                    }
                }
            }

            //file was cached before, load it
            $endcode = file_get_contents($file);

            echo $this->internalEsiParsing($endcode, true);
            $parsingTime = $cx->stopTimer();
            \DBG::log("(Cx: {$cx->getId()}) Request parsing completed after $parsingTime (from cache)");
            exit;
        } else {
            $headerFile = new \Cx\Lib\FileSystem\File($headerFile);
            $headerFile->delete();
            $file = new \Cx\Lib\FileSystem\File($file);
            $file->delete();
        }
    }


    /**
     * End caching functions. Check for a sessionId: if not set, write pagecontent to a file.
     */
    public function endContrexxCaching($page, $endcode)
    {
        // back-replace ESI variables that are url encoded
        foreach ($this->dynVars as $groupName=>$vars) {
            if (is_callable($vars)) {
                $esiPlaceholder = '$(' . $groupName . ')';
                $endcode = str_replace(urlencode($esiPlaceholder), $esiPlaceholder, $endcode);
            } else {
                foreach ($vars as $varName=>$url) {
                    $esiPlaceholder = '$(' . $groupName . '{\'' . $varName . '\'})';
                    $endcode = str_replace(urlencode($esiPlaceholder), $esiPlaceholder, $endcode);
                }
            }
        }
        
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        
        $this->exceptions = array(
            // never cache errors
            'Error', 

            // never cache when caching is disabled
            function($cx, $page) {
                return !$this->boolIsEnabled;
            },

            // all the following exceptions are TEMPORARY and only necessary
            // due to non-proper implementation of caching mechanisms

            // do not cache if uploader is in use (since its ID will get cached otherwise)
            function ($cx, $page) {
                return $cx->getComponent('Uploader')->isActive();
            },

            // here come the modules:
            'Access',
            'Blog',
            'Calendar' => array(
                'my_events',
                'add',
                'edit',
                'register',
                'sign',
                'success',
            ),
            'Checkout',
            'Crm',
            'Data',
            'Directory',
            'DocSys',
            'Downloads',
            'Ecard',
            'Egov',
            'Feed',
            'FileSharing',
            'Forum',
            'Gallery',
            'GuestBook',
            'Jobs',
            'Knowledge',
            'Livecam',
            'Market',
            'Media',
            'Media1',
            'Media2',
            'Media3',
            'Media4',
            'MediaDir' => array(
                'latest',
                'popular',
                'myentries',
                'adduser',
                'confirm_in_progress',
                'add',
                'edit',
            ),
            'MemberDir',
            'News' => array(
                'submit',
            ),
            'Newsletter' => array(
                function($page) {
                    return $page->getCmd() == 'profile' && $_SERVER['REQUEST_METHOD'] != 'POST';
                },
            ),
            'Podcast',
            'Shop',
            'Survey',
            'U2u',
            'Voting',
        );

        if ($this->isException($page, $cx)) {
            return $this->internalEsiParsing($endcode);
        }

        // write header cache file
        $resolver = \Env::get('Resolver');
        $headers = $resolver->getHeaders();
        $this->writeCacheFileForRequest(
            $page,
            $headers,
            $endcode,
            $this->forceUserbased
        );
        return $this->internalEsiParsing($endcode);
    }

    /**
     * Writes the cache file for the current request
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Current page (might be null for redirects before postResolve)
     * @param array $headers List of headers set for the current response
     * @param string $endcode Current response
     */
    public function writeCacheFileForRequest($page, $headers, $endcode, $forceUserbased = false) {
        $userbased = $forceUserbased;
        $pageId = '';
        if ($page) {
            $pageId = $page->getId();
            if ($page->isFrontendProtected()) {
                // if no session, abort (we don't cache permission errors)
                if (empty($_COOKIE[session_name()])) {
                    return;
                }
                $userbased = true;
            }
        }
        $user = '';
        if ($userbased) {
            $sessionId = '0';
            // we need to sort out empty session IDs and session ID '0'
            // so _u-part does not match any of the other cases
            if (!empty($_COOKIE[session_name()])) {
                $sessionId = $_COOKIE[session_name()];
            }
            $user = '_u' . $sessionId;
        }
        if (!$userbased && isset($_COOKIE[session_name()])) {
            $user = '_u';
        }

        // cleanup any existing files. This is important in order to
        // differ userbased and non-userbased cache when reading from cache
        $this->cleanupCacheFiles($this->strCacheFilename, $pageId, $userbased);

        if (count($headers)) {
            foreach ($headers as &$header) {
                $header = (string) $header;
            }
            $handleFile = $this->strCachePath . $this->strCacheFilename . '_h' . $pageId . $user;
            $File = new \Cx\Lib\FileSystem\File($handleFile);
            $File->write(serialize($headers));
        }
        \DBG::log('Writing cache file "' . $this->strCacheFilename . '_' . $pageId . $user . ' for request info:');
        \DBG::dump($this->arrPageContent);
        // write page cache file
        $handleFile = $this->strCachePath . $this->strCacheFilename . '_' . $pageId . $user;
        $File = new \Cx\Lib\FileSystem\File($handleFile);
        $File->write($endcode);
    }

    /**
     * Removes all page cache files for a request
     * @param string $filename Request hash (as in $this->strCacheFilename
     * @param int $pageId Page ID
     * @param boolean $userbased True if the current request is userbased, false otherwise
     */
    protected function cleanupCacheFiles($filename, $pageId, $userbased) {
        if ($userbased) {
            $cacheFileSuffixes = array(
                '', // no session, not userbased
                '_u', // session, not userbased
            );
        } else {
            $cacheFileSuffixes = array(
                '_u0', // no session, userbased
            );
            if (isset($_COOKIE[session_name()])) {
                $cacheFileSuffixes[] = '_u' . $_COOKIE[session_name()]; // session, userbased
            }
        }
        $cacheFileNames = array();
        foreach ($cacheFileSuffixes as $cacheFileSuffix) {
            $cacheFileNames[] = $this->strCachePath . $filename . '_' . $pageId . $cacheFileSuffix;
            $cacheFileNames[] = $this->strCachePath . $filename . '_h' . $pageId . $cacheFileSuffix;
        }
        foreach ($cacheFileNames as $cacheFileName) {
            if (!file_exists($cacheFileName)) {
                continue;
            }
            $file = new \Cx\Lib\FileSystem\File($cacheFileName);
            $file->delete();
        }
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
        
        // Replace include tags
        $settings = $this->getSettings();
        $replaceEsiFn = function($matches) use (&$cxNotYetInitialized, $settings) {
            // return cached content if available
            $cacheFile = $this->getCacheFileNameFromUrl(
                $matches[1],
                $this->currentUrl
            );
            if ($settings['internalSsiCache'] == 'on' && file_exists($this->strCachePath . $cacheFile)) {
                $expireTimestamp = -1;
                if (file_exists($this->strCachePath . $cacheFile . '_h')) {
                    $expireTimestamp = file_get_contents(
                        $this->strCachePath . $cacheFile . '_h'
                    );
                }

                if (
                    (
                        $expireTimestamp >= 0 && $expireTimestamp > time()
                    ) ||
                    (
                        $expireTimestamp < 0 && filemtime(
                            $this->strCachePath . $cacheFile
                        ) > (
                            time() - $this->intCachingTime
                        )
                    )
                ) {
                    \DBG::dump($matches[1]);
                    \DBG::dump($cacheFile);
                    return file_get_contents($this->strCachePath . $cacheFile);
                } else {
                    \DBG::msg('Drop expired cached file ' . $this->strCachePath . $cacheFile);
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

            // TODO: Somehow FRONTEND_LANG_ID is sometimes undefined here...
            $esiUrl = new \Cx\Lib\Net\Model\Entity\Url($matches[1]);
            $langId = \FWLanguage::getLanguageIdByCode($esiUrl->getParam('lang'));
            if (!defined('FRONTEND_LANG_ID')) {
                define('FRONTEND_LANG_ID', $langId);
            }
            if (!defined('BACKEND_LANG_ID')) {
                define('BACKEND_LANG_ID', $langId);
            }
            if (!defined('LANG_ID')) {
                define('LANG_ID', $langId);
            }

            try {
                $content = $this->getApiResponseForUrl($matches[1]);

                if ($settings['internalSsiCache'] == 'on') {
                    // back-replace ESI variables that are url encoded
                    foreach ($this->dynVars as $groupName=>$vars) {
                        if (is_callable($vars)) {
                            $esiPlaceholder = '$(' . $groupName . ')';
                            $content = str_replace(urlencode($esiPlaceholder), $esiPlaceholder, $content);
                        } else {
                            foreach ($vars as $varName=>$url) {
                                $esiPlaceholder = '$(' . $groupName . '{\'' . $varName . '\'})';
                                $content = str_replace(urlencode($esiPlaceholder), $esiPlaceholder, $content);
                            }
                        }
                    }

                    $file = new \Cx\Lib\FileSystem\File($this->strCachePath . $cacheFile);
                    $file->write($content);
                }
            } catch (\Exception $e) {
                $content = '';
            }

            return $content;
        };

        do {
            // apply ESI dynamic variables
            foreach ($this->dynVars as $groupName => $var) {
                if (is_callable($var)) {
                    $esiPlaceholder = '$(' . $groupName . ')';
                    if (strpos($htmlCode, $esiPlaceholder) === false) {
                        continue;
                    }
                    $varValue = $var();
                    $htmlCode = str_replace($esiPlaceholder, $varValue, $htmlCode);
                } else {
                    foreach ($var as $varName => $callback) {
                        $esiPlaceholder = '$(' . $groupName . '{\'' . $varName . '\'})';
                        if (strpos($htmlCode, $esiPlaceholder) === false) {
                            continue;
                        }
                        $varValue = $callback();
                        $htmlCode = str_replace($esiPlaceholder, $varValue, $htmlCode);
                    }
                }
            }

            // Random include tags
            $htmlCode = preg_replace_callback(
                '#<!-- ESI_RANDOM_START -->[\s\S]*<esi:assign name="content_list">\s*\[([^\]]+)\]\s*</esi:assign>[\s\S]*<!-- ESI_RANDOM_END -->#U',
                function($matches) {
                    $uris = explode('\',\'', substr($matches[1], 1, -1));
                    $randomNumber = rand(0, count($uris) - 1);
                    $uri = $uris[$randomNumber];
                    
                    // this needs to match the format below!
                    return '<esi:include src="' . $uri . '" onerror="continue"/>';
                },
                $htmlCode
            );

            $htmlCode = preg_replace_callback(
                '#<esi:include src="([^"]+)" onerror="continue"/>#',
                $replaceEsiFn,
                $htmlCode,
                -1,
                $count
            );
            // repeat replacement to recursively parse ESI-tags 
        } while ($count);

        return $htmlCode;
    }

    /**
     * This parses the list of exceptions (defined in endContrexxCaching()),
     * which will not be cached. Each entry can be:
     * - A component name, this will stop caching for the whole component
     * - An array with the component name as key and a list of conditions as
     *   value. In that case, the sub-conditions can either be a cmd of the
     *   component or a callback (function($page) {}) which returns true
     *   if the exception matches and false otherwise.
     * - A callback (function($cx, $page) {}) which returns true if the
     *   exception matches and false otherwise.
     * @param \Cx\Core\ContentManager\Model\Entity\Page $cx Current page
     * @param \Cx\Core\Core\Controller\Cx Current Cx instance
     * @return boolean True if current request matches an exception, false otherwise
     */
    public function isException($page, $cx)
    {
        foreach ($this->exceptions as $componentName=>$conditions) {
            // find the correct component name
            if (is_numeric($componentName)) {
                if (is_callable($conditions) && $conditions($cx, $page)) {
                    // callback exception matches: do not cache:
                    return true;
                }
                if (!is_string($conditions)) {
                    continue;
                }
                $componentName = $conditions;
            }
            // since we're looking for component exceptions, we only have to check
            // if we have a component page:
            if (
                !$page ||
                $page->getType() != \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION
            ) {
                continue;
            }
            // check if the component name matches
            if ($page->getModule() != $componentName) {
                continue;
            }
            // if we have sub-conditions, we need to check them as well:
            if (is_array($conditions)) {
                $match = false;
                foreach ($conditions as $condition) {
                    // sub-condition can be a CMD or a callback
                    if (
                        (
                            is_callable($condition) && $condition($page)
                        ) ||
                        (
                            $page->getCmd() == $condition
                        )
                    ) {
                        // sub-condition matches: do not cache
                        $match = true;
                        break;
                    }
                }
                // no sub-condition has matched, jump to next exception
                if (!$match) {
                    continue;
                }
            }
            // exception has matched (including sub-conditions, if any): do not cache
            return true;
        }
        return false;
    }

    /**
     * Delete all cache files from tmp directory
     */
    public function cleanContrexxCaching()
    {
        $this->_deleteAllFiles();
    }

    /**
     * Forces page cache to be stored per user
     */
    public function forceUserbasedPageCache() {
        $this->forceUserbased = true;
    }
}

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
    const HTTP_STATUS_CODE_HEADER = 'X-StatusCode';
    var $boolIsEnabled = false; //Caching enabled?

    var $strCachePath; //Path to cache-directory
    var $strCacheFilename; //Name of the current cache-file

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

    /**
     * @see ComponentController::forceUserbasedPageCache()
     */
    public function addException($componentOrCallback, $additionalInfo = array()) {
        if (is_string($componentOrCallback) && count($additionalInfo)) {
            $this->exceptions[$componentOrCallback] = $additionalInfo;
        } else {
            $this->exceptions[] = $componentOrCallback;
        }
    }

    protected function initContrexxCaching()
    {
        global $_CONFIG;

        if ($_CONFIG['cacheEnabled'] == 'off') {
            $this->boolIsEnabled = false;
            return;
        }

        if (isset($_REQUEST['caching']) && $_REQUEST['caching'] == '0') {
            $this->boolIsEnabled = false;
            return;
        }

        // @todo: Solve using ComponentController::addException()
        if (isset($_GET['templateEditor']) && $_GET['templateEditor'] == 1) {
            $this->boolIsEnabled = false;
            return;
        }

        // @todo: Solve using ComponentController::addException()
        if (isset($_GET['pagePreview'])) {
            $this->boolIsEnabled = false;
            return;
        }

        // Since FE does not yet support caching, we disable it when FE is active
        // @todo: Solve using ComponentController::addException()
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

        $this->initRequestInfo();

        $this->strCacheFilename = md5(serialize($this->arrPageContent));
    }

    /**
     * Start caching functions. If this page is already cached, load it, otherwise create new file
     */
    public function startContrexxCaching($cx)
    {
        if (!$this->boolIsEnabled) {
            return null;
        }
        $files = glob($this->strCachePath . static::CACHE_DIRECTORY_OFFSET_PAGE . $this->strCacheFilename . "*");

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

        // load headers
        $matches = array();
        preg_match($cacheFileRegex, $file, $matches);
        // @todo: Make header cache user based

        // $matches[2] is not set if the following conditions are all true:
        // 1. We have no session
        // 2. Request is not user-based
        // 3. We have no page (request to without URI-Slug, for example: /de/)
        if (!isset($matches[2])) {
            $matches[2] = '';
        }

        $headerFile = $this->strCachePath . static::CACHE_DIRECTORY_OFFSET_PAGE . $matches[1] . '_h' . $matches[2];

        if (filemtime($file) > (time() - $this->intCachingTime)) {
            if (file_exists($headerFile)) {
                $headers = unserialize(file_get_contents($headerFile));
                if (is_array($headers)) {
                    foreach ($headers as $name=>$value) {
                        if ($name == static::HTTP_STATUS_CODE_HEADER) {
                            http_response_code(intval($value));
                            continue;
                        }
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
                                try {
                                    $headerFile = new \Cx\Lib\FileSystem\File(
                                        $headerFile
                                    );
                                    $headerFile->delete();
                                    $file = new \Cx\Lib\FileSystem\File($file);
                                    $file->delete();
                                } catch (\Throwable $e) {}
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

            \DBG::writeFinishLine($cx, true);
            exit;
        } else {
            try {
                if (file_exists($headerFile)) {
                    $headerFile = new \Cx\Lib\FileSystem\File($headerFile);
                    $headerFile->delete();
                }
                $file = new \Cx\Lib\FileSystem\File($file);
                $file->delete();
            } catch (\Throwable $e) {}
        }
    }

    /**
     * End caching functions. Check for a sessionId: if not set, write pagecontent to a file.
     */
    public function endContrexxCaching($page, $endcode)
    {
        $this->initEsiDynVars();
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
        
        $this->exceptions += array(
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
            'Login' => array(
                function($page) {
                    return $_SERVER['REQUEST_METHOD'] === 'POST';
                },
            ),
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
            'Podcast',
            'Shop',
            'Survey',
            'U2u',
            'Voting',
        );

        if ($this->isException($page, $cx)) {
            return $this->internalEsiParsing($endcode);
        }

        $this->setCachedLocaleData($cx);

        // write header cache file
        $resolver = \Env::get('Resolver');
        $headers = $resolver->getHeaders();
        $httpStatusCode = http_response_code();
        if (is_int(http_response_code()) && $httpStatusCode != 200) {
            $headers[static::HTTP_STATUS_CODE_HEADER] = $httpStatusCode;
        }
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
            $handleFile = $this->strCachePath . static::CACHE_DIRECTORY_OFFSET_PAGE . $this->strCacheFilename . '_h' . $pageId . $user;
            $File = new \Cx\Lib\FileSystem\File($handleFile);
            $File->write(serialize($headers));
        }
        \DBG::log('Writing cache file "' . $this->strCacheFilename . '_' . $pageId . $user . ' for request info:');
        \DBG::dump($this->arrPageContent);
        // write page cache file
        $handleFile = $this->strCachePath . static::CACHE_DIRECTORY_OFFSET_PAGE . $this->strCacheFilename . '_' . $pageId . $user;
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
            $cacheFileNames[] = $this->strCachePath . static::CACHE_DIRECTORY_OFFSET_PAGE . $filename . '_' . $pageId . $cacheFileSuffix;
            $cacheFileNames[] = $this->strCachePath . static::CACHE_DIRECTORY_OFFSET_PAGE . $filename . '_h' . $pageId . $cacheFileSuffix;
        }
        foreach ($cacheFileNames as $cacheFileName) {
            if (!file_exists($cacheFileName)) {
                continue;
            }
            try {
                $file = new \Cx\Lib\FileSystem\File($cacheFileName);
                $file->delete();
            } catch (\Throwable $e) {}
        }
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
     * Forces page cache to be stored per user
     */
    public function forceUserbasedPageCache() {
        $this->forceUserbased = true;
    }
}

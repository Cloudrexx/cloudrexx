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
        $this->initOPCaching();
        $this->initUserCaching();
        $this->getActivatedCacheEngines();
    }

    protected function initContrexxCaching()
    {
        global $_CONFIG;

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

        $this->boolIsEnabled = true;

        // check the cache directory
        if (!is_dir(ASCMS_CACHE_PATH)) {
            \Cx\Lib\FileSystem\FileSystem::make_folder(ASCMS_CACHE_PATH);
        }
        if (!is_writable(ASCMS_CACHE_PATH)) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable(ASCMS_CACHE_PATH);
        }
        $this->strCachePath = ASCMS_CACHE_PATH . '/';

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
                readfile($file);
                exit;
            } else {
                $File = new \Cx\Lib\FileSystem\File($file);
                $File->delete();
            }
        }

        //if there is no cached file, start recording
        ob_start();
    }


    /**
     * End caching functions. Check for a sessionId: if not set, write pagecontent to a file.
     */
    public function endContrexxCaching($page)
    {
        if (!$this->boolIsEnabled) {
            return null;
        }
        if (session_id() != '' && \FWUser::getFWUserObject()->objUser->login()) {
            return null;
        }
        if (!$page->getCaching()) {
            return null;
        }

        $strCacheContents = ob_get_contents();
        ob_end_flush();
        $handleFile = $this->strCachePath . $this->strCacheFilename . "_" . $page->getId();
        $File = new \Cx\Lib\FileSystem\File($handleFile);
        $File->write($strCacheContents);
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

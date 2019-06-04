<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2016
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
 * Main controller for Cache
 *
 * @copyright   Cloudrexx AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */

namespace Cx\Core_Modules\Cache\Controller;

/**
 * Main controller for Cache
 *
 * @copyright   Cloudrexx AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * cache instance (can also be set to \Cx\Core_Modules\Cache\Controller\Cache, check preInit())
     * @var \Cx\Core_Modules\Cache\Controller\CacheLib
     */
    protected $cache;

    /**
     * doctrine cache driver instance
     * @var mixed
     */
    protected $cacheDriver;

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Since this comoponent only has this controller, this returns an empty array
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Start caching with op cache, user cache and cloudrexx caching (cloudrexx caching in frontend only)
     * @param \Cx\Core\Core\Controller\Cx $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function preInit(\Cx\Core\Core\Controller\Cx $cx) {
        global $argv;

        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            $this->cache = new \Cx\Core_Modules\Cache\Controller\Cache();
        } else { // load CacheLib for other modes than frontend
            //- ATTENTION: never load CacheManager here, because it uses not yet defined constants which will cause a fatal error
            $this->cache = new \Cx\Core_Modules\Cache\Controller\CacheLib();
        }
        // disable user cache when calling Cache command from CLI
        if (
            $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_COMMAND &&
            php_sapi_name() == 'cli' &&
            isset($argv) &&
            count($argv) > 2 &&
            $argv[1] == 'Cache'
        ) {
            // do not activate db cache
            $this->cacheDriver = new \Doctrine\Common\Cache\ArrayCache();
        } else {
            $this->cacheDriver = $this->cache->getDoctrineCacheDriver();
        }
        if ($this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            $this->cache->deactivateNotUsedOpCaches();
        } elseif (!isset($_GET['cmd']) || $_GET['cmd'] != 'settings') {
            $this->cache->deactivateNotUsedOpCaches();
        }
        if (
            $this->cache->getUserCacheEngine() == \Cx\Core_Modules\Cache\Controller\Cache::CACHE_ENGINE_APC ||
            $this->cache->getOpCacheEngine() == \Cx\Core_Modules\Cache\Controller\Cache::CACHE_ENGINE_APC
        ) { // when using apc the memory limit can be reduced to 32M to save RAM
            $this->cx->setMemoryLimit(32);
        }
        // start cloudrexx caching
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        $this->cache->startContrexxCaching($this->cx);
    }

    /**
     * Register events
     */
    public function registerEvents()
    {
        $this->cx->getEvents()->addEvent('clearEsiCache');
    }

    /**
     * Registers event listeners
     */
    public function registerEventListeners() {
        $evm = $this->cx->getEvents();
        $evm->addModelListener(
            'postFlush',
            'Cx\Core\Routing\Model\Entity\RewriteRule',
            new \Cx\Core_Modules\Cache\Model\Event\RewriteRuleEventListener(
                $this->cx
            )
        );

        // TODO: This is a workaround for Doctrine's result query cache.
        //       Proper handling of ResultCache must be implemented.
        $evm->addModelListener(
            'postFlush',
            'Cx\Core\Core\Model\Entity\EntityBase',
            new \Cx\Core_Modules\Cache\Model\Event\CoreEntityBaseEventListener(
                $this->cx
            )
        );
        $evm->addModelListener(
            'postFlush',
            'Cx\Core\Locale\Model\Entity\Locale',
            new \Cx\Core_Modules\Cache\Model\Event\LocaleChangeListener(
                $this->cx
            )
        );
    }

    /**
     * Ends the contrexx caching after the main template got parsed (frontend only)
     * @param string $endcode The processed data to be sent to the client as response
     */
    public function postFinalize(&$endcode) {
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        $page = $this->cx->getPage();
        if (!$page) {
            $page = \Env::get('Page');
        }
        $endcode = $this->cache->endContrexxCaching($page, $endcode);
    }

    /**
     * Wrapper to fetch an item from cache using the doctrine usercache cachedriver
     * @param string $id Id of the item
     * @return mixed     The item
     */
    public function fetch($id) {
        return $this->cacheDriver->fetch($id);
    }

    /**
     * Wrapper to save an item to cache using the doctrine user usercache cachedriver
     * @param string $id Id of the item
     * @param mixed $data data of the item
     * @param int $lifeTime Expiraton time of the item (if it equals zero, the item never expires)
     */
    public function save($id, $data, $lifeTime = 0) {
        $this->cacheDriver->save($id, $data, $lifeTime);
    }

    /**
     * Wrapper to delete an item from cache using the doctrine usercache cache driver
     * @param string $id Id of the item
     */
    public function delete($id) {
        $this->cacheDriver->delete($id);
    }

    /**
     * Wrapper to flush all cache instances
     * @param \Doctrine\Common\Cache\AbstractCache $cacheEngine The doctrine cache engine
     */
    public function clearCache($cacheEngine = null) {
        $this->cache->clearCache($cacheEngine);
    }

    /**
     * Wrapper to drop a cache page on reverse proxy cache
     * @param string $urlPatter URL pattern to drop on reverse cache proxy
     */
    public function clearReverseProxyCache($urlPattern) {
        $this->cache->clearReverseProxyCache($urlPattern);
    }

    /**
     * Wrapper to drop all cached ESI/SSI elements
     */
    public function clearSsiCache($urlPattern = '') {
        $this->cache->clearSsiCache($urlPattern);
    }

    /**
     * Wrapper to drop the ESI cache for a specific call
     * @param string $adapterName (Json)Data adapter name
     * @param string $adapterMethod (Json)Data method name
     * @param array $params (optional) params for (Json)Data method call
     */
    public function clearSsiCachePage($adapterName, $adapterMethod, $params = array()) {
        $this->cache->clearSsiCachePage($adapterName, $adapterMethod, $params);
    }

    /**
     * Wrapper to get randomizedEsiContent
     *
     * Each entry of $esiContentInfos consists of an array like:
     * array(
     *     <adapterName>,
     *     <adapterMethod>,
     *     <params>,
     * )
     * @param array $esiContentInfos List of ESI content info arrays
     * @param int $count (optional) Number of unique random entries to parse
     * @return string ESI randomized include code
     */
    public function getRandomizedEsiContent($esiContentInfos, $count = 1) {
        return $this->cache->getRandomizedEsiContent($esiContentInfos, $count);
    }

    /**
     * Wrapper to return the ESI/SSI content for a (json)data call
     * @param string $adapterName (Json)Data adapter name
     * @param string $adapterMethod (Json)Data method name
     * @param array $params (optional) params for (Json)Data method call
     * @return string ESI/SSI directives to put into HTML code
     */
    public function getEsiContent($adapterName, $adapterMethod, $params = array()) {
        return $this->cache->getEsiContent($adapterName, $adapterMethod, $params);
    }

    /**
     * Delete all cached file's of the cache system
     * @param object $cacheEngine The user cache engine
     */
    function deleteAllFiles($cacheEngine = null) {
        $this->cache->_deleteAllFiles($cacheEngine);
    }

    /**
     * Deletes a file from page cache
     * @param int $pageId ID of the page to drop cache of
     */
    public function deleteSingleFile($pageId) {
        $this->cache->deleteSingleFile($pageId);
    }

    /**
     * Delete all cached files for a component from cache-folder
     */
    function deleteComponentFiles($componentName) {
        $this->cache->deleteComponentFiles($componentName);
    }

    /**
     * Deletes cached header redirects
     */
    function deleteNonPagePageCache() {
        $this->cache->deleteNonPagePageCache();
    }

    /**
     * Clear user based page cache of a specific user identified by its
     * session ID.
     *
     * @param   string  $sessionId  The session ID of the user of whom
     *                              to clear the page cache from.
     */
    public function clearUserBasedPageCache($sessionId) {
        $this->cache->clearUserBasedPageCache($sessionId);
    }

    /**
     * Clear user based ESI cache of a specific user identified by its
     * session ID.
     *
     * @param   string  $sessionId  The session ID of the user of whom
     *                              to clear the esi cache from.
     */
    public function clearUserBasedEsiCache($sessionId) {
        $this->cache->clearUserBasedEsiCache($sessionId);
    }

    /**
     * @return \Doctrine\Common\Cache\AbstractCache The doctrine cache driver object
     */
    public function getCacheDriver()
    {
        return $this->cacheDriver;
    }

    /**
     * Set the cache driver to use
     *
     * @param   $driver \Doctrine\Common\Cache\AbstractCache The doctrine cache driver object
     */
    public function setCacheDriver($driver)
    {
        $this->cacheDriver = $driver;
    }

    /**
     * Returns the validated file search parts of the URL
     * @param string $url URL to parse
     * @param string $originalUrl URL of the page that ESI is parsed for
     * @return array <fileNamePrefix>=><parsedValue> type array
     */
    public function getCacheFileNameSearchPartsFromUrl($urlPattern, $originalUrl) {
        return $this->cache->getCacheFileNameSearchPartsFromUrl($urlPattern, $originalUrl);
    }

    /**
     * Gets the local cache file name for an URL
     * @param string $url URL to get file name for
     * @param string $originalUrl URL of the page that ESI is parsed for
     * @param boolean $withCacheInfoPart (optional) Adds info part (default true)
     * @return string File name (without path)
     */
    public function getCacheFileNameFromUrl($url, $originalUrl, $withCacheInfoPart = true) {
        return $this->cache->getCacheFileNameFromUrl($url, $originalUrl, $withCacheInfoPart);
    }

    /**
     * Parses ESI directives internally if configured to do so
     * @param string $htmlCode HTML code to replace ESI directives in
     * @return string Parsed HTML code
     */
    public function internalEsiParsing($htmlCode) {
        return $this->cache->internalEsiParsing($htmlCode);
    }

    /**
     * Writes the cache file for the current request
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Current page (might be null for redirects before postResolve)
     * @param array $headers List of headers set for the current response
     * @param string $endcode Current response
     */
    public function writeCacheFileForRequest($page, $headers, $endcode) {
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        $this->cache->writeCacheFileForRequest($page, $headers, $endcode);
    }

    /**
     * Returns a list of command mode commands provided by this component
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array(
            'Cache' => new \Cx\Core_Modules\Access\Model\Entity\Permission(
                array(),
                array('cli'),
                false
            ),
        );
    }

    /**
     * Returns the description for a command provided by this component
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        if ($command != 'Cache') {
            return '';
        }
        if ($short) {
            return 'Allows to clear caches';
        }
        return 'Cache clear user [<engine>]
Cache clear page [<pageId>]
Cache clear (esi|proxy) [<urlPattern>]
Cache clear opcode [<engine>]
Cache clear all';
    }

    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     * @see getCommandsForCommandMode()
     * @param string $command Name of command to execute
     * @param array $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     * @return void
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {
        switch ($command) {
            case 'Cache':
                if (count($arguments) < 2) {
                    echo 'Not enough arguments' . "\n";
                    return;
                }
                switch (array_shift($arguments)) {
                    case 'clear':
                        $type = array_shift($arguments);
                        $options = '';
                        if (count($arguments)) {
                            $options = array_shift($arguments);
                        }
                        $this->clearCacheCommand($type, $options);
                        break;
                    default:
                        echo 'No such command' . "\n";
                        break;
                }
                break;
        }
    }

    /**
     * Clears the selected type of cache.
     *
     * Known cache types are:
     * - user: DB cache
     * - page: "Contrexx cache"
     * - esi: ESI/SSI cache (internal or on proxy)
     * - proxy: Reverse proxy cache
     * - opcode: PHP OP code cache
     * - all: Drop all of the above
     * @param string $type Cache type to clear
     * @param string $options (optional) Engine for user or opcode cache, filter for page, esi and reverse proxy cache
     */
    protected function clearCacheCommand($type, $options = '') {
        $types = array('user', 'page', 'esi', 'proxy', 'opcode');
        if ($type == 'all') {
            $this->clearCache();
            $this->clearCache(CacheLib::CACHE_ENGINE_MEMCACHED);
            return;
        }
        if (!in_array($type, $types)) {
            echo 'Unknown cache type' . "\n";
            return;
        }
        switch ($type) {
            case 'user':
                if (!empty($options)) {
                    if (!in_array(
                        $options,
                        array(
                            CacheLib::CACHE_ENGINE_APC,
                            CacheLib::CACHE_ENGINE_MEMCACHE,
                            CacheLib::CACHE_ENGINE_MEMCACHED,
                            CacheLib::CACHE_ENGINE_XCACHE,
                        )
                    )) {
                        echo 'Unknown cache engine' . "\n";
                        return;
                    }
                    if ($options == CacheLib::CACHE_ENGINE_MEMCACHED) {
                        if (!extension_loaded('memcached')) {
                            dl('memcached');
                        }
                    }
                    $this->cache->forceClearCache($options);
                    break;
                }
                $this->cache->_deleteAllFiles();
                break;
            case 'page':
                if (!empty($options)) {
                    $this->cache->deleteSingleFile($options);
                    break;
                }
                $this->cache->_deleteAllFiles('cxPages');
                break;
            case 'esi':
                $this->cache->clearSsiCache();
                break;
            case 'proxy':
                if (empty($options)) {
                    $options = '*';
                }
                $this->cache->clearReverseProxyCache($options);
                break;
            case 'opcode':
                if (!empty($options)) {
                    if (!in_array(
                        $options,
                        array(
                            CacheLib::CACHE_ENGINE_APC,
                            CacheLib::CACHE_ENGINE_ZEND_OPCACHE,
                            CacheLib::CACHE_ENGINE_XCACHE,
                        )
                    )) {
                        echo 'Unknown cache engine' . "\n";
                        return;
                    }
                    $this->cache->_deleteAllFiles($options);
                    break;
                }
                $this->cache->_deleteAllFiles($this->cache->getOpCacheEngine());
                break;
        }
        echo 'Cache cleared' . "\n";
    }

    /**
     * Forces page cache to be stored per user
     */
    public function forceUserbasedPageCache() {
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        $this->cache->forceUserbasedPageCache();
    }

    /**
     * Add an exception that must not get cached
     *
     * Case A: $componentOrCallback is a string, $additionalInfo is an empty array
     * Case B: $componentOrCallback is a string, $additionalInfo is non-empty
     * Case C: $componentOrCallback is a callback, $additionalInfo is an empty array
     * Case D: $componentOrCallback is a callback, $additionalInfo is non-empty
     *
     * Case A will disable caching for all requests to a component.
     * Case B will disable caching for all requests to a component that meet
     * the criteria defined in $additionalInfo.
     * Case C will execute the callback for each not yet cached request. The
     * current Cx instance will be passed to the callback as the first argument.
     * The currently resolved page will be passed to the callback as the second
     * argument. If the callback returns true, the current request will not be
     * cached, otherwise it will.
     * Case D will ignore $additionalInfo and therefore result in case C.
     *
     * The format for $additionalInfo is either a list of CMDs or a single
     * entry which is a callback.
     * The former will not cache requests to any of
     * the listed CMDs for the component specified in $componentOrCallback.
     * The latter will execute the callback for any request to the component
     * specified in $componentOrCallback. The currently resolved page will be
     * passed to the callback as the first argument. If the callback returns
     * true, the current request will not be cached, otherwise it will.
     *
     * If there's already an entry for the component specified in
     * $componentOrCallback one of the following will happen:
     * - $componentOrCallback is a callback or $additionalInfo is empty: The
     *   exception will be blindly added. If at least one of the two (or more)
     *   rules match, the request will not get cached.
     * - $componentOrCallback is a component name and $additionalInfo is non-
     *   empty: If there's a hard-coded entry in $this->exceptions in
     *   static::endContrexxCaching() for the given component the exception
     *   you're trying to add through this method will get overwritten.
     *
     * @param string|Callable $componentOrCallback Component name or callback
     * @param array $additionalInfo (optional) Conditions
     */
    public function addException($componentOrCallback, $additionalInfo = array()) {
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        $this->cache->addException($componentOrCallback, $additionalInfo);
    }

    /**
     * Overwrite the automatically set CachePrefix
     *                          Setting an empty string will reset
     *                          the CachePrefix to its initial value.
     * @param   $prefix String  The new CachePrefix to be used
     */
    public function setCachePrefix($prefix = '') {
        $this->cache->setCachePrefix($prefix);
    }

    /**
     * Sets the cached locale data
     *
     * Default locale and the following hashtables are cached:
     * <localeCode> to <localeId>
     * <localeCountryCode> to <localeCodes>
     * @param \Cx\Core\Core\Controller\Cx $cx Cx instance
     */
    public function setCachedLocaleData($cx) {
        $this->cache->setCachedLocaleData($cx);
    }
}

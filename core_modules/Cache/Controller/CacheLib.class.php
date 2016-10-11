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
 * Class Cache Library
 *
 * Cache Library class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 * @todo        Edit PHP DocBlocks!
 * @todo        Descriptions are wrong. What is it really?
 */
namespace Cx\Core_Modules\Cache\Controller;
/**
 * Class Cache Library
 *
 * Cache Library class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 * @todo        Descriptions are wrong. What is it really?
 */
class CacheLib
{
    var $strCachePath;

    /**
     * Alternative PHP Cache extension
     */
    const CACHE_ENGINE_APC = 'apc';

    /**
     * memcache extension
     */
    const CACHE_ENGINE_MEMCACHE = 'memcache';

    /**
     * memcache(d) extension
     */
    const CACHE_ENGINE_MEMCACHED = 'memcached';

    /**
     * xcache extension
     */
    const CACHE_ENGINE_XCACHE = 'xcache';

    /**
     * zend opcache extension
     */
    const CACHE_ENGINE_ZEND_OPCACHE = 'zendopcache';

    /**
     * file system user cache extension
     */
    const CACHE_ENGINE_FILESYSTEM = 'filesystem';

    /**
     * cache off
     */
    const CACHE_ENGINE_OFF = 'off';

    /**
     * Used op cache engines
     * @var array Cache engine names, empty for none
     */
    protected $opCacheEngines = array();

    /**
     * Used user cache engines
     * @var type array Cache engine names, empty for none
     */
    protected $userCacheEngines = array();

    protected $opCacheEngine = null;
    protected $userCacheEngine = null;
    protected $memcache = null;
    protected $memcached = null;

    /**
     * @var \Doctrine\Common\Cache\AbstractCache doctrine cache engine for the active user cache engine
     */
    protected $doctrineCacheEngine = null;

    /**
     * @var \Cx\Lib\ReverseProxy\Model\Entity\ReverseProxyProxy SSI proxy
     */
    protected $ssiProxy;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initOPCaching();
        $this->initUserCaching();
        $this->getActivatedCacheEngines();
    }

    /**
     * Delete all cached file's of the cache system
     */
    function _deleteAllFiles($cacheEngine = null)
    {
        if (!in_array($cacheEngine, array('cxPages', 'cxEntries'))) {
            $this->getDoctrineCacheDriver()->deleteAll();
            return;
        }
        $handleDir = opendir($this->strCachePath);
        if ($handleDir) {
            while ($strFile = readdir($handleDir)) {
                if ($strFile != '.' && $strFile != '..') {
                    switch ($cacheEngine) {
                        case 'cxPages':
                            if(is_file($this->strCachePath . $strFile)){
                                unlink($this->strCachePath . $strFile);
                            }
                            break;
                        case 'cxEntries':
                            $this->getDoctrineCacheDriver()->deleteAll();
                            break;
                        default:
                            unlink($this->strCachePath . $strFile);
                            break;
                    }
                }
            }
            closedir($handleDir);
        }
    }

    protected function initOPCaching()
    {
        // APC
        if ($this->isInstalled(self::CACHE_ENGINE_APC)) {
            ini_set('apc.enabled', 1);
            if ($this->isActive(self::CACHE_ENGINE_APC)) {
                $this->opCacheEngines[] = self::CACHE_ENGINE_APC;
            }
        }

        // Disable eAccelerator if active
        if (extension_loaded('eaccelerator')) {
            ini_set('eaccelerator.enable', 0);
            ini_set('eaccelerator.optimizer', 0);
        }

        // Disable zend opcache if it is enabled
        // If save_comments is set to TRUE, doctrine2 will not work properly.
        // It is not possible to set a new value for this directive with php.
        if ($this->isInstalled(self::CACHE_ENGINE_ZEND_OPCACHE)) {
            ini_set('opcache.save_comments', 1);
            ini_set('opcache.load_comments', 1);
            @ini_set('opcache.enable', 1);

            if (
                !$this->isActive(self::CACHE_ENGINE_ZEND_OPCACHE) ||
                !$this->isConfigured(self::CACHE_ENGINE_ZEND_OPCACHE)
            ) {
                ini_set('opcache.enable', 0);
            } else {
                $this->opCacheEngines[] = self::CACHE_ENGINE_ZEND_OPCACHE;
            }
        }

        // XCache
        if (
            $this->isInstalled(self::CACHE_ENGINE_XCACHE) &&
            $this->isActive(self::CACHE_ENGINE_XCACHE) &&
            $this->isConfigured(self::CACHE_ENGINE_XCACHE)
        ) {
            $this->opCacheEngines[] = self::CACHE_ENGINE_XCACHE;
        }
    }

    protected function initUserCaching()
    {
        global $_CONFIG;

        // APC
        if ($this->isInstalled(self::CACHE_ENGINE_APC)) {
            // have to use serializer "php", not "default" due to doctrine2 gedmo tree repository
            ini_set('apc.serializer', 'php');
            if (
                $this->isActive(self::CACHE_ENGINE_APC) &&
                $this->isConfigured(self::CACHE_ENGINE_APC, true)
            ) {
                $this->userCacheEngines[] = self::CACHE_ENGINE_APC;
            }
        }

        // Memcache
        if (   $this->isInstalled(self::CACHE_ENGINE_MEMCACHE)
            && (\Env::get('cx')->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND
            || $_CONFIG['cacheUserCache'] == self::CACHE_ENGINE_MEMCACHE)
        ) {
            $memcacheConfiguration = $this->getMemcacheConfiguration();
            unset($this->memcache); // needed for reinitialization
            if (class_exists('\Memcache')) {
                $memcache = new \Memcache();
                if (@$memcache->addServer($memcacheConfiguration['ip'], $memcacheConfiguration['port'])) {
                    $this->memcache = $memcache;
                }
            }
            if ($this->isConfigured(self::CACHE_ENGINE_MEMCACHE)) {
                $this->userCacheEngines[] = self::CACHE_ENGINE_MEMCACHE;
            }
        }

        // Memcached
        if (   $this->isInstalled(self::CACHE_ENGINE_MEMCACHED)
            && (\Env::get('cx')->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND
            || $_CONFIG['cacheUserCache'] == self::CACHE_ENGINE_MEMCACHED)
        ) {
            $memcachedConfiguration = $this->getMemcachedConfiguration();
            unset($this->memcached); // needed for reinitialization
            if (class_exists('\Memcached')) {
                $memcached = new \Memcached();
                if (@$memcached->addServer($memcachedConfiguration['ip'], $memcachedConfiguration['port'])) {
                    $this->memcached = $memcached;
                }
            }
            if ($this->isConfigured(self::CACHE_ENGINE_MEMCACHED)) {
                $this->userCacheEngines[] = self::CACHE_ENGINE_MEMCACHED;
            }
        }

        // XCache
        if (
            $this->isInstalled(self::CACHE_ENGINE_XCACHE) &&
            $this->isActive(self::CACHE_ENGINE_XCACHE) &&
            $this->isConfigured(self::CACHE_ENGINE_XCACHE, true)
        ) {
            $this->userCacheEngines[] = self::CACHE_ENGINE_XCACHE;
        }

        // Filesystem
        if ($this->isConfigured(self::CACHE_ENGINE_FILESYSTEM)) {
            $this->userCacheEngines[] = self::CACHE_ENGINE_FILESYSTEM;
        }
    }

    protected function getActivatedCacheEngines()
    {
        global $_CONFIG;

        $this->userCacheEngine = self::CACHE_ENGINE_OFF;
        if (   isset($_CONFIG['cacheUserCache'])
            && in_array($_CONFIG['cacheUserCache'], $this->userCacheEngines)
        ) {
            $this->userCacheEngine = $_CONFIG['cacheUserCache'];
        }

        $this->opCacheEngine = self::CACHE_ENGINE_OFF;
        if (   isset($_CONFIG['cacheOPCache'])
            && in_array($_CONFIG['cacheOPCache'], $this->opCacheEngines)
        ) {
            $this->opCacheEngine = $_CONFIG['cacheOPCache'];
        }

        // if system is configured for "intern" or not correctly configured
        $proxySettings = $this->getSsiProcessorConfiguration();
        if (
            !isset($_CONFIG['cacheSsiOutput']) ||
            $_CONFIG['cacheSsiOutput'] == 'intern' ||
            !in_array(
                $_CONFIG['cacheSsiOutput'],
                array(
                    'intern',
                    'ssi',
                    'esi',
                )
            ) ||
            !in_array(
                $_CONFIG['cacheSsiType'],
                array(
                    'varnish',
                    'nginx',
                )
            )
        ) {
            $this->ssiProxy = new \Cx\Core_Modules\Cache\Model\Entity\ReverseProxyCloudrexx(
                $proxySettings['ip'],
                $proxySettings['port']
            );
            return;
        }
        $className = '\\Cx\\Lib\\ReverseProxy\\Model\\Entity\\SsiProcessor' . ucfirst($_CONFIG['cacheSsiOutput']);
        $ssiProcessor = new $className();
        $className = '\\Cx\\Lib\\ReverseProxy\\Model\\Entity\\ReverseProxy' . ucfirst($_CONFIG['cacheSsiType']);
        $this->ssiProxy = new $className(
            $proxySettings['ip'],
            $proxySettings['port'],
            $ssiProcessor
        );
    }

    public function deactivateNotUsedOpCaches()
    {
        if (empty($this->opCacheEngine)) {
            $this->getActivatedCacheEngines();
        }
        $opCacheEngine = $this->opCacheEngine;
        if (!$this->getOpCacheActive()) {
            $opCacheEngine = self::CACHE_ENGINE_OFF;
        }

        // deactivate other op cache engines
        foreach ($this->opCacheEngines as $engine) {
            if ($engine != $opCacheEngine) {
                switch ($engine) {
                    case self::CACHE_ENGINE_APC:
                        ini_set('apc.cache_by_default', 0);
                        break;
                    case self::CACHE_ENGINE_ZEND_OPCACHE:
                        ini_set('opcache.enable', 0);
                        break;
                    case self::CACHE_ENGINE_XCACHE:
                        ini_set('xcache.cacher', 0);
                        break;
                }
            }
        }
    }

    public function getUserCacheActive()
    {
        global $_CONFIG;
        return
            isset($_CONFIG['cacheDbStatus'])
            && $_CONFIG['cacheDbStatus'] == 'on';
    }

    public function getOpCacheActive() {
        global $_CONFIG;
        return
            isset($_CONFIG['cacheOpStatus'])
            && $_CONFIG['cacheOpStatus'] == 'on';
    }

    public function getOpCacheEngine() {
        return $this->opCacheEngine;
    }

    public function getUserCacheEngine() {
        return $this->userCacheEngine;
    }

    public function getMemcache() {
        return $this->memcache;
    }

    /**
     * @return \Memcache The memcached object
     */
    public function getMemcached() {
        return $this->memcached;
    }

    public function getAllUserCacheEngines() {
        return array(self::CACHE_ENGINE_APC, self::CACHE_ENGINE_MEMCACHE, self::CACHE_ENGINE_MEMCACHED, self::CACHE_ENGINE_XCACHE);
    }

    public function getAllOpCacheEngines() {
        return array(self::CACHE_ENGINE_APC, self::CACHE_ENGINE_ZEND_OPCACHE);
    }

    /**
     * Returns the current SSI proxy
     * @return \Cx\Lib\ReverseProxy\Model\Entity\ReverseProxy SSI proxy
     */
    public function getSsiProxy() {
        return $this->ssiProxy;
    }

    /**
     * Returns the ESI/SSI content for a (json)data call
     * @param string $adapterName (Json)Data adapter name
     * @param string $adapterMethod (Json)Data method name
     * @param array $params (optional) params for (Json)Data method call
     * @return string ESI/SSI directives to put into HTML code
     */
    public function getEsiContent($adapterName, $adapterMethod, $params = array()) {
        $url = $this->getUrlFromApi($adapterName, $adapterMethod, $params);
        return $this->getSsiProxy()->getSsiProcessor()->getIncludeCode($url->toString());
    }

    /**
     * Each entry of $esiContentInfos consists of an array like:
     * array(
     *     <adapterName>,
     *     <adapterMethod>,
     *     <params>,
     * )
     */
    public function getRandomizedEsiContent($esiContentInfos) {
        $urls = array();
        foreach ($esiContentInfos as $i=>$esiContentInfo) {
            $urls[] = $this->getUrlFromApi($esiContentInfo[0], $esiContentInfo[1], $esiContentInfo[2])->toString();
        }
        return $this->getSsiProxy()->getSsiProcessor()->getRandomizedIncludeCode($urls);
    }

    /**
     * Drops the ESI cache for a specific call
     * @param string $adapterName (Json)Data adapter name
     * @param string $adapterMethod (Json)Data method name
     * @param array $params (optional) params for (Json)Data method call
     * @todo Only drop this specific content instead of complete cache
     */
    public function clearSsiCachePage($adapterName, $adapterMethod, $params = array()) {
        $url = $this->getUrlFromApi($adapterName, $adapterMethod, $params);
        $this->getSsiProxy()->clearCachePage($url->toString(), $this->getDomainsAndPorts());
    }
    
    /**
     * Wrapper for \Cx\Core\Routing\Url::fromApi()
     * This ensures correct param order
     * @param string $adapterName (Json)Data adapter name
     * @param string $adapterMethod (Json)Data method name
     * @param array $params (optional) params for (Json)Data method call
     * @return \Cx\Core\Routing\Url URL for (Json)Data call
     */
    protected function getUrlFromApi($adapterName, $adapterMethod, $params) {
        $url = \Cx\Core\Routing\Url::fromApi('Data', array('Plain', $adapterName, $adapterMethod), $params);
        // make sure params are in correct order:
        $correctIndexOrder = array('page', 'lang', 'user', 'theme', 'country', 'currency');
        $params = $url->getParamArray();
        $params = array_replace(array_flip($correctIndexOrder), $params);
        $url->setParams($params);
        $url->setParam('EOU', '');
        return $url;
    }

    /**
     * Drops all cached ESI/SSI elements
     */
    public function clearSsiCache() {
        $this->getSsiProxy()->clearCache($this->getDomainsAndPorts());
    }

    protected function isInstalled($cacheEngine)
    {
        switch ($cacheEngine) {
            case self::CACHE_ENGINE_APC:
                return extension_loaded('apc');
            case self::CACHE_ENGINE_ZEND_OPCACHE:
                return extension_loaded('opcache') || extension_loaded('Zend OPcache');
            case self::CACHE_ENGINE_MEMCACHE:
                return extension_loaded('memcache');
            case self::CACHE_ENGINE_MEMCACHED:
                return extension_loaded('memcached');
            case self::CACHE_ENGINE_XCACHE:
                return extension_loaded('xcache');
            case self::CACHE_ENGINE_FILESYSTEM:
                return true;
        }
    }

    protected function isActive($cacheEngine)
    {
        if (!$this->isInstalled($cacheEngine)) {
            return false;
        }
        switch ($cacheEngine) {
            case self::CACHE_ENGINE_APC:
                $setting = 'apc.enabled';
                break;
            case self::CACHE_ENGINE_ZEND_OPCACHE:
                $setting = 'opcache.enable';
                break;
            case self::CACHE_ENGINE_MEMCACHE:
                return $this->memcache ? true : false;
            case self::CACHE_ENGINE_MEMCACHED:
                return $this->memcached ? true : false;
            case self::CACHE_ENGINE_XCACHE:
                $setting = 'xcache.cacher';
                break;
            case self::CACHE_ENGINE_FILESYSTEM:
                return true;
        }
        if (!empty($setting)) {
            $configurations = ini_get_all();
            return $configurations[$setting]['global_value'];
        }
    }

    protected function isConfigured($cacheEngine, $user = false)
    {
        if (!$this->isActive($cacheEngine)) {
            return false;
        }
        switch ($cacheEngine) {
            case self::CACHE_ENGINE_APC:
                if ($user) {
                    return ini_get('apc.serializer') == 'php';
                }
                return true;
            case self::CACHE_ENGINE_ZEND_OPCACHE:
                return ini_get('opcache.save_comments') && ini_get('opcache.load_comments');
            case self::CACHE_ENGINE_MEMCACHE:
                return $this->memcache ? true : false;
            case self::CACHE_ENGINE_MEMCACHED:
                return $this->memcached ? true : false;
            case self::CACHE_ENGINE_XCACHE:
                if ($user) {
                    return (
                        ini_get('xcache.var_size') > 0 &&
                        ini_get('xcache.admin.user') &&
                        ini_get('xcache.admin.pass')
                    );
                }
                return ini_get('xcache.size') > 0;
            case self::CACHE_ENGINE_FILESYSTEM:
                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                return is_writable($cx->getWebsiteCachePath());
        }
    }

    protected function getMemcacheConfiguration()
    {
        global $_CONFIG;
        $ip = '127.0.0.1';
        $port = '11211';

        if(!empty($_CONFIG['cacheUserCacheMemcacheConfig'])){
            $settings = json_decode($_CONFIG['cacheUserCacheMemcacheConfig'], true);
            $ip = $settings['ip'];
            $port = $settings['port'];
        }

        return array('ip' => $ip, 'port' => $port);
    }

    protected function getMemcachedConfiguration()
    {
        global $_CONFIG;
        $ip = '127.0.0.1';
        $port = '11211';

        if(!empty($_CONFIG['cacheUserCacheMemcachedConfig'])){
            $settings = json_decode($_CONFIG['cacheUserCacheMemcachedConfig'], true);
            $ip = $settings['ip'];
            $port = $settings['port'];
        }

        return array('ip' => $ip, 'port' => $port);
    }

    /**
     * Gets the configuration value for reverse proxy
     * @return array 'ip' and 'port' of reverse proxy
     */
    protected function getReverseProxyConfiguration()
    {
        global $_CONFIG;
        $ip = '127.0.0.1';
        $port = '8080';

        if (!empty($_CONFIG['cacheProxyCacheConfig'])){
            $settings = json_decode($_CONFIG['cacheProxyCacheConfig'], true);
            $ip = $settings['ip'];
            $port = $settings['port'];
        }

        return array('ip' => $ip, 'port' => $port);
    }

    /**
     * Gets the configuration value for external ESI/SSI processor
     * @return array 'ip' and 'port' of external ESI/SSI processor
     */
    protected function getSsiProcessorConfiguration()
    {
        global $_CONFIG;
        $ip = '127.0.0.1';
        $port = '8080';

        if (!empty($_CONFIG['cacheSsiProcessorConfig'])){
            $settings = json_decode($_CONFIG['cacheSsiProcessorConfig'], true);
            $ip = $settings['ip'];
            $port = $settings['port'];
        }

        return array('ip' => $ip, 'port' => $port);
    }

    /**
     * Flush all cache instances
     * @see \Cx\Core\ContentManager\Model\Event\PageEventListener on update of page objects
     */
    public function clearCache($cacheEngine = null)
    {
        if (!$this->strCachePath) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            if (is_dir($cx->getWebsiteCachePath())) {
                if (is_writable($cx->getWebsiteCachePath())) {
                    $this->strCachePath = $cx->getWebsiteCachePath() . '/';
                }
            }
        }
        if ($cacheEngine === null) {
            // remove cached files
            $this->_deleteAllFiles('cxPages');
        }

        $cacheEngine = $cacheEngine == null ? $this->userCacheEngine : $cacheEngine;
        switch ($cacheEngine) {
            case self::CACHE_ENGINE_APC:
                $this->clearApc();
                break;
            case self::CACHE_ENGINE_MEMCACHE:
                $this->clearMemcache();
                break;
            case self::CACHE_ENGINE_MEMCACHED:
                $this->clearMemcached();
                break;
            case self::CACHE_ENGINE_XCACHE:
                $this->clearXcache();
                break;
            case self::CACHE_ENGINE_ZEND_OPCACHE:
                $this->clearZendOpCache();
                break;
            case self::CACHE_ENGINE_FILESYSTEM:
                $this->_deleteAllFiles();
            default:
                break;
        }

        $this->clearReverseProxyCache('*');
        $this->clearSsiCache();
    }

    /**
     * Drops a cache page on reverse proxy cache
     * @param string $urlPatter URL pattern to drop on reverse cache proxy
     */
    public function clearReverseProxyCache($urlPattern) {
        global $_CONFIG;

        // find rproxy driver
        if (!isset($_CONFIG['cacheReverseProxy']) || $_CONFIG['cacheReverseProxy'] == 'none') {
            return;
        }
        $reverseProxyType = $_CONFIG['cacheReverseProxy'];

        $className = '\\Cx\\Lib\\ReverseProxy\\Model\\Entity\\ReverseProxy' . ucfirst($reverseProxyType);
        $reverseProxyConfiguration = $this->getReverseProxyConfiguration();
        $reverseProxy = new $className(
            $reverseProxyConfiguration['ip'],
            $reverseProxyConfiguration['port']
        );

        // advise driver to drop page for HTTP and HTTPS ports on all domain aliases
        $reverseProxy->clearCachePage($urlPattern, $this->getDomainsAndPorts());
    }

    /**
     * Returns all domains and ports this instance of cloudrexx can be reached at
     * @return array List of domains and ports (array(array(0=>{domain}, 1=>{port})))
     */
    protected function getDomainsAndPorts() {
        $domainsAndPorts = array();
        $domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();
        $domains = $domainRepo->findAll();
        foreach (array('http', 'https') as $protocol) {
            foreach ($domains as $domain) {
                $domainsAndPorts[] = array(
                    $domain->getName(),
                    \Cx\Core\Setting\Controller\Setting::getValue('portFrontend' . strtoupper($protocol), 'Config')
                );
            }
        }
        return $domainsAndPorts;

        $requestDomain = $_CONFIG['domainUrl'];
        $domainOffset  = ASCMS_PATH_OFFSET;

        $request  = "BAN $domainOffset HTTP/1.0\r\n";
        $request .= "Host: $requestDomain\r\n";
        $request .= "User-Agent: Cloudrexx Varnish Cache Clear\r\n";
        $request .= "Connection: Close\r\n\r\n";

        fwrite($varnishSocket, $request);
        fclose($varnishSocket);
    }

    /**
     * Clears APC cache if APC is installed
     */
    private function clearApc()
    {
        if($this->isInstalled(self::CACHE_ENGINE_APC)){
            $apcInfo = \apc_cache_info();
            foreach($apcInfo['entry_list'] as $entry) {
                if(false !== strpos($entry['key'], $this->getCachePrefix()))
                \apc_delete($entry['key']);
            }
            \apc_clear_cache(); // this only deletes the cached files
        }
    }

    /**
     * Clears all Memcachedata related to this Domain if Memcache is installed
     */
    private function clearMemcache()
    {
        if(!$this->isInstalled(self::CACHE_ENGINE_MEMCACHE)){
            return;
        }
        //$this->memcache->flush(); //<- not like this!!!
        $keys = array();
        $allSlabs = $this->memcache->getExtendedStats('slabs');

        foreach ($allSlabs as $server => $slabs) {
            if (is_array($slabs)) {
                foreach (array_keys($slabs) as $slabId) {
                    $dump = $this->memcache->getExtendedStats('cachedump', (int) $slabId);
                    if ($dump) {
                        foreach ($dump as $entries) {
                            if ($entries) {
                                $keys = array_merge($keys, array_keys($entries));
                            }
                        }
                    }
                }
            }
        }
        foreach($keys as $key){
            if(strpos($key, $this->getCachePrefix()) !== false){
                $this->memcache->delete($key);
            }
        }
    }

    /**
     * Clears all Memcacheddata related to this Domain if Memcache is installed
     */
    private function clearMemcached()
    {
        if(!$this->isInstalled(self::CACHE_ENGINE_MEMCACHED)){
            return;
        }
        //$this->memcache->flush(); //<- not like this!!!
        $keys = $this->memcached->getAllKeys();
        foreach($keys as $key){
            if(strpos($key, $this->getCachePrefix()) !== false){
                $this->memcached->delete($key);
            }
        }
    }

    /**
     * Clears XCache if configured. Configuration is needed to clear.
     */
    private function clearXcache()
    {
        if($this->isConfigured(self::CACHE_ENGINE_XCACHE, true)){
            \xcache_clear_cache();
        }
    }

    /**
     * Clears Zend OPCache if installed
     */
    private function clearZendOpCache()
    {
        if($this->isInstalled(self::CACHE_ENGINE_ZEND_OPCACHE)){
            \opcache_reset();
        }
    }

    /**
     * Retunrns the CachePrefix related to this Domain
     * @global string $_DBCONFIG
     * @return string CachePrefix
     */
    protected function getCachePrefix()
    {
        global $_DBCONFIG;
        return $_DBCONFIG['database'].'.'.$_DBCONFIG['tablePrefix'];
    }

    /**
     * Detects the correct doctrine cache driver for the user caching engine in use
     * @return \Doctrine\Common\Cache\AbstractCache The doctrine cache driver object
     */
    public function getDoctrineCacheDriver() {
        if($this->doctrineCacheEngine) { // return cache engine if already set
            return $this->doctrineCacheEngine;
        }
        $userCacheEngine = $this->getUserCacheEngine();
        // check if user caching is active
        if (!$this->getUserCacheActive()) {
            $userCacheEngine = \Cx\Core_Modules\Cache\Controller\Cache::CACHE_ENGINE_OFF;
        }
        switch ($userCacheEngine) {
            case \Cx\Core_Modules\Cache\Controller\Cache::CACHE_ENGINE_APC:
                $cache = new \Doctrine\Common\Cache\ApcCache();
                $cache->setNamespace($this->getCachePrefix());
                break;
            case \Cx\Core_Modules\Cache\Controller\Cache::CACHE_ENGINE_MEMCACHE:
                $memcache = $this->getMemcache();
                $cache = new \Doctrine\Common\Cache\MemcacheCache();
                $cache->setMemcache($memcache);
                $cache->setNamespace($this->getCachePrefix());
                break;
            case \Cx\Core_Modules\Cache\Controller\Cache::CACHE_ENGINE_MEMCACHED:
                $memcached = $this->getMemcached();
                $cache = new \Doctrine\Common\Cache\MemcachedCache();
                $cache->setMemcached($memcached);
                $cache->setNamespace($this->getCachePrefix());
                break;
            case \Cx\Core_Modules\Cache\Controller\Cache::CACHE_ENGINE_XCACHE:
                $cache = new \Doctrine\Common\Cache\XcacheCache();
                $cache->setNamespace($this->getCachePrefix());
                break;
            case \Cx\Core_Modules\Cache\Controller\Cache::CACHE_ENGINE_FILESYSTEM:
                $cache = new \Cx\Core_Modules\Cache\Controller\Doctrine\CacheDriver\FileSystemCache($this->strCachePath);
                break;
            default:
                $cache = new \Doctrine\Common\Cache\ArrayCache();
                break;
        }
        // set the doctrine cache engine to avoid getting it a second time
        $this->doctrineCacheEngine = $cache;
        return $cache;
    }

    /**
     * Creates an array containing all important cache-settings
     *
     * @global     object    $objDatabase
     * @return    array    $arrSettings
     */
    function getSettings() {
        $arrSettings = array();
        \Cx\Core\Setting\Controller\Setting::init('Config', NULL,'Yaml');
        $ymlArray = \Cx\Core\Setting\Controller\Setting::getArray('Config', null);

        foreach ($ymlArray as $key => $ymlValue){
            $arrSettings[$key] = $ymlValue['value'];
        }

        return $arrSettings;
    }

    /**
     * Returns the validated file search parts of the URL
     * @param string $url URL to parse
     * @return array <fileNamePrefix>=><parsedValue> type array
     */
    public function getCacheFileNameSearchPartsFromUrl($url) {
        $url = new \Cx\Lib\Net\Model\Entity\Url($url);
        $params = $url->getParsedQuery();
        $searchParams = array(
            'p' => 'page',
            'l' => 'lang',
            'u' => 'user',
            't' => 'theme',
            'g' => 'country',
            'c' => 'currency',
        );
        $fileNameSearchParts = array();
        foreach ($searchParams as $short=>$long) {
            if (!isset($params[$long])) {
                continue;
            }
            // security: abort if any mystirius characters are found
            if (!preg_match('/^[a-zA-Z0-9-]+$/', $params[$long])) {
                return array();
            }
            $fileNameSearchParts[$short] = '_' . $short . $params[$long];
        }
        return $fileNameSearchParts;
    }

    /**
     * Gets the local cache file name for an URL
     * @param string $url URL to get file name for
     * @return string File name
     */
    public function getCacheFileNameFromUrl($url) {
        $fileName = md5($url);
        return $fileName . implode('', $this->getCacheFileNameSearchPartsFromUrl($url));
    }

    /**
     * Delete all specific file from cache-folder
     */
    function deleteSingleFile($intPageId) {
        $intPageId = intval($intPageId);
        if ( 0 < $intPageId ) {
            $files = glob( $this->strCachePath . '*_' . $intPageId );
            if ( count( $files ) ) {
                foreach ( $files as $file ) {
                    @unlink( $file );
                }
            }
        }
    }

    /**
     * Delete all cached files for a component from cache-folder
     */
    function deleteComponentFiles($componentName)
    {
        $pages = array();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        // get all application pages
        $applicationPages = $pageRepo->findBy(array(
            'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
            'module' => $componentName,
        ));
        foreach ($applicationPages as $page) {
            $pages[$page->getId()] = $page;
            // get all fallbacks to them
            // get all symlinks to them
            $pages += $this->getPagesPointingTo($page);
        }
        // foreach of the above
        foreach ($pages as $pageId=>$page) {
            $this->deleteSingleFile($pageId);
        }
    }
    
    /**
     * Generates a list of pages pointing to $page
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page to get referencing pages for
     * @param array $subPages (optional, by reference) Do not use, internal
     * @return array List of pages (ID as key, page object as value)
     */
    protected function getPagesPointingTo($page, &$subPages = array()) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $fallback_lang_codes = \FWLanguage::getFallbackLanguageArray();
        $active_langs = \FWLanguage::getActiveFrontendLanguages();

        // get all active languages and their fallbacks
        // $fallbacks[<langId>] = <fallsBackToLangId>
        // if <langId> has no fallback <fallsBackToLangId> will be null
        $fallbacks = array();
        foreach ($active_langs as $lang) {
            $fallbacks[\FWLanguage::getLanguageCodeById($lang['id'])] = ((array_key_exists($lang['id'], $fallback_lang_codes)) ? \FWLanguage::getLanguageCodeById($fallback_lang_codes[$lang['id']]) : null);
        }

        // get all symlinks and fallbacks to it
        $query = '
            SELECT
                p
            FROM
                Cx\Core\ContentManager\Model\Entity\Page p
            WHERE
                (
                    p.type = "symlink" AND
                    (
                        p.target LIKE "%NODE_' . $page->getNode()->getId() . '%"';
        if ($page->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION) {
            $query .= ' OR
                        p.target LIKE "%NODE_' . strtoupper($page->getModule()) . '%"';
        }
        $query .= '
                    )
                ) OR
                (
                    p.type = "fallback" AND
                    p.node_id = ' . $page->getNode()->getId() . '
                )
        ';

        foreach ($em->createQuery($query)->getResult() as $subPage) {
            if ($subPage->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_SYMLINK) {
                $subPages[$subPage->getId()] = $subPage;
            } else if ($subPage->getType() == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK) {
                // check if $subPage is a fallback to $page
                $targetLang = $page->getLang();
                $currentLang = $subPage->getLang();
                while ($currentLang && $currentLang != $targetLang) {
                    $currentLang = $fallbacks[$currentLang];
                }
                if ($currentLang) {
                    $subPages[$subPage->getId()] = $subPage;
                }
            }
        }

        // recurse!
        foreach ($subPages as $subPage) {
            $this->getPagesPointingTo($subPage, $subPages);
        }
        return $subPages;
    }
}

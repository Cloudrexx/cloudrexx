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

    /**
     * Delete all cached file's of the cache system   
     */
    function _deleteAllFiles($cacheEngine = null)
    {
        if (!in_array($cacheEngine, array('cxPages', 'cxEntries'))) {
        \Env::get('cache')->deleteAll();
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
                            \Env::get('cache')->deleteAll();
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
// Fixed: This is apparently useless, as it has no effect
            //ini_set('opcache.enable', 1);
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
                if (@$memcache->connect($memcacheConfiguration['ip'], $memcacheConfiguration['port'])) {
                    $this->memcache = $memcache;
                }
            } elseif (class_exists('\Memcached')) {
                $memcache = new \Memcached();
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
            unset($this->memcache); // needed for reinitialization
            if (class_exists('\Memcached')) {
                $memcache = new \Memcached();
                if (@$memcache->addServer($memcachedConfiguration['ip'], $memcachedConfiguration['port'])) {
                    $this->memcache = $memcache;
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
    
    public function getAllUserCacheEngines() {
        return array(self::CACHE_ENGINE_APC, self::CACHE_ENGINE_MEMCACHE, self::CACHE_ENGINE_MEMCACHED, self::CACHE_ENGINE_XCACHE);
    }
    
    public function getAllOpCacheEngines() {
        return array(self::CACHE_ENGINE_APC, self::CACHE_ENGINE_ZEND_OPCACHE);
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
                return $this->memcache ? true : false;
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
                return $this->memcache ? true : false;
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
                return is_writable(ASCMS_CACHE_PATH);
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
    
    protected function getVarnishConfiguration()
    {
        global $_CONFIG;
        $ip = '127.0.0.1';
        $port = '8080';
        
        if(!empty($_CONFIG['cacheProxyCacheVarnishConfig'])){
            $settings = json_decode($_CONFIG['cacheProxyCacheVarnishConfig'], true);
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
            if (is_dir(ASCMS_CACHE_PATH)) {
                if (is_writable(ASCMS_CACHE_PATH)) {
                    $this->strCachePath = ASCMS_CACHE_PATH . '/';
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
        
        $this->clearVarnishCache();
    }
    
    /**
     * Clears Varnish cache
     */
    private function clearVarnishCache()
    {
        global $_CONFIG;

        if (!isset($_CONFIG['cacheVarnishStatus']) || $_CONFIG['cacheVarnishStatus'] != 'on') {
            return;
        }

        $varnishConfiguration = $this->getVarnishConfiguration();
        $varnishSocket = fsockopen($varnishConfiguration['ip'], $varnishConfiguration['port'], $errno, $errstr);

        if (!$varnishSocket) {
            \DBG::log("Varnish error: $errstr ($errno) on server {$varnishConfiguration['ip']}:{$varnishConfiguration['port']}");
        }

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
        return $_DBCONFIG['database'].'.'.DBPREFIX;
    }
}
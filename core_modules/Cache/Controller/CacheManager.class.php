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
 * @version     1.0.1
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 * @todo        Edit PHP DocBlocks!
 */
 namespace Cx\Core_Modules\Cache\Controller;
/**
 * Cache
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.1
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */
class CacheManager extends \Cx\Core_Modules\Cache\Controller\CacheLib
{
    var $objTpl;
    var $arrSettings = array();

    private $objSettings;

    /**
     * Constructor
     *
     */
    function CacheManager()
    {
        $this->__construct();
    }

    /**
     * PHP5 constructor
     *
     * @global     object    $objTemplate
     * @global    array    $_ARRAYLANG
     */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG, $objInit;

        $this->objTpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH . '/Cache/View/Template/Backend');
        $langData = $objInit->loadLanguageData('Cache');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->objTpl);
        $this->objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->arrSettings = $this->getSettings();
        $this->objSettings = new \Cx\Core\Config\Controller\Config();

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if (is_dir($cx->getWebsiteCachePath())) {
            if (is_writable($cx->getWebsiteCachePath())) {
                $this->strCachePath = $cx->getWebsiteCachePath() . '/';
            } else {
                $objTemplate->SetVariable('CONTENT_STATUS_MESSAGE', $_ARRAYLANG['TXT_CACHE_ERR_NOTWRITABLE'] . $cx->getWebsiteCachePath());
            }
        } else {
            $objTemplate->SetVariable('CONTENT_STATUS_MESSAGE', $_ARRAYLANG['TXT_CACHE_ERR_NOTEXIST'] . $cx->getWebsiteCachePath());
        }

        parent::__construct();
    }

    /**
     * Show settings of the module
     *
     * @global     object    $objTemplate
     * @global     array    $_ARRAYLANG
     */
    function showSettings()
    {
        global $objTemplate, $_ARRAYLANG;

        $this->objTpl->loadTemplateFile('settings.html');
        $this->objTpl->setVariable($_ARRAYLANG);
        $this->objTpl->setVariable(array(
            'TXT_CACHE_GENERAL' => $_ARRAYLANG['TXT_SETTINGS_MENU_CACHE'],
            'TXT_SETTINGS_SAVE' => $_ARRAYLANG['TXT_SAVE'],
            'TXT_SETTINGS_ON' => $_ARRAYLANG['TXT_ACTIVATED'],
            'TXT_SETTINGS_OFF' => $_ARRAYLANG['TXT_DEACTIVATED'],
            'TXT_SETTINGS_STATUS' => $_ARRAYLANG['TXT_CACHE_SETTINGS_STATUS'],
            'TXT_SETTINGS_STATUS_HELP' => $_ARRAYLANG['TXT_CACHE_SETTINGS_STATUS_HELP'],
            'TXT_SETTINGS_EXPIRATION' => $_ARRAYLANG['TXT_CACHE_SETTINGS_EXPIRATION'],
            'TXT_SETTINGS_EXPIRATION_HELP' => $_ARRAYLANG['TXT_CACHE_SETTINGS_EXPIRATION_HELP'],
            'TXT_EMPTY_BUTTON' => $_ARRAYLANG['TXT_CACHE_EMPTY'],
            'TXT_EMPTY_DESC' => $_ARRAYLANG['TXT_CACHE_EMPTY_DESC'],
            'TXT_EMPTY_DESC_APC' => $_ARRAYLANG['TXT_CACHE_EMPTY_DESC_FILES_AND_ENRIES'],
            'TXT_EMPTY_DESC_ZEND_OP' => $_ARRAYLANG['TXT_CACHE_EMPTY_DESC_FILES'],
            'TXT_EMPTY_DESC_MEMCACHE' => $_ARRAYLANG['TXT_CACHE_EMPTY_DESC_MEMCACHE'],
            'TXT_EMPTY_DESC_XCACHE' => $_ARRAYLANG['TXT_CACHE_EMPTY_DESC_FILES_AND_ENRIES'],
            'TXT_STATS_FILES' => $_ARRAYLANG['TXT_CACHE_STATS_FILES'],
            'TXT_STATS_FOLDERSIZE' => $_ARRAYLANG['TXT_CACHE_STATS_FOLDERSIZE'],
            'CACHE_EXPERIMENTAL' => \Cx\Core\Core\Controller\Cx::instanciate()->getComponent(
                'View'
            )->flagExperimental(),
        ));
        $this->objTpl->setVariable($_ARRAYLANG);

        if ($this->objSettings->isWritable()) {
            $this->objTpl->parse('cache_submit_button');
        } else {
            $this->objTpl->hideBlock('cache_submit_button');
            $objTemplate->SetVariable('CONTENT_STATUS_MESSAGE', implode("<br />\n", $this->objSettings->strErrMessage));
        }

        // parse op cache engines
        $this->parseOPCacheEngines();
        // parse user cache engines
        $this->parseUserCacheEngines();

        $this->parseMemcacheSettings();
        $this->parseMemcachedSettings();
        $this->parseReverseProxySettings();
        $this->parseSsiProcessorSettings();

        // generate stats for page cache
        $intFoldersizePages = 0;
        $intFilesPages = 0;
        $handleFolder = opendir($this->strCachePath . static::CACHE_DIRECTORY_OFFSET_PAGE);
        if ($handleFolder) {
            while ($strFile = readdir($handleFolder)) {
                if (substr($strFile, 0, 1) == '.') {
                    continue;
                }
                $intFoldersizePages += filesize($this->strCachePath . static::CACHE_DIRECTORY_OFFSET_PAGE . $strFile);
                ++$intFilesPages;
            }
            closedir($handleFolder);
        }

        // generate stats for esi cache
        $intFoldersizeEsi = 0;
        $intFilesEsi = 0;
        $handleFolder = opendir($this->strCachePath . static::CACHE_DIRECTORY_OFFSET_ESI);
        if ($handleFolder) {
            while ($strFile = readdir($handleFolder)) {
                if (substr($strFile, 0, 1) == '.') {
                    continue;
                }
                $intFoldersizeEsi += filesize($this->strCachePath . static::CACHE_DIRECTORY_OFFSET_ESI . $strFile);
                ++$intFilesEsi;
            }
            closedir($handleFolder);
        }

        if (   $this->isInstalled(self::CACHE_ENGINE_APC)
            && $this->isConfigured(self::CACHE_ENGINE_APC)
            && (
                $this->opCacheEngine == self::CACHE_ENGINE_APC
                || $this->userCacheEngine == self::CACHE_ENGINE_APC
            )
        ){
            $this->objTpl->touchBlock('apcCachingStats');
            $apcSmaInfo = \apc_sma_info();
            $apcCacheInfo = \apc_cache_info();
        }else{
            $this->objTpl->hideBlock('apcCachingStats');
        }
        if (   $this->isInstalled(self::CACHE_ENGINE_ZEND_OPCACHE)
            && $this->isConfigured(self::CACHE_ENGINE_ZEND_OPCACHE)
            && $this->opCacheEngine == self::CACHE_ENGINE_ZEND_OPCACHE
            && $this->getOpCacheActive()
        ){
            $this->objTpl->touchBlock('zendOpCachingStats');
            $opCacheConfig = \opcache_get_configuration();
            $opCacheStatus = \opcache_get_status();
        }else{
            $this->objTpl->hideBlock('zendOpCachingStats');
        }
        if (   $this->isInstalled(self::CACHE_ENGINE_MEMCACHE)
            && $this->isConfigured(self::CACHE_ENGINE_MEMCACHE)
            && $this->userCacheEngine == self::CACHE_ENGINE_MEMCACHE
            && $this->getUserCacheActive()
        ){
            $this->objTpl->touchBlock('memcacheCachingStats');
            $memcacheStats = $this->memcache->getStats();
        }else{
            $this->objTpl->hideBlock('memcacheCachingStats');
        }
        if (   $this->isInstalled(self::CACHE_ENGINE_MEMCACHED)
            && $this->isConfigured(self::CACHE_ENGINE_MEMCACHED)
            && $this->userCacheEngine == self::CACHE_ENGINE_MEMCACHED
            && $this->getUserCacheActive()
        ){
            $this->objTpl->touchBlock('memcachedCachingStats');
        }else{
            $this->objTpl->hideBlock('memcachedCachingStats');
        }
        if (   $this->isInstalled(self::CACHE_ENGINE_XCACHE)
            && $this->isConfigured(self::CACHE_ENGINE_XCACHE)
            && (
                   $this->opCacheEngine == self::CACHE_ENGINE_XCACHE
                || $this->userCacheEngine == self::CACHE_ENGINE_XCACHE
            )
        ){
            $this->objTpl->touchBlock('xCacheCachingStats');
        }else{
            $this->objTpl->hideBlock('xCacheCachingStats');
        }
        $apcSizeCount = isset($apcCacheInfo['nhits']) ? $apcCacheInfo['nhits'] : 0;
        $apcEntriesCount = 0;
        if(isset($apcCacheInfo)){
            foreach($apcCacheInfo['cache_list'] as $entity){
                if(false !== strpos($entity['key'], $this->getCachePrefix())){
                    $apcEntriesCount++;
                }
            }
        }
        $apcMaxSizeKb = isset($apcSmaInfo['num_seg']) && isset($apcSmaInfo['seg_size']) ? $apcSmaInfo['num_seg']*$apcSmaInfo['seg_size'] / 1024 : 0;
        $apcSizeKb = isset($apcCacheInfo['mem_size']) ? $apcCacheInfo['mem_size'] / 1024 : 0;

        $opcacheSizeCount = !isset($opCacheStatus) || $opCacheStatus == false ? 0 : $opCacheStatus['opcache_statistics']['num_cached_scripts'];
        $opcacheSizeKb = (!isset($opCacheStatus) || $opCacheStatus == false ? 0 : $opCacheStatus['memory_usage']['used_memory']) / (1024 * 1024);
        $opcacheMaxSizeKb = isset($opCacheConfig['directives']['opcache.memory_consumption']) ? $opCacheConfig['directives']['opcache.memory_consumption'] / (1024 * 1024) : 0;

        $memcacheEntriesCount = isset($memcacheStats['curr_items']) ? $memcacheStats['curr_items'] : 0;
        $memcacheSizeMb = isset($memcacheStats['bytes']) ? $memcacheStats['bytes'] / (1024 *1024) : 0;
        $memcacheMaxSizeMb = isset($memcacheStats['limit_maxbytes']) ? $memcacheStats['limit_maxbytes'] / (1024 *1024) : 0;

        $memcacheConfiguration = $this->getMemcacheConfiguration();
        $memcacheServerKey = $memcacheConfiguration['ip'].':'.$memcacheConfiguration['port'];

        $this->objTpl->setVariable(array(
            'SETTINGS_STATUS_ON' => ($this->arrSettings['cacheEnabled'] == 'on') ? 'checked' : '',
            'SETTINGS_STATUS_OFF' => ($this->arrSettings['cacheEnabled'] == 'off') ? 'checked' : '',
            'SETTINGS_OP_CACHE_STATUS_ON'   => ($this->arrSettings['cacheOpStatus'] == 'on') ? 'checked' : '',
            'SETTINGS_OP_CACHE_STATUS_OFF'  => ($this->arrSettings['cacheOpStatus'] == 'off') ? 'checked' : '',
            'SETTINGS_DB_CACHE_STATUS_ON'   => ($this->arrSettings['cacheDbStatus'] == 'on') ? 'checked' : '',
            'SETTINGS_DB_CACHE_STATUS_OFF'  => ($this->arrSettings['cacheDbStatus'] == 'off') ? 'checked' : '',
            'SETTINGS_CACHE_REVERSE_PROXY_NONE'  => ($this->arrSettings['cacheReverseProxy'] == 'none') ? 'selected' : '',
            'SETTINGS_CACHE_REVERSE_PROXY_VARNISH' => ($this->arrSettings['cacheReverseProxy'] == 'varnish') ? 'selected' : '',
            'SETTINGS_CACHE_REVERSE_PROXY_NGINX' => ($this->arrSettings['cacheReverseProxy'] == 'nginx') ? 'selected' : '',
            'SETTINGS_SSI_CACHE_STATUS_INTERN'  => ($this->arrSettings['cacheSsiOutput'] == 'intern') ? 'selected' : '',
            'SETTINGS_SSI_CACHE_STATUS_SSI' => ($this->arrSettings['cacheSsiOutput'] == 'ssi') ? 'selected' : '',
            'SETTINGS_SSI_CACHE_STATUS_ESI' => ($this->arrSettings['cacheSsiOutput'] == 'esi') ? 'selected' : '',
            'INTERNAL_SSI_CACHE_ON' => ($this->arrSettings['internalSsiCache'] == 'on') ? 'checked' : '',
            'INTERNAL_SSI_CACHE_OFF' => ($this->arrSettings['internalSsiCache'] != 'on') ? 'checked' : '',
            'SETTINGS_SSI_CACHE_TYPE_VARNISH' => ($this->arrSettings['cacheSsiType'] == 'varnish') ? 'selected' : '',
            'SETTINGS_SSI_CACHE_TYPE_NGINX' => ($this->arrSettings['cacheSsiType'] == 'nginx') ? 'selected' : '',
            'SETTINGS_EXPIRATION' => intval($this->arrSettings['cacheExpiration']),
            'STATS_PAGE_FILE_COUNT'                 => $intFilesPages,
            'STATS_FOLDERSIZE_PAGES'                => number_format($intFoldersizePages / 1024, 2, '.', '\''),
            'STATS_ESI_FILE_COUNT'                  => $intFilesEsi,
            'STATS_FOLDERSIZE_ESI'                  => number_format($intFoldersizeEsi / 1024, 2, '.', '\''),
            'STATS_APC_CACHE_SITE_COUNT'            => $apcSizeCount,
            'STATS_APC_CACHE_ENTRIES_COUNT'         => $apcEntriesCount,
            'STATS_APC_MAX_SIZE'                    => number_format($apcMaxSizeKb, 2, '.', '\''),
            'STATS_APC_SIZE'                        => number_format($apcSizeKb, 2, '.', '\''),
            'STATS_OPCACHE_CACHE_SITE_COUNT'        => $opcacheSizeCount,
            'STATS_OPCACHE_SIZE'                    => number_format($opcacheSizeKb, 2, '.', '\''),
            'STATS_OPCACHE_MAX_SIZE'                => number_format($opcacheMaxSizeKb, 2, '.', '\''),
            'STATS_MEMCACHE_CACHE_ENTRIES_COUNT'    => $memcacheEntriesCount,
            'STATS_MEMCACHE_SIZE'                   => number_format($memcacheSizeMb, 2, '.', '\''),
            'STATS_MEMCACHE_MAX_SIZE'               => number_format($memcacheMaxSizeMb, 2, '.', '\''),
        ));

        $objTemplate->setVariable(array(
            'CONTENT_TITLE' => $_ARRAYLANG['TXT_SETTINGS_MENU_CACHE'],
            'ADMIN_CONTENT' => $this->objTpl->get()
        ));
    }

    /**
     * Update settings and write them to the database
     *
     * @global     object    $objDatabase
     * @global     object    $objTemplate
     * @global     array    $_ARRAYLANG
     */
    function updateSettings()
    {
        global $objDatabase, $objTemplate, $_ARRAYLANG, $_CONFIG;

        if (!isset($_POST['frmSettings_Submit'])) {
            return;
        }

        \Cx\Core\Setting\Controller\Setting::init('Config', 'cache','Yaml');
        \Cx\Core\Setting\Controller\Setting::set('cacheEnabled', $_POST['cachingStatus']);
        \Cx\Core\Setting\Controller\Setting::set('cacheExpiration', intval($_POST['cachingExpiration']));
        \Cx\Core\Setting\Controller\Setting::set('cacheUserCache', contrexx_input2db($_POST['usercache']));
        \Cx\Core\Setting\Controller\Setting::set('cacheOPCache', contrexx_input2db($_POST['opcache']));
        \Cx\Core\Setting\Controller\Setting::set('cacheOpStatus', contrexx_input2db($_POST['cacheOpStatus']));
        \Cx\Core\Setting\Controller\Setting::set('cacheOpStatus', contrexx_input2db($_POST['cacheOpStatus']));
        \Cx\Core\Setting\Controller\Setting::set('cacheDbStatus', contrexx_input2db($_POST['cacheDbStatus']));
        \Cx\Core\Setting\Controller\Setting::set('cacheReverseProxy', contrexx_input2db($_POST['cacheReverseProxy']));
        \Cx\Core\Setting\Controller\Setting::set('internalSsiCache', contrexx_input2db($_POST['internalSsiCache']));
        $oldSsiValue = $_CONFIG['cacheSsiOutput'];
        \Cx\Core\Setting\Controller\Setting::set('cacheSsiOutput', contrexx_input2db($_POST['cacheSsiOutput']));
        \Cx\Core\Setting\Controller\Setting::set('cacheSsiType', contrexx_input2db($_POST['cacheSsiType']));

        foreach (
            array(
                'cacheUserCacheMemcacheConfig' => array(
                    'key' => 'memcacheSetting',
                    'defaultPort' => 11211,
                ),
                'cacheUserCacheMemcachedConfig' => array(
                    'key' => 'memcachedSetting',
                    'defaultPort' => 11211,
                ),
                'cacheProxyCacheConfig' => array(
                    'key' => 'reverseProxy',
                    'defaultPort' => 8080,
                ),
                'cacheSsiProcessorConfig' => array(
                    'key' => 'ssiProcessor',
                    'defaultPort' => 8080,
                ),
            )
            as
            $settingName => $settings
        ) {
            $hostnamePortSetting = $settings['key'];
            if (
                !empty($_POST[$hostnamePortSetting . 'Ip']) ||
                !empty($_POST[$hostnamePortSetting . 'Port'])
            ) {
                $settings = json_encode(
                    array(
                        'ip'   => (
                            !empty($_POST[$hostnamePortSetting . 'Ip']) ?
                            contrexx_input2raw($_POST[$hostnamePortSetting . 'Ip']) :
                            '127.0.0.1'
                        ),
                        'port' => (
                            !empty($_POST[$hostnamePortSetting . 'Port']) ?
                            intval($_POST[$hostnamePortSetting . 'Port']) :
                            $defaultPort
                        ),
                    )
                );
                \Cx\Core\Setting\Controller\Setting::set($settingName, $settings);
            }
        }

        \Cx\Core\Setting\Controller\Setting::updateAll();
        $this->arrSettings = $this->getSettings();
        $this->initUserCaching(); // reinit user caches (especially memcache)
        $this->initOPCaching(); // reinit opcaches
        $this->getActivatedCacheEngines();
        $this->clearCache($this->getOpCacheEngine());
        $this->clearCache($this->getUserCacheEngine());

        if ($oldSsiValue != contrexx_input2db($_POST['cacheSsiOutput'])) {
            $this->_deleteAllFiles('cxPages');
        }

        if (!count($this->objSettings->strErrMessage)) {
            $objTemplate->SetVariable('CONTENT_OK_MESSAGE', $_ARRAYLANG['TXT_SETTINGS_UPDATED']);
        } else {
            $objTemplate->SetVariable('CONTENT_STATUS_MESSAGE', implode("<br />\n", $this->objSettings->strErrMessage));
        }
    }

    private function parseOPCacheEngines() {
        $cachingEngines = array(
            self::CACHE_ENGINE_APC => array(),
            self::CACHE_ENGINE_ZEND_OPCACHE => array(),
            self::CACHE_ENGINE_XCACHE => array(),
        );
        $this->objTpl->setVariable('CHECKED_OPCACHE_' . strtoupper($this->getOpCacheEngine()), 'checked="checked"');
        if ($this->isInstalled(self::CACHE_ENGINE_APC)) {
            $cachingEngines[self::CACHE_ENGINE_APC]['installed'] = true;
        }
        if ($this->isActive(self::CACHE_ENGINE_APC)) {
            $cachingEngines[self::CACHE_ENGINE_APC]['active'] = true;
        }
        if ($this->isConfigured(self::CACHE_ENGINE_APC)) {
            $cachingEngines[self::CACHE_ENGINE_APC]['configured'] = true;
        }

        if ($this->isInstalled(self::CACHE_ENGINE_ZEND_OPCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_ZEND_OPCACHE]['installed'] = true;
        }
        if ($this->isActive(self::CACHE_ENGINE_ZEND_OPCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_ZEND_OPCACHE]['active'] = true;
        }
        if ($this->isConfigured(self::CACHE_ENGINE_ZEND_OPCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_ZEND_OPCACHE]['configured'] = true;
        }

        if ($this->isInstalled(self::CACHE_ENGINE_XCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_XCACHE]['installed'] = true;
        }
        if ($this->isActive(self::CACHE_ENGINE_XCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_XCACHE]['active'] = true;
        }
        if ($this->isConfigured(self::CACHE_ENGINE_XCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_XCACHE]['configured'] = true;
        }

        foreach ($cachingEngines as $engine => $data) {
            $installationIcon = $activeIcon = $configurationIcon = 'led_red.gif';
            if (isset($data['installed']) && isset($data['active']) && isset($data['configured'])) {
                if ($this->objTpl->blockExists('cache_opcache_' . $engine)) {
                    $this->objTpl->touchBlock('cache_opcache_' . $engine);
                }
            }
            if (isset($data['installed'])) {
                $installationIcon = 'led_green.gif';
            }
            if (isset($data['active'])) {
                $activeIcon = 'led_green.gif';
            }
            if (isset($data['configured'])) {
                $configurationIcon = 'led_green.gif';
            }
            $engine = strtoupper($engine);
            $this->objTpl->setVariable($engine . '_OPCACHE_INSTALLATION_ICON', $installationIcon);
            $this->objTpl->setVariable($engine . '_OPCACHE_ACTIVE_ICON', $activeIcon);
            $this->objTpl->setVariable($engine . '_OPCACHE_CONFIGURATION_ICON', $configurationIcon);
        }
    }

    private function parseUserCacheEngines() {
        $cachingEngines = array(
            self::CACHE_ENGINE_APC => array(),
            self::CACHE_ENGINE_MEMCACHE => array(),
            self::CACHE_ENGINE_MEMCACHED => array(),
            self::CACHE_ENGINE_XCACHE => array(),
        );
        $this->objTpl->setVariable('CHECKED_USERCACHE_' . strtoupper($this->getUserCacheEngine()), 'checked="checked"');
        if ($this->isInstalled(self::CACHE_ENGINE_APC, true)) {
            $cachingEngines[self::CACHE_ENGINE_APC]['installed'] = true;
        }
        if ($this->isActive(self::CACHE_ENGINE_APC)) {
            $cachingEngines[self::CACHE_ENGINE_APC]['active'] = true;
        }
        if ($this->isConfigured(self::CACHE_ENGINE_APC, true)) {
            $cachingEngines[self::CACHE_ENGINE_APC]['configured'] = true;
        }

        if ($this->isInstalled(self::CACHE_ENGINE_MEMCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_MEMCACHE]['installed'] = true;
        }
        if ($this->isActive(self::CACHE_ENGINE_MEMCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_MEMCACHE]['active'] = true;
        }
        if ($this->isConfigured(self::CACHE_ENGINE_MEMCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_MEMCACHE]['configured'] = true;
        }

        if ($this->isInstalled(self::CACHE_ENGINE_MEMCACHED)) {
            $cachingEngines[self::CACHE_ENGINE_MEMCACHED]['installed'] = true;
        }
        if ($this->isActive(self::CACHE_ENGINE_MEMCACHED)) {
            $cachingEngines[self::CACHE_ENGINE_MEMCACHED]['active'] = true;
        }
        if ($this->isConfigured(self::CACHE_ENGINE_MEMCACHED)) {
            $cachingEngines[self::CACHE_ENGINE_MEMCACHED]['configured'] = true;
        }

        if ($this->isInstalled(self::CACHE_ENGINE_XCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_XCACHE]['installed'] = true;
        }
        if ($this->isActive(self::CACHE_ENGINE_XCACHE)) {
            $cachingEngines[self::CACHE_ENGINE_XCACHE]['active'] = true;
        }
        if ($this->isConfigured(self::CACHE_ENGINE_XCACHE, true)) {
            $cachingEngines[self::CACHE_ENGINE_XCACHE]['configured'] = true;
        }

        foreach ($cachingEngines as $engine => $data) {
            $installationIcon = $activeIcon = $configurationIcon = 'led_red.gif';
            if (isset($data['installed']) && isset($data['active']) && isset($data['configured'])) {
                if ($this->objTpl->blockExists('cache_usercache_' . $engine)) {
                    $this->objTpl->touchBlock('cache_usercache_' . $engine);
                }
            }
            if (isset($data['installed'])) {
                $installationIcon = 'led_green.gif';
            }
            if (isset($data['active'])) {
                $activeIcon = 'led_green.gif';
            }
            if (isset($data['configured'])) {
                $configurationIcon = 'led_green.gif';
            }
            $engine = strtoupper($engine);
            $this->objTpl->setVariable($engine . '_USERCACHE_INSTALLATION_ICON', $installationIcon);
            $this->objTpl->setVariable($engine . '_USERCACHE_ACTIVE_ICON', $activeIcon);
            $this->objTpl->setVariable($engine . '_USERCACHE_CONFIGURATION_ICON', $configurationIcon);
        }
    }

    protected function parseMemcacheSettings() {
        $configuration = $this->getMemcacheConfiguration();
        $this->objTpl->setVariable('MEMCACHE_USERCACHE_CONFIG_IP', contrexx_raw2xhtml($configuration['ip']));
        $this->objTpl->setVariable('MEMCACHE_USERCACHE_CONFIG_PORT', contrexx_raw2xhtml($configuration['port']));
    }

    protected function parseMemcachedSettings() {
        $configuration = $this->getMemcachedConfiguration();
        $this->objTpl->setVariable('MEMCACHED_USERCACHE_CONFIG_IP', contrexx_raw2xhtml($configuration['ip']));
        $this->objTpl->setVariable('MEMCACHED_USERCACHE_CONFIG_PORT', contrexx_raw2xhtml($configuration['port']));
    }

    /**
     * Parses reverse proxy settings to current template
     */
    protected function parseReverseProxySettings(){
        $configuration = $this->getReverseProxyConfiguration();
        $this->objTpl->setVariable('PROXYCACHE_CONFIG_IP', contrexx_raw2xhtml($configuration['ip']));
        $this->objTpl->setVariable('PROXYCACHE_CONFIG_PORT', contrexx_raw2xhtml($configuration['port']));
    }

    /**
     * Parses reverse ESI/SSI processor settings to current template
     */
    protected function parseSsiProcessorSettings(){
        $configuration = $this->getSsiProcessorConfiguration();
        $this->objTpl->setVariable('SSI_PROCESSOR_CONFIG_IP', contrexx_raw2xhtml($configuration['ip']));
        $this->objTpl->setVariable('SSI_PROCESSOR_CONFIG_PORT', contrexx_raw2xhtml($configuration['port']));
    }

    /**
     * Calls the related Clear Function from Lib and sets an OK-Message
     * @global array $_ARRAYLANG
     * @global object $objTemplate
     * @param string $cacheEngine
     */
    public function forceClearCache($cacheEngine = null){
        global $objTemplate, $_ARRAYLANG;

        parent::forceClearCache($cacheEngine);
        $objTemplate->SetVariable('CONTENT_OK_MESSAGE', $_ARRAYLANG['TXT_CACHE_EMPTY_SUCCESS']);
    }
}

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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @package     cloudrexx
 * @subpackage  coremodule_cache
 */
global $_ARRAYLANG;
$_ARRAYLANG['TXT_CACHE_ERR_NOTWRITABLE'] = 'The choosen caching-directory is not writeable. Please set chmod 777 on ';
$_ARRAYLANG['TXT_CACHE_ERR_NOTEXIST'] = 'Directory for cache-system does not exist. Please check path: ';
$_ARRAYLANG['TXT_SETTINGS_MENU_CACHE'] = 'Caching';
$_ARRAYLANG['TXT_CACHE_STATS'] = 'Statistics';
$_ARRAYLANG['TXT_CACHE_CONTREXX_CACHING'] = 'Contrexx-Caching';
$_ARRAYLANG['TXT_CACHE_USERCACHE'] = 'Datenbank Cache Systeme';
$_ARRAYLANG['TXT_CACHE_OPCACHE'] = 'Programmcode Cache Systeme';
$_ARRAYLANG['TXT_CACHE_PROXYCACHE'] = 'Proxy Cache';
$_ARRAYLANG['TXT_CACHE_EMPTY'] = 'Tøm cache';
$_ARRAYLANG['TXT_CACHE_APC'] = 'APC';
$_ARRAYLANG['TXT_CACHE_ZEND_OPCACHE'] = 'Zend OPCache';
$_ARRAYLANG['TXT_CACHE_XCACHE'] = 'xCache';
$_ARRAYLANG['TXT_CACHE_MEMCACHE'] = 'Memcache';
$_ARRAYLANG['TXT_CACHE_MEMCACHED'] = 'Memcached';
$_ARRAYLANG['TXT_CACHE_APC_ACTIVE_INFO'] = 'APC ist aktiviert, sobald die PHP Direktive "apc.enabled" auf "On" gesetzt ist.';
$_ARRAYLANG['TXT_CACHE_APC_CONFIG_INFO'] = 'Wenn Sie APC als Datenbank Cache System einsetzen möchten, muss die PHP Direktive "apc.serializer" auf "php" gesetzt sein.';
$_ARRAYLANG['TXT_CACHE_ZEND_OPCACHE_ACTIVE_INFO'] = 'Zend OPCache ist aktiviert, sobald die PHP Direktive "opcache.enable" auf "On" gesetzt ist.';
$_ARRAYLANG['TXT_CACHE_XCACHE_ACTIVE_INFO'] = 'xCache ist aktiviert, sobald die PHP Direktive "xcache.cacher" auf "On" gesetzt ist.';
$_ARRAYLANG['TXT_CACHE_XCACHE_CONFIG_INFO'] = 'Um xCache als Datenbank Cache System zu verwenden, muss die PHP Direktive "xcache.var_size" grösser 0 sein. Für den Programmcode Cache muss "xcache.size" grösser 0 sein.';
$_ARRAYLANG['TXT_CACHE_MEMCACHE_ACTIVE_INFO'] = 'Memcache ist aktiviert, sobald der Memcache Server läuft und die Konfigurationen korrekt gesetzt sind.';
$_ARRAYLANG['TXT_CACHE_MEMCACHE_CONFIG_INFO'] = 'Um Memcache zu verwenden, muss die Konfiguration (IP-Adresse und Port) korrekt konfiguriert sein.';
$_ARRAYLANG['TXT_CACHE_MEMCACHE_ACTIVE_INFO'] = 'Memcached ist aktiviert, sobald der Memcached Server läuft und die Konfigurationen korrekt gesetzt sind.';
$_ARRAYLANG['TXT_CACHE_MEMCACHE_CONFIG_INFO'] = 'Um Memcached zu verwenden, muss die Konfiguration (IP-Adresse und Port) korrekt konfiguriert sein.';
$_ARRAYLANG['TXT_CACHE_ENGINE'] = 'System';
$_ARRAYLANG['TXT_CACHE_INSTALLATION_STATE'] = 'Installiert';
$_ARRAYLANG['TXT_CACHE_ACTIVE_STATE'] = 'Aktiv';
$_ARRAYLANG['TXT_CACHE_CONFIGURATION_STATE'] = 'Konfiguriert';
$_ARRAYLANG['TXT_SAVE'] = 'Gem';
$_ARRAYLANG['TXT_ACTIVATED'] = 'Aktiver';
$_ARRAYLANG['TXT_DEACTIVATED'] = 'Deactivated';
$_ARRAYLANG['TXT_CACHE_SETTINGS_STATUS'] = 'Cache-system';
$_ARRAYLANG['TXT_CACHE_SETTINGS_STATUS_HELP'] = 'Status for cache-systemet: (on | off)';
$_ARRAYLANG['TXT_CACHE_SETTINGS_EXPIRATION'] = 'Expiration';
$_ARRAYLANG['TXT_CACHE_SETTINGS_EXPIRATION_HELP'] = 'After this period (measured in seconds) cached pages will be recreated.';
$_ARRAYLANG['TXT_CACHE_EMPTY_DESC'] = 'With a click on the button, you can remove all cached files in the caching-folder. The cache will be recreated itself while viewing the pages.';
$_ARRAYLANG['TXT_CACHE_EMPTY_DESC_FILES_AND_ENRIES'] = 'Über den Button können Sie den aktuellen Inhalt des Caches leeren. Der Cache der Dateien und Einträge baut sich anschliessend bei Aufrufen der Seite jeweils wieder neu auf.';
$_ARRAYLANG['TXT_CACHE_EMPTY_DESC_FILES'] = 'Über den Button können Sie den aktuellen Inhalt des Caches leeren. Der Cache der Dateien baut sich anschliessend bei Aufrufen der Seite jeweils wieder neu auf.';
$_ARRAYLANG['TXT_CACHE_EMPTY_DESC_MEMCACHE'] = 'Über den Button können Sie alle aktuell gecachten Einträge als veraltet markieren. Diese werden beim nächsten Aufruf der ensprechenden Site erneuert.';
$_ARRAYLANG['TXT_CACHE_STATS_FILES'] = 'Cached pages';
$_ARRAYLANG['TXT_CACHE_STATS_FOLDERSIZE'] = 'Mappestørrelse';
$_ARRAYLANG['TXT_STATS_CACHE_SITE_COUNT'] = 'Gecachte Dateien';
$_ARRAYLANG['TXT_STATS_CACHE_ENTRIES_COUNT'] = 'Gecachte Datenbankeinträge';
$_ARRAYLANG['TXT_STATS_CACHE_SIZE'] = 'Datenmenge der gespeicherten Daten';
$_ARRAYLANG['TXT_DISPLAY_CONFIGURATION'] = 'Konfiguration einblenden';
$_ARRAYLANG['TXT_HIDE_CONFIGURATION'] = 'Konfiguration ausblenden';
$_ARRAYLANG['TXT_CACHE_VARNISH'] = 'Varnish';
$_ARRAYLANG['TXT_CACHE_PROXY_IP'] = 'Proxy IP-Adresse';
$_ARRAYLANG['TXT_CACHE_PROXY_PORT'] = 'Proxy Port';
$_ARRAYLANG['TXT_SETTINGS_UPDATED'] = 'Settings have been updated.';
$_ARRAYLANG['TXT_CACHE_FOLDER_EMPTY'] = 'Cache-mappen er blevet tømt.';
$_ARRAYLANG['TXT_CACHE_EMPTY_SUCCESS'] = 'Der Cache wurde erfolgreich geleert';

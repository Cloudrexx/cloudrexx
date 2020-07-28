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
 * Abstract representation of a caching reverse proxy
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */

namespace Cx\Core_Modules\Cache\Model\Entity;

/**
 * Abstract representation of a caching reverse proxy
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
class ReverseProxyCloudrexx extends \Cx\Lib\ReverseProxy\Model\Entity\ReverseProxy {
    
    /**
     * Initializes a reverse proxy instance
     * @param string $hostname Proxy hostname
     * @param int $port Proxy port
     */
    public function __construct($hostname, $port) {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->ssiProcessor = new \Cx\Lib\ReverseProxy\Model\Entity\SsiProcessorEsi();
    }

    /**
     * {@inheritdoc}
     *
     * This method has been overwritten as the Cloudrexx ESI cache is not
     * stored specific per domain and/or port. Therefore, we only have to
     * call the flush operation (on clearCachePageForDomainAndPort()) once.
     */
    public function clearCachePage($urlPattern, $domainsAndPorts) {
        $this->clearCachePageForDomainAndPort($urlPattern, '', 0);
    }
    
    /**
     * Clears a cache page
     * Please note that this will not work during an ESI sub-request.
     * @param string $urlPattern Drop all pages that match the pattern, for exact format, make educated guesses
     * @param string $domain Domain name to drop cache page of.
     *                       Not used by ReverseProxyCloudrexx
     * @param int $port Port to drop cache page of.
     *                  Not used by ReverseProxyCloudrexx
     */
    protected function clearCachePageForDomainAndPort($urlPattern, $domain, $port) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $strCachePath = $cx->getWebsiteCachePath() . '/';
        $strCachePath .= \Cx\Core_Modules\Cache\Controller\CacheLib::CACHE_DIRECTORY_OFFSET_ESI;

        $glob = null;
        $glob2 = null;
        if ($urlPattern == '*') {
            $glob = $strCachePath . '*';
        }

        if (!$glob) {
            $searchParts = $cx->getComponent('Cache')->getCacheFileNameSearchPartsFromUrl(
                $urlPattern,
                $cx->getRequest()->getUrl()
            );
            $glob = $strCachePath . $cx->getComponent('Cache')->getCacheFileNameFromUrl(
                $urlPattern,
                $cx->getRequest()->getUrl(),
                false
            ) . '*' . implode('', $searchParts) . '*';
            $this->toggleHttps($urlPattern);
            $glob2 = $strCachePath . $cx->getComponent('Cache')->getCacheFileNameFromUrl(
                $urlPattern,
                $cx->getRequest()->getUrl(),
                false
            ) . '*' . implode('', $searchParts) . '*';
        }
        
        if ($glob !== null) {
            $this->globDrop($glob);
            if ($glob2 !== null && $glob2 != $glob) {
                $this->globDrop($glob2);
            }
            return;
        }

        $cacheFile = $cx->getComponent('Cache')->getCacheFileNameFromUrl(
            $urlPattern,
            $cx->getRequest()->getUrl()
        );
        try {
            $file = new \Cx\Lib\FileSystem\File($strCachePath . $cacheFile);
            $file->delete();
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {}
        
        // make sure HTTP and HTTPS files are dropped
        $this->toggleHttps($urlPattern);
        $cacheFile = $cx->getComponent('Cache')->getCacheFileNameFromUrl(
            $urlPattern,
            $cx->getRequest()->getUrl()
        );
        try {
            $file = new \Cx\Lib\FileSystem\File($strCachePath . $cacheFile);
            $file->delete();
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {}
    }
    
    protected function toggleHttps(&$url) {
        if (substr($url, 0, 5) == 'https') {
            $url = 'http' . substr($url, 5);
        } else if (substr($url, 0, 4) == 'http') {
            $url = 'https' . substr($url, 4);
        }
    }
    
    protected function globDrop($glob) {
        $fileNames = glob($glob);
        foreach ($fileNames as $fileName) {
            if (!preg_match('#/[0-9a-f]{32}((_[plutcgrq][a-zA-Z0-9-=\.]+)+)?$#', $fileName)) {
                continue;
            }
            try {
                $file = new \Cx\Lib\FileSystem\File($fileName);
                $file->delete();
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {}
        }
    }
}


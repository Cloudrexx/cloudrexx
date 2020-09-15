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
 * @author      Dario Graf <dario.graf@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */

namespace Cx\Lib\ReverseProxy\Model\Entity;

/**
 * Abstract representation of a caching reverse proxy
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Dario Graf <dario.graf@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
class ReverseProxyCloudflare extends ReverseProxy
{

    /**
     * Remove all files from cloudflare's cache.
     * Ignoring the $urlPattern, $domain and $port because the full cache gets cleared.
     *
     * @param string $urlPattern Drop all pages that match the pattern, for exact format, make educated guesses
     * @param string $domain Domain name to drop cache page of
     * @param int $port Port to drop cache page of
     */

    protected function clearCachePageForDomainAndPort($urlPattern, $domain, $port)
    {
        //config data of cloudflare instance
        $cfZoneId = '';
        $cfUrl = 'https://api.cloudflare.com/client/v4/zones/' . $cfZoneId . '/purge_cache';
        $cfEmail = '';
        $cfAuthKey = '';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $cfUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"purge_everything\":true}");

        $headers = array();
        $headers[] = 'X-Auth-Email:' . $cfEmail;
        $headers[] = 'X-Auth-Key:' . $cfAuthKey;
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_exec($ch);
        if (curl_errno($ch)) {
            \DBG::log('Error:' . curl_error($ch));
        }
        curl_close($ch);
    }
}

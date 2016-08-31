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
 * Socket class
 *
 * Helper methods
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @since       2.1.0
 * @package     cloudrexx
 * @subpackage  core
 */

/**
 * Useful methods that help you connect to other hosts using sockets
 *
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @since       2.1.0
 * @package     cloudrexx
 * @subpackage  core
 */
class Socket
{
    /**
     * Seconds until the connection attempt times out
     */
    private static $timeout_connect  = 5;

    /**
     * Seconds until waiting for a response times out
     */
    private static $timeout_response = 5;


    /**
     * Connect to the given URI and return the response
     *
     * The URI should consist of a string like
     *     http://my.domain.com:80/path/to?your=resource
     * Where only the host name is mandatory.
     * The defaults for other parts are:
     * - Protocol: http
     * - Port:     80
     * - Path:     /
     * Returns false upon the tiniest of errors.
     * Note:  Uses HTTP/1.0 *ONLY*
     * @param  string      $uri        The URI to connect to
     * @return string                  The response on success, false otherwise
     */
    static function getHttp10Response($uri)
    {
//echo("getHttp10Response($uri): Entered<br />");
        // Split the gateway URI into protocol, host, port, and path
        $arrMatch = array();
//        '/^
//          (?:
//            (\w+)           # (http)
//            \:\/\/          # ://
//          ){0,1}
//            ([^\:\/]+)      # (my.domain.com)
//          (?:
//            \:              # :
//            (\d+)           # (80)
//          ){0,1}
//            (.*?)           # (/path/to?your=resource)
//        $/ix',
        if (!preg_match(
                '/^(?:(\w+)\:\/\/){0,1}([^\:\/]+)(?:\:(\d+)){0,1}(.*?)$/i',
                $uri, $arrMatch
        )) return false;
        list($match, $proto, $host, $port, $path) = $arrMatch;
//echo("match $match: proto $proto, host $host, port $port, path $path<br />");
        // Check:  Cannot connect without host name!
        if (empty($host)) return false;

        // Fix a few missing values
        if (empty($proto)) $proto = 'https';
        // This is mandatory for https connects.
        // Also, your PHP needs to be comiled with SSL support built in.
        if ($proto == 'https' || $proto == 'ssl')
            $host = 'ssl://'.$host;
        if (empty($port)) {
            switch (strtolower($proto)) {
                case 'https':
                    $port = '443'; break;
                case 'http':
                default:
                    $port = '80';
            }
        }
        if (empty($path)) $path = '/';
//echo("fixed: proto $proto, host $host, port $port, path $path<br />");

        // Open socket connection
        $method = 'GET';
// POST only
//        $contenttype = "text/plain";
        $errno = 0;
        $errstr = '';
        $fp = fsockopen($host, intval($port), $errno, $errstr, self::$timeout_connect);
        if (!$fp) {
//echo("ERROR $errno: $errstr<br />");
            return false;
        }
        stream_set_timeout($fp, self::$timeout_response, 0);

        // Send request
        fputs(
            $fp,
            "$method $path HTTP/1.0\r\n".
            "Connection: close\r\n\r\n"
// POST only
//          "Host: $host\r\n".
//          "Content-type: $contenttype\r\n".
//          "Content-length: ".strlen($data)."\r\n".
        );

        // Get response
        $response = false;
        while(!feof($fp))
            $response .= fgets($fp, 128);
        fclose($fp);
        if ($response === false) return false;
        $arrResponse = explode("\r\n\r\n", $response);
        if (empty($arrResponse[1])) return false;
        return $arrResponse[1];
    }

}

?>

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
 * Url class
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Controller;

/**
 * The class Url for checking the link is internal or external and for checking the url is correct or not
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class UrlController extends \Cx\Core\Core\Model\Entity\Controller {

    /**
     * Check if the link is internal or external link.
     *
     * @param string $externalUrl
     *
     * @return boolean
     */
    public function isInternalUrl($externalUrl)
    {
        global $_CONFIG;

        //check the requested link is internal or external
        $baseInternalUrl  = ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'];
        $baseInternalHost = parse_url($baseInternalUrl, PHP_URL_HOST);
        $externalHost     = parse_url($externalUrl, PHP_URL_HOST);

        return (empty($externalHost) || $baseInternalHost == $externalHost);
    }

    /**
     * Check the image and url path
     *
     * @param string $path
     *
     * @return boolean|string
     */
    public function checkPath($path, $refererPath)
    {
        global $_CONFIG;

        if (empty($path)) {
            return false;
        }

        $baseInternalUrl = ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'];
        $offsetPath      = ASCMS_PATH_OFFSET;
        $pattern         = '#^{$offsetPath}# i';

        switch (true) {
            //If the url is correct one(http://www.example.com) or is a mailto(mailto: example@test.com) or is a javascript(javascript: void(0);), it returns the same url.
        case preg_match('/(^|[\n ])([\w]*?)(((ht|f)tp(s)?:\/\/)[\w]+[^ \,\"\n\r\t<]*)/is', $path) || preg_match('#^[mailto:|javascript:]# i', $path):
            $src = $path;
            break;
            //If the url is start with offsetpath, add domain name infront of the url. for example: /test/de/Willkommen => http://www.example.com/test/de/Willkommen
        case preg_match($pattern, $path):
            $src = preg_replace($pattern, $baseInternalUrl.$offsetPath, $path);
            break;
            //If the url is start with '../', set the correct url. for example: ../images/content/test.png => http://www.example.com/test/images/content/test.png
        case preg_match('/^(..\/)(.*)?/', $path):
            $src = preg_replace('/^(..\/)(.*)?/', $baseInternalUrl.$offsetPath.'/$2', $path);
            break;
            //If the url is start with './', set the correct url. for example: ../images/content/test.png => http://www.example.com/test/images/content/test.png
        case preg_match('/^(.\/)(.*)?/', $path):
            $src = preg_replace('/^(.\/)(.*)?/', $baseInternalUrl.$offsetPath.'/$2', $path);
            break;
            //If the url is start with '/', set the correct url. for example: /Welcome/Test-page => http://www.example.com/Welcome/Test-page
        case preg_match('/^(\/)(.*)?/', $path):
            $src = $baseInternalUrl.$path;
            break;
            //If the url is start with '#', that page url is added infront of the url. for example: /#Welcome => http://www.example.com/test/de/Willkommen#Welcome
        case preg_match('#^[\#]# i', $path):
            $src = $refererPath.$path;
            break;
        default :
            $src = $baseInternalUrl.$offsetPath.$path;
            break;
        }
        return $src;
    }
}

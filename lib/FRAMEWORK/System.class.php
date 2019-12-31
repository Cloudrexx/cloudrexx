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
 * FWSystem
 * This class provides system related methods.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @todo        Edit PHP DocBlocks!
 */

/**
 * FWSystem
 * This class provides system related methods.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @todo        Edit PHP DocBlocks!
 */
class FWSystem
{
    /**
    * Returns the maximum file size in bytes that is allowed to upload
    *
    * @return string filesize
    */
    static public function getMaxUploadFileSize()
    {
        $upload_max_filesize = self::getBytesOfLiteralSizeFormat(ini_get('upload_max_filesize'));
        $post_max_size = self::getBytesOfLiteralSizeFormat(ini_get('post_max_size'));

        if ($upload_max_filesize < $post_max_size) {
            $maxUploadFilesize = $upload_max_filesize;
        } else {
            $maxUploadFilesize = $post_max_size;
        }
        return $maxUploadFilesize;
    }

    /**
     * Return the literal size of $bytes with the appropriate suffix (bytes, KB, MB, GB)
     *
     * @param integer $bytes
     * @return string
     */
    static public function getLiteralSizeFormat($bytes)
    {
        if (!$bytes) {
            return false;
        }

        $exp = floor(log($bytes, 1024));

        switch ($exp) {
            case 0: // bytes
                $suffix = ' bytes';
                break;

            case 1: // KB
                $suffix = ' KB';
                break;

            case 2: // MB
                $suffix = ' MB';
                break;

            case 3: // GB
                $suffix = ' GB';
                break;
        }

        return round(($bytes / pow(1024, $exp)), 1).$suffix;
    }

    /**
     * Return the literal size $literalSize in bytes.
     *
     * @param string $literalSize
     * @return integer
     */
    static public function getBytesOfLiteralSizeFormat($literalSize)
    {
        $subpatterns = array();
        if (preg_match('#^([0-9.]+)\s*(gb?|mb?|kb?|bytes?|)?$#i', $literalSize, $subpatterns)) {
            $bytes = $subpatterns[1];
            switch (strtolower(substr($subpatterns[2], 0, 1))) {
                case 'g':
                    $bytes *= 1024;

                case 'm':
                    $bytes *= 1024;

                case 'k':
                    $bytes *= 1024;
            }

            return $bytes;
        } else {
            return 0;
        }
    }

    /**
     * Checks if the given string contains utf8 characters
     *
     * @param   string   $string
     * @return  boolean  false (no match) or true (match)
     */
    public static function detectUtf8($string) {
        return (bool) preg_match(
            '%(?:
                [\xC2-\xDF][\x80-\xBF]              # non-overlong 2-byte
                |\xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
                |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
                |\xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
                |\xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
                |[\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
                |\xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )+%xs',
            $string
        );
    }

}
?>

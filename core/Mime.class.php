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
 * OBSOLETE -- See {@see Filetype.class.php}
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @package     cloudrexx
 * @subpackage  core
 * @version     1.0.0
 */

/**
 * OBSOLETE -- See {@see Filetype.class.php}
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @package     cloudrexx
 * @subpackage  core
 * @version     1.0.0
 */
class Mime
{
    /**
     * Known extensions and corresponding MIME types.
     *
     * Note that these associations are arbitrary!
     * @access  private
     * @var     array
     */
    private static $arrExtensions2MimeTypes = array(
        '3dm' => 'x-world/x-3dmf',
        '3dmf' => 'x-world/x-3dmf',
        'ai' => 'application/postscript',
        'aif' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'au' => 'audio/basic',
        'avi' => 'video/x-msvideo',
        'bin' => 'application/octet-stream',
        'cab' => 'application/x-shockwave-flash',
        'chm' => 'application/mshelp',
        'class' => 'application/octet-stream',
        'com' => 'application/octet-stream',
        'csh' => 'application/x-csh',
        'css' => 'text/css',
        'csv' => 'text/comma-separated-values',
        'dll' => 'application/octet-stream',
        'doc' => 'application/msword',
        'dot' => 'application/msword',
        'eps' => 'application/postscript',
        'exe' => 'application/octet-stream',
        'fh4' => 'image/x-freehand',
        'fh5' => 'image/x-freehand',
        'fhc' => 'image/x-freehand',
        'fif' => 'image/fif',
        'gif' => 'image/gif',
        'gtar' => 'application/x-gtar',
        'gz ' => 'application/gzip',
        'hlp' => 'application/mshelp',
        'hqx' => 'application/mac-binhex40',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/x-icon',
        'ief' => 'image/ief',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'application/x-javascript',
        'js' => 'text/javascript',
        'latex' => 'application/x-latex',
        'mcf' => 'image/vasa',
        'mid' => 'audio/x-midi',
        'midi' => 'audio/x-midi',
        'mov' => 'video/quicktime',
        'movie' => 'video/x-sgi-movie',
        'mp2' => 'audio/x-mpeg',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'pbm' => 'image/x-portable-bitmap',
        'pdf' => 'application/pdf',
        'pgm' => 'image/x-portable-graymap',
        'php' => 'application/x-httpd-php',
        'phtml' => 'application/x-httpd-php',
        'png' => 'image/png',
        'pnm' => 'image/x-portable-anymap',
        'pot' => 'application/mspowerpoint',
        'ppm' => 'image/x-portable-pixmap',
        'pps' => 'application/mspowerpoint',
        'ppt' => 'application/mspowerpoint',
        'ppz' => 'application/mspowerpoint',
        'ps' => 'application/postscript',
        'qd3' => 'x-world/x-3dmf',
        'qd3d' => 'x-world/x-3dmf',
        'qt' => 'video/quicktime',
        'ra' => 'audio/x-pn-realaudio',
        'ram' => 'audio/x-pn-realaudio',
        'rgb' => 'image/x-rgb',
        'rpm' => 'audio/x-pn-realaudio-plugin',
        'rtf' => 'text/rtf',
        'rtx' => 'text/richtext',
        'sgm' => 'text/x-sgml',
        'sgml' => 'text/x-sgml',
        'sh' => 'application/x-sh',
        'shtml' => 'text/html',
        'sit' => 'application/x-stuffit',
        'snd' => 'audio/basic',
        'stream' => 'audio/x-qt-stream',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texi' => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'tsv' => 'text/tab-separated-values',
        'txt' => 'text/plain',
        'viv' => 'video/vnd.vivo',
        'vivo' => 'video/vnd.vivo',
        'wav' => 'audio/x-wav',
        'wbmp' => 'image/vnd.wap.wbmp',
        'wml' => 'text/vnd.wap.wml',
        'wrl' => 'model/vrml',
        'xbm' => 'image/x-xbitmap',
        'xhtml' => 'application/xhtml+xml',
        'xla' => 'application/msexcel',
        'xls' => 'application/msexcel',
        'xml' => 'text/xml',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-windowdump',
        'z' => 'application/x-compress',
        'zip' => 'application/zip',
    );

    /**
     * The default MIME type used if nothing is known about the data
     * @access  private
     * @var   string
     */
    private static $strDefaultType = 'application/octet-stream';


    /**
     * OBSOLETE -- All static here.
     * Create a Mime object
     *
     * Usually not needed, as this class should be used statically.
     * @author  Reto Kohli <reto.kohli@comvation.com>
    function __construct()
    {
    }
     */


    /**
     * Returns boolean true if the string argument is a known ending,
     * false otherwise.
     * @static
     * @param   string     $strExtension    The file extension
     * @return  boolean                     True if the extension is known,
     *                                      false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function isKnownExtension($strExtension)
    {
        return isset(self::$arrExtensions2MimeTypes[$strExtension]);
    }


    /**
     * Return the MIME type for the extension provided.
     *
     * Takes a full file name, or a file extension with or without
     * the dot as an argument, i.e. 'cloudrexx.zip', '.gif, or 'txt'.
     * Returns the string 'application/octet-stream' for any unknown ending.
     * Use {@link isKnownExtension()} to test exactly that.
     * @static
     * @param   string     $strExtension    The file extension
     * @return  string                      The corresponding MIME type
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getMimeTypeForExtension($strExtension)
    {
        // Make sure only the extension is present.
        // Chop the file name up to and including  the last dot
        $strChoppedExtension = preg_replace('/^.*\./', '', $strExtension);
        if (Mime::isKnownExtension($strChoppedExtension))
            return self::$arrExtensions2MimeTypes[$strChoppedExtension];
        return self::$strDefaultType;
    }


    /**
     * Return the default MIME type
     *
     * The value as stored in {@link $strDefaultType}.
     * @static
     * @return  string                      The default MIME type
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function getDefaultType()
    {
        return self::$strDefaultType;
    }


    /**
     * Returns the HTML code for the MIME type dropdown menu
     * @param   string    $selected     The optional selected MIME tpye
     * @return  string                  The menu options HTML code
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getTypeMenuoptions($selected='')
    {
        $strMenuoptions = '';
        foreach (self::$arrExtensions2MimeTypes as $extension => $mimetype) {
            $strMenuoptions .=
                '<option value="'.$mimetype.'"'.
                ($selected == $mimetype ? ' selected="selected"' : '').
                ">$mimetype ($extension)</option>\n";
        }
        return $strMenuoptions;
    }

}

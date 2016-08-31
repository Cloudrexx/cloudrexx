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
* PDF class
*
* Generate PDF for pdfview
* @copyright    CLOUDREXX CMS - CLOUDREXX AG
* @author       Cloudrexx Development Team <info@cloudrexx.com>
* @package      cloudrexx
* @subpackage   core
* @version      1.1.0
*/

/**
 * @ignore
 */
require_once ASCMS_LIBRARY_PATH.'/html2fpdf/html2fpdf.php';

/**
* PDF class
*
* Generate PDF for pdfview
* @copyright    CLOUDREXX CMS - CLOUDREXX AG
* @author       Cloudrexx Development Team <info@cloudrexx.com>
* @package      cloudrexx
* @subpackage   core
* @version      1.1.0
*/
class PDF extends HTML2FPDF
{
    /**
    * string $content
    * Content for insert
    */
    var $content;

    /**
    * string $title
    * File name
    */
    var $title;

    /**
    * string $orientation
    * pageorientation
    */
    var $pdf_orientation;

    /**
    * string $unit
    * Unit-format
    */
    var $pdf_unit;

    /**
    * string $format
    * Page-format
    */
    var $pdf_format;

    /**
    * string $pdf_creator
    * PDF author
    */
    var $pdf_autor;

    function __construct()
    {
        global $_CONFIG;

        $this->pdf_orientation     = 'P';
        $this->pdf_unit         = 'mm';
        $this->pdf_format         = 'A4';
        $this->pdf_autor        = $_CONFIG['coreCmsName'];
    }

    function Create()
    {

        $this->content = utf8_decode($this->_ParseHTML($this->content));

        $pdf = new HTML2FPDF();
        $pdf->ShowNOIMG_GIF();
        $pdf->DisplayPreferences('HideWindowUI');
        $pdf->AddPage();
        $pdf->WriteHTML($this->content);
        $pdf->Output(\Cx\Lib\FileSystem\FileSystem::replaceCharacters($this->title));

    }

    function _ParseHTML($source){

        // H1
        // ----------------
        $source = str_replace('<h1>', '<div class="h1">', $source);
        $source = str_replace('</h1>', '</div>', $source);
        // H2
        // ----------------
        $source = str_replace('<h2>', '<div class="h2">', $source);
        $source = str_replace('</h2>', '</div>', $source);
        // H3
        // ----------------
        $source = str_replace('<h3>', '<div class="h3">', $source);
        $source = str_replace('</h3>', '</div>', $source);
        // H4
        // ----------------
        $source = str_replace('<h3>', '<div class="h3">', $source);
        $source = str_replace('</h3>', '</div>', $source);

        // body
        // ----------------
        $source = str_replace('<body>', '<body><div class="body">', $source);
        $source = str_replace('</body>', '</div></body>', $source);

        // p
        // ----------------
        $source = str_replace('<p>', '<div class="p">', $source);
        $source = str_replace('</p>', '</div>', $source);

        // image to relative path
        // ----------------
        $source = str_replace('src="/images', 'src="images', $source);
        $source = str_replace("src='/images", "src='images", $source);

        return $source;
    }
}

?>

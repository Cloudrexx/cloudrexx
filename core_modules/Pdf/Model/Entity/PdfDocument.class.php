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
 * Generate PDF for pdfview
 *
 * @copyright   Cloudrexx AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_pdf
 * @version     1.0.0
 */

namespace Cx\Core_Modules\Pdf\Model\Entity;

/**
 * PDF class
 * Generate PDF for pdfview
 *
 * @copyright   Cloudrexx AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_pdf
 * @version     1.0.0
 */
class PdfDocument extends \mPDF
{
    /**
     * @var string $content
     */
    protected $content;

    /**
     * @var string $destination
     */
    protected $destination = 'I';

    /**
     * @var string $filePath
     */
    protected $filePath = '';

    /**
     * Constructor
     */
    public function __construct($orientation = 'P', $format = 'A4')
    {
        parent::__construct('', $format, 0, '', 15, 15, 16, 16, 9, 9, $orientation);
    }

    /**
     * Set the content
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Set the output destination
     *
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }

    /**
     * Set the file path to store the PDF document
     *
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Create PDF
     */
    public function Create()
    {
        global $_CONFIG;

        $coreModulePath = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getCodeBaseCoreModulePath();
        $this->noImageFile = $coreModulePath . '/Pdf/View/Media/no_picture.gif';
        if (empty($this->author)) {
            if (isset($_CONFIG['coreAdminName'])) {
                $this->SetAuthor($_CONFIG['coreAdminName']);
            } else {
                $this->SetAuthor($_CONFIG['coreCmsName']);
            }
        }
        $this->SetDisplayPreferences('HideWindowUI');
        $this->AddPage();
        $this->WriteHTML($this->content);
        if (empty($this->filePath)) {
            $this->filePath = \Cx\Lib\FileSystem\FileSystem::replaceCharacters(
                $this->title
            );
        }
        $this->Output($this->filePath, $this->destination);
    }

    /**
     * Get file full path
     *
     * @param string $path     file path
     * @param string $basepath file base path
     */
    public function GetFullPath(&$path, $basepath = '', $tagname = '')
    {
        if ($tagname == 'A') {
            $url = \Cx\Core\Routing\Url::fromMagic($path);
            if ($url->isInternal()) {
                if (substr($path, 0, 1) == '/') {
                    $path = substr($path, 1);
                }
                $docroot = \Cx\Core\Routing\Url::fromDocumentRoot();
                $docroot->setMode('backend');
                $path = $docroot->toString() . $path;
            }
            return;
        }
        // When parsing CSS need to pass temporary basepath -
        // so links are relative to current stylesheet
        if (!$basepath) {
            $basepath = $this->basepath;
        }
        //Fix path value
        $path = str_replace('\\', '/', $path); //If on Windows
        // mPDF 5.7.2
        if (substr($path, 0, 2) == '//') {
            $tr = parse_url($basepath);
            $path = $tr['scheme'] . ':' . $path; // mPDF 6
        }

        $regexp = '|^./|'; // Inadvertently corrects "./path/etc"
        //and "//www.domain.com/etc"
        $path = preg_replace($regexp, '', $path);

        if (substr($path, 0, 1) == '#') {
            return;
        }
        if (preg_match('@^(mailto|tel|fax):.*@i', $path)) {
            return;
        }

        if (substr($path, 0, 3) == '../') {
            $filepath = str_replace('../', '', $path);
            $objFile  = new \Cx\Lib\FileSystem\FileSystemFile($filepath);
            $path     = $objFile->getAbsoluteFilePath();
        } elseif (strpos($path, ':/') === false || strpos($path, ':/') > 10) {
            $objFile = new \Cx\Lib\FileSystem\FileSystemFile($path);
            $path    = $objFile->getAbsoluteFilePath();
        }
    }

}

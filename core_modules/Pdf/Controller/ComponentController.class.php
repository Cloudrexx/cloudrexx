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
 * This is the controllers for the component
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_pdf
 * @version     1.0.0
 */

namespace Cx\Core_Modules\Pdf\Controller;

/**
 * This is the main controller for the component
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_pdf
 * @version     1.0.0
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * Returns all Controller class names for this component (except this)
     *
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses()
    {
        return array('Backend');
    }

    /**
     * Get all the list of PDF templates
     *
     * @return array
     */
    public function getPdfTemplates()
    {
        $repo = $this
            ->cx
            ->getDb()
            ->getEntityManager()
            ->getRepository('\Cx\Core_Modules\Pdf\Model\Entity\PdfTemplate');
        $pdfTemplates = $repo->findBy(array('active' => 1));
        if (!$pdfTemplates) {
            return array();
        }

        $templates = array();
        foreach ($pdfTemplates as $pdfTemplate) {
            $templates[$pdfTemplate->getId()] = $pdfTemplate->getTitle();
        }

        return $templates;
    }

    /**
     * Generate PDF Document
     *
     * @param integer $pdfTemplateId          id of the PDF Template
     * @param array   $substitution           array of substitution values
     * @param string  $mailTplKey             MailTemplate key
     * @param boolean $convertToHtmlEntities  convert input to HTML entities
     *
     * @return mixed array|null
     */
    public function generatePDF($pdfTemplateId, $substitution, $mailTplKey, $convertToHtmlEntities = false)
    {
        if (empty($mailTplKey)) {
            return;
        }

        $repo = $this
            ->cx
            ->getDb()
            ->getEntityManager()
            ->getRepository('\Cx\Core_Modules\Pdf\Model\Entity\PdfTemplate');
        $pdfTemplates = $repo->findOneBy(array('id' => $pdfTemplateId));
        if (!$pdfTemplates || !$pdfTemplates->getHtmlContent()) {
            return;
        }

        $tplContent = $pdfTemplates->getHtmlContent();

        // parse blocks
        $tplContent = preg_replace(
            '/\[\[(BLOCK_[A-Z_0-9]+)\]\]/',
            '{\1}',
            $tplContent
        );
        \Cx\Modules\Block\Controller\Block::setBlocks($tplContent);
        $tplContent = $this->getComponent('Cache')->internalEsiParsing(
            $tplContent
        );

        \Cx\Core\MailTemplate\Controller\MailTemplate::substitute(
            $tplContent,
            $substitution,
            $convertToHtmlEntities
        );

        $fileName = $pdfTemplates->getFileName();
        if (!empty($fileName)) {
            \Cx\Core\MailTemplate\Controller\MailTemplate::substitute(
                $fileName,
                $substitution,
                $convertToHtmlEntities
            );
        } else {
            $datetime = $this->getComponent('DateTime')
                ->createDateTimeForUser('now')->format('d_m_Y_h_s_i');
            $fileName = $mailTplKey . '_' . $datetime;
        }

        $session = $this->getComponent('Session')->getSession();
        $pdf     = new \Cx\Core_Modules\Pdf\Model\Entity\PdfDocument();
        $pdf->SetTitle($fileName . '.pdf');
        $pdf->setContent($tplContent);
        $pdf->setDestination('F');
        $pdf->setFilePath($session->getTempPath() . '/' . $fileName . '.pdf');
        $pdf->Create();

        return array(
            'filePath' => $session->getWebTempPath() . '/' . $fileName . '.pdf',
            'fileName' => $fileName . '.pdf'
        );
    }

    /**
     * https://stackoverflow.com/questions/39120906/mpdf-use-another-font-without-editing-the-package-files
     */
    public function postComponentLoad()
    {
        if (
            defined('_MPDF_TTFONTPATH') ||
            defined('_MPDF_SYSTEM_TTFONTS_CONFIG')
        ) {
            return;
        }
        define(
            '_MPDF_TTFONTPATH',
            $this->cx->getWebsiteDocumentRootPath() . \Cx\Core\Core\Controller\Cx::FOLDER_NAME_MEDIA
                . '/Pdf/ttfonts/'
        );
        define(
            '_MPDF_SYSTEM_TTFONTS_CONFIG',
            $this->getDirectory() . '/Controller/clx_config.php'
        );
        if (
            defined('_MPDF_SYSTEM_TTFONTS')
        ) {
            return;
        }
        define(
            '_MPDF_SYSTEM_TTFONTS',
            ltrim(
                $this->cx->getLibraryFolderName(),
                '/'
            ) . '/mpdf/ttfonts/'
        );
    }
}

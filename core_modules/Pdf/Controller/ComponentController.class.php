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
        \Cx\Core\MailTemplate\Controller\MailTemplate::substitute(
            $tplContent,
            $substitution,
            $convertToHtmlEntities
        );

        $session    = $this->getComponent('Session')->getSession();
        $datetime   = $this->getComponent('DateTime')->createDateTimeForUser('now')->format('d_m_Y_h_s_i');
        $title      = $mailTplKey . '.pdf';
        $fileName   = $mailTplKey . '_' . $datetime . '.pdf';
        $pdf        = new \Cx\Core_Modules\Pdf\Model\Entity\PdfDocument();
        $pdf->SetTitle($title);
        $pdf->setContent($tplContent);
        $pdf->setDestination('F');
        $pdf->setFilePath($session->getTempPath() . '/' . $fileName);
        $pdf->Create();

        return array(
            'filePath' => $session->getWebTempPath() . '/' . $fileName,
            'fileName' => $title
        );
    }
}

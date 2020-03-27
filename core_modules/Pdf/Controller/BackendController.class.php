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
 * Specific BackendController for this Component.
 * Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_pdf
 */

namespace Cx\Core_Modules\Pdf\Controller;

/**
 * Specific BackendController for this Component.
 * Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_pdf
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {

    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands()
    {
        return array();
    }

    /**
     * Use this to parse your backend page
     *
     * You will get the template located in /View/Template/{CMD}.html
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array $cmd CMD separated by slashes
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false)
    {
        global $_ARRAYLANG, $objInit;

        $objTpl = new \Cx\Core\Html\Sigma(
            $this->getDirectory(true) . '/View/Template/Backend'
        );

        //merge language
        $langData   = $objInit->loadLanguageData('Pdf');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);
        $objTpl->setGlobalVariable($_ARRAYLANG);
        $objTpl->loadTemplatefile('Default.html');

        // Not an entity, parse overview or settings
        switch (current($cmd)) {
            default:
                // Parse entity view generation pages
                $entityClassName = $this->getNamespace() .
                    '\\Model\\Entity\\PdfTemplate';
                $this->parseEntityClassPage(
                    $objTpl,
                    $entityClassName,
                    'PdfTemplate'
                );
                if ($objTpl->blockExists('overview')) {
                    $objTpl->touchBlock('overview');
                }
                break;
        }

        \JS::registerCSS(
            substr(
                $this->getDirectory(false, true) . '/View/Style/Backend.css',
                1
            )
        );
        $template->setVariable(array(
            'CONTENT_TITLE' => $_ARRAYLANG['TXT_CORE_MODULE_PDF'],
            'ADMIN_CONTENT' => $objTpl->get(),
        ));
    }

    /**
     * This function returns the ViewGeneration options for a given entityClass
     *
     * @access protected
     * @global $_ARRAYLANG
     * @param $entityClassName contains the FQCN from entity
     * @param $dataSetIdentifier if $entityClassName is DataSet, this is used for better partition
     * @return array with options
     */
    protected function getViewGeneratorOptions($entityClassName, $dataSetIdentifier = '') {
        global $_ARRAYLANG;

        $classNameParts    = explode('\\', $entityClassName);
        $classIdentifier   = end($classNameParts);
        $placeholderPrefix = 'TXT_' . strtoupper(
            $this->getType() . '_' . $this->getName() .
            '_ACT_' . $classIdentifier
        );

        // list of mPdf placemarkers
        // see https://mpdf.github.io/what-else-can-i-do/replaceable-aliases.html
        $mPdfPlacemarkers = array(
            'nb',
            'nbpg',
            'PAGENO',
            'DATE\s.+',
            'colsum(?:\s\d+)?',
            'iteration\s[a-z0-9]+',
        );
        $mPdfPlacemarkersRegexp = '(' . join('|', $mPdfPlacemarkers) . ')';

        return array(
            'header'     => $_ARRAYLANG[$placeholderPrefix],
            'entityName' => $_ARRAYLANG[$placeholderPrefix . '_ENTITY'],
            'order' => array(
                'overview' => array(
                    'active',
                    'title'
                ),
            ),
            'functions' => array(
                'add'       => true,
                'edit'      => true,
                'delete'    => true,
                'sorting'   => true,
                'paging'    => true,
                'filtering' => false,
            ),
            'fields' => array(
                'id' => array(
                    'showOverview' => false,
                ),
                'title' => array(
                    'header' => $_ARRAYLANG[$placeholderPrefix . '_TITLE'],
                    'table'  => array(
                        'parse' => function($data, $rows, $options) {
                            $editUrl = \Cx\Core\Html\Controller\ViewGenerator::getVgEditUrl($options['functions']['vg_increment_number'], $rows['id']);
                            $link = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
                            $link ->setAttribute('href', $editUrl);
                            $link ->setAttribute('title', $data);
                            $value = new \Cx\Core\Html\Model\Entity\TextElement($data);
                            $link->addChild($value);
                            return $link;
                        },
                    ),
                ),
                'fileName' => array(
                    'showOverview' => false,
                    'header' => $_ARRAYLANG[$placeholderPrefix . '_FILENAME'],
                ),
                'active' => array(
                    'header'   => $_ARRAYLANG[$placeholderPrefix . '_STATE'],
                    'formtext' => $_ARRAYLANG[$placeholderPrefix . '_ACTIVE'],
                    'sorting'  => false,
                    'table'    => array(
                        'parse' => function($data, $rows) {
                            if ($data) {
                                return \Html::getLed('green');
                            }
                            return \Html::getLed('red');
                        },
                    ),
                ),
                'htmlContent' => array(
                    'header'       => $_ARRAYLANG[
                            $placeholderPrefix . '_HTML_CONTENT'
                        ] . '&nbsp;&nbsp;<span class="tooltip-trigger icon-info">'
                        . '</span><span class="tooltip-message">' .
                        $_ARRAYLANG[$placeholderPrefix . '_HTML_CONTENT_TOOLTIP'] .
                        '</span>',
                    'showOverview' => false,
                    'formfield'    => function (
                        $name,
                        $type,
                        $length,
                        $value,
                        $options
                    ) use ($mPdfPlacemarkersRegexp) {
                        // escape mPdf placemarkers
                        $regexp = '/\{' . $mPdfPlacemarkersRegexp . '\}/';
                        $value = preg_replace($regexp, '[[\1]]', $value);
                        $editor = new \Cx\Core\Wysiwyg\Wysiwyg(
                            $name,
                            contrexx_raw2xhtml($value),
                            'full'
                        );
                        $span   = new \Cx\Core\Html\Model\Entity\HtmlElement(
                            'span'
                        );
                        $span->addChild(
                            new \Cx\Core\Html\Model\Entity\TextElement($editor)
                        );
                        return $span;
                    },
                    'storecallback' => function($value) use ($mPdfPlacemarkersRegexp) {
                        // unescape mPdf placemarkers
                        $regexp = '/\[\[' . $mPdfPlacemarkersRegexp . '\]\]/';
                        return preg_replace($regexp, '{\1}', $value);
                    }
                ),
            ),
        );
    }
}

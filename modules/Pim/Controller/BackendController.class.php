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
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_pim
 */

namespace Cx\Modules\Pim\Controller;

/**
 * Specific BackendController for this Component. Use this to easily create a backend view
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_pim
 */
class BackendController extends \Cx\Core\Core\Model\Entity\SystemComponentBackendController {

    /**
     * Template object
     */
    protected $template;

    public function showOverviewPage() {
        return true;
    }

    /**
     * Returns a list of available commands (?act=XY)
     * @return array List of acts
     */
    public function getCommands() {
        return array('Price', 'VatRate');
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
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false) {
        // this class inherits from Controller, therefore you can get access to
        // Cx like this:
        $this->cx;
        $this->template = $template;
        $act = $cmd[0];

        /* If the act is not empty, we are not on the first tab an we can use parsePage() from
           SystemComponentBackendController to create the view.
           If act is empty, we are on first tab where parent::parsePage() will not work, because ViewGenerator does
           not support views on first tab of components.
           Note: This function (parsePage) can be removed as soon as ViewGenerator has first tab support
        */
        if ($act != '') {
            parent::parsePage($template, $cmd, $isSingle);
        } else {
            $this->parseEntityClassPage($template, 'Cx\Modules\Pim\Model\Entity\Product', 'Product');
        }

        \Message::show();
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

        $classNameParts = explode('\\', $entityClassName);
        $classIdentifier = end($classNameParts);

        $langVarName = 'TXT_' . strtoupper($this->getType() . '_' . $this->getName() . '_ACT_' . $classIdentifier);
        $header = '';
        if (isset($_ARRAYLANG[$langVarName])) {
            $header = $_ARRAYLANG[$langVarName];
        }
        switch ($entityClassName) {
            case 'Cx\Modules\Pim\Model\Entity\VatRate':
                return array(
                    'header' => $_ARRAYLANG['TXT_MODULE_PIM_ACT_VATRATE'],
                    'fields' => array(
                        'products'    => array(
                            'showOverview' => false,
                        ),
                        'rate'  => array(
                            'table' => array(
                                'parse' => function($value) {
                                    if (empty($value)) {
                                        return;
                                    }
                                    return $value . '%';
                                }
                            )
                        )
                    ),
                    'functions' => array(
                        'add'       => true,
                        'edit'      => true,
                        'delete'    => true,
                        'sorting'   => true,
                        'paging'    => true,
                        'filtering' => false,
                    ),
                );
                break;
            case 'Cx\Modules\Pim\Model\Entity\Product':
                return array(
                    'header'    => $_ARRAYLANG['TXT_MODULE_PIM_ACT_DEFAULT'],
                    'functions' => array(
                        'add'       => true,
                        'edit'      => true,
                        'delete'    => true,
                        'sorting'   => true,
                        'paging'    => true,
                        'filtering' => false,
                    ),
                    'fields'    => array(
                        'vatRate'  => array(
                            'table' => array(
                                'parse' => function($value) {
                                    if (empty($value)) {
                                        return;
                                    }
                                    $vatRate = $this->cx->getDb()->getEntityManager()->getRepository('Cx\Modules\Pim\Model\Entity\VatRate')->findOneBy(array('id' => $value ));
                                    return $vatRate->getRate(). '%';
                                },
                            ),
                            'formfield' => function($fieldname, $fieldtype, $fieldlength, $fieldvalue, $fieldoptions) {
                                global $_ARRAYLANG;

                                $vatRates        = $this->cx->getDb()->getEntityManager()->getRepository('Cx\Modules\Pim\Model\Entity\VatRate')->findAll();
                                $arrOptions['0'] = $_ARRAYLANG['TXT_MODULE_PIM_PLEASE_SELECT'];
                                foreach ( $vatRates as $vatRate) {
                                    $arrOptions[$vatRate->getId()] = $vatRate->getVatClass().' '. $vatRate->getRate() .'%';
                                }
                                $selectOption = new \Cx\Core\Html\Model\Entity\DataElement(
                                    $fieldname,
                                    \Html::getOptions(
                                        $arrOptions,
                                        $fieldvalue
                                    ),
                                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
                                );
                                return $selectOption;
                            },
                        ),
                    ),
                );
                break;
            case 'Cx\Modules\Pim\Model\Entity\Price':
                return array(
                    'header' => $_ARRAYLANG['TXT_MODULE_PIM_ACT_PRICE'],
                    'validate' => function ($formGenerator) {
                        // this validation checks whether already a price for the currency and product exists
                        $data = $formGenerator->getData()->toArray();

                        $currency = $data['currency'];
                        $product = $data['product'];
                        $priceRepository = \Env::get('cx')->getDb()->getEntityManager()->getRepository('Cx\Modules\Pim\Model\Entity\Price');
                        $prices =
                            $priceRepository->createQueryBuilder('p')
                                ->where('p.currency = ?1')->setParameter(1, $currency)
                                ->andWhere('p.product = ?2')->setParameter(2, $product);
                        $prices = $prices->getQuery()->getResult();
                        if (!empty($data['editid']) && count($prices) > 1) {
                            return false;
                        }
                        if (empty($data['editid']) && count($prices) > 0) {
                            return false;
                        }
                        return true;
                    },
                    'functions' => array(
                        'add' => true,
                        'edit' => true,
                        'delete' => true,
                        'sorting' => true,
                        'paging' => true,
                        'filtering' => false,
                    ),
                );
                break;
            default:
                return array(
                    'header' => $header,
                    'functions' => array(
                        'add'       => true,
                        'edit'      => true,
                        'delete'    => true,
                        'sorting'   => true,
                        'paging'    => true,
                        'filtering' => false,
                    ),
                );
        }
    }
}

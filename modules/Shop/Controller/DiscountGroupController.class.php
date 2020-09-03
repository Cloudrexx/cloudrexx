<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2018
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
 * OrderController to handle orders
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Controller;

/**
 * OrderController to handle orders
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class DiscountGroupController extends \Cx\Core\Core\Model\Entity\Controller
{
    /**
     * To be able to display the article and customer groups in a matrix, the
     * view has to be created by itself and cannot be implemented with the
     * ViewGenerator, as it expects an array of objects and not just values.
     *
     * @param \Cx\Core\Html\Sigma $template default backend template for this
     *                                      page
     * @param array $options parent options
     * @return \BackendTable generated BackendTable
     * @throws \Cx\Core\Html\Controller\ViewGeneratorException
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     */
    public function parsePage($template, $options)
    {
        global $_ARRAYLANG;

        $em = $this->cx->getDb()->getEntityManager();
        $discountGroupRepo = $em->getRepository(
            'Cx\Modules\Shop\Model\Entity\RelDiscountGroup'
        );
        // It is checked if this parameter exists in order to save the
        // RelDiscountGroup entity. Because this view was not built with
        // objects, this entity cannot be saved automatically.
        if ($_GET['storeRelDiscountGroup']) {
            $customerGroups = $this->cx->getRequest()->getParam(
                'customerGroup', false
            );

            $discountGroupRepo->storeDiscountCustomer($customerGroups);

            \Message::add(
                $_ARRAYLANG[
                    'TXT_SHOP_REL_DISCOUNT_GROUP_RECORDS_UPDATED_SUCCESSFUL'
                ]
            );
        }

        $customerGroups = $em->getRepository(
            'Cx\Modules\Shop\Model\Entity\CustomerGroup'
        )->findAll();
        $articleGroups =  $em->getRepository(
            'Cx\Modules\Shop\Model\Entity\ArticleGroup'
        )->findAll();

        // The table view is changed so that we can extend the action of the
        // form by the parameter "storeRelDiscountGroup", this is needed later
        // for saving
        $options['template']['table'] = $this->cx->getCodeBaseModulePath()
            . '/Shop/View/Template/Backend/DiscountGroup.html';
        $options['functions']['sorting'] = false;
        $options['functions']['edit'] = false;
        $options['functions']['delete'] = false;
        $options['functions']['editable'] = true;
        $options['fields']['articleGroupId']['showOverview'] = false;
        $options['fields']['articleGroup']['table']['parse'] = array(
            'adapter' => 'DiscountGroup',
            'method' => 'getLinkHeader'
        );

        // Set customerGroup name. Article Group title is defined in
        // the first row
        $options['fields']['articleGroup']['header'] = $_ARRAYLANG[
        'TXT_SHOP_CUSTOMER_GROUP'
        ];

        $arr = array();
        // First row to add title to article groups
        $arr[0] = array(
            'articleGroup' => $_ARRAYLANG['TXT_SHOP_ARTICLE_GROUP']
        );
        $prefix = 'customerGroup-';

        foreach ($articleGroups as $articleGroup) {
            $row['articleGroup'] = $articleGroup->getName();
            $row['articleGroupId'] = $articleGroup->getId();

            foreach ($customerGroups as $customerGroup) {
                // Find the matching RelDiscountGroup entity to get the rate
                $entity = $discountGroupRepo->findOneBy(
                    array(
                        'customerGroupId' => $customerGroup->getId(),
                        'articleGroup' => $articleGroup->getId(),
                    )
                );
                if (empty($entity)) {
                    $entity = new \Cx\Modules\Shop\Model\Entity\RelDiscountGroup();
                }
                $identifier = $prefix . $customerGroup->getId();
                $row[$identifier] = $entity->getRate();

                if (isset($arr[$identifier])) {
                    continue;
                }

                // Add link to header
                $link = new \Cx\Core\Html\Model\Entity\HtmlElement(
                    'a'
                );

                $url = \Cx\Core\Routing\Url::fromBackend(
                    'Shop', 'Customer/CustomerGroup'
                );
                $editUrl = \Cx\Core\Html\Controller\ViewGenerator::getVgEditUrl(
                    0, $customerGroup->getId(), $url
                );
                $link->setAttribute('href', $editUrl);
                $link->addChild(
                    new \Cx\Core\Html\Model\Entity\TextElement(
                        $customerGroup->getName()
                    )
                );
                $options['fields'][$identifier]['header'] = $link;
                $options['fields'][$identifier]['editable'] = true;
                $options['fields'][$identifier]['table']['parse'] = array(
                    'adapter' => 'DiscountGroup',
                    'method' => 'getRateInput'
                );

                // To prevent notices
                $row['virtual'] = false;

                // Add empty string for first row
                $arr[0][$identifier] = '';
            }

            $arr[] = $row;
        }

        $entities = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($arr);

        // We have to add the ViewGenerator to use the editable function in the
        // overview
        $view = new \BackendTable(
            $entities,
            $options,
            '',
            new \Cx\Core\Html\Controller\ViewGenerator($entities)
        );

        $template->setVariable('ENTITY_VIEW', $view->toHtml());

        return $view;
    }
}
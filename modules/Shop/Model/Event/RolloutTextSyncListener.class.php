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
 * Temporary controller for Shop to sync \Text with \Gedmo\Translatable, until
 * code in Shop is updated
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
namespace Cx\Modules\Shop\Model\Event;

/**
 * Temporary controller for Shop to sync \Text with \Gedmo\Translatable, until
 * code in Shop is updated
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class RolloutTextSyncListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{
    protected $mappedAttributes =
        array(
            'category_description' => array(
                'entity' => 'Category',
                'attr' => 'description'
            ),
            'category_name' => array(
                'entity' => 'Category',
                'attr' => 'name'
            ),
            'category_short_description' => array(
                'entity' => 'Category',
                'attr' => 'shortDescription'
            ),
            'currency_name' => array(
                'entity' => 'Currency',
                'attr' => 'name'
            ),
            'discount_group_article' => array(
                'entity' => 'ArticleGroup',
                'attr' => 'name'
            ),
            'discount_group_customer' => array(
                'entity' => 'CustomerGroup',
                'attr' => 'name'
            ),
            'discount_group_name' => array(
                'entity' => 'DiscountgroupCountName',
                'attr' => 'name'
            ),
            'discount_group_unit' => array(
                'entity' => 'DiscountgroupCountName',
                'attr' => 'name'
            ),
            'product_code' => array(
                'entity' => 'Product',
                'attr' => 'code'
            ),
            'product_keys' => array(
                'entity' => 'Product',
                'attr' => 'keys'
            ),
            'product_long' => array(
                'entity' => 'Product',
                'attr' => 'long'
            ),
            'product_name' => array(
                'entity' => 'Product',
                'attr' => 'name'
            ),
            'product_short' => array(
                'entity' => 'Product',
                'attr' => 'short'
            ),
            'product_uri' => array(
                'entity' => 'Product',
                'attr' => 'uri'
            ),
        );

    /**
     * Update translatable entities with doctrine
     *
     * @param $replaceInfo array info includes id of entity, key and value of
     *                           \Text
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function tmpShopTextReplace($replaceInfo)
    {
        $id = $replaceInfo[0];
        $key = $replaceInfo[1];
        $value = $replaceInfo[2];

        $entityAndAttr = $this->getEntityNameAndAttr($key);
        $setter = 'set' . $entityAndAttr['attrName'];

        $em = $this->cx->getDb()->getEntityManager();
        $repo = $em->getRepository(
            $entityAndAttr['entityName']
        );

        // Only entities with a identifier named id affected
        $entity = $repo->find($id);
        $entity->$setter($value);

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Delete entries from translatable entities
     *
     * @param $deleteInfo
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function tmpShopTextDelete($deleteInfo)
    {
        $id = $deleteInfo[0];
        $key = $deleteInfo[1];

        $em = $this->cx->getDb()->getEntityManager();
        $entityAndAttr = $this->getEntityNameAndAttr($key);

        $entities = $em->getRepository(
            '\Cx\Core\Locale\Model\Entity\Translation'
        )->findBy(
            array(
                'objectClass' => $entityAndAttr['entityName'],
                'field' => $entityAndAttr['attrName'],
                'foreignKey' => $id
            )
        );

        if (!$entities) {
            return;
        }
        foreach ($entities as $entity) {
            $em->remove($entity);
        }

        $em->flush();
    }

    /**
     * Get the entity namespace and attribute name from key
     *
     * @param $key array include key of \Text
     * @return array
     */
    protected function getEntityNameAndAttr($key)
    {
        if (array_key_exists($key, $this->mappedAttributes)) {
            $entityName = 'Cx\\Modules\Shop\\Model\\Entity\\'.
                $this->mappedAttributes[$key]['entity'];
            $attrName = $this->mappedAttributes[$key]['attr'];
        } else {
            $keyFragments = explode('_', $key);
            $entityName = 'Cx\\Modules\Shop\\Model\\Entity\\'. ucfirst(
                $keyFragments[0]
            );
            $attrName = $keyFragments[1];
        }
        $attrName = \Doctrine\Common\Inflector\Inflector::classify($attrName);
        return array('entityName' => $entityName, 'attrName' => $attrName);
    }
}
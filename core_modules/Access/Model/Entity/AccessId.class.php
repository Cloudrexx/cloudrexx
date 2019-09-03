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


namespace Cx\Core_Modules\Access\Model\Entity;

/**
 * Cx\Core_Modules\Access\Model\Entity\AccessId
 */
class AccessId extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $entity_class_name
     */
    private $entity_class_name;

    /**
     * @var string $entity_class_id
     */
    private $entity_class_id;

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set entity_class_name
     *
     * @param string $entityClassName
     */
    public function setEntityClassName($entityClassName)
    {
        $this->entity_class_name = $entityClassName;
    }

    /**
     * Get entity_class_name
     *
     * @return string $entityClassName
     */
    public function getEntityClassName()
    {
        return $this->entity_class_name;
    }

    /**
     * Set entity_class_id
     *
     * @param string $entityClassId
     */
    public function setEntityClassId($entityClassId)
    {
        $this->entity_class_id = $entityClassId;
    }

    /**
     * Get entity_class_id
     *
     * @return string $entityClassId
     */
    public function getEntityClassId()
    {
        return $this->entity_class_id;
    }
}

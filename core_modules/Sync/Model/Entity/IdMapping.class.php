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
 * IdMapping
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */

namespace Cx\Core_Modules\Sync\Model\Entity;

/**
 * IdMapping
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */
class IdMapping extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $foreignHost
     */
    protected $foreignHost;

    /**
     * @var string $entityType
     */
    protected $entityType;

    /**
     * @var integer $foreignId
     */
    protected $foreignId;

    /**
     * @var integer $localId
     */
    protected $localId;


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
     * Set foreignHost
     *
     * @param string $foreignHost
     */
    public function setForeignHost($foreignHost)
    {
        $this->foreignHost = $foreignHost;
    }

    /**
     * Get foreignHost
     *
     * @return string $foreignHost
     */
    public function getForeignHost()
    {
        return $this->foreignHost;
    }

    /**
     * Set entityType
     *
     * @param string $entityType
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;
    }

    /**
     * Get entityType
     *
     * @return string $entityType
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * Set foreignId
     *
     * @param integer $foreignId
     */
    public function setForeignId($foreignId)
    {
        $this->foreignId = $foreignId;
    }

    /**
     * Get foreignId
     *
     * @return integer $foreignId
     */
    public function getForeignId()
    {
        return $this->foreignId;
    }

    /**
     * Set localId
     *
     * @param integer $localId
     */
    public function setLocalId($localId)
    {
        $this->localId = $localId;
    }

    /**
     * Get localId
     *
     * @return integer $localId
     */
    public function getLocalId()
    {
        return $this->localId;
    }
}

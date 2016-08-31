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
 * HostEntity
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */

namespace Cx\Core_Modules\Sync\Model\Entity;

/**
 * HostEntity
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */
class HostEntity extends \Cx\Model\Base\EntityBase
{
    /**
     * @var string $entityId
     */
    protected $entityId;

    /**
     * @var Cx\Core_Modules\Sync\Model\Entity\Sync
     */
    protected $sync;

    /**
     * @var Cx\Core_Modules\Sync\Model\Entity\Host
     */
    protected $host;

    /**
     * Set entityId
     *
     * @param string $entityId
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
    }

    /**
     * Get entityId
     *
     * @return string $entityId
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set sync
     *
     * @param Cx\Core_Modules\Sync\Model\Entity\Sync $sync
     */
    public function setSync(\Cx\Core_Modules\Sync\Model\Entity\Sync $sync)
    {
        $this->sync = $sync;
    }

    /**
     * Get sync
     *
     * @return Cx\Core_Modules\Sync\Model\Entity\Sync $sync
     */
    public function getSync()
    {
        return $this->sync;
    }

    /**
     * Set host
     *
     * @param Cx\Core_Modules\Sync\Model\Entity\Host $host
     */
    public function setHost(\Cx\Core_Modules\Sync\Model\Entity\Host $host)
    {
        $this->host = $host;
    }

    /**
     * Get host
     *
     * @return Cx\Core_Modules\Sync\Model\Entity\Host $host
     */
    public function getHost()
    {
        return $this->host;
    }
    /**
     * @var integer $syncId
     */
    protected $syncId;


    /**
     * Set syncId
     *
     * @param integer $syncId
     */
    public function setSyncId($syncId)
    {
        $this->syncId = $syncId;
    }

    /**
     * Get syncId
     *
     * @return integer $syncId
     */
    public function getSyncId()
    {
        return $this->syncId;
    }
    /**
     * @var integer $hostId
     */
    protected $hostId;


    /**
     * Set hostId
     *
     * @param integer $hostId
     */
    public function setHostId($hostId)
    {
        $this->hostId = $hostId;
    }

    /**
     * Get hostId
     *
     * @return integer $hostId
     */
    public function getHostId()
    {
        return $this->hostId;
    }
}

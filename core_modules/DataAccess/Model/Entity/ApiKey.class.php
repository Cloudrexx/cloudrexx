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
 * ApiKey
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_dataaccess
 */

namespace Cx\Core_Modules\DataAccess\Model\Entity;

/**
 * ApiKey
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_dataaccess
 */

class ApiKey extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $apiKey
     */
    protected $apiKey;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $dataAccessApiKeys;

    public function __construct()
    {
        $this->dataAccessApiKeys = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * Set apiKey
     *
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Get apiKey
     *
     * @return string $apiKey
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Add dataAccessApiKeys
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey $dataAccessApiKeys
     * @return ApiKey
     */
    public function addDataAccessApiKey(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey $dataAccessApiKeys)
    {
        $this->dataAccessApiKeys[] = $dataAccessApiKeys;

        return $this;
    }

    /**
     * Remove dataAccessApiKeys
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey $dataAccessApiKeys
     */
    public function removeDataAccessApiKey(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey $dataAccessApiKeys)
    {
        $this->dataAccessApiKeys->removeElement($dataAccessApiKeys);
    }

    /**
     * Add dataAccessApiKeys
     *
     * @param Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey $dataAccessApiKeys
     */
    public function addDataAccessApiKeys(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey $dataAccessApiKeys)
    {
        $this->dataAccessApiKeys[] = $dataAccessApiKeys;
    }

    /**
     * Get dataAccessApiKeys
     *
     * @return Doctrine\Common\Collections\Collection $dataAccessApiKeys
     */
    public function getDataAccessApiKeys()
    {
        return $this->dataAccessApiKeys;
    }
}

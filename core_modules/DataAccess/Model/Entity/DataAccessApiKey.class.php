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
 * DataAccessApiKey
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_dataaccess
 */

namespace Cx\Core_Modules\DataAccess\Model\Entity;

/**
 * DataAccessApiKey
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_dataaccess
 */
class DataAccessApiKey extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer id
     */
    protected $id;

    /**
     * @var boolean $readOnly
     */
    protected $readOnly;

    /**
     * @var \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey
     */
    protected $apiKey;

    /**
     * @var \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess
     */
    protected $dataAccess;


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
     * Set readOnly
     *
     * @param boolean $readOnly
     */
    public function setReadOnly($readOnly)
    {
        $this->readOnly = $readOnly;
    }

    /**
     * Get readOnly
     *
     * @return boolean $readOnly
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Set apiKey
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey $apiKey
     */
    public function setApiKey(\Cx\Core_Modules\DataAccess\Model\Entity\ApiKey $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Get apiKey
     *
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey $apiKey
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set dataAccess
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess
     */
    public function setDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess)
    {
        $this->dataAccess = $dataAccess;
    }

    /**
     * Get dataAccess
     *
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess
     */
    public function getDataAccess()
    {
        return $this->dataAccess;
    }

    /**
     * Show the name of the related DataAccess
     * @return string Display name of this entity
     */
    public function __toString(): string {
        return $this->getDataAccess()->getName();
    }
}

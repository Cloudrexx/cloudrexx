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
 * ApiKeyRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_dataaccess
 */

namespace Cx\Core_Modules\DataAccess\Model\Repository;

/**
 * ApiKeyRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_dataaccess
 */
class ApiKeyRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Add or edit an existing DataAccessApiKey entry.
     *
     * @param $apiKey        int  id of ApiKey
     * @param $dataAccessId  int  id of DataAccess
     * @param bool $readOnly bool if the API key has read-only access.
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function addNewDataAccessApiKey(
        $apiKey, $dataAccessId, $readOnly = false
    ) {
        $repoDataAccess = $this->_em->getRepository(
            'Cx\Core_Modules\DataAccess\Model\Entity\DataAccess'
        );
        $repoDataAccessApiKey = $this->_em->getRepository(
            'Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey'
        );

        $apiKey = $this->find($apiKey);

        // Find existing DataAccessApiKey
        $dataAccessApiKey = $repoDataAccessApiKey->findOneBy(
            array(
                'apiKey' => $apiKey->getId(),
                'dataAccess' => $dataAccessId
            )
        );

        if (!$dataAccessApiKey) {
            $dataAccessApiKey = new \Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey();
        }
        $dataAccess = $repoDataAccess->find($dataAccessId);
        $dataAccessApiKey->setApiKey($apiKey);
        $dataAccessApiKey->setReadOnly($readOnly);
        $dataAccessApiKey->setDataAccess($dataAccess);
        $this->_em->persist($dataAccessApiKey);

        $this->_em->flush();
    }

    /**
     * Delete all DataAccessApiKey entries that do not have a DataAccess entry
     * located in the $dataAccessIds array.
     *
     * @param $apiKey        int   id of ApiKey
     * @param $dataAccessIds array Ids of DataAccess entries that still exist
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeOldDataAccessApiKeys(
        $apiKey, $dataAccessIds
    ) {
        $repoDataAccessApiKey = $this->_em->getRepository(
            'Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey'
        );

        $allDataAccessApiKeys = $repoDataAccessApiKey->findAll();

        foreach ($allDataAccessApiKeys as $dataAccessApiKey) {
            if (
                $dataAccessApiKey->getApiKey()->getId() == $apiKey &&
                !in_array(
                    $dataAccessApiKey->getDataAccess()->getId(), $dataAccessIds
                )
            ) {
                $this->_em->remove($dataAccessApiKey);
            }
        }

        $this->_em->flush();
    }
}

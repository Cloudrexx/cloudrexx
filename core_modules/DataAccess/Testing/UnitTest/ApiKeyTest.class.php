<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * Test ApiKey
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_module_data_access
 */
namespace Cx\Core_Modules\DataAccess\Testing\UnitTest;

/**
 * Test ApiKey
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_module_data_access
 */
class ApiKeyTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase
{
    /**
     * Get an ApiKey entity with test data.
     *
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey API-Key entity
     */
    protected function getTestApiKey()
    {
        $entity = new \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey();
        $entity->setApiKey('test');

        return $entity;
    }

    /**
     * Get an DataAccessApiKey entity with test data.
     *
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey DataAccessApiKey entity
     */
    protected function getTestDataAccessApiKey($apiKeyEntity)
    {
        $entity = new \Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey();
        $dataAccesEntity = new \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess();

        $permissionRead = new \Cx\Core_Modules\Access\Model\Entity\Permission();
        $permissionWrite = new \Cx\Core_Modules\Access\Model\Entity\Permission();
        $permissionRead->setVirtual(false);
        $permissionWrite->setVirtual(false);

        $dataAccesEntity->setName('test');
        $dataAccesEntity->setAccessCondition(array());
        $dataAccesEntity->setFieldList(array());
        $dataAccesEntity->setAllowedOutputMethods(array());
        $dataAccesEntity->setDataSource($this->getDataSource());
        $dataAccesEntity->setReadPermission($permissionRead);
        $dataAccesEntity->setWritePermission($permissionWrite);

        parent::$em->persist($apiKeyEntity);
        parent::$em->persist($dataAccesEntity);
        parent::$em->persist($permissionRead);
        parent::$em->persist($permissionWrite);

        $entity->setApiKey($apiKeyEntity);
        $entity->setDataAccess($dataAccesEntity);
        $entity->setReadOnly(true);

        parent::$em->persist($entity);

        return $entity;
    }

    /**
     * Get a DataSource
     *
     * @return \Cx\Core\DataSource\Model\Entity\DataSource
     */
    protected function getDataSource()
    {
        $dataSource = parent::$em->getRepository(
            'Cx\Core\DataSource\Model\Entity\DataSource'
        )->find(1);

        return $dataSource;
    }

    /**
     * Store the given ApiKey entity in the database.
     *
     * @param $entity \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey API-Key to save
     */
    protected function saveApiKey($entity)
    {
        parent::$em->persist($entity);
        parent::$em->flush();
    }

    /**
     * Delete the given ApiKey entity from the database.
     *
     * @param $entity \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey API-Key to delete
     */
    protected function deleteApiKey($entity)
    {
        parent::$em->remove($entity);
        parent::$em->flush();
    }

    /**
     * Find the ApiKey entity by given API-Key.
     *
     * @param $apiKey string api-key to identify entity
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey found entity
     */
    protected function findApiKey($apiKey)
    {
        $repo = parent::$em->getRepository(
            'Cx\Core_Modules\DataAccess\Model\Entity\ApiKey'
        );
        return $repo->findOneBy(array('apiKey' => $apiKey));
    }

    /**
     * Test if an ApiKey entity can be created.
     *
     * @coversNothing
     */
    public function testCreateApiKey()
    {
        $entity = $this->getTestApiKey();
        $apiKey = $entity->getApiKey();
        $this->saveApiKey($entity);

        $this->assertSame($entity, $this->findApiKey($apiKey));
    }

    /**
     * Test if an ApiKey entity can be edited.
     *
     * @coversNothing
     */
    public function testEditApiKey()
    {
        $newApiKey = 'testEdit';

        $entity = $this->getTestApiKey();
        $this->saveApiKey($entity);

        $entity->setApiKey($newApiKey);
        $this->saveApiKey($entity);

        $editedEntity = $this->findApiKey($newApiKey);

        $this->assertInstanceOf(
            'Cx\Core_Modules\DataAccess\Model\Entity\ApiKey',
            $editedEntity
        );
    }

    /**
     * Test if an ApiKey entity can be deleted.
     *
     * @coversNothing
     */
    public function testDeleteApiKey()
    {
        $entity = $this->getTestApiKey();
        $apiKey = $entity->getApiKey();
        $this->saveApiKey($entity);
        $this->deleteApiKey($entity);

        $this->assertNull($this->findApiKey($apiKey));
    }

    /**
     * Saving an identical entity twice to test if an exception is thrown.
     *
     * @coversNothing
     */
    public function testToSaveDuplicatedApiKey()
    {
        $entity = $this->getTestApiKey();
        $entityWithSameKey = $this->getTestApiKey();
        $this->saveApiKey($entity);
        $this->expectException(
            \Cx\Core\Error\Model\Entity\ShinyException::class
        );
        $this->saveApiKey($entityWithSameKey);
    }

    /**
     * Test if an DataAccess can be added.
     *
     * @coversNothing
     */
    public function testAddDataAccess()
    {
        $entity = $this->getTestApiKey();
        $apiKey = $entity->getApiKey();

        $dataAccessApiKey = $this->getTestDataAccessApiKey($entity);
        $entity->addDataAccessApiKey($dataAccessApiKey);
        $this->saveApiKey($entity);

        $storedEntity = $this->findApiKey($apiKey);
        $firstDataAccess = $storedEntity->getDataAccessApiKeys()->current();

        $this->assertInstanceOf(
            'Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey',
            $firstDataAccess
        );
    }

    /**
     * Test if an DataAccess can be removed.
     *
     * @coversNothing
     */
    public function testRemoveDataAccess()
    {
        $entity = $this->getTestApiKey();
        $apiKey = $entity->getApiKey();

        $dataAccessApiKey = $this->getTestDataAccessApiKey($entity);
        $entity->addDataAccessApiKey($dataAccessApiKey);
        $this->saveApiKey($entity);

        $storedEntity = $this->findApiKey($apiKey);
        $firstDataAccess = $storedEntity->getDataAccessApiKeys()->current();
        $storedEntity->removeDataAccessApiKey($firstDataAccess);
        $this->saveApiKey($storedEntity);
        $count = $storedEntity->getDataAccessApiKeys()->count();

        $this->assertEquals(0, $count);
    }
}
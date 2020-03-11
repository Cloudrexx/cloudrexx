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
 * Test DataAccess
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_module_data_access
 */
namespace Cx\Core_Modules\DataAccess\Testing\UnitTest;

/**
 * Test DataAccess
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_module_data_access
 */
class DataAccessTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase
{
    /**
     * Get a DataAccess entity with test data.
     *
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess DataAccess
     *                                                             entity
     * @throws \Cx\Core_Modules\Access\Model\Entity\PermissionException
     */
    protected function getTestDataAccess()
    {
        $entity = new \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess();
        $permissionRead = new \Cx\Core_Modules\Access\Model\Entity\Permission();
        $permissionWrite = new \Cx\Core_Modules\Access\Model\Entity\Permission();
        $permissionRead->setVirtual(false);
        $permissionWrite->setVirtual(false);

        $entity->setName('test');
        $entity->setAccessCondition(array());
        $entity->setFieldList(array());
        $entity->setAllowedOutputMethods(array());
        $entity->setDataSource($this->getDataSource());
        $entity->setReadPermission($permissionRead);
        $entity->setWritePermission($permissionWrite);

        static::$em->persist($permissionRead);
        static::$em->persist($permissionWrite);

        return $entity;
    }

    /**
     * Get a DataAccessApiKey entity with test data.
     *
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey Data
     *                                                      AccessApiKey entity
     */
    protected function getTestDataAccessApiKey($dataAccesEntity)
    {
        $entity = new \Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey();
        $apiKeyEntity = new \Cx\Core_Modules\DataAccess\Model\Entity\ApiKey();

        $apiKeyEntity->setApiKey('test');

        $entity->setApiKey($apiKeyEntity);
        $entity->setDataAccess($dataAccesEntity);
        $entity->setReadOnly(true);

        static::$em->persist($entity);
        static::$em->persist($apiKeyEntity);
        static::$em->persist($dataAccesEntity);

        return $entity;
    }

    /**
     * Get a DataSource
     *
     * @return \Cx\Core\DataSource\Model\Entity\DataSource
     */
    protected function getDataSource()
    {
        $dataSource = static::$em->getRepository(
            'Cx\Core\DataSource\Model\Entity\DataSource'
        )->find(1);

        return $dataSource;
    }

    /**
     * Store the given DataAccess entity in the database.
     *
     * @param $entity \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess
     *                                                        DataAccess to save
     * @throws \Doctrine\ORM\OptimisticLockException handle orm interactions
     */
    protected function saveDataAccess($entity)
    {
        static::$em->persist($entity);
        static::$em->flush();
    }

    /**
     * Find the DataAccess entity by given ID.
     *
     * @param $id int ID to identify entity
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess found entity
     */
    protected function findDataAccess($id)
    {
        $repo = static::$em->getRepository(
            'Cx\Core_Modules\DataAccess\Model\Entity\DataAccess'
        );

        return $repo->find($id);
    }

    /**
     * Find the DataAccess entity by given name.
     *
     * @param $name string name to identify entity
     * @return \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess found entity
     */
    protected function findDataAccessByName($name)
    {
        $repo = static::$em->getRepository(
            'Cx\Core_Modules\DataAccess\Model\Entity\DataAccess'
        );

        return $repo->findOneBy(array('name' => $name));
    }

    /**
     * Get DataAccess Json Controller.
     *
     * @return \Cx\Core_Modules\DataAccess\Controller\JsonDataAccessController
     */
    protected function getJsonController()
    {
        $componentRepo = static::$em->getRepository(
            'Cx\Core\Core\Model\Entity\SystemComponent'
        );
        $componentContoller = $componentRepo->findOneBy(
            array('name' => 'DataAccess')
        );

        return $componentContoller->getController('JsonDataAccess');
    }

    /**
     * Test if 'DataAccess' will be returned.
     *
     * @covers \Cx\Core_Modules\DataAccess\Controller\JsonDataAccessController::getName
     */
    public function testGetName()
    {
        $jsonDataAccess = $this->getJsonController();
        $name = $jsonDataAccess->getName();

        $this->assertEquals('DataAccess', $name);
    }

    /**
     * Test if a string will be returned.
     *
     * @covers \Cx\Core_Modules\DataAccess\Controller\JsonDataAccessController::getMessageAsString
     */
    public function testGetMessageAsString()
    {
        $jsonDataAccess = $this->getJsonController();
        $message = $jsonDataAccess->getMessagesAsString();

        $this->assertIsString('string', $message);
    }

    /**
     * Test if the returned permission object matches the defined permission
     * object.
     *
     * @covers \Cx\Core_Modules\DataAccess\Controller\JsonDataAccessController::getDefaultPermissions
     */
    public function testGetDefaultPermissions()
    {
        $default = new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array('http', 'https'),
            array('get', 'post', 'cli'),
            false,
            array(),
            array()
        );

        $jsonDataAccess = $this->getJsonController();
        $permission = $jsonDataAccess->getDefaultPermissions();

        $this->assertEquals($default, $permission);
    }

    /**
     * Test if all necessary method names are in the array.
     *
     * @covers \Cx\Core_Modules\DataAccess\Controller\JsonDataAccessController::getAccessableMethods
     */
    public function testGetAccessableMethods()
    {
        $jsonDataAccess = $this->getJsonController();
        $methodNames = $jsonDataAccess->getAccessableMethods();

        $this->assertIsArray($methodNames);
    }

    /**
     * Test if an HTML-Element is returned so that it can be displayed
     * automatically by the ViewGenerator.
     *
     * @covers \Cx\Core_Modules\DataAccess\Controller\JsonDataAccessController::getAccessCondition
     */
    public function testGetAccessCondition()
    {
        $args = array(
            'name' => 'test_element',
            'id' => 1,
        );

        $jsonDataAccess = $this->getJsonController();
        $htmlElement = $jsonDataAccess->getAccessCondition(
            $args
        );

        $this->assertInstanceOf(
            'Cx\Core\Html\Model\Entity\HtmlElement',
            $htmlElement
        );
    }

    /**
     * Test if an HTML-Element is returned so that it can be displayed
     * automatically by the ViewGenerator.
     *
     * @covers \Cx\Core_Modules\DataAccess\Controller\JsonDataAccessController::getDataAccessPermission
     */
    public function testGetDataAccessPermission()
    {
        $args = array(
            'name' => 'test_element',
            'value' => new \Cx\Core_Modules\Access\Model\Entity\Permission(),
        );

        $jsonDataAccess = $this->getJsonController();
        $htmlElement = $jsonDataAccess->getDataAccessPermission(
            $args
        );

        $this->assertInstanceOf(
            'Cx\Core\Html\Model\Entity\HtmlElement',
            $htmlElement
        );
    }

    /**
     * Test if an HTML-Element is returned so that it can be displayed
     * automatically by the ViewGenerator.
     *
     * @covers \Cx\Core_Modules\DataAccess\Controller\JsonDataAccessController::getFieldListSearch
     */
    public function testGetFieldListSearch()
    {
        $args = array(
            'name' => 'test_element',
            'id' => 1
        );

        $jsonDataAccess = $this->getJsonController();
        $htmlElement = $jsonDataAccess->getFieldListSearch(
            $args
        );

        $this->assertInstanceOf(
            'Cx\Core\Html\Model\Entity\DataElement',
            $htmlElement
        );
    }

    /**
     * Test if an entity can be edited.
     *
     * @coversNothing
     */
    public function testEditDataAccess()
    {
        $newName = 'testEdit';

        $entity = $this->getTestDataAccess();
        $this->saveDataAccess($entity);

        $entity->setName($newName);
        $this->saveDataAccess($entity);

        $editedEntity = $this->findDataAccess($entity->getId());

        $this->assertEquals($newName, $editedEntity->getName());
    }

    /**
     * Test if an ApiKey can be added.
     *
     * @coversNothing
     */
    public function testAddApiKey()
    {
        $entity = $this->getTestDataAccess();

        $dataAccessApiKey = $this->getTestDataAccessApiKey($entity);
        $entity->addDataAccessApiKey($dataAccessApiKey);
        $this->saveDataAccess($entity);

        $storedEntity = $this->findDataAccess($entity->getId());
        $firstElement = $storedEntity->getDataAccessApiKeys()->current();

        $this->assertInstanceOf(
            'Cx\Core_Modules\DataAccess\Model\Entity\DataAccessApiKey',
            $firstElement
        );
    }

    /**
     * Test if an ApiKey can be removed.
     *
     * @coversNothing
     */
    public function testRemoveApiKey()
    {
        $entity = $this->getTestDataAccess();
        $name = $entity->getName();

        $dataAccessApiKey = $this->getTestDataAccessApiKey($entity);
        $entity->addDataAccessApiKey($dataAccessApiKey);
        $this->saveDataAccess($entity);

        $storedEntity = $this->findDataAccessByName($name);
        $firstDataAccess = $storedEntity->getDataAccessApiKeys()->current();
        $storedEntity->removeDataAccessApiKey($firstDataAccess);
        $this->saveDataAccess($storedEntity);
        $count = $storedEntity->getDataAccessApiKeys()->count();

        $this->assertEquals(0, $count);
    }

}
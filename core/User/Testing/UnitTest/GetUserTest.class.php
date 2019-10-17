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
 * GetUserTest
 *
 * @copyright   Cloudrexx AG
 * @author      Hava Fuga <info@cloudrexx.com>, Mirjam Doyon <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */

namespace Cx\Core\User\Testing\UnitTest;

use phpDocumentor\Reflection\DocBlock\Tags\Uses;
use function JmesPath\search;

/**
 * Test GetUser
 *
 * @copyright   Cloudrexx AG
 * @author      Hava Fuga <info@cloudrexx.com>, Mirjam Doyon <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
class GetUserTest extends \Cx\Core\Test\Model\Entity\MySQLTestCase
{

    public function testOneUserById() {
        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;
        $user->reset();
        $user->setEmail('test@testmail.com');
        $user->store();

        $this->assertEquals(
            $user->getId(),
            $user->getUsers($user->getId())->getId()
        );
    }


    public function testOneUserByEmail() {
        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;
        $user->reset();
        $user->setEmail('test1@testmail.com');
        $user->store();

        $this->assertEquals(
            $user->getEmail(),
            $user->getUsers($user->getId())->getEmail()
        );
    }
    /**
     * Test One User By Name
     *
     * Search for a given Username
     * @author      Mirjam Doyon <info@cloudrexx.com>
     * @return      void
     *
     */
    public function testOneUserByUsername() {
        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;
        $user->reset();
        $user->setEmail('testerson@testmail.com');
        $user->setUsername('Testerson');
        $user->store();
        //determine test User
        $myTestUser = array('username'=>'Testerson');
        //check if test User is found
        $this->assertEquals(
            $user->getUsername(),
            $user->getUsers($myTestUser)->getUsername()
        );
    }
    /**
     * Test UserAmount
     *
     * Count the amount of Users and check if any are missing
     * @author      Mirjam Doyon <info@cloudrexx.com>
     * @return       void
     */
    public function testUserAmount() {
        //counter for offset
        $offset = 0;
        //counter for Users
        $userCount = 0;

        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        //count existing Users in DB
        $users = $user->getUsers();
        while (!$users->EOF){
            $offset++;
            $users->next();
        }
        $user->reset();
        $user->setUsername('One');
        $user->setEmail('test1@testmail.com');
        $user->store();
        $user->reset();
        $user->setUsername('Two');
        $user->setEmail('test2@testmail.com');
        $user->store();
        $user->reset();
        $user->setUsername('Three');
        $user->setEmail('test3@testmail.com');
        $user->store();
        $user->reset();
        $user->setUsername('Four');
        $user->setEmail('test4@testmail.com');
        $user->store();
        //count all Users and ignore previously existing entries ($offset)
        $users = $user->getUsers(
            null,
            null,
            null,
            null,
            5,
            $offset
        );
        while (!$users->EOF){
            $userCount++;
            $users->next();
        }
        //check if there are four test Users
        $this->assertEquals(
            4,
            $userCount
        );
    }

    /**
     * Test Users By Birthdate
     *
     * Search for a given Username
     * @author      Mirjam Doyon <info@cloudrexx.com>
     * @return      void
     *
     */
    public function testUsersByBirthdate() {

    }
    /**
     * Test Find User By First Letter
     *
     * Search for a given Username
     * @author      Mirjam Doyon <info@cloudrexx.com>
     * @return      void
     *
     */
    public function testFindUserByFirstLetter() {

    }
    /**
     * Test Find User By First Letter Of First And Lastname
     *
     * Search for a given Username
     * @author      Mirjam Doyon <info@cloudrexx.com>
     * @return      void
     *
     */
    public function testFindUserByFirstLetterOfFirstAndLastname() {

    }
    /**
     * Test Get List Of Users By Limit
     *
     * Get a List of Users with a Limit of 3
     * @author      Mirjam Doyon <info@cloudrexx.com>
     * @return      void
     *
     */
    public function testGetListOfUsersByLimit() {

    }
}

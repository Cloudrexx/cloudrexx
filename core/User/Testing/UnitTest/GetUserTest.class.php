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
        $id = $user->getId();

        $this->assertEquals(
            $user->getUser($id),
            $user->getUsers($id)
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

    public function testOneUserByName() {
        $user = new \Cx\Core\User\Model\Entity\User;
        $user->setUsername('Testerson');

        $this->assertEquals('Testerson', $user->getUsername());


    }

    public function testAllUsers() {
        $user1 = new \Cx\Core\User\Model\Entity\User;
        $user1->setId(1);
        $user2 = new \Cx\Core\User\Model\Entity\User;
        $user2->setId(2);
        $user3 = new \Cx\Core\User\Model\Entity\User;
        $user3->setId(3);
        $user4 = new \Cx\Core\User\Model\Entity\User;
        $user4->setId(4);

        $this->assertCount(4, [ 'Test']);

    }

}

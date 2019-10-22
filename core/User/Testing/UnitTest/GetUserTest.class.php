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
    /**
     * Test one user by Id
     * Search for an Id
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
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

    /**
     * Test one user by Email
     * Search for a given Email
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
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
     * Test all Users
     * Search for all given Users
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testAllUsers() {
        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;
        $users = $user->getUsers();

        //counter for offset
        $offset = 0;

        //count existing Users in DB
        while (!$users->EOF) {
            $offset++;
            $users->next();
        }

        //set users with email
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

        //get all users and ignore previously existing entries ($offset)
        $users = $user->getUsers(
            null,
            null,
            null,
            null,
            4,
            $offset
        );

        $emails = array();
        while (!$users->EOF) {
            array_push($emails, $users->getEmail());
            $users->next();
        }

        $this->assertEquals(
            array(
                'test1@testmail.com',
                'test2@testmail.com',
                'test3@testmail.com',
            ),
            $emails
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
        while (!$users->EOF) {
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
        while (!$users->EOF) {
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
     * Test all users by birthday and existing profilepicture
     * Search for all users with a specific birthday and a profilepicture
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testAllUsersByBirthdayAndExistingProfilepicture() {
        //array for offset-id's
        $offsetId = array();

        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        //save id's from existing Users in DB
        $users = $user->getUsers();
        while (!$users->EOF) {
            array_push($offsetId, $users->getId());
            $users->next();
        }

        $user->reset();
        //user with wanted birthday and with profile-picture
        $user->setEmail('test1@testmail.com');
        $user->setProfile(array(
            'birthday' => array('02.10.1999'),
            'picture' => array('image.jpg'),
        ));
        $user->store();
        $user->reset();
        //user with wanted birthday and without profile-picture
        $user->setEmail('test2@testmail.com');
        $user->setProfile(array(
            'birthday' => array('02.10.2000'),
            'picture' => array(''),
        ));
        $user->store();
        $user->reset();
        //user with unwanted birthday and with profile-picture
        $user->setEmail('test3@testmail.com');
        $user->setProfile(array(
            'birthday' => array('30.10.2000'),
            'picture' => array('image.jpg'),
        ));
        $user->store();
        $user->reset();
        //user with wanted birthday and with profile-picture
        $user->setEmail('test4@testmail.com');
        $user->setProfile(array(
            'birthday' => array('02.10.2000'),
            'picture' => array('image.jpg'),
        ));
        $user->store();

        //set filter
        $day = '2';
        $month = '10';
        $filter = array (
            'birthday_day' => $day,
            'birthday_month' => $month,
            'picture' => array (
                '!=' => ''
            )
        );

        //get all users with wanted conditions ($filter)
        $users = $user->getUsers(
            $filter
        );

        $emails = array();
        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                array_push($emails, $users->getEmail());
            }
            $users->next();
        }

        $this->assertEquals(
            array(
                'test1@testmail.com',
                'test4@testmail.com',
            ),
            $emails
        );
    }

    /**
     * Test all users with same initial letter in firstname and lastname
     * Search for all users with the same inital letter
     * in the first- and the lastname
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testAllUsersWithSameInitialLetterInFirstnameAndLastname() {
        //array for offset-id's
        $offsetId = array();

        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        //save id's from existing Users in DB
        $users = $user->getUsers();
        while (!$users->EOF) {
            array_push($offsetId, $users->getId());
            $users->next();
        }

        //set users with email and firstname
        $user->reset();
        $user->setEmail('test1@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Sarah'),
            'lastname'  => array('Conner')
        ));
        $user->store();
        $user->reset();
        $user->setEmail('test2@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Chris'),
            'lastname' => array('Crisp'),
        ));
        $user->store();
        $user->reset();
        $user->setEmail('test3@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Harry'),
            'lastname' => array('Potter'),
        ));
        $user->store();
        $user->reset();
        $user->setEmail('test4@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Christina'),
            'lastname' => array('Müller'),
        ));
        $user->store();


        $filter = array(
            'OR' => array(
                0 => array(
                    'firstname' => 'C%',
                ),
                1 => array(
                    'lastname' => 'C%',
                ),
            ),
        );

        // get all users with wanted conditions ($filter)
        $users = $user->getUsers($filter);

        $emails = array();
        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                array_push($emails, $users->getEmail());
            }
            $users->next();
        }

        $this->assertEquals(
            array(
                'test1@testmail.com',
                'test2@testmail.com',
                'test4@testmail.com',
            ),
            $emails
        );
    }

    /**
     * Test limit user
     * Search for all users with a limit
     * in the first- and the lastname
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testLimitUser() {
        //counter for offset
        $offset = 0;

        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        //count existing Users in DB
        $users = $user->getUsers();
        while (!$users->EOF) {
            $offset++;
            $users->next();
        }

        //set users with email
        $user->reset();
        $user->setEmail('test1@testmail.com');
        $user->store();
        $user->reset();
        $user->setEmail('test2@testmail.com');
        $user->store();
        $user->reset();
        $user->setEmail('test3@testmail.com');
        $user->store();
        $user->reset();
        $user->setEmail('test4@testmail.com');
        $user->store();
        $user->reset();
        $user->setEmail('test5@testmail.com');
        $user->store();

        $limit = 4;
        //get all users with limit ($limit)
        //and ignore previously existing entries ($offset)
        $users = $user->getUsers(null, null, null, null, $limit, $offset);

        $countUsers = 0;
        while (!$users->EOF) {
            $countUsers++;
            $users->next();
        }

        $this->assertEquals(
            4,
            $countUsers
        );
    }



    /**
     * Test all users with same initial letter in firstname
     * Search for all users with the same inital letter in the firstnames
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testAllUsersWithSameInitialLetterInFirstname() {
        //array for offset-id's
        $offsetId = array();

        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        //save id's from existing Users in DB
        $users = $user->getUsers();
        while (!$users->EOF) {
            array_push($offsetId, $users->getId());
            $users->next();
        }

        //set users with email and firstname
        $user->reset();
        $user->setEmail('test1@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Aaron'),
        ));
        $user->store();
        $user->reset();
        $user->setEmail('test2@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Anna'),
        ));
        $user->store();
        $user->reset();
        $user->setEmail('test3@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Xavier'),
        ));
        $user->store();

        $filter = array(
            'firstname' => 'A%',
        );
        $users = $user->getUsers($filter);

        // get all users with wanted conditions ($filter)
        $users = $user->getUsers(
            $filter
        );

        $names = array();
        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                array_push($names, $users->getProfileAttribute('firstname'));
            }
            $users->next();
        }

        $this->assertEquals(
            array(
                'Aaron',
                'Anna',
            ),
            $names
        );
    }


    /**
     * Test list sorted by firstname
     * Search for a list, sorted by the firstnames
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testListSortedByFirstnames() {
        //array for offset-id's
        $offsetId = array();

        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        //save id's from existing Users in DB
        $users = $user->getUsers();
        while (!$users->EOF) {
            array_push($offsetId, $users->getId());
            $users->next();
        }

        //set users with email and firstname
        $user->reset();
        $user->setEmail('test1@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Xavier'),
        ));
        $user->store();
        $user->reset();
        $user->setEmail('test2@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Aaron'),
        ));
        $user->store();
        $user->reset();
        $user->setEmail('test3@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Anna'),
        ));
        $user->store();
        $user->reset();
        $user->setEmail('test4@testmail.com');
        $user->setProfile(array(
            'firstname' => array('Nadia'),
        ));
        $user->store();

        $arrSort = array(
            'firstname' => 'asc',
        );

        // get all users sorted by firstname ($arrSort)
        $users = $user->getUsers(
            null,
            null,
            $arrSort
        );

        $names = array();
        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                array_push($names, $users->getProfileAttribute('firstname'));
            }
            $users->next();
        }

        $this->assertEquals(
            array(
                'Aaron',
                'Anna',
                'Nadia',
                'Xavier',
            ),
            $names
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
}

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
class GetUserTest extends \Cx\Core\User\Testing\UnitTest\UserTestCase
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
        $user = $this->createUserObject();

        $userInfos = array(
            'test@testmail.com',
        );
        $this->createUsers($user, $userInfos);

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
        $user = $this->createUserObject();

        $userInfos = array(
            'test@testmail.com',
        );
        $this->createUsers($user, $userInfos);

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
        $user = $this->createUserObject();

        $userInfos = array(
            'testerson@testmail.com' => array(
                'username' => 'Testerson'
            )
        );

        $this->createUsers($user, $userInfos);

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
        $user = $this->createUserObject();
        $users = $user->getUsers();

        //counter for offset
        $offset = 0;

        //count existing Users in DB
        while (!$users->EOF) {
            $offset++;
            $users->next();
        }

        $userInfos = array(
            'test1@testmail.com',
            'test2@testmail.com',
            'test3@testmail.com',
        );
        //create users with email
        $this->createUsers($user, $userInfos);

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
            $userInfos,
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

        $user = $this->createUserObject();

        //count existing Users in DB
        $users = $user->getUsers();
        while (!$users->EOF) {
            $offset++;
            $users->next();
        }

        $userInfos = array(
            'test1@testmail.com' => array(
                'username' => 'One'
            ),
            'test2@testmail.com' => array(
                'username' => 'Two'
            ),
            'test3@testmail.com' => array(
                'username' => 'Three'
            ),
            'test4@testmail.com' => array(
                'username' => 'Four'
            ),
        );

        $this->createUsers($user, $userInfos);

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
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userInfos = array(
            'test1@testmail.com' => array(
                'profile' => array(
                    'birthday' => array('02.10.1999'),
                    'picture' => array('image.jpg'),
                ),
            ),
            'test2@testmail.com' => array(
                'profile' => array(
                    'birthday' => array('02.10.2000'),
                    'picture' => array(''),
                ),
            ),
            'test3@testmail.com' => array(
                'profile' => array(
                    'birthday' => array('30.10.2000'),
                    'picture' => array('image.jpg'),
                ),
            ),
            'test4@testmail.com' => array(
                'profile' => array(
                    'birthday' => array('02.10.2000'),
                    'picture' => array('image.jpg'),
                ),
            ),
        );
        //create users with wanted birthday and with profile-picture
        $this->createUsers($user, $userInfos);

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
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userInfos = array(
            'test1@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Sarah'),
                    'lastname'  => array('Conner')
                ),
            ),
            'test2@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Chris'),
                    'lastname' => array('Crisp'),
                ),
            ),
            'test3@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Harry'),
                    'lastname' => array('Potter'),
                ),
            ),
            'test4@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Christina'),
                    'lastname' => array('Müller'),
                ),
            ),
        );
        //create users with email, firstname and lastname
        $this->createUsers($user, $userInfos);

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
     * Test list firstname and lastname from all users
     * List all firstnames and lastnames from all users
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testListFirstnameAndLastnameFromAllUsers() {
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $arrAttributes = array('firstname', 'lastname');

        $userInfos = array(
            'test1@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Sarah'),
                    'lastname'  => array('Conner'),
                ),
            ),
            'test2@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Chris'),
                    'lastname' => array('Müller'),
                ),
            ),
            'test3@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Harry'),
                    'lastname' => array('Potter'),
                ),
            ),
        );
        //create users with email and firstname
        $this->createUsers($user, $userInfos);

        //get only wanted attributes from users ($arrAttributes)
        $users = $user->getUsers(null, null, null, $arrAttributes);

        $names = array();
        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                $arr = array();
                array_push(
                    $names,
                    $users->getProfileAttribute('firstname') . ' ' .
                    $users->getProfileAttribute('lastname')
                );
            }
            $users->next();
        }

        $this->assertEquals(
            array(
                'Sarah Conner',
                'Chris Müller',
                'Harry Potter'
            ),
            $names
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

        $user = $this->createUserObject();

        //count existing Users in DB
        $users = $user->getUsers();
        while (!$users->EOF) {
            $offset++;
            $users->next();
        }

        $userInfos = array(
            'test1@testmail.com',
            'test2@testmail.com',
            'test3@testmail.com',
            'test4@testmail.com',
            'test5@testmail.com',
        );
        //create users with email
        $this->createUsers($user, $userInfos);

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
     * Test all active users that signed in within last hour
     * Fetch all active users that have been signed in within the last hour
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testAllActiveUsersThatSignedInWithinLastHour() {
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userInfos = array(
            //user that is active and wasn't logged in within the last hour
            'test1@testmail.com' => array(
                'status' => 1,
                'auth' => 3700
            ),
            //user that is active but was logged in within the last hour
            'test2@testmail.com' => array(
                'status' => 1,
                'auth' => 300
            ),
            //user that isn't active but was logged in within the last hour
            'test3@testmail.com' => array(
                'status' => 0,
                'auth' => 300
            ),
            //user that isn't active and wasn't logged in within the last hour
            'test4@testmail.com' => array(
                'status' => 0,
            ),
        );
        //create users with status
        $this->createUsers($user, $userInfos);

        $filter = array(
            'AND' => array(
                0 => array(
                    'last_auth' => array(
                        '>' => time()-3600
                    )
                ),
                1 => array(
                    'active' => 1,
                ),
            )
        );
        $users = $user->getUsers($filter);

        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                $email = $users->getEmail();
            }
            $users->next();
        }

        $this->assertEquals(
            'test2@testmail.com',
            $email
        );
    }

    /**
     * Test limit value of auth time
     * The acceptable limit would be 3599.
     * But since the Test takes about 1-2 seconds to execute, the limit is set
     * 3 seconds before. Therefore the attribute auth is set as 3597 rather then 3599
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @return      void
     */
    public function testLimitValueOfAuthTime() {
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userInfos = array(
            'test1@testmail.com' => array(
                'auth' => 3600,
            ),
            'test2@testmail.com' => array(
                'auth' => 3597,
            ),
        );

        //create users with status
        $this->createUsers($user, $userInfos);

        $filter = array(
            'last_auth' =>  array(
                '>' => time()-3600
            )
        );
        $users = $user->getUsers($filter);

        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                $email = $users->getEmail();
            }
            $users->next();
        }

        $this->assertEquals(
            'test2@testmail.com',
            $email
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
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userInfos = array(
            'test1@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Aaron'),
                ),
            ),
            'test2@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Anna'),
                ),
            ),
            'test3@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Xavier'),
                ),
            ),
        );
        //create users with email and firstname
        $this->createUsers($user, $userInfos);

        $filter = array(
            'firstname' => 'A%',
        );

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
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userInfos = array(
            'test1@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Xavier'),
                ),
            ),
            'test2@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Aaron'),
                ),
            ),
            'test3@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Anna'),
                ),
            ),
            'test4@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Nadia'),
                ),
            ),
        );
        //create users with email and firstname
        $this->createUsers($user, $userInfos);

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
     * Search for a all Users with given Birthday
     * @author      Mirjam Doyon <info@cloudrexx.com>
     * @return      void
     *
     */
    public function testUsersByBirthdate() {
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userInfos = array(
            'test1@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Xavier'),
                    'birthday' => array('02.10.1991')
                ),
            ),
            'test2@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Aaron'),
                    'birthday' => array('02.10.19.1995')
                ),
            ),
            'test3@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Anna'),
                    'birthday' => array('02.12.1991')
                ),
            ),
        );
        //create users with email and firstname
        $this->createUsers($user, $userInfos);

        //set filter
        $day = '2';
        $month = '10';
        $filter = array (
            'birthday_day' => $day,
            'birthday_month' => $month
        );

        //get all users with conditions set in $filter
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
            ),
            $emails
        );

    }
    
    /**
     * Test all users without admin rights
     * Test all users that don't have admin rights
     *
     * @author Hava Fuga <info@cloudrexx.com>
     *
     * @return void
     */
    public function testAllUsersWithoutAdminRights() {
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userInfos = array(
            'test1@testmail.com' => array(
                'admin' => 1,
            ),
            'test2@testmail.com',
        );
        //create users with email and firstname
        $this->createUsers($user, $userInfos);

        $filter = array('is_admin' => 0);
        $users = $user->getUsers($filter);

        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                $email = $users->getEmail();
            }
            $users->next();
        }

        $this->assertEquals(
            'test2@testmail.com',
            $email
        );
    }

    /**
     * Test Custom set Attributes
     * Test if Attributes get deleted from Database
     *
     * @author Mirjam Doyon  <info@cloudrexx.com>
     *
     * @return void
     */
    public function testCustomSetAttributeNames() {
        global $objDatabase;
        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        $profileAttribute = $user->objAttribute->getById(0);
        $profileAttribute->reset();
        $profileAttribute->setNames(array('1'=>'TestColor'));
        $profileAttribute->store();

        //create User with a Attribute Entry
        $userInfos = array(
            'test1@testmail.com' => array(
                'profile' => array(
                    'firstname' => array('Xavier'),
                    'TestColor' => array('Red')
                ),
            )
        );
        $this->createUsers($user, $userInfos);

        //determine ID to delete
        $deleteAttribute = $profileAttribute->getId();
        $profileAttribute->deleteAttribute($deleteAttribute);

        //check if custom Attribute is still in DB
        $objResult = $objDatabase->Execute(
            'SELECT      *
                FROM    '. DBPREFIX. 'access_user_attribute_name
                WHERE attribute_id ='. $deleteAttribute

        );

        //if Attribute isn't in DB, $objResult->EOF is true and Test is successful
        //if Attribute is still in DB, $objResult->EOF is false and Test fails
        $this->assertTrue(
            $objResult->EOF
        );
    }     


    /**
     * Test find active user by username or email
     * Find user that is active by username or by email
     *
     * @author Hava Fuga <info@cloudrexx.com>
     *
     * @return void
     */
    public function testFindActiveUserByUsernameOrEmail() {
        $user = $this->createUserObject();
        $offsetId = $this->saveExistingUserIds();

        $userName = 'Test1';
        $userMail = 'test2@testmail.com';

        $userInfos = array(
            'test1@testmail.com' => array(
                'status'    => 1,
                'username'  => $userName,
            ),
            $userMail => array(
                'status'    => 1,
            ),
            'test3@testmail.com' => array(
                'status'    => 0,
                'username'  => $userName,
            ),
        );
        //create users with email and firstname
        $this->createUsers($user, $userInfos);

        $filter = array(
            'AND' => array(
                0 => array(
                    'OR' => array(
                        0 => array('email' => $userMail),
                        1 => array('username' => $userName),
                    )
                ),
                1 => array('status' => 1),
            ),
        );
        $users = $user->getUsers();//$filter);

        $emails = array();
        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($users->getId(), $offsetId)) {
                var_dump($users->getEmail());
                array_push($emails, $users->getEmail());
            }
            $users->next();
        }

        $this->assertEquals(
            array(
                'test1@testmail.com',
                'test2@testmail.com',
            ),
            $emails
        );
    }

    
    /**
     * Test Custom set Attributes Value
     * Test if AttributeID gets deleted from contrexx_access_user_attribute
     *
     * @author Mirjam Doyon  <info@cloudrexx.com>
     *
     * @return void
     */
    public function testCustomSetAttributesValue() {
        global $objDatabase;
        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        $profileAttribute = $user->objAttribute->getById(0);
        $profileAttribute->reset();
        $profileAttribute->setNames(array('1'=>'TestFood'));
        $profileAttribute->store();

        //determine ID to delete
        $deleteAttribute = $profileAttribute->getId();
        $profileAttribute->deleteAttribute($deleteAttribute);

        //check if custom Attribute is still in DB
        $objResult = $objDatabase->Execute(
            'SELECT      *
                FROM    '. DBPREFIX. 'access_user_attribute
                WHERE id ='. $deleteAttribute
        );

        //if Attribute isn't in DB, $objResult->EOF is true and Test is successful
        //if Attribute is still in DB, $objResult->EOF is false and Test fails
        $this->assertTrue(
            $objResult->EOF
        );
    }
}

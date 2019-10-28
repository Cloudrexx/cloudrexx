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
     * Create test-users
     * Creates test-users with the given parameters
     *
     * How the array should look like for example
     * array(
     *  'test@testmail.com' => array(
     *      'username'  => 'randomUsername',
     *      'profile'   => array(
     *          'birthday' => array('02.10.1999'),
     *          'firstname' => array('Lilly'),
     *          ),
     *      ),
     *  );
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * return void
     */
    protected function createUsers($userObject, $users) {
        //loop over user-data and create users
        foreach ($users as $email => $data) {
            $userObject->reset();

            if (!is_array($data)) {     //if only the email is given
                $userObject->setEmail($data);
            } else {
                $userObject->setEmail($email); //set email and the other data
                foreach ($data as $key => $value) {
                    switch ($key) {
                        case 'username':
                            $userObject->setUsername($value);
                            break;
                        case 'profile':
                            $userObject->setProfile($value);
                            break;
                        case 'group':
                            $userObject->setPrimaryGroup($value);
                            break;
                        case 'admin':
                            $userObject->setAdminStatus($value);
                        case 'status':
                            $userObject->setActiveStatus($value);
                        case 'auth':
                            $auth = true;
                    }
                }
            }
            $userObject->store();
            if ($auth) {
                //the user should first be stored before the Login can be successful
                $userObject->registerSuccessfulLogin();
            }
        }
    }

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
        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

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
        //array for offset-id's
        $offsetId = array();

        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

        $arrAttributes = array('firstname', 'lastname');

        //save id's from existing Users in DB
        $users = $user->getUsers(null, null, null, $arrAttributes);
        while (!$users->EOF) {
            array_push($offsetId, $users->getId());
            $users->next();
        }

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

        $object = \FWUser::getFWUserObject();
        $user = $object->objUser;

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
        //set user with desired birthday
        $user->setEmail('test1@testmail.com');
        $user->setProfile(array(
            'birthday' => array('02.10.1991'),
        ));
        $user->store();
        $user->reset();
        //set second user with same birthday
        $user->setEmail('test2@testmail.com');
        $user->setProfile(array(
            'birthday' => array('02.10.19.1995'),
        ));
        $user->store();
        $user->reset();
        //set user with different birthday
        $user->setEmail('test3@testmail.com');
        $user->setProfile(array(
            'birthday' => array('02.12.1991'),
        ));
        $user->store();
        $user->reset();

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
}

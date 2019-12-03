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
 * UserTestCase
 *
 * @copyright   Cloudrexx AG
 * @author      Hava Fuga <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
namespace Cx\Core\User\Testing\UnitTest;

/**
 * UserTestCase
 *
 * @copyright   Cloudrexx AG
 * @author      Hava Fuga <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
abstract class UserTestCase extends \Cx\Core\Test\Model\Entity\MySQLTestCase
{

    /**
     * Create test-users
     * Creates test-users with the given parameters
     *
     * If the array has all possible cases it can look like this:
     * array(
     *     'test2@testmail.ch',
     *     'test@testmail.com' => array(
     *         'username'  => 'randomUsername',
     *         'profile'   => array(
     *             'birthday' => array('02.10.1999'),
     *             'firstname' => array('Lilly'),
     *         ),
     *         'admin'     => 1,
     *         //time() -  your auth-number
     *         'auth'      => 300,
     *         'status'    => 1,
     *
     *     ),
     * );
     *
     * It is also possible to create users with only the email.
     * The array would then looks like this:
     * array(
     *     'test@testmail.com',
     *     'example@mail.org'
     * );
     *
     * @author      Hava Fuga <info@cloudrexx.com>
     *
     * @param $userObject \User The UserObject
     * @param $userInfos Array Contains all infos for the users
     *
     * return void
     */
    protected function createUsers($userObject, $userInfos) {
        //loop over user-data and create users
        foreach ($userInfos as $email => $data) {
            $userObject->reset();

            if (!is_array($data)) {
                //if only the email is given
                $userObject->setEmail($data);
                $userObject->store();
                continue;
            }

            $userObject->setEmail($email);
            //set email and the other data
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
                        break;
                    case 'status':
                        $userObject->setActiveStatus($value);
                        break;
                    case 'auth':
                        $time = $value;
                        break;
                    }
                }
            $userObject->store();

            if (isset($time)) {
                //the user should first be stored before the Login can be successful
                $userObject->registerSuccessfulLogin();
                $this->setLastAuthenticationTime($userObject->getId(), $time);
                $userObject->store();
            }
        }
    }

    /**
     * The userObject that contains all users
     * @var \User
     * @access protected
     */
    protected $user;

    /**
     * Contains the id of currently loaded users
     * @var integer
     * @access protected
     */
    protected $offsetId = array();

    /**
     * initialise Test
     * Initial for a testCase
     *
     * @author Hava Fuga    <info@cloudrexx.com>
     *
     * @return $this->user \User
     */
    protected function initTest() {
        $this->createUserObject();
        $this->saveExistingUserIds();
        return $this->user;
    }

    /**
     * Create UserObject
     * Create an UserObject
     *
     * @author Hava Fuga    <info@cloudrexx.com>
     *
     * @return $this->user \User
     */
    protected function createUserObject() {
        $object = \FWUser::getFWUserObject();
        $this->user = $object->objUser;
        return $this->user;
    }

    /**
     * Save existing UserIds
     * Save already existing UserId's
     *
     * @author Hava Fuga    <info@cloudrexx.com>
     *
     * @return $this->offsetId string[]
     */
    protected function saveExistingUserIds() {
        //save id's from existing Users in DB
        $users = $this->user->getUsers();
        while (!$users->EOF) {
            array_push($this->offsetId, $users->getId());
            $users->next();
        }
        return $this->offsetId;
    }

    /**
     * Set last AuthTime
     * Set last AuthTime with the given number
     *
     * @author Hava Fuga    <info@cloudrexx.com>
     *
     * @param $id int the current users id
     * @param $time int the time that will be subtracted from the current time
     *
     * @return void
     */
    protected function setLastAuthenticationTime($id, $time)
    {
        global $objDatabase;

        // change authentication time
        $objDatabase->Execute('
            UPDATE `' . DBPREFIX . 'access_users`
               SET `last_auth`="' . (time() - $time) . '"
             WHERE `id`=' . $id );
    }

    /**
     * Get values
     * Get values from userObject
     *
     * @author Hava Fuga    <info@cloudrexx.com>
     *
     * @param $value string the name of the wanted attribute
     *
     * @return string[]
     */
    protected function getValues($value) {
        $array = array();
        $users = $this->user;
        //remove user if previously existed ($offsetId)
        while (!$users->EOF) {
            if (!in_array($this->users->getId(), $this->offsetId)) {
                switch ($value) {
                    case 'email':
                        $result = $users->getEmail();
                        break;
                    case 'username':
                        $result = $users->getUsername();
                        break;
                }
                array_push($array, $result);
            }
            $users->next();
        }
    }

}
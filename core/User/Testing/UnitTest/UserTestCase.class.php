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
     * The userObject that contains all users
     * @var \User
     * @access private
     */
    protected $user;

    /**
     * Contains the id of currently loaded users
     * @var integer
     * @access private
     */
    protected $offsetId = array();

    /**
     * initialise Test
     * Initial for a testCase
     *
     * @author Hava Fuga    <info@cloudrexx.com>
     *
     * @return void
     */
    protected function initTest() {
        $this->createUserObject();
        $this->saveExistingUserIds();
    }

    /**
     * Create UserObject
     * Create an UserObject
     *
     * @author Hava Fuga    <info@cloudrexx.com>
     *
     * @return void
     */
    protected function createUserObject() {
        $object = \FWUser::getFWUserObject();
        $this->user = $object->objUser;
    }

    /**
     * Save existing UserIds
     * Save already existing UserId's
     *
     * @author Hava Fuga    <info@cloudrexx.com>
     *
     * @return void
     */
    protected function saveExistingUserIds() {
        //save id's from existing Users in DB
        $users = $this->user->getUsers();
        while (!$users->EOF) {
            array_push($this->offsetId, $users->getId());
            $users->next();
        }
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
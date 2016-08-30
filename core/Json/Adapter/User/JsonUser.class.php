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
 * JSON Adapter for User class
 * @copyright   Cloudrexx AG
 * @author      Michael Räss <michael.raess@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */

namespace Cx\Core\Json\Adapter\User;

use \Cx\Core\Json\JsonAdapter;

/**
 * JSON Adapter for Block module
 * @copyright   Cloudrexx AG
 * @author      Michael Räss <michael.raess@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */
class JsonUser implements JsonAdapter {

    /**
     * List of messages
     * @var Array
     */
    private $messages = array();

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return 'user';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('getUserById', 'getUsers', 'loginUser', 'logoutUser', 'lostPassword', 'setPassword');
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return implode('<br />', $this->messages);
    }

    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * Returns the user with the given user id.
     * If the user does not exist then return the currently logged in user.
     *
     * @return array User id and title
     */
    public function getUserById() {
        global $objInit, $_CORELANG;

        $objFWUser = \FWUser::getFWUserObject();

        if (!\FWUser::getFWUserObject()->objUser->login() || $objInit->mode != 'backend') {
            throw new \Exception($_CORELANG['TXT_ACCESS_DENIED_DESCRIPTION']);
        }

        $id = !empty($_GET['id']) ? intval($_GET['id']) : 0;

        if (!$objUser = $objFWUser->objUser->getUser($id)) {
            $objUser = $objFWUser->objUser;
        }

        return array(
            'id' => $objUser->getId(),
            'title' => $objFWUser::getParsedUserTitle($objUser),
        );
    }

    /**
     * Returns all users according to the given term.
     *
     * @return array List of users
     */
    public function getUsers() {
        global $objInit, $_CORELANG;

        $objFWUser = \FWUser::getFWUserObject();

        if (!\FWUser::getFWUserObject()->objUser->login() || $objInit->mode != 'backend') {
            throw new \Exception($_CORELANG['TXT_ACCESS_DENIED_DESCRIPTION']);
        }

        $term = !empty($_GET['term']) ? trim($_GET['term']) : '';

        $arrSearch = array(
            'company' => $term,
            'firstname' => $term,
            'lastname' => $term,
            'username' => $term,
        );
        $arrAttributes = array(
            'company', 'firstname', 'lastname', 'username',
        );
        $arrUsers = array();

        if ($objUser = $objFWUser->objUser->getUsers(null, $arrSearch, null, $arrAttributes)) {
            while (!$objUser->EOF) {
                $id = $objUser->getId();
                $title = $objFWUser->getParsedUserTitle($objUser);

                $arrUsers[$id] = $title;
                $objUser->next();
            }
        }

        return $arrUsers;
    }

    /**
     * Logs the current User in.
     *
     * @param string $_POST['USERNAME']
     * @param string $_POST['PASSWORD']
     * @return false on failure and array with userdata on success
     */
    public function loginUser() {
        $objFWUser = \FWUser::getFWUserObject();
        if ($objFWUser->checkLogin()) {
            $objFWUser->loginUser($objFWUser->objUser);
            return array($objFWUser->objUser->getUsername(),
                $objFWUser->objUser->getAssociatedGroupIds(),
                $objFWUser->objUser->getAdminStatus(),
                $objFWUser->objUser->getBackendLanguage()
            );
        }
        return false;
    }

    /**
     * Logs the current User out.
     *
     * @return boolean
     */
    public function logoutUser() {
        \FWUser::getFWUserObject()->logoutAndDestroySession();
        return true;
    }

    /**
     * Sends a Email with a new tomporary Password to the user with given email
     *
     * @param string $arguments['get']['email'] || $arguments['post']['email']
     * @return boolean
     */
    public function lostPassword($arguments) {
        if (empty($arguments['get']['email']) && empty($arguments['post']['email'])) {
            return false;
        }
        $email = contrexx_stripslashes(!empty($arguments['get']['email']) ? $arguments['get']['email'] : $arguments['post']['email']);
        $objFWUser = \FWUser::getFWUserObject();
        if ($objFWUser->restorePassword($email)) {
            return true;
        }
        return false;
    }

    /**
     * Set a new Password for a specific user if the admin has enough permissions
     *
     * @param string $arguments['get']['userId'] || $arguments['post']['userId']
     * @param string $arguments['get']['password'] || $arguments['post']['password']
     * @param string $arguments['get']['repeatPassword'] || $arguments['post']['repeatPassword']
     * @return boolean
     */
    public function setPassword($arguments) {
        if ((empty($arguments['get']['userId']) && empty($arguments['post']['userId'])) ||
                (empty($arguments['get']['password']) && empty($arguments['post']['password'])) ||
                (empty($arguments['get']['repeatPassword']) && empty($arguments['post']['repeatPassword']))) {
            return false;
        }
        $objFWUser = \FWUser::getFWUserObject();
        $arrPermissionIds = $objFWUser->objGroup->getGroups()->getStaticPermissionIds();
        if (!$objFWUser->objUser->login()) {
            return false;
        }
        if ($objFWUser->objUser->getAdminStatus() || (in_array('18', $arrPermissionIds) && in_array('36', $arrPermissionIds))) {
            $password = contrexx_stripslashes(!empty($arguments['get']['password']) ? $arguments['get']['password'] : $arguments['post']['password']);
            $password2 = contrexx_stripslashes(!empty($arguments['get']['repeatPassword']) ? $arguments['get']['repeatPassword'] : $arguments['post']['repeatPassword']);
            $userId = !empty($arguments['get']['userId']) ? $arguments['get']['userId'] : $arguments['post']['userId'];
            $user = $objFWUser->objUser->getUser($userId);
            return $user->setPassword($password, $password2) && $user->store();
        }
        return false;
    }

}

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
        $terms = explode(' ', $term);
        array_walk($terms, function(&$value, $key) {
            $value = '%' . $value . '%';
        });

        $whitelistedFields = array(
            'company',
            'address',
            'city',
            'zip',
            'firstname',
            'lastname',
            'username',
            'email',
        );

        $searchFields = array('company', 'firstname', 'lastname', 'username', 'email');
        if (!empty($_GET['searchFields'])) {
            $possibleSearchFields = explode(',', $_GET['searchFields']);
            foreach ($possibleSearchFields as $key=>$possibleSearchField) {
                if (!in_array($possibleSearchField, $whitelistedFields)) {
                    unset($possibleSearchFields[$key]);
                }
            }
            if (count($possibleSearchFields)) {
                $searchFields = $possibleSearchFields;
            }
        }
        // AND-ed search is only available if 2 or less search fields are
        // in use and no more than 2 search terms are specified. With higher
        // values there are an increasing amount of possibilities.
        // If there's but one search field the sensful search query
        // results in the same as the OR-ed one. If there's but one term the
        // same happens.
        if (
            count($searchFields) == 2 &&
            count($terms) == 2 &&
            isset($_GET['searchAnd']) &&
            $_GET['searchAnd'] == 'true'
        ) {
            // We can search for "Peter Muster" or "Muster Peter"
            // (field1 = term1 AND field2 = term2) OR (field2 = term1 AND field1 = term2)
            $arrFilter = array(
                'OR' => array(
                    0 => array(
                        'AND' => array(
                            0 => array(
                                $searchFields[0] => $terms[0],
                            ),
                            1 => array(
                                $searchFields[1] => $terms[1],
                            ),
                        ),
                    ),
                    1 => array(
                        'AND' => array(
                            0 => array(
                                $searchFields[1] => $terms[0],
                            ),
                            1 => array(
                                $searchFields[0] => $terms[1],
                            ),
                        ),
                    ),
                ),
            );
        } else {
            $arrFilter = array('OR' => array());
            foreach ($searchFields as $field) {
                $arrFilter['OR'][][$field] = $terms;
            }
        }

        $arrAttributes = $whitelistedFields;

        $limit = 0;
        if (isset($_GET['limit'])) {
            $limit = intval($_GET['limit']);
        }

        $arrUsers = array();
        $objUser = $objFWUser->objUser->getUsers(
            $arrFilter,
            null,
            null,
            $arrAttributes,
            $limit ? $limit : null
        );

        if (!$objUser) {
            return array();
        }
        $resultFormat = '%title%';
        if (!empty($_GET['resultFormat'])) {
            $resultFormat = contrexx_input2raw($_GET['resultFormat']);
        }
        while (!$objUser->EOF) {
            $id = $objUser->getId();
            $title = $objFWUser->getParsedUserTitle($objUser);

            $result = str_replace('%id%', $id, $resultFormat);
            $result = str_replace('%title%', $title, $result);
            $result = str_replace('%username%', $objUser->getUsername(), $result);
            $result = str_replace('%email%', $objUser->getEmail(), $result);
            foreach ($whitelistedFields as $field) {
                $result = str_replace(
                    '%' . $field . '%',
                    $objUser->getProfileAttribute($field),
                    $result
                );
            }

            $arrUsers[$id] = $result;
            $objUser->next();
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

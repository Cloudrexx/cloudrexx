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
 * User class
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Controller;

/**
 * The class User for getting the user name
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

class User extends \User {

    /**
     * Get the user name
     *
     * @param integer $userId
     *
     * @return string
     */
    public function getUpdatedUserName($userId, $currentUser)
    {
        $objFwUser = \FWUser::getFWUserObject();

        if (!empty($userId)) {
            $objUser = $objFwUser->objUser->getUser($userId);
            if ($objUser) {
                return $objUser->getUsername();
            }
        } else if (empty ($userId) && $currentUser) {
            return array(
                'id'    => $objFwUser->objUser->getId(),
                'name'  => $objFwUser->objUser->getUsername()
            );
        }
        return false;
    }

}

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
 * JsonController for DataAccess
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_modules_dataaccess
 * @version     5.0.0
 */
namespace Cx\Core_Modules\DataAccess\Controller;

/**
 * JsonController for DataAccess
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_modules_dataaccess
 * @version     5.0.0
 */
class JsonDataAccessController
    extends \Cx\Core\Core\Model\Entity\Controller
    implements \Cx\Core\Json\JsonAdapter
{
    /**
     * List of messages
     * @var array
     */
    protected $messages = array();

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'DataAccess';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'storeSelectedDataAccess' => $this->getDefaultPermissions(),
            'getDataAccessReadOnlySearch' => $this->getDefaultPermissions(),
            'getDataAccessSearch' => $this->getDefaultPermissions(),
            'getFieldListSearch' => $this->getDefaultPermissions(),
            'getAccessCondition' => $this->getDefaultPermissions(),
            'getAllowedOutputMethods' => $this->getDefaultPermissions(),
            'getDataAccessPermission' => $this->getDefaultPermissions(),
            'getDataAccessPermissionId' => $this->getDefaultPermissions(),
        );
    }

    /**
     * Returns all messages as string
     *
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString()
    {
        return implode('<br />', $this->messages);
    }

    /**
     * Returns default permission as object
     *
     * @return \Cx\Core_Modules\Access\Model\Entity\Permission
     */
    public function getDefaultPermissions()
    {
        $permission = new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array('http', 'https'),
            array('get', 'post'),
            true,
            array(),
            array(113)
        );

        return $permission;
    }

    public function getAccessCondition() {}

    public function getAllowedOutputMethods() {}

    public function getDataAccessPermission() {}

    public function getDataAccessPermissionId() {}

    public function getFieldListSearch($args) {}

    public function getDataAccessSearch($args) {}

    public function getDataAccessReadOnlySearch($args) {}

    protected function getDataAccessValues($apiKeyId) {}

    protected function getSelectedDataAccessValues($apiKeyId) {}

    protected function getAllDataAccessValues() {}

    protected function getSearch($name, $data) {}

    public function storeSelectedDataAccess($args) {}
}
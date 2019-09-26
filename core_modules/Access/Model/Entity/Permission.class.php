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
 * Permission
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_access
 */

namespace Cx\Core_Modules\Access\Model\Entity;

/**
 * PermissionException
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_access
 */

class PermissionException extends \Exception {}

/**
 * Permission
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_access
 */

class Permission extends \Cx\Model\Base\EntityBase {
    /**
     * Id
     *
     * @var integer
     */
    protected $id;

    /**
     * Allowed protocols
     *
     * @var array
     */
    protected $allowedProtocols = array();

    /**
     * Allowed access methods
     *
     * @var array
     */
    protected $allowedMethods   = array();

    /**
     * is Login required or not
     *
     * @var boolean
     */
    protected $requiresLogin    = false;

    /**
     * Valid User Groups
     *
     * @var array
     */
    protected $validUserGroups  = array();

    /**
     * valid Access ids
     *
     * @var array
     */
    protected $validAccessIds   = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $readDataAccesses;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $writeDataAccesses;

    /**
     * Callback function name
     *
     * @var string
     */
    protected $callback = null;

    /**
     * Constructor
     * Calback may only be used for virtual instances
     *
     * @param Array   $allowedProtocols
     * @param Array   $allowedMethods
     * @param Boolean $requiresLogin
     */
    public function __construct($allowedProtocols = array('http', 'https'), $allowedMethods = array('get', 'post'), $requiresLogin = true, $validUserGroups = array(), $validAccessIds = array(), $callback = null) {
        if ($callback && !($callback instanceof Callback)) {
            $callback = new Callback($callback);
        }
        if (!$allowedProtocols) {
            $allowedProtocols = array('http', 'https');
        }
        if (!$allowedMethods) {
            $allowedMethods = array('get', 'post');
        }
        $this->allowedProtocols = array_map('strtolower', $allowedProtocols);
        $this->allowedMethods   = array_map('strtolower', $allowedMethods);
        $this->validUserGroups  = $validUserGroups;
        $this->validAccessIds   = $validAccessIds;
        $this->requiresLogin    = $requiresLogin;
        if (count($this->validUserGroups) || count($this->validAccessIds)) {
            $this->requiresLogin = true;
        }
        $this->setVirtual(true);
        if ($callback) {
            $this->setCallback($callback);
        }
        $this->readDataAccesses  = new \Doctrine\Common\Collections\ArrayCollection();
        $this->writeDataAccesses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get the id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the allowed protocols
     *
     * @param array $allowedProtocols
     */
    public function setAllowedProtocols($allowedProtocols)
    {
        $this->allowedProtocols = $allowedProtocols;
    }

    /**
     * Get the allowed protocols
     *
     * @return array
     */
    public function getAllowedProtocols()
    {
        return $this->allowedProtocols;
    }

    /**
     * Set the allowed methods
     *
     * @param array $allowedMethods
     */
    public function setAllowedMethods($allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * Get the allowed methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return $this->allowedMethods;
    }

    /**
     * Set the requires login
     *
     * @param boolean $requiresLogin
     */
    public function setRequiresLogin($requiresLogin)
    {
        $this->requiresLogin = $requiresLogin;
    }

    /**
     * Get the requires login
     *
     * @return boolean
     */
    public function getRequiresLogin()
    {
        return $this->requiresLogin;
    }

    /**
     * Set the valid user groups
     *
     * @param array $validUserGroups
     */
    public function setValidUserGroups($validUserGroups)
    {
        $this->validUserGroups = $validUserGroups;
    }

    /**
     * Get the valid user groups
     *
     * @return array
     */
    public function getValidUserGroups()
    {
        return $this->validUserGroups;
    }

    /**
     * Set the valid user groups
     *
     * @param array $validAccessIds
     */
    public function setValidAccessIds($validAccessIds)
    {
        $this->validAccessIds = $validAccessIds;
    }

    /**
     * Get the valid access ids
     *
     * @return array
     */
    public function getvalidAccessIds()
    {
        return $this->validAccessIds;
    }

    /**
     * Add readDataAccesses
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $readDataAccesses
     * @return Permission
     */
    public function addReadDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $readDataAccesses)
    {
        $this->readDataAccesses[] = $readDataAccesses;

        return $this;
    }

    /**
     * Remove readDataAccesses
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $readDataAccesses
     */
    public function removeReadDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $readDataAccesses)
    {
        $this->readDataAccesses->removeElement($readDataAccesses);
    }

    /**
     * Set the read data access
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess
     */
    public function setReadDataAccesses(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess)
    {
        $this->readDataAccesses[] = $dataAccess;
    }

    /**
     * Get the read data access
     *
     * @return type
     */
    public function getReadDataAccesses()
    {
        return $this->readDataAccesses;
    }

    /**
     * Add writeDataAccesses
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $writeDataAccesses
     * @return Permission
     */
    public function addWriteDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $writeDataAccesses)
    {
        $this->writeDataAccesses[] = $writeDataAccesses;

        return $this;
    }

    /**
     * Remove writeDataAccesses
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $writeDataAccesses
     */
    public function removeWriteDataAccess(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $writeDataAccesses)
    {
        $this->writeDataAccesses->removeElement($writeDataAccesses);
    }

    /**
     * Set the write data access
     *
     * @param \Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess
     */
    public function setWriteDataAccesses(\Cx\Core_Modules\DataAccess\Model\Entity\DataAccess $dataAccess)
    {
        $this->writeDataAccesses[] = $dataAccess;
    }

    /**
     * Get the read data access
     *
     * @return type
     */
    public function getWriteDataAccesses()
    {
        return $this->writeDataAccesses;
    }

    /**
     * Set the callback
     * Callback may only be used for virtual instances
     *
     * @param Callback $callback
     */
    public function setCallback(Callback $callback)
    {
        if (!$this->isVirtual() && !$callback->isSerializable()) {
            throw new PermissionException('Permission::setCallback() failed: Could not set callback for non-virtual instance.');
        }
        $this->callback = $callback;
    }

    /**
     * Get the callback
     *
     * @return Callback Callback for custom permission management
     */
    public function getCallback()
    {
        if ($this->callback && !($this->callback instanceof Callback)) {
            try {
                $this->callback = new Callback($this->callback);
            } catch (CallbackException $e) {
                $this->callback = null;
            }
        }
        return $this->callback;
    }

    /**
     * Set virtual
     * Callback may only be used for virtual instances
     *
     * @param boolean $virtual
     */
    public function setVirtual($virtual)
    {
        //While setting instance as non-virtual, check the instance have callback if so throw exception
        if ($this->callback && !$this->callback->isSerializable() && !$virtual) {
            throw new PermissionException('Permission::setVirtual() failed: Could not set instance as non-virtual since instance contains callback.');
        }
        parent::setVirtual($virtual);
    }

    /**
     * Check the permissions(Is allowed protocol, Is allowed method, user's group access, user's login status)
     *
     * @param array $params Params to pass to callback (if any)
     * @return boolean
     */
    public function hasAccess(array $params = array()) {
        $protocol = $this->cx->getRequest() ? \Env::get('cx')->getRequest()->getUrl()->getProtocol() : '';
        $method = $this->cx->getRequest()->getHttpRequestMethod();
        if (php_sapi_name() === 'cli') {
            $method = 'cli';
        }

        //protocol check
        if ($method != 'cli' && !empty($this->allowedProtocols) && !in_array($protocol, $this->allowedProtocols)) {
            \DBG::msg(__METHOD__ . ': protocol check failed: ' . $protocol);
            return false;
        }

        //access method check
        if (!empty($this->allowedMethods) && !in_array($method, $this->allowedMethods)) {
            \DBG::msg(__METHOD__ . ': method check failed: ' . $method);
            return false;
        }

        // user loggedin or not (OR) user's group access check
        if (!empty($this->requiresLogin) && !$this->checkLoginAndUserAccess()) {
            return false;
        }

        //callback function check
        if ($this->getCallback() && call_user_func($this->getCallback(), $params) !== true) {
            return false;
        }

        return true;
    }

    /**
     * Check the user's login status and user's group access
     *
     * @return boolean
     */
    protected function checkLoginAndUserAccess() {

        if (!$this->requiresLogin) {
            return true;
        }

        //check user logged in or not
        if (!\FWUser::getFWUserObject()->objUser->login()) {
            return false;
        }

        // admins have all privileges
        if (\FWUser::getFWUserObject()->objUser->getAdminStatus()) {
            return true;
        }

        //check user's group access
        if (   !empty($this->validUserGroups)
            && !count(array_intersect($this->validUserGroups, \FWUser::getFWUserObject()->objUser->getAssociatedGroupIds()))
           ) {
            return false;
        }

        if (empty($this->validAccessIds)) {
            return true;
        }

        //check valid access ids
        foreach ($this->validAccessIds as $accessId) {
            if (\Permission::checkAccess($accessId, 'static', true)) {
                return true;
            }
        }

        return false;
    }
}

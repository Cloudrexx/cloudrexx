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
 * Permission
 * 
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_access
 */

class Permission extends \Cx\Model\Base\EntityBase {
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
     * Allowed group users
     * 
     * @var array 
     */
    protected $allowedGroups    = array();
    
    /**
     * Callback function name
     * 
     * @var string
     */
    public $callback            = null;
    
    /**
     * Constructor
     * 
     * @param Array   $allowedProtocols
     * @param Array   $allowedMethods
     * @param Boolean $requiresLogin
     */
    public function __construct($allowedProtocols = array('http', 'https'), $allowedMethods = array('get', 'post'), $requiresLogin = true, $validUserGroups = array(), $validAccessIds = array(), $callback = null) {
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
        $this->callback         = $callback;
    }
    
    /**
     * Check the permissions(Is allowed protocol, Is allowed method, user's group access, user's login status)
     * 
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
            return false;
        }
        
        //access method check
        if (!empty($this->allowedMethods) && !in_array($method, $this->allowedMethods)) {
            return false;
        }
        
        // user loggedin or not (OR) user's group access check 
        if (!empty($this->requiresLogin) && !$this->checkLoginAndUserAccess()) {
            return false;
        }
        
        //callback function check
        if (isset($this->callback) && call_user_func($this->callback, $params) !== true) {
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

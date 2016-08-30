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
 * Main controller for DataAccess
 * 
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_modules_dataaccess
 */

namespace Cx\Core_Modules\DataAccess\Controller;

/**
 * Main controller for DataAccess
 * 
 * @copyright   Cloudrexx AG
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_modules_dataaccess
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    
    /**
     * Returns all Controller class names for this component (except this)
     * 
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('JsonOutput', 'RawOutput');
    }
    
    /**
     * Returns a list of command mode commands provided by this component
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array(
            'v1' => new \Cx\Core_Modules\Access\Model\Entity\Permission(
                array('http', 'https'), // allowed protocols
                array(
                    'get',
                    'post',
                    'put',
                    'delete',
                    'trace',
                    'options',
                    'head',
                    'cli',
                ),   // allowed methods
                false                   // requires login
            ),
        );
    }

    /**
     * Returns the description for a command provided by this component
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        switch ($command) {
            case 'v1':
                if ($short) {
                    return 'RESTful data interchange API v1';
                }
                return 'RESTful data interchange API v1' . "\n" .
                    'Usage: v1 <outputModule> <dataSource> (<elementId>) (apikey=<apiKey>) (<options>)';
            default:
                return '';
        }
    }
    
    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     *
     * <domain>(/<offset>)/api/v1/<outputModule>/<dataSource>/<parameters>[(?apikey=<apikey>(&<options>))|?<options>]
     * @see getCommandsForCommandMode()
     * @param string $command Name of command to execute
     * @param array $arguments The optional list of arguments for the command
     * @return void
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {
        try {
            switch ($command) {
                case 'v1':
                    $this->apiV1($command, $arguments, $dataArguments);
            }
        } catch (\Exception $e) {
            // This should only be used if API cannot handle the request at all.
            // Most exceptions should be catched inside the API!
            http_response_code(400); // BAD REQUEST
            echo 'Exception of type "' . get_class($e) . '" with message "' . $e->getMessage() . '"';
        }
    }
    
    /**
     * Version 1 of Cloudrexx RESTful API
     * 
     * @param string $command Name of command to execute
     * @param array $arguments List of arguments for the command
     * @return void
     */
    public function apiV1($command, $arguments, $dataArguments) {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        
        // handle CLI
        if (php_sapi_name() == 'cli') {
            // we force usage of output module "raw" in CLI
            array_unshift($arguments, 'raw');
            
            // method will not be set in CLI, there for we educate-guess it
            $method = 'get';
            if (count($dataArguments)) {
                // this is a temporary fix:
                $method = 'put';
            }
        }
        
        // If we can't find the output module, we can't produce a proper error message
        if (empty($arguments[0])) {
            throw new \InvalidArgumentException('Not enough arguments');
        }
        $outputModule = $this->getOutputModule($arguments[0]);
        $response = new \Cx\Core_Modules\DataAccess\Model\Entity\ApiResponse();
        
        // Globally wrap all exceptions through the output module
        try {
            if (empty($arguments[1])) {
                throw new \InvalidArgumentException('Not enough arguments');
            }
            $dataSource = $this->getDataSource($arguments[1]);
            
            $elementId = null;
            if (isset($arguments[2])) {
                $elementId = $arguments[2];
            }
            
            $apiKey = null;
            if (isset($arguments['apikey'])) {
                $apiKey = $arguments['apikey'];
            }
            
            $order = array();
            if (isset($arguments['order'])) {
                $order = $arguments['order'];
            }
            $order = array();
            if (isset($arguments['order'])) {
                $orderStrings = explode(';', $arguments['order']);
                foreach ($orderStrings as $orderString) {
                    $orderStringParts = explode('/', $orderString);
                    $order[$orderStringParts[0]] = $orderStringParts[1];
                }
            }
            
            $filter = array();
            if (isset($arguments['filter'])) {
                $filterStrings = explode(';', $arguments['filter']);
                foreach ($filterStrings as $filterString) {
                    $filterStringParts = explode('=', $filterString);
                    $filter[$filterStringParts[0]] = $filterStringParts[1];
                }
            }
            
            $limit = 0;
            $offset = 0;
            if (isset($arguments['limit'])) {
                $limitParts = explode(',', $arguments['limit']);
                $limit = $limitParts[0];
                if (isset($limitParts[1])) {
                    $offset = $limitParts[1];
                }
            }
            
            $em = $this->cx->getDb()->getEntityManager();
            $dataAccessRepo = $em->getRepository($this->getNamespace() . '\Model\Entity\DataAccess');
            $dataAccess = $dataAccessRepo->getAccess($outputModule, $dataSource, $method, $apiKey);
            if (!$dataAccess) {
                $response->setStatusCode(403);
                throw new \BadMethodCallException('Access denied');
            }
            
            if (
                count($dataAccess->getAllowedOutputMethods()) &&
                !in_array($arguments[0], $dataAccess->getAllowedOutputMethods())
            ) {
                $response->setStatusCode(403);
                throw new \BadMethodCallException('Access denied');
            }
            
            if (count($dataAccess->getAccessCondition())) {
                $filter = array_merge($filter, $dataAccess->getAccessCondition());
            }
            
            $data = array();
            switch ($method) {
                // administrative access
                case 'options':
                    // lists available methods for a request
                    http_response_code(204); // No Content
                    $allowedMethods = $dataAccessRepo->getAllowedMethods($dataSource, $apiKey);
                    header('Allow: ' . implode(', ', $allowedMethods));
                    die();
                    break;
                
                // write access
                case 'post':
                    // create entry
                    // should be 201 (Created) with Location header to item URL
                    // should be 404 if ressource does not exist
                    // should be 409 (Conflict) if ressource already exists
                    $data = $dataSource->add($dataArguments);
                    break;
                case 'patch':
                case 'put':
                    // update entry
                    // should be 200 or 204 (No content)
                    // should be 404 if $elementId not set or not found
                    $data = $dataSource->update($elementId, $dataArguments);
                    break;
                case 'delete':
                    // delete entry
                    // should be 200
                    // should be 404 if element is not set or not found
                    $data = $dataSource->remove($elementId);
                    break;
                
                // read access
                case 'head':
                    // return the same headers as 'get', but no body
                    break;
                case 'get':
                default:
                    // should be 200
                    // should be 404 if item not found
                    $data = $dataSource->get($elementId, $filter, $order, $limit, $offset, $dataAccess->getFieldList());
                    break;
            }
            $response->setStatus(
                \Cx\Core_Modules\DataAccess\Model\Entity\ApiResponse::STATUS_OK
            );
            $response->setData($data);
            
            $response->send($outputModule);
        } catch (\Exception $e) {
            global $_ARRAYLANG;
            
            $response->setStatus(
                \Cx\Core_Modules\DataAccess\Model\Entity\ApiResponse::STATUS_ERROR
            );
            $response->addMessage(
                \Cx\Core_Modules\DataAccess\Model\Entity\ApiResponse::MESSAGE_TYPE_ERROR,
                sprintf(
                    $_ARRAYLANG['TXT_CORE_MODULE_DATA_ACCESS_ERROR'],
                    get_class($e),
                    $e->getMessage()
                )
            );
            /*$response->addMessage(
                \Cx\Core_Modules\DataAccess\Model\Entity\ApiResponse::MESSAGE_TYPE_INFO,
                $e->getTraceAsString()
            );//*/
            $response->send($outputModule);
        }
    }
    
    /**
     * Returns the output module with the given name
     * @param string $name Name of the output module
     * @return OutputController Output module
     */
    protected function getOutputModule($name) {
        $outputModule = $this->getController(ucfirst($name) . 'Output');
        if (!$outputModule) {
            throw new \Exception('No such output module "' . $name . '"');
        }
        return $outputModule;
    }
    
    /**
     * Returns the data source with the given name
     * @param string $name Name of the data source
     * @return \Cx\Core\DataSource\Model\Entity\DataSource Data source
     */
    protected function getDataSource($name) {
        $em = $this->cx->getDb()->getEntityManager();
        $dataAccessRepo = $em->getRepository($this->getNamespace() . '\Model\Entity\DataAccess');
        $dataAccess = $dataAccessRepo->findOneBy(array('name' => $name));
        if (!$dataAccess || !$dataAccess->getDataSource()) {
            throw new \Exception('No such DataSource: ' . $name);
        }
        return $dataAccess->getDataSource();
    }
}


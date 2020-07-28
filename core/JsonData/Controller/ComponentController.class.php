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
 * Main controller for JsonData
 *
 * @copyright   Cloudrexx AG
 * @author Project Team SS4U <info@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_jsondata
 */

namespace Cx\Core\JsonData\Controller;

/**
 * Main controller for JsonData
 *
 * @copyright   Cloudrexx AG
 * @author Project Team SS4U <info@cloudrexx.com>
 * @package cloudrexx
 * @subpackage core_jsondata
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    const ARGUMENT_INDEX_OUTPUT_MODULE = 0;
    const ARGUMENT_INDEX_DATA_ADAPTER = 1;
    const ARGUMENT_INDEX_DATA_METHOD = 2;
    
    /**
     * Returns a list of command mode commands provided by this component
     *
     * Data command is deprecated. Use /api/v1/ instead
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array('Data');
    }
    
    /**
     * Returns the description for a command provided by this component
     *
     * Data command is deprecated. Use /api/v1/ instead
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        switch ($command) {
            case 'Data':
                return 'Return data from a data source';
                break;
        }
    }

    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     *
     * Data command is deprecated. Use /api/v1/ instead
     * @see getCommandsForCommandMode()
     * @param string $command Name of command to execute
     * @param array $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     * @return void
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {
        switch ($command) {
            case 'Data':
                if (
                    !isset($arguments[static::ARGUMENT_INDEX_OUTPUT_MODULE]) ||
                    !isset($arguments[static::ARGUMENT_INDEX_DATA_ADAPTER]) ||
                    !isset($arguments[static::ARGUMENT_INDEX_DATA_METHOD])
                ) {
                    throw new \Exception('Not enough arguments');
                }
                $outputModule = $arguments[static::ARGUMENT_INDEX_OUTPUT_MODULE];
                $dataAdapter = $arguments[static::ARGUMENT_INDEX_DATA_ADAPTER];
                $dataMethod = $arguments[static::ARGUMENT_INDEX_DATA_METHOD];
                unset($arguments[static::ARGUMENT_INDEX_OUTPUT_MODULE]);
                unset($arguments[static::ARGUMENT_INDEX_DATA_ADAPTER]);
                unset($arguments[static::ARGUMENT_INDEX_DATA_METHOD]);
                $dataArguments = array('get' => $arguments, 'post' => $dataArguments);
                if (!isset($arguments['response'])) {
                    $arguments['response'] = $this->cx->getResponse();
                }
                
                $json = new \Cx\Core\Json\JsonData();
                $data = $json->data($dataAdapter, $dataMethod, $dataArguments);
                if ($data['status'] != 'success') {
                    if (empty($data['message'])) {
                        throw new \Exception('Fetching data failed without message');
                    }
                }
                
                switch ($outputModule) {
                    case 'Plain':
                        echo $data['data']['content'];
                        break;
                    case 'Json':
                        $response = $arguments['response'];
                        $response->setAbstractContent($data);
                        $response->setParser($json->getParser());
                        $response->send();
                        echo $json->parse($data, true);
                        break;
                    default:
                        throw new \Exception('No such output module: "' . $outputModule . '"');
                        break;
                }
                break;
        }
    }
    
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

     /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->routeToJsonData();
                break;
        }
    }

    /**
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $section;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                if ($section == 'JsonData') {
                    $this->routeToJsonData();
                }
                break;
        }
    }

    protected function routeToJsonData() {
        // TODO: move this code to /core/Json/...
        // TODO: handle expired sessions in any xhr callers.
        $json = new \Cx\Core\Json\JsonData();
        // TODO: Verify that the arguments are actually present!
        $adapter = contrexx_input2raw($_GET['object']);
        $method = contrexx_input2raw($_GET['act']);
        // TODO: Replace arguments by something reasonable
        $arguments = array(
            'get' => $_GET,
            'post' => $_POST,
            'response' => $this->cx->getResponse(),
        );
        echo $json->jsondata($adapter, $method, $arguments);

        \DBG::writeFinishLine($this->cx, false, 'json');
        die();
    }
}

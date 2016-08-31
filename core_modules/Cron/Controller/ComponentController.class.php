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
 * Class ComponentController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */

namespace Cx\Core_Modules\Cron\Controller;

/**
 * Class ComponentController
 *
 * The main controller for Cron
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
   /*
     * Constructor
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponent $systemComponent, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponent, $cx);
    }

    public function getCommandsForCommandMode() {
        return array('Cron');
    }

    /**
     * This component only has a backend
     */
    public function getControllerClasses() {
        return array('Backend', 'Default', 'Settings');
    }

    /**
     * To describe the Cron command
     *
     * @param type $command
     * @param type $short
     * @return string
     */
    public function getCommandDescription($command, $short = false) {
        switch ($command) {
            case 'Cron':
                return 'Fetching all Jobs and execute if they need to execute';
        }
    }
    /**
     * Fetch all Job entities and check each of them if they need to be executed
     *
     * @param string $command Name of command to execute
     * @param array  $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     */
    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        switch ($command) {
            case 'Cron':
                $executedJobs = 0;
                $starttime = microtime(true);
                $severity = 'INFO';
                $details = $this->executeCronJobs($severity, $executedJobs);
                $duration = microtime(true) - $starttime;
                $data = 'Executed ' . $executedJobs . ' job(s). This took ' . $duration . 's';
                if ($executedJobs > 0) {
                    $data .= "\r\n\r\nDetails:";
                    foreach ($details as $command=>$detail) {
                        $data .= "\r\n\r\n" . $command . ":\r\n" . $detail;
                    }
                }

                $this->cx->getEvents()->triggerEvent('SysLog/Add', array(
                    'severity'  => $severity,
                    'message'   => 'Cron Executed',
                    'data'      => $data,
                ));
                break;
        }
    }

    protected function executeCronJobs(&$severity, &$executedJobs) {
        $em = $this->cx->getDb()->getEntityManager();
        $cronJobs = $em->getRepository('Cx\Core_Modules\Cron\Model\Entity\Job')->findBy(array('active'=>1));
        if (!$cronJobs) {
            return;
        }
        $details = array();
        foreach ($cronJobs as $cron) {
            try {
                ob_start();
                $jobExecuted = false;
                if ($cron->execute()) {
                    $jobExecuted = true;
                    $executedJobs++;
                }
                $em->flush();
                $detail = ob_get_flush();
                if ($jobExecuted) {
                    $details[$cron->getCommand()] = $detail;
                }
            } catch (\Exception $e) {
                $executedJobs++;
                $severity = 'FATAL';
                $details[$cron->getCommand()] = 'Exception of type "' .
                    get_class($e) . '" with message "' . $e->getMessage() .
                    '" caught in ' . $e->getFile() . ' on line ' . $e->getLine();
            }
        }
        return $details;
    }
}

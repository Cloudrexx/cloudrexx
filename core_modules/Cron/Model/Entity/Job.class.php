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
 * Class Job
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */

namespace Cx\Core_Modules\Cron\Model\Entity;

class JobException extends \Exception {}

/**
 * Class Job
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */
class Job extends \Cx\Model\Base\EntityBase {
    /**
     * @var integer $id
     */
    protected $id;
    /**
     *
     * @var boolean $active
     */
    protected $active;
    /**
     *
     * @var string $expression
     */
    protected $expression;
    /**
     *
     * @var string $command
     */
    protected $command;
    /**
     *
     * @var datetime $lastRan
     */
    protected $lastRan;

    /**
     * Get the id
     *
     * @return integer $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the id
     *
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Get the active
     *
     * @return boolean $active
     */
    public function getActive() {
        return $this->active;
    }
    /**
     * Set the active
     *
     * @param boolean $active
     */
    public function setActive($active) {
        $this->active = $active;
    }
    /**
     * Get the expression
     *
     * @return string expression
     */
    public function getExpression() {
        return $this->expression;
    }
    /**
     * Set the expression
     *
     * @param string $expression
     */
    public function setExpression($expression) {
        $this->expression = $expression;
    }
    /**
     * Get the command
     *
     * @return string command
     */
    public function getCommand() {
        return $this->command;
    }
    /**
     * Set the command
     *
     * @param string $command
     */
    public function setCommand($command) {
        $this->command = $command;
    }
    /**
     * Get the lastRan
     *
     * @return type lastRan
     */
    public function getLastRan() {
        return $this->lastRan;
    }
    /**
     * Set the lastRan
     *
     * @param type $lastRan
     */
    public function setLastRan($lastRan) {
        $this->lastRan = $lastRan;
    }
    /**
     * check and execute cron job
     *
     * @return boolean
     */
    public function execute() {
        if (!$this->getActive()) {
            return false;
        }

        try {
            // check if cron job needs to be executed
            $cron = \Cron\CronExpression::factory($this->expression);
            if ($cron->getNextRunDate($this->lastRan, 0)->getTimestamp() > time()) {
                return false;
            }
            // execute cron job
            $arguments = explode(' ', $this->command);
            $command = array_shift($arguments);
            $commands = $this->cx->getCommands();
            if (!isset($commands[$command])) {
                throw new JobException('Command "' . $command . '" not found!');
            }
            $commands[$command]->executeCommand($command, $arguments);
            // update last ran time to now if cron job has successfully been executed
            $this->lastRan = new \DateTime();
            return true;
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw $e;
        }
    }
}

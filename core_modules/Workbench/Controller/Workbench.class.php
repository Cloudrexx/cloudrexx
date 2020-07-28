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
 * This is the Workbench Controller
 *
 * This handles Workbench's configuration and files
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

namespace Cx\Core_Modules\Workbench\Controller;

/**
 * This is the base Exception for all Exceptions thrown in this component
 */
class WorkbenchException extends \Exception {}

/**
 * This is the Workbench Controller
 *
 * This handles Workbench's configuration and files
 * @author Michael Ritter <michael.ritter@comvation.com>
 */
class Workbench {
    private $config = null;

    /**
     * Returns a list of files (directories include all contained files and folders)
     * @return array List of files and folders
     */
    public function getFileList() {
        return array(
            '/core_modules/Workbench',
            '/lib/behat',
        );
    }

    /**
     * Returns the configuration value for the given identifier
     * @param string $identifier Configuration identifier
     * @return string Configuration value
     */
    public function getConfigEntry($identifier) {
        if (!$this->config) {
            $this->loadConfig();
        }
        if (!isset($this->config[$identifier])) {
            return '';
        }
        return $this->config[$identifier];
    }

    /**
     * Sets the configuration value with the given identifier to the given value
     * @param string $identifier Configuration identifier
     * @param string $value Configuration value
     */
    public function setConfigEntry($identifier, $value) {
        if (!$this->config) {
            $this->loadConfig();
        }
        $this->config[$identifier] = $value;
    }

    /**
     * Destructor
     *
     * Write Workbench configuration
     */
    public function __destruct() {
        $this->writeConfig();
    }

    /**
     * Read configuration into memory
     */
    protected function loadConfig() {
        $content = file_get_contents(ASCMS_DOCUMENT_ROOT.'/workbench.config');
        $content = explode("\n", $content);
        $this->config = array();
        foreach ($content as $line) {
            $line = explode('=', $line, 2);
            if (count($line) != 2) {
                continue;
            }
            $this->config[trim($line[0])] = trim($line[1]);
        }
    }

    /**
     * Write configuration from memory into filesystem
     */
    protected function writeConfig() {
        $content = '';
        if (!$this->config) {
            return;
        }
        foreach ($this->config as $key=>$value) {
            $content .= $key.'='.$value."\r\n";
        }
        file_put_contents(ASCMS_DOCUMENT_ROOT.'/workbench.config', $content);
    }
}

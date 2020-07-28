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
 * Model main controller
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Controller;

/**
 * Model main controller
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    
    /**
     * PostInit hook to add entity validation
     * @param \Cx\Core\Core\Controller\Cx $cx Cx class instance
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx) {
        // init cx validation
        $cx->getEvents()->addEventListener(
            'model/onFlush',
            new \Cx\Core\Model\Model\Event\EntityBaseEventListener()
        );
    }

    /**
     * Slugifies the given string
     * @param $string The string to slugify
     * @return $string The slugified string
     */
    public function slugify($string) {
        // replace international characters
        $string = $this->getComponent('LanguageManager')
            ->replaceInternationalCharacters($string);

        // replace spaces
        $string = preg_replace('/\s+/', '-', $string);

        // replace all non-url characters
        $string = preg_replace('/[^a-zA-Z0-9-_]/', '', $string);

        // replace duplicate occurrences (in a row) of char "-" and "_"
        $string = preg_replace('/([-_]){2,}/', '-', $string);

        return $string;
    }

    /**
     * Returns a list of command mode commands provided by this component
     *
     * @return array List of command names
     */
    public function getCommandsForCommandMode()
    {
        return array('Model');
    }

    /**
     * Returns the description for a command provided by this component
     *
     * @param string  $command The name of the command to fetch the description from
     * @param boolean $short   Whether to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false)
    {
        switch ($command) {
            case 'Model':
                $desc = 'Provides cleanup function for database table';
                if ($short) {
                    return $desc;
                }

                $desc .= PHP_EOL . 'optimize' . "\t" .
                    'Optimize tables. This speeds up the system and can save some disk space.';

                return $desc;
            default :
                return '';
        }
    }

    /**
     * Execute api command
     *
     * @param string $command       Name of command to execute
     * @param array  $arguments     List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     */
    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        $subcommand = null;
        if (!empty($arguments[0])) {
            $subcommand = $arguments[0];
        }

        switch ($command) {
            case 'Model':
                switch ($subcommand) {
                    case 'optimize':
                        // Optimize all tables based on DBPREFIX
                        $db        = $this->cx->getDb()->getAdoDb();
                        $objResult = $db->Execute('SHOW TABLE STATUS LIKE "' . DBPREFIX . '%"');

                        while (!$objResult->EOF) {
                            $db->Execute('OPTIMIZE TABLE ' . $objResult->fields['Name']);
                            $objResult->MoveNext();
                        }
                        break;
                    default :
                        break;
                }
                break;
            default:
                break;
        }
    }
}

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
 * Command to delete components
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

namespace Cx\Core_Modules\Workbench\Model\Entity;

/**
 * Command to delete components
 * @author Michael Ritter <michael.ritter@comvation.com>
 */
class UninstallCommand extends Command {
    
    /**
     * Command name
     * @var string
     */
    protected $name = 'uninstall';
    
    /**
     * Command description
     * @var string
     */
    protected $description = 'Uninstall a component (core, core_module, lib, module)';
    
    /**
     * Command synopsis
     * @var string
     */
    protected $synopsis = 'workbench(.bat) uninstall [core|core_module|lib|module] {component_name}';
    
    /**
     * Command help text
     * @var string
     */
    protected $help = 'Deactivates and deletes the component with the specified type named {component_name}.';
    
    /**
     * Execute this command
     * @param array $arguments Array of commandline arguments
     */
    public function execute(array $arguments) {
        if (!$this->interface->yesNo('Do you really want to irrevocably delete ' . $arguments[2] . ' "' . $arguments[3] . '"?')) {
            return;
        }
        
        $component = new \Cx\Core\Core\Model\Entity\ReflectionComponent($arguments[3], $arguments[2]);
        $component->remove();
        
        $this->interface->show('Done');
    }
}

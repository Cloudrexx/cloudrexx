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
 * Command to copy components
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

namespace Cx\Core_Modules\Workbench\Model\Entity;

/**
 * Command to copy components
 * @author Michael Ritter <michael.ritter@comvation.com>
 */
class CopyCommand extends Command {

    /**
     * Command name
     * @var string
     */
    protected $name = 'copy';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Copy components';

    /**
     * Command synopsis
     * @var string
     */
    protected $synopsis = 'workbench(.bat) copy [core|core_module|lib|module] {component_name} [core|core_module|lib|module] {new_component_name} ([customized|uncustomized])';

    /**
     * Command help text
     * @var string
     */
    protected $help = 'Copies specified component to the location specified.';

    /**
     * Execute this command
     * @param array $arguments Array of commandline arguments
     */
    public function execute(array $arguments) {
        try {
            $oldComponent = new ReflectionComponent($arguments[3], $arguments[2]);
            $newComponent = new ReflectionComponent($arguments[5], $arguments[4]);
        } catch (\Exception $ex) {
            throw new CommandException('Invalid arguments passed. Please check the parameters or run help command to see the options');
        }

        if ($oldComponent == $newComponent) {
            $this->interface->show('Nothing to do');
            return;
        }

        if (!$oldComponent->exists()) {
            throw new CommandException('No such component "' . $oldComponent->getName() . '" of type ' . $oldComponent->getType());
        }

        $toBeCustomized = false;
        if (isset($arguments[6])) {
            $toBeCustomized = $arguments[6] == 'customized';
        }

        if (!$this->interface->yesNo('This comes without any warranty. Are your sure you want to copy the component?')) {
            return;
        }

        $oldComponent->copy($newComponent->getName(), $newComponent->getType(), $toBeCustomized);

        $this->interface->show('Done');
    }
}

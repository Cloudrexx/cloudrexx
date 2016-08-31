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
 * Command to install components
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

namespace Cx\Core_Modules\Workbench\Model\Entity;

/**
 * Command to install components
 * @author Michael Ritter <michael.ritter@comvation.com>
 */
class ImportCommand extends Command {

    /**
     * Command name
     * @var string
     */
    protected $name = 'import';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Installs a component (core, core_module, lib, module, template, etc.)';

    /**
     * Command synopsis
     * @var string
     */
    protected $synopsis = 'workbench(.bat) import {path to zip package}';

    /**
     * Command help text
     * @var string
     */
    protected $help = 'Installs a component using the specified zip package.';

    /**
     * Execute this command
     * @param array $arguments Array of commandline arguments
     */
    public function execute(array $arguments) {
        // The following tasks will be done by Apps:
        // Get package info
        // Recursively get all dependencies
        // Foreach package to install (sorted, the one without any dependencies first):
        // Download package
        $comp = new \Cx\Core\Core\Model\Entity\ReflectionComponent($arguments[2]);
        $comp->install();
    }
}

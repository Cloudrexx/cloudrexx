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
 * ModuleInterface
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * ModuleInterfaceException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class ModuleInterfaceException extends Exception {}

/**
 * An interface using which modules provide functionality to stuff outside the module itself.
 * Subclass this (xyModuleInterface extends ModuleInterface) if your module needs to be called from other parts of the cms.
 * Module Interfaces are Singletons.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
abstract class ModuleInterface {
    //singleton functionality: instance
    static private $instances = array();

    /**
     * Instance getter
     * @param string $moduleName the modules' interface you want
     * @throws ModuleInterfaceException if you provide an unknonw class
     * @return ModuleInterface
     */
    static public function getInstance($moduleName)
    {
        //all module interfaces follow the xyModuleInterface convention, extend the classname
        $className = $moduleName.'ModuleInterface';
        if(!class_exists($className))
           throw new ModuleInterfaceException("Could not find class '$className'. Did you load the appropriate header?");

        //handle instantiation
        if(!isset(self::$instances[$className])) {
            $object = new $className();
            if($object instanceof ModuleInterface) {
                self::$instances[$className] = $object;
            }
            else {
                throw new ModuleInterfaceException("'$className' is no instance of ModuleInterface. Does it extend ModuleInterface?");
            }
        }
        return self::$instances[$className];
    }
}
?>

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
 * Wrapper class for PHPUnit_TextUI_TestRunner
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     3.2
 * @package     cloudrexx
 * @subpackage  core
 */

namespace Cx\Core\Model\Controller;

/**
 * Wrapper class for PHPUnit_TextUI_TestRunner
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core
 */
class PHPUnitTextUITestRunner extends \PHPUnit_TextUI_TestRunner {

    /**
     * Set the loader to be used.
     *
     * @param \PHPUnit_Runner_TestSuiteLoader $loader
     */
    public function setLoader(\PHPUnit_Runner_TestSuiteLoader $loader = NULL)
    {
        $this->loader = $loader;
    }

    /**
     * Reset the printer value from the class
     */
    public function resetPrinter()
    {
        $this->printer = NULL;
    }
}

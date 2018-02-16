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
 * This is a temporary wrapper-script to access Workbench from console.
 * The nice way for this would be to directly access Cx from console like
 * > cx Workbench ....
 * @author Michael Ritter <michael.ritter@comvation.com>
 */

$rootDir = dirname(dirname(__DIR__));

include($rootDir . '/core/Core/init.php');

// This loads Cloudrexx in CLI mode
$cx = init('minimal');

new \Cx\Core_Modules\Workbench\Model\Entity\ConsoleInterface($argv, $cx);

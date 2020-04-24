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
 * Search script
 *
 * This script is standalone because otherwise it would be too slow.
 * @author      Stefan Heinemann <info@cloudrexx.com>
 * @copyright   CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

/**
 * Search script
 *
 * This script is standalone because otherwise it would be too slow.
 * @author      Stefan Heinemann <info@cloudrexx.com>
 * @copyright   CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

global $objDatabase;

// detect system location
$depth = 4;
if (strpos(__FILE__, 'customizing/') !== false) {
    // this files resides within the customizing directory, therefore we'll have to strip
    // out one directory more than usually
    $depth++;
}
if (strpos(__FILE__, 'codeBases/') !== false) {
    // this files resides in a codeBase directory, therefore we'll have to strip
    // out two directory more than usually
    $depth += 2;
}
$contrexx_path = dirname(__FILE__, $depth);

/**
 * @ignore
 */
require_once($contrexx_path . '/core/Core/init.php');
$cx = init('minimal');

$objDatabase = $cx->getDb()->getAdoDb();

\Env::get('ClassLoader')->loadFile(ASCMS_MODULE_PATH.'/Knowledge/Controller/DatabaseError.class.php');

//require_once '../../lib/CSRF.php';
// Temporary fix until all GET operation requests will be replaced by POSTs
//CSRF::setFrontendMode();

if (!defined('FRONTEND_LANG_ID') && !empty($_GET['lang'])) {
    define('FRONTEND_LANG_ID', \FWLanguage::getLanguageIdByCode($_GET['lang']));
}

$search = new \Cx\Modules\Knowledge\Controller\Search();
$search->performSearch();
die();

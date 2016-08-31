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
 * MySQLTestCase
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @package     cloudrexx
 * @subpackage  core_test
 */

namespace Cx\Core\Test\Model\Entity;

/**
 * MySQLTestCase
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @package     cloudrexx
 * @subpackage  core_test
 */
class MySQLTestCase extends ContrexxTestCase {
    protected static $database;

    public static function setUpBeforeClass() {
        global $_DBCONFIG, $_CONFIG;

        // Set database connection details
        $objDb = new \Cx\Core\Model\Model\Entity\Db();
        $objDb->setHost($_DBCONFIG['host']);
        $objDb->setName($_DBCONFIG['database']);
        $objDb->setTablePrefix($_DBCONFIG['tablePrefix']);
        $objDb->setDbType($_DBCONFIG['dbType']);
        $objDb->setCharset($_DBCONFIG['charset']);
        $objDb->setCollation($_DBCONFIG['collation']);
        $objDb->setTimezone((empty($_CONFIG['timezone'])?$_DBCONFIG['timezone']:$_CONFIG['timezone']));

        // Set database user details
        $objDbUser = new \Cx\Core\Model\Model\Entity\DbUser();
        $objDbUser->setName($_DBCONFIG['user']);
        $objDbUser->setPassword($_DBCONFIG['password']);

        // Initialize database connection
        $db = new \Cx\Core\Model\Db($objDb, $objDbUser);
        self::$database = $db->getAdoDb();
    }

    public function setUp() {
        self::$database->BeginTrans();
    }

    public function tearDown() {
        self::$database->RollbackTrans();
    }
}

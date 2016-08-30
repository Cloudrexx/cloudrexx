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
 * SettingTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Sergei Popov <sergei.popov@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_setting
 */

namespace Cx\Core\Setting\Testing\UnitTest;
use Cx\Core\Setting\Controller\Setting as Setting;


/**
 * SettingTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Sergei Popov <sergei.popov@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_setting
 */
class SettingTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase
{

    /** data init **/

    private function addValuesDB() {
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        Setting::add('key1.1', 'value1.1');
        Setting::add('key1.2', 'value1.2');
        Setting::add('test333','test333');

        Setting::init('MultiSiteTest', 'testgroup2', 'Database', null, Setting::POPULATE);
        Setting::add('key2.1', 'value2.1');
        Setting::add('key2.2', 'value2.2');

        Setting::init('ShopTest', 'testgroup3', 'Database', null, Setting::POPULATE);
        Setting::add('key3.1', 'value3.1');
        Setting::add('key3.2', 'value3.2');
    }

    private function deleteValuesDB() {
        Setting::init('MultiSiteTest', null, 'Database');
        Setting::deleteModule();
        Setting::init('ShopTest', null, 'Database');
        Setting::deleteModule();
    }

    private function addValuesYaml() {
        Setting::init('ConfigTest', 'testgroup4', 'Yaml');
        Setting::add('key4.1', 'value4.1');
        Setting::add('key4.2', 'value4.2');

        Setting::init('ConfigTest', 'testgroup5', 'Yaml', null, Setting::POPULATE);
        Setting::add('key5.1', 'value5.1');
        Setting::add('key5.2', 'value5.2');
    }

    private function deleteValuesYaml() {
        Setting::init('ConfigTest', null, 'Yaml');
        Setting::deleteModule();
    }

    private function addValuesFileSystem() {
        Setting::init('MultiSiteTest', 'testgroup6','FileSystem');
        Setting::add('key6.1', 'value6.1');
        Setting::add('key6.2', 'value6.2');

        Setting::init('MultiSiteTest', 'testgroup7','FileSystem', null, Setting::POPULATE);
        Setting::add('key7.1', 'value7.1');
        Setting::add('key7.2', 'value7.2');

        Setting::init('SupportTest', 'testgroup8','FileSystem', null, Setting::POPULATE);
        Setting::add('key8.1', 'value8.1');
        Setting::add('key8.2', 'value8.2');
    }

    private function deleteValuesFileSystem() {
        Setting::init('MultiSiteTest', null, 'FileSystem');
        Setting::deleteModule();
        Setting::init('SupportTest', null, 'FileSystem');
        Setting::deleteModule();
    }

    /** tests **/

    public function testInitWithAllSettings(){
        \DBG::log('*** UNITTEST START ***');
        Setting::init('MultiSiteTest', 'testgroup345','FileSystem');
        $this->assertEquals('MultiSiteTest', Setting::getCurrentSection());
        $this->assertEquals('testgroup345', Setting::getCurrentGroup());
        $this->assertEquals('FileSystem', Setting::getCurrentEngine());
    }

    public function testInitWithoutGroup(){
        \DBG::log('*** testInitWithoutGroup ***');
        Setting::init('MultiSiteTest', null,'FileSystem');
        $this->assertEquals('MultiSiteTest', Setting::getCurrentSection());
        $this->assertNull(Setting::getCurrentGroup());
        $this->assertEquals('FileSystem', Setting::getCurrentEngine());
    }

    public function testInitWithoutGroupAndDatabase(){
        \DBG::log('*** testInitWithoutGroupAndDatabase ***');
        Setting::init('MultiSiteTest');
        $this->assertEquals('MultiSiteTest', Setting::getCurrentSection());
        $this->assertNull(Setting::getCurrentGroup());
        $this->assertEquals('Database', Setting::getCurrentEngine());
    }

    public function testGetAndSetSectionEngine(){
        \DBG::log('*** testGetAndSetSectionEngine ***');
        Setting::init('MultiSiteTest', null,'Database');
        $oEngine = Setting::getSectionEngine();
        $this->assertEquals('MultiSiteTest', Setting::getCurrentSection());
        $this->assertEquals('Database',  Setting::getCurrentEngine());
        Setting::init('MultiSiteTest', null,'Database');
        Setting::setSectionEngine($oEngine, Setting::REPOPULATE);
        $oTestEngine = Setting::getSectionEngine();
        $this->assertEquals($oEngine, $oTestEngine);
    }

    public function testAddGetDeleteValueFromDatabase() {
        \DBG::log('*** testAddAndGetValueFromDatabase ***');
        $this->addValuesDB();
        Setting::init('ShopTest', null,'Database');
        Setting::init('MultiSiteTest', null,'Database');

        $this->assertEquals('value1.1', Setting::getValue('key1.1'));
        $this->assertEquals('value1.2', Setting::getValue('key1.2'));
        $this->assertEquals('value3.1', Setting::getValue('key3.1', 'ShopTest'));
        $this->assertEquals('value3.2', Setting::getValue('key3.2', 'ShopTest'));
        $this->assertEquals('value2.1', Setting::getValue('key2.1'));
        $this->assertEquals('value2.2', Setting::getValue('key2.2'));

        /** delete check **/
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        Setting::delete('key1.1');
        Setting::init('MultiSiteTest', 'testgroup1', 'Database');
        $this->assertNull(Setting::getValue('key1.1'));

        $this->deleteValuesDB();
        Setting::init('MultiSiteTest', null, 'Database');
        $this->assertEmpty(Setting::getSettings('MultiSiteTest', 'Database'));
        Setting::init('ShopTest', null, 'Database');
        $this->assertEmpty(Setting::getSettings('ShopTest', 'Database'));

    }

    public function testAddGetDeleteValueFromYaml() {
        \DBG::log('*** testAddAndGetValueFromYaml ***');
        $this->addValuesYaml();

        Setting::init('ConfigTest', null, 'Yaml');
        $this->assertEquals('value4.1', Setting::getValue('key4.1'));
        $this->assertEquals('value4.2', Setting::getValue('key4.2'));
        $this->assertEquals('value5.1', Setting::getValue('key5.1'));
        $this->assertEquals('value5.2', Setting::getValue('key5.2'));

        /** delete check **/
        Setting::delete('key4.1');
        Setting::init('ConfigTest', null, 'Yaml');
        $this->assertNull(Setting::getValue('key4.1'));

        $this->deleteValuesYaml();
        Setting::init('ConfigTest', null, 'Yaml');
        $this->assertEmpty(Setting::getSettings('ConfigTest', 'Yaml'));
    }

    public function testAddGetDeleteValueFromFileSystem() {
        \DBG::log('*** testAddAndGetValueFromFileSystem ***');
        $this->addValuesFileSystem();

        $this->addValuesFileSystem();
        Setting::init('MultiSiteTest', null,'FileSystem');
        $this->assertEquals('value6.1', Setting::getValue('key6.1'));
        $this->assertEquals('value6.2', Setting::getValue('key6.2'));
        $this->assertEquals('value8.1', Setting::getValue('key8.1','SupportTest'));
        $this->assertEquals('value8.2', Setting::getValue('key8.2','SupportTest'));
        $this->assertEquals('value7.1', Setting::getValue('key7.1'));
        $this->assertEquals('value7.2', Setting::getValue('key7.2'));

        /** delete check **/
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        Setting::delete('key1.1');
        Setting::init('MultiSiteTest', 'testgroup1', 'Database');
        $this->assertNull(Setting::getValue('key1.1'));

        $this->deleteValuesFileSystem();
        Setting::init('MultiSiteTest', null, 'FileSystem');
        $this->assertEmpty(Setting::getSettings('MultiSiteTest', 'FileSystem'));
        Setting::init('SupportTest', null, 'FileSystem');
        $this->assertEmpty(Setting::getSettings('SupportTest', 'FileSystem'));
    }

    public function testDatabaseGetArrayOfOneGroup() {
        \DBG::log('*** testDatabaseGetArrayOfOneGroup ***');
        $this->addValuesDB();
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        $val = Setting::getArray('MultiSiteTest','testgroup1');
        $this->assertEquals(3, count($val));
        $this->deleteValuesDB();
    }

    public function testDatabaseGetArrayOfAllGroups() {
        \DBG::log('*** testDatabaseGetArrayOfAllGroups ***');
        $this->addValuesDB();
        Setting::init('MultiSiteTest', null,'Database');
        $val = Setting::getArray('MultiSiteTest');
        $this->assertEquals(5, count($val));
        $this->deleteValuesDB();
    }

    public function testYamlGetArrayOfOneGroup() {
        \DBG::log('*** testYamlGetArrayOfOneGroup ***');
        $this->addValuesYaml();
        Setting::init('ConfigTest', 'testgroup4','Yaml');
        $val = Setting::getArray('ConfigTest','testgroup4');
        $this->assertEquals(2, count($val));
        $this->deleteValuesYaml();
    }

    public function testYamlGetArrayOfAllGroups() {
        \DBG::log('*** testYamlGetArrayOfAllGroups ***');
        $this->addValuesYaml();
        Setting::init('ConfigTest', null, 'Yaml');
        $val = Setting::getArray('ConfigTest');
        $this->assertEquals(4, count($val));
        $this->deleteValuesYaml();
    }

    public function testFileSystemGetArrayOfOneGroup() {
        \DBG::log('*** testFileSystemGetArrayOfOneGroup ***');
        $this->addValuesFileSystem();
        Setting::init('MultiSiteTest', 'testgroup6','FileSystem');
        $val = Setting::getArray('MultiSiteTest','testgroup6');
        $this->assertEquals(2, count($val));
        $this->deleteValuesFileSystem();
    }

    public function testFileSystemGetArrayOfAllGroups() {
        \DBG::log('*** testFileSystemGetArrayOfAllGroups ***');
        $this->addValuesFileSystem();
        Setting::init('MultiSiteTest', null,'FileSystem');
        $val = Setting::getArray('MultiSiteTest');
        $this->assertEquals(4, count($val));
        $this->deleteValuesFileSystem();
    }

    public function testSetDatabase() {
        \DBG::log('*** testSetDatabase ***');
        $this->addValuesDB();
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        Setting::set('key1.1','changedvalue1.1');
        $this->assertEquals('changedvalue1.1', Setting::getValue('key1.1'));
        $this->deleteValuesDB();
    }

    public function testUpdateDatabase() {
        \DBG::log('*** testUpdateDatabase ***');
        $this->addValuesDB();
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        Setting::set('key1.1', 'changedvalue1.1');
        Setting::update('key1.1');
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        $this->assertEquals('changedvalue1.1', Setting::getValue('key1.1'));
        $this->deleteValuesDB();
    }

    public function testUpdateAllInDatabase() {
        \DBG::log('*** testUpdateAllInDatabase ***');
        $this->addValuesDB();
        Setting::init('MultiSiteTest', null,'Database');
        Setting::set('key1.2','changedvalue1.2');
        Setting::set('key2.1','changedvalue2.1');
        Setting::set('key2.2','changedvalue2.2');
        Setting::updateAll();
        Setting::init('MultiSiteTest', null,'Database');
        $this->assertEquals('changedvalue1.2', Setting::getValue('key1.2'));
        $this->assertEquals('changedvalue2.1', Setting::getValue('key2.1'));
        $this->assertEquals('changedvalue2.2', Setting::getValue('key2.2'));
        $this->deleteValuesDB();
    }

    public function testSetYaml() {
        \DBG::log('*** testSetYaml ***');
        $this->addValuesYaml();
        Setting::init('ConfigTest', 'testgroup5', 'Yaml');
        Setting::set('key5.1','changedvalue5.1');
        $this->assertEquals('changedvalue5.1', Setting::getValue('key5.1'));
        $this->deleteValuesYaml();
    }

    public function testUpdateYaml() {
        \DBG::log('*** testUpdateYaml ***');
        $this->addValuesYaml();
        Setting::init('ConfigTest', 'testgroup5', 'Yaml');
        Setting::set('key5.1', 'changedvalue5.1');
        Setting::update('key5.1');
        Setting::init('ConfigTest', 'testgroup5', 'Yaml');
        $this->assertEquals('changedvalue5.1', Setting::getValue('key5.1'));
        $this->deleteValuesYaml();
    }

    public function testUpdateAllInYaml() {
        \DBG::log('*** testUpdateAllInYaml ***');
        $this->addValuesYaml();
        Setting::init('ConfigTest', null,'Yaml');
        Setting::set('key4.2','changedvalue4.2');
        Setting::set('key5.1','changedvalue5.1');
        Setting::set('key5.2','changedvalue5.2');
        Setting::updateAll();
        Setting::init('ConfigTest', null,'Yaml');
        $this->assertEquals('changedvalue4.2', Setting::getValue('key4.2'));
        $this->assertEquals('changedvalue5.1', Setting::getValue('key5.1'));
        $this->assertEquals('changedvalue5.2', Setting::getValue('key5.2'));
        $this->deleteValuesYaml();
    }

    public function testSetFileSystem() {
        \DBG::log('*** testSetFileSystem ***');
        $this->addValuesFileSystem();
        Setting::init('MultiSiteTest', 'testgroup7','FileSystem');
        Setting::set('key7.1', 'changedvalue7.1');
        $this->assertEquals('changedvalue7.1', Setting::getValue('key7.1'));
        $this->deleteValuesFileSystem();
    }

    public function testUpdateFileSystem() {
        \DBG::log('*** testUpdateFileSystem ***');
        $this->addValuesFileSystem();
        Setting::init('MultiSiteTest', 'testgroup7','FileSystem');
        Setting::set('key7.1', 'changedvalue7.1');
        Setting::update('key7.1');
        Setting::init('MultiSiteTest', 'testgroup7','FileSystem');
        $this->assertEquals('changedvalue7.1', Setting::getValue('key7.1'));
        $this->deleteValuesFileSystem();
    }

    public function testUpdateAllInFileSystem() {
        \DBG::log('*** testUpdateAllInFileSystem ***');
        $this->addValuesFileSystem();
        Setting::init('MultiSiteTest', null,'FileSystem');
        Setting::set('key6.2','changedvalue6.2');
        Setting::set('key7.1','changedvalue7.1');
        Setting::set('key7.2','changedvalue7.2');
        Setting::updateAll();
        Setting::init('MultiSiteTest', null,'FileSystem');
        $this->assertEquals('changedvalue6.2', Setting::getValue('key6.2'));
        $this->assertEquals('changedvalue7.1', Setting::getValue('key7.1'));
        $this->assertEquals('changedvalue7.2', Setting::getValue('key7.2'));
        $this->deleteValuesFileSystem();
    }


    public function testStoreFromPost() {
        \DBG::log('*** testStoreFromPost ***');
        global $_POST, $_FILES;
        Setting::init('MultiSiteTest', 'posttestgroup', 'FileSystem');
        //Setting::add('key10.1', '', false, Setting::TYPE_FILEUPLOAD);
        Setting::add('key10.2', '', false, Setting::TYPE_CHECKBOX);
        Setting::add('key10.3', '', false, Setting::TYPE_CHECKBOXGROUP);
        Setting::add('key10.4', '');

        $_POST = array(
            //'key10.1' => 'testfilename.txt'
            'key10.2' => 'postcheckboxvalue10',
            'key10.3' => array_flip(array(1,2,3,4,5,6)),
            'key10.4' => 'postvalue10'
        );
        try {
            Setting::storeFromPost();
        } catch(Exception $e) {
        } finally {
            Setting::init('MultiSiteTest', 'posttestgroup', 'FileSystem');
            $this->assertEquals('postcheckboxvalue10', Setting::getValue('key10.2'));
            $this->assertEquals('1,2,3,4,5,6', Setting::getValue('key10.3'));
            $this->assertEquals('postvalue10', Setting::getValue('key10.4'));
            Setting::init('MultiSiteTest', null, 'FileSystem');
            Setting::deleteModule();
        }
    }


    public function testDeleteGroupFromDatabase() {
        \DBG::log('*** testDeleteGroupFromDatabase ***');
        $this->addValuesDB();
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        Setting::delete(null, 'testgroup1');
        Setting::init('MultiSiteTest', 'testgroup1','Database');
        $this->assertNull(Setting::getValue('key1.1'));
        $this->deleteValuesDB();
    }


    public function testDeleteGroupFromYaml() {
        \DBG::log('*** testDeleteGroupFromYaml ***');
        $this->addValuesYaml();
        Setting::init('ConfigTest', 'testgroup5','Yaml');
        Setting::delete(null, 'testgroup5');
        Setting::init('ConfigTest', 'testgroup5','Yaml');
        $this->assertNull(Setting::getValue('key5.1'));
        $this->assertNull(Setting::getValue('key5.2'));
        $this->deleteValuesYaml();
    }

    public function testDeleteGroupFromFileSystem() {
        \DBG::log('*** testDeleteGroupFromFileSystem ***');
        $this->addValuesFileSystem();
        Setting::init('MultiSiteTest', 'testgroup7','FileSystem');
        Setting::delete(null, 'testgroup7');
        Setting::flush();
        $this->assertNull(Setting::getValue('key7.1'));
        $this->assertNull(Setting::getValue('key7.2'));
        $this->deleteValuesFileSystem();
    }

}

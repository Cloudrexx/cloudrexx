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
 * DataSet Test
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_modules_listing
 */

namespace Cx\Core_Modules\Listing\Testing\UnitTest;

/**
 * DataSet Test
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_modules_listing
 */
class DataSetTest extends \Cx\Core\Test\Model\Entity\ContrexxTestCase
{
    /**
     * @var array
     * This is our test array. It is built like a real-live example:
     * There's the data of one entity per row
     */
    protected $testArray = array(
        0 => array('field1' => 'value1', 'field2' => 'value2'),
        1 => array('field1' => 'value3', 'field2' => 'value4'),
        2 => array('field1' => 'value3', 'field2' => 'value6'),
    );

    /**
     * @var array
     * This is the expected output after flipping the array
     */
    protected $flippedArray = array(
        'field1' => array(0 => 'value1', 1 => 'value3', 2 => 'value3'),
        'field2' => array(0 => 'value2', 1 => 'value4', 2 => 'value6'),
    );

    /**
     * @var array
     * This is the expected output after sorting for "field1" descending
     * followed by "field2" ascending
     */
    protected $sortedArray = array(
        1 => array('field1' => 'value3', 'field2' => 'value4'),
        2 => array('field1' => 'value3', 'field2' => 'value6'),
        0 => array('field1' => 'value1', 'field2' => 'value2'),
    );

    /**
     * @var array
     * This is the expected output after sorting columns descending
     */
    protected $sortedColumnsArray = array(
        0 => array('field2' => 'value2', 'field1' => 'value1'),
        1 => array('field2' => 'value4', 'field1' => 'value3'),
        2 => array('field2' => 'value6', 'field1' => 'value3'),
    );

    public function testFlip() {
        $testSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($this->testArray);
        $flippedSet = $testSet->flip();
        $this->assertEquals($this->flippedArray, $flippedSet->toArray());
    }

    public function testSort() {
        $testSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($this->testArray);
        $sortedSet = $testSet->sort(array(
            'field1' => SORT_DESC,
            'field2' => SORT_ASC,
        ));
        $this->assertEquals($this->sortedArray, $sortedSet->toArray());
    }

    public function testSortColumns() {
        $testSet = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($this->testArray);
        $testSet->sortColumns(array('field2', 'field1'));
        $this->assertEquals($this->sortedColumnsArray, $testSet->toArray());
    }
}

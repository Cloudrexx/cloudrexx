<?php declare(strict_types=1);

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
 * An example for Cloudrexx UnitTests
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  modules_skeleton
 */

namespace Cx\Core\ContentManager\Testing\UnitTest;

/**
 * An example for Cloudrexx UnitTests
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  modules_skeleton
 */
class SampleTest extends \Cx\Core\Test\Model\Entity\ContrexxTestCase
{

    /**
     * Tests if "foo" equals "bar". Only successful if "foo" equals "bar".
     */
    public function testFooBar() {
        // This will never be successful. Make it do something useful!
        $this->assertEquals('foo', 'bar');
    }
}

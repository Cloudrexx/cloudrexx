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
 * LinkSanitizerTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_linkSanitizer
 */

namespace Cx\Core\Testing\UnitTest;

/**
 * LinkSanitizerTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core_linkSanitizer
 */
class LinkSanitizerTest extends \Cx\Core\Test\Model\Entity\ContrexxTestCase {
    public function testReplace() {
        //src, "
        $content = '<img src="index.php?cmd=a&module=b" />';
        $result = '<img src="/cms/de/index.php?cmd=a&module=b" />';
        $this->checkSanitizing($content, $result);

        //href, '
        $content = "<a href='index.php' />";
        $result = "<a href='/index.php' />";
        $this->checkSanitizing($content, $result);

        //multiple matches
        $content = '<img src="first" /><img src="second" />';
        $result = '<img src="/cms/de/first" /><img src="/cms/de/second" />';
        $this->checkSanitizing($content, $result);

        //absolute links preserval
        $content = '<a href="/cms/index.php" />';
        $result = $content;
        $this->checkSanitizing($content, $result);

        //absolute links preserval
        $content = '<a href="/images/pic.jpg" />';
        $result = $content;
        $this->checkSanitizing($content, $result);

        //foreign links preserval
        $content = '<a href="http://www.google.ch" />';
        $result = $content;
        $this->checkSanitizing($content, $result);

    }

    protected function checkSanitizing($in, $expectedOut) {
        $offset = '/cms/';
        $langDir = 'de/';

        $ls = new \LinkSanitizer($offset.$langDir, $in);
        $this->assertEquals($expectedOut, $ls->replace());
    }
}

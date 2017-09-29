<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2017
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
 * LanguageTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 */

namespace Cx\Core\Locale\Testing\UnitTest;

/**
 * LanguageTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 */
class LanguageTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase {

    /**
     * Tests if magic method __toString returns combination of
     * display name (translated to system language) and iso-1 code
     * e.g. German (de), with English as system language
     */
    public function testToString() {
        // Arrange
        $iso1 = 'en';
        $translateIn = 'de';
        $expected =
            \Locale::getDisplayLanguage($iso1, $translateIn) . ' (' . $iso1 . ')';
        // set system language to de
        define('LANG_ID', 1);
        // Act
        $language = self::$em->find('\Cx\Core\Locale\Model\Entity\Language', $iso1);
        // Arrange
        $this->assertEquals($expected, $language->__toString());
    }

    /**
     * Tests the setter and getter methods
     */
    public function testSetterAndGetter() {
        // Arrange
        $iso1 = 'de';
        $iso3 = 'deu';
        $backend = $this->getMock('\Cx\Core\Locale\Model\Entity\Backend');
        $language = new \Cx\Core\Locale\Model\Entity\Language();
        // Act
        $language->setIso1($iso1);
        $language->setIso3($iso3);
        $language->setSource(true);
        $language->setBackend($backend);
        // Assert
        $this->assertEquals($iso1, $language->getIso1());
        $this->assertEquals($iso3, $language->getIso3());
        $this->assertTrue($language->getSource());
        $this->assertEquals($backend, $language->getBackend());
        $this->assertInstanceOf(
            '\Doctrine\Common\Collections\ArrayCollection',
            $language->getLocaleRelatedBySourceLanguages()
        );
        $this->assertInstanceOf(
            '\Doctrine\Common\Collections\ArrayCollection',
            $language->getLocaleRelatedByIso1s()
        );
    }

}
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
 * CountryTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_country
 */

namespace Cx\Core\Country\Testing\UnitTest;

/**
 * CountryTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_country
 */
class CountryTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase {

    /**
     * Tests if magic method __toString returns combination of
     * display region (translated to system language) and alpha2 code
     * e.g. Germany (DE), with English as system language
     */
    public function testToString() {
        // Arrange
        $alpha2 = 'DE';
        $translateIn = 'de';
        $expected =
            \Locale::getDisplayRegion('und_' . $alpha2, $translateIn) . ' (' . $alpha2 . ')';
        // set system language to de
        define('LANG_ID', 1);
        // Act
        $country = self::$em->find('\Cx\Core\Country\Model\Entity\Country', $alpha2);
        // Assert
        $this->assertEquals($expected, $country->__toString());
    }

    /**
     * Tests the setter and getter methods
     */
    public function testSetterAndGetter() {
        // Arrange
        $alpha2 = 'DE';
        $alpha3 = 'DEU';
        $ord = 1;
        $country = new \Cx\Core\Country\Model\Entity\Country();
        // Act
        $country->setAlpha2($alpha2);
        $country->setAlpha3($alpha3);
        $country->setOrd($ord);
        // Assert
        $this->assertEquals($alpha2, $country->getAlpha2());
        $this->assertEquals($alpha3, $country->getAlpha3());
        $this->assertEquals($ord, $country->getOrd());
        $this->assertInstanceOf(
            '\Doctrine\Common\Collections\ArrayCollection',
            $country->getLocales()
        );
    }

}
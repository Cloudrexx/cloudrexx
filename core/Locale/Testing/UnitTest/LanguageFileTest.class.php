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
 * LanguageFileTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 */

namespace Cx\Core\Locale\Testing\UnitTest;

/**
 * LanguageFileTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 */
class LanguageFileTest extends \Cx\Core\Test\Model\Entity\ContrexxTestCase {

    /**
     * Tests the constructor of LanguageFile
     *
     * Tests if the correct path to the yaml file is set,
     * if the locale and the identifier are set correctly
     */
    public function testConstructor() {
        // Arrange
        $expectedPath = ASCMS_CUSTOMIZING_PATH . '/lang/de/frontend.yaml';
        $expectedIdentifier = 'Cx\Core\Locale\Model\Entity\LanguageFile';

        // mock source language
        $sourceLanguageMock = $this
            ->getMockBuilder('\Cx\Core\Locale\Model\Entity\Language')
            ->getMock();
        $sourceLanguageMock->method('getIso1')->willReturn('de');

        // mock locale
        $localeMock = $this
            ->getMockBuilder('\Cx\Core\Locale\Model\Entity\Locale')
            ->getMock();
        $localeMock->method('getId')->willReturn(1);
        $localeMock->method('getSourceLanguage')->willReturn($sourceLanguageMock);

        // Act
        $languageFile = new \Cx\Core\Locale\Model\Entity\LanguageFile(
            $localeMock
        );

        // Assert
        // check path
        $this->assertEquals($expectedPath, $languageFile->getPath());
        // check locale
        $this->assertEquals($localeMock, $languageFile->getLocale());
        // check identifier
        $this->assertEquals($expectedIdentifier, $languageFile->getIdentifier());
    }

    /**
     * Tests if the language data is correctly updated when
     * adding a placeholder
     */
    public function testUpdateLanguageData() {
        // Arrange
        $placeholderName = 'TXT_CORE_SUBMIT';
        $placeholderValue = 'Testvalue';

        // mock source language
        $sourceLanguageMock = $this
            ->getMockBuilder('\Cx\Core\Locale\Model\Entity\Language')
            ->getMock();
        $sourceLanguageMock->method('getIso1')->willReturn('de');

        // mock locale
        $localeMock = $this
            ->getMockBuilder('\Cx\Core\Locale\Model\Entity\Locale')
            ->getMock();
        $localeMock->method('getId')->willReturn(1);
        $localeMock->method('getSourceLanguage')->willReturn($sourceLanguageMock);

        // mock placeholder
        $placeholderMock = $this
            ->getMockBuilder('\Cx\Core\Locale\Model\Entity\Placeholder')
            ->getMock();
        $placeholderMock->method('getName')->willReturn($placeholderName);
        $placeholderMock->method('getValue')->willReturn($placeholderValue);

        // instanciate Language File
        $languageFile = new \Cx\Core\Locale\Model\Entity\LanguageFile(
            $localeMock
        );

        // Act
        $languageFile->addPlaceholder($placeholderMock);
        $languageFile->updateLanguageData();

        // Assert
        $this->assertEquals(
            $placeholderValue,
            $languageFile->getData()[$placeholderName]
        );
    }

}
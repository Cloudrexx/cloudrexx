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



namespace Cx\Core_Modules\TemplateEditor\Testing\UnitTest;

use Cx\Core\Html\Sigma;
use Cx\Core\Test\Model\Entity\ContrexxTestCase;
use Cx\Core_Modules\TemplateEditor\Model\Entity\AreaOption;
use Cx\Core_Modules\TemplateEditor\Model\Entity\ColorOption;
use Cx\Core_Modules\TemplateEditor\Model\Entity\ImageOption;
use Cx\Core_Modules\TemplateEditor\Model\Entity\ImageSeriesOption;
use Cx\Core_Modules\TemplateEditor\Model\Entity\OptionValueNotValidException;
use Cx\Core_Modules\TemplateEditor\Model\Entity\TextOption;

/**
 * Class OptionTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class OptionTest extends ContrexxTestCase
{

    protected $template = '<!-- BEGIN option -->
        <div class="option {TEMPLATEEDITOR_OPTION_TYPE}">
            {TEMPLATEEDITOR_OPTION}
        </div>
        <!-- END option -->';

    protected function setUp(): void {
        global $_LANGID;
        $_LANGID = 1;
        \Env::get('init')->loadLanguageData('TemplateEditor');
    }

    public function testTextOption() {
        $testValue       = 'TestString';
        $textOption      = new TextOption(
            'test', array(1 => 'Unit-Test'),
            array('textvalue' => $testValue, 'regex' => '/^[a-z]+$/i', 'regexError' => array(1 => 'Darf nur Buchstaben enthalten: %s'))
        );
        $backendTemplate = new Sigma();
        $backendTemplate->setTemplate($this->template);

        $invalidValue = 'hello1';
        try {
            $textOption->handleChange($invalidValue);
        } catch (OptionValueNotValidException $e) {
            $this->assertTrue((strpos($e->getMessage(), $invalidValue) !== 0));
        }
        $textOption->renderOptionField($backendTemplate);
        $renderedTemplate = $backendTemplate->get();
        $this->assertTrue((strpos($renderedTemplate, $testValue) !== 0));
    }

    public function testAreaOption() {
        $areaOption      = new AreaOption(
            'test', array(1 => 'Unit-Test'),
            array('active' => true)
        );
        $backendTemplate = new Sigma();
        $backendTemplate->setTemplate($this->template);
        $areaOption->renderOptionField($backendTemplate);
        $renderedTemplate = $backendTemplate->get();
        $this->assertTrue((strpos($renderedTemplate, 'checked') !== 0));
    }

    public function testColorOption() {
        $color            = '#efefef';
        $choice            = array('#ededed', '#fefefe');
        $colorOption      = new ColorOption(
            'test', array(1 => 'Unit-Test'),
            array(
                'color' => $color,
                'choice' => $choice
            )
        );
        $backendTemplate = new Sigma();
        $backendTemplate->setTemplate($this->template);
        $colorOption->renderOptionField($backendTemplate);
        $renderedTemplate = $backendTemplate->get();
        foreach ($choice as $colorChoice) {
            $this->assertTrue((strpos($renderedTemplate, $colorChoice) !== 0));
        }
        $this->assertTrue((strpos($renderedTemplate, $color) !== 0));
    }

    public function testImageOption() {
        $url = 'https://placekitten.com/1500/300';
        $imageOption = new ImageOption( 'test', array(1 => 'Unit-Test'),
            array(
                'url' => $url
            ));
        $backendTemplate = new Sigma();
        $backendTemplate->setTemplate($this->template);
        $imageOption->renderOptionField($backendTemplate);
        $renderedTemplate = $backendTemplate->get();
        $this->assertTrue((strpos($renderedTemplate, $url) !== 0));
    }

    public function testImageSeriesOption() {
        $urls = array('https://placekitten.com/1500/300');
        $imageOption = new ImageSeriesOption( 'test', array(1 => 'Unit-Test'),
            array(
                'urls' => $urls
            ));
        $backendTemplate = new Sigma();
        $backendTemplate->setTemplate($this->template);
        $imageOption->renderOptionField($backendTemplate);
        $renderedTemplate = $backendTemplate->get();
        foreach ($urls as $url) {
            $this->assertTrue((strpos($renderedTemplate, $url) !== 0));
        }
    }

}

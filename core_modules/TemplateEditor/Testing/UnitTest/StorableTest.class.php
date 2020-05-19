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

use Cx\Core\Test\Model\Entity\ContrexxTestCase;
use Cx\Core\View\Model\Entity\Theme;
use Cx\Core_Modules\TemplateEditor\Model\Entity\ColorOption;
use Cx\Core_Modules\TemplateEditor\Model\Entity\OptionSet;
use Cx\Core_Modules\TemplateEditor\Model\Repository\OptionSetRepository;
use Cx\Core_Modules\TemplateEditor\Model\Storable;
use Cx\Core_Modules\TemplateEditor\Model\TestStorage;

/**
 * Class StorableTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class StorableTest extends ContrexxTestCase
{
    /**
     * @var Storable
     */
    protected $testStorage;

    /**
     * @var OptionSetRepository
     */
    protected $themeOptionRepository;

    protected function setUp(): void
    {
        $this->testStorage = new TestStorage();
        $this->themeOptionRepository = new OptionSetRepository($this->testStorage);
    }

    public function testLoadOption()
    {
        $themeOption = $this->themeOptionRepository->get(new Theme(null, null, 'standard_3_0'));
        $this->assertTrue($themeOption instanceof OptionSet);
        if ($themeOption instanceof OptionSet) {
            $this->assertTrue($themeOption->getOption('main_color') instanceof ColorOption);
        }
    }

    public function testSaveOption()
    {
        $themeOption = $this->themeOptionRepository->get(new Theme(null, null, 'standard_3_0'));
        $newColor = 'dddddd';
        $this->assertTrue($themeOption instanceof OptionSet);
        if ($themeOption instanceof OptionSet) {
            /**
             * @var $color ColorOption
             */
            $color = $themeOption->getOption('main_color');
            $color->handleChange($newColor);
            $this->assertTrue($color->getColor() == $newColor);
        }

        $this->assertTrue($this->themeOptionRepository->save($themeOption));
    }

}

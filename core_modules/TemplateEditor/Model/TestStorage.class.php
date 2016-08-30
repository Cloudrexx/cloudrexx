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


namespace Cx\Core_Modules\TemplateEditor\Model;


use Cx\Core\Core\Controller\Cx;
use Symfony\Component\Yaml\Yaml;

/**
 * Class TestStorage
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class TestStorage implements Storable
{

    /**
     * @param String $name
     *
     * @return array
     */
    public function retrieve($name)
    {
        return Yaml::load(
            file_get_contents(
                Cx::instanciate()->getCodeBaseCoreModulePath()
                . '/TemplateEditor/Testing/UnitTest/component.yml'
            )
        );
    }

    /**
     * @param                  $name
     * @param YamlSerializable $data
     *
     * @return bool
     */
    public function persist($name, YamlSerializable $data)
    {
        return true;
    }

    /**
     * @return array
     */
    public function getList()
    {
        return [];
    }

    /**
     * @param $name
     */
    public function remove($name) {}
}

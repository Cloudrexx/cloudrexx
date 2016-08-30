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


namespace Cx\Core_Modules\TemplateEditor\Model\Repository;

use Cx\Core_Modules\TemplateEditor\Model\Entity\Preset;
use Cx\Core_Modules\TemplateEditor\Model\Storable;

/**
 * Class ThemeOptionsRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class PresetRepository
{

    /**
     * @var Storable
     */
    protected $storage;

    /**
     * @param Storable $storage
     */
    public function __construct(Storable $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param       $name
     *
     * @return Preset
     */
    public function getByName($name)
    {
        return Preset::createFromArray($name,$this->storage->retrieve($name));
    }

    /**
     * Find all presets
     *
     * @return array
     */
    public function findAll()
    {
        return $this->storage->getList();
    }

    /**
     * Save a ThemeOptions entity to the component.yml file.
     *
     * @param Preset $entity
     *
     * @return bool
     */
    public function save($entity)
    {
        return $this->storage->persist($entity->getName(), $entity);
    }

    /**
     * @param $entity
     */
    public function remove($entity) {
        return $this->storage->remove($entity->getName());
    }


}

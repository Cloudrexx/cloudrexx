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
 * YamlSetting Repository
 *
 * Repository to manage the YamlSetting entities.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_setting
 */

namespace Cx\Core\Setting\Model\Repository;

/**
 * YamlSetting Repository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_setting
 */
class YamlSettingRepositoryException extends \Exception {};

/**
 * YamlSetting Repository
 *
 * Repository to manage the YamlSetting entities.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_setting
 */
class YamlSettingRepository extends \Cx\Core\Model\Controller\YamlRepository {

    public function __construct($repositoryPath) {
        parent::__construct($repositoryPath);
    }

    protected function load() {
        if (!parent::load()) {
            $this->entityIdentifier = 'id';
            $this->entityUniqueKeys = array('name');
        }
    }
}

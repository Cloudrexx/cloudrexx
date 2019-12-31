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
 * Specific ENUM type class for Doctrine mapping
 *
 * The namespace of this class contains information about the component,
 * entity and field it is generated for. Namespace scheme is:
 * \Cx\Core\Model\Data\Enum\<component_name>\<entity_name>
 * The class name equals the field's name (starting with an uppercase letter).
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Data\Enum\User\User;

/**
 * Specific ENUM type class for Doctrine mapping
 *
 * The namespace of this class contains information about the component,
 * entity and field it is generated for. Namespace scheme is:
 * \Cx\Core\Model\Data\Enum\<component_name>\<entity_name>
 * The class name equals the field's name (starting with an uppercase letter).
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  core_model
 */
class EmailAccess extends \Cx\Core\Model\Model\Entity\EnumType {
    
    /**
     * Sets the possible values for this ENUM type
     */
    protected $values = array('everyone', 'members_only', 'nobody');
}

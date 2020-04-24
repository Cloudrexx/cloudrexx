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
 * 
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Model\Entity;

/**
 * Generic ENUM type to make 'enum' available as a type to doctrine.
 *
 * Mappings using the 'enum' type will be replaced during run-time
 * so they use a specific sub-class of this class for each field.
 * The respective classes are automatically generated.
 * @see \Cx\Core\Model\Controller\YamlDriver
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */
class EnumType extends \Doctrine\DBAL\Types\Type {

    /**
     * This needs to be filled by sub-classes
     * @var array List of possible values for this ENUM
     */
    protected $values = array();

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return 'ENUM(\'' . implode('\',\'', $this->values) . '\')';
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLength(\Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ENUM';
    }
}

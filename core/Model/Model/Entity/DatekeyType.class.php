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
 * DATE as (primary) key support for Doctrine
 * @copyright   Cloudrexx AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Model\Model\Entity;

/**
 * DATE as (primary) key support for Doctrine
 * @see https://stackoverflow.com/questions/17125863/symfony-doctrine-datetime-as-primary-key
 * @copyright   Cloudrexx AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */
class DatekeyType extends \Doctrine\DBAL\Types\DateType {

    /**
     * {@inheritdoc}
     */
    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return 'DATE';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        $value = parent::convertToPHPValue($value, $platform);
        if ($value !== NULL) {
            $value = Datekey::fromDateTime($value);
        }
        return $value;
    }


    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }
        // If the value of the field is NULL the method convertToDatabaseValue() is not called.
        // http://doctrine-orm.readthedocs.org/en/latest/cookbook/custom-mapping-types.html
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $dateTime = $cx->getComponent('DateTime');
        $dateTime->intern2db($value);
        return $value->format('Y-m-d');
    }

    /**
     * {@inheritdoc}
     * Must be like the first parameter to \Doctrine\DBAL\Types\Type::addType()
     * (lower case)
     */
    public function getName()
    {
        return 'datekey';
    }

    /**
     * As this Doctrine Type maps to an already mapped database type,
     * reverse schema engineering can't tell them apart. You need to mark
     * one of those types as commented, which will have Doctrine use an SQL
     * comment to typehint the actual Doctrine Type.
     * @param \Doctrine\DBAL\Platforms\AbstractPlatform $platform
     * @return bool
     */
    public function requiresSQLCommentHint(\Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return true;
    }

}

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
 * TIMESTAMP support for Doctrine
 *
 * Timezone does not need to be handled here since doctrine converts the
 * timezone between database and internal.
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */
class TimestampType extends \Doctrine\DBAL\Types\Type {

    /**
     * {@inheritdoc}
     */
    public function getSqlDeclaration(array $fieldDeclaration, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        return 'TIMESTAMP';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }
        $val = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if (!$val) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), 'Y-m-d H:i:s');
        }
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $dateTime = $cx->getComponent('DateTime');
        $dbDate = $dateTime->createDateTimeForDb();
        $dbDate->setTime($val->format('H'), $val->format('i'), $val->format('s'));
        $dbDate->setDate($val->format('Y'), $val->format('m'), $val->format('d'));
        return $dateTime->db2intern($dbDate);
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
        return $value->format('Y-m-d H:i:s');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'timestamp';
    }
}

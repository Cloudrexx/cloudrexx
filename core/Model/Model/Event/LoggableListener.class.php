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
 * Wrapper class for the Gedmo\Loggable\LoggableListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     3.1.2
 * @package     cloudrexx
 * @subpackage  core
 */

namespace Cx\Core\Model\Model\Event;


class LoggableListenerException extends \Exception { }

/**
 * Wrapper class for the Gedmo\Loggable\LoggableListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core
 */
class LoggableListener extends \Gedmo\Loggable\LoggableListener {

    /**
     * {@inheritDoc}
     */
    protected function getEventAdapter(\Doctrine\Common\EventArgs $args) {
        parent::getEventAdapter($args);

        $class = get_class($args);
        if (preg_match('@Doctrine\\\([^\\\]+)@', $class, $m) && $m[1] == 'ORM') {
            $this->adapters[$m[1]] = new ORM();
            $this->adapters[$m[1]]->setEventArgs($args);
        }
        if (isset($this->adapters[$m[1]])) {
            return $this->adapters[$m[1]];
        } else {
            throw new LoggableListenerException('Event mapper does not support event arg class: '.$class);
        }
    }

    /**
     * Returns the log entity class for a given entity class
     *
     * This does not tell whether an entity is loggable or not.
     * @todo As metadata needs to be read for this we should cache the result
     * @param string $entityClassName Entity class name
     * @return string Log entity class name
     */
    public function getLogEntryClassForEntityClass(string $entityClassName): string {
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $event = new \Doctrine\ORM\Event\LoadClassMetadataEventArgs(
            $em->getClassMetadata($entityClassName),
            $em
        );

        // Metadata is not always pre-read into cache. Therefore we need to
        // force a metadata reload for this entity class.
        $this->loadClassMetadata($event);
        return $this->getLogEntryClass(
            $this->getEventAdapter(
                $event
            ),
            $entityClassName
        );
    }
}

<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2016
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



namespace Cx\Core_Modules\Access\Model\Event;

/**
 * LocaleLocaleEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 */
class LocaleLocaleEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener {

    /**
     * Fills the locale specific user attribute names for the new locale
     * with attribute values of the the default locale
     * when adding a new Cx\Core\Locale\Model\Entity\Locale
     *
     * @param $eventArgs
     */
    public function postPersist($eventArgs) {
        // get persisted locale
        $persistedLocale = $eventArgs->getEntity();

        $defaultLocaleId = \FWLanguage::getDefaultLangId();
        $localeId = $persistedLocale->getId();

        $em = $this->cx->getDb()->getEntityManager();
        $attributeNameRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttributeName');
        $attributeNames = $attributeNameRepo->findBy(array('langId' => $defaultLocaleId));

        // Add user attribute names for new locale
        foreach ($attributeNames as $attributeName) {
            $newAttributeName = $attributeNameRepo->findOneBy(
                array('userAttribute' => $attributeName->getUserAttribute()->getId(), 'langId' => $localeId)
            );
            if (!empty($newAttributeName)) {
                continue;
            }
            $newAttributeName = new \Cx\Core\User\Model\Entity\UserAttributeName();
            $newAttributeName->setUserAttribute($attributeName->getUserAttribute());
            $newAttributeName->setName($attributeName->getName());
            $newAttributeName->setLangId($localeId);

            $em->persist($newAttributeName);
        }
    }

    /**
     * Deletes the locale specific user attribute names
     * when deleting a Cx\Core\Locale\Model\Entity\Locale
     *
     * @param $eventArgs
     */
    public function preRemove($eventArgs) {
        // get locale, which will be deleted
        $delLocale = $eventArgs->getEntity();
        $localeId = $delLocale->getId();

        $em = $this->cx->getDb()->getEntityManager();
        $attributeNameRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttributeName');
        $attributeNames = $attributeNameRepo->findBy(array('langId' => $localeId));

        // Update the access user attributes
        foreach ($attributeNames as $attributeName) {
            $em->remove($attributeName);
        }
        $em->flush();
    }
}
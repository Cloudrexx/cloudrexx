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



namespace Cx\Core\View\Model\Event;

/**
 * LocaleLocaleEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 */
class LocaleLocaleEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener {

    /**
     * Adds new frontend entities for each channel
     * when persisting a new locale
     *
     * @param $eventArgs
     */
    public function postPersist($eventArgs) {

        $em = $eventArgs->getEntityManager();
        // get persisted locale
        $persistedLocale = $eventArgs->getEntity();

        // get default frontends (one for each Cx\Core\View\Model\Entity\Theme channel)
        $frontendRepo = $em->getRepository('Cx\Core\View\Model\Entity\Frontend');
        $defaultFrontends = $frontendRepo->findBy(array('language' => \FWLanguage::getDefaultLangId()));
        foreach ($defaultFrontends as $defaultFrontend) {
            if ($defaultFrontend) {
                // create new frontend entity
                $newFrontend = clone $defaultFrontend;
                // set iso1 of persisted locale
                $newFrontend->setLanguage($persistedLocale->getId());
                // set effective association to language entity
                $newFrontend->setLocaleRelatedByIso1s(
                    $persistedLocale
                );
                $em->persist($newFrontend);
            }
        }
        $em->flush();
    }

    /**
     * Deletes the frontend entities of a deleted locale
     *
     * @param $eventArgs
     */
    public function preRemove($eventArgs) {

        $em = $eventArgs->getEntityManager();
        // get frontend entities associated to locale
        $localeToDel = $eventArgs->getEntity();
        $frontendRepo = $em->getRepository('Cx\Core\View\Model\Entity\Frontend');
        // delete frontend entity with deleted locale's iso1 code
        $frontendsOfLocale = $frontendRepo->findBy(array('language' => $localeToDel->getId()));
        foreach ($frontendsOfLocale as $frontend) {
            $em->remove($frontend);
        }
    }
}
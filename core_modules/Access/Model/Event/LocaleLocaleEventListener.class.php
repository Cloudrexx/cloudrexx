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
     * with attribute values of the the default locale or lang placeholders
     * when adding a new Cx\Core\Locale\Model\Entity\Locale
     *
     * @param $eventArgs
     */
    public function postPersist($eventArgs) {
        global $_CORELANG;

        // get persisted locale
        $persistedLocale = $eventArgs->getEntity();
        $source = $persistedLocale->getSourceLanguage()->getIso1();

        $_CORELANG = \Env::get('init')->getComponentSpecificLanguageDataByCode(
            'Core', true, $source
        );

        $em = $this->cx->getDb()->getEntityManager();
        $attributeRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttribute');
        $attributes = $attributeRepo->findAll();

        foreach ($attributes as $attribute) {
            if ($attribute->isDefault()) {
                if (!$attribute->arrDefaultAttributeTemplates[$attribute->getContext()]) {
                    continue;
                }

                $template = $attribute->arrDefaultAttributeTemplates[
                $attribute->getContext()
                ];
                $name = $_CORELANG[$template['desc']];
            } else {
                $name = $attribute->getName();
            }

            $attribute->setTranslatableLocale($persistedLocale->getIso1()->getIso1());
            $em->refresh($attribute);
            $attribute->setName($name);
            $em->persist($attribute);
            $em->flush();
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

        $em = $this->cx->getDb()->getEntityManager();
        $attributeRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttribute');
        // Only delete custom attributes names so that we can keep the names of the default attributes
        $attributes = $attributeRepo->findBy(array('default' => 0));
        // Update the access user attributes
        foreach ($attributes as $attribute) {
            $defaultName = $attribute->getName();
            $attribute->setTranslatableLocale($delLocale->getIso1()->getIso1());
            $em->refresh($attribute);
            $attribute->setName($defaultName);
            $em->persist($attribute);
            $em->flush();
        }
    }
}

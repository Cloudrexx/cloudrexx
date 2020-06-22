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
 * Class EsiWidgetController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_languagemanager
 * @version     1.0.0
 */

namespace Cx\Core\LanguageManager\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_languagemanager
 * @version     1.0.0
 */

class EsiWidgetController extends \Cx\Core_Modules\Widget\Controller\EsiWidgetController {

    /**
     * Parses a widget
     *
     * @param string                                 $name     Widget name
     * @param \Cx\Core\Html\Sigma              $template Template
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object
     * @param array                                  $params   Get parameters
     */
    public function parseWidget($name, $template, $response, $params)
    {
        if (!isset($params['query'])) {
            $params['query'] = array();
        }
        if ($name == 'locale_navbar') {

            $currentPage = $params['page'];

            $listProtectedPages = \Cx\Core\Setting\Controller\Setting::getValue(
                'coreListProtectedPages',
                'Config'
            ) == 'on';

            foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
                $languageCode = $lang['iso1'];
                $langId = $lang['id'];
                $lang = $lang['lang'];
                $langPage = $currentPage->getNode()->getPage($langId);
                // if page is not translated,
                // inactive (incl. scheduled publishing) or protected
                if (
                    !$langPage ||
                    !$langPage->isActive() ||
                    (
                        !$listProtectedPages &&
                        $langPage->isFrontendProtected() &&
                        !\Permission::checkAccess(
                            $langPage->getFrontendAccessId(),
                            'dynamic',
                            true
                        )
                    )
                ) {
                    continue;
                }

                $template->setVariable(
                    array(
                        'PAGE_LINK' => contrexx_raw2xhtml(
                            \Cx\Core\Routing\Url::fromPage(
                                $langPage,
                                $params['query']
                            )->toString()
                        ),
                        'PAGE_TITLE' => contrexx_raw2xhtml($langPage->getTitle()),
                        'LOCALE' => $lang,
                        'LANGUAGE_CODE' => $languageCode,
                    )
                );
                if ($lang == $params['locale']->getShortForm()) {
                    $template->touchBlock('current_locale');
                }
                $template->parse($name);
            }
            return;
        }

        if ($name === 'CHARSET') {
            $template->setVariable($name, CONTREXX_CHARSET);
            return;
        }

        if ($name == 'ACTIVE_LANGUAGE_NAME') {
            $template->setVariable(
                $name,
                $params['locale']->getShortForm()
            );
            return;
        }

        $matches = null;

        if (
            preg_match(
                '/^LANG_SELECTED_([A-Z]{1,2}(?:_[A-Z]{2,4})?)$/',
                $name,
                $matches // E.g., "FR_CH"
            )
        ) {
            $selected = '';
            $langCode = $params['locale']->getShortForm(); // E.g., "fr-CH"
            if (str_replace('_', '-', $matches[1]) === strtoupper($langCode)) {
                $selected = 'selected';
            }
            $template->setVariable($name, $selected);
            return;
        }

        $page = $params['page'];
        if (!$page) {
            return;
        }

        if ($name === 'LANGUAGE_NAVBAR') {
            $template->setVariable(
                $name,
                $this->getFrontendLangNavigation($page, $params['query'])
            );
            return;
        }

        if ($name === 'LANGUAGE_NAVBAR_SHORT') {
            $template->setVariable(
                $name,
                $this->getFrontendLangNavigation($page, $params['query'], true)
            );
            return;
        }

        $langMatches = null;
        if (
            preg_match(
                '/^LANG_CHANGE_([A-Z]{1,2}(?:_[A-Z]{2,4})?)$/',
                $name,
                $langMatches
            )
        ) {
            // make iso1 part of code lowercase (e.g DE-CH --> de-CH)
            $code = explode('_', $langMatches[1]);
            $code[0] = strtolower($code[0]);
            $code = implode('-', $code);

            $locale = $this->cx->getDb()->getEntityManager()
                ->getRepository('\Cx\Core\Locale\Model\Entity\Locale')
                ->findOneByCode($code);

            // return early and don't set variable if locale doesn't exist
            if (!$locale) {
                return;
            }

            $template->setVariable(
                $name,
                $this->getLanguageLinkById($page, $locale->getId(), $params['query'])
            );
        }
    }

    /**
     * Get frontend language navigation
     *
     * @param Cx\Core\ContentManager\Model\Entity\Page $page                page object
     * @param boolean                                  $langNameContraction If true, display short lang name
     *
     * @return string
     */
    protected function getFrontendLangNavigation($page, $params, $langNameContraction = false)
    {
        $activeLanguages = \FWLanguage::getActiveFrontendLanguages();
        $node = $page->getNode();

        $langNavigation = array();
        foreach ($activeLanguages as $langId => $langData) {
            $targetPage = $node->getPage($langId);
            if (!$targetPage || !$targetPage->isActive()) {
                continue;
            }
            $name = $langData['name'];
            if ($langNameContraction) {
                $name = strtoupper($langData['lang']);
            }

            $class = $langData['lang'];
            if ($langId == FRONTEND_LANG_ID) {
                $class = $langData['lang'] . ' active';
            }

            $nodePlaceholder  = \Cx\Core\Routing\NodePlaceholder::fromPage(
                $targetPage,
                $params
            )->getPlaceholder(true);
            $langNavigation[] = \Html::getLink(
                $nodePlaceholder,
                contrexx_raw2xhtml($name),
                null,
                'class="' . $class . '" title="' . contrexx_raw2xhtml($name) . '" '
            );
        }

        return implode('', $langNavigation);
    }


    /**
     * Get language link by lang ID
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page   page object
     * @param integer                                   $langId language ID
     *
     * @return string
     */
    protected function getLanguageLinkById(
        \Cx\Core\ContentManager\Model\Entity\Page $page,
        $langId,
        $params
    ) {
        if (empty($langId)) {
            return;
        }

        $node       = $page->getNode();
        $targetPage = $node->getPage($langId);
        if (!$targetPage || !$targetPage->isActive()) {
            return \Cx\Core\Routing\Url::fromModuleAndCmd('Error', '', $langId);
        }
        return \Cx\Core\Routing\NodePlaceholder::fromPage(
            $targetPage,
            $params
        )->getPlaceholder(true);
    }

}

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
     * @param \Cx\Core\Html\Sigma Widget             $template Template
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object
     * @param array                                  $params   Get parameters
     */
    public function parseWidget($name, $template, $response, $params)
    {
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
                '/^LANG_SELECTED_([A-Z]{1,2}(?:-[A-Z]{2,4})?)$/',
                $name,
                $matches
            )
        ) {
            $selected = '';
            $langCode = $params['locale']->getShortForm();
            if ($matches[1] === strtoupper($langCode)) {
                $selected = 'selected';
            }
            $template->setVariable($name, $selected);
            return;
        }

        $page = $params['page'];
        if (!$page) {
            return;
        }

        $navbar = new \Navigation($page->getId(), $page);
        if ($name === 'LANGUAGE_NAVBAR') {
            $template->setVariable($name, $navbar->getFrontendLangNavigation($page));
            return;
        }

        if ($name === 'LANGUAGE_NAVBAR_SHORT') {
            $template->setVariable($name, $navbar->getFrontendLangNavigation($page, true));
            return;
        }

        $langMatches = null;
        if (
            preg_match(
                '/^LANG_CHANGE_([A-Z]{1,2}(?:-[A-Z]{2,4})?)$/',
                $name,
                $langMatches
            )
        ) {
            // make iso1 part of code lowercase (e.g DE-CH --> de-CH)
            $code = explode('-', $langMatches[1]);
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
                $navbar->getLanguageLinkById($page, $locale->getId())
            );
        }
    }
}

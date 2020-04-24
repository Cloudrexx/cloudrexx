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
 * Frontend controller to easily create a frontent view
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */

namespace Cx\Core\Core\Model\Entity;

/**
 * Frontend controller to easily create a frontent view
 *
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_core
 * @version     3.1.0
 */
abstract class SystemComponentFrontendController extends Controller {

    /**
     * This is called by the default ComponentController and does all the repeating work
     *
     * This creates a template of the page content and calls parsePage($template)
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved page
     */
    public function getPage(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_ARRAYLANG;

        // init component template
        $componentTemplate = new \Cx\Core\Html\Sigma('.', '', $page);
        $componentTemplate->setErrorHandling(PEAR_ERROR_DIE);
        $componentTemplate->setTemplate($page->getContent());

        // default css and js
        if (file_exists($this->cx->getClassLoader()->getFilePath($this->getDirectory(false) . '/View/Style/Frontend.css'))) {
            \JS::registerCSS(substr($this->getDirectory(false, true) . '/View/Style/Frontend.css', 1));
        }
        if (file_exists($this->cx->getClassLoader()->getFilePath($this->getDirectory(false) . '/View/Script/Frontend.js'))) {
            \JS::registerJS(substr($this->getDirectory(false, true) . '/View/Script/Frontend.js', 1));
        }

        // parse page

        $componentTemplate->setGlobalVariable($_ARRAYLANG);
        $this->parsePage($componentTemplate, $page->getCmd());
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($componentTemplate);
        $page->setContent($componentTemplate->get());
    }

    /**
     * Use this to parse your frontend page
     *
     * You will get a template based on the content of the resolved page
     * You can access Cx class using $this->cx
     * To show messages, use \Message class
     * @param \Cx\Core\Html\Sigma $template Template containing content of resolved page
     */
    public abstract function parsePage(\Cx\Core\Html\Sigma $template, $cmd);
}

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
 * A cloudrexx response to a request
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */

namespace Cx\Core\Routing\Model\Entity;

/**
 * A cloudrexx response to a request
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
class Response extends \Cx\Lib\Net\Model\Entity\Response {
    /**
     * @var \Cx\Core\ContentManager\Model\Entity\Page Response page
     */
    protected $page;

    /**
     * @param \Cx\Core\View\Model\Entity\Theme Current theme
     */
    protected $theme = null;

    /**
     * Sets the current page for this response
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page
     */
    public function setPage($page) {
        $this->page = $page;
    }

    /**
     * Returns the current page for this response
     * @return \Cx\Core\ContentManager\Model\Entity\Page Page
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * Sets the current theme
     * @param \Cx\Core\View\Model\Entity\Theme $theme Current theme
     */
    public function setTheme(\Cx\Core\View\Model\Entity\Theme $theme) {
        $this->theme = $theme;
    }

    /**
     * Returns the current theme
     * @return \Cx\Core\View\Model\Entity\Theme Current theme
     */
    public function getTheme() {
        if ($this->theme) {
            return $this->theme;
        }
        $themesRepository = new \Cx\Core\View\Model\Repository\ThemeRepository();
        $init = \Env::get('init');
        if (!$init) {
            return null;
        }
        $this->theme = $themesRepository->findById(
            $init->getCurrentThemeId()
        );
        return $this->theme;
    }
}

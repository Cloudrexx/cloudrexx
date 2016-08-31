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
 * Main controller for Stats
 *
 * @copyright   cloudrexx
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodule_stats
 */

namespace Cx\Core_Modules\Stats\Controller;

/**
 * Main controller for Stats
 *
 * @copyright   cloudrexx
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodule_stats
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * Instance of StatsLibrary
     *
     * @var StatsLibrary
     */
    protected $counter;

     /**
     * getControllerClasses
     *
     * @return type
     */
    public function getControllerClasses() {
        return array();
    }

     /**
     * Load the component Stats.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $objTemplate, $_CORELANG;

        \Permission::checkAccess(163, 'static');
        $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
        $objTemplate = $this->cx->getTemplate();

        $subMenuTitle = $_CORELANG['TXT_STATISTIC'];
        $statistic= new \Cx\Core_Modules\Stats\Controller\Stats();
        $statistic->getContent();
    }

     /**
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        // Initialize counter and track search engine robot
        $this->getCounterInstance()->checkForSpider();
    }

    /**
     * Get the Counter instance, if instance already created use the existing one
     *
     * @return \Cx\Core_Modules\Stats\Controller\StatsLibrary
     */
    public function getCounterInstance()
    {

        if (!$this->counter) {
            $this->counter = new \Cx\Core_Modules\Stats\Controller\StatsLibrary();
        }

        return $this->counter;
    }

}

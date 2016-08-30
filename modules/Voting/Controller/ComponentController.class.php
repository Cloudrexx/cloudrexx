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
 * Main controller for Voting
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_voting
 */

namespace Cx\Modules\Voting\Controller;

/**
 * Main controller for Voting
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_voting
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * getControllerClasses
     *
     * @return type
     */
    public function getControllerClasses() {
        return array();
    }

    /**
     * Load the component Voting.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $objTemplate, $_CORELANG;

        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                \Env::get('cx')->getPage()->setContent(votingShowCurrent(\Env::get('cx')->getPage()->getContent()));
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(14, 'static');
                $subMenuTitle = $_CORELANG['TXT_CONTENT_MANAGER'];
                $objvoting = new VotingManager();
                $objvoting->getVotingPage();
                break;

            default:
                break;
        }
    }

    /**
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $cl, $_ARRAYLANG, $objInit, $themesPages, $arrMatches, $page_template;
        // get voting
        /** @ignore */
        if ($cl->loadFile(ASCMS_MODULE_PATH.'/Voting/Controller/Voting.class.php')) {
            $_ARRAYLANG = array_merge($_ARRAYLANG, $objInit->loadLanguageData('Voting'));
            //  if ($objTemplate->blockExists('voting_result')) {
            //      $objTemplate->_blocks['voting_result'] = setVotingResult($objTemplate->_blocks['voting_result']);
            //  }
            if (preg_match('@<!--\s+BEGIN\s+(voting_result)\s+-->(.*)<!--\s+END\s+\1\s+-->@m', $themesPages['sidebar'], $arrMatches)) {
                $themesPages['sidebar'] = preg_replace('@(<!--\s+BEGIN\s+(voting_result)\s+-->.*<!--\s+END\s+\2\s+-->)@m', setVotingResult($arrMatches[2]), $themesPages['sidebar']);
            }
            if (preg_match('@<!--\s+BEGIN\s+(voting_result)\s+-->(.*)<!--\s+END\s+\1\s+-->@m', $themesPages['index'], $arrMatches)) {
                $themesPages['index'] = preg_replace('@(<!--\s+BEGIN\s+(voting_result)\s+-->.*<!--\s+END\s+\2\s+-->)@m', setVotingResult($arrMatches[2]), $themesPages['index']);
            }
            if (preg_match('@<!--\s+BEGIN\s+(voting_result)\s+-->(.*)<!--\s+END\s+\1\s+-->@m', \Env::get('cx')->getPage()->getContent(), $arrMatches)) {
                \Env::get('cx')->getPage()->setContent(preg_replace('@(<!--\s+BEGIN\s+(voting_result)\s+-->.*<!--\s+END\s+\2\s+-->)@m', setVotingResult($arrMatches[2]), \Env::get('cx')->getPage()->getContent()));
            }
            if (preg_match('@<!--\s+BEGIN\s+(voting_result)\s+-->(.*)<!--\s+END\s+\1\s+-->@m', $page_template, $arrMatches)) {
                $page_template = preg_replace('@(<!--\s+BEGIN\s+(voting_result)\s+-->.*<!--\s+END\s+\2\s+-->)@m', setVotingResult($arrMatches[2]), $page_template);
            }
        }
    }

}

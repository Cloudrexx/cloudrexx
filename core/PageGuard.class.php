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
 * PageGuard
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */

/**
 * PageGuardException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */
class PageGuardException extends Exception {}

/**
 * Handles access restriction administration on Pages.
 * (Retrieve / Store)
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */
class PageGuard {
    protected $db = null;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Returns the group ids with access to front- or backend of a page
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page to get the group ids of
     * @param boolean $frontend True for frontend access groups, false for backend
     * @return mixed Array of group ids or false on error
     * @throws PageGuardException
     */
    public function getAssignedGroupIds($page, $frontend) {
        if ($frontend && !$page->isFrontendProtected()) {
            return array();
        }
        if (!$frontend && !$page->isBackendProtected()) {
            return array();
        }

    try {
        $accessId = $this->getAccessId($page, $frontend);
    }
    catch (PageGuardException $e) {
        // the selected page is listed as protected but does not have an access id.
        // this is probably due to a db inconsistency, which we should be able to handle gracefully:
        $accessId = \Permission::createNewDynamicAccessId();

        if ($frontend && $accessId) {
        $page->setFrontendAccessId($accessId);
        }
        elseif (!$frontend && $accessId) {
        $page->setBackendAccessId($accessId);
        }
        else {
        // cannot create a new dynamic access id.
        throw new PageGuardException('This protected page doesn\'t have an access id associated with
it. Cloudrexx encountered an error while generating a new access id.');
        }
        Env::get('em')->persist($page);
        Env::get('em')->flush();
    }

        return \Permission::getGroupIdsForAccessId($accessId);
    }

    public function setAssignedGroupIds($page, $ids, $frontend) {
        $accessId = $this->getAccessId($page, $frontend);
        \Permission::setAccess($accessId, 'dynamic', $ids);
    }

    protected function getAccessId($page, $frontend) {
        $accessId = $page->getFrontendAccessId();
        if(!$frontend)
            $accessId = $page->getBackendAccessId();
        if($accessId === 0)
            throw new PageGuardException('Tried to protect Page without accessid. Call setFrontendProtection() / setBackendProtection() first');
        return $accessId;
    }

    /**
     * Returns an array of all front- or backend groups
     * @param boolean $frontend True for frontend access groups, false for backend
     * @return mixed Array (id=>name) or false on error
     */
    public function getGroups($frontend) {
        return \Permission::getGroups($frontend);
    }
}

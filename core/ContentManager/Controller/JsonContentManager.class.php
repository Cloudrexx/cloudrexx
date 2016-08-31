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
 * JSON Adapter for ContentManager
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */

namespace Cx\Core\ContentManager\Controller;
use \Cx\Core\Json\JsonAdapter;

/**
 * JSON Adapter for ContentManager
 * @copyright   Cloudrexx AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */
class JsonContentManager implements JsonAdapter {

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return 'cm';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('saveToggleStatuses', 'getAccess', 'copy', 'link');
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return '';
    }

    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * Saves the toggle statuses in the session.
     * @param Array $params Client parameters
     * @author Yannic Tschanz <yannic.tschanz@comvation.com>
     */
    public function saveToggleStatuses($params) {
        $arrToggleStatuses = array();
        foreach ($params['post'] as $toggleKey => $toggleValue) {
            $arrToggleStatuses[contrexx_input2raw($toggleKey)] = contrexx_input2raw($toggleValue);
        }

        if (!isset($_SESSION['contentManager'])) {
            $_SESSION['contentManager'] = array();
        }
        $_SESSION['contentManager']['toggleStatuses'] = $arrToggleStatuses;
    }

    /**
     * Returns an array containing the permissions of the current user
     * The array has the following keys with boolean values:
     *  global      If this is false, cannot do anything in the content manager
     *  delete      If this is true, the user can delete pages and nodes
     *  create      If this is true, the user can create pages and nodes
     *  access      If this is true, the user can change access to pages
     *  publish     If this is true, the user can publish or decline drafts
     * @todo Move this method to the ContentManager class and use it everywhere
     * @return array Array containing the permissions of the current user
     */
    public function getAccess() {
        $global =   \Permission::checkAccess(6, 'static', true) &&
                    \Permission::checkAccess(35, 'static', true);
        return array(
            'global'    => $global,
            'delete'    => $global && \Permission::checkAccess(26, 'static', true),
            'create'    => $global && \Permission::checkAccess(5, 'static', true),
            'access'    => $global && \Permission::checkAccess(36, 'static', true),
            'publish'   => $global && \Permission::checkAccess(78, 'static', true),
        );
    }

    /**
     * Copies the complete tree from the default language to another and copies the pages content
     * @param array $params Array containing target language (array("get"=>array("to"=>{target}))
     */
    public function copy($params) {
        return $this->performLanguageAction('copy', $params);
    }

    /**
     * Copies the complete tree from the default language to another and sets content type to fallback
     * @param array $params Array containing target language (array("get"=>array("to"=>{target}))
     */
    public function link($params) {
        return $this->performLanguageAction('link', $params);
    }

    private function performLanguageAction($action, $params) {
        global $_CORELANG;

        // Global access check
        if (!\Permission::checkAccess(6, 'static', true) ||
                !\Permission::checkAccess(35, 'static', true)) {
            throw new \Cx\Core\ContentManager\ContentManagerException($_CORELANG['TXT_CORE_CM_USAGE_DENIED']);
        }
        if (!\Permission::checkAccess(53, 'static', true)) {
            throw new \Cx\Core\ContentManager\ContentManagerException($_CORELANG['TXT_CORE_CM_COPY_DENIED']);
        }
        if (!isset($params['get']) || !isset($params['get']['to'])) {
            throw new \Cx\Core\ContentManager\ContentManagerException('Illegal parameter list');
        }
        $em = \Env::get('em');
        $nodeRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        $targetLang = contrexx_input2raw($params['get']['to']);
        $fromLang = \FWLanguage::getFallbackLanguageIdById($targetLang);
        if ($fromLang === false) {
            throw new \Cx\Core\ContentManager\ContentManagerException('Language has no fallback to copy/link from');
        }
        $toLangCode = \FWLanguage::getLanguageCodeById($targetLang);
        if ($toLangCode === false) {
            throw new \Cx\Core\ContentManager\ContentManagerException('Could not get id for language #"' . $targetLang .'"');
        }
        $limit = 0;
        $offset = 0;
        if (isset($params['get']['limit'])) {
            $limit = contrexx_input2raw($params['get']['limit']);
        }
        if (isset($params['get']['offset'])) {
            $offset = contrexx_input2raw($params['get']['offset']);
        }
        $result = $nodeRepo->translateRecursive($nodeRepo->getRoot(), $fromLang, $targetLang, $action == 'copy', $limit, $offset);
        return $result;
    }
}

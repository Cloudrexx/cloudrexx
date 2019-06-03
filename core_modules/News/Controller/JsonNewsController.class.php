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
 * JsonNewsController
 * Json controller for news module
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
namespace Cx\Core_Modules\News\Controller;

/**
 * JsonNewsController
 * Json controller for news module
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class JsonNewsController extends \Cx\Core\Core\Model\Entity\Controller implements \Cx\Core\Json\JsonAdapter {
    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return parent::getName();
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array(
            'getNews',
        );
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
        return new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array(), false);
    }

    /**
     * get all news list
     *
     * @return json result
     */
    public function getNews($data = array())
    {
        if (empty($data['get']['term'])) {
            return array();
        }

        $searchTerm = contrexx_input2raw($data['get']['term']);

        $excludeId = '';
        if (!empty($data['get']['id'])) {
            $excludeId = 'AND n.id != ' . contrexx_input2int($data['get']['id']);
        }

        // filter by access level
        $newsLibrary = new NewsLibrary();
        $protection = '';
        if (
            $newsLibrary->arrSettings['news_message_protection'] == '1' &&
            !\Permission::hasAllAccess()
        ) {
            $objFWUser = \FWUser::getFWUserObject();
            if (
                $objFWUser &&
                $objFWUser->objUser->login()
            ) {
                $protection = 'AND (frontend_access_id IN ('.
                    implode(',', array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).
                    ') OR userid='.$objFWUser->objUser->getId().')';
            } else {
                $protection = 'AND frontend_access_id=0';
            }
        }

        // fetch news
        $query = '  SELECT      n.id,
                                nl.title
                    FROM        '.DBPREFIX.'module_news AS n
                    INNER JOIN  '.DBPREFIX.'module_news_locale AS nl ON nl.news_id = n.id
                    WHERE       
                                n.status=1
                                ' . $excludeId . '
                                ' . $protection . '
                            AND (
                                nl.title        LIKE "%' .  contrexx_raw2db($searchTerm) . '%" OR
                                nl.teaser_text  LIKE "%' .  contrexx_raw2db($searchTerm) . '%"
                            )
                    ORDER BY nl.`title`';

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $result = $cx->getDb()->getAdoDb()->query(
            $query
        );

        if (
            $result === false ||
            $result->EOF
        ) {
            return array();
        }

        $news = array();
        while (!$result->EOF) {
            $news[$result->fields['id']] = $result->fields['title'];
            $result->MoveNext();
        }

        return $news;
    }
}

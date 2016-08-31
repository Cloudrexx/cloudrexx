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
 * JSON Adapter for Cx\Core_Modules\LinkManager\Model\Entity\Link
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Controller;
use \Cx\Core\Json\JsonAdapter;

/**
 * JSON Adapter for Cx\Core_Modules\LinkManager\Model\Entity\Link
 * the class JsonLink handles, the link status whether the link is resolved or not.
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class JsonLink implements JsonAdapter {
    /**
     * Reference to the Doctine EntityManager
     * @var \Doctrine\ORM\EntityManager
     */
    private $em = null;

    /**
     * Reference to the Doctrine NodeRepo
     * @var \Cx\Core_Modules\LinkManager\Model\Repository\LinkRepository
     */
    private $linkRepo = null;

    /**
     * List of messages
     * @var Array
     */
    private $messages = array();


    /**
     * Constructor
     * the class JsonLink handles, the link status whether the link is resolved or not.
     */
    public function __construct() {
        $this->em = \Env::get('em');
        if ($this->em) {
            $this->linkRepo = $this->em->getRepository('\Cx\Core_Modules\LinkManager\Model\Entity\Link');
        }
    }

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return 'link';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('modifyLinkStatus');
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return implode('<br />', $this->messages);
    }

    /**
     * Returns default permission as object
     * Has no restrictions
     * @return null
     */
    public function getDefaultPermissions(){
        return null;
    }

    /**
     * Edit the link status(link resolved or not)
     *
     * @return array
     */
    public function modifyLinkStatus()
    {
        //get post values
        $id             = isset($_GET['id']) ? contrexx_input2raw($_GET['id']) : 0;
        $solvedLinkStat = isset($_GET['status']) ? $_GET['status'] : 0;

        $result  = array();
        $objUser = new \Cx\Core_Modules\LinkManager\Controller\User();
        if ($objUser) {
            $user = $objUser->getUpdatedUserName(0, 1);
        }

        if (!empty($id)) {
            $linkStatus = ($solvedLinkStat == 0) ? 1 : 0;
            $userId     = $linkStatus ? $user['id'] : 0;
            $brokenLink = $this->linkRepo->findOneBy(array('id' => $id));

            $brokenLink->setLinkStatus($linkStatus);
            $brokenLink->setUpdatedBy($userId);

            $this->em->persist($brokenLink);
            $this->em->flush();

            $result['linkStatus'] = $linkStatus;
            $result['userName']   = $user['name'];
        }
        return $result;
    }

}

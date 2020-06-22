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
 * JSON Adapter for Jobs module
 * @copyright   Cloudrexx AG
 * @author      ss4u <ss4ugroup@gmail.com>
 * @package     cloudrexx
 * @subpackage  module_jobs
 */

namespace Cx\Modules\Jobs\Controller;
use \Cx\Core\Json\JsonAdapter;

/**
 * JSON Adapter for Jobs module
 * @copyright   Cloudrexx AG
 * @author      ss4u <ss4ugroup@gmail.com>
 * @package     cloudrexx
 * @subpackage  module_jobs
 */
class JsonJobsController extends \Cx\Core\Core\Model\Entity\Controller 
                         implements JsonAdapter {
    /**
     * List of messages
     * @var Array 
     */
    protected $messages = array();

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName() {
        return 'Jobs';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array('updateJobsHotOffer' => new \Cx\Core_Modules\Access\Model\Entity\Permission(array('http', 'https'), array('post'), true));
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
     * @return Object
     */
    public function getDefaultPermissions() {
        return null;
    }

    /**
     * Update the job's hot offer status
     * 
     * @return array status of job update
     */
    public function updateJobsHotOffer($params)
    {
        global $_ARRAYLANG;

        $objDatabase = $this->cx->getDb()->getAdoDb();
        $langData    = \Env::get('init')->loadLanguageData($this->getName());
        $_ARRAYLANG  = array_merge($_ARRAYLANG, $langData);

        //get the input values
        $id       = isset($params['post']['id']) ? contrexx_input2int($params['post']['id']) : 0;
        $hotOffer = isset($params['post']['hotOffer']) ? contrexx_input2int($params['post']['hotOffer']) : 0;

        if (empty($id)) {
            throw new \Exception($_ARRAYLANG['TXT_JOBS_RECORD_UPDATE_FAILED']);
        }

        $query = 'UPDATE `' . DBPREFIX . 'module_jobs` 
                    SET `hot` = ' . $hotOffer . ' WHERE `id` = ' . $id;
        if (!$objDatabase->Execute($query)) {
            throw new \Exception($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
        }

        JobsLibrary::clearCache();

        return array('status' => 'success');
    }
}

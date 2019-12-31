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
* Main controller for LinkManager
*
* @copyright   Cloudrexx AG
* @author      Project Team SS4U <info@cloudrexx.com>
* @package     cloudrexx
* @subpackage  coremodule_linkmanager
*/

namespace Cx\Core_Modules\LinkManager\Controller;

/**
* Main controller for LinkManager
*
* @copyright   Cloudrexx AG
* @author      Project Team SS4U <info@cloudrexx.com>
* @package     cloudrexx
* @subpackage  coremodule_linkmanager
*/
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    /**
     * Get the controller classes
     *
     * @return array array of the controller classes
     */
    public function getControllerClasses() {
        return array('Backend', 'CrawlerResult', 'Default', 'Settings', 'LinkCrawler', 'Url');
    }

    /**
     * Returns a list of JsonAdapter class names
     *
     * The array values might be a class name without namespace. In that case
     * the namespace \Cx\{component_type}\{component_name}\Controller is used.
     * If the array value starts with a backslash, no namespace is added.
     *
     * Avoid calculation of anything, just return an array!
     * @return array List of ComponentController classes
     */
    public function getControllersAccessableByJson(){
        return array('JsonLink');
    }

    /**
     * Get the response status of the given URL
     *
     * @param string $url requested page url
     */
    public function getUrlStatus($url)
    {
        //Fetch the requested url status
        if (preg_match('#^[mailto:|javascript:]# i', $url)) {
            $status = 200;
        } else {
            $response = $this->getUrlResponse($url);
            $status   = ($response instanceof \HTTP_Request2_Response)
                          ? $response->getStatus()
                          : 0;
        }
        return $status;
    }

    /**
     * Get the response for the given URL
     *
     * @staticvar \HTTP_Request2 $request Request instance
     *
     * @param string $url requested page url
     *
     * @return mixed \HTTP_Request2_Response | false
     */
    public function getUrlResponse($url)
    {
        //If the argument url is empty then return
        if (empty($url)) {
            return false;
        }

        try {
            $request = new \HTTP_Request2();
            $request->setUrl($url);
            // ignore ssl issues
            // otherwise, cloudrexx does not activate 'https'
            // when the server doesn't have an ssl certificate installed
            $request->setConfig(array(
                'ssl_verify_peer'  => false,
                'ssl_verify_host'  => false,
                'follow_redirects' => true,
            ));

            return $request->send();
        } catch (\Exception $e) {
            \DBG::log('An url ' . $url . ' is Failed to load, due to: ' . $e->getMessage());
        }
        return false;
    }
}

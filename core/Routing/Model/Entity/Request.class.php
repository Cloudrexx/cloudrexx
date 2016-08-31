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
 * Handling Request
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */

namespace Cx\Core\Routing\Model\Entity;

/**
 * Handling Request
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */

class Request {

    /**
     * HTTP requested method
     *
     * @var String
     */
    protected $httpRequestMethod;

    /**
     * Resolved url object
     *
     * @var object
     */
    protected $url;

    /**
     * Constructor to initialize the $httpRequestMethod and $url
     *
     * @param String $method
     * @param Object $resolvedUrl
     */
    public function __construct($method, \Cx\Core\Routing\Url $resolvedUrl) {
        $this->httpRequestMethod = strtolower($method);
        $this->url               = $resolvedUrl;
    }

    /**
     * Get the httpRequest method
     *
     * @return String
     */
    public function getHttpRequestMethod() {
        return $this->httpRequestMethod;
    }

    /**
     * Get the resolved url object
     *
     * @return Object
     */
    public function getUrl() {
        return $this->url;
    }
}

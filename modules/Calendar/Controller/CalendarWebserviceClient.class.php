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
 * Calendar
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
namespace Cx\Modules\Calendar\Controller;

/**
 * Calendar Class WebserviceClient
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarWebserviceClient
{
    /**
     * SOAP Client object
     *
     * @access private
     * @var object
     */
    private $SOAPClient;

    /**
     * CalendarWebserviceClient Constructor
     *
     * @param string $location the URL of the SOAP server to send the request
     * @param string $uri      the target namespace of the SOAP service
     */
    public function __construct($location, $uri) {
        $options = array(
            'location' => $location,
            'uri'      => $uri
        );

        $this->SOAPClient = new \SoapClient(null, $options);
    }

    /**
     * verify the host name, if its exists returns the host data
     *
     * @param string $myHost     host name
     * @param string $foreignKey reference Key
     *
     * @return mixed host details on success, false otherwise
     */
    public function verifyHost($myHost,$foreignKey) {
        return $this->SOAPClient->verifyHost($myHost,$foreignKey);
    }

    /**
     * Get the event list
     *
     * @param integer $start_date                     Start date
     * @param integer $end_date                       End date
     * @param boolean $auth                           Authorization
     * @param string  $term                           search term
     * @param integer $langId                         Language id
     * @param integer $foreignHostId                  Foreign Host id
     * @param integer $myHostId                       Host id
     * @param boolean $showEventsOnlyInActiveLanguage get event only active
     *                                                frontend language
     *
     * @return array Event list object
     */
    function getEventList($start_date, $end_date, $auth, $term, $langId, $foreignHostId, $myHostId, $showEventsOnlyInActiveLanguage) {
       return $this->SOAPClient->getEventList($start_date, $end_date, $auth, $term, $langId, $foreignHostId, $myHostId, $showEventsOnlyInActiveLanguage);
    }
}

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
 * Listing handler
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */

namespace Cx\Core_Modules\Listing\Controller;

/**
 * This class defines a handler for Listings
 * (for example the PagingController)
 * @author ritt0r
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */
abstract class ListingHandler {

    /**
     * Constructor for the Handler. Do not
     * do anything Listing specific (e.g.
     * don't initialize page number here)
     */
    public abstract function __construct();

    /**
     * Handles the listing by changing parameters like limit and offset
     *
     * Both the params argument and the return value look like this:
     * array(
     *     'offset'     => {integer},   // start offset to use
     *     'count'      => {integer},   // number of entries to show
     *     'criteria'   => {array},     // criteria (similiar to SQLs WHERE)
     *     'order'      => {array},     // order to sort by
     * )
     * @param array $params Parameters
     * @param array $config Configuration
     * @return array The handled parameters
     */
    public abstract function handle($params, $config);
}

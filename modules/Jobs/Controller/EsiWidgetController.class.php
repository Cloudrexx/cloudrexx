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
 * JsonAdapter Controller to handle EsiWidgets
 *
 * @author Thomas Wirz <thomas.wirz@cloudrexx.com>
 * @package cloudrexx
 * @subpackage modules_jobs
 */

namespace Cx\Modules\Jobs\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 * @author Thomas Wirz <thomas.wirz@cloudrexx.com>
 * @package cloudrexx
 * @subpackage modules_jobs
 */
class EsiWidgetController extends \Cx\Core_Modules\Widget\Controller\EsiWidgetController {
    /**
     * Parses a widget
     *
     * @param string                                 $name     Widget name
     * @param \Cx\Core\Html\Sigma                    $template WidgetTemplate
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object
     * @param array                                  $params   Get parameters
     */
    public function parseWidget($name, $template, $response, $params)
    {
        global $_ARRAYLANG;

        $_ARRAYLANG = \Env::get('init')->getComponentSpecificLanguageData(
            'Jobs',
            true,
            $params['locale']->getId()
        );

        //Parse the Hot / Latest jobs
        if ($name == 'jobs_list') {
            $jobLib = new JobsLibrary();
            $jobLib->parseHotOrLatestJobs($template, $params['locale']);

            $dateTime = new \DateTime('tomorrow');
            $response->setExpirationDate($dateTime);
            return;
        }
    }
}

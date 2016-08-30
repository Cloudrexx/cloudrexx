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
 * SettingsController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */

namespace Cx\Core_Modules\Cron\Controller;

/**
 *
 * SettingsController for listing The last execution of the cron job
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */
class SettingsController extends \Cx\Core\Core\Model\Entity\Controller {
    /**
     * Em instance
     * @var \Doctrine\ORM\EntityManager em
     */
    protected $em;

    /**
     * Sigma template instance
     * @var Cx\Core\Html\Sigma  $template
     */
    protected $template;

    /**
     * Controller for the Backend Cron jobs  views
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     * @param string                                               $submenu                   the submenu name
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);

        $this->em                = $this->cx->getDb()->getEntityManager();
    }
    /**
     * Use this to parse your backend page
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showSettings();
    }

    /**
     * Display the  time of the newest SysLog entry from Cron
     *
     * @global type $_ARRAYLANG
     */
    public function showSettings() {
        global $_ARRAYLANG;

        $logRepo = $this->em->getRepository('Cx\Core_Modules\SysLog\Model\Entity\Log');

        $nameSpace   = explode('\\', $this->getNamespace());
        array_shift($nameSpace);
        $logger                  = implode('/', $nameSpace);
        $cronSysLogs             = $logRepo->findLatestLogEntryByLogger($logger);
        $lastSysLogExecutionTime = $_ARRAYLANG['TXT_CORE_MODULE_CRON_NEVER'];

        if (!empty($cronSysLogs)) {
            $lastSysLogEntry         = current($cronSysLogs);
            $lastSysLogExecutionTime = $lastSysLogEntry->getTimestamp()->format(ASCMS_DATE_FORMAT_DATETIME);
        } else {
            \Message::warning($_ARRAYLANG['TXT_CORE_MODULE_CRON_ERROR_MSG']);
        }

        $this->template->setVariable(array(
            'CRON_LAST_EXECUTION'      => $_ARRAYLANG['TXT_CORE_MODULE_CRON_LAST_EXECUTION'],
            'CRON_LAST_EXECUTION_TIME' => $lastSysLogExecutionTime,
        'CRON_SETTINGS'            => $_ARRAYLANG['TXT_CORE_MODULE_CRON_ACT_SETTINGS']
        ));
    }
}

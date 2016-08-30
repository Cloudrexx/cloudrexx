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
 * DefaultController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */

namespace Cx\Core_Modules\Cron\Controller;

/**
 *
 * DefaultController for Scheduled Tasks to list entities of Job using ViewGenerator
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_cron
 */
class DefaultController extends \Cx\Core\Core\Model\Entity\Controller {

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
     * JobRepository instance
     * @var \Cx\Core_Modules\Cron\Model\Repository\JobRepository $jobRepository
     */
    protected $jobRepository;

    /**
     * module name
     * @var string $moduleName
     */
    public $moduleName = 'Cron';

    /**
     * module name for language placeholder
     * @var string $moduleNameLang
     */
    public $moduleNameLang = 'CRON';

    /**
     * Controller for the Backend Cron jobs  views
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);

        $this->em                = $this->cx->getDb()->getEntityManager();
        $this->jobRepository     = $this->em->getRepository('Cx\Core_Modules\Cron\Model\Entity\Job');
    }

    /**
     * Use this to parse your backend page
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showCronJobs();
    }
    /**
     * Displaying entities of job using ViewGenerator.
     *
     * @global type $_ARRAYLANG
     */
    public function showCronJobs()
    {
        global $_ARRAYLANG;

        $cronJob = $this->jobRepository->findAll();
        if (empty($cronJob)) {
            $cronJob = new \Cx\Core_Modules\Cron\Model\Entity\Job();
        }

        $options = $this->getController('Backend')->getAllViewGeneratorOptions();
        $view = new \Cx\Core\Html\Controller\ViewGenerator($cronJob, $options);
        $this->template->setVariable('CRON_CONTENT', $view->render());
    }
}

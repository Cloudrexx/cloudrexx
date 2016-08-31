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
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_pim
 */

namespace Cx\Modules\Pim\Controller;

/**
 *
 * DefaultController for displaying all the orders.
 *
 * @copyright   Cloudrexx AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_pim
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
     * ProductRepository instance
     * @var \Cx\Modules\Product\Model\Repository\ProductRepository $productRepository
     */
    protected $productRepository;

    /**
     * module name
     * @var string $moduleName
     */
    public $moduleName = 'Pim';

    /**
     * module name for language placeholder
     * @var string $moduleNameLang
     */
    public $moduleNameLang = 'PIM';

    /**
     * Controller for the Backend Orders views
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);

        $this->em                  = $this->cx->getDb()->getEntityManager();
        $this->productRepository   = $this->em->getRepository('Cx\Modules\Pim\Model\Entity\Product');
    }

    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showProducts();
    }

    public function showProducts()
    {
        // Create view for product. This must be done in component, because ViewGenerator don't support views in first
        // tab. This can be delete as soon as the ViewGenerator can handle the first tab.
        $view = new \Cx\Core\Html\Controller\ViewGenerator(
            '\Cx\Modules\Pim\Model\Entity\Product',
            $this->getController('Backend')->getAllViewGeneratorOptions()
        );
        $this->template->setVariable('PRODUCTS_CONTENT', $view->render());
    }
}

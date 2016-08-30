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
 * Create error pages
 *
 * @copyright    CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @package        cloudrexx
 * @subpackage  core_error
 * @version        1.0.0
 */

namespace Cx\Core\Error\Model\Entity;

/**
 * Display a exception as a human readable error message
 *
 * @copyright    CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @package        cloudrexx
 * @subpackage  core_error
 * @version        1.0.0
 */
class ShinyException extends \Exception
{
    protected $templateFile = null;
    protected $templatePlaceholders = array();

    public function __construct($message = null, $code = 0, \Exception $previous = null) {
        $this->templateFile = \Env::get('cx')->getCodeBaseCorePath() . '/Error/View/Template/Backend/ShinyException.html';
        parent::__construct($message, $code, $previous);

        $this->templatePlaceholders = array(
            'ERROR_EXCEPTION_MESSAGE'           => $this->getMessage(),
            'ERROR_EXCEPTION_CODE'              => $this->getCode(),
            'ERROR_EXCEPTION_FILE'              => $this->getFile(),
            'ERROR_EXCEPTION_LINE'              => $this->getLine(),
            'ERROR_EXCEPTION_TRACE'             => $this->getTraceAsString(),
            'ERROR_EXCEPTION_PREVIOUS'          => $this->getPrevious(),
        );
    }

    public function setTemplateFile($templateFile) {
        $this->templateFile = $templateFile;
    }

    public function registerPlaceholders($placeholders) {
        $this->templatePlaceholders = array_merge($this->templatePlaceholders, $placeholders);
    }

    public function getBackendViewMessage() {
        $template = new \Cx\Core\Html\Sigma();
        $template->setErrorHandling(PEAR_ERROR_DIE);
        $template->loadTemplateFile($this->templateFile);
        $template->setVariable($this->templatePlaceholders);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($template);
        return $template->get();
    }
}

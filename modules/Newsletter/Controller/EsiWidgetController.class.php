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
 * Class EsiWidgetController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Mirjam Doyon <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  modules_newsletter
 */
namespace Cx\Modules\Newsletter\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Mirjam Doyon <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  modules_newsletter
 */
class EsiWidgetController extends \Cx\Core_Modules\Widget\Controller\EsiWidgetController
{

    /**
     * @inheritdoc
     */
    public function parseWidget($name, $template, $response, $params)
    {
        switch ($name) {
            case 'newsletter_form':
                $newsletter = new \Cx\Modules\Newsletter\Controller\Newsletter('');
                $newsletter->newsletterSignUp($template);
                
                $template->replaceBlock(
                    'newsletter_form',
                    $newsletter->_objTpl->get(),
                    false,
                    true
                );
                break;

            case 'NEWSLETTER_BLOCK':
                $template->setVariable($name, \Cx\Modules\Newsletter\Controller\NewsletterLib::_getHTML());
                break;
                
        }
    }
}

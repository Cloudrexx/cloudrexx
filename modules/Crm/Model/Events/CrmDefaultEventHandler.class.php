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
 * CrmDefaultEventHandler Class CRM
 *
 * @category   CrmDefaultEventHandler
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Model\Events;

/**
 * CrmDefaultEventHandler Class CRM
 *
 * @category   CrmDefaultEventHandler
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */
class CrmDefaultEventHandler implements \Cx\Modules\Crm\Model\Events\CrmEventHandler
{
    /**
     * Default Information
     *
     * @access protected
     * @var array
     */
    protected $default_info = array(
        'section'   => 'Crm'
    );

    /**
     * Event handler
     *
     * @param Event $event calling event
     *
     * @return boolean
     */
    function handleEvent(\Cx\Modules\Crm\Model\Entity\CrmEvent $event)
    {
        $info          = $event->getInfo();
        $substitutions = isset($info['substitution']) ? $info['substitution'] : array();
        $lang_id       = isset($info['lang_id']) ? $info['lang_id'] : FRONTEND_LANG_ID;
        $arrMailTemplate = array_merge(
            $this->default_info,
            array(
                'key'          => $event->getName(),
                'lang_id'      => $lang_id,
                'substitution' => $substitutions,
        ));

        if (false === \Cx\Core\MailTemplate\Controller\MailTemplate::send($arrMailTemplate)) {
            $event->cancel();
            return false;
        };
        return true;
    }
}

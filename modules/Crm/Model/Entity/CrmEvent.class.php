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
 * CrmEvent Class CRM
 *
 * @category   CrmEvent
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Model\Entity;

/**
 * CrmEvent Class CRM
 *
 * @category   CrmEvent
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */
class CrmEvent
{
    /**
    * Event name
    *
    * @access protected
    * @var string
    */
    protected $name;

    /**
    * Event context
    *
    * @access protected
    * @var string
    */
    protected $context;

    /**
    * Event cancel status
    *
    * @access protected
    * @var boolean
    */
    protected $cancelled = false;

    /**
    * Event information
    *
    * @access protected
    * @var string
    */
    protected $info;

    /**
     * Constructor
     *
     * @param String $name    event name
     * @param String $context event context
     * @param String $info    event info
     *
     * @return null
     */
    function __construct($name, $context = null, $info = null)
    {
        $this->setName($name);
        $this->setContext($context);
        $this->setInfo($info);
    }

    /**
     * Set Context
     *
     * @param String $context event context
     *
     * @return null
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * Get Context
     *
     * @return String
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set information
     *
     * @param String $info event info
     *
     * @return null
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    /**
     * Get Information
     *
     * @return String
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Cancel status
     *
     * @return null
     */
    function cancel()
    {
        $this->cancelled = true;
    }

    /**
     * return cancel status
     *
     * @return Boolean
     */
    public function isCancelled()
    {
        return $this->cancelled;
    }

    /**
     * Set name
     *
     * @param String $name set event name
     *
     * @return null
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }
}

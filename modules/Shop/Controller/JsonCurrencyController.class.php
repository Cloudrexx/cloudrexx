<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * JsonController for Currency
 *
 * @copyright  Cloudrexx AG
 * @author     Sam Hawkes <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_shop
 * @version    5.0.0
 */
namespace Cx\Modules\Shop\Controller;

/**
 * JsonController for Currency
 *
 * @copyright  Cloudrexx AG
 * @author     Sam Hawkes <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_shop
 * @version    5.0.0
 */
class JsonCurrencyController
    extends \Cx\Core\Core\Model\Entity\Controller
    implements \Cx\Core\Json\JsonAdapter
{
    /**
     * @var array messages from this controller
     */
    protected $messages;

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'Currency';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'getCodeDropdown',
            'getDefaultButton'
        );
    }

    /**
     * Returns all messages as string
     *
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString()
    {
        return implode('<br />', $this->messages);
    }

    /**
     * Returns default permission as object
     *
     * @return \Cx\Core_Modules\Access\Model\Entity\Permission
     */
    public function getDefaultPermissions()
    {
        $permission = new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array('http', 'https'),
            array('get', 'post'),
            true,
            array()
        );

        return $permission;
    }

    /**
     * Get a dropdown with all available currencies
     *
     * @param array $params contains the parameters of the callback function
     * @return \Cx\Core\Html\Model\Entity\DataElement dropdown with currencies
     */
    public function getCodeDropdown($params)
    {
        $fieldname = !empty($params['name']) ? $params['name'] : '';

        $scope = 'currency';
        \ContrexxJavascript::getInstance()->setVariable(
            'CURRENCY_INCREMENT',
            \Cx\Modules\Shop\Controller\CurrencyController::get_known_currencies_increment_array(),
            $scope
        );
        $select = new \Cx\Core\Html\Model\Entity\DataElement(
            $fieldname, '', 'select',
            null,
            \Cx\Modules\Shop\Controller\CurrencyController::get_known_currencies_name_array()
        );
        $select->setAttribute(
            'onchange',
            'updateCurrencyCode(this)'
        );
        return $select;
    }

    /**
     * Get radio button to select the default currency
     *
     * @param array $params contains the parameters of the callback function
     * @return \Cx\Core\Html\Model\Entity\DataElement default currency button
     * @throws \Doctrine\ORM\ORMException handle if orm interaction fails
     */
    public function getDefaultButton($params)
    {
        $defaultEntity = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Currency'
        )->getDefaultCurrency();

        $rowData = !empty($params['rows']) ? $params['rows'] : array();
        $id = !empty($rowData['id']) ? $rowData['id'] : 0;

        $radioButton = new \Cx\Core\Html\Model\Entity\DataElement(
            'default-' . $id, $id, 'input'
        );
        $radioButton->setAttribute('type', 'radio');
        $radioButton->setAttribute(
            'onchange',
            'updateDefault(this); updateExchangeRates(this)'
        );
        if (!empty($defaultEntity) && $id == $defaultEntity->getId()) {
            $radioButton->setAttribute('checked', 'checked');
        }
        return $radioButton;
    }
}
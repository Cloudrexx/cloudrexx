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
 * JsonController for Discount Group
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Controller;

/**
 * JsonController for Discount Group
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class JsonDiscountGroupController
    extends \Cx\Core\Core\Model\Entity\Controller
    implements \Cx\Core\Json\JsonAdapter
{
    /**
     * @var array messages from this controller
     */
    protected $messages = array();

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'DiscountGroup';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'getRateInput',
            'getLinkHeader'
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
     * Change the name of the element to pass the data as an array and add a
     * percent character after the input field
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement input with percent
     */
    public function getRateInput($params)
    {
        $data = $params['data'];
        $name = $data->getIdentifier();
        $nameParts = explode('-', $name);
        $fieldName = $nameParts[0]; // e.x. customerGroup
        $customerGroupId = $nameParts[1];

        $articleGroupId = 0;
        if (!empty($params['rows']['articleGroupId'])) {
            $articleGroupId = $params['rows']['articleGroupId'];
        }

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $percent = new \Cx\Core\Html\Model\Entity\TextElement('%');
        $input = new \Cx\Core\Html\Model\Entity\DataElement(
            $fieldName . '[' . $customerGroupId . ']['.$articleGroupId . ']',
            $data->getData()
        );

        $wrapper->addChildren(array($input, $percent));

        return $wrapper;
    }

    /**
     * Wrap a link around the
     *
     * @param array $params contains the parameters of the callback function
     *
     * @return \Cx\Core\Html\Model\Entity\HtmlElement link element
     */
    public function getLinkHeader($params)
    {
        $rows = $params['rows'];

        if (empty($rows['articleGroupId'])) {
            return $params['data'];
        }

        $link = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'a'
        );

        $url = \Cx\Core\Routing\Url::fromBackend(
            'Shop', 'Product/ArticleGroup'
        );
        $editUrl = \Cx\Core\Html\Controller\ViewGenerator::getVgEditUrl(
            0, $rows['articleGroupId'], $url
        );
        $link->setAttribute('href', $editUrl);
        $link->addChild(
            new \Cx\Core\Html\Model\Entity\TextElement(
                $params['data']
            )
        );

        return $link;
    }
}
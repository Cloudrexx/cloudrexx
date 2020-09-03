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
 * JsonController for Pricelist
 *
 * @copyright  Cloudrexx AG
 * @author     Sam Hawkes <info@cloudrexx.com>
 * @package    cloudrexx
 * @subpackage module_shop
 * @version    5.0.0
 */
namespace Cx\Modules\Shop\Controller;


class JsonPriceListController
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
        return 'PriceList';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'getLineFieldFooter',
            'getLineFieldHeader',
            'getAllCategoriesCheckbox',
            'storeAllCategories',
            'getCategoryCheckboxesForPricelist',
            'getGeneratedPdfLink',
            'getLinkElement',
            'checkIfAllCategoriesAreSelected'
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
     * Two header elements are returned in a wrapper so that they can be
     * displayed in one line
     *
     * @param array $params contains the parameters of the callback function
     * @return \Cx\Core\Html\Model\Entity\HtmlElement line with two elements
     */
    public function getLineFieldHeader($params)
    {
        $name = !empty($params['name']) ? $params['name'] : '';
        $value = !empty($params['value']) ? $params['value'] : '';

        return $this->getLineField($name, $value, 'headerRight');
    }

    /**
     * Two footer elements are returned in a wrapper so that they can be
     * displayed in one line
     *
     * @param array $params contains the parameters of the callback function
     * @return \Cx\Core\Html\Model\Entity\HtmlElement line with two elements
     */
    public function getLineFieldFooter($params)
    {
        global $_ARRAYLANG;

        $name = !empty($params['name']) ? $params['name'] : '';
        $value = !empty($params['value']) ? $params['value'] : '';
        $placeholders = array(
            '[DATE]' => $_ARRAYLANG[
                'TXT_DATE'
            ],
            '[PAGENUMBER]' =>$_ARRAYLANG[
                'TXT_PAGENUMBER'
            ]
        );
        return $this->getLineField($name, $value, 'footerRight', $placeholders);
    }

    /**
     * Two elements are returned in a wrapper so that they can be displayed in
     * one line
     *
     * @param string $nameLeft     name of the left element
     * @param string $valueLeft    value of the left element
     * @param string $nameRight    name of the right element
     * @param array  $placeholders the placeholders are displayed below the
     *                             element as a hint.
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    protected function getLineField(
        $nameLeft, $valueLeft, $nameRight, $placeholders = array()
    ) {
        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $headerLeft = new \Cx\Core\Html\Model\Entity\HtmlElement('textarea');
        $headerRight = new \Cx\Core\Html\Model\Entity\HtmlElement('textarea');
        $leftText = new \Cx\Core\Html\Model\Entity\TextElement($valueLeft);
        $rightText = new \Cx\Core\Html\Model\Entity\TextElement('');

        $headerLeft->setAttributes(
            array(
                'name' => $nameLeft,
                'id' => $nameLeft,
                'rows' => 4
            )
        );
        $headerRight->setAttributes(
            array(
                'id' => $nameRight,
                'rows' => 4
            )
        );

        $headerLeft->addChild($leftText);
        $headerRight->addChild($rightText);
        $wrapper->addChild($headerLeft);
        $wrapper->addChild($headerRight);

        if (empty($placeholders)) return $wrapper;

        $wrapperPlaceholders = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'div'
        );
        foreach ($placeholders as $placeholder=>$lang) {
            $block = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
            $name = new \Cx\Core\Html\Model\Entity\TextElement(
                $placeholder
            );
            $tt = new \Cx\Core\Html\Model\Entity\HtmlElement('tt');
            $tt->addChild($name);
            $block->addChild($tt);
            $block->addChild(
                new \Cx\Core\Html\Model\Entity\TextElement(
                    ': ' . $lang
                )
            );
            $wrapperPlaceholders->addChild($block);
        }
        $wrapper->addChild($wrapperPlaceholders);
        return $wrapper;
    }

    /**
     * Get a checkbox to be able to select all categories
     *
     * @param array $params contains the parameters of the callback function
     * @return \Cx\Core\Html\Model\Entity\HtmlElement wrapper with checkbox
     */
    public function getAllCategoriesCheckbox($params)
    {
        global $_ARRAYLANG;

        $isActive = !empty($params['value']) ? (bool)$params['value'] : false;

        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

        $label = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $label->setAttributes(
            array(
                'class' => 'category',
                'for' => 'category-all'
            )
        );
        $text = new \Cx\Core\Html\Model\Entity\TextElement(
            $_ARRAYLANG['TXT_SHOP_ALL_CATEGORIES']
        );
        $checkbox = new \Cx\Core\Html\Model\Entity\DataElement(
            'category-all',
            1
        );
        $checkbox->setAttributes(
            array(
                'type' => 'checkbox',
                'id' => 'category-all',
                empty($isActive) ? '' : 'checked' => 'checked'
            )
        );

        $label->addChild($checkbox);
        $label->addChild($text);
        $wrapper->addChild($label);

        return $wrapper;
    }

    /**
     * Get all categories as checkbox to select them
     *
     * @param array $params contains the parameters of the callback function
     * @return \Cx\Core\Html\Model\Entity\HtmlElement list with checkboxes
     * @throws \Exception
     */
    public function getCategoryCheckboxesForPricelist($params)
    {
        $pricelistId = !empty($params['id']) ? $params['id'] : 0;

        $categories = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Category'
        )->findBy(array('active' => 1, 'parentId' => null));
        $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

        foreach ($categories as $category) {
            $wrapper->addChild(
                $this->getCategoryCheckbox(
                    $category, $pricelistId
                )
            );

            foreach ($category->getChildren() as $child) {
                $childWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement(
                    'span'
                );
                $childWrapper->addClass('child');

                $childCheckbox = $this->getCategoryCheckbox(
                    $child, $pricelistId
                );

                $childWrapper->addChild($childCheckbox);
                $wrapper->addChild($childWrapper);
            }
        }
        return $wrapper;
    }

    /**
     * Get a checkbox to select the category
     *
     * @param \Cx\Modules\Shop\Model\Entity\Category $category category to show
     * @param int $pricelistId id of list
     * @return \Cx\Core\Html\Model\Entity\HtmlElement category checkbox
     * @throws \Doctrine\ORM\ORMException handle if orm interaction fails
     */
    protected function getCategoryCheckbox($category, $pricelistId)
    {
        $repo = $this->cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Pricelist'
        );
        $label = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $label->setAttributes(
            array(
                'class' => 'category',
                'for' => 'category-'. $category->getId()
            )
        );
        $text = new \Cx\Core\Html\Model\Entity\TextElement(
            $category->getName()
        );
        $checkbox = new \Cx\Core\Html\Model\Entity\DataElement(
            'categories[' . $category->getId() . ']',
            $category->getId()

        );

        $isActive = (boolean)$repo->getPricelistByCategoryAndId(
            $category,
            $pricelistId
        );
        $checkbox->setAttributes(
            array(
                'type' => 'checkbox',
                'id' => 'category-' . $category->getId(),
                empty($isActive) ? '' : 'checked' => 'checked'
            )
        );

        $label->addChild($checkbox);
        $label->addChild($text);

        return $label;
    }

    /**
     * Get element to display a link
     *
     * @param array $params contains the parameters of the callback function
     * @return \Cx\Core\Html\Model\Entity\HtmlElement link element
     */
    public function getLinkElement($params)
    {
        $value = !empty($params['data']) ? $params['data'] : '';
        $value = !empty($params['value']) ? $params['value'] : $value;
        $link = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
        $text = new \Cx\Core\Html\Model\Entity\TextElement($value);
        $link->setAttributes(
            array(
                'href' => $value,
                'target' => '_blank'
            )
        );
        $link->addChild($text);

        return $link;
    }

    /**
     * Generate a link to access the PDF pricelist
     *
     * @param array $params contains the parameters of the callback function
     * @return string generated link to pdf
     */
    public function getGeneratedPdfLink($params)
    {
        $rowData = !empty($params['rowData']) ? $params['rowData'] : array();
        $langId = !empty($rowData['langId']) ? $rowData['langId'] : '';
        $id = !empty($rowData['id']) ? $rowData['id'] : 0;

        $url = $this->cx->getRequest()->getUrl();
        $protcol = $url->getProtocol();
        $domain = $url->getDomain();
        $pdfLinkUrl = \Cx\Core\Routing\Url::fromApi(
            'generatePdfPricelist', array()
        );

        $locale = \FWLanguage::getLanguageCodeById($langId);
        $pdfLinkUrl->setParam('id', $id);
        $pdfLinkUrl->setParam('locale', $locale);

        $link = $protcol . '://' . $domain . $pdfLinkUrl;

        return $link;
    }

    /**
     * Check if the param category_all exists. The callback is necessary
     * because unselected checkboxes are not sent via the form.
     *
     * @return bool if checkbox category_all is selected
     */
    public function checkIfAllCategoriesAreSelected()
    {
        return $this->cx->getRequest()->hasParam(
            'category-all',
            false
        );
    }
}
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
 * With the MultiSelectElement element you get two multiple-select elements. This makes
 * it easier to select entries and post multiple ones.
 *
 * @copyright  Cloudrexx AG
 * @author     Sam Hawkes <sam.hawkes@comvation.com>
 * @package    cloudrexx
 * @subpackage core_html
 * @since      v5.0.3
 */
namespace Cx\Core\Html\Model\Entity;

/**
 * With the MultiSelectElement element you get two multiple-select elements. This makes
 * it easier to select entries and post multiple ones.
 *
 * @copyright  Cloudrexx AG
 * @author     Sam Hawkes <sam.hawkes@comvation.com>
 * @package    cloudrexx
 * @subpackage core_html
 * @since      v5.0.3
 */
class MultiSelectElement extends \Cx\Core\Html\Model\Entity\DataElement
{
    /**
     * Name of the wrapper to identify the MultiSelectElement
     *
     * @var string
     */
    protected $wrapperName;

    /**
     * Name of the associated form
     *
     * @var string
     */
    protected $form;

    /**
     * Name of the associated select
     *
     * @var string
     */
    protected $associatedName;

    /**
     * Name of the not associated select
     *
     * @var string
     */
    protected $notAssociatedName;

    /**
     * Delimiter with which parents and child elements are separated
     *
     * @var string
     */
    protected $delimiter = '\\';

    /**
     * MultiSelectElement constructor
     *
     * The notAssociatedValues do not necessarily have to contain only the
     * not assigned values. All possible entries can also be passed. The arrays
     * notAssociatedValues and associatedValues are compared and values with the
     * same key are removed from the array notAssociatedValues.
     *
     * @param string $wrapperName         name of the wrapper to identify the
     *                                    MultiSelectElement
     * @param string $associatedName      name for the associated select
     * @param string $associatedTitle     to describe the select element
     * @param array  $associatedValues    associated values
     * @param string $notAssociatedName   name for the not associated select
     * @param string $notAssociatedTitle     to describe the select element
     * @param array  $notAssociatedValues values that are not associated or all
     *                                    possible values
     * @param string $form                name of the associated form
     * @param \Cx\Core\Validate\Model\Entity\Validator $validator to validate
     */
    public function __construct(
        $wrapperName,
        $associatedName,
        $associatedTitle,
        $associatedValues,
        $notAssociatedName,
        $notAssociatedTitle,
        $notAssociatedValues,
        $form,
        $validator = null
    ) {
        //get the language interface text
        $frontend = $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND;
        $langData = \Env::get('init')->getComponentSpecificLanguageData('Html', $frontend);

        $this->wrapperName = $wrapperName;
        $this->form = $form;
        $this->associatedName = $associatedName;
        $this->notAssociatedName = $notAssociatedName;

        \Cx\Core\Html\Model\Entity\HtmlElement::__construct('div');
        $this->setAttribute('id', $wrapperName);
        $this->addClass('multi-select');

        // Remove the values, that are associated
        foreach ($associatedValues as $key=>$value) {
            if (isset($notAssociatedValues[$key])) {
                unset($notAssociatedValues[$key]);
            }
        }

        // Associated and not associated Wrapper
        $associatedWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $associatedWrapper->addClass('multi-select-wrapper');
        $notAssociatedWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $notAssociatedWrapper->addClass('multi-select-wrapper');

        // Selects
        $associatedSelector = $this->getSelect(
            $associatedName, $associatedValues, $validator
        );
        $notAssociatedSelector = $this->getSelect(
            $notAssociatedName, $notAssociatedValues, $validator
        );

        // Buttons
        $btnWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $btnWrapper->addClass('multi-select-wrapper');
        $btnWrapper->addClass('control-buttons');

        $addButton = $this->getControlElement(
            'button',
            'addBtn',
            $langData['TXT_CORE_HTML_MULTI_SELECT_ADD_ENTRY']
        );
        $removeButton = $this->getControlElement(
            'button',
            'removeBtn',
            $langData['TXT_CORE_HTML_MULTI_SELECT_REMOVE_ENTRY']
        );
        $btnWrapper->addChildren(
            array($addButton, $removeButton)
        );

        // Links
        $associatedLinkWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'div'
        );
        $associatedLinkWrapper->addClass('control-links');
        $selectAllLink = $this->getControlElement(
            'a',
            'select-all',
            $langData['TXT_CORE_HTML_MULTI_SELECT_SELECT_ALL']
        );
        $deselectAllLink = $this->getControlElement(
            'a',
            'deselect-all',
            $langData['TXT_CORE_HTML_MULTI_SELECT_DESELECT_ALL']
        );

        $associatedLinkWrapper->addChildren(
            array(
                $selectAllLink,
                $deselectAllLink
            )
        );

        $notAssociatedLinkWrapper = clone $associatedLinkWrapper;
        $associatedLinkWrapper->addClass('multi-select-associated');
        $notAssociatedLinkWrapper->addClass('multi-select-not-associated');

        // Add titles for select elements
        $associatedTitleElement = new \Cx\Core\Html\Model\Entity\TextElement($associatedTitle);
        $associatedTitleSpan = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $associatedTitleSpan->addChild($associatedTitleElement);

        $notAssociatedTitleElement = new \Cx\Core\Html\Model\Entity\TextElement($notAssociatedTitle);
        $notAssociatedTitleSpan = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $notAssociatedTitleSpan->addChild($notAssociatedTitleElement);

        // Add elements to their wrappers
        $associatedWrapper->addChildren(
            array(
                $associatedTitleSpan,
                $associatedSelector,
                $associatedLinkWrapper
            )
        );

        $notAssociatedWrapper->addChildren(
            array(
                $notAssociatedTitleSpan,
                $notAssociatedSelector,
                $notAssociatedLinkWrapper,
            )
        );

        // Add everything to the main div
        $this->addChildren(
            array(
                $notAssociatedWrapper,
                $btnWrapper,
                $associatedWrapper
            )
        );
    }

    /**
     * Set delimiter with which parents and child elements are separated
     *
     * @param string $delimiter defined delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * Get a select element that has additional attributes
     *
     * @param string $name   name of the select element
     * @param array  $values values for select
     * @param \Cx\Core\Validate\Model\Entity\Validator $validator to validate
     *
     * @return DataElement
     */
    protected function getSelect($name, $values, $validator)
    {
        $selector = new \Cx\Core\Html\Model\Entity\DataElement(
            $name .'[]',
            '',
            'select',
            $validator,
            $values
        );
        $selector->setAttribute('class', $name);
        $selector->setAttribute('size', 15);
        $selector->setAttribute('multiple', 'multiple');

        foreach ($selector->getChildren() as $child) {
            $child->setAttribute(
                'title',
                $values[$child->getAttribute('value')]
            );
        }

        return $selector;
    }

    /**
     * Get an element, which consists of the element tag and a text.
     * The defined text is also set as the title.
     *
     * Used to get the control elements for MultiSelectElement
     *
     * @param string $tag  the tag name of the element (e.g. button)
     * @param string $name to set the class name for the element
     * @param string $text text and title of the element
     *
     * @return HtmlElement element to control actions
     */
    protected function getControlElement($tag, $name, $text)
    {
        $button = new \Cx\Core\Html\Model\Entity\HtmlElement($tag);
        $button->addClass($name);
        $button->setAttribute('title', $text);
        $button->addChild(new \Cx\Core\Html\Model\Entity\TextElement($text));

        return $button;
    }

    /**
     * Load the JavaScript and CSS file for MultiSelectElement. JavaScript variables are
     * set, which are necessary for JavaScript to work correctly.
     *
     * @throws     \Cx\Core\Core\Model\Entity\SystemComponentException
     * @inheritdoc
     */
    public function render()
    {
        $scope = 'multi-select-' . $this->wrapperName;
        // load MultiSelectElement JavaScript code and CSS styles
        $directory = $this->getComponentController()->getDirectory(
            true, true
        );
        \JS::registerCSS($directory . '/View/Style/MultiSelectElement.css');
        \JS::registerJS($directory . '/View/Script/MultiSelectElement.js');
        \JS::registerCode('MultiSelectElementScopes.push("'.$scope.'");');

        // Set JavaScript variables
        $cxJs = \ContrexxJavascript::getInstance();
        $cxJs->setVariable(
            array(
                'associated_form' => $this->form,
                'associated_wrapper' => $this->wrapperName,
                'associated_select' => $this->associatedName,
                'not_associated_select' => $this->notAssociatedName,
                'delimiter' => $this->delimiter
            ),
            $scope
        );

        return parent::render();
    }
}
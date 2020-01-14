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
 * With the TwinSelect element you get two multiple-select elements. This makes
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
 * With the TwinSelect element you get two multiple-select elements. This makes
 * it easier to select entries and post multiple ones.
 *
 * @copyright  Cloudrexx AG
 * @author     Sam Hawkes <sam.hawkes@comvation.com>
 * @package    cloudrexx
 * @subpackage core_html
 * @since      v5.0.3
 */
class TwinSelect extends \Cx\Core\Html\Model\Entity\DataElement
{
    /**
     * Name of the wrapper to identify the TwinSelect
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
     * Title of the associated select
     *
     * @var string
     */
    protected $associatedTitle;

    /**
     * Title of the not associated select
     *
     * @var string
     */
    protected $notAssociatedTitle;

    /**
     * Values of the associated select
     *
     * @var array
     */
    protected $associatedValues;

    /**
     * Values of the not associated select
     *
     * @var array
     */
    protected $notAssociatedValues;

    /**
     * Delimiter with which parents and child elements are separated
     *
     * @var string
     */
    protected $delimiter = '\\';

    /**
     * Activate the "chosen" element. If the number of possible and selected values is less than or equal to a defined
     * value (this can be changed in the Global Configuration), the "chosen" element is displayed, otherwise both
     * selects are displayed.
     *
     * @var bool
     */
    protected $enableChosen = true;

    /**
     * TwinSelect constructor
     *
     * The notAssociatedValues do not necessarily have to contain only the
     * not assigned values. All possible entries can also be passed. The arrays
     * notAssociatedValues and associatedValues are compared and values with the
     * same key are removed from the array notAssociatedValues.
     *
     * @param string $wrapperName         name of the wrapper to identify the
     *                                    TwinSelect
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
        $enableChosen = true,
        $validator = null
    ) {
        global $_ARRAYLANG, $objInit;

        //get the language interface text
        $langData   = $objInit->loadLanguageData('Html');
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        $this->wrapperName = $wrapperName;
        $this->form = $form;
        $this->associatedName = $associatedName;
        $this->notAssociatedName = $notAssociatedName;
        $this->associatedTitle = $associatedTitle;
        $this->notAssociatedTitle = $notAssociatedTitle;
        $this->associatedValues = $associatedValues;
        $this->notAssociatedValues = $notAssociatedValues;
        $this->validator = $validator;
        $this->enableChosen = $enableChosen;

        \Cx\Core\Html\Model\Entity\HtmlElement::__construct('div');
        $this->setAttribute('id', $this->wrapperName);
        $this->addClass('twin-select');

        $totalValues = count($notAssociatedValues) + count($associatedValues);

        if ($this->enableChosen && $totalValues <= 30) {
            $this->initChosen();
        } else {
            $this->enableChosen = false;
            $this->initTwinSelect();
        }
    }

    public function initChosen()
    {
        $mergedValues = array_replace($this->notAssociatedValues, $this->associatedValues);
        $select = $this->getSelect(
            $this->associatedName, $mergedValues, $this->associatedValues
        );
        $select->addClass('init-chosen');
        $select->setAttribute('data-placeholder', $this->associatedTitle);
        $this->addChild($select);
    }

    public function initTwinSelect()
    {
        global $_ARRAYLANG;

        // Remove the values, that are associated
        foreach ($this->associatedValues as $key=>$value) {
            if (isset($this->notAssociatedValues[$key])) {
                unset($this->notAssociatedValues[$key]);
            }
        }

        // Associated and not associated Wrapper
        $associatedWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $associatedWrapper->addClass('twin-select-wrapper');
        $notAssociatedWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $notAssociatedWrapper->addClass('twin-select-wrapper');

        // Selects
        $associatedSelector = $this->getSelect(
            $this->associatedName, $this->associatedValues, $this->validator
        );
        $notAssociatedSelector = $this->getSelect(
            $this->notAssociatedName, $this->notAssociatedValues, $this->validator
        );

        // Buttons
        $btnWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $btnWrapper->addClass('twin-select-wrapper control-buttons');
        $addButton = $this->getControlElement(
            'button',
            'addBtn',
            $_ARRAYLANG['TXT_CORE_HTML_TWIN_SELECT_ADD_ENTRY']
        );
        $removeButton = $this->getControlElement(
            'button',
            'removeBtn',
            $_ARRAYLANG['TXT_CORE_HTML_TWIN_SELECT_REMOVE_ENTRY']
        );
        $btnWrapper->addChildren(
            array($addButton, $removeButton)
        );

        // Links
        $associatedLinkWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'div'
        );
        $associatedLinkWrapper->addClass('control-links');
        $associatedSelectAllLink = $this->getControlElement(
            'a',
            'select-all-associated',
            $_ARRAYLANG['TXT_CORE_HTML_TWIN_SELECT_SELECT_ALL']
        );
        $associatedDeselectAllLink = $this->getControlElement(
            'a',
            'deselect-all-associated',
            $_ARRAYLANG['TXT_CORE_HTML_TWIN_SELECT_DESELECT_ALL']
        );

        $notAssociatedLinkWrapper = new \Cx\Core\Html\Model\Entity\HtmlElement(
            'div'
        );
        $notAssociatedLinkWrapper->addClass('control-links');
        $notAssociatedSelectAllLink = $this->getControlElement(
            'a',
            'select-all-not-associated',
            $_ARRAYLANG['TXT_CORE_HTML_TWIN_SELECT_SELECT_ALL']
        );
        $notAssociatedDeselectAllLink = $this->getControlElement(
            'a',
            'deselect-all-not-associated',
            $_ARRAYLANG['TXT_CORE_HTML_TWIN_SELECT_DESELECT_ALL']
        );

        $associatedLinkWrapper->addChildren(
            array(
                $associatedSelectAllLink,
                $associatedDeselectAllLink
            )
        );

        $notAssociatedLinkWrapper->addChildren(
            array(
                $notAssociatedSelectAllLink,
                $notAssociatedDeselectAllLink
            )
        );

        // Add titles for select elements
        $associatedTitleElement = new \Cx\Core\Html\Model\Entity\TextElement(
            $this->associatedTitle . '<br/>'
        );
        $notAssociatedTitleElement = new \Cx\Core\Html\Model\Entity\TextElement(
            $this->notAssociatedTitle . '<br/>'
        );

        // Add elements to their wrappers
        $associatedWrapper->addChildren(
            array(
                $associatedTitleElement,
                $associatedSelector,
                $associatedLinkWrapper
            )
        );

        $notAssociatedWrapper->addChildren(
            array(
                $notAssociatedTitleElement,
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
     * @param string $name           name of the select element
     * @param array  $values         values for select
     * @param array  $selectedValues the selected values
     *
     * @return DataElement
     */
    protected function getSelect($name, $values, $selectedValues = array())
    {
        $selector = new \Cx\Core\Html\Model\Entity\DataElement(
            $name .'[]',
            '',
            'select',
            $this->validator,
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
            if (!empty($selectedValues[$child->getAttribute('value')])) {
                $child->setAttribute(
                    'selected',
                    true
                );
            }
        }

        return $selector;
    }

    /**
     * Get an element, which consists of the element tag and a text.
     * The defined text is also set as the title.
     *
     * Used to get the control elements for TwinSelect
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
     * Load the JavaScript and CSS file for TwinSelect. JavaScript variables are
     * set, which are necessary for JavaScript to work correctly.
     *
     * @throws     \Cx\Core\Core\Model\Entity\SystemComponentException
     * @inheritdoc
     */
    public function render()
    {
        $directory = $this->getComponentController()->getDirectory(
            true, true
        );
        if ($this->enableChosen) {
            \JS::activate('chosen');
            \JS::registerJS($directory . '/View/Script/ChosenElement.js');
            return parent::render();
        }

        $scope = 'twin-select-' . $this->wrapperName;
        // load Twinselect JavaScript code and CSS styles
        \JS::registerCSS($directory . '/View/Style/TwinSelect.css');
        \JS::registerJS($directory . '/View/Script/TwinSelect.js');
        \JS::registerCode('TwinSelectScopes.push("' . $scope . '");');

        // Set JavaScript variables
        $cxJs = \ContrexxJavascript::getInstance();
        $cxJs->setVariable('associated_form', $this->form, $scope);
        $cxJs->setVariable(
            'associated_wrapper', $this->wrapperName, $scope
        );
        $cxJs->setVariable(
            'associated_select', $this->associatedName, $scope
        );
        $cxJs->setVariable(
            'not_associated_select', $this->notAssociatedName, $scope
        );
        $cxJs->setVariable(
            'delimiter', $this->delimiter, $scope
        );

        return parent::render();
    }
}
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
 * Html Range Slider Element
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_html
 * @since       v5.0.0
 */

namespace Cx\Core\Html\Model\Entity;

/**
 * Html Range Slider Element
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_html
 */
class RangeSliderElement extends HtmlElement {
    private $content;
    
    public function __construct($name, $id, $rangeMin, $rangeMax, $currentMin, $currentMax, $rounding) {
        parent::__construct('div');
        $this->setAttribute('class', 'range-slider');

        // slider
        $sliderTrack = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $sliderTrack->setAttribute('class', 'nstSlider');
        $sliderTrack->setAttribute('id', $id);
        $sliderTrack->setAttribute('data-aria_enabled', 'true');
        $sliderTrack->setAttribute('data-range_min', $rangeMin);
        $sliderTrack->setAttribute('data-range_max', $rangeMax);
        $sliderTrack->setAttribute('data-cur_min', $currentMin);
        $sliderTrack->setAttribute('data-cur_max', $currentMax);

        // rounding must be > 0
        if (!$rounding) {
            $rounding = 1;
        }
        $sliderTrack->setAttribute('data-rounding', $rounding);

        // slider bar
        $sliderBar = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $sliderBar->setAttribute('class', 'bar');
        $sliderBar->addChild(new \Cx\Core\Html\Model\Entity\TextElement(''));
        $sliderTrack->addChild($sliderBar);
    
        // left grip
        $sliderHandleLeft = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $sliderHandleLeft->setAttribute('class', 'leftGrip range-slider-grip');
        $sliderHandleLeft->addChild(new \Cx\Core\Html\Model\Entity\TextElement(''));
        $sliderTrack->addChild($sliderHandleLeft);
    
        // right grip
        $sliderHandleRight = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $sliderHandleRight->setAttribute('class', 'rightGrip range-slider-grip');
        $sliderHandleRight->addChild(new \Cx\Core\Html\Model\Entity\TextElement(''));
        $sliderTrack->addChild($sliderHandleRight);

        $this->addChild($sliderTrack);
    
        $sliderPulls = new \Cx\Core\Html\Model\Entity\HtmlElement('p');

        // left label
        $sliderPullLeft = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $sliderPullLeft->setAttribute('class', 'pull-left');
        $sliderPullLeft->addChild(new \Cx\Core\Html\Model\Entity\TextElement(''));
        $sliderPulls->addChild($sliderPullLeft);

        // right label
        $sliderPullRight = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
        $sliderPullRight->setAttribute('class', 'pull-right');
        $sliderPullRight->addChild(new \Cx\Core\Html\Model\Entity\TextElement(''));
        $sliderPulls->addChild($sliderPullRight);

        $this->addChild($sliderPulls);

        // hidden input to store selected min value
        $sliderSelectionMin = new \Cx\Core\Html\Model\Entity\HtmlElement('input');
        $sliderSelectionMin->setAttribute('type', 'hidden');
        $sliderSelectionMin->setAttribute('name', $name.'[]');
        $sliderSelectionMin->setAttribute('id', $id.'_min');
        $sliderSelectionMin->setAttribute('value', $currentMin);
        $this->addChild($sliderSelectionMin);
        
        // hidden input to store selected max value
        $sliderSelectionMax = new \Cx\Core\Html\Model\Entity\HtmlElement('input');
        $sliderSelectionMax->setAttribute('type', 'hidden');
        $sliderSelectionMax->setAttribute('name', $name.'[]');
        $sliderSelectionMax->setAttribute('id', $id.'_max');
        $sliderSelectionMax->setAttribute('value', $currentMax);
        $this->addChild($sliderSelectionMax);
    }
    
    public function render() {
        // load Nestoria Sliders jQuery Plugin
        \JS::activate('jquery-nstslider');

        // load RangeSliderElement javascript code and CSS styles
        $directory = $this->getComponentController()->getDirectory(true, true);
        \JS::registerJS($directory . '/View/Script/RangeSliderElement.js');
        \JS::registerCSS($directory . '/View/Style/RangeSliderElement.css');

        return parent::render();
    }
}


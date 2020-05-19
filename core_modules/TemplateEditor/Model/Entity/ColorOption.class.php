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


namespace Cx\Core_Modules\TemplateEditor\Model\Entity;

use Cx\Core\Html\Sigma;

/**
 * Class ColorOption
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class ColorOption extends Option
{
    /**
     * RegEx for CSS colors
     * Based on https://gist.github.com/olmokramer/82ccce673f86db7cda5e
     * @var string
     */
    const CSS_COLOR_REGEX = '/^(#([0-9a-f]{3}){1,2}|(rgba|hsla)\(\d{1,3}%?(,\s?\d{1,3}%?){2},\s?(1(?:\.0)?|0|0?\.\d+)\)|(rgb|hsl)\(\d{1,3}%?(,\s?\d{1,3}%?){2}\))$/i';

    /**
     * Color in hex format
     *
     * @var String
     */
    protected $color;

    /**
     * Array with color choices in hex format
     *
     * @var array
     */
    protected $choice;

    /**
     * @param String $name Name of the option
     * @param array  $translations Array with translations for option.
     * @param array  $data
     */
    public function __construct($name, $translations, $data)
    {
        parent::__construct($name, $translations, $data);
        $this->color = $data['color'];
        if (isset($data['choice'])) {
            $this->choice = $data['choice'];
        }
    }

    /**
     * Render the option field in the backend.
     *
     * @param Sigma $template
     */
    public function renderOptionField($template)
    {
        global $_ARRAYLANG;
        $subTemplate = new Sigma();
        $subTemplate->loadTemplateFile(
            $this->cx->getCodeBaseCoreModulePath()
            . '/TemplateEditor/View/Template/Backend/ColorOption.html'
        );
        $subTemplate->setVariable('TEMPLATEEDITOR_OPTION_VALUE', $this->color);
        $subTemplate->setVariable('TEMPLATEEDITOR_OPTION_NAME', $this->name);
        $subTemplate->setVariable(
            'TEMPLATEEDITOR_OPTION_HUMAN_NAME', $this->humanName
        );
        if ($this->choice) {
            $subTemplate->setVariable(
                'TEMPLATEEDITOR_OPTION_CHOICE', json_encode($this->choice)
            );
        }
        \ContrexxJavascript::getInstance()->setVariable(
            array(
                'select' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_SELECT'],
                'colorError' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_COLOR_WRONG_FORMAT'],
                'cancel' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL']
            ),
            'TemplateEditor'
        );
        $template->setVariable('TEMPLATEEDITOR_OPTION', $subTemplate->get());
        $template->setVariable('TEMPLATEEDITOR_OPTION_TYPE', 'color');
        $template->parse('option');
    }

    /**
     * Render the option in the frontend.
     *
     * @param Sigma $template
     */
    public function renderTheme($template)
    {
        $template->setVariable(
            'TEMPLATE_EDITOR_' . strtoupper($this->name), $this->color
        );
        for ($i = -255; $i < 255; $i++) {
            $template->setVariable(
                'TEMPLATE_EDITOR_' . strtoupper($this->name) . '_' . (($i < 0)
                    ? 'SUBTRACT' : 'ADD') . '_' . abs($i),
                $this->adjustBrightness($this->color, $i)
            );
        }

    }

    /**
     * Handle a change of the option.
     *
     * @param array $data Data from frontend javascript
     *
     * @return array Changed data for the frontend javascript
     *
     * @throws OptionValueNotValidException If the data which the option should
     *                                      handle is invalid this exception
     *                                      will be thrown.
     *                                      It gets caught by the JsonData
     *                                      class and gets handled by the
     *                                      javascript callback in the frontend.
     */
    public function handleChange($data)
    {
        global $_ARRAYLANG;
        if (!preg_match(static::CSS_COLOR_REGEX, $data)) {
            throw new OptionValueNotValidException(
                $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_COLOR_WRONG_FORMAT']
            );
        }
        $this->color = $data;
        return array('color' => $this->color);
    }

    /**
     * Get the data in a serializable format.
     *
     * @return array
     */
    public function yamlSerialize()
    {
        $option = parent::yamlSerialize();
        $option['specific'] = array(
            'choice' => $this->choice
        );
        return $option;
    }

    /**
     * Get the color
     *
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set the color
     *
     * @param mixed $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * Gets the current value of the option.
     *
     * @return array
     */
    public function getValue()
    {
        return array('color' => $this->color);
    }

    /**
     * Adjust the brightness of a hex color
     *
     * @param $hex
     * @param $steps
     *
     * @return string
     */
    function adjustBrightness($hex, $steps)
    {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));
        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(
                    substr($hex, 1, 1), 2
                ) . str_repeat(substr($hex, 2, 1), 2);
        }
        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);
        $return      = '#';
        foreach ($color_parts as $color) {
            $color = hexdec($color); // Convert to decimal
            $color = max(0, min(255, $color + $steps)); // Adjust color
            $return .= str_pad(
                dechex($color), 2, '0', STR_PAD_LEFT
            ); // Make two char hex code
        }
        return $return;
    }
}

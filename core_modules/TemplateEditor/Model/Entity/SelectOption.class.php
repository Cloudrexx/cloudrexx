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
 * class SelectOption
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 */
class SelectOption extends Option
{

    /**
     * The text value of the option.
     *
     * @var string
     */
    protected $activeChoice = '';

    /**
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
        $this->activeChoice = isset($data['activeChoice'])
            ? $data['activeChoice'] : '';
        $this->choice       = isset($data['choice']) ? $data['choice'] : '';
    }

    /**
     * Render the option field in the backend.
     *
     * @param Sigma $template
     */
    public function renderOptionField($template)
    {
        global $_LANGID;
        $subTemplate = new Sigma();
        $subTemplate->loadTemplateFile(
            $this->cx->getCodeBaseCoreModulePath()
            . '/TemplateEditor/View/Template/Backend/SelectOption.html'
        );
        foreach ($this->choice as $value => $choice) {
            $subTemplate->setVariable(
                'CHOICE_NAME',
                isset($choice[$_LANGID])
                    ? $choice[$_LANGID]
                    : (isset($choice[2]) ? $choice[2] : $value)
            );
            $subTemplate->setVariable('CHOICE_VALUE', $value);
            if ($value == $this->activeChoice) {
                $subTemplate->setVariable('CHOICE_ACTIVE', 'selected');
            }
            $subTemplate->parse('choices');
        }
        $subTemplate->setVariable(
            'TEMPLATEEDITOR_OPTION_VALUE', $this->activeChoice
        );
        $subTemplate->setVariable('TEMPLATEEDITOR_OPTION_NAME', $this->name);
        $subTemplate->setVariable(
            'TEMPLATEEDITOR_OPTION_HUMAN_NAME', $this->humanName
        );
        $template->setVariable('TEMPLATEEDITOR_OPTION', $subTemplate->get());
        $template->setVariable('TEMPLATEEDITOR_OPTION_TYPE', 'select');
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
            'TEMPLATE_EDITOR_' . strtoupper($this->name), $this->activeChoice
        );
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
        if (!is_string($data) || !isset($this->choice[$data])) {
            throw new OptionValueNotValidException('Not a option!');
        }
        $this->activeChoice = $data;
        return array('activeChoice' => $this->activeChoice);
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
     * Gets the current value of the option.
     *
     * @return array
     */
    public function getValue()
    {
        return array('activeChoice' => $this->activeChoice);
    }
}

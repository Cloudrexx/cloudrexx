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
 * Class AreaOption
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class AreaOption extends Option
{

    /**
     * Shows whether the area should be shown
     *
     * @var bool
     */
    protected $active;

    /**
     * @param String $name Name of the option
     * @param array  $translations Array with translations for option.
     * @param array  $data
     */
    public function __construct($name, $translations, $data)
    {
        parent::__construct($name, $translations, $data);
        $this->active = $data['active'] == 'true';
    }

    /**
     * Render the option field in the backend.
     *
     * @param Sigma $template
     */
    public function renderOptionField($template)
    {
        $subTemplate = new Sigma();
        $subTemplate->loadTemplateFile(
            $this->cx->getCodeBaseCoreModulePath()
            . '/TemplateEditor/View/Template/Backend/AreaOption.html'
        );
        $subTemplate->setVariable(
            'TEMPLATEEDITOR_OPTION_VALUE', ($this->active) ? 'checked' : ''
        );
        $subTemplate->setVariable('TEMPLATEEDITOR_OPTION_NAME', $this->name);
        $subTemplate->setVariable(
            'TEMPLATEEDITOR_OPTION_HUMAN_NAME', $this->humanName
        );
        $template->setVariable('TEMPLATEEDITOR_OPTION', $subTemplate->get());
        $template->setVariable('TEMPLATEEDITOR_OPTION_TYPE', 'area');
        $template->parse('option');
    }

    /**
     * Render the option in the frontend.
     *
     * @param Sigma $template
     */
    public function renderTheme($template)
    {
        $blockName = strtolower('TEMPLATE_EDITOR_' . $this->name);
        if (!$template->blockExists($blockName)) {
            return;
        }

        if ($this->active) {
            $template->touchBlock($blockName);
        } else {
            $template->hideBlock($blockName);
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
        if ($data != 'true' && $data != 'false') {
            throw new OptionValueNotValidException('Should be true or false.');
        }
        $this->active = $data;
        return array('active' => $data);
    }


    /**
     * Returns if area is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Set is active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Gets the current value of the option.
     *
     * @return array
     */
    public function getValue()
    {
        return array('active' => $this->active);
    }
}

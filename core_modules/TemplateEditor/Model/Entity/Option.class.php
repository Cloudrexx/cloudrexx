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
use Cx\Core_Modules\TemplateEditor\Model\YamlSerializable;

/**
 * Class OptionValueNotValidException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package contrexx
 * @subpackage  core_module_templateeditor
 */
class OptionValueNotValidException extends \Exception
{
}

/**
 * Class Option
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
abstract class Option extends \Cx\Model\Base\EntityBase
    implements YamlSerializable
{

    /**
     * The identifying name of the option.
     * @var void
     */
    protected $name;

    /**
     * Array with translations for all available languages.
     * The key of the array is the language id.
     *
     * @var array
     */
    protected $translations;

    /**
     * The translated name of the option. If the active language isn't available
     * english is used as a fallback. If this also isn't available the name of
     * the option is used.
     *
     * @var string
     */
    protected $humanName;

    /**
     * @param String $name Name of the option
     * @param array  $translations Array with translations for option.
     * @param array  $data
     */
    public function __construct($name, $translations, $data) {
        global $_LANGID;
        $this->name         = $name;
        $this->humanName    = isset($translations[$_LANGID])
            ? $translations[$_LANGID]
            : (isset($translations[2]) ? $translations[2] : $name);
        $this->translations = $translations;
    }

    /**
     * Render the option field in the backend.
     *
     * @param Sigma $template The template of the backend view.
     */
    public abstract function renderOptionField($template);

    /**
     * Render the option in the frontend.
     *
     * @param Sigma $template The frontend template.
     */
    public abstract function renderTheme($template);

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
    public abstract function handleChange($data);

    /**
     * Get the name of the option.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set the name of the option.
     *
     * @param void $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get the translated name of the option
     *
     * @return string
     */
    public function getHumanName() {
        return $this->humanName;
    }

    /**
     * Set the human name.
     *
     * @param string $humanName
     */
    public function setHumanName($humanName) {
        $this->humanName = $humanName;
    }

    /**
     * Get the data in a serializable format.
     *
     * @return array
     */
    public function yamlSerialize() {
        return array(
            'name' => $this->name,
            'specific' => array(),
            'type' => get_called_class(),
            'translation' => $this->translations
        );
    }

    /**
     * Gets the current value of the option.
     *
     * @return array
     */
    public abstract function getValue();

}

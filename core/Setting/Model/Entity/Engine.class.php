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
 * Specific Setting for this Component. Use this abstract class extends with the Db.class.php or FileSystem.class.php
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com> (parts)
 * @author      Manish Thakur <manishthakur@cdnsol.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_setting
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core\Setting\Model\Entity;

/**
 * Manages settings stored in the database or file system
 *
 * Before trying to access a modules' settings, *DON'T* forget to call
 * {@see Setting::init()} before calling getValue() for the first time!
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com> (parts)
 * @author      Manish Thakur <manishthakur@cdnsol.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_setting
 * @todo        Edit PHP DocBlocks!
 */
abstract class Engine implements EngineInterface {
    /**
     * The array of currently loaded settings, like
     *  array(
     *    'name' => array(
     *      'section' => section,
     *      'group' => group,
     *      'value' => current value,
     *      'type' => element type (text, dropdown, ... [more to come]),
     *      'values' => predefined values (for dropdown),
     *      'ord' => ordinal number (for sorting),
     *    ),
     *    ... more ...
     *  );
     * @var     array
     *
     * @access  protected
     */
    protected $arrSettings = null;

    /**
     * The group last used to {@see init()} the settings.
     * Defaults to null (ignored).
     * @var     string
     *
     * @access  protected
     */

    protected $group = null;
    /**
     * The section last used to {@see init()} the settings.
     * Defaults to null (which will cause an error in most methods).
     * @var     string
     *
     * @access  protected
     */

    protected $section = null;
    /**
     * Changed flag
     *
     * This flag is set to true as soon as any change to the settings is detected.
     * It is cleared whenever {@see updateAll()} is called.
     * @var     boolean
     *
     * @access  protected
     */
    protected $changed = false;
    /**
     * Returns the current value of the changed flag.
     *
     * If it returns true, you probably want to call {@see updateAll()}.
     * @return  boolean           True if values have been changed in memory,
     *                            false otherwise
     */
    public  function changed()
    {
        return $this->changed;
    }

    /**
     * Tab counter for the {@see show()} and {@see show_external()}
     * @var     integer
     * @access  public
     */
    public $tab_index = 1;

    /**
     * Optionally sets and returns the value of the tab index
     * @param   integer $tab_index  The optional new tab index
     * @return  integer             The current tab index
     */
    public  function tab_index($tab_index=null)
    {
        if (isset($tab_index)) {
            $this->tab_index = intval($tab_index);
        }
        return $this->tab_index;
    }

    /**
     * Flush the stored settings
     *
     * Resets the class to its initial state.
     * Does *NOT* clear the section, however.
     * @return  void
     */
    public  function flush()
    {
        $this->arrSettings = null;
        $this->section = null;
        $this->group = null;
        $this->changed = null;
    }

    /**
     * Returns the settings array for the given section and group
     *
     * See {@see init()} on how the arguments are used.
     * If the method is called successively using the same $group argument,
     * the current settings are returned without calling {@see init()}.
     * Thus, changes made by calling {@see set()} will be preserved.
     * @param   string    $section    The section
     * @param   string    $group        The optional group
     * @return  array                 The settings array on success,
     *                                false otherwise
     */
    public function getArray($section, $group=null)
    {
        if ($this->section !== $section
         || $this->group !== $group) {
            if (!parent::init($section, $group)) return false;
        }
        return $this->arrSettings;
    }

    /**
     * Returns the settings value stored in the object for the name given.
     *
     * If the settings have not been initialized (see {@see init()}), or
     * if no setting of that name is present in the current set, null
     * is returned.
     * @param   string    $name       The settings name
     * @return  mixed                 The settings value, if present,
     *                                null otherwise
     */
    public function getValue($name)
    {
        if (is_null($this->arrSettings)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\Engine::getValue($name): ERROR: no settings loaded");
            return null;
        }
        if (isset($this->arrSettings[$name]['value'])) {
            return $this->arrSettings[$name]['value'];
        };
        return null;
    }

     /**
     * Returns the true or false for given the setting name
     *
     * If the settings have not been initialized (see {@see init()}), or
     * if no setting of that name is present in the current set, false
     * is returned.
     * @param   string    $name       The settings name
     * @return  boolean               The if setting name is exist returned true,
     *                                false otherwise
     */
    public  function isDefined($name)
    {
        if (isset($this->arrSettings[$name]['name'])) {
            return true;
        }
        return false;
    }


    /**
     * Updates a setting
     *
     * If the setting name exists and the new value is not equal to
     * the old one, it is updated, and $changed set to true.
     * Otherwise, nothing happens, and false is returned
     * @see init(), updateAll()
     * @param   string    $name       The settings name
     * @param   string    $value      The settings value
     * @return  boolean               True if the value has been changed,
     *                                false otherwise, null on noop
     */
    public function set($name, $value)
    {
        if (!isset($this->arrSettings[$name])) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\Engine::set($name, $value): Unknown, changed: ".$this->changed);
            return false;
        }
        if ($this->arrSettings[$name]['value'] == $value) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\Engine::set($name, $value): Identical, changed: ".$this->changed);
            return null;
        }
        $this->changed = true;
        $this->arrSettings[$name]['value'] = $value;
        \DBG::log("\Cx\Core\Setting\Model\Entity\Engine::set($name, $value): Added/updated, changed: ".$this->changed);
        return true;
    }

    /**
     * Adds element to array
     * @param   string  $name
     * @param   string  $item
     */
    public function addToArray($name, $item)
    {
        $this->arrSettings[$name] = $item;
    }

    /**
     * Get group
     * @return  group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Get section
     * @return  string
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Get array
     * @return type
     */
    public function getArraySetting() {
        return $this->arrSettings;
    }

}

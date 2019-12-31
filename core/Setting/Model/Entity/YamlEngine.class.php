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
 * Specific Setting for this Component. Use this to interact with the Setting.class.php
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
class YamlEngine extends Engine{
    /**
     * Path to the configuration file used as storage location
     * @var string
     */
    protected $filename = null;
    protected $yamlSettingRepo = null;

    /**
     * Initialize the settings entries from the file with key/value pairs
     * for the current section and the given group
     *
     * An empty $group value is ignored.  All records with the section are
     * included in this case.
     * Note that all setting names *SHOULD* be unambiguous for the entire
     * section.  If there are two settings with the same name but different
     * $group values, the second one may overwrite the first!
     * @internal  The records are ordered by
     *            `group` ASC, `ord` ASC, `name` ASC
     * @param   string    $section    The section
     * @param   string    $group      The optional group.
     *                                Defaults to null
     * @param   string    $configRepository     An optional path
     *                                to the storage location of config files (/config) which shall be used for the engine 'File System'.
     * @return  boolean               True on success, false otherwise
     */
     function init($section, $group=null, $configRepository = null) {
        if (!$configRepository) {
            $configRepository = \Env::get('cx')->getWebsiteConfigPath();
        }

        $this->flush();
        $this->section = $section;
        $this->group = $group;
        $this->filename =  $configRepository . '/'.$section.'.yml';

        $this->yamlSettingRepo = new \Cx\Core\Setting\Model\Repository\YamlSettingRepository($this->filename);
        $this->arrSettings = $this->load();

    }

    function load() {
        if (!empty($this->yamlSettingRepo)) {
            $yamlSettings = $this->yamlSettingRepo->findAll();
            $yamlSettingArray = array();
            $websitePath      = \Cx\Core\Core\Controller\Cx::instanciate()
                ->getWebsiteDocumentRootPath();
            if (isset($yamlSettings)) {
                foreach ($yamlSettings as $yamlSetting) {
                    $value = $yamlSetting->getValue();
                    if (
                        $yamlSetting->getType() == \Cx\Core\Setting\Controller\Setting::TYPE_FILECONTENT &&
                        $yamlSetting->getValues() &&
                        \Cx\Lib\FileSystem\FileSystem::exists(
                            $websitePath . '/' . $yamlSetting->getValues() 
                        )
                    ) {
                        try {
                            $objFile  = new \Cx\Lib\FileSystem\File(
                                $websitePath . '/' . $yamlSetting->getValues()
                            );
                            $value = $objFile->getData();
                        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                            \DBG::log($e->getMessage());
                            $value = '';
                        }
                    }
                    $yamlSettingArray[$yamlSetting->getName()] = array(
                        'name'    => $yamlSetting->getName(),
                        'section' => $yamlSetting->getSection(),
                        'group'   => $yamlSetting->getGroup(),
                        'value'   => $value,
                        'type'    => $yamlSetting->getType(),
                        'values'  => $yamlSetting->getValues(),
                        'ord'     => $yamlSetting->getOrd()
                    );
                }
                return $yamlSettingArray;
            }
        }
        return array();
    }

    /**
     * Returns the settings array for the given section and group
     * @return  array
     */
    public function getArraySetting()
    {
        $settingArray=array();
        if (!empty($this->group)) {
            foreach ($this->arrSettings as $value) {
                if ($value['group']==$this->group) {
                    $settingArray[$value['name']]= $value;
                }
            }
        } else {
            $settingArray=$this->arrSettings;
        }
        return $settingArray;
    }

    public function getArray($section, $group = null)
    {
        $groupArray = array();
        if ($group !== null && $this->section == $section) {
            foreach ($this->arrSettings as $value) {
                if ($value['group'] == $group) {
                    $groupArray[$value['name']] = $value;
                }
            }
        } else {
            $groupArray = $this->arrSettings;
        }
        return $groupArray;
    }
    /**
     * Stores all settings entries present in the $arrSettings object
     * array variable
     *
     * Returns boolean true if all records were stored successfully,
     * null if nothing changed (noop), false otherwise.
     * Upon success, also resets the $changed class variable to false.
     * The class *MUST* have been initialized before calling this
     * method using {@see init()}, and the new values been {@see set()}.
     * Note that this method does not work for adding new settings.
     * See {@see add()} on how to do this.
     * @return  boolean                   True on success, null on noop,
     *                                    false otherwise
     */
    function updateAll()
    {
        //global $_CORELANG;
        if (!$this->changed) {
        // TODO: These messages are inapropriate when settings are stored by another piece of code, too.
        // Find a way around this.
        // Message::information($_CORELANG['TXT_CORE_SETTING_INFORMATION_NO_CHANGE']);
            return null;
        }
        // TODO: Add error messages for section errors
        if (empty($this->section)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::updateAll(): ERROR: Empty section!");
            return false;
        }
        // TODO: Add error messages for setting array errors
        if (empty($this->arrSettings)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::updateAll(): ERROR: Empty section!");
            return false;
        }
        $success = true;
        try {
            foreach ($this->arrSettings As $yamlSettingName => $yamlSettingValue) {
                $objYamlSetting = $this->yamlSettingRepo->findOneBy(array('name' => $yamlSettingName, 'section' => $this->section));
                if ($objYamlSetting->getType() == \Cx\Core\Setting\Controller\Setting::TYPE_FILECONTENT) {
                    continue;
                }
                $objYamlSetting->setValue($yamlSettingValue['value']);

            }
            $this->yamlSettingRepo->flush();
        } catch (\Cx\Core\Setting\Model\Entity\YamlSettingException $e) {
            \DBG::msg($e->getMessage());
            return false;
        }
        if ($success) {
            $this->changed = false;
            //return Message::ok($_CORELANG['TXT_CORE_SETTING_STORED_SUCCESSFULLY']);
            return true;
        }
        //return Message::error($_CORELANG['TXT_CORE_SETTING_ERROR_STORING']);
        return false;
    }

    /**
     * Updates the value for the given name in the settings
     *
     * The class *MUST* have been initialized before calling this
     * method using {@see init()}, and the new value been {@see set()}.
     * Sets $changed to true and returns true if the value has been
     * updated successfully.
     * Note that this method does not work for adding new settings.
     * See {@see add()} on how to do this.
     * Also note that the loaded setting is not updated,
     * @param   string    $name   The settings name
     * @return  boolean           True on successful update or if
     *                            unchanged, false on failure
     *
     */
     function update($name)
    {
        // TODO: Add error messages for individual errors
        if (empty($this->section)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::update(): ERROR: Empty section!");
            return false;
        }
        // Fail if the name is invalid
        // or the setting does not exist
        if (empty($name)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::update(): ERROR: Empty name!");
            return false;
        }
        if (!isset($this->arrSettings[$name])) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::update(): ERROR: Unknown setting name '$name'!");
            return false;
        }
        if (!empty($this->arrSettings)) {
            try {
                $objYamlSetting = $this->yamlSettingRepo->findOneBy(array('name' => $name, 'section' => $this->section));
                // do not flush file-content to setting repo
                if ($objYamlSetting->getType() == \Cx\Core\Setting\Controller\Setting::TYPE_FILECONTENT) {
                    return true;
                }
                $objYamlSetting->setValue($this->arrSettings[$name]['value']);

                $this->yamlSettingRepo->flush();
            } catch (\Cx\Core\Setting\Model\Entity\YamlSettingException $e) {
                \DBG::msg($e->getMessage());
                return false;
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a new record to the settings
     *
     * The class *MUST* have been initialized by calling {@see init()}
     * or {@see getArray()} before this method is called.
     * The present $group stored in the class is used as a default.
     * If the current class $group is empty, it *MUST* be specified in the call.
     * @param   string    $name     The setting name
     * @param   string    $value    The value
     * @param   integer   $ord      The ordinal value for sorting,
     *                              defaults to 0
     * @param   string    $type     The element type for displaying,
     *                              defaults to 'text'
     * @param   string    $values   The values for type 'dropdown',
     *                              defaults to the empty string
     * @param   string    $group    The optional group
     * @return  boolean             True on success, false otherwise
     */
    function add( $name, $value, $ord=false, $type='text', $values='', $group=null)
    {
        if (!isset($this->section)) {
            // TODO: Error message
            \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::add(): ERROR: Empty section!");
            return false;
        }
        // Fail if the name is invalid
        if (empty($name)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::add(): ERROR: Empty name!");
            return false;
        }
        // This can only be done with a non-empty group!
        // Use the current group, if present, otherwise fail
        if (!$group) {
            if (!$this->group) {
                \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::add(): ERROR: Empty group!");
                return false;
            }
            $group = $this->group;
        }
        // Initialize if necessary
        if (is_null($this->arrSettings) || $this->group != $group){
            $this->init($this->section, $group);
        }
        // Such an entry exists already, fail.
        // Note that getValue() returns null if the entry is not present
        $old_value = $this->getValue($name);
        if (isset($old_value)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\YamlEngine::add(): ERROR: Setting '$name' already exists and is non-empty ($old_value)");
            return false;
        }

        try {
            $objYamlSetting = new \Cx\Core\Setting\Model\Entity\YamlSetting($name);
            $objYamlSetting->setSection($this->section);
            $objYamlSetting->setGroup($group);
            $objYamlSetting->setValue($value);
            $objYamlSetting->setValue($value);
            $objYamlSetting->setType($type);
            $objYamlSetting->setValues($values);
            $objYamlSetting->setOrd($ord);

            $this->yamlSettingRepo->add($objYamlSetting);
            $this->yamlSettingRepo->flush();
            } catch (\Cx\Core\Setting\Model\Entity\YamlSettingException $e) {
            \DBG::msg($e->getMessage());
            return false;
        }

        $this->arrSettings = $this->load();
        return true;
    }

    /**
     * Delete one or more records from the File
     *
     * For maintenance/update purposes only.
     * At least one of the parameter values must be non-empty.
     * It will fail if both are empty.  Mind that in this case,
     * no records will be deleted.
     * Does {@see flush()} the currently loaded settings on success.
     * @param   string    $name     The optional setting name.
     *                              Defaults to null
     * @param   string    $group      The optional group.
     *                              Defaults to null
     * @return  boolean             True on success, false otherwise
     */
    function delete($name=null, $group=null)
    {
        // Fail if both parameter values are empty
        if (empty($name) && empty($group) && empty($this->section)) return false;

        try {
            if (!empty($name) && !empty($group)) {
                $yamlSettings = $this->yamlSettingRepo->findBy(array('name' => $name, 'group' => $group, 'section' => $this->section));
            } else if (!empty ($name)) {
                $yamlSettings = $this->yamlSettingRepo->findBy(array('name' => $name, 'section' => $this->section));
            } else if (!empty ($group)) {
                $yamlSettings = $this->yamlSettingRepo->findBy(array('group' => $group, 'section' => $this->section));
            }
            foreach ($yamlSettings As $yamlSetting) {
                $this->yamlSettingRepo->remove($yamlSetting);
            }
            $this->yamlSettingRepo->flush();
        } catch (\Cx\Core\Setting\Model\Entity\YamlSettingException $e) {
            \DBG::msg($e->getMessage());
            return false;
        }

        $this->arrSettings = $this->load();
        return true;
    }

    /**
     * Deletes all entries for the current section
     *
     * This is for testing purposes only.  Use with care!
     * The $section determines the module affected.
     * @return    boolean               True on success, false otherwise
     */
    function deleteModule()
    {
        if (empty($this->section)) return false;

        try {
            $yamlSettings = $this->yamlSettingRepo->findBy(array('section' => $this->section));
            foreach ($yamlSettings As $yamlSetting) {
                $this->yamlSettingRepo->remove($yamlSetting);
            }
            $this->yamlSettingRepo->flush();
            return true;
        } catch (\Cx\Core\Setting\Model\Entity\YamlSettingException $e) {
            \DBG::msg($e->getMessage());
            return false;
        }
    }

    /**
     * Should be called whenever there's a problem with the settings
     *
     * Tries to fix or recreate the settings.
     * @return  boolean             False, always.
     *
     */
    function errorHandler()
    {
        try {
            $file = new \Cx\Lib\FileSystem\File(\Env::get('cx')->getWebsiteConfigPath() . '/'.$this->section.'.yml');
            $file->touch();
            return false;
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }
    }
}

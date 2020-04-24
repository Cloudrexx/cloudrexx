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
class DbEngine extends Engine{
    /**
     * Initialize the settings entries from the database with key/value pairs
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
     * @return  boolean               True on success, false otherwise
     * @global  ADOConnection   $objDatabase
     */
    function init($section, $group=null, $configRepository = null) {
        global $objDatabase;

        if (empty($section)) {
            die("\Cx\Core\Setting\Model\Entity\DbEngine::init($section, $group): ERROR: Missing \$section parameter!");
            //return false;
        }
        $this->flush();
        //echo("$this->init($section, $group): Entered<br />");
        $objResult = $objDatabase->Execute("
            SELECT `name`, `group`, `value`,
                   `type`, `values`, `ord`
              FROM ".DBPREFIX."core_setting
             WHERE `section`='".addslashes($section)."'".
             ($group ? " AND `group`='".addslashes($group)."'" : '')."
             ORDER BY `group` ASC, `ord` ASC, `name` ASC");
        if (!$objResult) return $this->errorHandler();
        // Set the current group to the empty string if empty
        $this->section = $section;
        $this->group = $group;
        $this->arrSettings = array();
        $websitePath = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getWebsiteDocumentRootPath();
        while (!$objResult->EOF) {
            $value      = $objResult->fields['value'];
            $type       = $objResult->fields['type'];
            $values     = $objResult->fields['values'];
            if (
                $type == \Cx\Core\Setting\Controller\Setting::TYPE_FILECONTENT &&
                $values &&
                \Cx\Lib\FileSystem\FileSystem::exists(
                    $websitePath . '/' . $values
                )
            ) {
                try {
                    $objFile  = new \Cx\Lib\FileSystem\File(
                        $websitePath . '/' . $values
                    );
                    $value = $objFile->getData();
                } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                    \DBG::log($e->getMessage());
                    $value = '';
                }
            }
            $this->arrSettings[$objResult->fields['name']] = array(
                'name'    => $objResult->fields['name'],
                'section' => $section,
                'group'   => $objResult->fields['group'],
                'value'   => $value,
                'type'    => $type,
                'values'  => $values,
                'ord'     => $objResult->fields['ord'],
            );
            //echo("Setting ".$objResult->fields['name']." = ".$objResult->fields['value']."<br />");
        $objResult->MoveNext();
        }
    }

    /**
     * Returns the settings array for the given section and group
     * @return  array
     */
    function getArraySetting()
    {
       return $this->arrSettings;
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
        //        global $_CORELANG;

        if (!$this->changed) {
            // TODO: These messages are inapropriate when settings are stored by another piece of code, too.
            // Find a way around this.
            // Message::information($_CORELANG['TXT_CORE_SETTING_INFORMATION_NO_CHANGE']);
            return null;
        }
        $success = true;
        foreach ($this->arrSettings as $name => $arrSetting) {
            $success &= $this->update($name);
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
     * Updates the value for the given name in the settings table
     *
     * The class *MUST* have been initialized before calling this
     * method using {@see init()}, and the new value been {@see set()}.
     * Sets $changed to true and returns true if the value has been
     * updated successfully.
     * Note that this method does not work for adding new settings.
     * See {@see add()} on how to do this.
     * Also note that the loaded setting is not updated, only the database!
     * @param   string    $name   The settings name
     * @return  boolean           True on successful update or if
     *                            unchanged, false on failure
     *
     * @global  mixed     $objDatabase    Database connection object
     */
    function update($name)
    {
        global $objDatabase;

        // TODO: Add error messages for individual errors
        if (empty($this->section)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\DbEngine::update(): ERROR: Empty section!");
            return false;
        }
        // Fail if the name is invalid
        // or the setting does not exist
        if (empty($name)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\DbEngine::update(): ERROR: Empty name!");
            return false;
        }
        if (!isset($this->arrSettings[$name])) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\DbEngine::update(): ERROR: Unknown setting name '$name'!");
            return false;
        }
        // do not flush file-content to setting repo
        if ($this->arrSettings[$name]['type'] == \Cx\Core\Setting\Controller\Setting::TYPE_FILECONTENT) {
            return true;
        }
        $objResult = $objDatabase->Execute("
            UPDATE `".DBPREFIX."core_setting`
               SET `value`='".addslashes($this->arrSettings[$name]['value'])."'
             WHERE `name`='".addslashes($name)."'
               AND `section`='".addslashes($this->section)."'".
            ($this->group
                ? " AND `group`='".addslashes($this->group)."'" : ''));
        if (!$objResult) return $this->errorHandler();
        $this->changed = true;
        return true;
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
    function add($name, $value, $ord=false, $type='text', $values='', $group=null)
    {
        global $objDatabase;
        if (!isset($this->section)) {
            // TODO: Error message
            \DBG::log("\Cx\Core\Setting\Model\Entity\DbEngine::add(): ERROR: Empty section!");
            return false;
        }
        // Fail if the name is invalid
        if (empty($name)) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\DbEngine::add(): ERROR: Empty name!");
            return false;
        }
        // This can only be done with a non-empty group!
        // Use the current group, if present, otherwise fail
        if (!$group) {
            if (!$this->group) {
                \DBG::log("\Cx\Core\Setting\Model\Entity\DbEngine::add(): ERROR: Empty group!");
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
            \DBG::log("\Cx\Core\Setting\Model\Entity\DbEngine::add(): ERROR: Setting '$name' already exists and is non-empty ($old_value)");
            return false;
        }

        // Not present, insert it
        $query = "
            INSERT INTO `".DBPREFIX."core_setting` (
                `section`, `group`, `name`, `value`,
                `type`, `values`, `ord`
            ) VALUES (
                '".addslashes($this->section)."',
                '".addslashes($group)."',
                '".addslashes($name)."',
                '".addslashes($value)."',
                '".addslashes($type)."',
                '".addslashes($values)."',
                ".intval($ord)."
            )";

        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            \DBG::log("\Cx\Core\Setting\Model\Entity\DbEngine::add(): ERROR: Query failed: $query");
            return false;
        }
        $this->arrSettings[$name] = array(
            'section'   => $this->section,
            'group'     => $group,
            'value'     => $value,
            'type'      => $type,
            'values'    => $values,
            'ord'       => $ord,
        );
        return true;
    }

    /**
     * Delete one or more records from the database table
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
        global $objDatabase;
        // Fail if both parameter values are empty
        if (empty($name) && empty($group)) return false;
        $objResult = $objDatabase->Execute("
            DELETE FROM `".DBPREFIX."core_setting`
             WHERE 1".
            ($name ? " AND `name`='".addslashes($name)."'" : '').
            ($group  ? " AND `group`='".addslashes($group)."'"   : ''));
        if (!$objResult) return $this->errorHandler();
        $this->flush();
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
        global $objDatabase;

        if (empty($this->section)) {
            // TODO: Error message
            return false;
        }
        $objResult = $objDatabase->Execute("
            DELETE FROM `".DBPREFIX."core_setting`
             WHERE `section`='".$this->section."'");
        if (!$objResult) return $this->errorHandler();
        return true;
    }

    /**
     * Should be called whenever there's a problem with the settings table
     *
     * Tries to fix or recreate the settings table.
     * @return  boolean             False, always.
     *
     */
    function errorHandler()
    {
        $table_name = DBPREFIX.'core_setting';
        $table_structure = array(
            'section' => array('type' => 'VARCHAR(32)', 'default' => '', 'primary' => true),
            'name' => array('type' => 'VARCHAR(255)', 'default' => '', 'primary' => true),
            'group' => array('type' => 'VARCHAR(32)', 'default' => '', 'primary' => true),
            'type' => array('type' => 'VARCHAR(32)', 'default' => 'text'),
            'value' => array('type' => 'TEXT', 'default' => ''),
            'values' => array('type' => 'TEXT', 'notnull' => true, 'default' => null),
            'ord' => array('type' => 'INT(10)', 'unsigned' => true, 'default' => '0'),
        );
        // TODO: The index array structure is wrong here!
        $table_index =  array();
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);
        //echo("$this->errorHandler(): Created table ".DBPREFIX."core_setting<br />");

        //Use $this->add(); in your module code to add settings; example:
        //$this->init('core', 'country');
        //$this->add('numof_countries_per_page_backend', 30, 1, $this->TYPE_TEXT);

        //More to come...

        //Always!
        return false;
    }

    public function getArray($section, $group = null)
    {
        $groupArray = array();
        if ($group !== null && $this->section == $section) {
            foreach ($this->arrSettings as $key => $value) {
                if ($value['group'] == $group) {
                    $groupArray[$key] = $value;
                }
            }
        } else {
            $groupArray = $this->arrSettings;
        }
        return $groupArray;
    }
}

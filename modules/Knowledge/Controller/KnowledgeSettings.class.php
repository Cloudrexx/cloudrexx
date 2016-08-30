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
 * This file holds the settings object for the knowledge module
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * The settings of the knowledge module
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */
class KnowledgeSettings
{
    /**
     * The settings
     *
     * @var array
     */
    private $settings = array();

    /**
     * The name of the settings table
     *
     * @var string
     */
    private $table = "";

    /**
     * Read the settings
     */
    public function __construct()
    {
        $this->table = "module_knowledge_".MODULE_INDEX."settings";

        $this->readSettings();
    }

    /**
     * Get all settings
     *
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function readSettings()
    {
        global $objDatabase;

        $query = "  SELECT name, value
                    FROM ".DBPREFIX.$this->table;

        $rs = $objDatabase->Execute($query);
        if ($rs === false) {
            throw new DatabaseError("failed to get settings");
        }

        foreach ($rs as $setting) {
            $this->settings[$setting['name']] = $setting['value'];
        }
    }

    /**
     * Return a value
     *
     * @param string $what
     * @return string
     */
    public function get($what)
    {
        return $this->settings[$what];
    }

    /**
     * Return all settings
     * @return array
     */
    public function getAll()
    {
        return $this->settings;
    }

    /**
     * Set a value
     *
     * If the value doesn't exist yet, create it
     * @param string $what
     * @param string $value
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function set($what, $value)
    {
        global $objDatabase;

        $what = contrexx_addslashes($what);
        $value = contrexx_addslashes($value);

        if (!isset($this->settings[$what])) {
            $query = "  INSERT INTO ".DBPREFIX.$this->table."
                        (name, value)
                        VALUES
                        ('".$what."', '".$value."')";
        } else {
            $query = "  UPDATE ".DBPREFIX.$this->table."
                        SET value = '".$value."'
                        WHERE name = '".$what."'";
        }
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("");
        }
    }

    /**
     * Format the templates
     *
     * Replace the [[ ]] placeholder with {}
     * @param string $template
     * @return string
     */
    public function formatTemplate($template)
    {
        return preg_replace("/\[\[([A-Z_]+)\]\]/", '{$1}', $template);
    }
}

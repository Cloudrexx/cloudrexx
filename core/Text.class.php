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
 * Text (core version)
 *
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */

/**
 * Text
 *
 * Includes access methods and data layer.
 * Do not, I repeat, do not mess with protected fields, or even try
 * to access the database directly (unless you know what you are doing,
 * but you most probably don't).
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */
class Text
{
    /**
     * @var     integer         $id                 The object ID
     */
    protected $id = null;

    /**
     * @var     integer         $lang_id            The language ID
     */
    protected $lang_id = null;

    /**
     * @var     string          $section            The optional section
     */
    protected $section = null;

    /**
     * @var     string          $key                The optional key
     */
    protected $key = null;

    /**
     * @var     string          $text               The content
     */
    protected $text = null;

    /**
     * @var     boolean         $replacement        True if a replacement
     *                                              language was used
     */
    protected $replacement = null;


    /**
     * Create a Text object
     *
     * @access  public
     * @param   string      $text             The content
     * @param   integer     $lang_id          The language ID
     * @param   string      $section          The section
     * @param   string      $key              The key
     * @param   integer     $id               The optional Text ID.
     *                                        Defaults to null
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function __construct($text, $lang_id, $section, $key, $id=null)
    {
        $this->text = $text;
        $this->lang_id = $lang_id;
        $this->section = $section;
        $this->key = $key;
        $this->id = $id;
    }


    /**
     * Get the ID
     * @return  integer                             The Text ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function id()
    {
        return $this->id;
    }
    /**
     * Set the ID -- NOT ALLOWED
     * See {@link makeClone()}
     */

    /**
     * Returns the language ID
     *
     * Optionally sets the language ID.  Returns null on invalid values.
     * @param   integer         $lang_id            The optional language ID
     * @return  integer                             The language ID, or null
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function lang_id($lang_id=null)
    {
        if (isset($lang_id)) {
            $lang_id = intval($lang_id);
            if ($lang_id <= 0) return null;
            $this->lang_id = $lang_id;
        }
        return $this->lang_id;
    }

    /**
     * Returns the section
     * @return  string                              The section
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function section()
    {
        return $this->section;
    }

    /**
     * Returns the key
     * @return  string                              The key
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function key()
    {
        return $this->key;
    }

    /**
     * Returns the text
     *
     * Optionally sets the text.
     * The string value is used as-is, but is ignored if null.
     * Nothing is checked, trimmed nor stripped.  Mind your step!
     * Note: This method cannot be called text() for obscure reasons. :)
     * @param   string          $text               The optional text
     * @return  string                              The text
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function content($text=null)
    {
        if (isset($text)) {
            $this->text = $text;
        }
        return $this->text;
    }


    /**
     * Clone the object
     *
     * Note that this does NOT create a copy in any way, but simply clears
     * the Text ID.  Upon storing this Text, a new ID is created.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function makeClone()
    {
        $this->id = 0;
    }


    /**
     * Delete a record from the database.
     *
     * Deletes the Text record in the selected language, if not empty.
     * If the optional $lang_id parameter is missing or empty, all
     * languages are removed.
     * Note that you *SHOULD NOT* call this from outside the module
     * classes as all of these *SHOULD* take care of cleaning up by
     * themselves.
     * Remark:  See {@link deleteLanguage()} for details on nuking entire
     * sections.
     * @static
     * @param   integer   $id             The ID
     * @param   string    $section        The section
     * @param   string    $key            The key
     * @param   integer   $lang_id        The optional language ID
     * @return  boolean                   True on success, false otherwise
     * @global  mixed     $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteById($id, $section, $key, $lang_id=null)
    {
        $objText = self::getById($id, $section, $key, $lang_id);
        if (!$objText) return true;
        return $objText->delete(empty($lang_id));
    }


    /**
     * Delete all Text with the given language ID from the database.
     *
     * This is dangerous stuff -- mind your step!
     * @static
     * @global  mixed       $objDatabase    Database object
     * @param   integer     $lang_id        The language ID
     * @return  boolean                     True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteLanguage($lang_id)
    {
        global $objDatabase;

        if (!$lang_id) return false;
        $query = "DELETE FROM `".DBPREFIX."core_text` WHERE `lang_id`=$lang_id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        return true;
    }


    /**
     * Test whether a record of this is already present in the database.
     * @return  boolean                     True if the record exists,
     *                                      false otherwise
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function recordExists()
    {
        global $objDatabase;

        $query = "
            SELECT 1
              FROM `".DBPREFIX."core_text`
             WHERE `id`=$this->id
               AND `lang_id`=$this->lang_id
               AND `section`=".(isset($this->section)
            ? "'".addslashes($this->section)."'" : 'NULL')."
               AND `key`='".addslashes($this->key)."'";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        return (boolean)$objResult->RecordCount();
    }


    /**
     * Stores the object in the database.
     *
     * Either updates or inserts the object, depending on the outcome
     * of the call to {@link recordExists()}.
     * Calling {@link recordExists()} is necessary, as there may be
     * no record in the current language, although the Text ID is valid.
     * @return  boolean     True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function store()
    {
        if ($this->id && $this->recordExists()) {
            return $this->update();
        }
        return $this->insert();
    }


    /**
     * Update this object in the database.
     * @return  boolean                     True on success, false otherwise
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function update()
    {
        global $objDatabase;

//echo("Text::update(): ".var_export($this, true)."<br />");

        $query = "
            UPDATE `".DBPREFIX."core_text`
               SET `text`='".contrexx_raw2db($this->text)."'
             WHERE `id`=$this->id
               AND `lang_id`=$this->lang_id
               AND `section`=".
            ($this->section
                ? "'".contrexx_raw2db($this->section)."'" : 'NULL')."
               AND `key`='".contrexx_raw2db($this->key)."'";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
DBG::log("Text::update(): Failed to update ".var_export($this, true));
            return self::errorHandler();
        }
        return true;
    }


    /**
     * Insert this object into the database.
     *
     * Fails if either the ID or lang_id is empty.
     * @return  boolean                     True on success, false otherwise
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function insert()
    {
        global $objDatabase;

        if (empty($this->id)) {
DBG::log("Text::insert(): Invalid ID ".var_export($this, true));
            return false;
        }
        if (empty($this->lang_id)) {
DBG::log("Text::insert(): Invalid language ID ".var_export($this, true));
            return false;
        }
        if (empty($this->key)) {
DBG::log("Text::insert(): Invalid key ".var_export($this, true));
            return false;
        }
        $query = "
            INSERT INTO `".DBPREFIX."core_text` (
                `id`, `lang_id`, `section`, `key`, `text`
            ) VALUES (
                $this->id, $this->lang_id, ".
            (isset($this->section)
                ? "'".contrexx_raw2db($this->section)."'" : 'NULL').",
                '".contrexx_raw2db($this->key)."',
                '".contrexx_raw2db($this->text)."'
            )";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
DBG::log("Text::insert(): Failed to insert ".var_export($this, true));
            return self::errorHandler();
        }
        return true;
    }


    /**
     * OBSOLETE -- see {@see replace()}
     * Add a new Text object directly to the database table
     *
     * Mind that this method uses the MODULE_ID global constant.
     * This is but a handy shortcut for those in the know.
     * @param   string    $text       The text string
     * @param   string    $key        The key
     * @param   string    $text       The optional language ID,
     *                                defaults to FRONTEND_LANG_ID
     * @return  integer               The object ID on success, false otherwise
     */
    static function add($text, $key, $lang_id=0)
    {
      ++$text;
      ++$key;
      ++$lang_id;
die("Obsolete method Text::add() called");
/*
        if (empty($lang_id)) $lang_id = FRONTEND_LANG_ID;
        $objText = new Text($text, $lang_id, MODULE_ID, $key);
        if ($objText->store()) return $objText->getId();
        return false;
*/
    }


    /**
     * OBSOLETE
     * Returns the next available ID
     *
     * Called by {@link insert()}.
     * @return  integer               The next ID on success, false otherwise
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function nextId()
    {
DBG::log("Text::nextId(): Obsolete method called");
/*
        global $objDatabase;

        $query = "SELECT MAX(`id`) AS `id` FROM `".DBPREFIX."core_text`";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) return self::errorHandler();
        // This will also work for an empty database table,
        // when mySQL merrily returns NULL in the ID field.
        return 1+$objResult->fields['id'];
*/
    }


    /**
     * Select an object by ID from the database.
     *
     * Note that if the $lang_id parameter is empty, this method picks the
     * first language of the Text that it encounters.  This is useful
     * for displaying records in languages which haven't been edited yet.
     * If the Text cannot be found for the language ID given, the first
     * language encountered is returned.
     * If no record is found for the given ID, creates a new object
     * with an empty string content, and returns it.
     * @static
     * @param   integer     $id             The ID
     * @param   string      $section        The section, may be null
     * @param   string      $key            The key
     * @param   integer     $lang_id        The optional language ID
     * @return  Text                        The Text
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getById($id, $section, $key, $lang_id=null)
    {
        global $objDatabase; //, $_CORELANG;

        $objText = false;
        if (intval($id)) {
            $query = "
                SELECT `lang_id`, `text`
                  FROM `".DBPREFIX."core_text`
                 WHERE `id`=$id
                   AND `section`".
                ($section
                    ? "='".addslashes($section)."'" : ' IS NULL')."
                   AND `key`='".addslashes($key)."'".
                ($lang_id
                    ? " AND `lang_id`=$lang_id" : '');
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) return self::errorHandler();
            if ($objResult->RecordCount()) {
                $objText = new Text(
                    $objResult->fields['text'],
                    $objResult->fields['lang_id'],
                    $section, $key, $id);
// Optionally mark replacement language Texts
//                if (!$lang_id) $objText->markDifferentLanguage();
            } else {
                if ($lang_id) {
                    $objText = self::getById($id, $section, $key);
                    if ($objText) {
                        $objText->replacement = true;
                        $objText->lang_id = $lang_id;
                    }
                }
            }
        }
        if (!$objText) {
//echo("Text::getById($id, $lang_id): Missing text ID $id, returning new<br />");
            $objText = new Text(
                '', /*$_CORELANG['TXT_CORE_TEXT_MISSING']*/
                $lang_id, $section, $key, $id);
        }
        return $objText;
    }


    /**
     * OBSOLETE
     * Select an object by its key from the database
     *
     * This method is intended to provide a means to store arbitrary
     * texts in various languages that don't need to be referred to by
     * an ID, but some distinct key.  If the key is not unique, however,
     * you will not be able to retrieve any particular of those records,
     * but only the first one that is encountered.
     * Note that if the $lang_id parameter is zero, this method picks the
     * first language of the Text that it encounters.  This is useful
     * for displaying records in languages which haven't been edited yet.
     * If the Text cannot be found for the language ID given, the first
     * language encountered is returned.
     * If no record is found for the given key, creates a new object
     * with a warning message and returns it.
     * Note that in the last case, neither the module nor the text ID
     * are set and remain at their default (null) value.  You should
     * set them to the desired values before storing the object.
     * @static
     * @param   integer     $key            The key, must not be empty
     * @param   integer     $lang_id        The language ID
     * @return  Text                        The object on success,
     *                                      false otherwise
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getByKey($key, $lang_id)
    {
        ++$key;
        ++$lang_id;
die("Obsolete method Text::getByKey() called");
/*
        global $objDatabase, $_CORELANG;

        if (empty($key)) return false;
        $query = "
            SELECT `id`, `lang_id`, `text`, `section`, `key`
              FROM `".DBPREFIX."core_text`
             WHERE `key`='".addslashes($key)."'
               ".($lang_id ? "AND `lang_id`=$lang_id" : '');
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        if ($objResult->RecordCount() == 0) {
            if ($lang_id) {
                $objText = self::getByKey($key, 0);
                if ($objText) {
                    $objText->lang_id = $lang_id;
// Mark Text not present in the selected language
//                    $objText->markDifferentLanguage($lang_id);
                    return $objText;
                }
            }
            // Return "* Text missing *".
            // In order to avoid this being shown, check either
            // the id of the object returned, which is non-empty
            // for any valid Text.
            return new Text(
                $_CORELANG['TXT_CORE_TEXT_MISSING'],
                $lang_id, $section, $key, null
            );
        }
        $objText = new Text(
            $objResult->fields['text'],
            $objResult->fields['lang_id'],
            $objResult->fields['section'],
            $objResult->fields['key'],
            $objResult->fields['id']
        );
        return $objText;
*/
    }


    /**
     * Replace or insert the Text record
     *
     * If the Text ID is specified, looks for the same record in the
     * given language, or any other language if that is not found.
     * If no record to update is found, a new one is created.
     * The parameters are applied, and the Text is then stored.
     * @param   integer     $id             The Text ID
     * @param   integer     $lang_id        The language ID
     * @param   integer     $section        The section
     * @param   string      $key            The key
     * @param   string      $strText        The text
     * @return  integer                     The Text ID on success,
     *                                      null otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function replace($id, $lang_id, $section, $key, $strText)
    {
//DBG::log("Text::replace($id, $lang_id, $section, $key, $strText): Entered");

        $objText = Text::getById($id, $section, $key, $lang_id);
//echo("replace($id, $lang_id, $strText, $section, $key): got by ID: ".$objText->content()."<br />");
//        if (!$objText) {
//            $objText = new Text('', 0, $section, $key, $id);
//        }
        $objText->content($strText);
        // The language may be empty!
        $objText->lang_id($lang_id);

        if ($section == 'Shop' && !preg_match('/^core_mail.*$/i', $key)) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'TmpShopText:Replace',
                array(array($id, $key, $strText))
            );
        }

//DBG::log("Text::replace($id, $lang_id, $section, $key, $strText): Storing ".var_export($objText, true));
        if (!$objText->store()) {
DBG::log("Text::replace($id, $lang_id, $section, $key, $strText): Error: failed to store Text");
            return null;
        }
        return $objText->id;
    }


    /**
     * Deletes the Text record from the database
     *
     * If the optional $all_languages parameter evaluates to boolean true,
     * all records with the same ID are deleted.  Otherwise, only the
     * language of this object is affected.
     * @param   boolean   $all_languages    Delete all languages if true
     * @return  boolean                     True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function delete($all_languages=false)
    {
        global $objDatabase;

        if (empty($this->id)
         || (!$all_languages && empty($this->lang_id))
         || empty($this->key)) {
DBG::log("Text::delete($all_languages): ERROR: Empty ID ($this->id), lang_id ($this->lang_id), or key ($this->key)");
            return false;
        }
        if ($this->section == 'Shop' && !preg_match("/^core_mail.*$/i", $this->key)) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getEvents()->triggerEvent(
                'TmpShopText:Delete',
                array(array($this->id, $this->key))
            );
        }
        $query = "
            DELETE FROM `".DBPREFIX."core_text`
             WHERE `id`=$this->id".
            ($all_languages
                ? '' : " AND `lang_id`=$this->lang_id")."
               AND `section`=".
            (isset($this->section)
                ? "'".addslashes($this->section)."'" : 'NULL')."
               AND `key`='".addslashes($this->key)."'";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
DBG::log("Text::delete($all_languages): ERROR: Query failed: $query");
            return self::errorHandler();
        }
//DBG::log("Text::delete($all_languages): Successfully deleted ID ($this->id), lang_id ($this->lang_id), key ($this->key)");
        return true;
    }


    /**
     * Deletes the Text records with the given section and key from the database
     *
     * Use with due care!
     * If you entirely omit the key, or set it to null, no change will take
     * place.  Explicitly set it to the empty string, zero, or false to have
     * it ignored in the process.  In that case, the complete section will
     * be deleted!
     * If you set $section to null, "global" entries are affected, where
     * the section field is indeed NULL.
     * @param   string    $section          The section
     * @param   string    $key              The key to match
     * @return  boolean                     True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteByKey($section, $key)
    {
        global $objDatabase;

        if (!isset($key)) {
DBG::log("Text::deleteByKey($section, $key): WARNING: Unset key, skipped operation!");
            return false;
        }
        $query = "
            DELETE FROM `".DBPREFIX."core_text`
             WHERE `section`".
            (isset($section) ? "='".addslashes($section)."'" : ' IS NULL').
            ($key ? " AND `key`='".addslashes($key)."'" : '');
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        return true;
    }


    /**
     * Returns an array of SQL snippets to include the selected Text records
     * in the query.
     *
     * Provide a single value for the $key, or an array.
     * If you use an array, the array keys *MUST* contain distinct alias names
     * for the respective text keys.
     * The array returned looks as follows:
     *  array(
     *    'alias' => The array of Text field aliases:
     *                array(key => field name alias, ...)
     *               Use the alias to access the text content in the resulting
     *               recordset, or if you need to sort the result by that
     *               column.
     *    'field' => Field snippet to be included in the SQL SELECT, uses
     *               aliased field names for the id ("text_#_id") and text
     *               ("text_#_text") fields.
     *               No leading comma is included!
     *    'join'  => SQL JOIN snippet, the LEFT JOIN with the core_text table
     *               and conditions
     *  )
     * The '#' is replaced by a unique integer number.
     * The '*' may be any descriptive part of the name that disambiguates
     * multiple foreign keys in a single table, like 'name', or 'value'.
     * Note that the $lang_id parameter is mandatory and *MUST NOT* be
     * emtpy.  $alias may be null (or omitted), in which case it is ignored,
     * and the default form "text_<index>" is used, where <index> is an integer
     * incremented on each use.
     * @static
     * @param   string      $field_id   The name of the text ID
     *                                  foreign key field.  Note that this
     *                                  is not part of the SELECTed fields,
     *                                  but used in the JOIN only.
     * @param   integer     $lang_id    The language ID
     * @param   string      $section    The section
     * @param   mixed       $keys       A single key, or an array thereof
     * @return  array                   The array with SQL code parts
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getSqlSnippets($field_id, $lang_id, $section, $keys)
    {
        static $table_alias_index = 0;

        if (empty($field_id)) {
DBG::log("Text::getSqlSnippets(): ERROR: Empty field ID");
            return false;
        }
        if (empty($lang_id)) {
DBG::log("Text::getSqlSnippets(): ERROR: Empty language ID");
            return false;
        }
        if (empty($section)) {
DBG::log("Text::getSqlSnippets(): ERROR: Empty section");
            return false;
        }
        if (empty($keys)) {
DBG::log("Text::getSqlSnippets(): ERROR: Empty keys");
            return false;
        }
        if (!is_array($keys)) $keys = array($keys);
        $query_field = '';
        $query_join = '';
        $arrSql = array(
            'alias' => array(),
        );
        foreach ($keys as $alias => $key) {
            $table_alias = 'text_'.++$table_alias_index;
            $field_id_alias = $table_alias.'_id';
            $field_text_alias = ($alias ? $alias : $table_alias.'_text');
            $field_text_name = "`$table_alias`.`text`";
            $query_field .=
                ($query_field ? ', ' : '')."
                `$table_alias`.`id` AS `$field_id_alias`,
                $field_text_name AS `$field_text_alias`";
            $query_join .= "
                LEFT JOIN `".DBPREFIX."core_text` as `$table_alias`
                  ON `$table_alias`.`id`=$field_id
                 AND `$table_alias`.`lang_id`=$lang_id
                 AND `$table_alias`.`section`".
                (isset($section) ? "='".addslashes($section)."'" : ' IS NULL')."
                 AND `$table_alias`.`key`='".addslashes($key)."'";
            $arrSql['alias'][$alias] = $field_text_name;
        }
        $arrSql['field'] = $query_field;
        $arrSql['join'] = $query_join;
//DBG::log("Text::getSqlSnippets(): field: {$arrSql['field']}");
//DBG::log("Text::getSqlSnippets(): join: {$arrSql['join']}");
        return $arrSql;
    }


    /**
     * OBSOLETE
     * Returns an array of objects selected by language ID, section, and key,
     * plus optional text IDs from the database.
     *
     * You may multiply the $lang_id parameter with -1 to get a negative value,
     * in which case this method behaves very much like {@link getById()} and
     * returns other languages or a warning if the language with the same
     * positive ID is unavailable.  This is intended for backend use only.
     * The array returned looks like this:
     *  array(
     *    text_id => obj_text,
     *    ... more ...
     *  )
     * @static
     * @param   string      $section        The section
     * @param   string      $key            The key
     * @param   integer     $lang_id        The language ID
     * @param   integer     $ids            The optional comma separated
     *                                      list of Text IDs
     * @return  array                       The array of Text objects
     *                                      on success, null otherwise
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getArrayById($lang_id, $section, $key, $ids='')
    {
        ++$lang_id;
        ++$section;
        ++$key;
        ++$ids;
die("Obsolete method Text::getArrayById() called");
/*
        global $objDatabase;

        if (empty($section) || empty($key) || empty($lang_id)) return false;
        $query = "
            SELECT `id`
              FROM `".DBPREFIX."core_text`
             WHERE `section`='".addslashes($section)."'
               AND `key`='".addslashes($key)."'".
               ($lang_id > 0 ? ' AND `lang_id`='.$lang_id          : '').
               ($ids    ? ' AND `text_id` IN ('.$ids.')' : '');
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        $arrText = array();
        $lang_id = abs($lang_id);
        while (!$objResult->EOF) {
            $id = $objResult->fields['id'];
            $objText = self::getById($id, $lang_id);
            if ($objText) $arrText[$id] = $objText;
            $objResult->MoveNext();
        }
        return $arrText;
*/
    }


    /**
     * OBSOLETE
     * Returns an array of Text and language IDs of records matching
     * the search pattern and optional section, key, language, and text IDs
     *
     * Note that you have to add "%" signs to the pattern if you want the
     * match to be open ended.
     * The array returned looks like this:
     *  array(
     *    id => array(
     *      0 => Language ID,
     *      ... 1 => more language IDs ...
     *    ),
     *    ... more ids ...
     *  )
     * @static
     * @param       string      $pattern        The search pattern
     * @param       integer     $section      The optional section, or false
     * @param       string      $key            The optional key, or false
     * @param       integer     $lang_id        The optional language ID, or false
     * @param       integer     $ids       The optional comma separated
     *                                          list of Text IDs, or false
     * @return      array                       The array of Text and language
     *                                          IDs on success, false otherwise
     * @global      mixed       $objDatabase    Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getIdArrayBySearch(
        $pattern, $section=false, $key=false, $lang_id=false, $ids=false
    ) {
        ++$pattern;
        ++$section;
        ++$key;
        ++$lang_id;
        ++$ids;
die("Obsolete method Text::getIdArrayBySearch() called");
/*
        global $objDatabase;

        $query = "
            SELECT `id`, `lang_id`
              FROM `".DBPREFIX."core_text`
             WHERE `text` LIKE '".addslashes($pattern)."'".
            ($lang_id   !== false ? " AND `lang_id`=$lang_id"           : '').
            ($section !== false ? " AND `section`='".addslashes($section)."'" : '').
            ($key       !== false ? " AND `key`='".addslashes($key)."'" : '').
            ($ids  !== false ? " AND `id` IN ($ids)"     : '');
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        $arrId = array();
        while (!$objResult->EOF) {
            $arrId[$objResult->fields['id']][$objResult->fields['lang_id']] = true;
            $objResult->MoveNext();
        }
        return $arrId;
*/
    }


    /**
     * OBSOLETE
     * Substitutes missing text with another language, if available
     *
     * If $text is not null, it is returned unchanged.
     * Otherwise, if $id is valid, searches for any available entry
     * in any language and, if it finds one, sets $text to that.
     * If no substitute is found, $text will still be null.
     * @param   string    $text       The text string, by reference
     * @param   integer   $id    The Text ID
     * @return  void
     * @static
    static function substitute(&$text, $id)
    {
        $id = intval($id);
        if ($id && $text === null) {
            $objText = Text::getById($id, 0);
            if ($objText)
                $objText->markDifferentLanguage();
                $text = $objText->content();
        }
    }
     */


    /**
     * If the language ID given is different from the language of this
     * Text object, the content may be marked here.
     *
     * Customize as desired.
     * @param   integer   $lang_id         The desired language ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function markDifferentLanguage()
    {
// Different formatting -- up to you.
// None
        return;
// or
//        $this->text = '['.$this->text.']';
// or
//        $this->text = $this->text.' *';
// or (not suitable for form element contents)
//        $this->text = '<font color="red">'.$this->text.'</font>';
    }


    /**
     * Handle any error occurring in this class.
     *
     * Tries to fix known problems with the database table.
     * @global  mixed     $objDatabase    Database object
     * @return  boolean                   False.  Always.
     * @throws  Update_DatabaseException
     * @static
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function errorHandler()
    {
        $table_name = DBPREFIX."core_text";
        if (!\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            $query = "
                CREATE TABLE `".DBPREFIX."core_text` (
                  `id` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                  `lang_id` INT(10) UNSIGNED NOT NULL DEFAULT 1,
                  `section` VARCHAR(32) NULL DEFAULT NULL,
                  `key` VARCHAR(255) NOT NULL,
                  `text` TEXT NOT NULL,
                  PRIMARY KEY `id` (`id`, `lang_id`, `section`, `key`(32)),
                  FULLTEXT `text` (`text`)
                ) ENGINE=MyISAM";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);
            if (!$objResult) {
                throw new \Cx\Lib\Update_DatabaseException(
                   'Failed to create Text table', $query);
            }
        }

        // More to come...

        return false;
    }

}

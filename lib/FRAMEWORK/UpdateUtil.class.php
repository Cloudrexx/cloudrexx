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
 * UpdateUtil
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

namespace Cx\Lib;

/**
 * UpdateException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class UpdateException extends \Exception {};


/**
 * Update_DatabaseException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class Update_DatabaseException extends UpdateException {
    public $sql;

    function __construct($message, $sql = null) {
        parent::__construct($message);
        $this->sql = $sql;
    }
}

/**
 * UpdateUtil
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class UpdateUtil
{
    /**
     * Creates or modifies a table to the given specification.
     *
     * @param string name - the name of the table. do not forget DBPREFIX!
     * @param array struc - the structure of the columns. This is an associative
     *     array with the keys being the column names and the values being an array
     *     with the following keys:
     *       array(
     *           'type'            => 'INT', # or VARCHAR(30) or whatever
     *           'notnull'         => true/false, # optional, defaults to true
     *           'auto_increment'  => true/false, # optional, defaults to false
     *           'default'         => 'value',    # optional, defaults to '' (or 0 if type is INT)
     *           'default_expr'    => expression, # use this instead of 'default' to use NOW(), CURRENT_TIMESTAMP etc
     *           'primary'         => true/false, # optional, defaults to false
     *           'renamefrom'      => 'a_name'    # optional. Use this if the column existed previously with another name
     *           'on_update'       => value for ON UPDATE #optional, defaults to none
     *           'on_delete'       => value for ON DELETE #optional, defaults to none
     *       )
     * @param array idx - optional. Additional index specification. This is an associative array
     *     where the keys are index names and the values are arrays with the following
     *     keys:
     *        array(
     *            'fields' => array('field1', 'field2', ..), # field names to be indexed
     *            'type'   => 'UNIQUE/FULLTEXT', # optional. If left out, a normal search index is created
     *            'force'  => true/false,  # optional. forces creation of unique indexes, even if there
     *                                     # are duplicates (which will be dropped). use with care.
     *        )
     * @param string engine - optional. Specification of the DB-Engine to use (i.e. MyISAM, InnoDB, etc)
     *                          Defaults to 'MyISAM'
     * @param string comment - optional. Table comment.
     * @param array constraints - optional. Additional constraints specification. This is an associative array
     *      where the keys represent the foreign keys and the values are arrays defining the constraint on the
     *      foreign keys:
     *          array(
     *              'foreign_key' => array(
     *                  'table'     => 'foreign_table', # table of foreign key constraint
     *                  'column'    => 'foreign_column', # table's column of foreign key constraint
     *                  'onDelete'     => 'CASCADE|SET NULL|NO ACTION|RESTRICT', # constraint action on foreign relation' delete
     *                  'onUpdate'     => 'CASCADE|SET NULL|NO ACTION|RESTRICT', # constraint action on foreign relation' update
     *              ),
     *          )
     */
    public static function table($name, $struc, $idx=array(), $engine='MyISAM',
        $comment='', $constraints = array())
    {
        if (self::table_exist($name)) {
            self::check_columns($name, $struc);
            self::check_indexes($name, $idx, $struc);
            self::check_dbtype($name, $engine);
            self::set_constraints($name, $constraints);
        } else {
            self::create_table($name, $struc, $idx, $engine, $comment);
            self::set_constraints($name, $constraints);
        }
    }

    /**
     * Get constraints of database table
     *
     * @param name table - Name of database table
     * @return array constraints - Constraints definitions. This is an associative array
     *      where the keys represent the foreign keys and the values are arrays defining the constraint on the
     *      foreign keys:
     *          array(
     *              'foreign_key' => array(
     *                  'table'     => 'foreign_table', # table of foreign key constraint
     *                  'column'    => 'foreign_column', # table's column of foreign key constraint
     *                  'onDelete'     => 'CASCADE|SET NULL|NO ACTION|RESTRICT', # constraint action on foreign relation' delete
     *                  'onUpdate'     => 'CASCADE|SET NULL|NO ACTION|RESTRICT', # constraint action on foreign relation' update
     *              ),
     *          )
     */
    public static function get_constraints($name)
    {
        global $objDatabase, $_ARRAYLANG;

        $arrDefinedConstraints = array();

        $objResult = $objDatabase->Execute("SHOW CREATE TABLE `$name`");
        if (!$objResult) {
            self::cry(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], $name));
        }

        $createTableStm = explode("\n", $objResult->fields['Create Table']);
        $arrConstraintDefinitions = preg_grep('/^\s*CONSTRAINT/', $createTableStm);

        foreach ($arrConstraintDefinitions as $constraintDefinition) {
            if (preg_match('/CONSTRAINT\s+
                    # 1. constraint name
                    `([^`]+)`
                    \s+FOREIGN\s+KEY\s+
                    # 2. foreig key
                    \(`([^`]+)`\)
                    \s+REFERENCES\s+
                    # 3. referenced table
                    `([^`]+)`
                    # 4. referenced column
                    \s+\(`([^`]+)`\)
                    # 5. on delete action
                    (?:\s+ON\s+DELETE\s+(CASCADE|SET\s+NULL|NO\s+ACTION|RESTRICT))?
                    # 6. on update action
                    (?:\s+ON\s+UPDATE\s+(CASCADE|SET\s+NULL|NO\s+ACTION|RESTRICT))?
                    /xs', $constraintDefinition, $match)
            ) {
                $onDelete = isset($match[5]) ? $match[5] : 'RESTRICT';
                $onUpdate = isset($match[6]) ? $match[6] : 'RESTRICT';

                $arrDefinedConstraints[$match[2]] = array(
                    'key'       => $match[1],
                    'table'     => $match[3],
                    'column'    => $match[4],
                    'onDelete'  => $onDelete,
                    'onUpdate'  => $onUpdate,
                );
            }
        }

        return $arrDefinedConstraints;
    }

    /**
     * Set constraints to database table
     *
     * @param name table - Name of database table
     * @param array constraints - optional. Constraints specification. This is an associative array
     *      where the keys represent the foreign keys and the values are arrays defining the constraint on the
     *      foreign keys:
     *          array(
     *              'foreign_key' => array(
     *                  'table'     => 'foreign_table', # table of foreign key constraint
     *                  'column'    => 'foreign_column', # table's column of foreign key constraint
     *                  'onDelete'     => 'CASCADE|SET NULL|NO ACTION|RESTRICT', # constraint action on foreign relation' delete
     *                  'onUpdate'     => 'CASCADE|SET NULL|NO ACTION|RESTRICT', # constraint action on foreign relation' update
     *              ),
     *          )
     *      If left empty, all existing constraints will be removed from specified table
     */
    public static function set_constraints($name, $constraints = array())
    {
        $arrDefinedConstraints = self::get_constraints($name);
        foreach ($constraints as $foreignKey => $constraint) {
            $dropForeignKeyStmt = '';

            $onDelete = isset($constraint['onDelete']) ? $constraint['onDelete'] : 'RESTRICT';
            $onUpdate = isset($constraint['onUpdate']) ? $constraint['onUpdate'] : 'RESTRICT';

            if (isset($arrDefinedConstraints[$foreignKey])) {
                if (   $arrDefinedConstraints[$foreignKey]['table'] == $constraint['table']
                    && $arrDefinedConstraints[$foreignKey]['column'] == $constraint['column']
                    && $arrDefinedConstraints[$foreignKey]['onDelete'] == $onDelete
                    && $arrDefinedConstraints[$foreignKey]['onUpdate'] == $onUpdate
                ) {
                    continue;
                }

                $dropForeignKeyStmt = "DROP FOREIGN KEY `".$arrDefinedConstraints[$foreignKey]['key']."`,";
            }

            $query = "ALTER TABLE `".$name."`
                            $dropForeignKeyStmt
                            ADD FOREIGN KEY ( `".$foreignKey."` ) REFERENCES `".$constraint['table']."` ( `".$constraint['column']."`)
                             ON DELETE ".$onDelete."
                             ON UPDATE ".$onUpdate;
            self::sql($query);
        }

        foreach ($arrDefinedConstraints as $foreignKey => $definedConstraint) {
            if (!isset($constraints[$foreignKey])) {
                self::sql("ALTER TABLE `".$name."` DROP FOREIGN KEY `".$definedConstraint['key']."`");
            }
        }
    }


    public static function drop_table($name)
    {
        global $objDatabase;

        if (self::table_exist($name)) {
            $table_stmt = "DROP TABLE `$name`";
            if ($objDatabase->Execute($table_stmt) === false) {
                self::cry($objDatabase->ErrorMsg(), $table_stmt);
            }
        }
    }


    public static function column_exist($name, $col)
    {
        global $objDatabase, $_ARRAYLANG;

        $col_info = $objDatabase->MetaColumns($name);
        if ($col_info === false) {
            throw new UpdateException(sprintf(
                $_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'],
                $name));
        }
        return isset($col_info[strtoupper($col)]);
    }


    public static function check_column_type($name, $col, $type)
    {
        global $objDatabase, $_ARRAYLANG;

        $col_info = $objDatabase->MetaColumns($name);
        if ($col_info === false) {
            throw new UpdateException(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], $name));
        }
        if (!isset($col_info[strtoupper($col)])) {
            throw new UpdateException(sprintf('Column %s does not exist!', $name.'.'.$col));
        }
        return $col_info[strtoupper($col)]->type == $type;
    }


    public static function table_exist($name)
    {
        global $objDatabase, $_ARRAYLANG;

        $tableinfo = $objDatabase->MetaTables();
        if ($tableinfo === false) {
            throw new UpdateException(sprintf(
                $_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'],
                $name));
        }
        return in_array($name, $tableinfo);
    }


    private static function check_dbtype($name, $engine)
    {
        $tableinfo = self::sql("SHOW CREATE TABLE $name");
        $create_stmt = $tableinfo->fields['Create Table'];
        $match = null;
        preg_match('#ENGINE=(\w+)#i', $create_stmt, $match);
        $current_engine = strtoupper($match[1]);
        if (strtoupper($engine) == $current_engine) {
            return;
        }
        // need to change the engine type.
        self::sql("ALTER TABLE `$name` ENGINE=$engine");
    }


    private static function create_table($name, $struc, $idx, $engine,
        $comment='')
    {
        global $_DBCONFIG;

        // create table statement
        $cols = array();
        foreach ($struc as $col => $spec) {
            $cols[] = "`$col` ". self::_colspec($spec, true);
        }
        $colspec    = join(",\n", $cols);
        $primaries  = join("`,\n`", self::_getprimaries($struc));
        $comment    = !empty($comment) ? ' COMMENT="'.$comment.'"' : '';
        $charset    = ' DEFAULT CHARACTER SET '.$_DBCONFIG['charset'].' COLLATE '.$_DBCONFIG['collation'];

        $table_stmt = "CREATE TABLE `$name`(
            $colspec".(!empty($primaries) ? ",
            PRIMARY KEY (`$primaries`)" : '')."
        ) ENGINE=$engine$charset$comment";

        self::sql($table_stmt);
        // index statements. as we just created the table
        // we can now just do check_indexes() to take care
        // of the "problem".
        self::check_indexes($name, $idx);
    }


    /**
     * Rename the table $table_name_old to $table_name_new
     * @param   string  $table_name_old   The current table name
     * @param   string  $table_name_new   The new table name
     * @return  boolean                   True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function table_rename($table_name_old, $table_name_new)
    {
        return (boolean)self::sql(
            "RENAME TABLE `$table_name_old` TO `$table_name_new`");
    }


    /**
     * Returns true if the table is empty
     *
     * If the table cannot be accessed, returns null.
     * Hint: call {@see table_exists} first.
     * @param   string      $table_name     The table name
     * @return  boolean                     True if the table is empty,
     *                                      null on error, or false
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function table_empty($table_name)
    {
        $count = self::record_count($table_name);
        if (is_null($count)) return null;
        return !(boolean)$count;
    }


    /**
     * Returns the record count for the given table name
     *
     * If the table cannot be accessed, returns null.
     * @param   string    $table_name     The table name
     * @return  integer                   The record count on success,
     *                                    null otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function record_count($table_name)
    {
        $objResult = self::sql("
            SELECT COUNT(*) AS `record_count`
              FROM $table_name");
        if (!$objResult) return null;
        return $objResult->fields['record_count'];
    }


    private static function cry($msg, $sql)
    {
        throw new Update_DatabaseException($msg, $sql);
    }


    /**
     * Execute an SQL query
     *
     * Raises an Update_DatabaseException on error.
     * Returns a recordset on success.
     * Note that the recordset may be an empty one.
     * You may provide a query template plus an optional input array as with
     * {@see ADOConnection::Execute()}.
     * @global  ADOConnection   $objDatabase
     * @param   string          $statement      The query string
     * @param   array           $inputarray     The optional query parameters
     * @return  \ADORecordset
     */
    public static function sql($statement, $inputarray=null)
    {
        global $objDatabase;

        # ugly, ugly hack so it does not return Insert_ID when we didn't insert
        $objResult = $objDatabase->Execute($statement, $inputarray);
        if ($objResult === false) {
            self::cry($objDatabase->ErrorMsg(), $statement);
        }
        return $objResult;
    }


    public static function insert($statement)
    {
        global $objDatabase;

        self::sql($statement);
        return $objDatabase->Insert_ID();
    }


    private static function check_columns($name, $struc)
    {
        global $objDatabase, $_ARRAYLANG;

        $col_info = $objDatabase->MetaColumns($name);
        if ($col_info === false) {
            self::cry(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], $name));
        }
        // Create columns that don't exist yet
        foreach ($struc as $col => $spec) {
            if (self::_check_column($name, $col_info, $col, $spec)) {
                // col_info NEEDS to be reloaded, as _check_column() has changed the table
                $col_info = $objDatabase->MetaColumns($name);
                if ($col_info === false) {
                    self::cry(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], $name));
                }
            }
        }
        // Drop columns that are not specified
        self::_drop_unspecified_columns($name, $struc, $col_info);
    }


    private static function _drop_unspecified_columns($name, $struc, $col_info)
    {
        foreach (array_keys($col_info) as $col) {
            // we have to do a stupid loop here as we don't know
            // the exact case of the name in $spec ;(
            $exists = false;
            foreach (array_keys($struc) as $col_exists) {
                if (strtolower($col) == strtolower($col_exists)) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $col_name = $col_info[$col]->name;
                $query = "ALTER TABLE `$name` DROP COLUMN `$col_name`";
                self::sql($query);
            }
        }
    }


    /**
     * Checks the given column and ALTERS what's needed. Returns true
     * if a change has been done.
     */
    private static function _check_column($name, $col_info, $col, $spec)
    {
        if (!isset($col_info[strtoupper($col)])) {
            $colspec = self::_colspec($spec);
            $query = '';
            // check if we need to rename the column
            if (isset($spec['renamefrom']) and isset($col_info[strtoupper($spec['renamefrom'])])) {
                // rename requested and possible.
                $from = $spec['renamefrom'];
                $query = "ALTER TABLE `$name` CHANGE `$from` `$col` $colspec";
            }
            else {
                // rename not possible or not requested. create the new column!
                // TODO: maybe we should somehow notify the caller if
                //       rename was requested but not possible?
                $query = "ALTER TABLE `$name` ADD `$col` $colspec";
            }
            self::sql($query);
            return true;
        }
        $col_spec = $col_info[strtoupper($col)];
        $type = $col_spec->type . (preg_match('@[a-z]+\([0-9]+\)@i', $spec['type']) && $col_spec->max_length > 0 ? "($col_spec->max_length)" : ($col_spec->type == 'enum' ? "(".implode(",", $col_spec->enums).")" : ''));
        $default = isset($spec['default']) ? $spec['default'] : (isset($spec['default_expr']) ? $spec['default_expr'] : '');
        if ($type != strtolower($spec['type'])
            || $col_spec->unsigned != (isset($spec['unsigned']) && $spec['unsigned'])
            || (isset($col_spec->zerofill) && $col_spec->zerofill) != (isset($spec['zerofill']) && $spec['zerofill'])
            || $col_spec->not_null != (!isset($spec['notnull']) || $spec['notnull'])
            || $col_spec->has_default != (isset($spec['default']) || isset($spec['default_expr']))
            || $col_spec->has_default && ($col_spec->default_value != $default)
            || $col_spec->auto_increment != (isset($spec['auto_increment']) && $spec['auto_increment'])
            || isset($spec['on_update'])
            || isset($spec['on_delete'])
        ) {
            $colspec = self::_colspec($spec);
            $query = "ALTER TABLE `$name` CHANGE `$col` `$col` $colspec";
            self::sql($query);
            return true;
        }
        return false;
        // TODO: maybe we should check for the type of the
        // existing column here and adjust it too?
    }


    private static function check_indexes($name, $idx, $struc=null)
    {
        global $objDatabase;

        # mysql> show index from contrexx_access_user_mail;
        $keyinfo = $objDatabase->Execute("SHOW INDEX FROM `$name`");
        if ($keyinfo === false) {
            self::cry($objDatabase->ErrorMsg(), "SHOW INDEX FROM `$name`");
        }

        // Find already existing keys, drop unused keys
        $arr_keys_to_drop = array();
        $arr_primaries = array();
        while (!$keyinfo->EOF) {
            if (isset($idx[$keyinfo->fields['Key_name']])) {
                $idx[$keyinfo->fields['Key_name']]['exists'] = true;
                $keyinfo->MoveNext();
                continue;
            }
            if ($keyinfo->fields['Key_name'] == 'PRIMARY') {
                $arr_primaries[] =
                    $keyinfo->fields['Column_name'].
                    $keyinfo->fields['Sub_part'];
                // primary keys should NOT be dropped :P
                $keyinfo->MoveNext();
                continue;
            }
            $arr_keys_to_drop[] = $keyinfo->fields['Key_name'];
            $keyinfo->MoveNext();
        }

        if ($struc) {
            $new_primaries = self::_getprimaries($struc);
            // recreate the primary key in case it changed
            if (count(array_diff($new_primaries, $arr_primaries))
             || count(array_diff($arr_primaries, $new_primaries))) {
                // delete current primary key, in case there is one
                if (count($arr_primaries)) {
                    $drop_st = "ALTER TABLE `$name` DROP PRIMARY KEY";
                    self::sql($drop_st);
                }
                // add new primary key, in case one is defined
                if (count($new_primaries)) {
                    // Properly handle key lengths
                    $match = null;
                    foreach ($new_primaries as &$primary) {
                        if (preg_match('/(\w+)(\(\d+\))?/', $primary, $match)) {
                            $primary =
                                '`'.$match[1].'`'.
                                (empty($match[2]) ? '' : $match[2]);
                        }
                    }
                    $new_st =
                        "ALTER TABLE `$name` ADD PRIMARY KEY (".
                        join(', ', $new_primaries).')';
                    self::sql($new_st);
                }
            }
        }

        // drop obsolete keys
        if (count($arr_keys_to_drop)) {
            foreach ($arr_keys_to_drop as $key) {
                $drop_st = self::_dropkey($name, $key);
                self::sql($drop_st);
            }
        }

        // create new keys
        if (is_array($idx)) {
            foreach ($idx as $keyname => $spec) {
                if (!array_key_exists('exists', $spec)) {
                    $new_st = self::_keyspec($name, $keyname, $spec);
                    self::sql($new_st);
                }
            }
        }
        // okay, that's it, have a nice day!
    }


    private static function _dropkey($table, $name)
    {
        return "ALTER TABLE `$table` DROP INDEX `$name`";
    }


    private static function _keyspec($table, $name, $spec)
    {
        $arrFields = array();
        foreach ($spec['fields'] as $fieldInfo1 => $fieldInfo2) {
            if (intval($fieldInfo1) !== $fieldInfo1) {
                $arrFields[] = '`'.$fieldInfo1.'`('.$fieldInfo2.')';
            } else {
                $arrFields[] = '`'.$fieldInfo2.'`';
            }
        }
        $fields = join(',', $arrFields);
        $type = array_key_exists('type', $spec) ? $spec['type'] : '';
        $descr = null;
        if (isset($spec['force']) && $spec['force']) {
//            $descr = "ALTER IGNORE TABLE `$table` ADD $type INDEX `$name` ($fields)";
            $descr = "ALTER IGNORE TABLE `$table` ADD $type KEY `$name` ($fields)";
        } else {
// TODO "INDEX" instead of "KEY" produces an error for type "PRIMARY"? -- RK
  //          $descr  = "ALTER TABLE `$table` ADD $type INDEX `$name` ($fields)";
            $descr  = "ALTER TABLE `$table` ADD $type KEY `$name` ($fields)";
        }
        return $descr;
    }


    private static function _colspec($spec, $create_tbl_operation=false)
    {
        $unsigned     = (array_key_exists('unsigned',       $spec)) ? $spec['unsigned']       : false;
        $zerofill     = (array_key_exists('zerofill',       $spec)) ? $spec['zerofill']       : false;
        $notnull      = (array_key_exists('notnull',        $spec)) ? $spec['notnull']        : true;
        $autoinc      = (array_key_exists('auto_increment', $spec)) ? $spec['auto_increment'] : false;
        $default_expr = (array_key_exists('default_expr',   $spec)) ? $spec['default_expr']   : '';
//        $default      = (array_key_exists('default',        $spec)) ? $spec['default']        : null;
        // Allow "NULL" as a default value!
        $default = (array_key_exists('default', $spec)
            ? (is_null($spec['default'])
                ? 'NULL' : "'".addslashes($spec['default'])."'")
            : null);
        $binary       = (array_key_exists('binary',         $spec)) ? $spec['binary']         : null;
        $on_update    = (array_key_exists('on_update',      $spec)) ? $spec['on_update']      : null;
        $on_delete    = (array_key_exists('on_delete',      $spec)) ? $spec['on_delete']      : null;
        $after = false;
        if (!$create_tbl_operation) {
            $after    = (array_key_exists('after',          $spec)) ? $spec['after']          : false;
        }
        $default_st = '';
        if (strtoupper($spec['type']) != 'BLOB'
         && strtoupper($spec['type']) != 'TEXT'
         && strtoupper($spec['type']) != 'TINYTEXT') {
            // BLOB/TEXT can't have a default value... sez MySQL
            if (isset($default)) {
                $default_st = " DEFAULT $default";
            } elseif ($default_expr != '') {
                $default_st = " DEFAULT $default_expr";
            }
        }
        $descr  = $spec['type'];
        $descr .= $binary ? " BINARY" : '';
        $descr .= $unsigned ? " unsigned"      : '';
        $descr .= $zerofill ? " zerofill"      : '';
        $descr .= $notnull ? " NOT NULL"       : ' NULL';
        $descr .= $autoinc ? " auto_increment" : '';
        $descr .= $default_st;
        $descr .= $on_update ? " ON UPDATE ".$on_update : '';
        $descr .= $on_delete ? " ON DELETE ".$on_delete : '';
        $descr .= $after ? " AFTER `".$after."`" : '';
        return $descr;
    }


    /**
     * Picks the primary key names (and optional key lengths) from the
     * field specifications
     *
     * If the "primary" element value is numeric, it is assumed to be the
     * length of the key, and appended to the name in parentheses.
     * @param   array   $struc      The field specification array
     * @return  array               The array of primary key names
     */
    private static function _getprimaries($struc)
    {
        $primaries = array();
        foreach ($struc as $name => $spec) {
            $is_primary = (array_key_exists('primary', $spec)) ? $spec['primary'] : false;
// Primary with key length, as with the separate key specs.
// -> Use separate key specs in that case.
//            if (is_numeric($is_primary)) {
//                // Like "'type' => 'TEXT', 'primary' => 32, ..."
//                $primaries[] = "$name($is_primary)";
//            } else
            if ($is_primary) {
                // Like "'type' => 'INT(10)', 'primary' => true, ..."
                $key = '';
                if (is_numeric($is_primary)) {
                    $key = '(' . intval($is_primary) . ')';
                }
                $primaries[] = $name . $key;
            }
        }
        return $primaries;
    }

    /**
     * Replace certain strings in a content page
     *
     * This method will replace $search with $replace in the content page(s)
     * specified by the module ID $moduleId and CMD $cmd.
     * If $cmd is set to NULL, the replacement will be done on every content
     * page of the specified module.
     * $search and $replace can either be a single string or an array of strings.
     * $changeVersion specifies the Cloudrexx version in which the replacement
     * should take place. Latter means that the replace will only be done if
     * the installed Cloudrexx version is older than the one specified by
     * $changeVersion.
     *
     * @global  ContrexxUpdate     $objUpdate
     * @global  Array               $_CONFIG
     * @param   integer             $module           Module
     * @param   string    $cmd        CMD
     * @param   mixed     $search     Search string or array of strings
     * @param   mixed     $replace    Replacement string or array of strings
     * @param   string    $changeVersion  Cloudrexx version of the content page
     */
    public static function migrateContentPage($module, $cmd, $search, $replace, $changeVersion)
    {
        global $objUpdate, $_CONFIG;

        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], $changeVersion)) {
            $em = \Env::get('em');
            $allPages = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page')->getAllFromModuleCmdByLang($module, $cmd);
            foreach ($allPages as $lang => $pages) {
                foreach ($pages as $page) {
                    if ($page) {
                        $page->setContent(str_replace($search, $replace, $page->getContent()));
                        $page->setUpdatedAtToNow();
                        $em->persist($page);
                    }
                }
            }
            $em->flush();
        }
    }

    /**
     * Replace certain data of a content page using regexp
     *
     * This method will do a preg_replace() on pages (filtered by $criteria) data specified by $subject using $pattern as PATTERN and $replacement as REPLACEMENT.
     * Subject is either a string or an array referencing attributes of a page.
     * The behaviour of $pattern and $replacement is exactly the same as implemented by preg_replace().
     * $changeVersion specifies the Cloudrexx version in which the replacement
     * should take place. Latter means that the replace will only be done if
     * the installed Cloudrexx version is older than the one specified by
     * $changeVersion.
     *
     * @global  ContrexxUpdate     $objUpdate
     * @global  Array               $_CONFIG
     * @param   Array               $criteria         Argument list to filter page objects. Will be passed to Cx\Core\ContentManager\Model\Repository\PageRepository->findBy()
     * @param   mixed               $pattern          The pattern to search for. It can be either a string or an array with strings.
     * @param   mixed               $replacement      The string or an array with strings (pattern) to replace
     * @param   mixed               $subject          A string or array containing the name of an attribute of the page object
     * @param   string              $changeVersion    Cloudrexx version of the content page
     */
    public static function migrateContentPageUsingRegex($criteria, $pattern, $replacement, $subject, $changeVersion)
    {
        global $objUpdate, $_CONFIG;

        if (!is_array($subject)) {
            $subject = array($subject);
        }

        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], $changeVersion)) {
            $em = \Env::get('em');
            $pages = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page')->findBy($criteria, null, null, null, true);
            foreach ($pages as $page) {
                if ($page) {
                    if (!checkMemoryLimit()) {
                        throw new UpdateException();
                    }
                    foreach ($subject as $pageAttribute) {
                        try {
                            // fetch attribute value
                            $pageAttributeValue = call_user_func(array($page, "get".ucfirst($pageAttribute)));

                            // apply replace on attribute
                            $pageAttributeValueChanged = preg_replace($pattern, $replacement, $pageAttributeValue);

                            if (   $pageAttributeValueChanged !== null
                                && $pageAttributeValueChanged != $pageAttributeValue
                            ) {
                                call_user_func(array($page, "set".ucfirst($pageAttribute)), $pageAttributeValueChanged);
                                $page->setUpdatedAtToNow();
                                $em->persist($page);
                            }
                        }
                        catch (\Exception $e) {
                            \DBG::log("Migrating page failed: ".$e->getMessage());
                            throw new UpdateException('Bei der Migration einer Inhaltsseite trat ein Fehler auf! '.$e->getMessage());
                        }
                    }
                }
            }
            $em->flush();
        }
    }

    /**
     * Replace content using preg_replace_callback()
     * @todo    Add proper docblock
     */
    public static function migrateContentPageUsingRegexCallback($criteria, $pattern, $callback, $subject, $changeVersion)
    {
        global $objUpdate, $_CONFIG;

        if (!is_array($subject)) {
            $subject = array($subject);
        }

        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], $changeVersion)) {
            $em = \Env::get('em');
            $pages = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page')->findBy($criteria, null, null, null, true);
            foreach ($pages as $page) {
                if ($page) {
                    if (!checkMemoryLimit()) {
                        throw new UpdateException();
                    }
                    foreach ($subject as $pageAttribute) {
                        try {
                            // fetch attribute value
                            $pageAttributeValue = call_user_func(array($page, "get".ucfirst($pageAttribute)));

                            // apply replace on attribute
                            $pageAttributeValueChanged = preg_replace_callback($pattern, $callback, $pageAttributeValue);

                            if (   $pageAttributeValueChanged !== null
                                && $pageAttributeValueChanged != $pageAttributeValue
                            ) {
                                call_user_func(array($page, "set".ucfirst($pageAttribute)), $pageAttributeValueChanged);
                                $page->setUpdatedAtToNow();
                                $em->persist($page);
                            }
                        }
                        catch (\Exception $e) {
                            \DBG::log("Migrating page failed: ".$e->getMessage());
                            throw new UpdateException('Bei der Migration einer Inhaltsseite trat ein Fehler auf! '.$e->getMessage());
                        }
                    }
                }
            }
            $em->flush();
        }
    }

    public static function setSourceModeOnContentPage($criteria, $changeVersion)
    {
        global $objUpdate, $_CONFIG;

        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], $changeVersion)) {
            $em = \Env::get('em');
            $pages = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page')->findBy($criteria, null, null, null, true);
            foreach ($pages as $page) {
                if ($page) {
                    if (!checkMemoryLimit()) {
                        throw new UpdateException();
                    }
                    try {
                        // set source mode to content page
                        $page->setSourceMode(true);
                        $page->setUpdatedAtToNow();
                        $em->persist($page);
                    }
                    catch (\Exception $e) {
                        \DBG::log("Setting source mode to page failed: ".$e->getMessage());
                        throw new UpdateException('Bei der Migration einer Inhaltsseite trat ein Fehler auf! '.$e->getMessage());
                    }
                }
            }
            $em->flush();
        }
    }

    public static function DefaultActionHandler($e)
    {
        if ($e instanceof Update_DatabaseException) {
            return _databaseError($e->sql, $e->getMessage());
        }
        setUpdateMsg($e->getMessage());
        return false;
    }

}

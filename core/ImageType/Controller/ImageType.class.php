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
 * Image type handling
 *
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_imagetype
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */
namespace Cx\Core\ImageType\Controller;
/**
 * Image
 *
 * Includes access methods and data layer.
 * Do not, I repeat, do not access private fields, or even try
 * to access the database directly!
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_imagetype
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */
class ImageType
{
    /**
     * Text key for the image type
     */
    const TEXT_IMAGETYPE = "core_imagetype";

    /**
     * Default height used when the type is unknown
     */
    const DEFAULT_WIDTH  = 320;

    /**
     * Default height used when the type is unknown
     */
    const DEFAULT_HEIGHT = 240;

    /**
     * Default quality used when the type is unknown
     */
    const DEFAULT_QUALITY = 95;

    /**
     * Default height used when the type is unknown
     */
    const DEFAULT_WIDTH_THUMB = 160;

    /**
     * Default height used when the type is unknown
     */
    const DEFAULT_HEIGHT_THUMB = 120;

    /**
     * Default quality used when the type is unknown
     */
    const DEFAULT_QUALITY_THUMB = 95;

    /**
     * The key last used when {@see getArray()} was called, or false
     * @var   string
     */
    private static $last_key = false;
    /**
     * The array of Imagetypes as initialized by {@see getArray()}, or false
     * @var   array
     */
    private static $arrImagetypes = false;


    /**
     * Get an array with image type data.
     *
     * The $key argument may be empty (defaults to false), a single string,
     * or an array of strings.  Only matching keys are returned in the array.
     * The array returned looks like
     *  array(
     *    key => array(
     *      'width' => width,
     *      'height' => height,
     *      'quality' => quality,
     *      'width_thumb' => thumbnail width,
     *      'height_thumb' => thumbnail height,
     *      'quality_thumb' => thumbnail quality,
     *      'text_id' => Image type Text ID,
     *      'name' => Image type name,
     *    ),
     *    ... more ...
     *  )
     * The array elements are ordered by key, ascending.
     * Uses the MODULE_ID constant.
     * @static
     * @param   string      $key            The optional type key or key array
     * @return  array                       The type data array on success,
     *                                      false otherwise
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getArray($key=false)
    {
        global $objDatabase;

//echo("ImageType::getArray($key): Entered<br />");
        if (   self::$last_key !== ''
            && self::$last_key !== $key)
            self::reset();
        if (!is_array(self::$arrImagetypes)) {
//echo("ImageType::getArray($key): Entered<br />");
            $arrSqlName = \Text::getSqlSnippets(
                '`imagetype`.`text_id`', FRONTEND_LANG_ID,
                MODULE_ID, self::TEXT_IMAGETYPE
            );
            $query = "
                SELECT `imagetype`.`key`,
                       `imagetype`.`width`,
                       `imagetype`.`height`,
                       `imagetype`.`quality`,
                       `imagetype`.`width_thumb`,
                       `imagetype`.`height_thumb`,
                       `imagetype`.`quality_thumb`
                       ".$arrSqlName['field']."
                  FROM `".DBPREFIX."core_imagetype` AS `imagetype`".
                       $arrSqlName['join']."
                 WHERE 1".
                  (MODULE_ID ? ' AND `imagetype`.`module_id`='.MODULE_ID : '').
                  ($key
                    ? ' AND `imagetype`.`key`'.
                      (is_array($key)
                        ? " IN ('".join("','", array_map('addslashes', $key))."')"
                        : "='".addslashes($key)."'")
                    : '')."
                 ORDER BY `imagetype`.`key` ASC";
//echo("ImageType::getArray($key): query $query<br />");
            $objResult = $objDatabase->Execute($query);
//echo("ImageType::getArray($key): query ran, result ".var_export($objResult, true)."<br />");
            if (!$objResult) return self::errorHandler();
//die("ImageType::getArray($key): No error<br />");
            self::$arrImagetypes = array();
            while (!$objResult->EOF) {
                $strName = $objResult->fields[$arrSqlName['text']];
                if ($strName === null) {
//echo("No text<br />");
                    $strName = '';
                }
                $key = $objResult->fields['key'];
                self::$arrImagetypes[$key] = array(
                    'width' => $objResult->fields['width'],
                    'height' => $objResult->fields['height'],
                    'quality' => $objResult->fields['quality'],
                    'width_thumb' => $objResult->fields['width_thumb'],
                    'height_thumb' => $objResult->fields['height_thumb'],
                    'quality_thumb' => $objResult->fields['quality_thumb'],
                    'text_id' => $objResult->fields[$arrSqlName['id']],
                    'name' => $strName,
//                    'key' => $key,
                );
                $objResult->MoveNext();
            }
            self::$last_key = $key;
//die("ImageType::getArray($key): got ".var_export(self::$arrImagetypes, true)."<hr />");
        }
//echo("ImageType::getArray(): Array ".var_export(self::$arrImagetypes, true)."<br />");
        return self::$arrImagetypes;
    }


    /**
     * Get an array with image type names.
     *
     * See {@see getArray()} for details.
     * The array returned looks like
     *  array(
     *    key => Imagetype name,
     *    ... more ...
     *  )
     * The array elements are ordered by key, ascending.
     * Uses the MODULE_ID constant.
     * @static
     * @param   string      $key            The optional type key or key array
     * @return  array                       The type name array on success,
     *                                      false otherwise
     * @global  mixed       $objDatabase    Database object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getNameArray($key=false)
    {
        global $objDatabase;

//echo("ImageType::getNameArray($key): Entered<br />");
        $arrImagetypes = self::getArray($key);
        if ($arrImagetypes === false) return false;
        $arrName = array();
        foreach ($arrImagetypes as $key => $arrImagetype) {
            $arrName[$key] = $arrImagetype['name'];
        }
//die("ImageType::getNameArray($key): got ".var_export($arrImagetype, true)."<hr />");
        return $arrName;
    }


    static function reset()
    {
        self::$last_key = false;
        self::$arrImagetypes = false;
    }


    /**
     * Returns an array with the width, height and quality for both the
     * original and thumbnail image for the given Imagetype key
     *
     * If the key is not found, the default sizes are returned in the array.
     * The returned array looks like
     *  array(
     *    'width'         => image width,
     *    'height'        => image height,
     *    'quality        => image quality,
     *    'width_thumb'   => thumbnail width,
     *    'height_thumb'  => thumbnail height,
     *    'quality_thumb' => thumbnail quality,
     *  )
     * @param   string    $key        The Imagetype key
     * @return  array                 The Imagetype info array
     */
    static function getInfoArray($key=false)
    {
        $arrImagetype = false;
        if ($key) {
            $arrImagetype = self::getArray($key);
        }
        if (!is_array($arrImagetype) || empty($arrImagetype[$key])) {
            return array(
                'width'         => self::DEFAULT_WIDTH,
                'height'        => self::DEFAULT_HEIGHT,
                'quality'       => self::DEFAULT_QUALITY,
                'width_thumb'   => self::DEFAULT_WIDTH_THUMB,
                'height_thumb'  => self::DEFAULT_HEIGHT_THUMB,
                'quality_thumb' => self::DEFAULT_QUALITY_THUMB,
            );
        }
        return $arrImagetype[$key];
    }


    /**
     * Returns the width for the given Imagetype key
     *
     * If the Imagetype info cannot be retrieved, returns the default value.
     * @param   string    $key              The Imagetype key
     * @return  integer                     The Imagetype width
     */
    static function getWidth($key)
    {
        $arrImagetype = self::getArray($key);
        if (!is_array($arrImagetype) || empty($arrImagetype[$key])) {
            return self::DEFAULT_WIDTH;
        }
        return $arrImagetype[$key]['width'];

    }

    /**
     * Returns the height for the given Imagetype key
     *
     * If the Imagetype info cannot be retrieved, returns the default value.
     * @param   string    $key              The Imagetype key
     * @return  integer                     The Imagetype height
     */
    static function getHeight($key)
    {
        $arrImagetype = self::getArray($key);
        if (!is_array($arrImagetype) || empty($arrImagetype[$key])) {
            return self::DEFAULT_HEIGHT;
        }
        return $arrImagetype[$key]['height'];

    }

    /**
     * Returns the quality for the given Imagetype key
     *
     * If the Imagetype info cannot be retrieved, returns the default value.
     * @param   string    $key              The Imagetype key
     * @return  integer                     The Imagetype quality
     */
    static function getQuality($key)
    {
        $arrImagetype = self::getArray($key);
        if (!is_array($arrImagetype) || empty($arrImagetype[$key])) {
            return self::DEFAULT_QUALITY;
        }
        return $arrImagetype[$key]['quality'];

    }

    /**
     * Returns the thumbnail width for the given Imagetype key
     *
     * If the Imagetype info cannot be found, returns the default value.
     * This is the case if the $key is empty or an array, if the Imagetype
     * is unknown, or if the array of Imagetypes cannot be retrieved.
     * @param   string    $key              The Imagetype key
     * @return  integer                     The Imagetype thumbnail width
     */
    static function getWidthThumbnail($key)
    {
//echo("ImageType::getWidthThumbnail($key): Entered<br />");
        $arrImagetype = self::getArray($key);
        if (   empty($key)
            || is_array($key)
            || !is_array($arrImagetype)
            || empty($arrImagetype[$key])) {
            return self::DEFAULT_WIDTH_THUMB;
        }
        return $arrImagetype[$key]['width_thumb'];

    }

    /**
     * Returns the thumbnail height for the given Imagetype key
     *
     * If the Imagetype info cannot be found, returns the default value.
     * This is the case if the $key is empty or an array, if the Imagetype
     * is unknown, or if the array of Imagetypes cannot be retrieved.
     * @param   string    $key              The Imagetype key
     * @return  integer                     The Imagetype thumbnail height
     */
    static function getHeightThumbnail($key)
    {
//echo("ImageType::getHeightThumbnail($key): Entered<br />");
        $arrImagetype = self::getArray();
        if (   empty($key)
            || is_array($key)
            || !is_array($arrImagetype)
            || empty($arrImagetype[$key])) {
            return self::DEFAULT_HEIGHT_THUMB;
        }
        return $arrImagetype[$key]['height_thumb'];

    }

    /**
     * Returns the thumbnail quality for the given Imagetype key
     *
     * If the Imagetype info cannot be found, returns the default value.
     * This is the case if the $key is empty or an array, if the Imagetype
     * is unknown, or if the array of Imagetypes cannot be retrieved.
     * @param   string    $key              The Imagetype key
     * @return  integer                     The Imagetype thumbnail quality
     */
    static function getQualityThumbnail($key)
    {
        $arrImagetype = self::getArray();
        if (   empty($key)
            || is_array($key)
            || !is_array($arrImagetype)
            || empty($arrImagetype[$key])) {
            return self::DEFAULT_QUALITY_THUMB;
        }
        return $arrImagetype[$key]['quality_thumb'];

    }


    /**
     * Delete matching image types from the database.
     *
     * Also deletes associated Text records.
     * @param       string      $key          The type key
     * @return      boolean                   True on success, false otherwise
     * @global      mixed       $objDatabase  Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function deleteByKey($key)
    {
        global $objDatabase;

        if (empty($key)) return false;

        $objResult = $objDatabase->Execute("
            SELECT `text_id`
              FROM `".DBPREFIX."core_imagetype`
             WHERE `module_id`=".MODULE_ID."
               AND `key`='".addslashes($key)."'");
        if (!$objResult) return self::errorHandler();
        if ($objResult->RecordCount()) {
            if (!\Text::deleteById($objResult->fields['text_id']))
                return false;
        }
        $objResult = $objDatabase->Execute("
            DELETE FROM `".DBPREFIX."core_imagetype`
             WHERE `module_id`=".MODULE_ID."
               AND `key`='".addslashes($key)."'");
        if (!$objResult) return self::errorHandler();
        return true;
    }


    /**
     * Returns the Text ID of the Imagetype record with the given key.
     *
     * This works almost the same as {@see recordExists()} does,
     * except that you may have to check the result for null,
     * as the Text entry may be missing for any existing key.
     * @param       string      $key          The type key
     * @return      boolean                   The Text ID or null if the
     *                                        key exists, false otherwise
     * @global      mixed       $objDatabase  Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getTextIdByKey($key)
    {
        global $objDatabase;

        if (empty($key)) return false;
        $objResult = $objDatabase->Execute("
            SELECT `text_id`
              FROM `".DBPREFIX."core_imagetype`
             WHERE `module_id`=".MODULE_ID."
               AND `key`='".addslashes($key)."'");
        if (!$objResult) return self::errorHandler();
        if ($objResult->RecordCount())
            return $objResult->fields['text_id'];
        return false;
    }


    /**
     * Test whether a record with the given module ID and key is already
     * present in the database.
     * @param       string      $key          The type key
     * @return      boolean                   True if the record exists,
     *                                        false otherwise
     * @global      mixed       $objDatabase  Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function recordExists($key)
    {
        global $objDatabase;

        if (empty($key)) return false;
        $objResult = $objDatabase->Execute("
            SELECT 1
              FROM `".DBPREFIX."core_imagetype`
             WHERE `module_id`=".MODULE_ID."
               AND `key`='".addslashes($key)."'");
        if (!$objResult) return self::errorHandler();
        if ($objResult->RecordCount() == 1) return true;
        return false;
    }


    /**
     * Adds or updates the given image type.
     *
     * If a record with the given module ID and key already exists, it is
     * updated, otherwise it is inserted.
     * Also adds or updates the Text entry.  Only the language selected in
     * FRONTEND_LANG_ID is affected.
     * @param       string      $key            The type key
     * @param       string      $imagetype      The type description
     * @param       integer     $width          The width
     * @param       integer     $height         The height
     * @param       integer     $quality        The quality
     * @param       integer     $width_thumb    The thumbnail width
     * @param       integer     $height_thumb   The thumbnail height
     * @param       integer     $quality_thumb  The thumbnail quality
     * @return      boolean                     True on success,
     *                                          false otherwise
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function store(
        $key, $imagetype,
        $width='NULL', $height='NULL', $quality='NULL',
        $width_thumb='NULL', $height_thumb='NULL', $quality_thumb='NULL'
    ) {
        $text_id_old = self::getTextIdByKey($key);
        $text_id = \Text::replace(
            ($text_id_old ? $text_id_old : null),
            FRONTEND_LANG_ID, $imagetype,
            MODULE_ID, self::TEXT_IMAGETYPE);
        if ($text_id_old === false)
            return self::insert(
                $key, $text_id, $width, $height, $quality,
                $width_thumb, $height_thumb, $quality_thumb
            );
        return self::update(
            $key, $text_id, $width, $height, $quality,
            $width_thumb, $height_thumb, $quality_thumb
        );
    }


    /**
     * Update this image type in the database.
     *
     * Note that associations to module ID and key can *NOT* be modified.
     * If you need to change an image type this way, you have to delete()
     * and re-insert() it.
     * @param       string      $key            The type key
     * @param       integer     $text_id        The type description Text ID
     * @param       integer     $width          The width
     * @param       integer     $height         The height
     * @param       integer     $quality        The quality
     * @param       integer     $width_thumb    The thumbnail width
     * @param       integer     $height_thumb   The thumbnail height
     * @param       integer     $quality_thumb  The thumbnail quality
     * @return      boolean                     True on success,
     *                                          false otherwise
     * @global      mixed       $objDatabase  Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function update(
        $key, $text_id,
        $width='NULL', $height='NULL', $quality='NULL',
        $width_thumb='NULL', $height_thumb='NULL', $quality_thumb='NULL'
    ) {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            UPDATE `".DBPREFIX."core_imagetype`
               SET `text_id`=$text_id,
                   `width`=$width,
                   `height`=$height,
                   `quality`=$quality,
                   `width_thumb`=$width_thumb,
                   `height_thumb`=$height_thumb,
                   `quality_thumb`=$quality_thumb
             WHERE `module_id`=".MODULE_ID."
               AND `key`='".addslashes($key)."'");
        if (!$objResult) return self::errorHandler();
        return true;
    }


    /**
     * Insert this image type into the database.
     *
     * Uses the current language ID found in the FRONTEND_LANG_ID constant.
     * @param       string      $key          The type key
     * @param       integer     $text_id      The type description Text ID
     * @param       integer     $width        The width
     * @param       integer     $height       The height
     * @param       integer     $quality      The quality
     * @return      boolean                   True on success,
     *                                        false otherwise
     * @global      mixed       $objDatabase  Database object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function insert(
        $key, $text_id,
        $width='NULL', $height='NULL', $quality='NULL',
        $width_thumb='NULL', $height_thumb='NULL', $quality_thumb='NULL'
    ) {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            INSERT INTO ".DBPREFIX."core_imagetype (
                `module_id`, `key`, `text_id`,
                `width`, `height`, `quality`,
                `width_thumb`, `height_thumb`, `quality_thumb`
            ) VALUES (
                ".MODULE_ID.", '".addslashes($key)."', $text_id,
                $width, $height, $quality,
                $width_thumb, $height_thumb, $quality_thumb
            )");
        if (!$objResult) return self::errorHandler();
        return true;
    }


    /**
     * Display the imagetypes for editing
     *
     * Placeholders:
     * The imagetypes' name is written to IMAGETYPE_NAME, and the key
     * to IMAGETYPE_KEY.  Other fields are IMAGETYPE_WIDTH,
     * IMAGETYPE_HEIGHT, and IMAGETYPE_QUALITY for width, height, and
     * quality, respectively.
     * Some entries from $_CORELANG are set up. Their indices are used as
     * placeholder name as well.
     * If you want your imagetypes to be stored, you *MUST* handle the parameter
     * 'act=imagetypes_edit' in your modules' getPage(), and call this method
     * again.
     * @return  \Cx\Core\Html\Sigma   The Template object
     */
    static function edit()
    {
        global $objTemplate, $_CORELANG;

        $result = self::storeFromPost();
        if ($result === true) {
            $objTemplate->setVariable('CONTENT_OK_MESSAGE',
                $_CORELANG['TXT_CORE_IMAGETYPE_STORED_SUCCESSFULLY']);
        } elseif ($result === false) {
            $objTemplate->setVariable('CONTENT_STATUS_MESSAGE',
                $_CORELANG['TXT_CORE_IMAGETYPE_ERROR_STORING']);
        }

        if (!empty($_REQUEST['imagetype_delete_key'])) {
            $result = self::deleteByKey($_REQUEST['imagetype_delete_key']);
            if ($result === true) {
                $objTemplate->setVariable('CONTENT_OK_MESSAGE',
                    $_CORELANG['TXT_CORE_IMAGETYPE_DELETED_SUCCESSFULLY']);
            } elseif ($result === false) {
                $objTemplate->setVariable('CONTENT_STATUS_MESSAGE',
                    $_CORELANG['TXT_CORE_IMAGETYPE_ERROR_DELETING']);
            }
        }
        self::reset();

//$objTemplate->setCurrentBlock();
//echo(nl2br(htmlentities(var_export($objTemplate->getPlaceholderList()))));

        $objTemplateLocal = new \Cx\Core\Html\Sigma(\Env::get('cx')->getCodeBaseCorePath() . '/ImageType/View/Template/Generic');
// TODO: Needed?
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($objTemplateLocal);
        $objTemplateLocal->setErrorHandling(PEAR_ERROR_DIE);
        if (!$objTemplateLocal->loadTemplateFile('Types.html'))
            die("Failed to load template Types.html");
        $uri = \Html::getRelativeUri_entities();
        $active_tab = \Cx\Core\Setting\Controller\Setting::getTabIndex();
        \Html::replaceUriParameter($uri, 'active_tab='.$active_tab);
        \Html::stripUriParam($uri, 'imagetype_delete_key');
        \Html::stripUriParam($uri, 'key');
        \Html::stripUriParam($uri, 'act');
        $objTemplateLocal->setGlobalVariable(
// TODO: Add sorting
            $_CORELANG
          + array(
                'URI_BASE' => $uri,
//                'CORE_IMAGETYPE_ACTIVE_TAB' => $active_tab,
        ));

        $arrImagetypes = self::getArray();
//echo("ImageType::edit(): got Array: ".var_export($arrImagetypes, true)."<br />");
        if (!is_array($arrImagetypes)) {
            $objTemplateLocal->setVariable(
                'CONTENT_STATUS_MESSAGE',
                $_CORELANG['TXT_CORE_IMAGETYPE_ERROR_RETRIEVING']
            );
            return $objTemplateLocal;
        }
        if (empty($arrImagetypes)) {
            $objTemplateLocal->setVariable(
                'CONTENT_STATUS_MESSAGE',
                sprintf(
                    $_CORELANG['TXT_CORE_IMAGETYPE_WARNING_NONE_FOUND_FOR_MODULE'],
                    MODULE_ID
                )
            );
            return $objTemplateLocal;
        }

        $i = 0;
        foreach ($arrImagetypes as $key => $arrImagetype) {
            $name    = $arrImagetype['name'];
            $width   = $arrImagetype['width'];
            $height  = $arrImagetype['height'];
            $quality = $arrImagetype['quality'];
            $width_thumb   = $arrImagetype['width_thumb'];
            $height_thumb  = $arrImagetype['height_thumb'];
            $quality_thumb = $arrImagetype['quality_thumb'];
            $objTemplateLocal->setVariable(array(
                'CORE_IMAGETYPE_ROWCLASS'  => ++$i % 2 + 1,
                'CORE_IMAGETYPE_KEY'       =>
                    $key.
                    \Html::getHidden(
                        'imagetype_key['.$key.']', $key, 'imagetype_key-'.$key),
                'CORE_IMAGETYPE_NAME'      =>
                    \Html::getInputText(
                        'imagetype_name['.$key.']', $name, 'imagetype_name-'.$key,
                        'style="width: 220px;"'),
                'CORE_IMAGETYPE_WIDTH'     =>
                    \Html::getInputText(
                        'imagetype_width['.$key.']', $width, false,
                        'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PIXEL'],
                'CORE_IMAGETYPE_HEIGHT'    =>
                    \Html::getInputText(
                        'imagetype_height['.$key.']', $height, false,
                        'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PIXEL'],
                'CORE_IMAGETYPE_QUALITY'   =>
                    \Html::getInputText(
                        'imagetype_quality['.$key.']', $quality, false,
                        'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PERCENT_SIGN'],
                'CORE_IMAGETYPE_WIDTH_THUMB'     =>
                    \Html::getInputText(
                        'imagetype_width_thumb['.$key.']', $width_thumb, false,
                        'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PIXEL'],
                'CORE_IMAGETYPE_HEIGHT_THUMB'    =>
                    \Html::getInputText(
                        'imagetype_height_thumb['.$key.']', $height_thumb, false,
                        'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PIXEL'],
                'CORE_IMAGETYPE_QUALITY_THUMB'   =>
                    \Html::getInputText(
                        'imagetype_quality_thumb['.$key.']', $quality_thumb, false,
                        'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PERCENT_SIGN'],
// Disabled by popular demand
//                'CORE_IMAGETYPE_FUNCTIONS' =>
//                    \Html::getBackendFunctions(array(
//                            'delete' => $uri.'&amp;imagetype_delete_key='.urlencode($key),
//                        ),
//                        array(
//                            'delete' => $_CORELANG['TXT_CORE_IMAGETYPE_DELETE_CONFIRM'],
//                        )
//                    ),
            ));
            $objTemplateLocal->parse('core_imagetype_data');
        }
        $objTemplateLocal->touchBlock('core_imagetype_section');
        $objTemplateLocal->parse('core_imagetype_section');
        $objTemplateLocal->setVariable(array(
            'CORE_IMAGETYPE_ROWCLASS'  => 1,
            'CORE_IMAGETYPE_KEY'       =>
                \Html::getInputText(
                    'imagetype_key[new]', '', false,
                    'style="width: 220px;"'),
            'CORE_IMAGETYPE_NAME'      =>
                \Html::getInputText(
                    'imagetype_name[new]', '', false,
                    'style="width: 220px;"'),
            'CORE_IMAGETYPE_WIDTH'     =>
                \Html::getInputText(
                    'imagetype_width[new]', self::DEFAULT_WIDTH, false,
                    'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PIXEL'],
            'CORE_IMAGETYPE_HEIGHT'    =>
                \Html::getInputText(
                    'imagetype_height[new]', self::DEFAULT_HEIGHT, false,
                    'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PIXEL'],
            'CORE_IMAGETYPE_QUALITY'   =>
                \Html::getInputText(
                    'imagetype_quality[new]', self::DEFAULT_QUALITY, false,
                    'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PERCENT_SIGN'],
            'CORE_IMAGETYPE_WIDTH_THUMB'     =>
                \Html::getInputText(
                    'imagetype_width_thumb[new]', self::DEFAULT_WIDTH_THUMB, false,
                    'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PIXEL'],
            'CORE_IMAGETYPE_HEIGHT_THUMB'    =>
                \Html::getInputText(
                    'imagetype_height_thumb[new]', self::DEFAULT_HEIGHT_THUMB, false,
                    'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PIXEL'],
            'CORE_IMAGETYPE_QUALITY_THUMB'   =>
                \Html::getInputText(
                    'imagetype_quality_thumb[new]', self::DEFAULT_QUALITY_THUMB, false,
                    'style="width: 30px; text-align: right;"').
                    $_CORELANG['TXT_CORE_IMAGETYPE_PERCENT_SIGN'],
            'CORE_IMAGETYPE_FUNCTIONS' => '',
        ));
        $objTemplateLocal->parse('core_imagetype_data');
        return $objTemplateLocal;
    }


    /**
     * Update and store all imagetypes found in the $_POST array
     * @return  boolean                 True on success,
     *                                  the empty string if none was changed,
     *                                  or false on failure
     */
    static function storeFromPost()
    {
//echo("ImageType::storeFromPost(): Entered<br />");
        if (!isset($_POST['imagetype_key'])) return '';
        // Compare POST with current imagetypes.
        // Only store what was changed.
        $arrImagetypes = self::getArray();
        $result = '';
        // The keys don't really change, but we can recognize added
        // entries easily like this
        foreach ($_POST['imagetype_key'] as $key_old => $key_new) {
            // Strip crap characters from the key
            $key_new = preg_replace('/[^_a-z\d]/i', '', $key_new);
            // No new Imagetype is to be added if the new key is empty
            if (empty($key_new)) {
                continue;
            }
//echo("TEST: Old key $key_old, new: '$key_new'<br />");
            $key_old = contrexx_stripslashes($key_old);
            $key_new = contrexx_stripslashes($key_new);
            $name    = contrexx_stripslashes($_POST['imagetype_name'][$key_old]);
            $width   = contrexx_stripslashes($_POST['imagetype_width'][$key_old]);
            $height  = contrexx_stripslashes($_POST['imagetype_height'][$key_old]);
            $quality = contrexx_stripslashes($_POST['imagetype_quality'][$key_old]);
            $width_thumb   = contrexx_stripslashes($_POST['imagetype_width_thumb'][$key_old]);
            $height_thumb  = contrexx_stripslashes($_POST['imagetype_height_thumb'][$key_old]);
            $quality_thumb = contrexx_stripslashes($_POST['imagetype_quality_thumb'][$key_old]);
            if (   empty($arrImagetypes[$key_old])
                || $name != $arrImagetypes[$key_old]['name']
                || $width != $arrImagetypes[$key_old]['width']
                || $height != $arrImagetypes[$key_old]['height']
                || $quality != $arrImagetypes[$key_old]['quality']
                || $width_thumb != $arrImagetypes[$key_old]['width_thumb']
                || $height_thumb != $arrImagetypes[$key_old]['height_thumb']
                || $quality_thumb != $arrImagetypes[$key_old]['quality_thumb']
            ) {
//echo("Changed or new<br />");
                if ($result === '') $result = true;
                if (!self::store(
                    $key_new, $name, $width, $height, $quality,
                    $width_thumb, $height_thumb, $quality_thumb))
                    $result = false;
            }
        }
        return $result;
    }


    /**
     * Returns either a dropdown menu for the imagetypes available or
     * a hidden element and text for a single imagetype
     *
     * The $name parameter *SHOULD* be the same as the name attribute used for
     * a corresponding element created by {@see Html::getImageChooserUpload()}.
     * The string '_type' will be appended to the element name.
     * If the optional $imagetype_key is empty or not an array,
     * no image type can be selected.  If it's false, the image type field is
     * omitted altogether.  If it's empty but not false, the selected
     * Image type is used.  If it's a string, the type is set to
     * that value, overriding the selected type.  If it's an array,
     * the Image type can be selected from its values, and the selected
     * key is preselected.  The array keys *SHOULD* contain the Imagetype key,
     * its values the Imagetype names in the current language.
     * See {@see Image::updatePostImages()} and {@see Image::uploadAndStore()}
     * for more information and examples.
     * @param   string  $name           The base name for the element
     * @param   string  $selected       The optional preselected key
     * @param   mixed   $imagetype_key  The optional Image type key or
     *                                  Imagetype array.  Defaults to the
     *                                  empty string
     * @param   string  $attribute      The optional attributes
     * @return  string                  The image type dropdown menu HTML code,
     *                                  The image type name and hidden element,
     *                                  or the empty string
     */
    static function getMenu(
        $name, $selected='', $imagetype_key='', $attribute=null
    ) {
//        global $_CORELANG;

//echo("ImageType::getMenu($name, $selected, $imagetype_key): Entered<br />");
        if ($imagetype_key === false) return '';
        $menu = '';
        if (is_array($imagetype_key)) {
            $menu = \Html::getSelect(
                $name.'_type', $imagetype_key, $selected, false, '',
                $attribute);
        } else {
            $arrName = self::getNameArray($imagetype_key);
            $menu =
                \Html::getHidden($name.'_type', $imagetype_key).
                current($arrName);
        }
//        $menu = sprintf(
//            $_CORELANG['TXT_CORE_HTML_IMAGETYPE_NAME'], $menu
//        );
//echo("*** ImageType::getMenu($name, $selected, $imagetype_key): Made menu<br />".nl2br(htmlentities(var_export($menu, true)))."<br />");
        return $menu;
    }


    /**
     * Handle any error occurring in this class.
     *
     * Tries to fix known problems with the database table.
     * @global  mixed     $objDatabase    Database object
     * @return  boolean                   False.  Always.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function errorHandler()
    {
        global $objDatabase;

//die("ImageType::errorHandler(): Disabled!<br />");

        $arrTables = $objDatabase->MetaTables('TABLES');
        if (in_array(DBPREFIX."core_imagetype", $arrTables)) {
            $objResult = $objDatabase->Execute("
                DROP TABLE `".DBPREFIX."core_imagetype`");
            if (!$objResult) return false;

echo("ImageType::errorHandler(): Created table core_imagetype<br />");
        }
        $objResult = $objDatabase->Execute("
            CREATE TABLE IF NOT EXISTS `".DBPREFIX."core_imagetype` (
              `module_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'The ID of the module this image type occurs in',
              `key` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'The key unique for each module ID that identifies the image type',
              `text_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Relates to core_text.id',
              `width` INT UNSIGNED NULL DEFAULT NULL,
              `height` INT UNSIGNED NULL DEFAULT NULL,
              `quality` INT UNSIGNED NULL DEFAULT NULL,
              `width_thumb` INT UNSIGNED NULL DEFAULT NULL,
              `height_thumb` INT UNSIGNED NULL DEFAULT NULL,
              `quality_thumb` INT UNSIGNED NULL DEFAULT NULL,
              PRIMARY KEY (`module_id`, `key`),
              UNIQUE (`text_id`)
            ) ENGINE=MYISAM");
        if (!$objResult) return false;
echo("ImageType::errorHandler(): Created table core_imagetype<br />");

        $arrImagetypes = array(
            // hotelcard image type entries
            array(
              'module_id' => 10013,
              'key'       => 'hotelcard_hotel_title',
              'text'      => array(
                  1 => 'Titelbild', // de
                  2 => 'Title',     // en
                  3 => 'Title',     // fr
                  4 => 'Title',     // it
              ),
              'width'     => 320,
              'height'    => 240,
              'quality'   => 90,
              'width_thumb'   => 160,
              'height_thumb'  => 120,
              'quality_thumb' => 90,
            ),
            array(
              'module_id' => 10013,
              'key'       => 'hotelcard_hotel_room',
              'text'      => array(
                  1 => 'Zimmer',
                  2 => 'Room',
                  3 => 'Room',
                  4 => 'Room',
              ),
              'width'     => 320,
              'height'    => 240,
              'quality'   => 90,
              'width_thumb'   => 160,
              'height_thumb'  => 120,
              'quality_thumb' => 90,
            ),
            array(
              'module_id' => 10013,
              'key'       => 'hotelcard_hotel_vicinity',
              'text'      => array(
                  1 => 'Umbgebung',
                  2 => 'Vicinity',
                  3 => 'Vicinity',
                  4 => 'Vicinity',
              ),
              'width'     => 320,
              'height'    => 240,
              'quality'   => 90,
              'width_thumb'   => 160,
              'height_thumb'  => 120,
              'quality_thumb' => 90,
            ),
            array(
              'module_id' => 10013,
              'key'       => 'hotelcard_hotel_lobby',
              'text'      => array(
                  1 => 'Lobby',
                  2 => 'Lobby',
                  3 => 'Lobby',
                  4 => 'Lobby',
              ),
              'width'     => 320,
              'height'    => 240,
              'quality'   => 90,
              'width_thumb'   => 160,
              'height_thumb'  => 120,
              'quality_thumb' => 90,
            ),
        );

        \Text::deleteByKey(self::TEXT_IMAGETYPE);

        foreach ($arrImagetypes as $arrImagetype) {
            $text_id = 0;
            foreach ($arrImagetype['text'] as $lang_id => $text) {
                $text_id = \Text::replace(
                    $text_id, $lang_id, $text,
                    $arrImagetype['module_id'], self::TEXT_IMAGETYPE);
                if (!$text_id)
die("ImageType::errorHandler(): Error storing Text");
            }
            $objResult = $objDatabase->Execute("
                INSERT INTO `".DBPREFIX."core_imagetype` (
                  `module_id`, `key`, `text_id`,
                  `width`, `height`, `quality`,
                  `width_thumb`, `height_thumb`, `quality_thumb`
                ) VALUES (
                  ".$arrImagetype['module_id'].",
                  '".addslashes($arrImagetype['key'])."',
                  $text_id,
                  ".$arrImagetype['width'].",
                  ".$arrImagetype['height'].",
                  ".$arrImagetype['quality'].",
                  ".$arrImagetype['width_thumb'].",
                  ".$arrImagetype['height_thumb'].",
                  ".$arrImagetype['quality_thumb']."
                )");
            if (!$objResult)
die("ImageType::errorHandler(): Error adding Imagetype");

echo("ImageType::errorHandler(): Inserted image type ".var_export($arrImagetype, true)."<br />");
        }

        // More to come...

        return false;
    }

}

?>

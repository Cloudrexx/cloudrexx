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
 * Media Directory Inputfield WYSIWYG Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;

/**
 * Media Directory Inputfield WYSIWYG Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
class MediaDirectoryInputfieldWysiwyg extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary implements Inputfield
{
    public $arrPlaceholders = array('TXT_MEDIADIR_INPUTFIELD_NAME','MEDIADIR_INPUTFIELD_VALUE');


    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
        parent::getFrontendLanguages();
    }


    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase, $objInit, $_ARRAYLANG;;

        $intId = intval($arrInputfield['id']);
        $langId = static::getOutputLocale()->getId();

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                if (isset($intEntryId) && $intEntryId != 0) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`,
                            `lang_id`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                        WHERE
                            field_id=".$intId."
                        AND
                            entry_id=".$intEntryId."
                    ");

                    if ($objInputfieldValue !== false) {
                        while (!$objInputfieldValue->EOF) {
                            $arrValue[intval($objInputfieldValue->fields['lang_id'])] = $objInputfieldValue->fields['value'];
                            $objInputfieldValue->MoveNext();
                        }
// TODO: What if the current language value is missing?
// The empty string is an inconvenient default!
//                        $arrValue[0] = $arrValue[$langId];
                        $arrValue[0] = (isset($arrValue[$langId]) ? $arrValue[$langId] : '');
                    }
                } else {
                    $arrValue = null;
                }

                if (empty($arrValue)) {
// TODO: Bogus line
//                    $strDefaultValue = empty($strDefaultValue) ? $arrInputfield['default_value'][0] : $strDefaultValue;
                    foreach ($arrInputfield['default_value'] as $intLangKey => $strDefaultValue) {
                       if (substr($strDefaultValue,0,2) == '[[') {
                            $objPlaceholder = new \Cx\Modules\MediaDir\Controller\MediaDirectoryPlaceholder($this->moduleName);
                            $arrValue[$intLangKey] = $objPlaceholder->getPlaceholder($strDefaultValue);
                        } else {
                            $arrValue[$intLangKey] = $strDefaultValue;
                        }
                    }
                }

                /*$arrInfoValue = array();
                if (!empty($arrInputfield['info'][0])){
                    $arrInfoValue[0] = 'title="'.$arrInputfield['info'][0].'"';
                    foreach ($arrInputfield['info'] as $intLangKey => $strInfoValue) {
                        $strInfoClass = 'mediadirInputfieldHint';
                        $arrInfoValue[$intLangKey] = empty($strInfoValue) ? 'title="'.$arrInputfield['info'][0].'"' : 'title="'.$strInfoValue.'"';
                    }
                } else {
                    $arrInfoValue = null;
                    $strInfoClass = '';
                }*/

                if ($objInit->mode == 'backend') {
                    $strInputfield = '<span id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT_Minimized" style="display: block;">'.new \Cx\Core\Wysiwyg\Wysiwyg($this->moduleNameLC.'Inputfield['.$intId.'][0]', contrexx_raw2xhtml($arrValue[0])).'&nbsp;<a href="javascript:ExpandMinimizeMultiple(\''.$intId.'\', \'ELEMENT\');">'.$_ARRAYLANG['TXT_MEDIADIR_MORE'].'&nbsp;&raquo;</a></span>';

                    $strInputfield .= '<span id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT_Expanded" style="display: none;">';
                    foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                        $intLangId = $arrLang['id'];

                        $strInputfield .=  new \Cx\Core\Wysiwyg\Wysiwyg($this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.']', contrexx_raw2xhtml($arrValue[$intLangId])).'&nbsp;'.$arrLang['name'].'<br />';
                    }
                    $strInputfield .=  "&nbsp;<a href=\"javascript:ExpandMinimizeMultiple('".$intId."', 'ELEMENT');\">&laquo;&nbsp;".$_ARRAYLANG['TXT_MEDIADIR_MINIMIZE']."</a>";

                    $strInputfield .= '</span>';
                } else {
                    if ($this->arrSettings['settingsFrontendUseMultilang'] == 1) {
                        $strInputfield = '<span class="editorFix"><span id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT_Minimized" style="display: block;" class="'.$this->moduleNameLC.'GroupMultilang">'.new \Cx\Core\Wysiwyg\Wysiwyg($this->moduleNameLC.'Inputfield['.$intId.'][0]', contrexx_raw2xhtml($arrValue[0])).'&nbsp;<a href="javascript:ExpandMinimizeMultiple(\''.$intId.'\', \'ELEMENT\');">'.$_ARRAYLANG['TXT_MEDIADIR_MORE'].'&nbsp;&raquo;</a></span>';

                        $strInputfield .= '<span id="'.$this->moduleNameLC.'Inputfield_'.$intId.'_ELEMENT_Expanded" style="display: none;" class="'.$this->moduleNameLC.'GroupMultilang">';
                        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                            $intLangId = $arrLang['id'];
                            $strInputfield .=  new \Cx\Core\Wysiwyg\Wysiwyg($this->moduleNameLC.'Inputfield['.$intId.']['.$intLangId.']', contrexx_raw2xhtml($arrValue[$intLangId])).'&nbsp;'.$arrLang['name'].'<br />';
                        }
                        $strInputfield .= '<a href="javascript:ExpandMinimizeMultiple(\''.$intId.'\', \'ELEMENT\');">&nbsp;'.$_ARRAYLANG['TXT_MEDIADIR_MINIMIZE'].'</a>';
                        $strInputfield .= '</span></span>';
                    } else {
                        $strInputfield = '<span class="editorFix">'.new \Cx\Core\Wysiwyg\Wysiwyg($this->moduleNameLC.'Inputfield['.$intId.'][0]', contrexx_raw2xhtml($arrValue[0])).'</span>';
                    }
                }
                return $strInputfield;
            case 2:
                //search View
                $strValue = (isset ($_GET[$intId]) ? $_GET[$intId] : '');
                $strInputfield = '<input type="text" name="'.$intId.'" " class="'.$this->moduleNameLC.'InputfieldSearch" value="'.$strValue.'" />';
                return $strInputfield;
        }
    }


    function saveInputfield($intInputfieldId, $strValue, $langId = 0)
    {
        global $objInit;

        $strValue = contrexx_input2raw($strValue);

        return $strValue;
    }


    function deleteContent($intEntryId, $intIputfieldId)
    {
        global $objDatabase;

        return (boolean)$objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
             WHERE `entry_id`='".intval($intEntryId)."'
               AND  `field_id`='".intval($intIputfieldId)."'");
    }



    function getContent($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
        $strValue = static::getRawData($intEntryId, $arrInputfield, $arrTranslationStatus);

        if (!empty($strValue)) {
            $arrContent['TXT_'.$this->moduleLangVar.'_INPUTFIELD_NAME'] = htmlspecialchars($arrInputfield['name'][0], ENT_QUOTES, CONTREXX_CHARSET);
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'] = $strValue;
        } else {
            $arrContent = null;
        }

        return $arrContent;
    }

    function getRawData($intEntryId, $arrInputfield, $arrTranslationStatus) {
        global $objDatabase;

        $intId = intval($arrInputfield['id']);
        $objEntryDefaultLang = $objDatabase->Execute("SELECT `lang_id` FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_entries WHERE id=".intval($intEntryId)." LIMIT 1");
        $intEntryDefaultLang = intval($objEntryDefaultLang->fields['lang_id']);
        $langId = static::getOutputLocale()->getId();

        if ($this->arrSettings['settingsTranslationStatus'] == 1) {
            if (in_array($langId, $arrTranslationStatus)) {
                $intLangId = $langId;
            } else {
                $intLangId = $intEntryDefaultLang;
            }
        } else {
            $intLangId = $langId;
        }

        $objInputfieldValue = $objDatabase->Execute("
            SELECT
                `value`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
            WHERE
                field_id=".$intId."
            AND
                entry_id=".intval($intEntryId)."
            AND
                lang_id=".$intLangId."
            LIMIT 1
        ");

        if (empty($objInputfieldValue->fields['value'])) {
            $objInputfieldValue = $objDatabase->Execute("
                SELECT
                    `value`
                FROM
                    ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                WHERE
                    field_id=".$intId."
                AND
                    entry_id=".intval($intEntryId)."
                AND
                    lang_id=".intval($intEntryDefaultLang)."
                LIMIT 1
            ");
        }

        /*$intId = intval($arrInputfield['id']);
        $objInputfieldValue = $objDatabase->Execute("
            SELECT
                `value`
            FROM
                ".DBPREFIX."module_".self::$moduleTablePrefix."_rel_entry_inputfields
            WHERE
                field_id=".$intId."
            AND
                entry_id=".$intEntryId."
            LIMIT 1
        "); */

        return $objInputfieldValue->fields['value'];
    }



    public function getJavascriptCheck()
    {
        $fieldName = $this->moduleNameLC."Inputfield";
        $strJavascriptCheck = <<<EOF

            case 'wysiwyg':
                for (var i in CKEDITOR.instances) {
                    var fieldIdRegexp = new RegExp('${fieldName}\\\[' + field + '\\\]\\\[[0-9]+\\\]');
                    if (!fieldIdRegexp.test(i)) {
                        continue;
                    }
                    var value = CKEDITOR.instances[i].getData();
                    if (value == '' && isRequiredGlobal(inputFields[field][1], value)) {
                        isOk = false;
                        document.getElementById('cke_' + i).style.border = "#ff0000 1px solid";
                    } else if (value != '' && !matchType(inputFields[field][2], value)) {
                        isOk = false;
                        document.getElementById('cke_' + i).style.border = "#ff0000 1px solid";
                    } else {
                        document.getElementById('cke_' + i).style.borderColor = '';
                    }
                }
                break;

EOF;
        return $strJavascriptCheck;
    }



    function BBCodeToHTML($content){
        global $_ARRAYLANG;
        $objBBCode = new \StringParser_BBCode();
        $objBBCode->addFilter(STRINGPARSER_FILTER_PRE, array(&$this, 'convertlinebreaks')); //unify all linebreak variants from different systems
        $objBBCode->addFilter(STRINGPARSER_FILTER_PRE, array(&$this, 'convertlinks'));
//      $objBBCode->addFilter(STRINGPARSER_FILTER_POST, array(&$this, 'stripBBtags'));
        $objBBCode->addFilter(STRINGPARSER_FILTER_POST, array(&$this, 'removeDoubleEscapes'));
        $objBBCode->addParser(array ('block', 'inline', 'link', 'listitem'), 'htmlspecialchars');
        $objBBCode->addParser(array ('block', 'inline', 'link', 'listitem'), 'nl2br');
        $objBBCode->addParser('list', array(&$this, 'bbcode_stripcontents'));
        $objBBCode->addCode('b', 'simple_replace', null, array ('start_tag' => '<b>', 'end_tag' => '</b>'), 'inline', array ('block', 'inline'), array ());
        $objBBCode->addCode('i', 'simple_replace', null, array ('start_tag' => '<i>', 'end_tag' => '</i>'), 'inline', array ('block', 'inline'), array ());
        $objBBCode->addCode('u', 'simple_replace', null, array ('start_tag' => '<u>', 'end_tag' => '</u>'), 'inline', array ('block', 'inline'), array ());
        $objBBCode->addCode('s', 'simple_replace', null, array ('start_tag' => '<strike>', 'end_tag' => '</strike>'), 'inline', array ('block', 'inline'), array ());
        $objBBCode->addCode('url', 'usecontent?', array(&$this, 'do_bbcode_url'), array ('usecontent_param' => 'default'), 'inline', array ('listitem', 'block', 'inline'), array ('link'));
        $objBBCode->addCode('img', 'usecontent', array(&$this, 'do_bbcode_img'), array ('usecontent_param' => array('w', 'h')), 'image', array ('listitem', 'block', 'inline', 'link'), array ());
        $objBBCode->addCode('quote', 'callback_replace', array(&$this, 'do_bbcode_quote'), array('usecontent_param' => 'default'), 'block', array('block', 'inline'), array('list', 'listitem'));
        $objBBCode->addCode('code', 'usecontent', array(&$this, 'do_bbcode_code'), array('usecontent_param' => 'default'), 'block', array('block', 'inline'), array('list', 'listitem'));
        $objBBCode->addCode('list', 'simple_replace', null, array ('start_tag' => '<ul>', 'end_tag' => '</ul>'), 'list', array ('block', 'listitem'), array ());
        $objBBCode->addCode('*', 'simple_replace', null, array ('start_tag' => '<li>', 'end_tag' => '</li>'), 'listitem', array ('list'), array ());
        $objBBCode->setCodeFlag('*', 'closetag', BBCODE_CLOSETAG_OPTIONAL);
        $objBBCode->setCodeFlag('*', 'paragraphs', true);
        $objBBCode->setCodeFlag('list', 'paragraph_type', BBCODE_PARAGRAPH_BLOCK_ELEMENT);
        $objBBCode->setCodeFlag('list', 'opentag.before.newline', BBCODE_NEWLINE_DROP);
        $objBBCode->setCodeFlag('list', 'closetag.before.newline', BBCODE_NEWLINE_DROP);

        $objBBCode->setOccurrenceType('img', 'image');
        $objBBCode->setMaxOccurrences('image', 5);

        $objBBCode->setRootParagraphHandling(false); //do not convert new lines to paragraphs, see stringparser_bbcode::setParagraphHandlingParameters();
        $content = $objBBCode->parse($content);

        return $content;
    }


    function getFormOnSubmit($intInputfieldId)
    {
        return null;
    }
}

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
 * BBCode Handler for Wysiwyg
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 */

namespace Cx\Core\Wysiwyg;

/**
 * BBCodeHandler class
 *
 * This code comes from the forum module library and is now used by WYSIWYG class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @version     3.1.0
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 */

class BBCodeHandler extends \StringParser_BBCode
{
    /**
     * Construct the BBCodeHandler
     */
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSettings();
    }

    /**
     * Set default settings for the BBCode Handler
     */
    protected function setDefaultSettings()
    {
        $this->addFilter(STRINGPARSER_FILTER_PRE, array(&$this, 'convertlinebreaks')); //unify all linebreak variants from different systems
        $this->addFilter(STRINGPARSER_FILTER_PRE, array(&$this, 'convertlinks'));
        // $this->addFilter(STRINGPARSER_FILTER_POST, array(&$this, 'stripBBtags'));
        $this->addFilter(STRINGPARSER_FILTER_POST, array(&$this, 'removeDoubleEscapes'));
        $this->addParser(array('block', 'inline', 'link', 'listitem'), 'htmlspecialchars');
        $this->addParser(array('block', 'inline', 'link', 'listitem'), 'nl2br');
        $this->addParser('list', array(&$this, 'bbcode_stripcontents'));
        $this->addCode('b', 'simple_replace', null, array('start_tag' => '<b>', 'end_tag' => '</b>'), 'inline', array('block', 'inline'), array());
        $this->addCode('i', 'simple_replace', null, array('start_tag' => '<i>', 'end_tag' => '</i>'), 'inline', array('block', 'inline'), array());
        $this->addCode('u', 'simple_replace', null, array('start_tag' => '<u>', 'end_tag' => '</u>'), 'inline', array('block', 'inline'), array());
        $this->addCode('s', 'simple_replace', null, array('start_tag' => '<strike>', 'end_tag' => '</strike>'), 'inline', array('block', 'inline'), array());
        $this->addCode('url', 'usecontent?', array(&$this, 'do_bbcode_url'), array('usecontent_param' => 'default'), 'inline', array('listitem', 'block', 'inline'), array('link'));
        $this->addCode('img', 'usecontent', array(&$this, 'do_bbcode_img'), array('usecontent_param' => array('w', 'h')), 'image', array('listitem', 'block', 'inline', 'link'), array());
        $this->addCode('quote', 'callback_replace', array(&$this, 'do_bbcode_quote'), array('usecontent_param' => 'default'), 'block', array('block', 'inline'), array());
//        $this->addCode('quote', 'usecontent?', array(&$this, 'do_bbcode_quote'), array('usecontent_param' => 'default'), 'link', array ('block', 'inline'), array ('link'));
        $this->addCode('code', 'usecontent', array(&$this, 'do_bbcode_code'), array('usecontent_param' => 'default'), 'block', array('block', 'inline'), array('list', 'listitem'));
        $this->addCode('list', 'simple_replace', null, array('start_tag' => '<ul>', 'end_tag' => '</ul>'), 'list', array('block', 'listitem'), array());
        $this->addCode('*', 'simple_replace', null, array('start_tag' => '<li>', 'end_tag' => '</li>'), 'listitem', array('list'), array());
        $this->setCodeFlag('*', 'closetag', BBCODE_CLOSETAG_OPTIONAL);
        $this->setCodeFlag('*', 'paragraphs', true);
        $this->setCodeFlag('list', 'paragraph_type', BBCODE_PARAGRAPH_BLOCK_ELEMENT);
        $this->setCodeFlag('list', 'opentag.before.newline', BBCODE_NEWLINE_DROP);
        $this->setCodeFlag('list', 'closetag.before.newline', BBCODE_NEWLINE_DROP);

        $this->setOccurrenceType('img', 'image');
        $this->setMaxOccurrences('image', 5);

        // do not convert new lines to paragraphs, see stringparser_bbcode::setParagraphHandlingParameters();
        $this->setRootParagraphHandling(false);
    }

    /**
     * Remove double escapes
     *
     * @param string $text
     * @return string
     */
    public function removeDoubleEscapes($text)
    {
        return html_entity_decode($text, ENT_QUOTES, CONTREXX_CHARSET);
    }

    /**
     * Convert links
     *
     * @param string $text
     * @return mixed
     */
    public function convertlinks($text)
    {
        if (preg_match('#^http://.*#', $text)) {
            return preg_replace('#(http://)+(www\.)?([a-zA-Z0-9][a-zA-Z0-9-_/]+\.[a-zA-Z0-9][a-zA-Z0-9-_/&\#\+=\?\.:;%]+)+(\[/url\])?#i', '[url]$1$2$3$4$5[/url]', $text);
        }
        return preg_replace('#[\s]+(http://)+(www\.)?([a-zA-Z0-9][a-zA-Z0-9-_/]+\.[a-zA-Z0-9][a-zA-Z0-9-_/&\#\+=\?\.:;%]+)+(\[/url\])?#i', '[url]$1$2$3$4$5[/url]', $text);
    }

    /**
     * strip BB tags
     *
     * @param string $text
     * @return unknown
     */
    public function stripBBtags($text)
    {
        return preg_replace('#\[(.*[^\]])\](.*)\[/(.*[^\]])\]#', '$2', $text);
    }

    /**
     * convert different linebreaks to \n
     *
     * @param   string $text
     * @return  string $text with unified newlines (\n)
     */
    public function convertlinebreaks($text)
    {
        return preg_replace("#\015\012|\015|\012#", "\n", $text);
    }

    /**
     * remove everything but newlines
     *
     * @param   string $text
     * @return  string $text with newlines
     */
    public function bbcode_stripcontents($text)
    {
        return preg_replace("#[^\n]#", '', $text);
    }

    /**
     * convert [quote] tags
     * @see http://www.christian-seiler.de/projekte/php/bbcode/doc/de
     */
    public function do_bbcode_quote($action, $attributes, $content, $params, $node_object)
    {
        global $_ARRAYLANG;
        if ($action == 'validate') {
            return true;
        }
        if (!isset($attributes['default'])) {
            return '<span class="quote_from">' . $_ARRAYLANG['TXT_FORUM_SOMEONE_UNKNOWN'] . ' ' . $_ARRAYLANG['TXT_FORUM_WROTE'] . '</span><br /><div class="quote">' . $content . '</div>';
        }
        return '<span class="quote_from">' . $attributes['default'] . ' ' . $_ARRAYLANG['TXT_FORUM_WROTE'] . '</span><br /><div class="quote">' . $content . '</div>';
//<p class="quote"> <span class="quote_from">asdf </span><br /><br /> <p class="quote"> <span class="quote_from">qwer </span><br /><br /> yxcv </p>ghk </p>
    }

    /**
     * convert [code] tags
     * @see http://www.christian-seiler.de/projekte/php/bbcode/doc/de
     */
    public function do_bbcode_code($action, $attributes, $content, $params, $node_object)
    {
        if ($action == 'validate') {
            return true;
        }
        return 'Code:<br /><div class="code">' . $content . '</div>';
    }

    /**
     * embed URLs
     * @see http://www.christian-seiler.de/projekte/php/bbcode/doc/de
     */
    public function do_bbcode_url($action, $attributes, $content, $params, $node_object)
    {
//      $urlRegex = '#([a-zA-Z]+://)?(.*)#';
        if ($action == 'validate') {
            if (!isset ($attributes['default'])) {
                return $this->is_valid_url($content);
            } else {
                return $this->is_valid_url($attributes['default']);
            }
        }
        $httpRegex = '#^(http://)?(www\.)?([a-zA-Z][a-zA-Z0-9-/]+\.[a-zA-Z][a-zA-Z0-9-/&\#\+=\?\.;%]+)+#i';
        if (!isset ($attributes['default'])) {
            $content = preg_replace($httpRegex, 'http://$2$3', $content);
            return '<a href="' . htmlspecialchars($content, ENT_QUOTES, CONTREXX_CHARSET) . '">' . htmlspecialchars($content, ENT_QUOTES, CONTREXX_CHARSET) . '</a>';
        }
        $attributes['default'] = preg_replace($httpRegex, 'http://$2$3', $attributes['default']);
        return '<a href="' . htmlspecialchars($attributes['default'], ENT_QUOTES, CONTREXX_CHARSET) . '">' . $content . '</a>';
    }

    /**
     * dummy function which returns true (causes problems otherwise, since it's already been 'regexed' in convertlinks())
     *
     * @param   string $url
     * @return  bool true
     */
    protected function is_valid_url($url) {
        return true;
    }

    /**
     * for embedding images
     * @see http://www.christian-seiler.de/projekte/php/bbcode/doc/de
     */
    public function do_bbcode_img($action, $attributes, $content, $params, $node_object)
    {
        if ($action == 'validate') {
            return true;
        }

        $content = $this->stripBBtags($content);

        if (isset($attributes['w']) && isset($attributes['h'])) {
            return '<img src="' . htmlspecialchars($content, ENT_QUOTES, CONTREXX_CHARSET) . '" height="' . $attributes['h'] . '" width="' . $attributes['w'] . '" alt="user-posted image" border="0" />';
        }
        return '<img src="' . htmlspecialchars($content, ENT_QUOTES, CONTREXX_CHARSET) . '" alt="user-posted image" border="0" />';
    }
}

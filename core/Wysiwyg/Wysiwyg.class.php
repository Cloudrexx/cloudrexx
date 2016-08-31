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
 * Wysiwyg
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 */

namespace Cx\Core\Wysiwyg;
use Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser;

/**
 * Wysiqyg class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @author      Michael Räss <michael.raess@comvation.com>
 * @author      Ueli Kramer <ueli.kramer@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core_wysiwyg
 */

class Wysiwyg
{
    /**
     * options for the different types of wysiwyg editors
     * @var array the types which are available for cloudrexx wysiwyg editors
     */
    private $types = array(
        'small' => array(
            'toolbar' => 'Small',
            'width' => '100%',
            'height' => 200,
            'fullPage' => 'false',
            'extraPlugins' => array(),
        ),
        'full' => array(
            'toolbar' => 'Full',
            'width' => '100%',
            'height' => 450,
            'fullPage' => 'false',
            'extraPlugins' => array(),
        ),
        'fullpage' => array(
            'toolbar' => 'Full',
            'width' => '100%',
            'height' => 450,
            'fullPage' => 'true',
            'extraPlugins' => array(),
        ),
        'bbcode' => array(
            'toolbar' => 'BBCode',
            'width' => '100%',
            'height' => 200,
            'fullPage' => 'false',
            'extraPlugins' => array('bbcode'),
        ),
    );

    /**
     * @var string the value for the textarea html attribute "name"
     */
    private $name;
    /**
     * @var string the value for the textarea html attribute "value"
     */
    private $value;
    /**
     * @var string the type of wysiwyg editor
     */
    private $type;
    /**
     * @var int the language id of current language
     */
    private $langId;
    /**
     * @var array array of extra plugins added for the wysiwyg editor
     */
    private $extraPlugins;

    /**
     * Initialize WYSIWYG editor
     *
     * @param string $name the name content for name attribute
     * @param string $value content for value attribute
     * @param string $type the type of editor to use: possible types are small, full, bbcode
     * @param null|int $langId the language id
     * @param array $extraPlugins extra plugins to activate
     */
    public function __construct($name, $value = '', $type = 'small', $langId = null, $extraPlugins = array())
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = strtolower($type);
        $this->langId = $langId ? intval($langId) : FRONTEND_LANG_ID;
        $this->extraPlugins = $extraPlugins;
    }

    /**
     * Get the html source code for the wysiwyg editor
     *
     * @return string
     */
    public function getSourceCode()
    {
        $mediaBrowserCkeditor = new MediaBrowser();
        $mediaBrowserCkeditor->setOptions(array('type' => 'button', 'style' => 'display:none'));
        $mediaBrowserCkeditor->setCallback('ckeditor_image_callback');
        $mediaBrowserCkeditor->setOptions(array(
                'id' => 'ckeditor_image_button'
            ));

        \JS::activate('ckeditor');
        \JS::activate('jquery');

        $configPath = ASCMS_PATH_OFFSET.substr(\Env::get('ClassLoader')->getFilePath(ASCMS_CORE_PATH.'/Wysiwyg/ckeditor.config.js.php'), strlen(ASCMS_DOCUMENT_ROOT));
        $options = array(
            "customConfig: CKEDITOR.getUrl('".$configPath."?langId=".$this->langId."')",
            "width: '" . $this->types[$this->type]['width'] . "'",
            "height: '" . $this->types[$this->type]['height'] . "'",
            "toolbar: '" . $this->types[$this->type]['toolbar'] . "'",
            "fullPage: " . $this->types[$this->type]['fullPage']
        );

        $extraPlugins = array_merge($this->extraPlugins, $this->types[$this->type]['extraPlugins']);
        if (!empty($extraPlugins)) {
            $options[] = "extraPlugins: '" . implode(',', $extraPlugins) . "'";
        }

        $onReady = "CKEDITOR.replace('".$this->name."', { %s });";
        \JS::registerCode('
            $J(function(){
                '.sprintf($onReady, implode(",\r\n", $options)).'
            });
        ');

        return $mediaBrowserCkeditor->getXHtml('mediabrowser').'<textarea name="'.$this->name.'" style="width: 100%; height: ' . $this->types[$this->type]['height'] . 'px">'.$this->value.'</textarea>';
    }

    /**
     * Get safe BBCode
     *
     * @param string $bbcode the unsafe BBCode
     * @param bool $html return as html code
     * @return string
     */
    public static function prepareBBCodeForDb($bbcode, $html = false)
    {
        $bbcode = strip_tags($bbcode);
        if ($html) {
            $bbcode = self::prepareBBCodeForOutput($bbcode);
        }
        return contrexx_input2db($bbcode);
    }

    /**
     * Convert BBCode to HTML
     *
     * This code comes from the forum module, feel free to rewrite
     *
     * @param string $bbcode the BBCode which should be a html output
     * @return string the xhtml output
     */
    public static function prepareBBCodeForOutput($bbcode)
    {
        $BBCodeHandler = new \Cx\Core\Wysiwyg\BBCodeHandler();
        return $BBCodeHandler->parse($bbcode);
    }

    /**
     * Alias for the method getSourceCode()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getSourceCode();
    }

    /**
     * @param int $langId
     */
    public function setLangId($langId)
    {
        $this->langId = $langId;
    }

    /**
     * @return int
     */
    public function getLangId()
    {
        return $this->langId;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = strtolower($type);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param array $extraPlugins
     */
    public function setExtraPlugins($extraPlugins)
    {
        $this->extraPlugins = $extraPlugins;
    }

    /**
     * @return array
     */
    public function getExtraPlugins()
    {
        return $this->extraPlugins;
    }
}

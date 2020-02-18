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
 * Class Uploader
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_uploader
 */

namespace Cx\Core_Modules\Uploader\Model\Entity;


use Cx\Core\Core\Controller\Cx;
use Cx\Core\Html\Sigma;
use Cx\Lib\FileSystem\File;
use Cx\Model\Base\EntityBase;

/**
 * Class Uploader
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_uploader
 */
class Uploader extends EntityBase
{

    /**
     * The uploader id
     *
     * @var int
     */
    protected $id;

    const UPLOADER_TYPE_MODAL = 'Modal';
    const UPLOADER_TYPE_INLINE = 'Inline';

    /**
     * @var Array
     */
    protected $options = array();

    /**
     * @var Cx
     */
    protected $cx;

    function __construct()
    {
        $this->cx = Cx::instanciate();
        $this->getComponentController()->addUploader($this);
        if (!isset($_SESSION['uploader'])) {
            $_SESSION['uploader'] = array();
        }
        if (!isset($_SESSION['uploader']['handlers'])) {
            $_SESSION['uploader']['handlers'] = array();
        }
        $i       = self::generateId();
        $_SESSION['uploader']['handlers'][$i] = array('active' => true);
        $this->id = $i;
        $this->options = array(
            'data-pl-upload',
            'data-uploader-id' => $this->id,
            'class' => 'uploader-button button',
            'uploader-type' => self::UPLOADER_TYPE_MODAL
        );
    }

    /**
     * Saves the callback in the session.
     *
     * @param $callback
     *
     * @return $this
     */
    function setFinishedCallback($callback)
    {
        if (!isset($_SESSION['uploader']['handlers'])) {
            $_SESSION['uploader']['handlers'] = array();
        }
        if (!isset($_SESSION['uploader']['handlers'][$this->id])) {
            $_SESSION['uploader']['handlers'][$this->id] = array();
        }
        $_SESSION['uploader']['handlers'][$this->id]['callback'] = $callback;
        return $this;
    }

    /**
     * @param $options
     */
    function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);

        if (!isset($_SESSION['uploader']['handlers'])) {
            $_SESSION['uploader']['handlers'] = array();
        }
        if (!isset($_SESSION['uploader']['handlers'][$this->id])) {
            $_SESSION['uploader']['handlers'][$this->id] = array();
        }
        if (!isset($_SESSION['uploader']['handlers'][$this->id]['config'])) {
            $_SESSION['uploader']['handlers'][$this->id]['config'] = array();
        }

        //set upload file limit
        if (isset($this->options['upload-limit'])) {
            $_SESSION['uploader']['handlers'][$this->id]['config']['upload-limit'] = $this->options['upload-limit'];
        }
        //set custom allowed extensions
        if (isset($this->options['allowed-extensions'])) {
            $_SESSION['uploader']['handlers'][$this->id]['config']['allowed-extensions'] = $this->options['allowed-extensions'];
        }
    }

    /**
     * @param $option
     *
     * @return string
     */
    function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        }
        return null;
    }

    /**
     * Get all options as a string.
     * @return string
     */
    function getOptionsString()
    {
        $optionsString = '';
        foreach ($this->options as $key => $value) {
            if (is_int($key)) {
                $optionsString .= $value . ' ';
            } else {
                if (!in_array($key, array('class', 'id', 'style','title','value'))){
                    if ( strpos($key, 'data') !== 0){
                        $key = 'data-'.$key;
                    }
                }
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $optionsString .= $key . "='" . $value . "'";
            }
        }
        return $optionsString;
    }

    /**
     * @param string $buttonName
     *
     * @return string
     */
    function getXHtml($buttonName = 'Upload')
    {
        // set system upload file size limit,
        // if no file size limit has been set
        if (!$this->getMaxFileSize()) {
            $uploadFileSizeLimit =
                \Cx\Core\Setting\Controller\Setting::getValue(
                    'uploadFileSizeLimit',
                    'Config'
                );
            $this->setMaxFileSize($uploadFileSizeLimit);
        }

        $inline = '';
        if ($this->options['uploader-type'] == self::UPLOADER_TYPE_INLINE){
            $this->addClass('uploader-button-hidden');
            $inline = $this->getContainer();
        }
        return '<button ' . $this->getOptionsString() . ' disabled>' . $buttonName . '</button>' .$inline;
    }


    function getContainer(){
        $path = $this->cx->getCodeBaseCoreModulePath() . '/Uploader/View/Template/Backend/Uploader'.$this->options['uploader-type'].'.html';
        $template = new Sigma();
        $template->loadTemplateFile($path);
        $template->setVariable(
            array(
                'UPLOADER_ID' => $this->id,
                'UPLOADER_CODE' =>
                    file_get_contents($this->cx->getCodeBaseCoreModulePath()
                        . '/Uploader/View/Template/Backend/Uploader.html')
            )
        );
        return $template->get();
    }

    /**
     * Set a javascript callback on a global function.
     *
     * @param String $string
     *
     * @return $this
     */
    public function setCallback($string)
    {
        $this->setOptions(array('data-on-file-uploaded' => $string));
        return $this;
    }

    /**
     * Set a file upload limit.
     * @param $limit
     *
     * @return self
     */
    public function setUploadLimit($limit){
        $this->setOptions(array('data-upload-limit' => $limit));
        return $this;
    }

    /**
     * Add a class to the button
     *
     * @param $class
     *
     * @return self
     */
    public function addClass($class){
        $classString = $this->getOption('class');
        $classes = explode(' ',$classString);
        if (!in_array($class, $classes)){
            $classes[] = $class;
        }
        $this->setOptions(array('class' => implode(' ', $classes)));
        return $this;
    }

    /**
     * Add additional data for the uploader
     *
     * @param $data
     *
     * @return $this
     */
    public function setData($data)
    {
        if (!isset($_SESSION['uploader']['handlers'])) {
            $_SESSION['uploader']['handlers'] = array();
        }
        if (!isset($_SESSION['uploader']['handlers'][$this->id])) {
            $_SESSION['uploader']['handlers'][$this->id] = array();
        }
        $_SESSION['uploader']['handlers'][$this->id]['data'] = $data;
        return $this;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->options['uploader-type'];
    }

    /**
     * @param string $type
     */
    public function setType($type) {
        $this->options['uploader-type'] = $type;
    }

    /**
     * Set the maximum file size for the upload
     *
     * @param string $type
     */
    public function setMaxFileSize($type) {
        $this->options['pl-Max-File-Size'] = $type;
    }

    /**
     * Get the currently set upload file size limit
     *
     * @return  string  Set upload file size limit
     */
    public function getMaxFileSize() {
        return $this->options['pl-Max-File-Size'];
    }

    public static function generateId(){
        $uploaders = $_SESSION['uploader']['handlers'];
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < 10; $i++) {
            $randstring .= $characters[rand(0, strlen($characters) - 1)];
        }
        if (array_key_exists($randstring, $uploaders)){
            return self::generateId();
        }
        return $randstring;
    }

    /**
     * Return's the uploader id
     *
     * @return string Uploader id
     */
    public function getId()
    {
        return $this->id;
    }
}

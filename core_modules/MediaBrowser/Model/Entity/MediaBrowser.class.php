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
 * Class MediaBrowser
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_mediabrowser
 */
namespace Cx\Core_Modules\MediaBrowser\Model\Entity;

use Cx\Core\Html\Sigma;
use Cx\Model\Base\EntityBase;

/**
 * Class MediaBrowser
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 */
class MediaBrowser extends EntityBase
{
    protected static $optionValues = [
        'views',
        'startview',
        'startmediatype',
        'mediatypes',
        'multipleselect',
        'modalopened',
        'modalClosed'
    ];

    /**
     * The set options for the mediabrowser
     * @var Array
     */
    protected $options = array();

    /**
     * Create new instance of mediabrowser and register in componentcontroller.
     *
     * @throws \Cx\Core\Core\Model\Entity\SystemComponentException
     * @throws \Exception
     */
    function __construct()
    {
        $this->getComponentController()->addMediaBrowser($this);

        $this->options = array(
            'data-cx-mb',
            'class' => "mediabrowser-button button"
        );
    }


    /**
     * Set a mediabrowser option
     *
     * @param $options
     */
    function setOptions($options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get a option
     *
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
     * Set a Javascript callback when the modal gets closed
     *
     * @param $callback array Callback function name
     */
    function setCallback($callback)
    {
        $this->options['data-cx-Mb-Cb-Js-Modalclosed'] = $callback;
    }

    /**
     * Get all Options as a String
     *
     * @return string
     */
    function getOptionsString()
    {
        $optionsString = "";
        foreach ($this->options as $key => $value) {
            if (is_int($key)) {
                $optionsString .= $value . ' ';
            } else {
                if (in_array($key, self::$optionValues)){
                    $key = 'data-cx-Mb-'.$key;
                }
                $optionsString .= $key . '="' . $value . '" ';
            }
        }
        return $optionsString;
    }

    /**
     * Get the rendered mediabrowser button
     *
     * @param string $buttonName
     *
     * @return string
     */
    function getXHtml($buttonName = "MediaBrowser")
    {
        $button = new Sigma();
        $button->loadTemplateFile($this->cx->getCodeBaseCoreModulePath() . '/MediaBrowser/View/Template/MediaBrowserButton.html');
        $button->setVariable(array(
            'MEDIABROWSER_BUTTON_NAME' => $buttonName,
            'MEDIABROWSER_BUTTON_OPTIONS' =>  $this->getOptionsString()
        ));
        return $button->get();
    }

    /**
     * Add a class to the button
     *
     * @param $class
     *
     * @return self
     */
    public function addClass($class) {
        $this->addOption('class', $class);
        return $this;
    }


    protected function addOption($optionName, $value) {
        $option  = $this->getOption($optionName);
        $optionValues = explode(' ', $option);
        if (!in_array($value, $optionValues)) {
            $optionValues[] = $value;
        }
        $this->setOptions(array($optionName => implode(' ', $optionValues)));
    }
}

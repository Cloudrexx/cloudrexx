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
 * HTML Tag helpers
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 */

/**
 * HTML Tag Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 */
class HtmlTag
{
    /**
     * The tag name, like 'div' or 'img'
     * @var   string
     */
    private $name = false;
    /**
     * The next sibling of the tag
     *
     * May be empty, a HtmlTag object or a string
     * @var   mixed
     */
    private $next_sibling = false;
    /**
     * The first child of the tag
     *
     * May be empty, a HtmlTag object or a string
     * @var   mixed
     */
    private $first_child = false;
    /**
     * The attributes of the tag
     * @var   array
     */
    private $attributes = array();


    /**
     * Construct a Tag object
     *
     * The attributes array, if specified, must be of the form
     *  array(
     *    attribute name => attribute value,
     *    ... more ...
     *  )
     * @param   string    $name         The tag name
     * @param   array     $attributes   The optional list of attributes
     */
    function __construct($name, $attributes=array())
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }


    /**
     * Returns the name of the tag object
     * @return  string          The tag name
     */
    function getName()
    {
        return $this->name;
    }


    /**
     * Returns the attributes array
     * @return  array           The attribute array
     */
    function getAttributes()
    {
        return $this->attributes;
    }


    /**
     * Returns the value for the given attribute name
     *
     * If the attribute with the given name is not present,
     * the empty string is returned.
     * @param   string      $name     The attribute name
     * @return  string                The attribute value, or the empty string
     */
    function getAttributeValue($name)
    {
        return
            (isset($this->attributes[$name])
                ? $this->attributes[$name] : '');
    }


    /**
     * Sets the value of the given attribute
     *
     * If the value is empty, the attribute is removed.
     * @param   string    $name       The attribute name
     * @param   string    $value      The attribute value
     * @return  void
     */
    function setAttribute($name, $value)
    {
        if (empty($value))
            unset($this->attributes[$name]);
        else
            $this->attributes[$name] = $value;
    }


    /**
     * Returns the string representation of the tag
     * @return  string                The string representation of the tag
     */
    function toString()
    {
        return
            '<'.$this->name.
            $this->getAttributeString().
            ($this->first_child
              ? '>'.
                (is_a('HtmlTag', $this->first_child)
                  ? $this->first_child->toString
                  : $this->first_child
                )
              : ''
            );
    }


    /**
     * Returns the string representation of the tag's attributes
     *
     * If there are no attributes, the empty string is returned.
     * Otherwise, a leading space is prepended to the string.
     * @return  string                The string representation of the tags'
     *                                attributes
     */
    function getAttributeString()
    {
        if (empty($this->attributes)) return '';
        $attributes = '';
        foreach ($this->attributes as $name => $value) {
            $attributes .= ' '.$name.'="'.$value.'"';
        }
        return $attributes;
    }

}

?>

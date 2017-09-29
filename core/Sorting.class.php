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
 * Provides methods to create sorted tables
 *
 * Lets you create clickable table headers which indicate the sorting
 * field and direction.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 */

/**
 * Provides methods to create sorted tables
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 */
class Sorting
{
    /**
     * Default URL parameter name for the sorting order
     *
     * You *MUST* specify this yourself using {@see setOrderParameterName()}
     * when using more than one Sorting at a time!
     */
    const DEFAULT_PARAMETER_NAME = 'x_order';

    /**
     * Regular expression used to match the field name and order direction
     * used in an ORDER BY statement
     *
     * Matches the table name ($1), field name ($2), and direction ($3).
     * Ignores leading and trailing "stuff".
     */
    const REGEX_ORDER_FIELD = '/(?:`?(\w+)`?\.)?`?(\w+)`?(?:\s+(asc|desc))?/i';

    /**
     * The base page URI to use.
     *
     * The sorting parameters will be appended to this string and used
     * to build the header array.
     * Note that, as this is only used in links, the URI is stored with any
     * "&"s replaced by "&amp;" already!  See {@see setUri()}.
     * @var string
     */
    private $baseUri = null;

    /**
     * The array of database field names and header field names.
     *
     * Note that the first element will be the default sorting field.
     * @var array
     */
    private $arrField = null;

    /**
     * Flag indicating the default order
     *
     * if true, the default order is ascending, or descending otherwise.
     * @var boolean
     */
    private $flagDefaultAsc = null;

    /**
     * The order table name.  See {@link setOrder()}.
     * @var string
    private $orderTable = null;
     */

    /**
     * The order field name.  See {@link setOrder()}.
     *
     * Note that this may include the table name, and even the order direction!
     * @var string
     */
    private $orderField = null;

    /**
     * The order direction.  See {@link setOrder()}.
     * @var string
     */
    private $orderDirection = null;

    /**
     * The order parameter name for this Sorting
     */
    private $orderUriParameter = null;


    /**
     * Constructor
     *
     * Note that the base page URI is handed over by reference and that
     * the order parameter name is removed from that, if present.
     * @param   string  $baseURI        The base page URI, by reference
     * @param   array   $arrField       The field names and corresponding
     *                                  header texts
     * @param   boolean $flagDefaultAsc The flag indicating the default order
     *                                  direction. Defaults to true (ascending).
     * @param   string  $orderUriParameter  The optional name for the URL
     *                                  parameter.
     *                                  Defaults to self::DEFAULT_PARAMETER_NAME
     * @param   string  $defaultOrder   The optional default order.
     *                                  Applies only when none is set in the
     *                                  session already, and if it's a valid
     *                                  field index.
     * @return  Sorting
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function __construct(
        &$baseUri, $arrField, $flagDefaultAsc=true,
        $orderUriParameter=self::DEFAULT_PARAMETER_NAME,
        $defaultOrder=null
    ) {
        $this->flagDefaultAsc = $flagDefaultAsc;
        // Default order parameter name.  Change if needed.
        $this->orderUriParameter = $orderUriParameter;
        $this->setUri($baseUri);
        $this->arrField = array();
        foreach ($arrField as $key => $name) {
            // Skip empty string names
            if ($name === '') continue;
            $index = self::getFieldindex($key);
            // Skip fields with invalid indices
            if ($index) {
                $this->arrField[$index] = $name;
            } else {
\DBG::log("Sorting::__construct(): WARNING: index $key does not match ".self::REGEX_ORDER_FIELD);
            }
        }
//echo("Sorting::__construct(baseUri=$baseUri, arrField=$arrField, flagDefaultAsc=$flagDefaultAsc, orderUriParameter=$orderUriParameter):<br />"."Field names: ".var_export($this->arrField, true)."<br />"."<hr />");

        // The table will be sorted by the first field, according to the
        // direction flag, unless a default order is specified.
        // The default is overridden by the order stored in the session, if any.
        // If the order parameter is present in the $_REQUEST array,
        // however, it is used instead.
        // Note that $_REQUEST is the empty array when we're called by the
        // ComponentController, e.g. in preFinalize()!
        $orderRequested =
            (isset($_REQUEST[$this->orderUriParameter])
                ? $_REQUEST[$this->orderUriParameter]
                : (isset($_GET[$this->orderUriParameter])
                    ? $_GET[$this->orderUriParameter]
                    : (isset($_POST[$this->orderUriParameter])
                        ? $_POST[$this->orderUriParameter]
                        : null)));
        $this->setOrder(
            (empty($orderRequested)
                ? (empty($_SESSION['sorting'][$this->orderUriParameter])
                    ? (empty($defaultOrder)
                        ? current($this->arrField)
                        : $defaultOrder)
                    : $_SESSION['sorting'][$this->orderUriParameter])
                : $orderRequested
            )
        );
//\DBG::log("Sorting::__construct(): baseUri=$baseUri");
//\DBG::log("Sorting::__construct(): arrField=".var_export($this->arrField, true));
//\DBG::log("Sorting::__construct(): flagDefaultAsc=$flagDefaultAsc");
//\DBG::log("Sorting::__construct(): Made order: ".$this->getOrder());
    }


    /**
     * Set the order parameter name to be used for this Sorting
     * @param   string    $parameter_name   The parameter name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function setOrderParameterName($parameter_name)
    {
        $this->orderUriParameter = $parameter_name;
    }


    /**
     * Returns the order parameter name used for this Sorting
     * @return  string                      The parameter name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getOrderParameterName()
    {
        return $this->orderUriParameter;
    }


    /**
     * Returns the base URI with HTML entities encoded
     *
     * If the optional $field parameter contains any valid field name,
     * the sorting and direction for that field is appended.
     * @param   string    $field    The optional field name
     * @return  string              The URI
     */
    function getUri_entities($field=null)
    {
        if (empty($field)) $field = $this->orderField;
        if (empty($this->arrField[$field])) {
\DBG::log("Sorting::getUri_entities($field): ERROR: unknown field name");
            return '';
        }
        $uri = $this->baseUri;
        Html::replaceUriParameter($uri, $this->getOrderUriEncoded($field));
        return $uri;
    }


    /**
     * Sets the base URI
     * @param   string    $uri      The URI
     */
    function setUri($uri)
    {
        // Remove the order parameter name argument from the base URI
        Html::stripUriParam($uri, $this->orderUriParameter);
        $this->baseUri = Cx\Core\Routing\Url::encode_amp($uri);
    }


    /**
     * Returns an array of strings to display the table headers.
     *
     * Uses the order currently stored in the object, as set by
     * setOrder().
     * The array is, of course, in the same order as the arrays of
     * field and header names used.
     * @return  array               The array of clickable table headers.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getHeaderArray()
    {
        $arrHeader = array();
        foreach (array_keys($this->arrField) as $field) {
            $arrHeader[] = $this->getHeaderForField($field);
        }
        return $arrHeader;
    }


    /**
     * Returns a string to display the table header for the given field name.
     *
     * Uses the order currently stored in the object, as set by setOrder().
     * The optional $direction overrides the current state of the
     * order direction.
     * @param   string  $field      The field name
     * @param   string  $direction  The optional direction
     * @return  string              The string for a clickable table
     *                              header field.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getHeaderForField($field, $direction=null)
    {
        global $_CORELANG;
//\DBG::log("Sorting::getHeaderForField(field=$field): field names: ".var_export($this->arrField, true));

        // The field may consist of the database field name
        // enclosed in backticks, plus optional direction
        $index = self::getFieldindex($field);
//\DBG::log("Sorting::getHeaderForField($field): Fixed");
        if (empty($index)) {
\DBG::log("Sorting::getHeaderForField($field): WARNING: Cannot make index for $field");
            return '';
        }
        $direction_orig = null;
        if ($direction) {
            $direction_orig = $this->orderDirection;
            $this->setOrderDirection($direction);
            $direction = $this->getOrderDirection(); // Fixed case!
        }
//\DBG::log("Sorting::getHeaderForField($field): direction $direction, direction_orig $direction_orig, orderDirection $this->orderDirection");
        $uri = $this->baseUri;
        Html::replaceUriParameter($uri,
            ($direction
                ? $this->getOrderUriEncoded($field)
                : $this->getOrderReverseUriEncoded($field)));
//\DBG::log("Sorting::getHeaderForField($field): URI $uri");
        $strHeader = Html::getLink(
            $uri,
            ($direction
                ? sprintf(
                    $_CORELANG['TXT_CORE_SORTING_FORMAT_'.$this->orderDirection],
                    $this->arrField[$index])
                : $this->arrField[$index]
            ).
            ($this->orderField == $index
            && ($direction === null || $direction === $direction_orig)
              ? '&nbsp;'.$this->getOrderDirectionImage() : ''));
//echo("Sorting::getHeaderForField(fieldName=$field): made header: ".htmlentities($strHeader)."<br />");
        if ($direction_orig) {
            $this->orderDirection = $direction_orig;
        }
        return $strHeader;
    }


    /**
     * Returns an HTML img tag with the icon representing the current
     * order direction
     *
     * Note that the decision where to include the icon or not must
     * be made by the code calling.
     * @return    string        The HTML img tag for the sorting direction icon
     */
    function getOrderDirectionImage()
    {
        global $_CORELANG;

        $orderDirectionString =
            ($this->orderDirection == 'ASC'
                ? $_CORELANG['TXT_CORE_SORTING_ASCENDING']
                : $_CORELANG['TXT_CORE_SORTING_DESCENDING']
            );
        $img = \Cx\Core\Core\Controller\Cx::instanciate()->getClassLoader()->getWebFilePath(
            \Cx\Core\Core\Controller\Cx::instanciate()->getCoreFolderName() . '/Core/View/Media/icons/'.
            strtolower($this->orderDirection).
            '.png'
        );
        return
            '<img src="'.$img.'" border="0" alt="'.$orderDirectionString.
            '" title="'.$orderDirectionString.'" />';
    }

    /**
     * Parses all available ordering options into the blocks available
     *
     * Lower case $blockBase is the base for all template block names.
     * The UPPERCASE version of $blockBase is the name of the (only)
     * placeholder.
     *
     * Examples for $blockBase = 'shop_product_order':
     *
     * Standard sorting headers, alternating between ascending and descending.
     * Includes all available criteria.
     * The block name is shop_product_order, the placeholder SHOP_PRODUCT_ORDER.
     *
     * <div class="product_orders">
     *   <!-- BEGIN shop_product_order -->
     *   <div class="product_order">{SHOP_PRODUCT_ORDER}</div>
     *   <!-- END shop_product_order -->
     * </div>
     *
     * Custom sorting headers, fixed or alternating
     * Column and functionality are determined by the block name:
     *   "shop_product_order_" + field name [ + "_" + optional fixed direction ]
     * Note that non-letter characters in the field name (index) are replaced
     * by underscores, e.g. an order field declaration of "`product`.`ord`"
     * is stripped of the backticks by getFieldIndex(), resulting in
     * "product.ord" stored in the field array, then substituted by
     * "product_ord" in this method.
     *
     * <div class="product_orders">
     *   <!-- BEGIN shop_product_order_product_ord -->
     *   <div class="product_order">{SHOP_PRODUCT_ORDER}</div>
     *   <!-- END shop_product_order_product_ord -->
     *   <!-- BEGIN shop_product_order_name_asc -->
     *   <div class="product_order">{SHOP_PRODUCT_ORDER}</div>
     *   <!-- END shop_product_order_name_asc -->
     *   <!-- BEGIN shop_product_order_name_desc -->
     *   <div class="product_order">{SHOP_PRODUCT_ORDER}</div>
     *   <!-- END shop_product_order_name_desc -->
     *   <!-- BEGIN shop_product_order_bestseller_desc -->
     *   <div class="product_order">{SHOP_PRODUCT_ORDER}</div>
     *   <!-- END shop_product_order_bestseller_desc -->
     * </div>
     *
     * Note that invalid field names (not matching REGEX_ORDER_FIELD),
     * as well as empty string names (labels) are skipped.
     * @param   Cx\Core\Html\Sigma  $template   The Template
     * @param   string              $blockBase  The block base name
     */
    public function parseHeaders(Cx\Core\Html\Sigma $template, $blockBase)
    {
        $blockBase = strtolower($blockBase);
        $placeholder = strtoupper($blockBase);
        foreach ($this->getHeaderArray() as $header) {
            if ($template->blockExists($blockBase)) {
                $template->setVariable($placeholder, $header);
                $template->parse($blockBase);
            }
        }
        foreach (array_keys($this->arrField) as $field) {
            $index = $this->getFieldindex($field);
            $block = $blockBase . '_' .
                preg_replace('/\W/', '_', $index);
            if ($template->blockExists($block)) {
//\DBG::log("Sorting index $index, block $block FOUND");
                $template->setVariable(
                    $placeholder,
                    $this->getHeaderForField($field));
                $template->parse($block);
            }
            foreach (array('asc', 'desc') as $direction) {
                $block_directed = $block . '_' . $direction;
//\DBG::log("Sorting index $index, block $block_directed");
                if ($template->blockExists($block_directed)) {
//\DBG::log("Sorting index $index, block $block_directed FOUND");
                    $template->setVariable(
                        $placeholder,
                        $this->getHeaderForField($field, $direction));
                    $template->parse($block_directed);
                }
            }
        }
    }

    /**
     * Returns the current order string (SQL-ish syntax)
     *
     * Note that this adds backticks around the order table (if present)
     * and field name.  The string looks like
     *  "`table`.`field` dir"
     * or
     *  "`field` dir"
     * @return  string
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getOrder()
    {
        list($field, $direction) = self::getFieldDirection($this->orderField);
        if (empty($direction)) $direction = $this->orderDirection;
        return "$field $direction";
    }


    /**
     * Sets the order string (SQL-ish syntax)
     * @param   string  $order      The order string
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function setOrder($order)
    {
        $index = self::getFieldindex($order);
        list(, $direction) = self::getFieldDirection($order);
//\DBG::log("Sorting::setOrder($order): Index /$index/, Direction /$direction/");
        if (empty($direction)) $direction = $this->orderDirection;
        // If the order field is invalid or isn't in the list of field names,
        // fall back to default
        if (   !$index
            || (   empty($this->arrField[$index])
                && empty($this->arrField["$index $direction"]))) {
            $index = key($this->arrField);
//\DBG::log("Sorting::setOrder($order): arrField: ".var_export($this->arrField, true));
//\DBG::log("Sorting::setOrder($order): Fallback to default Index /$index/, Direction /$direction/");
        }
        switch ($direction) {
          case 'ASC':
          case 'DESC':
//\DBG::log("Sorting::setOrder($order): Direction $direction OK");
            break;
          default:
//\DBG::log("Sorting::setOrder($order): Direction $direction NOK");
            $direction =
                ($this->flagDefaultAsc ? 'ASC' : 'DESC');
        }
        $this->orderField     = $index;
        $this->orderDirection = $direction;
//\DBG::log("Sorting::setOrder($order): setting order to /$index $direction/");
        if (!isset($_SESSION['sorting'])) {
            $_SESSION['sorting'] = array();
        }
        $_SESSION['sorting'][$this->orderUriParameter] = $order;
    }


    /**
     * Returns the sorting order string in URI encoded format
     *
     * The returned string contains both the parameter name,
     * and the current order string value.
     * @param   string  $field    The optional order field
     * @return  string            URI encoded order string
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getOrderUriEncoded($field='')
    {
//\DBG::log("Sorting::getOrderUriEncoded($field): Entered");
        $index = self::getFieldindex($field);
        list($field, $direction) = self::getFieldDirection($field);
//\DBG::log("Sorting::getOrderUriEncoded(): index $index, field $field, direction $direction");
        if (!$index) {
            $index = $this->orderField;
        }
        if (!$direction) {
            $direction = $this->orderDirection;
        }
        return
// TODO: I guess that it's better to leave it to another piece of code
// whether to add '?' or '&'...?
            //'&amp;'.
            $this->orderUriParameter.
            '='.urlencode($index.' '.$direction);
    }


    /**
     * Returns the sorting order string in URI encoded format
     *
     * The returned string contains both the parameter name, 'order',
     * and the current order string value.
     * It is ready to be used in an URI in a link.
     * @return  string          URI encoded order string
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getOrderReverseUriEncoded($field='')
    {
        $index = self::getFieldindex($field);
        list($field, $direction) = self::getFieldDirection($field);
        if (!$index) {
            $index = $this->orderField;
        }
        if (!$direction) {
            $direction = $this->orderDirection;
        }
        return $this->getOrderUriEncoded(
            $index.' '.self::getOrderDirectionReverse($direction));
    }


    /**
     * Returns the sorting direction string
     *
     * This is either 'ASC' or 'DESC'.
     * @return  string          order direction string
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getOrderDirection()
    {
        return $this->orderDirection;
    }
    /**
     * Set the sorting direction string
     *
     * $direction defaults to 'ASC' and may be left empty, or set
     * to 'ASC' or 'DESC'.
     * Any other value is ignored and the default used instead.
     * @param   string    $direction    The optional order direction string
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function setOrderDirection($direction='ASC')
    {
        $this->orderDirection =
            (strtoupper(trim($direction)) == 'DESC' ? 'DESC' : 'ASC');
    }


    /**
     * Returns the reverse sorting direction string
     *
     * This is either 'ASC' or 'DESC'.
     * If empty, the $direction parameter defaults to this objects'
     * $orderDirection variable.
     * @param   string    $direction    The optional order direction
     * @return  string                  The reverse order direction
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getOrderDirectionReverse($direction=null)
    {
        if (empty($direction)) {
            $direction = $this->orderDirection;
        }
        switch (strtoupper(trim($direction))) {
          case 'ASC':  return 'DESC';
          case 'DESC': return 'ASC';
        }
        return ($this->flagDefaultAsc ? 'DESC' : 'ASC');
    }


    /**
     * Returns the current order field name
     * @return  string      The field name
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function getOrderField()
    {
        return $this->orderField;
    }


    /**
     * Returns the field array as provided to the constructor
     * @return  array         The field array
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @see     __construct()
     * @see     $arrField
     */
    function getFieldArray()
    {
        return $this->arrField;
    }


    /**
     * Extracts table plus field name, and the direction from an order
     * definition in SQL syntax and returns them in an array
     *
     * The array looks like
     *  array(
     *    0 => [`table`.]`field`,
     *    1 => direction,
     *  )
     * Note that if the table name is not found, it is omitted in the
     * respective array element.  No dot is included in this case.
     * The direction may be missing, in which case element #1 is set
     * to the empty string.
     * @param   string    $order_sql    The order in SQL syntax
     * @return  array                   The array with table plus field name
     *                                  and direction on success,
     *                                  null otherwise
     */
    static function getFieldDirection($order_sql)
    {
        $match = array();
        if (preg_match(self::REGEX_ORDER_FIELD, $order_sql, $match)) {
            return array(
                0 => (empty($match[1]) ? '' : '`'.$match[1].'`.').
                     '`'.$match[2].'`',
                1 => (empty($match[3]) ? '' : $match[3])
            );
        }
        return null;
    }


    /**
     * Extracts table name, field name, and direction from an order
     * definition in SQL syntax and returns them in a string
     *
     * The string is solely intended for use as an index of the object's
     * field array.
     * It has the form
     *  "[table.]field[ direction]
     * Note that if the table name is not found, it is omitted in the
     * respective array element.  No dot is included in this case.
     * Similarly, if the direction cannot be extracted from the string,
     * no space is added either.
     * @param   string    $order_sql    The order in SQL syntax
     * @return  string                  The field array index on success,
     *                                  null otherwise
     */
    static function getFieldindex($order_sql)
    {
        $match = array();
        if (preg_match(self::REGEX_ORDER_FIELD, $order_sql, $match)) {
            // Index the fields with as much information as requested;
            // "field", "field dir", "table.field", or even "table.field dir"
            return
                (empty($match[1]) ? '' : $match[1].'.').
                $match[2];
//                .(empty($match[3]) ? '' : ' '.$match[3]);
        }
        return null;
    }

}

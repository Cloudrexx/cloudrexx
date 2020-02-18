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
 * Shop Product class
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Product as available in the Shop.
 *
 * Includes access methods and data layer.
 * Do not, I repeat, do not access private fields, or even try
 * to access the database directly!
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 */
class Product
{
    /**
     * Text keys
     */
    const TEXT_NAME  = 'product_name';
    const TEXT_SHORT = 'product_short';
    const TEXT_LONG  = 'product_long';
    const TEXT_CODE  = 'product_code';
    const TEXT_URI   = 'product_uri';
    const TEXT_KEYS  = 'product_keys';

    /**
     * @var     string          $code               Product code
     * @access  private
     */
    private $code = null;
    /**
     * @var     string          $category_id         ShopCategory ID or IDs of the Product
     * @access  private
     */
    private $category_id = '';
    /**
     * @var     string          $name               Product name
     * @access  private
     */
    private $name = null;
    /**
     * @var     Distribution    $distribution       Distribution type
     * @access  private
     */
    private $distribution = 'delivery';
    /**
     * @var     double          $price              Product price
     * @access  private
     */
    private $price = 0.00;
    /**
     * @var     integer         $ord              Sorting order of the Product
     * @access  private
     */
    private $ord = 1;
    /**
     * @var     integer         $weight             Product weight (in grams)
     * @access  private
     */
    private $weight = 0;
    /**
     * @var     integer         $id                 The Product ID
     * @access  private
     */
    private $id = null;
    /**
     * The status is either active (true), or inactive (false).
     * @var     boolean         $active             Product active status
     * @access  private
     */
    private $active = true;
    /**
     * @var     string          $pictures           Product pictures
     * @access  private
     */
    private $pictures = null;
    /**
     * @var     double          $resellerprice      Product price for resellers
     * @access  private
     */
    private $resellerprice = 0.00;
    /**
     * @var     string            $short        Product short description
     * @access  private
     */
    private $short = null;
    /**
     * @var     string          $long        Product long description
     * @access  private
     */
    private $long = null;
    /**
     * @var     integer         $stock              Product stock
     * @access  private
     */
    private $stock = 10;
    /**
     * @var     boolean         $stock_visible     Product stock visibility
     * @access  private
     */
    private $stock_visible = false;
    /**
     * @var     double          $discountprice      Product discount price
     * @access  private
     */
    private $discountprice = 0.00;
    /**
     * @var     boolean         $discount_active     Product is special offer
     * @access  private
     */
    private $discount_active = false;
    /**
     * @var     boolean         $b2b              Product available for b2b
     * @access  private
     */
    private $b2b = true;
    /**
     * @var     boolean         $b2c              Product available for b2c
     * @access  private
     */
    private $b2c = true;
    /**
     * @var     string          $date_start          Product start date
     * @access  private
     */
    private $date_start = null;
    /**
     * @var     string          $date_end            Product end date
     * @access  private
     */
    private $date_end = null;
    /**
     * @var     integer         $manufacturer_id     Product manufacturer ID
     * @access  private
     */
    private $manufacturer_id = 0;
    /**
     * @var     string          $uri       Product external link
     * @access  private
     */
    private $uri = null;
    /**
     * @var     integer         $vat_id              Product VAT ID
     * @access  private
     */
    private $vat_id = 0;
    /**
     * The Product flags
     * @var string
     */
    private $flags = null;
    /**
     * The assigned (frontend) user group IDs
     *
     * Comma separated list
     * @var string
     */
    private $usergroup_ids = null;
    /**
     * The count type discount group ID
     * @var     integer
     */
    private $group_id = 0;
    /**
     * The article group ID
     * @var     integer
     */
    private $article_id = 0;
    /**
     * The list of keywords
     * @var     string
     */
    private $keywords = null;
    /**
     * @var     array   $arrRelations   The relation array
     * @access  private
     */
    private $arrRelations = null;
    /**
     * Is defined, when a minimum order quantity should be checked
     * @var integer
     */
    private $minimum_order_quantity = null;


    /**
     * Create a Product
     *
     * If the optional argument $id is set, the corresponding
     * Product is updated, if it exists.  Otherwise, a new Product is created.
     * Set the remaining object variables by calling the appropriate
     * access methods.
     * @access  public
     * @param   string  $code           The Product code
     * @param   string  $category_id    The ShopCategory ID or IDs of the Product
     *                                  (comma separated)
     * @param   string  $name           The Product name
     * @param   string  $distribution   The Distribution type
     * @param   double  $price          The Product price
     * @param   integer $active         The active status
     * @param   integer $ord          The sorting order
     * @param   integer $weight         The Product weight
     * @param   integer $id             The optional Product ID to be updated
     * @return  Product                 The Product
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function __construct(
        $code, $category_id, $name, $distribution, $price,
        $active, $ord, $weight, $id=0
    ) {
        // Assign & check
        $this->code         = trim(strip_tags($code));
        $this->category_id  = trim(strip_tags($category_id));
        $this->name         = trim($name);
        $this->distribution = trim(strip_tags($distribution));
        $this->price        = floatval($price);
        $this->ord          = intval($ord);
        $this->weight       = intval($weight);
        $this->id           = intval($id);
        $this->active($active);

        if ($this->ord <= 0) { $this->ord = 0; }
        // Default values for everything else as stated above

        // Enable cloning of Products with Attributes
        if ($this->id > 0) {
            $this->arrRelations =
                Attributes::getRelationArray($this->id);
        }
    }


    /**
     * The ID
     * @return  integer                             Product ID
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function id()
    {
        return $this->id;
    }
    /**
     * Set the ID -- NOT ALLOWED
     * See {@link Product::makeClone()}
     */

    /**
     * The Product code
     * @param   string    $code         The optional Product code
     * @return  string                  The Product code
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function code($code=null)
    {
        if (isset($code)) {
            $this->code = trim(strip_tags($code));
        }
        return $this->code;
    }

    /**
     * The Product name
     * @param   string    $name         The optional Product name
     * @return  string                  The Product name
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function name($name=null)
    {
        if (isset($name) && $name != '') {
            $this->name = trim($name);
        }
        return $this->name;
    }

    /**
     * The Category ID
     * @param   string    $category_id  The optional Category ID or comma
     *                                  separated list of IDs
     * @return  string                  The Category ID or comma separated
     *                                  list of IDs
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function category_id($category_id=null)
    {
        if (isset($category_id)) {
            $this->category_id = trim(strip_tags($category_id));
        }
        return $this->category_id;
    }

    /**
     * The Product price
     * @param   double    $price        The optional Product price
     * @return  double                  The Product price
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function price($price=null)
    {
        if (isset($price)) {
            $this->price = floatval($price);
        }
        return $this->price;
    }

    /**
     * The Product ordinal value
     * @param   integer   $ord          The optional ordinal value
     * @return  integer                 The ordinal value
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function ord($ord=null)
    {
        if (isset($ord)) {
            $this->ord = intval($ord);
        }
        return $this->ord;
    }

    /**
     * The Distribution type
     * @param   string    $distribution The optional distribution type
     * @return  string                  The distribution type
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function distribution($distribution=null)
    {
        if (isset($distribution)) {
            $this->distribution =
                (Distribution::isDistributionType($distribution)
                    ? $distribution : Distribution::getDefault());
        }
        return $this->distribution;
    }

    /**
     * The active status
     * @param   boolean   $active       The optional active status
     * @return  boolean                 The active status
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function active($active=null)
    {
        if (isset($active)) {
            $this->active = (boolean)$active;
        }
        return $this->active;
    }

    /**
     * Return product status based on the product stock and active settings
     *
     * @return boolean True when product is active, false otherwise
     */
    public function getStatus()
    {
        if ($this->stock_visible && $this->stock <= 0) {
            return false;
        }
        return $this->active;
    }

    /**
     * The pictures
     * @param   string    $pictures     The optional encoded picture string
     * @return  string                  The Encoded picture string
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function pictures($pictures=null)
    {
        if (isset($pictures)) {
            $this->pictures = $pictures;
        }
        return $this->pictures;
    }

    /**
     * The reseller price
     * @param   double    $resellerprice  The optional reseller price
     * @return  double                    The reseller price
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function resellerprice($resellerprice=null)
    {
        if (isset($resellerprice)) {
            $this->resellerprice = floatval($resellerprice);
        }
        return $this->resellerprice;
    }

    /**
     * The short description
     * @param   string    $short          The optional short description
     * @return  string                    The short description
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function short($short=null)
    {
        if (isset($short)) {
            $this->short = trim($short);
        }
        return $this->short;
    }

    /**
     * The long description
     * @param   string    $long           The optional long description
     * @return  string                    The long description
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function long($long=null)
    {
        if (isset($long)) {
            $this->long = trim($long);
        }
        return $this->long;
    }

    /**
     * The stock
     * @param   integer   $stock          The optional stock
     * @return  integer                   The stock
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function stock($stock=null)
    {
        if (isset($stock)) {
            $this->stock = intval($stock);
        }
        return $this->stock;
    }

    /**
     * The stock visibility
     * @param   boolean   $stock_visible  The optional stock visibility
     * @return  boolean                   The stock visibility
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function stock_visible($stock_visible=null)
    {
        if (isset($stock_visible)) {
            $this->stock_visible = (boolean)$stock_visible;
        }
        return $this->stock_visible;
    }

    /**
     * The discount price
     * @param   double    $discountprice  The optional discount price
     * @return  double                    The discount price
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function discountprice($discountprice=null)
    {
        if (isset($discountprice)) {
            $this->discountprice = floatval($discountprice);
        }
        return $this->discountprice;
    }

    /**
     * The special offer flag
     * @param   boolean   $discount_active  The optional special offer flag
     * @return  boolean                     The special offer flag
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function discount_active($discount_active=null)
    {
        if (isset($discount_active)) {
            $this->discount_active = (boolean)$discount_active;
        }
        return $this->discount_active;
    }

    /**
     * The Product flags
     * @param   string    $flags            The optional Product flags
     * @return  string                      The Product flags
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function flags($flags=null)
    {
        if (isset($flags)) {
            $this->flags = $flags;
        }
        return $this->flags;
    }
    /**
     * Add a flag
     *
     * If the flag is already present, nothing is changed.
     * Note that the match is case sensitive.
     * @param   string    $flag             The flag to be added
     * @return  boolean                     Always true for the time being
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function addFlag($flag)
    {
        if (!preg_match("/$flag/", $this->flags)) {
            $this->flags .= ' '.$flag;
        }
        return true;
    }
    /**
     * Remove a flag
     *
     * If the flag is not present, nothing is changed.
     * Note that the match is case insensitive.
     * @param   string    $flag             The flag to be removed
     * @return  boolean                     Always true for the time being
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function removeFlag($flag)
    {
        $this->flags = trim(preg_replace("/\\s*$flag\\s*/i", ' ', $this->flags));
        return true;
    }
    /**
     * Test for a match with the Product flags.
     *
     * Note that the match is case sensitive.
     * @param   string    $flag             The Product flag to test
     * @return  boolean                     Boolean true if the flag is present,
     *                                      false otherwise.
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function testFlag($flag)
    {
        return preg_match("/$flag/", $this->flags);
    }

    /**
     * The B2B flag
     * @param   boolean   $b2b              The optional B2B flag
     * @return  boolean                     The B2B flag
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function b2b($b2b=null)
    {
        if (isset($b2b)) {
            $this->b2b = (boolean)$b2b;
        }
        return $this->b2b;
    }

    /**
     * The B2C flag
     * @param   boolean   $b2c              The optional B2C flag
     * @return  boolean                     The B2C flag
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function b2c($b2c=null)
    {
        if (isset($b2c)) {
            $this->b2c = (boolean)$b2c;
        }
        return $this->b2c;
    }

    /**
     * The start date
     * @param   string    $date_start        The optional start date
     * @return  string                      The start date
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function date_start($date_start=null)
    {
        if (isset($date_start)) {
            $time_start = strtotime($date_start);
            // strtotime() will return unrecognized date when 0000-00-00 00:00:00
            if ($date_start && $date_start != '0000-00-00 00:00:00') {
                $this->date_start =
                    date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME, $time_start);
            } else {
// TODO: Unused DATETIME should be NULL
                $this->date_start = null;
            }
        }
        return $this->date_start;
    }

    /**
     * The end date
     * @param   string    $date_end          The optional end date
     * @return  string                      The end date
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function date_end($date_end=null)
    {
        if (isset($date_end)) {
            $time_end = strtotime($date_end);
            // strtotime() will return unrecognized date when 0000-00-00 00:00:00
            if ($time_end && $date_end != '0000-00-00 00:00:00') {
                $this->date_end =
                    date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME, $time_end);
            } else {
// TODO: Unused DATETIME should be NULL
                $this->date_end = null;
            }
        }
        return $this->date_end;
    }

    /**
     * Get the status of the product based on the scheduled publishing
     * Note: This function does not check whether the product is in scheduled publishing,
     * So make sure the product is in scheduled before calling this method
     *
     * @return boolean TRUE|FALSE True when product is active by scheduled publishing
     */
    public function getActiveByScheduledPublishing()
    {
        $start = null;
        if ($this->date_start()) {
            $start = \DateTime::createFromFormat(
                ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME,
                $this->date_start()
            );
            $start->setTime(0, 0, 0);
        }
        $end = null;
        if ($this->date_end()) {
            $end = \DateTime::createFromFormat(
                ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME,
                $this->date_end()
            );
            $end->setTime(23, 59, 59);
        }
        if (   (!empty($start) && empty($end) && ($start->getTimestamp() > time()))
            || (empty($start) && !empty($end) && ($end->getTimestamp() < time()))
            || (!empty($start) && !empty($end) && !($start->getTimestamp() < time() && $end->getTimestamp() > time()))
        ) {
            return false;
        }

        return true;
    }

    /**
     * The Manufacturer ID
     * @param   integer   $manufacturer     The optional Manufacturer ID
     * @return  integer                     The Manufacturer ID
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function manufacturer_id($manufacturer_id=null)
    {
        if (isset($manufacturer_id)) {
            $this->manufacturer_id = intval($manufacturer_id);
        }
        return $this->manufacturer_id;
    }

    /**
     * The external link
     * @param   string    $uri              The optional external link
     * @return  string                      The external link
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function uri($uri=null)
    {
        if (isset($uri)) {
            $this->uri = trim(strip_tags($uri));
        }
        return $this->uri;
    }

    /**
     * The VAT ID
     * @param   string    $vat_id           The optional VAT ID
     * @return  string                      The VAT ID
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function vat_id($vat_id=null)
    {
        if (isset($vat_id)) {
            $this->vat_id = intval($vat_id);
        }
        return $this->vat_id;
    }

    /**
     * The weight
     * @param   string    $weight           The optional weight
     * @return  string                      The weight
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function weight($weight=null)
    {
        if (isset($weight)) {
            $this->weight = intval($weight);
        }
        return $this->weight;
    }

    /**
     * The assigned Usergroups
     * @param   string    $usergroup_ids    The optional comma separated list
     *                                      of assigned user groups
     * @return  string                      The comma separated list
     *                                      of assigned user groups
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function usergroup_ids($usergroup_ids=null)
    {
        if (isset($usergroup_ids)) {
            $this->usergroup_ids = trim(strip_tags($usergroup_ids));
        }
        return $this->usergroup_ids;
    }

    /**
     * The minimum order quantity
     * @param   string    $minimum_order_quantity The optional minimum order quantity
     * @return  integer   minimum order quanity
     * @author  Kaleb Tschbaold <kaleb.tschabold@comvation.com>
     */
    function minimum_order_quantity($minimum_order_quantity=null)
    {
        if (isset($minimum_order_quantity)) {
            $this->minimum_order_quantity = intval ($minimum_order_quantity);
        }
        return $this->minimum_order_quantity;
    }

    /**
     * The keywords
     * @param   string    $keywords         The optional product keywords
     * @return  string                      The product keywords
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function keywords($keywords=null)
    {
        if (isset($keywords)) {
            $this->keywords = trim(strip_tags($keywords));
        }
        return $this->keywords;
    }


    /**
     * The visibility of the Product on the start page
     * @param   boolean   $shown_on_startpage   The optional visibility flag
     * @return  boolean                         The visibility flag
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function shown_on_startpage($shown_on_startpage=null)
    {
        if (isset($shown_on_startpage)) {
            if ($shown_on_startpage) {
                return $this->addFlag('__SHOWONSTARTPAGE__');
            }
            return $this->removeFlag('__SHOWONSTARTPAGE__');
        }
        return $this->testFlag('__SHOWONSTARTPAGE__');
    }


    /**
     * Return the correct Product price for any Customer and Product.
     *
     * Returns the reseller price if a valid Customer object is provided and
     * if this is of the type "reseller".
     * If this method is called without a valid Customer object,
     * the reseller price will never be returned.
     * @param   Customer    $objCustomer    The optional Customer object.
     * @return  double                      The Product price
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getCustomerPrice(&$objCustomer=false)
    {
        if (is_a($objCustomer, 'Customer') && $objCustomer->is_reseller()) {
            return $this->resellerprice;
        }
        return $this->price;
    }


    /**
     * Return the current discounted price for any Product, if applicable.
     * @return  mixed                       The Product discount price,
     *                                      or null if there is no discount.
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getDiscountedPrice()
    {
        if (!$this->hasDiscount()) return null;
        $price = $this->price;
        if ($this->discount_active) {
            $price = $this->discountprice;
        }
// NOTE: Add more conditions and rules as desired, i.e.
//        if ($this->testFlag('Outlet')) {
//            $discountRate = $this->getOutletDiscountRate();
//            $price = number_format(
//                $price * (100 - $discountRate) / 100,
//                2, '.', '');
//        }
        return $price;
    }


    /**
     * Returns boolean true if this Product has any kind of discount.
     *
     * This may either be the regular discount price if discount_active
     * is true, or the "Outlet" discount, or both.
     * Use {@link getDiscountPrice()} to get the correct discount price.
     * @return  boolean                 True if there is a discount,
     *                                  false otherwise.
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function has_discount()
    {
        return $this->discount_active
// NOTE: Add more conditions and rules as desired, i.e.
//            || $this->is_outlet()
        ;
    }


    /**
     * Returns boolean true if this Product is flagged as "Outlet"
     *
     * Note that this is an example extension only.
     * @return  boolean                 True if this is "Outlet",
     *                                  false otherwise.
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function is_outlet()
    {
        return $this->testFlag('Outlet');
    }


    /**
     * Return the discount rate for any Product in the virtual "Outlet"
     * Category.
     *
     * The rules for the discount are: 21% at the first date of the month,
     * plus an additional 1% per day, for a maximum rate of 51% on the 31st.
     * Note that this is an example extension only.
     * @return  integer                 The current Outlet discount rate
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function getOutletDiscountRate()
    {
        $dayOfMonth = date('j');
        return 20 + $dayOfMonth;
    }


    /**
     * The count type discount group ID
     * @param   integer   $group_id       The optional group ID
     * @return  integer                   The group ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function group_id($group_id=null)
    {
        if (isset($group_id)) {
            $this->group_id = intval($group_id);
        }
        return $this->group_id;
    }

    /**
     * The article group ID
     * @param   integer   $article_id   The optional article group ID
     * @return  string                  The article group ID
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function article_id($article_id=null)
    {
        if (isset($article_id)) {
            $this->article_id = intval($article_id);
        }
        return $this->article_id;
    }


    /**
     * Clone the Product
     *
     * Note that this does NOT create a copy in any way, but simply clears
     * the Product ID.  Upon storing this Product, a new ID is created.
     * Also note that all Attributes *MUST* be link()ed after every
     * insert() in order for this to work properly!
     * @return      void
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function makeClone()
    {
        $this->id = 0;
    }


    /**
     * Delete this Product from the database.
     *
     * Associated Attributes and pictures are deleted with it.
     * @return  boolean                         True on success, false otherwise
     * @global  ADONewConnection  $objDatabase  Database connection object
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function delete($flagDeleteImages=false)
    {
        global $objDatabase;

// TODO: MUST NOT delete while the Product is part of any Order!

        if (!$this->id) return false;
        if ($flagDeleteImages) {
            // Heck, most of this should go into the ProductPicture class...
            // Split picture data into single pictures
            $arrPictures = explode(':', $this->pictures);
            foreach ($arrPictures as $strPicture) {
                if (empty($strPicture)) continue;
                // Split picture into name, width, height -- all are base64
                // encoded!
                $arrPicture = explode('?', $strPicture);
                $strFileName = base64_decode($arrPicture[0]);
                // If it is the default image, skip it
                if (preg_match('/'.ShopLibrary::noPictureName.'$/', $strFileName))
                    continue;
                // Verify that no other Product uses the same picture.
                // $arrPicture[0] contains the encoded file name
                $query = "
                    SELECT picture FROM ".DBPREFIX."module_shop".MODULE_INDEX."_products
                     WHERE picture LIKE '%".addslashes($arrPicture[0])."%'";
                $objResult = $objDatabase->Execute($query);
                if ($objResult->RecordCount() == 1) {
                    // The only one -- it can be deleted.
                    // Delete the picture and thumbnail.
                    $thumbName = \Image::getThumbnailPath($strFileName);
                    // Continue even if deleting the images fails
                    \File::delete_file($strFileName);
                    \File::delete_file($thumbName);
                }
            }
        }
        // Remove any Text records present
        if (!\Text::deleteById($this->id, 'Shop', self::TEXT_NAME)) return false;
        if (!\Text::deleteById($this->id, 'Shop', self::TEXT_SHORT)) return false;
        if (!\Text::deleteById($this->id, 'Shop', self::TEXT_LONG)) return false;
        if (!\Text::deleteById($this->id, 'Shop', self::TEXT_KEYS)) return false;
        if (!\Text::deleteById($this->id, 'Shop', self::TEXT_CODE)) return false;
        if (!\Text::deleteById($this->id, 'Shop', self::TEXT_URI)) return false;
        // Delete the Product attribute relations and the Product itself
// TEST
        if (!Attributes::removeFromProduct($this->id)) {
            return false;
        }

        \Env::get('cx')->getEvents()->triggerEvent('model/preRemove', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));

        $objResult = $objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_shop".MODULE_INDEX."_products
                WHERE id=$this->id");

        if (!$objResult) {
            return false;
        }

        $objCatResult = $objDatabase->Execute('
            DELETE FROM '.DBPREFIX.'module_shop'.MODULE_INDEX.'_rel_category_product
                WHERE product_id='.$this->id
        );

        $objUserGroupResult = $objDatabase->Execute('
            DELETE FROM '.DBPREFIX.'module_shop'.MODULE_INDEX.'_rel_product_user_group
                WHERE product_id='.$this->id
        );

        if (!$objCatResult || !$objUserGroupResult) {
            return false;
        }

        \Env::get('cx')->getEvents()->triggerEvent('model/postRemove', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));

        return true;
    }


    /**
     * Test whether a record with the ID of this object is already present
     * in the database.
     * @return  boolean                     True if it exists, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function recordExists()
    {
        global $objDatabase;

        $query = "
            SELECT 1
              FROM ".DBPREFIX."module_shop".MODULE_INDEX."_products
             WHERE id=$this->id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult || $objResult->EOF) return false;
        return true;
    }


    /**
     * Stores the Product object in the database.
     *
     * Either updates or inserts the object, depending on the outcome
     * of the call to {@link recordExists()}.
     * Also stores associated Text records.
     * @return      boolean     True on success, false otherwise
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function store()
    {
//DBG::activate(DBG_ADODB);
        if ($this->recordExists()) {
            if (!$this->update()) return false;
            if (!Attributes::removeFromProduct($this->id)) return false;
        } else {
            if (!$this->insert()) return false;
        }
        if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_NAME, $this->name)) {
            return false;
        }
        if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_SHORT, $this->short)) {
            return false;
        }
        if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_LONG, $this->long)) {
            return false;
        }
        if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_KEYS, $this->keywords)) {
            return false;
        }
        if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_CODE, $this->code)) {
            return false;
        }
        if (!\Text::replace($this->id, FRONTEND_LANG_ID, 'Shop',
            self::TEXT_URI, $this->uri)) {
            return false;
        }
//DBG::deactivate(DBG_ADODB);
        // Store Attributes, if any
        if (is_array($this->arrRelations)) {
            foreach ($this->arrRelations as $value_id => $ord) {
                if (!Attributes::addOptionToProduct(
                    $value_id, $this->id, $ord
                )) return false;
                }
            }
        if (!$this->updateCategoryRelation()) {
            return false;
        }
        if (!$this->updateUserGroupRelation()) {
            return false;
        }
        return true;
    }


    /**
     * Update this Product in the database.
     *
     * Note that associated Text records are not changed here, use
     * {@see store()} to do that.
     * @return      boolean                     True on success, false otherwise
     * @global  ADONewConnection  $objDatabase    Database connection object
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function update()
    {
        global $objDatabase;

        \Env::get('cx')->getEvents()->triggerEvent('model/preUpdate', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
        $args = array(
            $this->pictures,
            $this->distribution,
            $this->price,
            $this->resellerprice,
            $this->stock,
            $this->stock_visible ? 1 : 0,
            $this->discountprice,
            $this->discount_active ? 1 : 0,
            $this->active ? 1 : 0,
            $this->b2b ? 1 : 0,
            $this->b2c ? 1 : 0,
            $this->date_start,
            $this->date_end,
            $this->manufacturer_id,
            $this->ord,
            $this->vat_id,
            $this->weight,
            addslashes($this->flags),
            $this->group_id ? $this->group_id : null,
            $this->article_id ? $this->article_id : null,
            $this->minimum_order_quantity ? $this->minimum_order_quantity : '0',
            $this->id
        );

        $query = '
            UPDATE
                `' . DBPREFIX . 'module_shop' . MODULE_INDEX . '_products`
            SET
                `picture` = ?,
                `distribution` = ?,
                `normalprice` = ?,
                `resellerprice` = ?,
                `stock` = ?,
                `stock_visible` = ?,
                `discountprice` = ?,
                `discount_active` = ?,
                `active` = ?,
                `b2b` = ?,
                `b2c` = ?,
                `date_start` = ?,
                `date_end` = ?,
                `manufacturer_id` = ?,
                `ord` = ?,
                `vat_id` = ?,
                `weight` = ?,
                `flags` = ?,
                `group_id` = ?,
                `article_id` = ?,
                `minimum_order_quantity` = ?
            WHERE
                `id` = ?
        ';

        $objResult = $objDatabase->Execute($query, $args);
        if ($objResult) {
            \Env::get('cx')->getEvents()->triggerEvent('model/postUpdate', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
            return true;
        }
        return false;
    }
    /**
     * Update usergroup-product relation in intermediate table.
     *
     * @param array array with all category ids
     * @return bool
     */
    function updateUserGroupRelation()
    {
        $table = 'rel_product_user_group';
        $attr = array('entity' => 'product_id', 'foreign' => 'usergroup_id');
        $arrIdsInput = explode (',', $this->usergroup_ids);

        return \Cx\Modules\Shop\Controller\ShopLibrary::updateRelation(
            $table, $attr, $arrIdsInput, $this->id
        );
    }

    /**
     * Update category-product relation in intermediate table.
     *
     * @param array array with all category ids
     * @return bool
     */
    function updateCategoryRelation()
    {
        $table = 'rel_category_product';
        $attr = array('entity' => 'product_id', 'foreign' => 'category_id');
        $arrIdsInput = explode (',', $this->category_id);

        return \Cx\Modules\Shop\Controller\ShopLibrary::updateRelation(
            $table, $attr, $arrIdsInput, $this->id
        );
    }

    /**
     * Insert this Product into the database.
     *
     * Note that associated Text records are not changed here, use
     * {@see store()} to do that.
     * @return      boolean                     True on success, false otherwise
     * @global      ADONewConnection
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function insert()
    {
        global $objDatabase;

        \Env::get('cx')->getEvents()->triggerEvent('model/prePersist', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
        $query = "
            INSERT INTO ".DBPREFIX."module_shop".MODULE_INDEX."_products (
                picture, distribution,
                normalprice, resellerprice,
                stock, stock_visible, discountprice, discount_active,
                active, b2b, b2c, date_start, date_end,
                manufacturer_id, ord, vat_id, weight,
                flags, group_id, article_id, minimum_order_quantity
            ) VALUES (
                '$this->pictures',
                '$this->distribution',
                $this->price, $this->resellerprice,
                $this->stock, ".($this->stock_visible ? 1 : 0).",
                $this->discountprice, ".($this->discount_active ? 1 : 0).", ".
                ($this->active ? 1 : 0).", ".
                ($this->b2b ? 1 : 0).", ".($this->b2c ? 1 : 0).",
                '$this->date_start', '$this->date_end',
                ".($this->manufacturer_id ? $this->manufacturer_id : 'NULL').",
                $this->ord, 
                ".($this->vat_id ? $this->vat_id : 'NULL').", 
                $this->weight,
                '".addslashes($this->flags)."',
                ".($this->group_id ? $this->group_id : 'NULL').",
                ".($this->article_id ? $this->article_id : 'NULL').",
                ".($this->minimum_order_quantity ? $this->minimum_order_quantity : '0')."
            )";
        $objResult = $objDatabase->Execute($query);
        if ($objResult) {
            \Env::get('cx')->getEvents()->triggerEvent('model/postPersist', array(new \Doctrine\ORM\Event\LifecycleEventArgs($this, \Env::get('em'))));
            // My brand new ID
            $this->id = $objDatabase->Insert_ID();
            return true;
        }
        return false;
    }


    /**
     * Select a Product by ID from the database.
     * @static
     * @param       integer     $id             The Product ID
     * @return      Product                     The Product object on success,
     *                                          false otherwise
     * @global      ADONewConnection
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    static function getById($id)
    {
        global $objDatabase;

        if (!$id) return NULL;
        $arrSql = \Text::getSqlSnippets(
            '`product`.`id`', FRONTEND_LANG_ID, 'Shop',
            array(
                'name' => self::TEXT_NAME,
                'short' => self::TEXT_SHORT,
                'long' => self::TEXT_LONG,
                'keys' => self::TEXT_KEYS,
                'code' => self::TEXT_CODE,
                'uri' => self::TEXT_URI,
            )
        );
        $query = "
            SELECT `product`.`id`,
                   `product`.`ord`, `product`.`active`, `product`.`weight`,
                   `product`.`picture`,
                   `product`.`normalprice`, `product`.`resellerprice`,
                   `product`.`discountprice`, `product`.`discount_active`,
                   `product`.`stock`, `product`.`stock_visible`,
                   `product`.`distribution`,
                   `product`.`date_start`, `product`.`date_end`,
                   `product`.`manufacturer_id`,
                   `product`.`b2b`, `product`.`b2c`,
                   `product`.`vat_id`,
                   `product`.`flags`,
                   `product`.`group_id`, `product`.`article_id`,
                   `product`.`minimum_order_quantity`, ".
                   $arrSql['field']."
              FROM `".DBPREFIX."module_shop".MODULE_INDEX."_products` AS `product`".
                   $arrSql['join']."
             WHERE `product`.`id`=$id";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) return self::errorHandler();
        if ($objResult->RecordCount() != 1) return false;
        $id = $objResult->fields['id'];
        $strCode = $objResult->fields['code'];
        if ($strCode === null) {
            $strCode = \Text::getById($id, 'Shop', self::TEXT_CODE)->content();
        }
        $strName = $objResult->fields['name'];
        if ($strName === null) {
            $strName = \Text::getById($id, 'Shop', self::TEXT_NAME)->content();
        }
        $strShort = $objResult->fields['short'];
        if ($strShort === null) {
            $strShort = \Text::getById($id, 'Shop', self::TEXT_SHORT)->content();
        }
        $strLong = $objResult->fields['long'];
        if ($strLong === null) {
            $strLong = \Text::getById($id, 'Shop', self::TEXT_LONG)->content();
        }
        $strUri = $objResult->fields['uri'];
        if ($strUri === null) {
            $strUri = \Text::getById($id, 'Shop', self::TEXT_URI)->content();
        }
        $strKeys = $objResult->fields['keys'];
        if ($strKeys === null) {
            $strKeys = \Text::getById($id, 'Shop', self::TEXT_KEYS)->content();
        }

        $queryCategories = '
            SELECT `category_id`
              FROM `'.DBPREFIX.'module_shop'.MODULE_INDEX.'_rel_category_product`
              WHERE `product_id`='.$id;
        $objResultCategories = $objDatabase->Execute($queryCategories);
        $arrCategoryIds = array();
        while (!$objResultCategories->EOF) {
            $arrCategoryIds[] = $objResultCategories->fields['category_id'];
            $objResultCategories->MoveNext();
        }

        $queryUserGroups = '
            SELECT `usergroup_id`
              FROM `'.DBPREFIX.'module_shop'.MODULE_INDEX.'_rel_product_user_group`
              WHERE `product_id`='.$id;
        $objResultUserGroups = $objDatabase->Execute($queryUserGroups);
        $arrUserGroupIds = array();
        while (!$objResultUserGroups->EOF) {
            $arrUserGroupIds[] = $objResultUserGroups->fields['usergroup_id'];
            $objResultUserGroups->MoveNext();
        }

        $objProduct = new Product(
            $strCode,
            implode(',', $arrCategoryIds),
            $strName,
            $objResult->fields['distribution'],
            $objResult->fields['normalprice'],
            $objResult->fields['active'],
            $objResult->fields['ord'],
            $objResult->fields['weight'],
            $objResult->fields['id']
        );
        $objProduct->pictures = $objResult->fields['picture'];
        $objProduct->resellerprice = floatval($objResult->fields['resellerprice']);
        $objProduct->short = $strShort;
        $objProduct->long = $strLong;
        $objProduct->stock($objResult->fields['stock']);
        $objProduct->stock_visible($objResult->fields['stock_visible']);
        $objProduct->discountprice = floatval($objResult->fields['discountprice']);
        $objProduct->discount_active($objResult->fields['discount_active']);
        $objProduct->b2b($objResult->fields['b2b']);
        $objProduct->b2c($objResult->fields['b2c']);
        $objProduct->date_start($objResult->fields['date_start']);
        $objProduct->date_end($objResult->fields['date_end']);
        $objProduct->manufacturer_id = $objResult->fields['manufacturer_id'];
        $objProduct->uri = $strUri;
        $objProduct->vat_id = $objResult->fields['vat_id'];
        $objProduct->flags = $objResult->fields['flags'];
        $objProduct->usergroup_ids = implode(',', $arrUserGroupIds);
        $objProduct->group_id = $objResult->fields['group_id'];
        $objProduct->article_id = $objResult->fields['article_id'];
        $objProduct->keywords = $strKeys;
        $objProduct->minimum_order_quantity = $objResult->fields['minimum_order_quantity'];
        // Fetch the Product Attribute relations
        $objProduct->arrRelations =
            Attributes::getRelationArray($objProduct->id);
//die("dfhreh: ".$objProduct->category_id());
        return $objProduct;
    }


    /**
     * Add the given Product Attribute value ID to this object.
     *
     * Note that the relation is is only permanently created after
     * the object is store()d.
     * @param   integer     $value_id    The Product Attribute value ID
     * @param   integer     $ord      The sorting order value
     * @return  boolean                 True. Always.
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function addAttribute($value_id, $ord)
    {
        $this->arrRelations[$value_id] = $ord;
        return true;
    }


    /**
     * Remove the given Product Attribute value ID from this object.
     *
     * Note that the relation is is only permanently destroyed after
     * the object is store()d.
     * Also note that this method always returns true. It cannot fail. :)
     * @param   integer     $value_id    The Product Attribute value ID
     * @return  boolean                 True. Always.
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function deleteAttribute($value_id)
    {
        unset($this->arrRelations[$value_id]);
        return true;
    }


    /**
     * Remove all Product Attribute value IDs from this object.
     *
     * Note that the relations are only permanently destroyed after
     * the object is store()d.
     * @return  boolean                 True on success, false otherwise
     * @author      Reto Kohli <reto.kohli@comvation.com>
     */
    function clearAttributes()
    {
        $this->arrRelations = array();
        return true;
    }


    /**
     * Decrease the Product stock count
     *
     * This applies to "real", shipped goods only.  These have "delivery"
     * set as their "distribution" field value.
     * @param   integer   $quantity       The quantity to subtract
     *                                    from the stock
     * @return  boolean                   True on success, false otherwise
     */
    function decreaseStock($quantity)
    {
        global $objDatabase;

        $query = "
            UPDATE ".DBPREFIX."module_shop".MODULE_INDEX."_products
               SET stock=stock-$quantity
             WHERE id=$this->id
               AND distribution='delivery'";
        return (boolean)$objDatabase->Execute($query);
        }


    /**
     * Returns a product price in the active currency, depending on the
     * Customer and special offer status.
     * @param   Customer  $objCustomer      The Customer, or null
     * @param   double    $price_options    The price for Attributes,
     *                                      if any, or 0 (zero)
     * @param   integer   $count            The number of products, defaults
     *                                      to 1 (one)
     * @param   boolean   $ignore_special_offer
     *                                      If true, special offers are ignored.
     *                                      This is needed to actually determine
     *                                      both prices in the products view.
     *                                      Defaults to false.
     * @return  double                      The price converted to the active
     *                                      currency
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    function get_custom_price($objCustomer=null, $price_options=0, $count=1,
        $ignore_special_offer=false)
    {
        $normalPrice = $this->price();
        $resellerPrice = $this->resellerprice();
        $discountPrice = $this->discountprice();
        $discount_active = $this->discount_active();
        $groupCountId = $this->group_id();
        $groupArticleId = $this->article_id();
        $price = $normalPrice;
        if (   !$ignore_special_offer
            && $discount_active == 1
            && $discountPrice != 0) {
            $price = $discountPrice;
        } else {
            if (   $objCustomer
                && $objCustomer->is_reseller()
                && $resellerPrice != 0) {
                $price = $resellerPrice;
            }
        }
        $price += $price_options;
        $rateCustomer = 0;
        if ($objCustomer) {
            $groupCustomerId = $objCustomer->group_id();
            if ($groupCustomerId) {
                $rateCustomer = Discount::getDiscountRateCustomer(
                    $groupCustomerId, $groupArticleId);
                $price -= ($price * $rateCustomer * 0.01);
            }
        }
        $rateCount = 0;
        if ($count > 0) {
            $rateCount = Discount::getDiscountRateCount($groupCountId, $count);
            $price -= ($price * $rateCount * 0.01);
        }
        $price = \Cx\Modules\Shop\Controller\CurrencyController::getCurrencyPrice($price);
        return $price;
    }


    /**
     * Handles database errors
     *
     * Also migrates text fields to the new structure
     * @return  boolean         False.  Always.
     * @static
     * @throws  Cx\Lib\Update_DatabaseException
     */
    static function errorHandler()
    {
// Product
        // Fix the Text, Discount, and Manufacturer tables first
        \Text::errorHandler();
//        Discount::errorHandler(); // Called by Customer::errorHandler();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $manufacturer = $cx->getDb()->getEntityManager()->getRepository(
            '\Cx\Modules\Shop\Model\Entity\Manufacturer'
        );
        $manufacturer->errorHandler();

        $table_name = DBPREFIX.'module_shop_products';
        $table_structure = array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'auto_increment' => true, 'primary' => true),
            'normalprice' => array('type' => 'DECIMAL(9,2)', 'default' => '0.00'),
            'resellerprice' => array('type' => 'DECIMAL(9,2)', 'default' => '0.00'),
            'discountprice' => array('type' => 'DECIMAL(9,2)', 'default' => '0.00'),
            'discount_active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '0', 'renamefrom' => 'is_special_offer'),
            'stock' => array('type' => 'INT(10)', 'default' => '10'),
            'stock_visible' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1', 'renamefrom' => 'stock_visibility'),
            'active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1', 'renamefrom' => 'status'),
            'b2b' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1'),
            'b2c' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'default' => '1'),
            'date_start' => array('type' => 'TIMESTAMP', 'default' => '0000-00-00 00:00:00', 'renamefrom' => 'startdate'),
            'date_end' => array('type' => 'TIMESTAMP', 'default' => '0000-00-00 00:00:00', 'renamefrom' => 'enddate'),
            'weight' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'vat_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'manufacturer_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null, 'renamefrom' => 'manufacturer'),
            'group_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'article_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => false, 'default' => null),
            'ord' => array('type' => 'INT(10)', 'default' => '0', 'renamefrom' => 'sort_order'),
            'distribution' => array('type' => 'VARCHAR(16)', 'default' => '', 'renamefrom' => 'handler'),
            'picture' => array('type' => 'VARCHAR(4096)', 'notnull' => false, 'default' => null),
            'flags' => array('type' => 'VARCHAR(4096)', 'notnull' => false, 'default' => null),
            'minimum_order_quantity' => array('type' => 'INT(10)', 'unsigned' => false, 'default' => '0'),
// Obsolete:
//`property1` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
//`property2` varchar(100) COLLATE utf8_unicode_ci DEFAULT '',
//`manufacturer_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
        );
        $table_index =  array(
            'group_id' => array('fields' => array('group_id')),
            'article_id' => array('fields' => array('article_id')),
            'flags' => array('fields' => array('flags'), 'type' => 'FULLTEXT', ),
        );
        $default_lang_id = \FWLanguage::getDefaultLangId();
        if (\Cx\Lib\UpdateUtil::table_exist($table_name)) {
            if (\Cx\Lib\UpdateUtil::column_exist($table_name, 'title')) {
                // Migrate all Product strings to the Text table first
                \Text::deleteByKey('Shop', self::TEXT_NAME);
                \Text::deleteByKey('Shop', self::TEXT_SHORT);
                \Text::deleteByKey('Shop', self::TEXT_LONG);
                \Text::deleteByKey('Shop', self::TEXT_CODE);
                \Text::deleteByKey('Shop', self::TEXT_URI);
                \Text::deleteByKey('Shop', self::TEXT_KEYS);
                $query = "
                    SELECT `id`, `title`, `shortdesc`, `description`,
                           `product_id`, `external_link`, `keywords`
                      FROM `$table_name`";
                $objResult = \Cx\Lib\UpdateUtil::sql($query);
                if (!$objResult) {
                    throw new \Cx\Lib\Update_DatabaseException(
                        "Failed to query Product strings", $query);
                }
                while (!$objResult->EOF) {
                    $id = $objResult->fields['id'];
                    $name = $objResult->fields['title'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_NAME, $name)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product name '$name'");
                    }
                    $short = $objResult->fields['shortdesc'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_SHORT, $short)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product short '$short'");
                    }
                    $long = $objResult->fields['description'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_LONG, $long)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product long '$long'");
                    }
                    $code = $objResult->fields['product_id'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_CODE, $code)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product code '$code'");
                    }
                    $uri = $objResult->fields['external_link'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_URI, $uri)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product uri '$uri'");
                    }
                    $keys = $objResult->fields['keywords'];
                    if (!\Text::replace($id, $default_lang_id, 'Shop',
                        self::TEXT_KEYS, $keys)) {
                        throw new \Cx\Lib\Update_DatabaseException(
                            "Failed to migrate Product keys '$keys'");
                    }
                    $objResult->MoveNext();
                }
            }
        }
        \Cx\Lib\UpdateUtil::table($table_name, $table_structure, $table_index);

        // Also fix Customer and some related tables
        Customer::errorHandler();

        // Always
        return false;
    }

}

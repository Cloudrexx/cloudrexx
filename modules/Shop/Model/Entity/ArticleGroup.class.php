<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2018
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
 * Class ArticleGroup
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
namespace Cx\Modules\Shop\Model\Entity;

/**
 * ArticleGroup contains products and are related to DiscountGroups
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 * @version     5.0.0
 */
class ArticleGroup extends \Cx\Model\Base\EntityBase implements \Gedmo\Translatable\Translatable {
    /**
     * @var string
     */
    protected $locale;
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $relDiscountGroups;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    protected $products;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relDiscountGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set translatable locale
     *
     * @param $locale
     */
    public function setTranslatableLocale($locale)
    {
        if (!is_string($locale) || !strlen($locale)) {
            $this->locale = $locale;
        }
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add relDiscountGroups
     *
     * @param \Cx\Modules\Shop\Model\Entity\RelDiscountGroup $relDiscountGroup
     */
    public function addRelDiscountGroup(\Cx\Modules\Shop\Model\Entity\RelDiscountGroup $relDiscountGroup)
    {
        $this->relDiscountGroups[] = $relDiscountGroup;
    }

    /**
     * Remove relDiscountGroups
     *
     * @param \Cx\Modules\Shop\Model\Entity\RelDiscountGroup $relDiscountGroup
     */
    public function removeRelDiscountGroup(\Cx\Modules\Shop\Model\Entity\RelDiscountGroup $relDiscountGroup)
    {
        $this->relDiscountGroups->removeElement($relDiscountGroup);
    }

    /**
     * Get relDiscountGroups
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRelDiscountGroups()
    {
        return $this->relDiscountGroups;
    }

    /**
     * Add products
     *
     * @param \Cx\Modules\Shop\Model\Entity\Product $product
     */
    public function addProduct(\Cx\Modules\Shop\Model\Entity\Product $product)
    {
        $this->products[] = $product;
    }

    /**
     * Remove products
     *
     * @param \Cx\Modules\Shop\Model\Entity\Product $product
     */
    public function removeProduct(\Cx\Modules\Shop\Model\Entity\Product $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }
}

<?php
/**
 * CoreShop.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (http://www.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Model\PriceRule\Condition;

use CoreShop\Exception;
use CoreShop\Model\Cart\PriceRule;
use CoreShop\Model\Cart;
use CoreShop\Model\Configuration;
use CoreShop\Model\Shop as ShopModel;
use CoreShop\Model\Product as ProductModel;
use CoreShop\Tool;

/**
 * Class Shop
 * @package CoreShop\Model\PriceRule\Condition
 */
class Shop extends AbstractCondition
{
    /**
     * @var int
     */
    public $shop;

    /**
     * @var string
     */
    public $type = 'shop';

    /**
     * @return ShopModel
     */
    public function getShop()
    {
        if (!$this->shop instanceof ShopModel) {
            $this->shop = ShopModel::getById($this->shop);
        }

        return $this->shop;
    }

    /**
     * @param int $shop
     */
    public function setShop($shop)
    {
        $this->shop = $shop;
    }

    /**
     * Check if Cart is Valid for Condition.
     *
     * @param Cart       $cart
     * @param PriceRule  $priceRule
     * @param bool|false $throwException
     *
     * @return bool
     *
     * @throws Exception
     */
    public function checkConditionCart(Cart $cart, PriceRule $priceRule, $throwException = false)
    {
        return $this->check($throwException);
    }

    /**
     * Check if Product is Valid for Condition.
     *
     * @param ProductModel $product
     * @param ProductModel\AbstractProductPriceRule $priceRule
     *
     * @return bool
     */
    public function checkConditionProduct(ProductModel $product, ProductModel\AbstractProductPriceRule $priceRule)
    {
        return $this->check();
    }

    /**
     * @param bool $throwException
     * @return bool
     * @throws Exception
     */
    protected function check($throwException = false)
    {
        if(Configuration::multiShopEnabled()) {
            $currentShop = ShopModel::getShop();

            if ($this->getShop()->getId() !== $currentShop->getId()) {
                if ($throwException) {
                    throw new Exception('You cannot use this voucher in this shop');
                } else {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}

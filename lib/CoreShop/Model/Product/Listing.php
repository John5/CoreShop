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

namespace CoreShop\Model\Product;

use CoreShop\Model\Category;
use CoreShop\Model\Index;
use CoreShop\Model\Product;
use CoreShop\Model\Shop;

/**
 * Class Listing
 * @package CoreShop\Model\Product
 */
abstract class Listing implements \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator
{
    const ORDERKEY_PRICE = 'orderkey_price';

    /**
     * Variant mode defines how to consider variants in product list results
     * - does not consider variants in search results.
     */
    const VARIANT_MODE_HIDE = 'hide';

    /**
     * Variant mode defines how to consider variants in product list results
     * - considers variants in search results and returns objects and variants.
     */
    const VARIANT_MODE_INCLUDE = 'include';

    /**
     * Variant mode defines how to consider variants in product list results
     * - considers variants in search results but only returns corresponding objects in search results.
     */
    const VARIANT_MODE_INCLUDE_PARENT_OBJECT = 'include_parent_object';

    /**
     * @var Index|null
     */
    public $index = null;

    /**
     * Listing constructor.
     *
     * @param Index $index
     */
    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    /**
     * Returns all products valid for this search.
     *
     * @return \CoreShop\Model\Product[]
     */
    abstract public function getProducts();

    /**
     * Adds filter condition to product list
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results.
     *
     * @param string $condition
     * @param string $fieldname
     */
    abstract public function addCondition($condition, $fieldname = '');

    /**
     * Adds query condition to product list for fulltext search
     * Fieldname is optional but highly recommended - needed for resetting condition based on fieldname
     * and exclude functionality in group by results.
     *
     * @param $condition
     * @param string $fieldname
     */
    abstract public function addQueryCondition($condition, $fieldname = '');

    /**
     * Reset filter condition for fieldname.
     *
     * @param $fieldname
     *
     * @return mixed
     */
    abstract public function resetCondition($fieldname);

    /**
     * Reset query condition for fieldname.
     *
     * @param $fieldname
     *
     * @return mixed
     */
    abstract public function resetQueryCondition($fieldname);

    /**
     * Adds relation condition to product list.
     *
     * @param string $fieldname
     * @param string $condition
     */
    abstract public function addRelationCondition($fieldname, $condition);

    /**
     * Adds join to product list.
     *
     * @param string $table
     * @param string $query
     */
    abstract public function addJoin($table, $query);

    /**
     * Resets all conditions of product list.
     */
    abstract public function resetConditions();

    /**
     * Adds price condition to product list.
     *
     * @param null|float $from
     * @param null|float $to
     */
    abstract public function addPriceCondition($from = null, $to = null);

    /**
     * sets order direction.
     *
     * @param $order
     */
    abstract public function setOrder($order);

    /**
     * gets order direction.
     *
     * @return string
     */
    abstract public function getOrder();

    /**
     * sets order key.
     *
     * @param $orderKey string | array  - either single field name, or array of field names or array of arrays (field name, direction)
     */
    abstract public function setOrderKey($orderKey);

    /**
     * @return string | array
     */
    abstract public function getOrderKey();

    /**
     * @param $limit int
     */
    abstract public function setLimit($limit);

    /**
     * @return int
     */
    abstract public function getLimit();

    /**
     * @param $offset int
     */
    abstract public function setOffset($offset);

    /**
     * @return int
     */
    abstract public function getOffset();

    /**
     * @param $category
     */
    abstract public function setCategory(Category $category);

    /**
     * @return \CoreShop\Model\Category
     */
    abstract public function getCategory();

    /**
     * @param $shop
     */
    abstract public function setShop(Shop $shop);

    /**
     * @return \CoreShop\Model\Shop
     */
    abstract public function getShop();

    /**
     * @param $variantMode
     */
    abstract public function setVariantMode($variantMode);

    /**
     * @return string
     */
    abstract public function getVariantMode();

    /**
     * loads search results from index and returns them.
     *
     * @return Product[]
     */
    abstract public function load();

    /**
     * loads group by values based on fieldname either from local variable if prepared or directly from product index.
     *
     * @param $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws Exception
     */
    abstract public function getGroupByValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);

    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index.
     *
     * @param      $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws Exception
     */
    abstract public function getGroupByRelationValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);

    /**
     * loads group by values based on relation fieldname either from local variable if prepared or directly from product index.
     *
     * @param      $fieldname
     * @param bool $countValues
     * @param bool $fieldnameShouldBeExcluded => set to false for and-conditions
     *
     * @return array
     *
     * @throws Exception
     */
    abstract public function getGroupBySystemValues($fieldname, $countValues = false, $fieldnameShouldBeExcluded = true);

    /**
     * returns order by statement for similarity calculations based on given fields and object ids
     * returns cosine similarity calculation
     *
     * @param $fields
     * @param $objectId
     *
     * @return Product[]
     */
    abstract public function buildSimilarityOrderBy($fields, $objectId);

    /**
     * @return Index|null
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param Index|null $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }
}

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

use CoreShop\Controller\Action;

/**
 * Class CoreShop_ProductController
 */
class CoreShop_ProductController extends Action
{
    public function detailAction()
    {
        $id = $this->getParam('product');
        $product = \CoreShop\Model\Product::getById($id);
        $this->view->contacts = \CoreShop\Model\Messaging\Contact::getList()->load();

        if ($product instanceof \CoreShop\Model\Product) {
            if(!in_array(\CoreShop\Model\Shop::getShop()->getId(), $product->getShops())) {
                throw new CoreShop\Exception(sprintf('Product (%s) not valid for shop (%s)', $id, \CoreShop\Model\Shop::getShop()->getId()));
            }

            $this->view->product = $product;
            $this->view->similarProducts = array();
            
            $this->view->seo = array(
                'image' => $product->getImage(),
                'description' => $product->getMetaDescription() ? $product->getMetaDescription() : $product->getShortDescription(),
            );

            if (count($product->getCategories()) > 0) {
                $mainCategory = $product->getCategories()[0];

                if ($mainCategory->getFilterDefinition() instanceof \CoreShop\Model\Product\Filter) {
                    $this->view->similarProducts = $this->getSimilarProducts($product, $mainCategory->getFilterDefinition());
                }
            }

            if ($this->getRequest()->isPost()) {
                $params = $this->getAllParams();

                $result = \CoreShop\Model\Messaging\Service::handleRequestAndCreateThread($params, $this->language);

                if ($result['success']) {
                    $this->view->success = true;
                } else {
                    $this->view->success = false;
                    $this->view->error = $this->view->translate($result['message']);
                }
            }

            $this->view->headTitle($product->getMetaTitle() ? $product->getMetaTitle() : $product->getName());
        } else {
            throw new CoreShop\Exception(sprintf('Product with id "%s" not found', $id));
        }
    }

    public function indexAction()
    {
        $this->view->headTitle('Home');
    }

    public function previewAction()
    {
        $id = $this->getParam('id');
        $product = \CoreShop\Model\Product::getById($id);

        $this->disableLayout();

        if ($product instanceof \CoreShop\Model\Product) {
            $this->view->product = $product;
        } else {
            throw new \CoreShop\Exception(sprintf('Product with id %s not found', $id));
        }
    }

    public function listAction()
    {
        $id = $this->getParam('category');
        $page = $this->getParam('page', 0);
        $sort = $this->getParam('sort', 'NAMEA');
        $perPage = $this->getParam('perPage', 12);
        $type = $this->getParam('type', 'list');

        $category = \CoreShop\Model\Category::getById($id);

        if ($category instanceof \CoreShop\Model\Category) {
            if ($category->getFilterDefinition() instanceof \CoreShop\Model\Product\Filter) {
                $index = $category->getFilterDefinition()->getIndex();
                $indexService = \CoreShop\IndexService::getIndexService()->getWorker($index->getName());

                $list = $indexService->getProductList();
                $list->setVariantMode(\CoreShop\Model\Product\Listing::VARIANT_MODE_HIDE);

                $this->view->currentFilter = \CoreShop\Model\Product\Filter\Helper::setupProductList($list, $this->getAllParams(), $category->getFilterDefinition(), new \CoreShop\Model\Product\Filter\Service());

                $list->setCategory($category);

                $this->view->filter = $category->getFilterDefinition();
                $this->view->list = $list;
                $this->view->params = $this->getAllParams();

                $paginator = Zend_Paginator::factory($list);
                $paginator->setCurrentPageNumber($this->getParam('page'));
                $paginator->setItemCountPerPage($list->getLimit());
                $paginator->setPageRange(10);

                $this->view->paginator = $paginator;
            } else {
                $this->view->paginator = $category->getProductsPaging($page, $perPage, $this->parseSorting($sort), true);
            }

            $this->view->category = $category;
            $this->view->page = $page;
            $this->view->sort = $sort;
            $this->view->perPage = $perPage;
            $this->view->type = $type;

            $this->view->seo = array(
                'image' => $category->getImage(),
                'description' => $category->getMetaDescription() ? $category->getMetaDescription() : $category->getDescription(),
            );

            $this->view->headTitle($category->getMetaTitle() ? $category->getMetaTitle() : $category->getName());
        } else {
            throw new CoreShop\Exception(sprintf('Category with id "%s" not found', $id));
        }
    }

    protected function parseSorting($sortString)
    {
        $allowed = array('name', 'price');
        $sort = array(
            'name' => 'name',
            'direction' => 'asc',
        );

        $sortString = explode('_', $sortString);

        if (count($sortString) < 2) {
            return $sort;
        }

        $name = strtolower($sortString[0]);
        $direction = strtolower($sortString[1]);

        if (in_array($name, $allowed) && in_array($direction, array('desc', 'asc'))) {
            return array(
                'name' => $name,
                'direction' => $direction,
            );
        }

        return $sort;
    }

    /**
     * get similar products based on filter
     *
     * @param \CoreShop\Model\Product $product
     * @param \CoreShop\Model\Product\Filter $filter
     * @return array|\CoreShop\Model\Product[]
     */
    protected function getSimilarProducts(\CoreShop\Model\Product $product, \CoreShop\Model\Product\Filter $filter)
    {
        $index = $filter->getIndex();

        if(!$index instanceof CoreShop\Model\Index) {
            return array();
        }

        $indexService = \CoreShop\IndexService::getIndexService()->getWorker($index->getName());

        $productList = $indexService->getProductList();
        $productList->setVariantMode(\CoreShop\Model\Product\Listing::VARIANT_MODE_INCLUDE_PARENT_OBJECT);
        $similarityFields = $filter->getSimilarities();

        if (is_array($similarityFields) && count($similarityFields) > 0) {
            $statement = $productList->buildSimilarityOrderBy($filter->getSimilarities(), $product->getId());
        }

        if (!empty($statement)) {
            $productList->setLimit(2);
            $productList->setOrder("ASC");
            $productList->addCondition("o_virtualProductId != " . $product->getId(), "o_id");
            
            /*if($filterDefinition->getCrossSellingCategory()) {
                $productList->setCategory($filterDefinition->getCrossSellingCategory());
            }*/
            $productList->setOrderKey($statement);

            return $productList->load();
        }

        return array();
    }
}

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

use Pimcore\Controller\Action\Admin;
use Pimcore\Tool as PimTool;

/**
 * Class CoreShop_Admin_ProductSpecificPriceController
 */
class CoreShop_Admin_ProductSpecificPriceController extends Admin
{
    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = array('list');
        if (!in_array($this->getParam('action'), $notRestrictedActions)) {
            $this->checkPermission("coreshop_permission_productSpecificPrices");
        }
    }
    public function listAction()
    {
        $product = \CoreShop\Model\Product::getById($this->getParam('product'));

        if ($product instanceof \CoreShop\Model\Product) {
            $prices = $product->getSpecificPrices();
            $data = array();

            foreach ($prices as $price) {
                $data[] = $this->getSpecificPriceTreeNodeConfig($price);
            }

            $this->_helper->json(array('success' => true, 'data' => $data));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    protected function getSpecificPriceTreeNodeConfig($price)
    {
        $tmpPriceRule = array(
            'id' => $price->getId(),
            'text' => $price->getName(),
            'qtipCfg' => array(
                'title' => 'ID: '.$price->getId(),
            ),
            'type' => $price->getType(),
            'name' => $price->getName(),
        );

        return $tmpPriceRule;
    }

    public function getConfigAction()
    {
        $this->_helper->json(array(
            'success' => true,
            'conditions' => \CoreShop\Model\Product\SpecificPrice::$availableConditions,
            'actions' => \CoreShop\Model\Product\SpecificPrice::$availableActions,
        ));
    }

    public function addAction()
    {
        $product = \CoreShop\Model\Product::getById($this->getParam('product'));
        $name = $this->getParam('name');

        if (strlen($name) <= 0 && $product instanceof \CoreShop\Model\Product) {
            $this->helper->json(array('success' => false, 'message' => $this->getTranslator()->translate('Name must be set')));
        } else {
            $specificPrice = new \CoreShop\Model\Product\SpecificPrice();
            $specificPrice->setName($name);
            $specificPrice->setO_Id($product->getId());
            $specificPrice->setInherit(false);
            $specificPrice->save();

            $this->_helper->json(array('success' => true, 'data' => $specificPrice));
        }
    }

    public function getAction()
    {
        $id = $this->getParam('id');
        $specificPrice = \CoreShop\Model\Product\SpecificPrice::getById($id);

        if ($specificPrice instanceof \CoreShop\Model\Product\SpecificPrice) {
            $this->_helper->json(array('success' => true, 'data' => $specificPrice->getObjectVars()));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function saveAction()
    {
        $id = $this->getParam('id');
        $data = $this->getParam('data');
        $specificPrice = \CoreShop\Model\Product\SpecificPrice::getById($id);

        if ($data && $specificPrice instanceof \CoreShop\Model\Product\SpecificPrice) {
            $data = \Zend_Json::decode($this->getParam('data'));

            $conditions = $data['conditions'];
            $actions = $data['actions'];
            $actionInstances = array();
            $conditionInstances = array();

            $actionNamespace = 'CoreShop\\Model\\PriceRule\\Action\\';
            $conditionNamespace = 'CoreShop\\Model\\PriceRule\\Condition\\';

            foreach ($conditions as $condition) {
                $class = $conditionNamespace.ucfirst($condition['type']);

                if (PimTool::classExists($class)) {
                    $instance = new $class();
                    $instance->setValues($condition);

                    $conditionInstances[] = $instance;
                } else {
                    throw new \CoreShop\Exception(sprintf('Condition with type %s not found'), $condition['type']);
                }
            }

            foreach ($actions as $action) {
                $class = $actionNamespace.ucfirst($action['type']);

                if (PimTool::classExists($class)) {
                    $instance = new $class();
                    $instance->setValues($action);

                    $actionInstances[] = $instance;
                } else {
                    throw new \CoreShop\Exception(sprintf('Action with type %s not found'), $action['type']);
                }
            }

            $specificPrice->setValues($data['settings']);
            $specificPrice->setActions($actionInstances);
            $specificPrice->setConditions($conditionInstances);
            $specificPrice->save();

            $product = \CoreShop\Model\Product::getById($specificPrice->getO_Id());

            if ($product instanceof \CoreShop\Model\Product) {
                $product->clearPriceCache();
            }

            $this->_helper->json(array('success' => true, 'data' => $specificPrice));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function deleteAction()
    {
        $id = $this->getParam('id');
        $specificPrice = \CoreShop\Model\Product\SpecificPrice::getById($id);

        if ($specificPrice instanceof \CoreShop\Model\Product\SpecificPrice) {
            $product = \CoreShop\Model\Product::getById($specificPrice->getO_Id());

            if ($product instanceof \CoreShop\Model\Product) {
                $product->clearPriceCache();
            }

            $specificPrice->delete();

            $this->_helper->json(array('success' => true));
        }

        $this->_helper->json(array('success' => false));
    }
}

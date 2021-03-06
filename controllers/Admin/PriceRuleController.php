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

use CoreShop\Model\Cart\PriceRule;
use Pimcore\Controller\Action\Admin;
use Pimcore\Tool as PimTool;

/**
 * Class CoreShop_Admin_PriceRuleController
 */
class CoreShop_Admin_PriceRuleController extends Admin
{
    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = array('list');
        if (!in_array($this->getParam('action'), $notRestrictedActions)) {
            $this->checkPermission('coreshop_permission_priceRules');
        }
    }

    public function listAction()
    {
        $list = PriceRule::getList();

        $data = array();
        if (is_array($list->getData())) {
            foreach ($list->getData() as $pricerule) {
                $data[] = $this->getTreeNodeConfig($pricerule);
            }
        }
        $this->_helper->json($data);
    }

    protected function getTreeNodeConfig($priceRule)
    {
        $tmpPriceRule = array(
            'id' => $priceRule->getId(),
            'text' => $priceRule->getName(),
            'qtipCfg' => array(
                'title' => 'ID: '.$priceRule->getId(),
            ),
            'name' => $priceRule->getName(),
        );

        return $tmpPriceRule;
    }

    public function getConfigAction()
    {
        $this->_helper->json(array(
            'success' => true,
            'conditions' => PriceRule::$availableConditions,
            'actions' => PriceRule::$availableActions,
        ));
    }

    public function addAction()
    {
        $name = $this->getParam('name');

        if (strlen($name) <= 0) {
            $this->helper->json(array('success' => false, 'message' => $this->getTranslator()->translate('Name must be set')));
        } else {
            $priceRule = new PriceRule();
            $priceRule->setName($name);
            $priceRule->setActive(0);
            $priceRule->setHighlight(0);
            $priceRule->save();

            $this->_helper->json(array('success' => true, 'data' => $priceRule));
        }
    }

    public function getAction()
    {
        $id = $this->getParam('id');
        $priceRule = PriceRule::getById($id);

        if ($priceRule instanceof PriceRule) {
            $this->_helper->json(array('success' => true, 'data' => $priceRule->getObjectVars()));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function saveAction()
    {
        $id = $this->getParam('id');
        $data = $this->getParam('data');
        $priceRule = PriceRule::getById($id);

        if ($data && $priceRule instanceof PriceRule) {
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
                    throw new \CoreShop\Exception(sprintf('Condition with type %s not found', $condition['type']));
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

            $priceRule->setValues($data['settings']);
            $priceRule->setActions($actionInstances);
            $priceRule->setConditions($conditionInstances);
            $priceRule->save();

            $this->_helper->json(array('success' => true, 'data' => $priceRule));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function deleteAction()
    {
        $id = $this->getParam('id');
        $priceRule = PriceRule::getById($id);

        if ($priceRule instanceof PriceRule) {
            $priceRule->delete();

            $this->_helper->json(array('success' => true));
        }

        $this->_helper->json(array('success' => false));
    }
}

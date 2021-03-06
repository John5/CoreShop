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

use CoreShop\Model\Carrier;
use Pimcore\Controller\Action\Admin;

/**
 * Class CoreShop_Admin_CarrierController
 */
class CoreShop_Admin_CarrierController extends Admin
{
    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = array('list');
        if (!in_array($this->getParam('action'), $notRestrictedActions)) {
            $this->checkPermission('coreshop_permission_carriers');
        }
    }

    public function listAction()
    {
        $list = Carrier::getList();

        $data = array();
        if (is_array($list->getData())) {
            foreach ($list->getData() as $carrier) {
                $data[] = $this->getTreeNodeConfig($carrier);
            }
        }
        $this->_helper->json($data);
    }

    public function getCarriersAction()
    {
        $list = Carrier::getList();
        $list->setOrder('ASC');
        $list->setOrderKey('name');
        $list->load();

        $carriers = array();
        if (is_array($list->getData())) {
            foreach ($list->getData() as $carrier) {
                $carriers[] = $this->getTreeNodeConfig($carrier);
            }
        }

        $this->_helper->json($carriers);
    }

    protected function getTreeNodeConfig($carrier)
    {
        $tmpCarrier = array(
            'id' => $carrier->getId(),
            'text' => $carrier->getName(),
            'qtipCfg' => array(
                'title' => 'ID: '.$carrier->getId(),
            ),
            'name' => $carrier->getName(),
        );

        return $tmpCarrier;
    }

    public function getRangeAction()
    {
        $id = $this->getParam('carrier');
        $carrier = Carrier::getById($id);

        if ($carrier instanceof Carrier) {
            $ranges = $carrier->getRanges();

            $this->_helper->json(array('success' => true, 'total' => count($ranges), 'data' => $ranges));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function getRangeZoneAction()
    {
        $id = $this->getParam('carrier');
        $carrier = Carrier::getById($id);

        if ($carrier instanceof Carrier) {
            $zones = \CoreShop\Model\Zone::getAll();
            $ranges = $carrier->getRanges();
            $prices = array();

            foreach ($zones as $zone) {
                $price = array(
                    'name' => $zone->getName(),
                    'zone' => $zone->getId(),
                );

                foreach ($ranges as $range) {
                    $deliveryPrice = Carrier\DeliveryPrice::getForCarrierInZone($carrier, $range, $zone);

                    $price['range_'.$range->getId()] = $deliveryPrice instanceof Carrier\DeliveryPrice ? $deliveryPrice->getPrice() : 0;
                }

                $prices[] = $price;
            }

            $this->_helper->json(array('success' => true, 'count' => count($prices), 'data' => $prices));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function addAction()
    {
        $name = $this->getParam('name');

        if (strlen($name) <= 0) {
            $this->helper->json(array('success' => false, 'message' => $this->getTranslator()->translate('Name must be set')));
        } else {
            $carrier = new Carrier();
            $carrier->setName($name);
            $carrier->setLabel($name);
            $carrier->setGrade(1);
            $carrier->setIsFree(0);
            $carrier->setShippingMethod('weight');
            $carrier->setRangeBehaviour('largest');
            $carrier->setMaxDepth(0);
            $carrier->setMaxHeight(0);
            $carrier->setMaxWeight(0);
            $carrier->setMaxWidth(0);
            $carrier->setNeedsRange(0);
            $carrier->save();

            $config = $this->getTreeNodeConfig($carrier);
            $config['success'] = true;

            $this->_helper->json(array('success' => true, 'data' => $carrier));
        }
    }

    public function getAction()
    {
        $id = $this->getParam('id');
        $carrier = Carrier::getById($id);

        if ($carrier instanceof Carrier) {
            $this->_helper->json(array('success' => true, 'data' => $carrier));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function saveAction()
    {
        $id = $this->getParam('id');
        $data = $this->getParam('data');
        $carrier = Carrier::getById($id);

        if ($data && $carrier instanceof Carrier) {
            $data = \Zend_Json::decode($this->getParam('data'));

            if ($data['settings']['image']) {
                $asset = \Pimcore\Model\Asset::getById($data['settings']['image']);

                if ($asset instanceof \Pimcore\Model\Asset) {
                    $data['settings']['image'] = $asset->getId();
                }
            }

            $carrier->setValues($data['settings']);

            $ranges = $data['range'];
            $rangesToKeep = array();
            if (is_array($ranges)) {
                foreach ($ranges as &$range) {
                    $rangeObject = null;
                    $deliveryPriceObject = null;

                    if ($range['id']) {
                        $rangeObject = Carrier\AbstractRange::getById($range['id'], $carrier->getShippingMethod());
                    }

                    if (is_null($rangeObject)) {
                        $rangeObject = Carrier\AbstractRange::create($carrier->getShippingMethod());
                    }

                    $rangeObject->setCarrier($carrier);
                    $rangeObject->setDelimiter1($range['delimiter1']);
                    $rangeObject->setDelimiter2($range['delimiter2']);
                    $rangeObject->save();

                    $rangesToKeep[] = $rangeObject->getId();

                    foreach ($range['zones'] as $zoneId => $price) {
                        $zone = \CoreShop\Model\Zone::getById($zoneId);

                        if ($zone instanceof \CoreShop\Model\Zone) {
                            $deliveryPriceObject = Carrier\DeliveryPrice::getForCarrierInZone($carrier, $rangeObject, $zone);

                            if (is_null($deliveryPriceObject)) {
                                $deliveryPriceObject = new Carrier\DeliveryPrice();

                                $deliveryPriceObject->setZone($zone);
                                $deliveryPriceObject->setCarrier($carrier);
                                $deliveryPriceObject->setRange($rangeObject);
                                $deliveryPriceObject->setRangeType($carrier->getShippingMethod());
                            }

                            $deliveryPriceObject->setPrice($price);

                            if (is_numeric($price)) {
                                $deliveryPriceObject->save();
                            } else {
                                $deliveryPriceObject->delete();
                            }
                        }
                    }
                }
            }

            if (count($rangesToKeep) > 0) {
                $carrier->setNeedsRange(true);
            } else {
                $carrier->setNeedsRange(false);
            }

            $carrier->save();

            $ranges = $carrier->getRanges();

            foreach ($ranges as $range) {
                if (!in_array($range->getId(), $rangesToKeep)) {
                    $range->delete();
                }
            }

            $this->_helper->json(array('success' => true, 'data' => $carrier, 'ranges' => $carrier->getRanges()));
        } else {
            $this->_helper->json(array('success' => false));
        }
    }

    public function deleteAction()
    {
        $id = $this->getParam('id');
        $carrier = Carrier::getById($id);

        if ($carrier instanceof Carrier) {
            $carrier->delete();

            $this->_helper->json(array('success' => true));
        }

        $this->_helper->json(array('success' => false));
    }
}

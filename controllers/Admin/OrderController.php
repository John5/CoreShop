<?php
/**
 * CoreShop
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015 Dominik Pfaffenbauer (http://dominik.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

use CoreShop\Plugin;
use CoreShop\Tool;
use CoreShop\Model\Currency;
use Pimcore\Controller\Action\Admin;

class CoreShop_Admin_OrderController extends Admin
{
    public function getOrdersAction() {
        $list = new \Pimcore\Model\Object\CoreShopOrder\Listing();
        $list->setLimit($this->getParam("limit", 30));
        $list->setOffset($this->getParam("page", 1) - 1);

        if($this->getParam("filter", null)) {
            $conditionFilters[] = \Pimcore\Model\Object\Service::getFilterCondition($this->getParam("filter"), \Pimcore\Model\Object\ClassDefinition::getByName("CoreShopOrder"));
            if(count($conditionFilters) > 0 && $conditionFilters[0] !== '(())')
            $list->setCondition(implode(" AND ", $conditionFilters));
        }

        $sortingSettings = \Pimcore\Admin\Helper\QueryParams::extractSortingSettings($this->getAllParams());

        $order = "DESC";
        $orderKey = "o_id";

        if ($sortingSettings['order']) {
            $order = $sortingSettings['order'];
        }
        if (strlen($sortingSettings['orderKey']) > 0) {
            $orderKey = $sortingSettings['orderKey'];
        }

        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        $orders = $list->load();
        $jsonOrders = array();

        foreach($orders as $order) {
            $jsonOrders[] = $this->prepareOrder($order);
        }

        $this->_helper->json(array("success" => true, "data" => $jsonOrders, "count" => count($jsonOrders), "total" => $list->getTotalCount()));
    }

    protected function prepareOrder(\CoreShop\Model\Order $order) {
        $element = array(
            "o_id" => $order->getId(),
            "orderState" => $order->getOrderState() instanceof \CoreShop\Model\OrderState ? $order->getOrderState()->getId() : null,
            "orderDate" => $order->getOrderDate() instanceof \Pimcore\Date ? $order->getOrderDate()->format("Y-m-d") : null,
            "orderNumber" => $order->getOrderNumber(),
            "lang" => $order->getLang(),
            "carrier" => $order->getCarrier()->getId(),
            "priceRule" => $order->getPriceRule() instanceof \CoreShop\Model\PriceRule ? $order->getPriceRule()->getId() : null,
            "discount" => $order->getDiscount(),
            "subtotal" => $order->getSubtotal(),
            "shipping" => $order->getShipping(),
            "paymentFee" => $order->getPaymentFee(),
            "totalTax" => $order->getTotalTax(),
            "total" => $order->getTotal()
        );

        return $element;
    }

    public function getInvoiceForOrderAction() {
        $orderId = $this->getParam("id");
        $order = \CoreShop\Model\Order::getById($orderId);

        if($order instanceof \CoreShop\Model\Order) {
            $invoice = $order->getProperty("invoice");

            if($invoice instanceof \Pimcore\Model\Asset\Document) {
                $this->_helper->json(array("success" => true, "assetId" => $invoice->getId()));
            }
        }

        $this->_helper->json(array("success" => false));
    }
}
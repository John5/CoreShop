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

use CoreShop\Plugin;
use Pimcore\Controller\Action\Admin;

/**
 * Class CoreShop_Admin_InstallController
 */
class CoreShop_Admin_InstallController extends Admin
{
    public function installAction()
    {
        try {
            $install = new Plugin\Install();

            \Pimcore::getEventManager()->trigger('coreshop.install.pre', null, array('installer' => $install));

            //install Data
            $install->installObjectData('orderStates', 'Order\\');
            $install->installObjectData('threadStates', 'Messaging\\Thread\\');
            $install->installObjectData('threadContacts', 'Messaging\\');
            $install->installDocuments('documents');
            $install->installMessagingMails();
            $install->installMessagingContacts();

            $install->createFieldCollection('CoreShopUserAddress');
            $install->createFieldCollection('CoreShopOrderTax');

            // create object classes
            $categoryClass = $install->createClass('CoreShopCategory');
            $productClass = $install->createClass('CoreShopProduct');
            $cartClass = $install->createClass('CoreShopCart');
            $cartItemClass = $install->createClass('CoreShopCartItem');
            $userClass = $install->createClass('CoreShopUser');

            $orderItemClass = $install->createClass('CoreShopOrderItem');
            $paymentClass = $install->createClass('CoreShopPayment');
            $orderClass = $install->createClass('CoreShopOrder');

            // create root object folder with subfolders
            $coreShopFolder = $install->createFolders();
            // create custom view for blog objects
            $install->createCustomView($coreShopFolder, array(
                $productClass->getId(),
                $categoryClass->getId(),
                $cartClass->getId(),
                $cartItemClass->getId(),
                $userClass->getId(),
                $orderItemClass->getId(),
                $orderClass->getId(),
                $paymentClass->getId(),
            ));
            // create static routes
            $install->createStaticRoutes();
            // create predefined document types
            //$install->createDocTypes();

            $install->installAdminTranslations(PIMCORE_PLUGINS_PATH.'/CoreShop/install/translations/admin.csv');

            $install->createImageThumbnails();

            \Pimcore::getEventManager()->trigger('coreshop.install.post', null, array('installer' => $install));

            $install->setConfigInstalled();

            $success = true;
        } catch (Exception $e) {
            \Logger::crit($e);
            throw $e;
            $success = false;
        }

        $this->_helper->json(array('success' => $success));
    }
}

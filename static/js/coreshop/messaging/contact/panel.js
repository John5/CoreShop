/**
 * CoreShop
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

pimcore.registerNS('pimcore.plugin.coreshop.messaging.contact.panel');
pimcore.plugin.coreshop.messaging.contact.panel = Class.create(pimcore.plugin.coreshop.messaging.panel, {

    /**
     * @var string
     */
    layoutId: 'coreshop_messaging_contacts_panel',
    storeId : 'coreshop_messaging_contacts',
    iconCls : 'coreshop_icon_messaging_contact',
    type : 'contact',

    url : {
        add : '/plugin/CoreShop/admin_messaging-contact/add',
        delete : '/plugin/CoreShop/admin_messaging-contact/delete',
        get : '/plugin/CoreShop/admin_messaging-contact/get',
        list : '/plugin/CoreShop/admin_messaging-contact/list'
    }
});

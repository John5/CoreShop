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

pimcore.registerNS('pimcore.object.classes.data.coreShopShop');
pimcore.object.classes.data.coreShopShop = Class.create(pimcore.plugin.coreshop.object.classes.data.data, {

    type: 'coreShopShop',

    getTypeName: function () {
        return t('coreshop_shops');
    },

    getGroup: function () {
        return 'coreshop';
    },

    getIconClass: function () {
        return 'coreshop_icon_shop';
    }
});

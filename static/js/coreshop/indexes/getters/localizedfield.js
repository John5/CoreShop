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

pimcore.registerNS('pimcore.plugin.coreshop.indexes.getters.localizedfield');

pimcore.plugin.coreshop.indexes.getters.localizedfield = Class.create(pimcore.plugin.coreshop.indexes.getters.abstract, {

    getLayout : function (record) {
        return [
            {
                xtype : 'textfield',
                fieldLabel : t('language'),
                name : 'locale',
                length : 255,
                value : record.data.getterConfig ? record.data.getterConfig.locale : null
            },
        ];
    }

});

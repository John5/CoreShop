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

pimcore.registerNS('pimcore.plugin.coreshop.report.monitoring.reports.emptyCategories');
pimcore.plugin.coreshop.report.monitoring.reports.emptyCategories = Class.create(pimcore.plugin.coreshop.report.monitoring.abstract, {

    url : '/plugin/CoreShop/admin_reports/get-empty-categories-monitoring',

    getName: function () {
        return t('coreshop_monitoring_emptyCategories');
    },

    getIconCls: function () {
        return 'coreshop_icon_category';
    },

    getGrid : function () {
        return new Ext.Panel({
            layout:'fit',
            height: 275,
            items: {
                xtype : 'grid',
                store: this.getStore(),
                columns : [
                    {
                        text: t('id'),
                        dataIndex : 'id',
                        width : 100
                    },
                    {
                        text: t('name'),
                        dataIndex : 'name',
                        flex : 1
                    }
                ],
                listeners : {
                    rowclick : function (grid, record) {
                        var d = record.data;

                        pimcore.helpers.openObject(d.id, 'object');
                    }
                }
            }
        });
    }
});

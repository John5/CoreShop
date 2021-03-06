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

pimcore.registerNS('pimcore.plugin.coreshop.report.reports.products');
pimcore.plugin.coreshop.report.reports.products = Class.create(pimcore.plugin.coreshop.report.abstract, {

    url : '/plugin/CoreShop/admin_reports/get-products-report',

    getName: function () {
        return t('coreshop_report_products');
    },

    getIconCls: function () {
        return 'coreshop_icon_product';
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
                        text: t('coreshop_report_products_name'),
                        dataIndex : 'name',
                        flex : 1
                    },
                    {
                        text: t('coreshop_report_products_count'),
                        dataIndex : 'count',
                        width : 50,
                        align : 'right'
                    },
                    {
                        text: t('coreshop_report_products_salesPrice'),
                        dataIndex : 'salesPrice',
                        width : 100,
                        align : 'right'
                    },
                    {
                        text: t('coreshop_report_products_sales'),
                        dataIndex : 'sales',
                        width : 100,
                        align : 'right'
                    },
                    {
                        text: t('coreshop_report_products_profit'),
                        dataIndex : 'profit',
                        width : 100,
                        align : 'right'
                    }
                ]
            }
        });
    }
});

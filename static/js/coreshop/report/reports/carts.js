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

pimcore.registerNS('pimcore.plugin.coreshop.report.reports.carts');
pimcore.plugin.coreshop.report.reports.carts = Class.create(pimcore.plugin.coreshop.report.abstract, {

    url : '/plugin/CoreShop/admin_reports/get-orders-carts-report',

    getName: function () {
        return t('coreshop_report_carts');
    },

    getIconCls: function () {
        return 'coreshop_icon_report_carts';
    },

    getGrid : function () {
        var panel = new Ext.Panel({
            layout:'fit',
            height: 275,
            items: {
                xtype: 'cartesian',
                store: this.getStore(),
                legend: {
                    docked: 'right'
                },
                interactions: ['itemhighlight',
                    {
                        type: 'panzoom',
                        zoomOnPanGesture: true
                    }
                ],
                axes: [{
                    type: 'numeric',
                    fields: ['carts', 'orders'],
                    position: 'left',
                    grid: true,
                    minimum: 0
                }, {
                        type: 'category',
                        fields: 'datetext',
                        position: 'bottom'
                    }
                ],
                series: [
                    {
                        type: 'line',
                        axis:' left',
                        title: t('coreshop_cart'),
                        xField: 'datetext',
                        yField: 'carts',
                        colors: ['#01841c'],
                        style: {
                            lineWidth: 2,
                            stroke: '#01841c'
                        },
                        marker: {
                            radius: 4,
                            fillStyle: '#01841c'
                        },
                        highlight: {
                            fillStyle: '#000',
                            radius: 5,
                            lineWidth: 2,
                            strokeStyle: '#fff'
                        },
                        tooltip: {
                            trackMouse: true,
                            style: 'background: #01841c',
                            renderer: function (tooltip, storeItem, item) {
                                var title = item.series.getTitle();
                                tooltip.setHtml(title + ' for ' + storeItem.get('datetext') + ': ' + storeItem.get(item.series.getYField()));
                            }
                        }
                    },
                    {
                        type:'line',
                        axis:' left',
                        title: t('coreshop_order'),
                        xField: 'datetext',
                        yField: 'orders',
                        colors: ['#15428B'],
                        style: {
                            lineWidth: 2,
                            stroke: '#15428B'
                        },
                        marker: {
                            radius: 4,
                            fillStyle: '#15428B'
                        },
                        highlight: {
                            fillStyle: '#000',
                            radius: 5,
                            lineWidth: 2,
                            strokeStyle: '#fff'
                        },
                        tooltip: {
                            trackMouse: true,
                            style: 'background: #00bfff',
                            renderer: function (tooltip, storeItem, item) {
                                var title = item.series.getTitle();
                                tooltip.setHtml(title + ' for ' + storeItem.get('datetext') + ': ' + storeItem.get(item.series.getYField()));
                            }
                        }
                    }
                ]
            }
        });

        return panel;
    }
});

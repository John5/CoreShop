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

pimcore.registerNS('pimcore.plugin.coreshop.orders.invoice');
pimcore.plugin.coreshop.orders.invoice = Class.create({
    initialize: function (element) {
        this.panels = [];
        this.element = element;
    },

    getLayout: function ()
    {
        if (!this.layout) {
            // create new panel
            this.layout = new Ext.Panel({
                title: t('coreshop_orders_invoice'),
                iconCls: 'coreshop_icon_orders_invoice',
                border: false,
                layout: 'border',
                items : []
            });
        }

        return this.layout;
    },

    reload : function () {
        Ext.Ajax.request({
            url: '/plugin/CoreShop/admin_order/get-invoice-for-order',
            params : { id : this.element.id },
            method: 'GET',
            success: function (result)
            {
                var response = Ext.decode(result.responseText);

                this.layout.removeAll(true);

                if (response.success) {
                    this.layout.add(this.loadDocument(response.assetId));
                } else {
                    this.layout.add(this.showEmptyPanel());
                }
            }.bind(this)
        });
    },

    loadDocument : function (invoiceId) {
        var frameUrl = '/admin/asset/get-preview-document/id/' + invoiceId + '/';

        //check for native/plugin PDF viewer
        if (this.hasNativePDFViewer()) {
            frameUrl += '?native-viewer=true';
        }

        var editPanel = new Ext.Panel({
            bodyCls: 'pimcore_overflow_scrolling',
            html: '<iframe src="' + frameUrl + '" frameborder="0" id="coreshop_invoice_preview_' + invoiceId + '"></iframe>',
            region : 'center'
        });
        editPanel.on('resize', function (el, width, height, rWidth, rHeight) {
            Ext.get('coreshop_invoice_preview_' + invoiceId).setStyle({
                width: width + 'px',
                height: (height) + 'px'
            });
        }.bind(this));

        return editPanel;
    },

    showEmptyPanel : function () {
        var emptyPanel = new Ext.Panel({
            html : '<p>' + t('coreshop_invoice_not_generated') + '</p>',
            region : 'center'
        });

        return emptyPanel;
    },

    hasNativePDFViewer: function () {

        var getActiveXObject = function (name) {
            try { return new ActiveXObject(name); } catch (e) {}
        };

        var getNavigatorPlugin = function (name) {
            for (key in navigator.plugins) {
                var plugin = navigator.plugins[key];
                if (plugin.name == name) return plugin;
            }
        };

        var getPDFPlugin = function () {
            return this.plugin = this.plugin || (function () {
                    if (typeof window['ActiveXObject'] != 'undefined') {
                        return getActiveXObject('AcroPDF.PDF') || getActiveXObject('PDF.PdfCtrl');
                    } else {
                        return getNavigatorPlugin('Adobe Acrobat') || getNavigatorPlugin('Chrome PDF Viewer') || getNavigatorPlugin('WebKit built-in PDF');
                    }
                })();
        };

        return !!getPDFPlugin();
    }
});

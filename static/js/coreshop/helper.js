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

//pimcore.helpers.openElement = function (id, type, subtype) {

pimcore.registerNS('coreshop.helpers.x');
pimcore.registerNS('coreshop.util.format.currency');

coreshop.helpers.openOrderByNumberDialog = function (keyCode, e) {

    if (e['stopEvent']) {
        e.stopEvent();
    }

    Ext.MessageBox.prompt(t('coreshop_order_by_number'), t('coreshop_please_enter_the_number_of_the_order'),
        function (button, value) {
            if (button == 'ok' && !Ext.isEmpty(value)) {
                coreshop.helpers.openOrderByNumber(value);
            }
        });
};

coreshop.helpers.openOrderByNumber = function (orderNumber) {
    Ext.Ajax.request({
        url: '/plugin/CoreShop/admin_helper/get-order',
        params: {
            orderNumber: orderNumber
        },
        success: function (response) {
            var res = Ext.decode(response.responseText);
            if (res.success) {
                coreshop.helpers.openOrder(res.id);
            } else {
                Ext.MessageBox.alert(t('error'), t('element_not_found'));
            }
        }
    });
};

coreshop.helpers.openProductByArticleNumber = function (articleNumber) {

};

coreshop.util.format.currency = function (currency, v) {
    v = (Math.round((v - 0) * 100)) / 100;
    v = (v == Math.floor(v)) ? v + '.00' : ((v * 10 == Math.floor(v * 10)) ? v + '0' : v);
    v = String(v);
    var ps = v.split('.'),
        whole = ps[0],
        sub = ps[1] ? '.' + ps[1] : '.00',
        r = /(\d+)(\d{3})/;
    while (r.test(whole)) {
        whole = whole.replace(r, '$1' + ',' + '$2');
    }

    v = whole + sub;
    if (v.charAt(0) == '-') {
        return '-' + currency + v.substr(1);
    }

    return currency + ' ' + v;
};

coreshop.helpers.showAbout = function () {

    var html = '<div class="pimcore_about_window">';
    html += '<br><img src="/plugins/CoreShop/static/img/logo.svg" style="width: 60px;"><br>';
    html += '<br><b>Version: ' + coreshop.settings.plugin.pluginVersion + '</b>';
    html += '<br><b>Build: ' + coreshop.settings.plugin.pluginRevision + '</b>';
    html += '<br><br>&copy; by Dominik Pfaffenbauer, Wels, Austria (<a href="http://www.coreshop.org/" target="_blank">coreshop.org</a>)';
    html += '<br><br><a href="https://github.com/coreshop/coreshop/blob/master/LICENSE.md" target="_blank">License</a> | ';
    html += '<a href="http://www.coreshop.org/contact.html" target="_blank">Contact</a>';
    html += '</div>';

    var win = new Ext.Window({
        title: t('about'),
        width:500,
        height: 300,
        bodyStyle: 'padding: 10px;',
        modal: true,
        html: html
    });

    win.show();
};

coreshop.helpers.constrastColor = function (color) {
    return (parseInt(color.replace('#', ''), 16) > 0xffffff / 2) ? 'black' : 'white';
};

coreshop.helpers.openOrder = function (id) {
    if (pimcore.globalmanager.exists('coreshop_order_' + id) == false) {
        Ext.Ajax.request({
            url: '/plugin/CoreShop/admin_order/detail',
            params: {
                id: id
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);

                if (res.success) {
                    pimcore.globalmanager.add('coreshop_order_' + id, new pimcore.plugin.coreshop.orders.order(res.order));
                } else {
                    //TODO: Show messagebox
                    Ext.Msg.alert(t('open_target'), t('problem_opening_new_target'));
                }

            }.bind(this)
        });
    } else {
        var tab = pimcore.globalmanager.get('coreshop_order_' + id);
        tab.activate();
    }
};

String.prototype.ucfirst = function () {
    return this.charAt(0).toUpperCase() + this.slice(1).toLowerCase();
};

if (!String.prototype.format) {
    String.prototype.format = function () {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function (match, number) {
            return typeof args[number] != 'undefined'
                ? args[number]
                : match
                ;
        });
    };
}

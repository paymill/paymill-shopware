/**
 * paymill order operations window
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2015 PAYMILL GmbH (https://www.paymill.com)
 */
    ////{block name="backend/order/view/detail/window" append}
    //{namespace name=backend/order/view/main}
Ext.define('Shopware.apps.PaymillOrderOperations.view.main.Window', {
    override:       'Shopware.apps.Order.view.detail.Window',
    createTabPanel: function ()
    {
        var me = this;
        var tabPanel = me.callParent(arguments);
        if (me.displayTab()) {
            tabPanel.add(Ext.create('Shopware.apps.PaymillOrderOperations.view.main.Panel', {
                title:              '{s namespace=paymill name=operations_title}Paymill Order Operations{/s}',
                id:                 'pmOrderOperationsTab',
                historyStore:       me.historyStore,
                record:             me.record,
                orderStatusStore:   me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore,
            }));
        }
        return          tabPanel;
    },
    displayTab:     function ()
    {
        var id = this.record.get('id');
        var result = false;
        Ext.Ajax.request({
            url:     '{url controller=PaymillOrderOperations action=displayTab}',
            method:  'POST',
            async:   false,
            params:  {
                orderId: id
            },
            success: function (response)
            {
                var decodedResponse = Ext.decode(response.responseText);
                result = decodedResponse.success;
            }
        });
        return result;
    }
});
//{/block}
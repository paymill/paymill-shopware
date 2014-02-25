/**
 * paymill order operations panel
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
    //{namespace name=backend/order/main}
Ext.require([
    'Ext.grid.*', 'Ext.data.*', 'Ext.panel.*'
]);

//{block name="backend/order/view/detail/paymill"}
Ext.define('Shopware.apps.PaymillOrderOperations.view.main.Panel', {

    extend:        'Ext.form.Panel',
    autoScroll:    true,
    initComponent: function ()
    {
        var me = this;
        var id = me.record.get('id');
        var transactionStore = Ext.create('Shopware.apps.PaymillOrderOperations.store.Paymilltransaction');
        me.items = [
            Ext.create('Ext.panel.Panel', {
                width:     '100%',
                height:    '100%',
                bodyStyle: {
                    background: '#F0F2F4'
                },
                items:     [
                    Ext.create('Ext.grid.Panel', {
                        id: 'transactionGrid',
                        store:     transactionStore.load({
                            params: {
                                'orderId': id
                            }
                        }),
                        listeners: {
                            activate: function (tab)
                            {
                                var me = this;
                                var store = transactionStore.load({
                                    params: {
                                        'orderId': id
                                    }
                                });
                                me.reconfigure(store);
                            }
                        },
                        columns:   [
                            {
                                header:    'Date',
                                dataIndex: 'entryDate',
                                flex:      1
                            },
                            {
                                header:    'Description',
                                dataIndex: 'description',
                                flex:      4
                            },
                            {
                                header:    'Amount',
                                dataIndex: 'amount',
                                flex:      1
                            }
                        ]
                    }), {
                        xtype:  'fieldset',
                        width:  '100%',
                        id:     'buttonSlot',
                        layout: {
                            type:  'hbox',
                            pack:  'end',
                            align: 'middle'
                        }
                    }
                ]
            })

        ];
        this.callParent(arguments);
        this.displayButtons();
    },

    canCapture: function ()
    {
        var id = this.record.get('id');
        var success = false;
        Ext.Ajax.request({
            url:     '{url controller=PaymillOrderOperations action=canCapture}',
            method:  'POST',
            async:   false,
            params:  {
                orderId: id
            },
            success: function (response)
            {
                var decodedResponse = Ext.decode(response.responseText);
                success = decodedResponse.success;
            }
        });
        return success;
    },

    capture: function ()
    {
        var id = this.record.get('id');
        var me = this;
        Ext.Ajax.request({
            url:     '{url controller=PaymillOrderOperations action=capture}',
            method:  'POST',
            async:   false,
            params:  {
                orderId: id
            },
            success: function (response)
            {
                var decodedResponse = Ext.decode(response.responseText);
                var messageText = "";
                if (decodedResponse.success) {
                    messageText = "{s namespace=Paymill name=paymill_backend_order_operations_capture_success}Transaction captured successfully.{/s}";
                } else {
                    messageText = "{s namespace=Paymill name=paymill_backend_order_operations_capture_failure}Transaction could not be captured: {/s}";
                    messageText += decodedResponse.code;
                }
                if (decodedResponse.success) {
                    me.displayButtons();
                    var transactionStore = Ext.create('Shopware.apps.PaymillOrderOperations.store.Paymilltransaction');
                    var store = transactionStore.load({
                        params: {
                            'orderId': id
                        }
                    });
                    Ext.ComponentManager.get('transactionGrid').reconfigure(store);
                }
                alert(messageText);
            }
        });
    },

    canRefund: function ()
    {
        var id = this.record.get('id');
        var success = false;
        Ext.Ajax.request({
            url:     '{url controller=PaymillOrderOperations action=canRefund}',
            method:  'POST',
            async:   false,
            params:  {
                orderId: id
            },
            success: function (response)
            {
                var decodedResponse = Ext.decode(response.responseText);
                success = decodedResponse.success;

            }
        });
        return success;
    },

    refund:         function ()
    {
        var id = this.record.get('id');
        var me = this;
        Ext.Ajax.request({
            url:     '{url controller=PaymillOrderOperations action=refund}',
            method:  'POST',
            async:   false,
            params:  {
                orderId: id
            },
            success: function (response)
            {
                var decodedResponse = Ext.decode(response.responseText);
                var messageText = "";
                if (decodedResponse.success) {
                    messageText = "{s namespace=Paymill name=paymill_backend_order_operations_refund_success}Transaction refunded successfully.{/s}";
                } else {
                    messageText = "{s namespace=Paymill name=paymill_backend_order_operations_refund_failure}Transaction could not be refunded: {/s}";
                    messageText += decodedResponse.code;
                }
                if (decodedResponse.success) {
                    me.displayButtons();
                    var transactionStore = Ext.create('Shopware.apps.PaymillOrderOperations.store.Paymilltransaction');
                    var store = transactionStore.load({
                        params: {
                            'orderId': id
                        }
                    });
                    Ext.ComponentManager.get('transactionGrid').reconfigure(store);
                }
                alert(messageText);
            }
        });
    },
    displayButtons: function ()
    {
        var me = this;
        var button = new Array();
        button.push(Ext.create('Ext.Button', {
            text:     '{s namespace=paymill name=paymill_backend_order_operations_refund_button}Refund{/s}',
            scale:    'medium',
            disabled: !(me.canRefund()),
            handler:  function ()
            {
                me.refund();
            }
        }));

        button.push({ xtype: 'splitter' });

        button.push(Ext.create('Ext.Button', {
            text:     '{s namespace=paymill name=paymill_backend_order_operations_capture_button}Capture{/s}',
            scale:    'medium',
            disabled: !(me.canCapture()),
            handler:  function ()
            {
                me.capture();
            }
        }));

        Ext.ComponentManager.get('buttonSlot').removeAll();
        Ext.ComponentManager.get('buttonSlot').add(button);
    }
});
//{/block}
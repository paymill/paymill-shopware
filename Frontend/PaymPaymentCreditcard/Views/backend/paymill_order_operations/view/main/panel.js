/**
 * paymill order operations panel
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
    //{namespace name=backend/order/main}
Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.panel.*'
]);

//{block name="backend/order/view/detail/paymill"}
Ext.define('Shopware.apps.PaymillOrderOperations.view.main.Panel', {

    extend:'Ext.form.Panel',
    autoScroll:true,
    initComponent: function() {
        var me = this;
        me.items = [
            Ext.create('Ext.panel.Panel',{
                width:'100%',
                height:'100%',
                bodyStyle: {
                    background: '#F0F2F4'
                },
                items:[
                    {
                        xtype:'fieldset',
                        collapsible: false,
                        items :[
                            Ext.create('Ext.panel.Panel',{
                                width:'100%',
                                layout:'column',
                                items:[
                                    {
                                        xtype: 'fieldcontainer',
                                        defaultType: 'displayfield',
                                        width:'50%',
                                        items: [
                                            {
                                                value       : "{s namespace=paymill name=paymill_backend_order_operations_capture_description}{/s}"
                                            }
                                        ]
                                    },{
                                        xtype: 'fieldcontainer',
                                        defaultType: 'displayfield',
                                        width:'50%',
                                        items: [
                                            {
                                                value       : "{s namespace=paymill name=paymill_backend_order_operations_refund_description}{/s}"
                                            }
                                        ]
                                    }
                                ]
                            }),

                            Ext.create('Ext.panel.Panel',{
                                width:'100%',
                                layout:'column',
                                items: [
                                    {
                                        xtype: 'fieldcontainer',
                                        defaultType: 'displayfield',
                                        id: 'captureButtonSlot',
                                        width:'50%'
                                    },{
                                        xtype: 'fieldcontainer',
                                        defaultType: 'displayfield',
                                        id: 'refundButtonSlot',
                                        width:'50%'
                                    }
                                ]
                            })
                        ]
                    }
                ]
            })

        ];
        this.callParent(arguments);
        this.displayRefundButton();
        this.displayCaptureButton();
    },

    canCapture: function(){
        var id = this.record.get('id');
        var success = false;
        Ext.Ajax.request({
            url: '{url controller=PaymillOrderOperations action=canCapture}',
            method:'POST',
            async:false,
            params: {
                orderId:id
            },
            success: function(response){
                var decodedResponse = Ext.decode(response.responseText);
                success = decodedResponse.success;
            }
        });
        return success;
    },

    capture: function(){
        var id = this.record.get('id');
        var me = this;
        Ext.Ajax.request({
            url: '{url controller=PaymillOrderOperations action=capture}',
            method:'POST',
            async:false,
            params: {
                orderId:id
            },
            success: function(response){
                var decodedResponse = Ext.decode(response.responseText);
                var messageText = "";
                if (decodedResponse.success) {
                    messageText = "{s namespace=Paymill name=paymill_backend_order_operations_capture_success}Transaction captured successfully.{/s}";
                } else {
                    messageText = "{s namespace=Paymill name=paymill_backend_order_operations_capture_failure}Transaction could not be captured: {/s}";
                    messageText += decodedResponse.code;
                }
                if(decodedResponse.success){
                    me.displayCaptureButton();
                    me.displayRefundButton();
                }
                alert(messageText);
            }
        });
    },

    canRefund: function(){
        var id = this.record.get('id');
        var success = false;
        Ext.Ajax.request({
            url: '{url controller=PaymillOrderOperations action=canRefund}',
            method:'POST',
            async:false,
            params: {
                orderId:id
            },
            success: function(response){
                var decodedResponse = Ext.decode(response.responseText);
                success = decodedResponse.success;

            }
        });
        return success;
    },

    refund: function(){
        var id = this.record.get('id');
        var me = this;
        Ext.Ajax.request({
            url: '{url controller=PaymillOrderOperations action=refund}',
            method:'POST',
            async:false,
            params: {
                orderId:id
            },
            success: function(response){
                var decodedResponse = Ext.decode(response.responseText);
                var messageText = "";
                if (decodedResponse.success) {
                    messageText = "{s namespace=Paymill name=paymill_backend_order_operations_refund_success}Transaction refunded successfully.{/s}";
                } else {
                    messageText = "{s namespace=Paymill name=paymill_backend_order_operations_refund_failure}Transaction could not be refunded: {/s}";
                    messageText += decodedResponse.code;
                }
                if(decodedResponse.success){
                    me.displayRefundButton();
                    me.displayCaptureButton();
                }
                alert(messageText);
            }
        });
    },
    displayRefundButton: function(){
        var me = this;
        var button =  new Array();
        button.push(Ext.create('Ext.Button', {
            text: '{s namespace=paymill name=paymill_backend_order_operations_refund_button}Refund{/s}',
            scale: 'medium',
            margin: '0 0 0 10',
            disabled: !(me.canRefund()),
            handler: function() {
                me.refund();
            }
        }));
        Ext.ComponentManager.get('refundButtonSlot').removeAll();
        Ext.ComponentManager.get('refundButtonSlot').add(button);
    },
    displayCaptureButton: function(){
        var me = this;
        var button =  new Array();
        button.push(Ext.create('Ext.Button', {
            text: '{s namespace=paymill name=paymill_backend_order_operations_capture_button}Capture{/s}',
            scale: 'medium',
            margin: '0 0 0 10',
            disabled: !(me.canCapture()),
            handler: function() {
                me.capture();
            }
        }));
        Ext.ComponentManager.get('captureButtonSlot').removeAll();
        Ext.ComponentManager.get('captureButtonSlot').add(button);
    }
});
//{/block}
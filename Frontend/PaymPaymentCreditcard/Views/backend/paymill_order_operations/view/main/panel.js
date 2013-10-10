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
        me = this;
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
                                                value       : '{s namespace=paymill name=backend_description_capture}Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.{/s}'
                                            }
                                        ]
                                    },{
                                        xtype: 'fieldcontainer',
                                        defaultType: 'displayfield',
                                        width:'50%',
                                        items: [

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
                                        width:'50%',
                                        items: [
                                            Ext.create('Ext.Button', {
                                                text: 'Capture',
                                                scale: 'medium',
                                                margin: '0 0 0 10',
                                                disabled: !(me.canCapture()),
                                                handler: function() {
                                                    me.capture();
                                                }
                                            })

                                        ]
                                    },{
                                        xtype: 'fieldcontainer',
                                        defaultType: 'displayfield',
                                        width:'50%',
                                        items: [

                                        ]
                                    }
                                ]
                            })
                        ]
                    }
                ]
            })

        ];
        this.callParent(arguments);
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
            url: '{url controller=PaymillOrderOperations action=executeCapture}',
            method:'POST',
            async:false,
            params: {
                orderId:id
            },
            success: function(response){
                var message = "{s namespace=paymill name=backend_feedback_title_capture_error}Fehler...{/s}";
                var decodedResponse = Ext.decode(response.responseText);
                var success = decodedResponse.success;
                var messageText = decodedResponse.messageText;
                if(success){
                    message = "{s namespace=paymill name=backend_feedback_title_capture_success}Erfolg{/s}";
                }
                alert(message + messageText);
            }
        });
    }
});
//{/block}
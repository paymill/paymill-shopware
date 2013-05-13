/**
 * window
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
Ext.define('Shopware.apps.PaymillLogging.view.main.Window', {
    extend: 'Enlight.app.Window',
    title: 'Paymill Logging',
    alias: 'widget.paymill_logging-main-window',
    border: false,
    autoShow: true,
    resizable: true,
    layout: {
        type:'fit'
    },
    height: 520,
    width: 800,

    initComponent: function() {
        var me = this;
        me.store = me.listStore;
        me.items = [
        me.createMainGrid(me)
        ];
        me.callParent(arguments);
    },
    createMainGrid: function(me){
        return Ext.create('Ext.grid.Panel', {
            store: me.store,
            forceFit:true,
            border: false,
            height: '100%',
            width:'100%',
            columns: [
            {
                text: 'Datum',
                dataIndex: 'entryDate',
                width:50
            },
            {
                text: 'Version',
                dataIndex: 'version',
                width:50
            },
            {
                text: 'H&auml;ndlerinformation',
                dataIndex: 'merchantInfo',
                width:350
            },{
                        xtype: 'actioncolumn',
                        header: 'actions',
                        width: 60,
                        items: [
                            {
                                iconCls: 'sprite-question-button',
                                action: 'Details',
                                scope: me,
                                handler: function(grid, rowIndex, colIndex, item, eOpts, record){
                                    me.openDetailPopup(record);
                                }
                            }
                        ]
                    }
            ],
            dockedItems: [{
                xtype: 'pagingtoolbar',
                store: me.store,
                dock: 'bottom',
                displayInfo: true
            }]
            });
    },
    openDetailPopup: function(record){
        Ext.create('Ext.window.Window', {
            title: "Details",
            layout: 'fit',
            draggable: true,
            resizable: false,
            width: '60%',
            items: [
                Ext.create('Ext.panel.Panel',{
                width:'100%',
                height:'100%',
                layout: 'fit',
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
                                items: [
                                {
                                    fieldLabel  : 'Date',
                                    value   : record.get('entryDate'),
                                    fieldStyle:{ margin:'0 0 0 50px' }
                                }
                                ]
                            },{
                                xtype: 'fieldcontainer',
                                defaultType: 'displayfield',
                                margin: '0 0 0 50px',
                                items: [
                                {
                                    fieldLabel  : 'Version',
                                    value       : record.get('version'),
                                    fieldStyle:{ margin:'0 0 0 50px' }
                                }]}]}),
                
                Ext.create('Ext.panel.Panel',{
                            layout: 'column',
                            items:[
                            {
                                xtype:'panel',
                                border: false,
                                layout:'fit',
                                title:'Developer Information',
                                html: record.get('devInfo'),
                                autoScroll:true,
                                width:'50%',
                                height: '100%'
                            },{
                                xtype:'panel',
                                border: false,
                                layout:'fit',
                                title:'Additional Developer Information',
                                html: record.get('devInfoAdditional'),
                                autoScroll:true,
                                width:'50%',
                                height: '100%'
                            }
                            ]
                        })
                            ]
                }
                        
                ]})
            ]
        }).show();
    }
});
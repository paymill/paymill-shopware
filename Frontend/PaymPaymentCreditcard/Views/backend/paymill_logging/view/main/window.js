/**
 * window
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2015 PAYMILL GmbH (https://www.paymill.com)
 */

    //{namespace name=backend/order/main}
Ext.require([
    'Ext.grid.*', 'Ext.data.*', 'Ext.panel.*'
]);
Ext.define('Shopware.apps.PaymillLogging.view.main.Window', {
    extend:    'Enlight.app.Window',
    title:     '{s namespace=backend/paym_payment_creditcard/log name=window_title}Paymill Logging{/s}',
    alias:     'widget.paymill_logging-main-window',
    border:    false,
    autoShow:  true,
    resizable: true,
    layout:    {
        type: 'fit'
    },
    height:    800,
    width:     800,

    initComponent:   function ()
    {
        var me = this;
        me.store = me.listStore;
        me.items = [
            me.createMainGrid(me)
        ];
        me.callParent(arguments);
    },
    createMainGrid:  function (me)
    {
        return Ext.create('Ext.grid.Panel', {
            store:       me.store,
            id:          'mainGrid',
            forceFit:    true,
            border:      false,
            height:      '100%',
            width:       '100%',
            columns:     [
                {
                    text:      '{s namespace=backend/paym_payment_creditcard/log name=column_header_date}Date{/s}',
                    dataIndex: 'entryDate',
                    width:     75
                },
                {
                    text:      '{s namespace=backend/paym_payment_creditcard/log name=column_header_version}Version{/s}',
                    dataIndex: 'version',
                    width:     40
                },
                {
                    text:      '{s namespace=backend/paym_payment_creditcard/log name=column_header_merchantinfo}Merchant Information{/s}',
                    dataIndex: 'merchantInfo',
                    width:     350
                },
                {
                    xtype:  'actioncolumn',
                    header: '{s namespace=backend/paym_payment_creditcard/log name=column_header_actions}Actions{/s}',
                    width:  35,
                    items:  [
                        {
                            iconCls: 'sprite-sticky-notes-pin',
                            action:  'Details',
                            scope:   me,
                            handler: function (grid, rowIndex, colIndex, item, eOpts, record)
                            {
                                me.openDetailPopup(record);
                            }
                        }
                    ]
                }
            ],
            dockedItems: [
                {
                    xtype:       'pagingtoolbar',
                    store:       me.store,
                    dock:        'bottom',
                    displayInfo: true
                },
                {
                    xtype: 'toolbar',
                    ui: 'shopware-ui',
                    id: 'topToolbar',
                    dock: 'top',
                    border: false,
                    items: me.getTopBar()
                }
            ]
        });
    },
    openDetailPopup: function (record)
    {
        Ext.create('Ext.window.Window', {
            title:     "Details",
            layout:    'fit',
            draggable: true,
            resizable: false,
            width:     '60%',
            items:     [
                Ext.create('Ext.panel.Panel', {
                    width:     '100%',
                    height:    '100%',
                    layout:    'fit',
                    bodyStyle: {
                        background: '#F0F2F4'
                    },
                    items:     [
                        {
                            xtype:       'fieldset',
                            collapsible: false,
                            items:       [
                                Ext.create('Ext.panel.Panel', {
                                    width:  '100%',
                                    layout: 'column',
                                    items:  [
                                        {
                                            xtype:       'fieldcontainer',
                                            defaultType: 'displayfield',
                                            items:       [
                                                {
                                                    fieldLabel: '{s namespace=backend/paym_payment_creditcard/log name=column_header_date}Date{/s}',
                                                    value:      record.get('entryDate'),
                                                    fieldStyle: { margin: '0 0 0 50px' }
                                                }
                                            ]
                                        },
                                        {
                                            xtype:       'fieldcontainer',
                                            defaultType: 'displayfield',
                                            margin:      '0 0 0 50px',
                                            items:       [
                                                {
                                                    fieldLabel: '{s namespace=backend/paym_payment_creditcard/log name=column_header_version}Version{/s}',
                                                    value:      record.get('version'),
                                                    fieldStyle: { margin: '0 0 0 50px' }
                                                }
                                            ]}
                                    ]}),

                                Ext.create('Ext.panel.Panel', {
                                    layout: 'column',
                                    items:  [
                                        {
                                            xtype:      'panel',
                                            border:     false,
                                            layout:     'fit',
                                            title:      '{s namespace=backend/paym_payment_creditcard/log name=column_header_developer}Developer Information{/s}',
                                            html:       record.get('devInfo'),
                                            autoScroll: true,
                                            width:      '100%',
                                            height:     '100%'
                                        }
                                    ]
                                })
                            ]
                        }

                    ]})
            ]
        }).show();
    },
    getTopBar:function () {
        var me = this,
        items = [];
        items.push(
            {
                xtype: 'textfield',
                name: 'searchfield',
                id: 'searchfield',
                cls:'searchfield',
                emptyText:'{s namespace=backend/paym_payment_creditcard/log name=toolbar_button_search}Search...{/s}',
                checkChangeBuffer: 1000,
                enableKeyEvents:true,
                width: 400
            },
            {
                xtype: 'checkbox',
                name: 'connectedSearch',
                id: 'connectedSearch',
                cls:'connectedSearch',
                fieldLabel:'{s namespace=backend/paym_payment_creditcard/log name=toolbar_checkbox_connectedsearch}Connected Search{/s}'
            }
        );
        return items;
    }
});
/**
 * main
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2015 PAYMILL GmbH (https://www.paymill.com)
 */

Ext.define('Shopware.apps.PaymillLogging.controller.Main', {
    extend:     'Ext.app.Controller',
    mainWindow: null,
    connectedSearch: false,
    init:       function ()
    {
        var me = this;
        me.mainWindow = null;
        me.mainWindow = me.getView('main.Window').create({
            listStore: me.getStore('List').load()
        });

        me.control({
            'paymill_logging-main-window [name=searchfield]': {
                change: me.onSearchForm
            },
            'paymill_logging-main-window [name=connectedSearch]': {
                change: me.onConnectedSearch
            }
        });

        me.callParent(arguments);
    },

    /**
     * Callback function triggered when the user enters something into the search field
     *
     * @param field
     * @param value
     */
    onSearchForm: function (field, value)
    {
        var me = this;
        var store = me.getStore('List');
        if (value.length === 0) {
            store.load();
        } else {
            store.load({
                filters : [
                   {
                       property: 'searchTerm',
                       value: value
                   },
                   {
                       property: 'connectedSearch',
                       value: me.connectedSearch
                   }
                ]
            });
        }
    },

    /**
     * Callback function triggered when the connected search state is beeing changed
     *
     * @param field
     * @param value
     */
    onConnectedSearch: function (field, value)
    {
        var me = this;
        me.connectedSearch = value;
    }
});
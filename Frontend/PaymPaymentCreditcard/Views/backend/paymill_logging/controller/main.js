/**
 * main
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */

Ext.define('Shopware.apps.PaymillLogging.controller.Main', {
    extend:     'Ext.app.Controller',
    mainWindow: null,
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
        console.log("Suche nach: " + value);
        if (value.length === 0) {
            store.load();
        } else {
            store.load({
                filters : [{
                               property: 'searchTerm',
                               value: value
                           }]
            });
        }
    }
});
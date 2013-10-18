/**
 * app
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
Ext.define('Shopware.apps.PaymillLogging', {
    extend:      'Enlight.app.SubApplication',
    name:        'Shopware.apps.PaymillLogging',
    bulkLoad:    true,
    loadPath:    '{url action=load}',
    controllers: ['Main'],
    models:      ['Main'],
    views:       ['main.Window'],
    store:       ['List'],
    launch:      function ()
    {
        var me = this;
        me.windowTitle = '{s namespace=Paymill name=paymill_log}Paymill Log {/s}';
        var mainController = me.getController('Main');
        return mainController.mainWindow;
    }
});
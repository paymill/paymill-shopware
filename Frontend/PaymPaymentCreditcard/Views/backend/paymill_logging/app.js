/**
 * app
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2015 PAYMILL GmbH (https://www.paymill.com)
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
        me.windowTitle = '{s namespace=backend/paym_payment_creditcard/log name=log_title}Paymill Log {/s}';
        var mainController = me.getController('Main');
        return mainController.mainWindow;
    }
});
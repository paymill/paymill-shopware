/**
 * list
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2015 PAYMILL GmbH (https://www.paymill.com)
 */

Ext.define('Shopware.apps.PaymillLogging.store.List', {
    extend:   'Ext.data.Store',
    model:    'Shopware.apps.PaymillLogging.model.Main',
    autoLoad: true,
    pageSize: 32,
    proxy: {
        type: 'ajax',
        url : '{url action=loadStore}',
        reader: {
            type: 'json',
            root: 'data'
        }
    },
    remoteSort: true,
    remoteFilter: true

});
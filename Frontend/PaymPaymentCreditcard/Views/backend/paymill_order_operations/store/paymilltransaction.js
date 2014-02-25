/**
 * paymilltransaction - store
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */

Ext.define('Shopware.apps.PaymillOrderOperations.store.Paymilltransaction', {
    extend:     'Ext.data.Store',
    model:      'Shopware.apps.PaymillOrderOperations.model.Paymilltransaction',
    autoLoad:   false,
    remoteSort: false,
    pageSize:   10
});
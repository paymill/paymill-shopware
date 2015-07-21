/**
 * paymilltransaction - store
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2015 PAYMILL GmbH (https://www.paymill.com)
 */

Ext.define('Shopware.apps.PaymillOrderOperations.store.Paymilltransaction', {
    extend:     'Ext.data.Store',
    model:      'Shopware.apps.PaymillOrderOperations.model.Paymilltransaction',
    autoLoad:   false,
    remoteSort: false,
    pageSize:   10
});
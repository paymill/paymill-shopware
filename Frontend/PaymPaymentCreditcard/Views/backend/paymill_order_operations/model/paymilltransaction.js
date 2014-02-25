/**
 * paymilltransaction - model
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
Ext.define('Shopware.apps.PaymillOrderOperations.model.Paymilltransaction', {
    extend: 'Ext.data.Model',
    fields: [
        { name: 'entryDate', type: 'string'},
        { name: 'description', type: 'string'},
        { name: 'amount', type: 'string'}
    ],
    proxy:  {
        type:   'ajax',
        api:    {
            read: '{url controller=PaymillOrderOperations action=loadStore}'
        },
        reader: {
            type: 'json',
            root: 'data'
        }
    }
});
/**
 * main
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
Ext.define('Shopware.apps.PaymillLogging.model.Main', {
    extend: 'Ext.data.Model',
    fields: [ 'id', 'entryDate', 'version', 'merchantInfo', 'devInfo', 'devInfoAdditional'],
    proxy:  {
        type:   'ajax',
        api:    {
            read: '{url action=loadStore}'
        },
        reader: {
            type:          'json',
            root:          'data',
            totalProperty: 'total'
        }
    }
});
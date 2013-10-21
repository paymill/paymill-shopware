/**
 * main
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
Ext.define('Shopware.apps.PaymillLogging.model.Main', {
    extend: 'Ext.data.Model',
    fields: [
        { name: 'id', type: 'int'},
        { name: 'processId', type: 'string'},
        { name: 'entryDate', type: 'date'},
        { name: 'version', type: 'string'},
        { name: 'merchantInfo', type: 'string'},
        { name: 'devInfo', type: 'string'}
    ]
});
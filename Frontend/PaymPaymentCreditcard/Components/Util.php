<?php

/**
 * Util
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2011 PayIntelligent GmbH (http://payintelligent.de)
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_Util
{

    /**
     * Returns an orderId by ordernumber
     *
     * @param string $OrderNumber
     * @return string
     */
    public function getOrderIdByNumber($OrderNumber)
    {
        $sql = Shopware()->Db()->select()
            ->from('s_order', 'id')
            ->where('ordernumber=?', $OrderNumber);
        return Shopware()->Db()->fetchOne($sql);
    }

}

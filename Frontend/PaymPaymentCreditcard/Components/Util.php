<?php

/**
 * Util
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2015 PAYMILL GmbH (https://www.paymill.com)
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

    /**
     * Returns the date for an Sepa order
     *
     * @param string $orderNumber
     * @return string
     */
    public function getSepaDate($orderNumber){
	$orderModel = Shopware()->Models()->find('Shopware\Models\Order\Order', $this->getOrderIdByNumber($orderNumber));
	$attribute = $orderModel->getAttribute();
	if(!is_null($attribute) && is_object($attribute) && method_exists($attribute, 'getPaymillSepaDate')){
	    $paymillSepaDate = $attribute->getPaymillSepaDate();
	}else{
	    $select = Shopware()->Db()->select()->from('s_order_attributes','paymill_sepa_date')
		    ->where('`s_order_attributes`.`orderID` = ?', $this->getOrderIdByNumber($orderNumber));
	    $paymillSepaDate = Shopware()->Db()->fetchOne($select);
	}
	return $paymillSepaDate;
    }
}

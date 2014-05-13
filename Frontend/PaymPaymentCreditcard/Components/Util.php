<?php

/**
 * Util
 *
 * @category   PayIntelligent
 * @package    Expression package is undefined on line 6, column 18 in Templates/Scripting/PHPClass.php.
 * @copyright  Copyright (c) 2011 PayIntelligent GmbH (http://payintelligent.de)
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_Util
{

    public function getOrderIdByNumber($OrderNumber)
    {
        $sql = Shopware()->Db()->select()
            ->from('s_order', 'id')
            ->where('ordernumber=?', $OrderNumber);
        return Shopware()->Db()->fetchOne($sql);
    }

}

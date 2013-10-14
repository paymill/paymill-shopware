<?php

require_once dirname(__FILE__) . '/../lib/Services/Paymill/PaymentProcessor.php';
require_once dirname(__FILE__) . '/../lib/Services/Paymill/LoggingInterface.php';

/**
 * This class stub allows the shop compliant usage of the paymill libs PaymentProcessor class
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     Paymill
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_PaymentProcessor extends Services_Paymill_PaymentProcessor implements Services_Paymill_LoggingInterface
{
    private $_loggingManager;

    public function __construct($params = null)
    {
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";
        $source = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->getVersion();
        $source .= "_shopware";
        $source .= "_" . Shopware()->Config()->get('version');
        $this->setSource($source);
        $this->_loggingManager = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManagerShopware();
        parent::__construct($privateKey, $apiUrl, null, $params, $this);
    }

    /**
     * Uses the LoggingManager to insert a new entry into the Log
     *
     * @param String $merchantInfo      Information of use to the merchant
     * @param String $devInfo           Information of use to developers
     */
    public function log($merchantInfo, $devInfo)
    {
        $this->_loggingManager->write($merchantInfo, $devInfo);
    }
}

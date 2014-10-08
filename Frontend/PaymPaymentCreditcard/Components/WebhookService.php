<?php
/**
 * webhookService
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2011 PayIntelligent GmbH (http://payintelligent.de)
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_WebhookService
{
    /**
     * @var type 
     */
    private $config;
    
    /**
     * @var Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManager 
     */
    private $logging;
    
    /**
     * Creates instance for this class
     */
    public function __construct() {
	$this->config = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $this->logging = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManager();
    }

    /**
     * Validates the requested refund
     * 
     * @param array $notification
     * @return boolean
     */
    private function isNotificationValid($notification)
    {

        if ($this->isStructureValid($notification) && $notification['event']['event_type'] == 'refund.succeeded') {
            $id = $notification['event']['event_resource']['transaction']['id'];
            $privateKey = trim($this->config->get('privateKey'));
            $transactionObject = new Services_Paymill_Transactions($privateKey, 'https://api.paymill.com/v2/');
            $result = $transactionObject->getOne($id);
            $this->logging->log('validate transaction-id for refund', var_export($result['id'] === $id,true));
	    return $result['id'] === $id;
        }
        return false;
    }

    /**
     * Validates the structure of the request
     * @param array $notification
     * @return boolean
     */
    private function isStructureValid($notification)
    {
        $isValid = !empty($notification) && isset($notification['event']['event_type']) && isset($notification['event']['event_resource']['transaction']['id']);
	$this->logging->log('validate structure for request', var_export($isValid,true));
	return $isValid;
    }

    /**
     * Returns id for the refunded transaction
     * @param array $notification
     * @return string
     */
    public function getTransactionId($notification)
    {

        if ($this->isNotificationValid($notification)) {
            $id = $notification['event']['event_resource']['transaction']['id'];
        }
        return $id;
    }
}

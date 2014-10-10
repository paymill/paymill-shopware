<?php
require_once dirname(__FILE__) . '/../lib/Services/Paymill/Transactions.php';
require_once dirname(__FILE__) . '/../lib/Services/Paymill/Webhooks.php';

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
     * Registers the webhook for this module
     * @param string $privateKey
     * @return null
     */
    public function registerWebhookEndpoint($privateKey){
	if($this->isWebhookAvailable()){
	    return;
	}
	
	$url = Shopware()->Front()->Router()->assemble(
	    array(
		'module' => 'frontend',
		'action' => 'webhook', 
		'controller' => 'payment_paymill',
		'forceSecure' => true
	    )
	);
	$webhook = new Services_Paymill_Webhooks($privateKey, 'https://api.paymill.com/v2/');
	$result = $webhook->create(array(
	    'url' => $url,
            'event_types' => array('refund.succeeded')
	));
	if(isset($result['id']) && isset($result['livemode']) && $result['livemode']){
	    Shopware()->Db()->query('REPLACE INTO `paymill_webhook` VALUES(?)',$result['id']);
	}
	
    }
    
    /**
     * Installs the database to store the webhook id
     */
    public static function install(){
	$sql = "CREATE TABLE IF NOT EXISTS `paymill_webhook` (" .
                   "`webhook_id` varchar(255) NOT NULL," .
                   "PRIMARY KEY (`webhook_id`) )";
	Shopware()->Db()->query($sql);
    }
    
    /**
     * Checks if the webhook is available
     * @return boolean
     */
    public function isWebhookAvailable(){
	$sql = "SELECT * FROM `paymill_webhook`";
	return (bool)Shopware()->Db()->fetchOne($sql);
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

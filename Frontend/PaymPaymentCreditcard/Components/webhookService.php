<?php
/**
 * webhookService
 *
 * @category   PayIntelligent
 * @package    Expression package is undefined on line 6, column 18 in Templates/Scripting/PHPClass.php.
 * @copyright  Copyright (c) 2011 PayIntelligent GmbH (http://payintelligent.de)
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_webhookService
{
    /**
     * @var Shopware_Controllers_Frontend_PaymentPaymill
     */
    private $context;

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function validateNotification($notification)
    {

        if ($this->isStructureValid($notification) && $notification['event']['event_type'] == 'refunded.succeeded') {
            $id = $notification['event']['event_resource']['transaction']['id'];
            $privateKey = trim($this->context->config->get('privateKey'));
            $transactionObject = new Services_Paymill_Transactions($privateKey, 'https://api.paymill.com/v2/');
            $result = $transactionObject->getOne($id);
            return $result['id'] === $id;
        }
        return false;
    }

    private function isStructureValid($notification)
    {
        return !empty($notification) && isset($notification['event']['event_type']) && isset($notification['event']['event_resource']['transaction']['id']);
    }

    public function getTransactionId($notification)
    {

        if ($this->validateNotification($notification)) {
            $id = $notification['event']['event_resource']['transaction']['id'];
        }
        return $id;
    }
}

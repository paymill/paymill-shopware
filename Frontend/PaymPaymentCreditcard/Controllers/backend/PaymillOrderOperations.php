<?php
/**
 * Paymill Order Operations
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     PayIntelligent
 */
class Shopware_Controllers_Backend_PaymillOrderOperations extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Action Listener to determine if the Paymill Order Operations Tab will be displayed
     */
    public function displayTabAction()
    {
        $orderId = $this->Request()->getParam("orderId");
        $result = $this->_isPaymillPayment($orderId);
        $this->View()->assign(array('success' => $result));
    }

    /**
     * Returns if the payment mean is a paymill payment mean
     * @param $orderId
     *
     * @return bool
     */
    private function _isPaymillPayment($orderId){
        $sql = "SELECT count(name) FROM s_core_paymentmeans payment, s_order o
                WHERE o.paymentID = payment.id
                AND (payment.name = 'paymilldebit' OR payment.name = 'paymillcc')
                AND o.id = ?";
        $isPaymillPayment = Shopware()->Db()->fetchOne($sql, array($orderId));

        return $isPaymillPayment == '1';
    }

    /**
     * Action Listener to determine if an order is applicable for capture
     */
    public function canCaptureAction()
    {
        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $orderId = $this->Request()->getParam("orderId");
        $orderNumber = $modelHelper->getOrderNumberById($orderId);
        $isPreAuth = $modelHelper->getPaymillPreAuthorization($orderNumber) !== "";
        $notCaptured = $modelHelper->getPaymillTransactionId($orderNumber) === "";
        $success = $isPreAuth && $notCaptured;
        $this->View()->assign(array('success' => $success));
    }

    /**
     * Action Listener to execute the capture for applicable transactions
     *
     * @todo Add translations and exception handling for different cases
     * @todo Add logging
     */
    public function captureAction()
    {
        $result = false;
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Preauthorizations.php';
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();

        //Gather Data
        $orderId = $this->Request()->getParam("orderId");
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";

        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $orderNumber = $modelHelper->getOrderNumberById($orderId);
        $preAuthId = $modelHelper->getPaymillPreAuthorization($orderNumber);

        $preAuthObject = new Services_Paymill_Preauthorizations($privateKey, $apiUrl);
        $preAuthObject = $preAuthObject->getOne($preAuthId);

        $description = $preAuthObject['client']['email'] . " " . Shopware()->Config()->get('shopname');
        $amount = $preAuthObject['amount'];
        $currency = $preAuthObject['currency'];

        //Create Transaction
        $parameter = array("amount" => $amount, "currency" => $currency, "description" => $description);

        $paymentProcessor = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_PaymentProcessor($parameter);
        $paymentProcessor->setPreauthId($preAuthId);

        try {
            $result = $paymentProcessor->capture();
            if ($result) {
                $messageText = "Capture has been successful.";
                $modelHelper->setPaymillTransactionId($orderNumber, $paymentProcessor->getTransactionId());
            } else {
                $messageText = "Capture failed.";
            }
        } catch (Exception $exception) {
            $messageText = $exception->getMessage();
        }

        $this->View()->assign(array('success' => $result, 'messageText' => $messageText));
    }

    /**
     * Action Listener to determine if an order is applicable for refund
     */
    public function canRefundAction()
    {
        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $orderId = $this->Request()->getParam("orderId");
        $orderNumber = $modelHelper->getOrderNumberById($orderId);
        $isTransaction = $modelHelper->getPaymillTransactionId($orderNumber) !== "";
        $notCanceled = !($modelHelper->getPaymillCancelled($orderNumber));
        $success = $isTransaction && $notCanceled;

        $this->View()->assign(array('success' => $success));
    }

    /**
     * Action Listener to execute the capture for applicable transactions
     *
     * @todo Add translations and exception handling for different cases
     * @todo Add logging
     */
    public function refundAction()
    {
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Transactions.php';
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Refunds.php';
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();

        //Gather Data
        $orderId = $this->Request()->getParam("orderId");
        $orderNumber = $modelHelper->getOrderNumberById($orderId);
        $transactionId = $modelHelper->getPaymillTransactionId($orderNumber);
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";

        $transactionObject = new Services_Paymill_Transactions($privateKey, $apiUrl);
        $transactionObject = $transactionObject->getOne($transactionId);

        $description = $transactionObject['client']['email'] . " " . Shopware()->Config()->get('shopname');
        $amount = $transactionObject['amount'];

        //Create Transaction
        $parameter = array('transactionId'   => $transactionId,
                           'params'          => array(
                               'amount'      => $amount,
                               'description' => $description
                               )
            );

        $refundObject = new Services_Paymill_Refunds($privateKey, $apiUrl);
        $refund = $refundObject->create($parameter);

        //Validate Result
        $messageText = "";
        $result = true;

        if($result){
            $modelHelper->setPaymillCancelled($orderNumber, true);
        }

        $this->View()->assign(array('success' => $result, 'messageText' => $messageText));
    }
}
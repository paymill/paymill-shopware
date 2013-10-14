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
        $model = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneById($orderId);
        $canCapture = false;
        $canRefund = false;
        $orderComplete = false;

        if ($model->getAttribute()->getPaymillCancelled() == 1) {
            $orderComplete = true;
        }

        if ($model->getAttribute()->getPaymillTransaction() !== null && !$orderComplete) {
        $canRefund = true;
        }

        if ($model->getAttribute()->getPaymillPreAuthorization() !== null && !$canRefund) {
            $canCapture = true;
        }

        $result = ($canCapture||$canRefund);

        $this->View()->assign(array('success' => $result));
    }

    /**
     * Action Listener to determine if an order is applicable for capture
     */
    public function canCaptureAction()
    {
        $success = false;
        $orderId = $this->Request()->getParam("orderId");
        $model = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneById($orderId);
        if ($model->getAttribute()->getPaymillPreAuthorization() !== null) {
            if ($model->getAttribute()->getPaymillTransaction() === null) {
                    $success = true;
            }
        }
        $this->View()->assign(array('success' => $success));
    }

    /**
     * Action Listener to execute the capture for applicable transactions
     *
     * @todo Add translations and exception handling for different cases
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

        $model = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneById($orderId);
        $preAuthId = $model->getAttribute()->getPaymillPreAuthorization();

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
                $model->getAttribute()->setPaymillTransaction($paymentProcessor->getTransactionId());
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
        $success = false;
        $orderId = $this->Request()->getParam("orderId");
        $model = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneById($orderId);
        if ($model->getAttribute()->getPaymillTransaction() !== null) {
            $success = true;
        }

        $this->View()->assign(array('success' => $success));
    }

    /**
     * Action Listener to execute the capture for applicable transactions
     *
     * @todo Add translations and exception handling for different cases
     */
    public function refundAction()
    {
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Transaction.php';
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();

        //Gather Data
        $orderId = $this->Request()->getParam("orderId");
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";

        $model = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneById($orderId);
        $transactionId = $model->getAttribute()->getPaymillTransaction();

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
        $result = false;
        $messageText = "";

        $this->View()->assign(array('success' => $result, 'messageText' => $messageText));
    }
}
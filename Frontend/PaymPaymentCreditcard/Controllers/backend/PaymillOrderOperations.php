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
     * Returns the store of the transaction overview
     */
    public function loadStoreAction()
    {
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $apiKey = trim($swConfig->get("privateKey"));
        $apiEndpoint = "https://api.paymill.com/v2/";
        $orderId = $this->Request()->getParam("orderId");
        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();

        $orderNumber = $modelHelper->getOrderNumberById($orderId);

        $success = true;
        $result = array();

        $preAuthId = $modelHelper->getPaymillPreAuthorization($orderNumber);
        $transactionId = $modelHelper->getPaymillTransactionId($orderNumber);
        $refundId = $modelHelper->getPaymillRefund($orderNumber);
        $cancelled = $modelHelper->getPaymillCancelled($orderNumber);

        if ($preAuthId !== "" && $preAuthId !== null) { //List PreAuth
            require_once dirname(dirname(dirname(__FILE__))) . '/lib/Services/Paymill/Preauthorizations.php';
            $preAuthObj = new Services_Paymill_Preauthorizations($apiKey, $apiEndpoint);
            $response = $preAuthObj->getOne($preAuthId);

            $result[] = array(
                'entryDate'   => date('d.M.Y H:i:s', $response['created_at']),
                'description' => 'PreAuthorization ' . $response['id'],
                'amount'      => ($response['amount'] / 100) . ' ' . $response['currency']
            );
        }

        if ($transactionId !== "" && $transactionId !== null) { //List Transaction
            require_once dirname(dirname(dirname(__FILE__))) . '/lib/Services/Paymill/Transactions.php';
            $transactionObj = new Services_Paymill_Transactions($apiKey, $apiEndpoint);
            $response = $transactionObj->getOne($transactionId);
            $currency = $response['currency'];
            $result[] = array(
                'entryDate'   => date('d.M.Y H:i:s', $response['created_at']),
                'description' => 'Transaction ' . $response['id'],
                'amount'      => ($response['origin_amount'] / 100) . ' ' . $currency
            );
        }

        if ($refundId !== "" && $refundId !== null) { //List Refund
            require_once dirname(dirname(dirname(__FILE__))) . '/lib/Services/Paymill/Refunds.php';
            $refundObj = new Services_Paymill_Refunds($apiKey, $apiEndpoint);
            $response = $refundObj->getOne($refundId);
            $result[] = array(
                'entryDate'   => date('d.M.Y H:i:s', $response['updated_at']),
                'description' => 'Refund ' . $response['id'],
                'amount'      => ($response['amount'] / 100) . ' ' . $currency
            );
        }

        $this->View()->assign(array('success' => $success, 'data' => $result, 'debug' => var_export($refundId, true)));
    }

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
     *
     * @param $orderId
     *
     * @return bool
     */
    private function _isPaymillPayment($orderId)
    {
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
     */
    public function captureAction()
    {
        $result = false;
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Preauthorizations.php';
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $preAuthObject = new Services_Paymill_Preauthorizations(trim($swConfig->get("privateKey")), 'https://api.paymill.com/v2/');

        //Gather Data
        $orderNumber = $modelHelper->getOrderNumberById($this->Request()->getParam("orderId"));
        $preAuthId = $modelHelper->getPaymillPreAuthorization($orderNumber);
        $preAuthObject = $preAuthObject->getOne($preAuthId);

        //Create Transaction
        $parameter = array(
            'amount'      => $preAuthObject['amount'],
            'currency'    => $preAuthObject['currency'],
            "description" => $preAuthObject['client']['email'] . ' ' . Shopware()->Config()->get('shopname')
        );

        $paymentProcessor = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_PaymentProcessor($parameter, $orderNumber);
        $paymentProcessor->setPreauthId($preAuthId);

        try {
            $result = $paymentProcessor->capture();
            $modelHelper->setPaymillTransactionId($orderNumber, $paymentProcessor->getTransactionId());
            $this->View()->assign(array('success' => $result));
            if ($result) {
                $this->_updatePaymentStatus(12, $this->Request()->getParam("orderId"));
            }
        } catch (Exception $exception) {
            $this->View()->assign(array('success' => $result, 'code' => $exception->getMessage()));
        }
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
        $refundId = $modelHelper->getPaymillRefund($orderNumber);
        $refundAvailableFlag = !($modelHelper->getPaymillCancelled($orderNumber));

        $notCancelled = ($refundId === "") && $refundAvailableFlag;
        $success = $isTransaction && $notCancelled;

        $this->View()->assign(array('success' => $success));
    }

    /**
     * Action Listener to execute the capture for applicable transactions
     *
     */
    public function refundAction()
    {
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Transactions.php';
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Refunds.php';
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $refund = new Services_Paymill_Refunds(trim($swConfig->get("privateKey")), 'https://api.paymill.com/v2/');
        $transaction = new Services_Paymill_Transactions(trim($swConfig->get("privateKey")), 'https://api.paymill.com/v2/');
        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $orderNumber = $modelHelper->getOrderNumberById($this->Request()->getParam("orderId"));
        $transactionId = $modelHelper->getPaymillTransactionId($orderNumber);

        $transaction = $transaction->getOne($transactionId);

        //Create Transaction
        $parameter = array(
            'transactionId' => $transactionId,
            'params'        => array(
                'amount'      => $transaction['amount'],
                'description' => $transaction['client']['email'] . " " . Shopware()->Config()->get('shopname')
            )
        );

        $response = $refund->create($parameter);

        //Validate result and prepare feedback
        if ($result = $this->_validateRefundResponse($response)) {
            $modelHelper->setPaymillRefund($orderNumber, $response['id']);
            $this->_updatePaymentStatus(20, $this->Request()->getParam("orderId"));
        }

        $this->View()->assign(array('success' => $result, 'code' => $response['data']['response_code']));
    }

    /**
     * Validates the response array given by the create call of a refund object
     *
     * @param $refund
     *
     * @return bool
     */
    private function _validateRefundResponse($refund)
    {

        $loggingManager = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManager();

        if (!isset($refund['id']) && !isset($refund['data']['id'])) {
            $loggingManager->log("No Refund created.", var_export($refund, true));
            $hasId = false;
        } else {
            $loggingManager->log("Refund created.", isset($refund['id']) ? $refund['id'] : $refund['data']['id']);
            $hasId = true;
        }

        if (isset($refund['data']['response_code']) && $refund['data']['response_code'] !== 20000) {
            $loggingManager->log("An Error occurred during refund creation: " .
                                 $refund['data']['response_code'], var_export($refund, true));
            $responseCodeOK = false;
        } else {
            $responseCodeOK = true;
        }

        return $hasId && $responseCodeOK;
    }

    /**
     * Sets the status of the given payment
     *
     * @param integer $statusId
     * @param string  $orderId
     */
    private function _updatePaymentStatus($statusId, $orderId)
    {
        $order = Shopware()->Modules()->Order();
        $order->setPaymentStatus($orderId, $statusId, false);
    }
}
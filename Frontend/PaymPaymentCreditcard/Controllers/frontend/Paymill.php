<?php

/**
 * Shopware 4.0
 * Copyright © 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     Paymill
 */
class Shopware_Controllers_Frontend_PaymentPaymill extends Shopware_Controllers_Frontend_Payment
{

    private $util;
    private $config;


    public function init(){
        $this->util = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_Util();
        $this->config = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
    }



    /**
     * Frontend index action controller
     */
    public function indexAction()
    {
        //Initialise variables
        $user = Shopware()->Session()->sOrderVariables['sUserData'];
        $sState = array('reserviert' => 18, 'bezahlt' => 12);
        $processId = md5(time()." ". $user['billingaddress']['lastname'] . ', ' . $user['billingaddress']['firstname']);
        Shopware()->Session()->paymillProcessId = $processId;
        $loggingManager = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManager();
        $loggingManager->setProcessId($processId);

        // read transaction token from session
        $paymillToken = Shopware()->Session()->paymillTransactionToken;

        // check if token present
        if (empty($paymillToken)) {
            $loggingManager->log("No paymill token was provided. Redirect to payments page.", null);

            $url = $this->Front()->Router()->assemble(array('action'      => 'payment', 'sTarget' => 'checkout',
                                                            'sViewport'   => 'account', 'appendSession' => true,
                                                            'forceSecure' => true));

            $this->redirect($url . '&paymill_error=1');
        }

        $loggingManager->log("Start processing payment " . $paymillToken === "NoTokenRequired" ? "without" : "with" . " token.", $paymillToken);

        // process the payment
        $userId = $user['billingaddress']['userID'];
        $paymentShortcut = $this->getPaymentShortName();
        $params = array(
            'token'            => $paymillToken,
            'authorizedAmount' => (int)Shopware()->Session()->paymillTotalAmount,
            'amount'           => (int)(round($this->getAmount() * 100, 0)),
            'currency'         => $this->getCurrencyShortName(),
            'name'             => $user['billingaddress']['lastname'] . ', ' . $user['billingaddress']['firstname'],
            'email'            => $user['additional']['user']['email'],
            'description'      => $user['additional']['user']['email'] . " " . Shopware()->Config()->get('shopname'),
            'payment'          => $paymentShortcut
        );

        $paymentProcessor = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_PaymentProcessor($params, $processId);

        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $clientId = $modelHelper->getPaymillClientId($userId);
        $paymentId = $modelHelper->getPaymillPaymentId($this->getPaymentShortName(), $userId);

        if ($clientId != "") {

            $privateKey = trim($this->config->get("privateKey"));
            $apiUrl = "https://api.paymill.com/v2/";
            require_once dirname(dirname(dirname(__FILE__))) . '/lib/Services/Paymill/Clients.php';
            $client = new Services_Paymill_Clients($privateKey, $apiUrl);
            $client->update(array('id' => $clientId, 'email' => $user['additional']['user']['email']));
            $paymentProcessor->setClientId($clientId);
        }

        if ($paymentId != "") {
            if ($paymillToken === "NoTokenRequired") {
                $paymentProcessor->setPaymentId($paymentId);
            }
        }

        $preAuthOption = $this->config->get("paymillPreAuth");
        $isCCPayment = $paymentShortcut === 'paymillcc';
        $captureNow = !($preAuthOption && $isCCPayment);
        $result = $paymentProcessor->processPayment($captureNow);

        $loggingManager->log("Payment processing resulted in: " . ($result ? "Success" : "Failure"), print_r($result, true));

        // finish the order if payment was successfully processed
        if ($result !== true) {
            Shopware()->Session()->paymillTransactionToken = null;
            Shopware()->Session()->pigmbhErrorMessage = $this->_getSnippet('PAYMILL_'.$paymentProcessor->getErrorCode());
            return $this->forward('error');
        }

        //Save Client Id
        $clientId = $paymentProcessor->getClientId();
        $modelHelper->setPaymillClientId($userId, $clientId);

        //Save Fast Checkout Data
        $isFastCheckoutEnabled = $this->config->get("paymillFastCheckout");
        if ($isFastCheckoutEnabled) {
            $paymentId = $paymentProcessor->getPaymentId();
            $modelHelper->setPaymillPaymentId($this->getPaymentShortName(), $userId, $paymentId);
        }

        //Create the order
        $statusId = $captureNow ? $sState['bezahlt']: $sState['reserviert'];
        $transactionId = $captureNow ? $paymentProcessor->getTransactionId() : $paymentProcessor->getPreauthId();
        $orderNumber = $this->saveOrder($transactionId, md5($transactionId), $statusId);
        $loggingManager->log("Finish order.", "Ordernumber: " . $orderNumber, "using TransactionId: " . $transactionId);

        if ($captureNow) {
            $modelHelper->setPaymillTransactionId($orderNumber, $paymentProcessor->getTransactionId());
        } else {
            $modelHelper->setPaymillPreAuthorization($orderNumber, $paymentProcessor->getPreauthId());
        }

        $this->_updateTransaction($orderNumber, $paymentProcessor, $loggingManager);

        if(!$isCCPayment){
            $this->_setSEPADate();
        }

        // reset the session field
        Shopware()->Session()->paymillTransactionToken = null;

        return $this->forward('finish', 'checkout', null, array('sUniqueID' => md5($transactionId)));
    }

    /**
     * This method updates the description of the current transaction by adding the order number
     * @param $orderNumber
     * @param $paymentProcessor
     * @param $loggingManager
     */
    private function _updateTransaction($orderNumber, $paymentProcessor, $loggingManager)
    {
        //Update Transaction
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Transactions.php';
        $user = Shopware()->Session()->sOrderVariables['sUserData'];
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";
        $transaction = new Services_Paymill_Transactions($privateKey, $apiUrl);
        $description = $orderNumber . " " . $user['additional']['user']['email'] . " " . Shopware()->Config()
                                                                                         ->get('shopname');
        $updateResponse = $transaction->update(array('id'          => $paymentProcessor->getTransactionId(),
                                                     'description' => $description));

        if ($updateResponse['response_code'] === 20000) {
            $loggingManager->log("Successfully updated the description of " . $paymentProcessor->getTransactionId(), $description);
        } else {
            $loggingManager->log("There was an error updating the description of " . $paymentProcessor->getTransactionId(), $description);
        }
    }

    /**
     * Redirects to the confirmation page and sets an error message.
     */
    public function errorAction()
    {
        $errorMessage = null;
        if (isset(Shopware()->Session()->pigmbhErrorMessage)) {
            $errorMessage = 1;
        }

        $this->redirect(array("controller"   => "checkout", "action" => "confirm", "forceSecure" => 1,
                              "errorMessage" => $errorMessage));
    }

    /**
     * Returns the error message in the correct language as a string
     * @param string $snippetName
     * @return string $error
     */
    private function _getSnippet($snippetName)
    {
        $default = "An error occurred while processing your payment.";
        $shopId = Shopware()->Shop()->getId();
        $sql = "SELECT value FROM s_core_snippets WHERE shopID = ? AND `name` = ?";
        $result = Shopware()->Db()->fetchOne( $sql, array( $shopId, $snippetName ) );
        return $result? $result : $default;
    }

    private function _setSEPADate(){
        $timeStamp = strtotime("+ " . $this->config->get('paymillSepaDate') . " DAYS");
        $orderNumber = $this->getOrderNumber();
        $orderModel = Shopware()->Models()->find('Shopware\Models\Order\Order', $this->util->getOrderIdByNumber($orderNumber));
        $orderModelAttribute = $orderModel->getAttribute();
        $orderModelAttribute->setPaymillSepaDate($timeStamp);
        $orderModelAttribute->setAttribute1("TEST");
        Shopware()->Models()->persist($orderModelAttribute);
        Shopware()->Models()->flush($orderModelAttribute);
    }
}
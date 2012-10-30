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

class Shopware_Controllers_Frontend_PaymentPaymillcc extends Shopware_Controllers_Frontend_Payment
{
    /**
     * Frontend index action controller
     * @return void
     */
    public function indexAction() {
        
        // read transaction token from session
        $paymillToken = Shopware()->Session()->paymillTransactionToken;
        
        // check if token present
        if (empty($paymillToken)) {
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Bootstrap::logAction("No paymill token was provided. Redirect to payments page.");
            $url = $this->Front()->Router()->assemble(array(
                'action' => 'payment',
                'sTarget' => 'checkout',
                'sViewport' => 'account',
                'appendSession' => true,
                'forceSecure' => true
            ));
            $this->redirect($url . '&paymill_error=1');
        } else {
            
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Bootstrap::logAction("Start processing payment with token " . $paymillToken);
            
            $config = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
            $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
            
            // process the payment
            $result = $this->processPayment(array(
                'token' => $paymillToken,
                'amount' => $this->getAmount() * 100,
                'currency' => $this->getCurrencyShortName(),
                'name' => $user['billingaddress']['lastname'] . ', ' . $user['billingaddress']['firstname'],
                'email' => $user['additional']['user']['email'],
                'description' => 'Order ' . $user['billingaddress']['lastname'] . ', ' . $user['billingaddress']['firstname'],
                'libBase' => dirname(dirname(dirname(__FILE__))) . '/paymill/lib/',
                'privateKey' => $config->privateKey,
                'apiUrl' => $config->apiUrl,
                'loggerCallback' => array('Shopware_Plugins_Frontend_PaymillPaymentCreditcard_Bootstrap', 'logAction')
            ));
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Bootstrap::logAction(
                "Payment processing resulted in: " 
                . ($result ? "Success" : "Fail")
            );
            
            // finish the order if payment was sucessfully processed
            if ($result === true) {
                
                Shopware_Plugins_Frontend_PaymPaymentCreditcard_Bootstrap::logAction("Finish order.");
                $this->saveOrder($paymillToken, md5($paymillToken));
                
                // reset the session field
                Shopware()->Session()->paymillTransactionToken = null;
                return $this->forward('finish', 'checkout', null, array('sUniqueID' => md5(microtime())));
                
            } else {
                Shopware()->Session()->paymillTransactionToken = null;
                throw new Exception("An error occured while processing your payment");                
            }
        }
    }
    
    /**
     * Processes the payment
     * @return boolean
     */
    private function processPayment($params) {  
        
        // setup the logger
        $logger = $params['loggerCallback'];
               
        // reformat paramters
        $params['currency'] = strtolower($params['currency']);
        
        require_once $params['libBase'] . 'Services/Paymill/Transactions.php';
        require_once $params['libBase'] . 'Services/Paymill/Clients.php';
        require_once $params['libBase'] . 'Services/Paymill/Creditcards.php';
        
        // setup client params
        $clientParams = array(
            'email' => $params['email'],
            'description' => $params['description']
        );
        
        // setup credit card params
        $creditcardParams = array(
            'token' => $params['token']
        );
        
        // setup transaction params
        $transactionParams = array(
            'amount' => $params['amount'],
            'currency' => $params['currency'],
            'description' => $params['description']
        );

        // Access objects for the Paymill API
        $clientsObject = new Services_Paymill_Clients(
            $params['privateKey'], $params['apiUrl']
        );
        $creditcardsObject = new Services_Paymill_Creditcards(
            $params['privateKey'], $params['apiUrl']
        );
        $transactionsObject = new Services_Paymill_Transactions(
            $params['privateKey'], $params['apiUrl']
        );
        
        // perform conection to the Paymill API and trigger the payment
        try {
            
            // create card
            $creditcard = $creditcardsObject->create($creditcardParams);
            if (!isset($creditcard['id'])) {
                call_user_func_array($logger, array("No creditcard created: " . var_export($creditcard, true)));
                return false;
            } else {
                call_user_func_array($logger, array("Creditcard created: " . $creditcard['id']));
            }
            
            // create client
            $clientParams['creditcard'] = $creditcard['id'];
            $client = $clientsObject->create($clientParams);
            if (!isset($client['id'])) {
                call_user_func_array($logger, array("No client created" . var_export($client, true)));
                return false;
            } else {
                call_user_func_array($logger, array("Client created: " . $client['id']));
            }
        
            // create transaction
            $transactionParams['client'] = $client['id'];
            $transaction = $transactionsObject->create($transactionParams);
            if (!isset($transaction['id'])) {
                call_user_func_array($logger, array("No transaction created" . var_export($transaction, true)));
                return false;
            } else {
                call_user_func_array($logger, array("Transaction created: " . $transaction['id']));
            }
        
            // check result
            if (is_array($transaction) && array_key_exists('status', $transaction)) {
                if ($transaction['status'] == "closed") {
                    // transaction was successfully issued
                    return true;
                } elseif ($transaction['status'] == "open") {
                    // transaction was issued but status is open for any reason
                    call_user_func_array($logger, array("Status is open."));
                    return false;
                } else {
                    // another error occured
                    call_user_func_array($logger, array("Unknown error." . var_export($transaction, true)));
                    return false;
                }
            } else {
                // another error occured
                call_user_func_array($logger, array("Transaction could not be issued."));
                return false;
            }
            
        } catch (Services_Paymill_Exception $ex) {
            // paymill wrapper threw an exception
            call_user_func_array($logger, array("Exception thrown from paymill wrapper: " . $ex->getMessage()));
            return false;
        }
        
        return true;
    }
}
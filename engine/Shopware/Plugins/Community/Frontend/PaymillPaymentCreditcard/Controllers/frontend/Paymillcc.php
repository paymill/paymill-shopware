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
    public function indexAction() {
        $paymillToken = Shopware()->Session()->paymillTransactionToken;
        if (empty($paymillToken)) {
            Shopware_Plugins_Frontend_PaymillPaymentCreditcard_Bootstrap::logAction("No paymill token was provided. Redirect to payments page.");
            $url = $this->Front()->Router()->assemble(array(
                'action' => 'payment',
                'sTarget' => 'checkout',
                'sViewport' => 'account',
                'appendSession' => true,
                'forceSecure' => true
            ));
            $this->redirect($url . '&paymill_error=1');
        } else {
            
            Shopware_Plugins_Frontend_PaymillPaymentCreditcard_Bootstrap::logAction("Start processing payment with token " . $paymillToken);
            
            $config = Shopware()->Plugins()->Frontend()->PaymillPaymentCreditcard()->Config();
            
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
            Shopware_Plugins_Frontend_PaymillPaymentCreditcard_Bootstrap::logAction("Payment processing resulted in: " . $result);
            
            // finish the order
            if ($result === true) {
                Shopware_Plugins_Frontend_PaymillPaymentCreditcard_Bootstrap::logAction("Finish order.");
                $this->saveOrder($paymillToken, md5($paymillToken));
                // reset the session field
                Shopware()->Session()->paymillTransactionToken = null;
                return $this->forward('finish', 'checkout', null, array('sUniqueID' => md5(microtime())));
            } else {
                throw new Exception("Die Zahlung konnte nicht durchgeführt werden. Bitte prüfen Sie das Log.");                
            }
            
        }
    }
    
    private function processPayment($params) {  
               
        // enhance paramters
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
                call_user_func_array($params['loggerCallback'], array("No creditcard created"));
                return false;
            } else {
                call_user_func_array($params['loggerCallback'], array("Creditcard created: " . $creditcard['id']));
            }
            
            // create client
            $clientParams['creditcard'] = $creditcard['id'];
            $client = $clientsObject->create($clientParams);
            
            if (!isset($client['id'])) {
                call_user_func_array($params['loggerCallback'], array("No client created"));
                return false;
            } else {
                call_user_func_array($params['loggerCallback'], array("Client created: " . $client['id']));
            }
        
            // create transaction
            $transactionParams['client'] = $client['id'];
            $transaction = $transactionsObject->create($transactionParams);
            if (!isset($transaction['id'])) {
                call_user_func_array($params['loggerCallback'], array("No client created"));
                return false;
            } else {
                call_user_func_array($params['loggerCallback'], array("Transaction created: " . $transaction['id']));
            }
        
            if (is_array($transaction) && array_key_exists('status', $transaction)) {
                if ($transaction['status'] == "closed") {
                    return true;
                } elseif ($transaction['status'] == "open") {
                    call_user_func_array($params['loggerCallback'], array("Status is open."));
                    return false;
                } else {
                    call_user_func_array($params['loggerCallback'], array("Unknown error."));
                    return false;
                }
            } else {
            }
        } catch (Services_Paymill_Exception $ex) {
            call_user_func_array($params['loggerCallback'], array("Exception thrown from paymill wrapper: " . $ex->getMessage()));
            return false;
        }
        
        return true;
    }
}
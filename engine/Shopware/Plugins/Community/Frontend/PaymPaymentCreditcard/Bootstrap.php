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

class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    // get the payment row
    public function Payment() {
        return Shopware()->Payments()->fetchRow(array('name=?' => 'paymillcc'));
    }
    
    // installer
    public function install() {
        
        $this->createPayments();
        
        $this->createForm();
        
        $hook = $this->createHook(
            'Shopware_Controllers_Frontend_Account', 
            'paymentAction', 
            'onpaymentAction', 
            Enlight_Hook_HookHandler::TypeAfter, 
            0
		);
		$this->subscribeHook($hook);
		
        $event = $this->createEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
        $this->subscribeEvent($event);

        $event = $this->createEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPaymillcc', 'onGetControllerPath');
        $this->subscribeEvent($event);
        
        return true;
    }
    
    // uninstaller
    public function uninstall() {
        if ($payment = $this->Payment()) {
            $payment->delete();
        }
        return parent::uninstall();
    }

    // enabler
    public function enable() {
        $payment = $this->Payment();
        $payment->active = 1;
        $payment->save();
        return parent::enable();
    }

    // disabler
    public function disable() {
        $payment = $this->Payment();
        $payment->active = 0;
        $payment->save();

        return parent::disable();
    }

    // create payment entry
    protected function createPayments() {
        $paymentRow = Shopware()->Payments()->createRow(
            array(
                'name' => 'paymillcc',
                'description' => 'Kreditkartenzahlung',
                'action' => 'payment_paymillcc',
                'active' => 1,
                'template' => 'paymillcc.tpl',
                'pluginID' => $this->getId()
            )
        )->save();
    }

    /**
     * the configuration path
     */
    public function createForm() {
        $form = $this->Form();

        $form->setElement('text', 'publicKey', array(
            'label' => 'Public Key',
            'required' => true
        ));

        $form->setElement('text', 'privateKey', array(
            'label' => 'Private Key',
            'required' => true
        ));
        
        $form->setElement('text', 'bridgeUrl', array(
            'label' => 'Bridge URL',
            'required' => true,
            'value' => 'https://bridge.paymill.de/'
        ));
        
        $form->setElement('text', 'apiUrl', array(
            'label' => 'API URL',
            'required' => true,
            'value' => 'https://api.paymill.de/v1/'
        ));

        $form->setElement('checkbox', 'paymillLogging', array(
            'label' => 'Logging aktivieren', 
            'value' => '0'
        ));
        
        $form->save();
    }
    
    /**
     * controller path
     */
    public static function onGetControllerPath(Enlight_Event_EventArgs $args) {
        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
        return dirname(__FILE__) . '/Controllers/frontend/Paymillcc.php';
    }
    
    /**
     * Called on post requests
     */
    public static function onPostDispatch(Enlight_Event_EventArgs $args) {
        
        $request  = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();
        $view     = $args->getSubject()->View();

        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');

        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
            return;
        }
        
        // if there is a token in the request save it for later use
        if ($request->get("paymillToken")) {
            Shopware()->Session()->paymillTransactionToken = $request->get("paymillToken");
            self::logAction("Token " . $request->get("paymillToken") . " stored to session");
        }
    }
    
    /**
     * called on payment configuration account/payment
     */
    public static function onpaymentAction(Enlight_Event_EventArgs $args) {
        $config = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $args->getSubject()->View()->publicKey = $config->publicKey;
        $args->getSubject()->View()->bridgeUrl = $config->bridgeUrl;
        $args->getSubject()->View()->paymillError = $args->getSubject()->Request()->paymill_error;
    }

    /**
     * Returns whether the current user did choose paymillcc as 
     * payment method
     */
    public static function isPaymillPayment() {
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        return $user['additional']['payment']['name'] == "paymillcc";
    }
    
    // logger
    public static function logAction($message) {
        $logfile = dirname(__FILE__) . '/log.txt';
        if (is_writable($logfile)) {
            $handle = fopen($logfile, 'a'); //
            fwrite($handle, "[" . date(DATE_RFC822) . "] " . $message . "\n");
            fclose($handle);
        }
    }
}
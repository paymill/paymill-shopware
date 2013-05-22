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

    /**
     * Returns the version
     * @return string
     */
    public function getVersion()
    {
        return "1.0.2";
    }
    /**
     * Get Info for the Pluginmanager
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'autor' => 'PayIntelligent GmbH',
            'source' => $this->getSource(),
            'support' => 'http://www.payintelligent.de',
            'link' => 'http://www.payintelligent.de',
            'copyright' => 'Copyright (c) 2013, PayIntelligent GmbH',
            'label' => 'Paymill',
            'description' => ''
        );
    }

    /**
     * Performs the necessary installation steps
     * @return boolean
     */
    public function install()
    {
        try{
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManagerShopware::install();
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_FastCheckoutHelper::install();
            $this->createPaymentMeans();
            $this->createForm();
            $this->createEvents();
            $this->applyBackendViewModifications();
        }  
        catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
        return true;
    }
    
    /**
     * Performs the necessary uninstallation steps
     * @return boolean
     */
    public function uninstall()
    {
        Shopware()->Db()->delete("s_core_paymentmeans", "name in('paymillcc','paymilldebit')");
        return parent::uninstall();
    }

    /**
     * Enables the plugin
     * @return boolean
     */
    public function enable()
    {
        try{
            
            $payment[0] = 'paymillcc';
            $payment[1] = 'paymilldebit';
            
            foreach ($payment as $key) {
                $currentPayment = $this->Payments()->findOneBy( array('name' => $key));
                $currentPayment->setActive(true);
            }
        }
        catch (Exception $exception){
            throw new Exception("Cannot change payment-activity state: ".$exception->getMessage());
        }
        return parent::enable();
    }

    /**
     * Disables the plugin
     * @return boolean
     */
    public function disable()
    {
        try{
            
            $payment[0] = 'paymillcc';
            $payment[1] = 'paymilldebit';
            
            foreach ($payment as $key) {
                $currentPayment = $this->Payments()->findOneBy( array('name' => $key));
                $currentPayment->setActive(false);
            }
        }
        catch (Exception $exception){
            throw new Exception("Cannot change payment-activity state: ".$exception->getMessage());
        }
        
        return parent::disable();
    }
    
    /**
     * Defines the capabilities of the plugin
     */
    public function getCapabilities()
    {
        return array( 'install' => true, 'update' => false, 'enable' => true);
    }

    /**
     * Creates the payment method
     * @return void
     */
    protected function createPaymentMeans()
    {
        $paymillcc = array(
                        'name' => 'paymillcc',
                        'description' => 'Kreditkartenzahlung',
                        'action' => 'payment_paymill',
                        'active' => 1,
                        'template' => 'paymill.tpl', //'paymillcc.tpl',
                        'pluginID' => $this->getId()
                    );
        
        Shopware()->Payments()->createRow($paymillcc)->save();
        
        $paymilldebit = array(
                            'name' => 'paymilldebit',
                            'description' => 'Lastschrift',
                            'action' => 'payment_paymill',
                            'active' => 1,
                            'template' => 'paymill.tpl', //'paymilldebit.tpl',
                            'pluginID' => $this->getId()
                        );
        
        Shopware()->Payments()->createRow($paymilldebit)->save();
    }

    /**
     * Creates the configuration fields
     * @return void
     */
    public function createForm()
    {
        $form = $this->Form();

        $form->setElement('text', 'publicKey', array(
            'label' => 'Public Key',
            'required' => true
        ));

        $form->setElement('text', 'privateKey', array(
            'label' => 'Private Key',
            'required' => true
        ));

        $form->setElement('checkbox', 'paymillDebugging', array(
            'label' => 'Debugging aktivieren',
            'value' => false
        ));
       
        $form->setElement('checkbox', 'paymillFastCheckout', array(
            'label' => 'Daten für Fast Checkout speichern',
            'value' => false
        ));

        $form->setElement('checkbox', 'paymillLogging', array(
            'label' => 'Logging aktivieren',
            'value' => false
        ));

        $form->setElement('checkbox', 'paymillShowLabel', array(
            'label' => 'Paymill Label anzeigen',
            'value' => false
        ));

        $form->save();
    }

    /**
     * Returns the controller path
     * @return string
     */
    public static function onGetControllerPath()
    {
        Shopware()->Template()->addTemplateDir( Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . '/Views/');
        return Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . '/Controllers/frontend/Paymill.php';
    }

    /**
     * Triggered on every request
     * @return void
     */
    public static function onPostDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();
        $view = $args->getSubject()->View();

        Shopware()->Template()->addTemplateDir( Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . '/Views/');

        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
            return;
        }

        // if there is a token in the request save it for later use
        if ($request->get("paymillToken")) {
            Shopware()->Session()->paymillTransactionToken = $request->get("paymillToken");
        }
        
        if (self::isPaymillPayment()) {
            $view->sRegisterFinished = 'false';
            if(self::isFcReady()){
                $view->sRegisterFinished = null;
                Shopware()->Session()->paymillTransactionToken = "NoTokenRequired";
            }
            
        }
    }

    /**
     * Extends the confirmationpage with an Errorbox, if there is an error.
     * Saves the Amount into the Session and passes it to the Template
     * @param Enlight_Event_EventArgs $arguments
     * @return null
     */
    public function onCheckoutConfirm(Enlight_Event_EventArgs $arguments)
    {
        $params = $arguments->getRequest()->getParams();
        $arguments->getSubject()->View()->ccHasFcData = $this->isFcReady("paymillcc");
        $arguments->getSubject()->View()->elvHasFcData = $this->isFcReady("paymilldebit");
        if (self::isPaymillPayment()) {
            $view->sRegisterFinished = 'false';
            if(self::isFcReady()){
                $view->sRegisterFinished = null;
                Shopware()->Session()->paymillTransactionToken = "NoTokenRequired";
            }
           
        }
        $this->saveAmount($arguments);
        
        if ($arguments->getRequest()->getActionName() !== 'confirm' && !isset($params["errorMessage"])) {
            return;
        }
        
        
        $pigmbhErrorMessage = Shopware()->Session()->pigmbhErrorMessage;
        unset(Shopware()->Session()->pigmbhErrorMessage);
        $view = $arguments->getSubject()->View();
        $content = '{if $pigmbhErrorMessage} <div class="grid_20">' .
                   '<div class="error">' .
                   '<div class="center">' . '<strong> {$pigmbhErrorMessage} </strong>' . '</div>' .
                   '</div>' .
                   '</div> {/if}';
        
        $view->extendsBlock("frontend_index_content_top", $content, "append");
        $view->setScope(Enlight_Template_Manager::SCOPE_PARENT);
        $view->pigmbhErrorMessage = $pigmbhErrorMessage;
        
    }
    
    
    /**
     * Returns whether the current user did choose paymillcc as
     * payment method
     * @return boolean
     */
    public static function isPaymillPayment()
    {
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        return in_array($user['additional']['payment']['name'], array("paymillcc", "paymilldebit"));
    }
    
    
    public static function isFcReady($paymentNameArg = null){
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        if(in_array($user['additional']['payment']['name'], array("paymillcc", "paymilldebit"))){
            $userId = $user['billingaddress']['userID'];
            if($paymentNameArg === null){
                $paymentName = $user['additional']['payment']['name'];
            }
            else{
                $paymentName = $paymentNameArg;
            }
            
            $payment = $paymentName == 'paymillcc'? 'ccPaymentId': 'elvPaymentId';
            $sql = "SELECT count(`$payment`) FROM `paymill_fastCheckout` WHERE `userId` = $userId AND `$payment` IS NOT null";
            $fcEnabled = Shopware()->Db()->fetchOne($sql);
            $log = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManagerShopware();
            return $fcEnabled == 1;
        }
        return false;
    }


    /**
     * Creates all Events for the plugins
     */
    private function createEvents()
    {
        $this->subscribeEvent( 'Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
        $this->subscribeEvent( 'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPaymill', 'onGetControllerPath' );
        $this->subscribeEvent( 'Enlight_Controller_Action_PreDispatch_Frontend_Checkout', 'onCheckoutConfirm');
        $this->subscribeEvent( 'Enlight_Controller_Dispatcher_ControllerPath_Backend_PaymillLogging', 'paymillBackendControllerLogging');
    }
    
    private function applyBackendViewModifications()
    {
        try {
            $parent = $this->Menu()->findOneBy('label', 'logfile');
            $this->createMenuItem(array(
                'label' => 'Paymill',
                'class' => 'sprite-cards-stack',
                'active' => 1,
                'controller' => 'PaymillLogging',
                'action' => 'index',
                'parent' => $parent
                ));
        } catch (Exception $exception) {
            throw new Exception("can not create menuentry." . $exception->getMessage());
        }
    }
    
    public function paymillBackendControllerLogging()
    {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . 'Views/');
        return Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . "/Controllers/backend/PaymillLogging.php";
    }
    
    /**
     * Saves the Amount to the session, passes it to the current Template
     * @param Enlight_Event_EventArgs $arguments
     */
    private function saveAmount(Enlight_Event_EventArgs $arguments){
        //Save amount into session to allow 3Ds
        $basket = Shopware()->Session()->sOrderVariables['sBasket'];
        $totalAmount = (round((float)$basket['sAmount'] * 100, 2));
        
        Shopware()->Session()->paymillTotalAmount = $totalAmount;
        $arguments->getSubject()->View()->Template()->assign("tokenAmount", $totalAmount);
    }
    
} 
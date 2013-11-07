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
     * Returns the controller path
     *
     * @return string
     */
    public static function onGetControllerPath()
    {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()
                                               ->Path() . '/Views/');

        return Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . '/Controllers/frontend/Paymill.php';
    }

    /**
     * Triggered on every request
     *
     * @param $args
     *
     * @return void
     */
    public static function onPostDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();
        $view = $args->getSubject()->View();

        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()
                                               ->Path() . '/Views/');

        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
            return;
        }

        // if there is a token in the request save it for later use
        if ($request->get("paymillToken")) {
            Shopware()->Session()->paymillTransactionToken = $request->get("paymillToken");
        }

        $user = Shopware()->Session()->sOrderVariables['sUserData'];
        $userId = $user['billingaddress']['userID'];
        $paymentName = $user['additional']['payment']['name'];

        if (in_array($user['additional']['payment']['name'], array("paymillcc", "paymilldebit"))) {
            $view->sRegisterFinished = 'false';
            $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
            $paymentId = $modelHelper->getPaymillPaymentId($paymentName, $userId);
            if ($paymentId != null && empty(Shopware()->Session()->paymillTransactionToken)) {
                $view->sRegisterFinished = null;
                Shopware()->Session()->paymillTransactionToken = "NoTokenRequired";
            }
        }
    }

    /**
     * Extends the confirmation page with an error box, if there is an error.
     * Saves the Amount into the Session and passes it to the Template
     *
     * @param Enlight_Event_EventArgs $arguments
     *
     * @return null
     */
    public function onCheckoutConfirm(Enlight_Event_EventArgs $arguments)
    {
        $view = $arguments->getSubject()->View();
        $params = $arguments->getRequest()->getParams();
        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        $userId = $user['billingaddress']['userID'];
        $paymentName = $user['additional']['payment']['name'];
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";

        require_once dirname(__FILE__) . '/lib/Services/Paymill/Payments.php';
        $paymentIdCc = $modelHelper->getPaymillPaymentId('cc',$userId);
        $paymentIdElv = $modelHelper->getPaymillPaymentId('elv',$userId);
        if ($paymentIdCc != "") {
            $ccPayment = new Services_Paymill_Payments($privateKey, $apiUrl);
            $paymentObject = $ccPayment->getOne($paymentIdCc);
            $view->paymillCardNumber = "..." . $paymentObject['last4'];
            $view->paymillCvc = "***";
            $view->paymillMonth = $paymentObject['expire_month'];
            $view->paymillYear = $paymentObject['expire_year'];
        } else {
            $view->paymillCardNumber = "";
            $view->paymillCvc = "";
            $view->paymillMonth = "";
            $view->paymillYear = "";
        }


        if ($paymentIdElv != "") {
            $elvPayment = new Services_Paymill_Payments($privateKey, $apiUrl);
            $paymentObject = $elvPayment->getOne($paymentIdElv);
            $view->paymillAccountNumber = $paymentObject['account'];
            $view->paymillBankCode = $paymentObject['code'];
        } else {
            $view->paymillAccountNumber = "";
            $view->paymillBankCode = "";
        }


        if (in_array($paymentName, array("paymillcc", "paymilldebit"))) {
            $view->sRegisterFinished = 'false';
            if ($modelHelper->getPaymillPaymentId($paymentName, $userId)) {
                Shopware()->Session()->paymillTransactionToken = "NoTokenRequired";
            }
        }

        //Save amount into session to allow 3Ds
        $basket = Shopware()->Session()->sOrderVariables['sBasket'];
        $totalAmount = (round((float)$basket['sAmount'] * 100, 2));

        Shopware()->Session()->paymillTotalAmount = $totalAmount;
        $arguments->getSubject()->View()->Template()->assign("tokenAmount", $totalAmount);
        $arguments->getSubject()->View()->Template()->assign("debug", $swConfig->get("paymillDebugging"));

        if ($arguments->getRequest()->getActionName() !== 'confirm' && !isset($params["errorMessage"])) {
            return;
        }

        $pigmbhErrorMessage = Shopware()->Session()->pigmbhErrorMessage;
        unset(Shopware()->Session()->pigmbhErrorMessage);
        $content = '{if $pigmbhErrorMessage} <div class="grid_20">' . '<div class="error">' . '<div class="center">' . '<strong> {$pigmbhErrorMessage} </strong>' . '</div>' . '</div>' . '</div> {/if}';

        $view->extendsBlock("frontend_index_content_top", $content, "append");
        $view->setScope(Enlight_Template_Manager::SCOPE_PARENT);
        $view->pigmbhErrorMessage = $pigmbhErrorMessage;
    }

    /**
     * Returns the version
     *
     * @return string
     */
    public function getVersion()
    {
        return "1.1.1";
    }



    /**
     * Return the path of the backend controller
     *
     * @return String backend controller path
     */
    public function paymillBackendControllerLogging()
    {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()
                                               ->Path() . 'Views/');

        return Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()
               ->Path() . "/Controllers/backend/PaymillLogging.php";
    }

    /**
     * Get Info for the Pluginmanager
     *
     * @return array
     */
    public function getInfo()
    {
        return array('version'   => $this->getVersion(),
                     'autor'     => 'PayIntelligent GmbH',
                     'source'    => $this->getSource(),
                     'supplier'  => 'PAYMILL GmbH',
                     'support'   => 'support@paymill.com',
                     'link'      => 'https://www.paymill.com',
                     'copyright' => 'Copyright (c) 2013, PayIntelligent GmbH',
                     'label'     => 'Paymill',
                     'description' => ''
        );
    }

    /**
     * @param $arguments
     */
    public function onUpdateCustomerEmail($arguments)
    {
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        $userId = $user['billingaddress']['userID'];
        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $clientId = $modelHelper->getPaymillClientId($userId);

        //If there is a client for the customer
        if ($clientId !== "") {
            $email = $arguments['email'];
            $description = Shopware()->Config()->get('shopname') . " " . $user['billingaddress']['customernumber'];

            //Update the client
            $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
            $privateKey = trim($swConfig->get("privateKey"));
            $apiUrl = "https://api.paymill.com/v2/";
            require_once dirname(__FILE__) . '/lib/Services/Paymill/Clients.php';
            $client = new Services_Paymill_Clients($privateKey, $apiUrl);
            $client->update(array('id' => $clientId, 'email' => $email, 'description' => $description));
        }
    }

    /**
     * Eventhandler for the display of the paymill order operations tab in the order detail view
     *
     * @param $arguments
     */
    public function extendOrderDetailView($arguments)
    {
        $arguments->getSubject()->View()->addTemplateDir($this->Path() . 'Views/');

        if ($arguments->getRequest()->getActionName() === 'load') {
            $arguments->getSubject()->View()->extendsTemplate('backend/paymill_order_operations/view/main/window.js');
        }

        if ($arguments->getRequest()->getActionName() === 'index') {
            $arguments->getSubject()->View()->extendsTemplate('backend/paymill_order_operations/app.js');
        }
    }

    /**
     * Performs the necessary installation steps
     *
     * @throws Exception
     * @return boolean
     */
    public function install()
    {
        try {
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManager::install();
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper::install($this);
            $translationHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_TranslationHelper($this->Form());
            $this->createPaymentMeans();
            $this->_createForm();
            $this->_addTranslationSnippets();
            $this->_createEvents();
            $this->_applyBackendViewModifications();
            $this->_translatePaymentNames();
            $translationHelper->createPluginConfigTranslation();
        } catch(Exception $exception) {
            $this->uninstall();
            throw new Exception($exception->getMessage());
        }

        $installSuccess = parent::install();
        Shopware()->Log()->Err("Installed PAYMILL plugin: ". var_export($installSuccess, true));
        return $installSuccess;
    }

    /**
     * Returns the controller path for the backend order operations controller
     *
     * @return string
     */
    public function paymillBackendControllerOperations()
    {
        Shopware()->Template()->addTemplateDir($this->Path() . 'Views/');

        return $this->Path() . "/Controllers/backend/PaymillOrderOperations.php";
    }

    /**
     * Performs the necessary uninstall steps
     *
     * @return boolean
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Updates the Plugin and its components
     *
     * @param string $oldVersion
     *
     * @throws Exception
     * @return boolean
     */
    public function update($oldVersion)
    {
        $updateSuccess = false;

        try{
            switch($oldVersion) {
                case '1.0.0':
                    // update not possible
                case '1.0.1':
                    // update not possible
                    Shopware()->Log()->Err('Your plugin version is no longer supported. Please contact support for further information');
                    throw new Exception('Your plugin version is no longer supported. Please contact support for further information');
                case '1.0.2':
                case '1.0.3':
                case '1.0.4':
                case '1.0.5':
                case '1.0.6':
                    $loggingHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManager();
                    $loggingHelper->updateFromLegacyVersion();

                    Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper::install($this);
                    $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
                    $modelHelper->updateFromLegacyVersion();

                    $this->_createEvents();
                    $this->_addTranslationSnippets();
                    $this->_updateConfigForm();
                    $translationHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_TranslationHelper($this->Form());
                    $translationHelper->updateConfigConfigTranslations();

                case '1.1.0':
                    $updateSuccess = true;
                    break;
                default:
                    Shopware()->Log()->Err('Unknown version, cannot update');
                    throw new Exception('Unknown version, cannot update');
            }
        } catch (Exception $exception) {
            Shopware()->Log()->Err($exception->getMessage());
            throw new Exception($exception->getMessage());
        }

        Shopware()->Log()->Err("Updated PAYMILL plugin: ". var_export($updateSuccess, true));
        return $updateSuccess;
    }

    /**
     * Adds the translation snippets into the database.
     * Returns true or throws an exception in case of an error
     *
     * @throws Exception
     * @return void
     */
    private function _addTranslationSnippets()
    {
        try {
            $csv = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_CsvReader(dirname(__FILE__) . '/locale/');
            Shopware()->Db()->exec($csv->getSqlInsert());
        } catch (Exception $exception) {
            Shopware()->Log()->Err("Can not insert translation-snippets." . $exception->getMessage());
            throw new Exception("Can not insert translation-snippets." . $exception->getMessage());
        }
    }

    /**
     * Translates the payment names
     *
     * @throws Exception
     * @return void
     */
    private function _translatePaymentNames()
    {
        try {
            $translationObject = new Shopware_Components_Translation();
            $ccPayment = $this->Payments()->findOneBy(array('name' => 'paymillcc'));
            $ccId = $ccPayment->getId();
            $elvPayment = $this->Payments()->findOneBy(array('name' => 'paymilldebit'));
            $elvId = $elvPayment->getId();
            $snippets = Shopware()->Db()->fetchAll("
                        SELECT localeID, name, value
                        FROM s_core_snippets
                        WHERE namespace = 'Paymill' AND (name = 'paymill_credit_card' OR name = 'paymill_direct_debit')
                        GROUP BY `localeID`,`value`"
            );

            foreach ($snippets as $snippet) {
                $translationObject->write($snippet['localeID'], "config_payment", $snippet['name'] ===
                                                                                  'paymill_direct_debit' ? $elvId : $ccId, array('description' => $snippet['value']), 1);
            }
        } catch (Exception $exception) {
            Shopware()->Log()->Err("Can not create translation for payment names." . $exception->getMessage());
            throw new Exception("Can not create translation for payment names." . $exception->getMessage());
        }
    }

    /**
     * Disables the plugin
     *
     * @throws Exception
     * @return boolean
     */
    public function disable()
    {
        try {

            $payment[0] = 'paymillcc';
            $payment[1] = 'paymilldebit';

            foreach ($payment as $key) {
                $currentPayment = $this->Payments()->findOneBy(array('name' => $key));
                if ($currentPayment) {
                    $currentPayment->setActive(false);
                }
            }
        } catch (Exception $exception) {
            Shopware()->Log()->Err("Cannot disable payment: " . $exception->getMessage());
            throw new Exception("Cannot disable payment: " . $exception->getMessage());
        }

        return parent::disable();
    }

    /**
     * Creates the payment method
     *
     * @throws Exception
     * @return void
     */
    protected function createPaymentMeans()
    {
        try{
            $this->createPayment(
                     array(
                          'active' => 1,
                          'name'     => 'paymillcc',
                          'action'   => 'payment_paymill',
                          'template' => 'paymill.tpl',
                          'description' => 'Kreditkartenzahlung',
                          'additionalDescription' => ''
                     )
                );

                $this->createPayment(
                     array(
                          'active' => 1,
                          'name'     => 'paymilldebit',
                          'action'   => 'payment_paymill',
                          'template' => 'paymill.tpl',
                          'description' => 'ELV',
                          'additionalDescription' => ''
                     )
                );

        } catch (Exception $exception){
            Shopware()->Log()->Err("There was an error creating the payment means. " . $exception->getMessage());
            throw new Exception("There was an error creating the payment means. " . $exception->getMessage());
        }
    }

    /**
     * Creates the configuration fields
     *
     * @throws Exception
     * @return void
     */
    private function _createForm()
    {

        try{
            $form = $this->Form();
            $form->setElement('text', 'publicKey', array('label' => 'Public Key', 'required' => true));
            $form->setElement('text', 'privateKey', array('label' => 'Private Key', 'required' => true));
            $form->setElement('checkbox', 'paymillPreAuth', array('label' => 'Authorize credit card transactions during checkout and capture manually', 'value' => false));
            $form->setElement('checkbox', 'paymillDebugging', array('label' => 'Activate debugging', 'value' => false));
            $form->setElement('checkbox', 'paymillFastCheckout', array('label' => 'Save data for FastCheckout', 'value' => false));
            $form->setElement('checkbox', 'paymillLogging', array('label' => 'Activate logging', 'value' => false));
            $form->setElement('checkbox', 'paymillShowLabel', array('label' => 'Show Paymill-label during checkout', 'value' => false));
        } catch (Exception $exception){
            Shopware()->Log()->Err("There was an error creating the plugin configuration. " . $exception->getMessage());
            throw new Exception("There was an error creating the plugin configuration. " . $exception->getMessage());
        }
    }

    /**
     * Creates all Events for the plugins
     *
     * @throws Exception
     * @return void
     */
    private function _createEvents()
    {
        try{
            $this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
            $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPaymill', 'onGetControllerPath');
            $this->subscribeEvent('Enlight_Controller_Action_PreDispatch_Frontend_Checkout', 'onCheckoutConfirm');
            $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_PaymillLogging', 'paymillBackendControllerLogging');
            $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_PaymillOrderOperations', 'paymillBackendControllerOperations');
            $this->subscribeEvent('Shopware_Modules_Admin_UpdateAccount_FilterEmailSql', 'onUpdateCustomerEmail');
            $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Backend_Order', 'extendOrderDetailView');
        } catch (Exception $exception){
            Shopware()->Log()->Err("There was an error registering the plugins events. " . $exception->getMessage());
            throw new Exception("There was an error registering the plugins events. " . $exception->getMessage());
        }
    }

    /**
     * Modifies the Backend menu by adding a PaymillLogging Label as a child element of the shopware logging
     *
     * @throws Exception
     * @return void
     */
    private function _applyBackendViewModifications()
    {
        try {
            $parent = $this->Menu()->findOneBy('label', 'logfile');
            $this->createMenuItem(array('label'      => 'Paymill', 'class' => 'sprite-cards-stack', 'active' => 1,
                                        'controller' => 'PaymillLogging', 'action' => 'index', 'parent' => $parent));
        } catch (Exception $exception) {
            Shopware()->Log()->Err("can not create menu entry." . $exception->getMessage());
            throw new Exception("can not create menu entry." . $exception->getMessage());
        }
    }

    /**
     * Updates the config form
     * @throws Exception
     */
    private function _updateConfigForm()
    {
        //Add new Config Element
        try{
            $form = $this->Form();
            $form->setElement('checkbox', 'paymillPreAuth', array('label' => 'Authorize credit card transactions during checkout and capture manually', 'value' => false));
        } catch (Exception $exception) {
            Shopware()->Log()->Err("Could not update config form with new fields: ". $exception->getMessage());
            throw new Exception("Could not update config form with new fields: ". $exception->getMessage());
        }
    }
}
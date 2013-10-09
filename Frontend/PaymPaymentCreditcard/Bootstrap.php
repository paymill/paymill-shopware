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
     * @param $args
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

        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        $userId = $user['billingaddress']['userID'];
        $paymentName = $user['additional']['payment']['name'];
        $helper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_FastCheckoutHelper($userId, $paymentName);
        if (in_array($user['additional']['payment']['name'], array("paymillcc", "paymilldebit"))) {
            $view->sRegisterFinished = 'false';

            if ($helper->isFcReady() && empty(Shopware()->Session()->paymillTransactionToken)) {
                $view->sRegisterFinished = null;
                Shopware()->Session()->paymillTransactionToken = "NoTokenRequired";
            }
        }
    }

    /**
     * Returns the version
     *
     * @return string
     */
    public function getVersion()
    {
        return "1.1.0";
    }

    /**
     * Get Info for the Pluginmanager
     *
     * @return array
     */
    public function getInfo()
    {
        return array('version'  => $this->getVersion(), 'autor' => 'PayIntelligent GmbH',
                     'source'   => $this->getSource(), 'supplier' => 'PAYMILL GmbH', 'support' => 'support@paymill.com',
                     'link'     => 'https://www.paymill.com', 'copyright' => 'Copyright (c) 2013, PayIntelligent GmbH',
                     'label'    => 'Paymill', 'description' => '');
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
        $params = $arguments->getRequest()->getParams();
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        $userId = $user['billingaddress']['userID'];
        $helper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_FastCheckoutHelper($userId, 'cc');
        $view = $arguments->getSubject()->View();

        $helper->assignDisplayData($view);

        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        if (in_array($user['additional']['payment']['name'], array("paymillcc", "paymilldebit"))) {
            $view->sRegisterFinished = 'false';
            if ($helper->isFcReady()) {
                Shopware()->Session()->paymillTransactionToken = "NoTokenRequired";
            }
        }

        //Save amount into session to allow 3Ds
        $basket = Shopware()->Session()->sOrderVariables['sBasket'];
        $totalAmount = (round((float)$basket['sAmount'] * 100, 2));

        Shopware()->Session()->paymillTotalAmount = $totalAmount;
        $arguments->getSubject()->View()->Template()->assign("tokenAmount", $totalAmount);

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
     * Performs the necessary installation steps
     * @throws Exception
     * @return boolean
     */
    public function install()
    {
        try {
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManagerShopware::install();
            Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_FastCheckoutHelper::install();
            $this->createPaymentMeans();
            $this->createForm();
            $this->_createPluginConfigTranslation();
            $this->addTranslationSnippets();
            $this->createEvents();
            $this->applyBackendViewModifications();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }

        return true;
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
     * @param $arguments event arguments
     */
    public function onUpdateCustomerEmail($arguments)
    {
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        $helper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_FastCheckoutHelper();
        //If there is a client for the customer
        if($helper->loadClientId()){
            $clientId = $helper->clientId;
            $email = $arguments['email'];
            $description = Shopware()->Config()->get('shopname') . " " . $user['billingaddress']['customernumber'];

            //Update the client
            $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
            $privateKey = trim($swConfig->get("privateKey"));
            $apiUrl = "https://api.paymill.com/v2/";
            require_once dirname(__FILE__) . '/lib/Services/Paymill/Clients.php';
            $client = new Services_Paymill_Clients($privateKey, $apiUrl);
            $result = $client->update(array('id' => $clientId, 'email' => $email, 'description' => $description));
        }
    }

    /**
     * Performs the necessary uninstall steps
     *
     * @return boolean
     */
    public function uninstall()
    {
        Shopware()->Db()->delete("s_core_paymentmeans", "name in('paymillcc','paymilldebit')");

        return parent::uninstall();
    }

    /**
     * Updates the Plugin and its components
     *
     * @param string $oldVersion
     * @return boolean
     */
    public function update($oldVersion)
    {
        $result = false;
        switch ($oldVersion) {
            case '1.0.0':
            case '1.0.1':
            case '1.0.2':
                $result = $this->addTranslationSnippets();
        }

        return $result;
    }

    /**
     * Creates the Translation for the plugin configuration
     */
    private function _createPluginConfigTranslation()
    {
        try {
            $form = $this->Form();
            $translations = array('de_DE' => array('publicKey'           => 'Public Key',
                                                   'privateKey' => 'Privat Key',
                                                   'paymillPreAuth' => 'Kreditkarten Transaktionen im Checkout authorisieren, Buchung manuell durchführen',
                                                   'paymillDebugging'    => 'Debugging aktivieren',
                                                   'paymillFastCheckout' => 'Daten für Fast Checkout speichern',
                                                   'paymillLogging'      => 'Logging aktivieren',
                                                   'paymillShowLabel'    => 'Paymill Label anzeigen'),
                                  'en_GB' => array('publicKey'           => 'Public Key',
                                                   'privateKey' => 'Private Key',
                                                   'paymillPreAuth' => 'Authorize credit card transactions during checkout and capture manually',
                                                   'paymillDebugging'    => 'Activate debugging',
                                                   'paymillFastCheckout' => 'Save data for FastCheckout',
                                                   'paymillLogging'      => 'Activate logging',
                                                   'paymillShowLabel'    => 'Show Paymill-label'));

            $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
            foreach ($translations as $locale => $snippets) {
                $localeModel = $shopRepository->findOneBy(array('locale' => $locale));
                foreach ($snippets as $element => $snippet) {
                    if ($localeModel === null) {
                        continue;
                    }
                    $elementModel = $form->getElement($element);
                    if ($elementModel === null) {
                        continue;
                    }
                    $translationModel = new \Shopware\Models\Config\ElementTranslation();
                    $translationModel->setLabel($snippet);
                    $translationModel->setLocale($localeModel);
                    $elementModel->addTranslation($translationModel);
                }
            }
        } catch (Exception $exception) {
            $this->uninstall();
            throw new Exception("Can not create translation." . $exception->getMessage());
        }
    }

    /**
     * Adds the translation snippets into the database.
     * Returns true or throws an exception in case of an error
     *
     * @return true
     * @throws Exception
     */
    private function addTranslationSnippets()
    {
        $sql_shop_ids = "SELECT `id` FROM `s_core_shops` WHERE `locale_id`= 2";
        $sql_snippets = "REPLACE INTO `s_core_snippets` (`namespace`, `name`, `value`, `localeID`, `shopID`,`created`, `updated`) VALUES " . "('Paymill', 'version', 'Version', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'date', 'Date', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'devinfo', 'Developerinformation', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'additionaldevinfo', 'Additional Developerinformation', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'action', 'Action', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'merchantinfo', 'Merchantinfo', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'invalid_cardnumber', 'Please enter a valid creditcardnumber.', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'invalid_cvc', 'Please enter a valid securecode (see back of creditcard).', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'invalid_expirydate', 'The expirydate is invalid.', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'invalid_accountholder', 'Please enter the cardholders name.', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'invalid_accountnumber', 'Please enter a valid accountnumber.', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'invalid_bankcode', 'Please a valid bankcode.', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'invalid_error_creditcard', 'Please enter your credit card information. For security reason we will not save them on our system.', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'invalid_error_debit', 'Please enter your accountdata. For security reason we will not save them on our system.', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_cardholder', 'Cardholder *', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_cardnumber', 'Cardnumber *', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_cvc', 'CVC *', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_expirydate', 'Valid until (MM/YYYY) *', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_accountholder', 'Accountholder *', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_accountnumber', 'Accountnumber *', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_bankcode', 'Bankcode *', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_info', 'Fields marked with a * are required.', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_paymilllabel_cc', 'Secure credit card payment powered by', '2', '%SHOPID%', NOW(), NOW()), " . "('Paymill', 'form_paymilllabel_debit', 'direct debit payment powered by', '2', '%SHOPID%', NOW(), NOW())," . "('Paymill', 'general_error', 'An error occurred while processing your payment', '2', '%SHOPID%', NOW(), NOW());";
        try {
            $shopIDs = Shopware()->Db()->fetchAll($sql_shop_ids);

            if (!empty($shopIDs)) {
                $sql = "";
                foreach ($shopIDs as $row) {
                    $sql .= preg_replace("/%SHOPID%/", $row['id'], $sql_snippets);
                }
                Shopware()->Db()->exec($sql);
            }

            return true;
        } catch (Exception $exception) {
            $this->uninstall();
            throw new Exception("Can not insert translation-snippets." . $exception->getMessage());
        }
    }

    /**
     * Enables the plugin
     *
     * @throws Exception
     * @return boolean
     */
    public function enable()
    {
        try {

            $payment[0] = 'paymillcc';
            $payment[1] = 'paymilldebit';

            foreach ($payment as $key) {
                $currentPayment = $this->Payments()->findOneBy(array('name' => $key));
                if ($currentPayment) {
                    $currentPayment->setActive(true);
                }
            }
        } catch (Exception $exception) {
            throw new Exception("Cannot change payment-activity state: " . $exception->getMessage());
        }

        return parent::enable();
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
            throw new Exception("Cannot change payment-activity state: " . $exception->getMessage());
        }

        return parent::disable();
    }

    /**
     * Defines the capabilities of the plugin
     */
    public function getCapabilities()
    {
        return array('install' => true, 'update' => true, 'enable' => true);
    }

    /**
     * Creates the payment method
     *
     * @return void
     */
    protected function createPaymentMeans()
    {
        $paymillcc = array('name'     => 'paymillcc', 'description' => 'Kreditkartenzahlung',
                           'action'   => 'payment_paymill', 'active' => 1, 'template' => 'paymill.tpl',
            //'paymillcc.tpl',
                           'pluginID' => $this->getId());

        Shopware()->Payments()->createRow($paymillcc)->save();

        $paymilldebit = array('name'     => 'paymilldebit', 'description' => 'ELV',
                              'action'   => 'payment_paymill', 'active' => 1, 'template' => 'paymill.tpl',
            //'paymilldebit.tpl',
                              'pluginID' => $this->getId());

        Shopware()->Payments()->createRow($paymilldebit)->save();
    }

    /**
     * Creates the configuration fields
     *
     * @return void
     */
    public function createForm()
    {
        $form = $this->Form();

        $form->setElement('text', 'publicKey', array('label' => 'Public Key', 'required' => true));

        $form->setElement('text', 'privateKey', array('label' => 'Private Key', 'required' => true));

        $form->setElement('checkbox', 'paymillPreAuth', array('label' => 'Kreditkarten Transaktionen im Checkout authorisieren, Buchung manuell durchführen', 'value' => false));

        $form->setElement('checkbox', 'paymillDebugging', array('label' => 'Debugging aktivieren', 'value' => false));

        $form->setElement('checkbox', 'paymillFastCheckout', array('label' => 'Daten für Fast Checkout speichern',
                                                                   'value' => false));

        $form->setElement('checkbox', 'paymillLogging', array('label' => 'Logging aktivieren', 'value' => false));

        $form->setElement('checkbox', 'paymillShowLabel', array('label' => 'Paymill Label anzeigen', 'value' => false));
    }

    /**
     * Creates all Events for the plugins
     */
    private function createEvents()
    {
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPaymill', 'onGetControllerPath');
        $this->subscribeEvent('Enlight_Controller_Action_PreDispatch_Frontend_Checkout', 'onCheckoutConfirm');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_PaymillLogging', 'paymillBackendControllerLogging');
        $this->subscribeEvent('Shopware_Modules_Admin_UpdateAccount_FilterEmailSql', 'onUpdateCustomerEmail');
    }

    /**
     * Modifies the Backendmenu by adding a PaymillLogging Label as a child element of the shopware logging
     *
     * @throws Exception "can not create menuentry"
     */
    private function applyBackendViewModifications()
    {
        try {
            $parent = $this->Menu()->findOneBy('label', 'logfile');
            $this->createMenuItem(array('label'      => 'Paymill', 'class' => 'sprite-cards-stack', 'active' => 1,
                                        'controller' => 'PaymillLogging', 'action' => 'index', 'parent' => $parent));
        } catch (Exception $exception) {
            throw new Exception("can not create menuentry." . $exception->getMessage());
        }
    }
}
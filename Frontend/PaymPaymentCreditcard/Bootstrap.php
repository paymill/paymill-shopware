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

    private $util;

    public function init()
    {
        $this->util = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_Util();
    }

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

        if ($params['sTargetAction'] === 'cart') {
            return;
        }

        $modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        $userId = $user['billingaddress']['userID'];
        $paymentName = $user['additional']['payment']['name'];
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";

        require_once dirname(__FILE__) . '/lib/Services/Paymill/Payments.php';
        $paymentIdCc = $modelHelper->getPaymillPaymentId('cc', $userId);
        $paymentIdElv = $modelHelper->getPaymillPaymentId('elv', $userId);
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
            $view->paymillAccountNumber = $paymentObject['iban'] != null ? $paymentObject['iban'] : $paymentObject['account'];
            $view->paymillBankCode = $paymentObject['bic'] != null ? $paymentObject['bic'] : $paymentObject['code'];
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
        $basket = $arguments->getSubject()->getBasket();
        $totalAmount = $basket['sAmount'];
        $totalAmount = (round((float) $totalAmount * 100, 2));


        Shopware()->Session()->paymillTotalAmount = $totalAmount;
        $arguments->getSubject()->View()->Template()->assign("tokenAmount", $totalAmount);
        $arguments->getSubject()->View()->Template()->assign("publicKey", trim($swConfig->get("publicKey")));
        $arguments->getSubject()->View()->Template()->assign("sepaActive", $swConfig->get("paymillSepaActive"));
        $arguments->getSubject()->View()->Template()->assign("debug", $swConfig->get("paymillDebugging"));
        $arguments->getSubject()->View()->Template()->assign("CreditcardBrands", $this->getEnabledCreditcardbrands());

        if ($paymentName === "paymilldebit" && Shopware()->Session()->sOrderVariables['sOrderNumber']) {
            $orderModel = Shopware()->Models()->find('Shopware\Models\Order\Order', $this->util->getOrderIdByNumber(Shopware()->Session()->sOrderVariables['sOrderNumber']));
            $orderModelAttribute = $orderModel->getAttribute();
            $arguments->getSubject()->View()->Template()->assign("sepaDate", date('d.m.Y',$orderModelAttribute->getPaymillSepaDate()));
            $view->extendsBlock("frontend_checkout_finishs_transaction_number", "{include file='frontend/Paymillfinish.tpl'}", "after");
        }
        if ($arguments->getRequest()->getActionName() !== 'confirm' && !isset($params["errorMessage"])) {
            return;
        }

        $isConexcoActiveSql = "SELECT active FROM s_core_plugins WHERE name='SwfResponsiveTemplate'";
        $isConexcoActive = (int) Shopware()->Db()->fetchOne($isConexcoActiveSql) === 1;
        if ($isConexcoActive) {
            $templateConfig = Shopware()->Plugins()->Frontend()->SwfResponsiveTemplate()->Config();
            $templateActive = $templateConfig->get('SwfResponsiveTemplateActive');
        } else {
            $templateActive = false;
        }

        $pigmbhErrorMessage = Shopware()->Session()->pigmbhErrorMessage;
        $class = $templateActive ? 'pm_error_replica' : '';
        unset(Shopware()->Session()->pigmbhErrorMessage);
        $content = '{if $pigmbhErrorMessage} ' .
            '<div class="grid_20 {$pigmbhErrorClass}">' .
            '<div class="error">' .
            '<div class="center">' .
            '<strong> {$pigmbhErrorMessage} </strong>' .
            '</div>' .
            '</div>' .
            '</div> ' .
            '{/if}';
        $view->extendsBlock("frontend_index_content_top", $content, "append");
        $view->setScope(Enlight_Template_Manager::SCOPE_PARENT);
        $view->pigmbhErrorMessage = $pigmbhErrorMessage;
        $view->pigmbhErrorClass = $class;
        $view->pigmbhTemplateActive = $templateActive;
    }

    private function getEnabledCreditcardbrands()
    {
        $config = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $shouldBe = array("amex", "carta-si", "carte-bleue", "dankort", "diners-club", "discover", "jcb", "maestro", "mastercard", "china-unionpay", "visa");
        $result = array();
        $result[] = $config->get("paymillBrandIconAmex") ? 'amex' : '';
        $result[] = $config->get("paymillBrandIconCartaSi") ? 'carta-si' : '';
        $result[] = $config->get("paymillBrandIconCarteBleue") ? 'carte-bleue' : '';
        $result[] = $config->get("paymillBrandIconDankort") ? 'dankort' : '';
        $result[] = $config->get("paymillBrandIconDinersclub") ? 'diners-club' : '';
        $result[] = $config->get("paymillBrandIconDiscover") ? 'discover' : '';
        $result[] = $config->get("paymillBrandIconJcb") ? 'jcb' : '';
        $result[] = $config->get("paymillBrandIconMaestro") ? 'maestro' : '';
        $result[] = $config->get("paymillBrandIconMastercard") ? 'mastercard' : '';
        $result[] = $config->get("paymillBrandIconUnionpay") ? 'china-unionpay' : '';
        $result[] = $config->get("paymillBrandIconVisa") ? 'visa' : '';

        $arrayLength = count(array_diff($shouldBe, $result));
        return ($arrayLength === 0 || $arrayLength === 11) ? $shouldBe : $result;
    }

    /**
     * Returns the version
     *
     * @return string
     */
    public function getVersion()
    {
        return "1.3.1";
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
        return array('version' => $this->getVersion(),
            'author' => 'PayIntelligent GmbH',
            'source' => $this->getSource(),
            'supplier' => 'PAYMILL GmbH',
            'support' => 'support@paymill.com',
            'link' => 'https://www.paymill.com',
            'copyright' => 'Copyright (c) 2013, PayIntelligent GmbH',
            'label' => 'Paymill',
            'description' => '<h2>Payment plugin for Shopware Community Edition Version 4.0.0 - 4.1.3</h2>'
            . '<ul>'
            . '<li style="list-style: inherit;">PCI DSS compatibility</li>'
            . '<li style="list-style: inherit;">Payment means: Credit Card (Visa, Visa Electron, Mastercard, Maestro, Diners, Discover, JCB, AMEX, China Union Pay), Direct Debit (ELV)</li>'
            . '<li style="list-style: inherit;">Refunds can be created from an additional tab in the order detail view</li>'
            . '<li style="list-style: inherit;">Optional configuration for authorization and manual capture with credit card payments</li>'
            . '<li style="list-style: inherit;">Optional fast checkout configuration allowing your customers not to enter their payment detail over and over during checkout</li>'
            . '<li style="list-style: inherit;">Improved payment form with visual feedback for your customers</li>'
            . '<li style="list-style: inherit;">Supported Languages: German, English, Italian, French, Spanish, Portuguese</li>'
            . '<li style="list-style: inherit;">Backend Log with custom View accessible from your shop backend</li>'
            . '</ul>'
        );
    }

    /**
     * Eventhandler for the update of the client with new data on email change
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
     * Fixes a known issue.
     *
     * @throws Exception
     */
    private function solveKnownIssue()
    {
        try {
            //Deleting translation for mainshop which causes in not be able to change it via backend
            Shopware()->Db()->delete('s_core_translations', Shopware()->Db()->quoteInto('objecttype = ?', "config_payment")
                . ' AND ' . Shopware()->Db()->quoteInto('objectkey = ?', 1)
                . ' AND ' . Shopware()->Db()->quoteInto('objectlanguage = ?', "1")
            );
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
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
            $this->createPaymentMeans();
            $this->_createForm();
            $this->_addTranslationSnippets();
            $this->_createEvents();
            $this->_applyBackendViewModifications();
            $this->_translatePaymentNames();
            $translationHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_TranslationHelper($this->Form());
            $translationHelper->createPluginConfigTranslation();
            $this->solveKnownIssue();
        } catch (Exception $exception) {
            $this->uninstall();
            throw new Exception($exception->getMessage());
        }

        $installSuccess = parent::install();
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
     * Adds Sepa-Information to Invoice
     *
     * @param Enlight_Hook_HookArgs $arguments
     */
    public function beforeCreatingDocument(Enlight_Hook_HookArgs $arguments)
    {
        $document = $arguments->getSubject();
        $view = $document->_view;
        if ($document->_order->payment['name'] != 'paymilldebit') {
            return;
        }

        $document->_template->addTemplateDir(dirname(__FILE__) . '/Views/');
        $containerData = $view->getTemplateVars('Containers');
        $containerData['Content_Info']['value'] .= 'PLACEHOLDER';
        $view->assign('Containers', $containerData);
    }

    /**
     * Performs the necessary uninstall steps
     *
     * @return boolean
     */
    public function uninstall()
    {
        $configHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ConfigHelper();
        $configHelper->persist();
        $translationHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_TranslationHelper(null);
        $translationHelper->dropSnippets();
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
        try {
            switch ($oldVersion) {
                default:
                    $updateSuccess = $this->uninstall();
                    $updateSuccess = $updateSuccess ? $this->install() : false;
                    return $updateSuccess;
            }
        } catch (Exception $exception) {
            Shopware()->Log()->Err($exception->getMessage());
            throw new Exception($exception->getMessage());
        }
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
            $ccPayment = $this->Payments()->findOneBy(array('name' => 'paymillcc'));
            $elvPayment = $this->Payments()->findOneBy(array('name' => 'paymilldebit'));

            $sortedSnippets = $this->getSortedSnippets();
            $shops = Shopware()->Db()->select()
                ->from('s_core_shops', array('id', 'locale_id', 'default'))
                ->query()
                ->fetchAll();

            foreach ($shops as $shop) {
                $shopId = $shop['id'];
                $locale = $shop['locale_id'];
                $this->updatePaymentTranslation($shopId, $ccPayment->getID(), $sortedSnippets[$locale]['frontend_creditcard'], $shop['default']);
                $this->updatePaymentTranslation($shopId, $elvPayment->getID(), $sortedSnippets[$locale]['frontend_directdebit'], $shop['default']);
            }
        } catch (Exception $exception) {
            Shopware()->Log()->Err("Can not create translation for payment names." . $exception->getMessage());
            throw new Exception("Can not create translation for payment names." . $exception->getMessage());
        }
    }

    /**
     * Update the translation of a payment
     *
     * @param integer $shopId
     * @param integer $paymentId
     * @param string $description
     * @param integer $default
     */
    private function updatePaymentTranslation($shopId, $paymentId, $description, $default)
    {
        if ($default) {
            Shopware()->Db()->update('s_core_paymentmeans', array(
                'description' => $description
                ), 'id=' . $paymentId
            );
        } else {
            $translationObject = new Shopware_Components_Translation();
            $translationObject->write(
                $shopId, "config_payment", $paymentId, array('description' => $description), true
            );
        }
    }

    /**
     * Returns a sorted array of all snippets
     *
     * @return array
     */
    private function getSortedSnippets()
    {
        $snippets = Shopware()->Db()->select()
            ->from('s_core_snippets', array('localeID', 'name', 'value'))
            ->where('namespace=?', "Paymill")
            ->where('name=?', "frontend_creditcard")
            ->orWhere('name=?', "frontend_directdebit")
            ->group(array('localeID', 'value'))
            ->query()
            ->fetchAll();

        $sortedSnippets = array();
        foreach ($snippets as $snippet) {
            $sortedSnippets[$snippet['localeID']][$snippet['name']] = $snippet['value'];
        }
        return $sortedSnippets;
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
        try {
            $this->createPayment(
                array(
                    'active' => 0,
                    'name' => 'paymillcc',
                    'action' => 'payment_paymill',
                    'template' => 'paymill.tpl',
                    'description' => 'Kreditkartenzahlung',
                    'additionalDescription' => ''
                )
            );

            $this->createPayment(
                array(
                    'active' => 0,
                    'name' => 'paymilldebit',
                    'action' => 'payment_paymill',
                    'template' => 'paymill.tpl',
                    'description' => 'ELV',
                    'additionalDescription' => ''
                )
            );
        } catch (Exception $exception) {
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

        try {
            $form = $this->Form();
            $configHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ConfigHelper();
            $data = $configHelper->loadData();

            $sql = "DELETE FROM s_core_config_element_translations
               WHERE element_id IN (SELECT s_core_config_elements.id FROM s_core_config_elements
               WHERE s_core_config_elements.form_id = (SELECT s_core_config_forms.id FROM s_core_config_forms
               WHERE s_core_config_forms.plugin_id = ?));
               DELETE FROM s_core_config_elements
               WHERE form_id = (SELECT id FROM s_core_config_forms WHERE plugin_id = ?);";
            Shopware()->Db()->query($sql, array($this->getId(), $this->getId()));

            $form->setElement('text', 'publicKey', array('label' => 'Public Key', 'required' => true, 'value' => $data['publicKey']));
            $form->setElement('text', 'privateKey', array('label' => 'Private Key', 'required' => true, 'value' => $data['privateKey']));
            $form->setElement('number', 'paymillSepaDate', array('label' => 'SEPA Datum', 'required' => true, 'value' => 7, 'attributes' => array('minValue' => 0)));
            $form->setElement('checkbox', 'paymillPreAuth', array('label' => 'Authorize credit card transactions during checkout and capture manually', 'value' => $data['paymillPreAuth'] == 1));
            $form->setElement('checkbox', 'paymillDebugging', array('label' => 'Activate debugging', 'value' => $data['paymillDebugging'] == 1));
            $form->setElement('checkbox', 'paymillFastCheckout', array('label' => 'Save data for FastCheckout', 'value' => $data['paymillFastCheckout'] == 1));
            $form->setElement('checkbox', 'paymillLogging', array('label' => 'Activate logging', 'value' => $data['paymillLogging'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconAmex', array('label' => 'American Express', 'value' => $data['paymillBrandIconAmex'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconCartaSi', array('label' => 'Carta Si', 'value' => $data['paymillBrandIconCartaSi'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconCarteBleue', array('label' => 'Carte Bleue', 'value' => $data['paymillBrandIconCarteBleue'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconDankort', array('label' => 'Dankort', 'value' => $data['paymillBrandIconDankort'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconDinersclub', array('label' => 'Dinersclub', 'value' => $data['paymillBrandIconDinersclub'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconDiscover', array('label' => 'Discover', 'value' => $data['paymillBrandIconDiscover'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconJcb', array('label' => 'JCB', 'value' => $data['paymillBrandIconJcb'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconMaestro', array('label' => 'Maestro', 'value' => $data['paymillBrandIconMaestro'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconMastercard', array('label' => 'Mastercard', 'value' => $data['paymillBrandIconMastercard'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconUnionpay', array('label' => 'China Unionpay', 'value' => $data['paymillBrandIconUnionpay'] == 1));
            $form->setElement('checkbox', 'paymillBrandIconVisa', array('label' => 'Visa', 'value' => $data['paymillBrandIconVisa'] == 1));
        } catch (Exception $exception) {
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
        try {
            $this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
            $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPaymill', 'onGetControllerPath');
            $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Checkout', 'onCheckoutConfirm');
            $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_PaymillLogging', 'paymillBackendControllerLogging');
            $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_PaymillOrderOperations', 'paymillBackendControllerOperations');
            $this->subscribeEvent('Shopware_Modules_Admin_UpdateAccount_FilterEmailSql', 'onUpdateCustomerEmail');
            $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Backend_Order', 'extendOrderDetailView');
            $this->subscribeEvent('Shopware_Components_Document::assignValues::after', 'beforeCreatingDocument');
        } catch (Exception $exception) {
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
            $this->createMenuItem(array('label' => 'Paymill', 'class' => 'sprite-cards-stack', 'active' => 1,
                'controller' => 'PaymillLogging', 'action' => 'index', 'parent' => $parent));
        } catch (Exception $exception) {
            Shopware()->Log()->Err("can not create menu entry." . $exception->getMessage());
            throw new Exception("can not create menu entry." . $exception->getMessage());
        }
    }

}

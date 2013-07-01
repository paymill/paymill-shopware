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
        return "1.0.3";
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
     * Performs the necessary uninstallation steps
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
     * @param string $oldversion
     */
    public function update($oldversion)
    {
        $result = false;
        switch ($oldversion) {
            case '1.0.0':
            case '1.0.1':
            case '1.0.2':
                $result = $this->addTranslationSnippets();
        }
        return $result;
    }

    /**
     * Creates the Translation for the Pluginconfiguration
     */
    private function _createPluginConfigTranslation()
    {
        try {
            $form = $this->Form();
            $translations = array(
                'de_DE' => array(
                    'publicKey' => 'Public Key',
                    'privateKey' => 'Privat Key',
                    'paymillDebugging' => 'Debugging aktivieren',
                    'paymillFastCheckout' => 'Daten für Fast Checkout speichern',
                    'paymillLogging' => 'Logging aktivieren',
                    'paymillShowLabel' => 'Paymill Label anzeigen'
                ),
                'en_GB' => array(
                    'publicKey' => 'Public Key',
                    'privateKey' => 'Private Key',
                    'paymillDebugging' => 'Activate debugging',
                    'paymillFastCheckout' => 'Save data for FastCheckout',
                    'paymillLogging' => 'Activate logging',
                    'paymillShowLabel' => 'Show Paymill-label'
                )
            );

            $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
            foreach ($translations as $locale => $snippets) {
                $localeModel = $shopRepository->findOneBy(array(
                    'locale' => $locale
                        ));
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

    private function addTranslationSnippets()
    {
        $sql_shop_ids = "SELECT `id` FROM `s_core_shops` WHERE `locale_id`= 2";
        $sql_snippets = "REPLACE INTO `s_core_snippets` (`namespace`, `name`, `value`, `localeID`, `shopID`,`created`, `updated`) VALUES "
                . "('Paymill', 'version', 'Version', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'date', 'Date', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'devinfo', 'Developerinformation', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'additionaldevinfo', 'Additional Developerinformation', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'action', 'Action', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'merchantinfo', 'Merchantinfo', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'invalid_cardnumber', 'Please enter a valid creditcardnumber.', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'invalid_cvc', 'Please enter a valid securecode (see back of creditcard).', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'invalid_expirydate', 'The expirydate is invalid.', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'invalid_accountholder', 'Please enter the cardholders name.', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'invalid_accountnumber', 'Please enter a valid accountnumber.', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'invalid_bankcode', 'Please a valid bankcode.', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'invalid_error_creditcard', 'Please enter your creditcarddata. For security reason we will not save them on our system.', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'invalid_error_debit', 'Please enter your accountdata. For security reason we will not save them on our system.', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_cardholder', 'Cardholder *', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_cardnumber', 'Cardnumber *', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_cvc', 'CVC *', '2', '%SHOPID%', '2013-06-25 17:29:18', NOW()), "
                . "('Paymill', 'form_expirydate', 'Valid until (MM/YYYY) *', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_accountholder', 'Accountholder *', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_accountnumber', 'Accountnumber *', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_bankcode', 'Bankcode *', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_info', 'Fields marked with a * are required.', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_paymilllabel_cc', 'Secure creditcardpayment powered by', '2', '%SHOPID%', NOW(), NOW()), "
                . "('Paymill', 'form_paymilllabel_debit', 'Secure directdebitpayment powered by', '2', '%SHOPID%', NOW(), NOW());";
        try {
            $shopIDs = Shopware()->Db()->fetchAll($sql_shop_ids);
            $sql = "";
            foreach ($shopIDs as $row) {
                $sql .= preg_replace("/%SHOPID%/", $row['id'], $sql_snippets);
            }
            Shopware()->Db()->exec($sql);
            return true;
        } catch (Exception $exception) {
            $this->uninstall();
            throw new Exception("Can not insert translation-snippets." . $exception->getMessage());
        }
    }

    /**
     * Enables the plugin
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
        return array('install' => true, 'update' => false, 'enable' => true);
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
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . '/Views/');
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

        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . '/Views/');

        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
            return;
        }

        // if there is a token in the request save it for later use
        if ($request->get("paymillToken")) {
            Shopware()->Session()->paymillTransactionToken = $request->get("paymillToken");
        }

        if (self::isPaymillPayment()) {
            $view->sRegisterFinished = 'false';
            if (self::isFcReady()) {
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
            if (self::isFcReady()) {
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

    /**
     * Checks if there is saved Data for FastCheckout associated with the current user
     * @param   String    $paymentNameArg
     * @return  boolean success
     */
    public static function isFcReady($paymentNameArg = null)
    {
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        if (in_array($user['additional']['payment']['name'], array("paymillcc", "paymilldebit"))) {
            $userId = $user['billingaddress']['userID'];
            if ($paymentNameArg === null) {
                $paymentName = $user['additional']['payment']['name'];
            } else {
                $paymentName = $paymentNameArg;
            }

            $payment = $paymentName == 'paymillcc' ? 'ccPaymentId' : 'elvPaymentId';
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
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatch');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPaymill', 'onGetControllerPath');
        $this->subscribeEvent('Enlight_Controller_Action_PreDispatch_Frontend_Checkout', 'onCheckoutConfirm');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_PaymillLogging', 'paymillBackendControllerLogging');
    }

    /**
     * Modifies the Backendmenu by adding a PaymillLogging Label as a child element of the shopware logging
     * @throws Exception "can not create menuentry"
     */
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

    /**
     * Return the path of the backendcontroller
     * @return String backend controller path
     */
    public function paymillBackendControllerLogging()
    {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . 'Views/');
        return Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Path() . "/Controllers/backend/PaymillLogging.php";
    }

    /**
     * Saves the Amount to the session, passes it to the current Template
     * @param Enlight_Event_EventArgs $arguments
     */
    private function saveAmount(Enlight_Event_EventArgs $arguments)
    {
        //Save amount into session to allow 3Ds
        $basket = Shopware()->Session()->sOrderVariables['sBasket'];
        $totalAmount = (round((float) $basket['sAmount'] * 100, 2));

        Shopware()->Session()->paymillTotalAmount = $totalAmount;
        $arguments->getSubject()->View()->Template()->assign("tokenAmount", $totalAmount);
    }

}
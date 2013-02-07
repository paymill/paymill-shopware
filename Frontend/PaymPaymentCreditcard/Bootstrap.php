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
        return "1.0.1";
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

        $this->createPayments();

        $this->createForm();

        $hook = $this->createHook(
                'Shopware_Controllers_Frontend_Account', 'paymentAction', 'onpaymentAction', Enlight_Hook_HookHandler::TypeAfter, 0
        );
        $this->subscribeHook($hook);

        $this->subscribeEvent(
                'Enlight_Controller_Action_PostDispatch', 'onPostDispatch'
        );
        $this->subscribeEvent(
                'Enlight_Controller_Dispatcher_ControllerPath_Frontend_PaymentPaymill', 'onGetControllerPath'
        );
        $this->subscribeEvent(
                'Enlight_Controller_Action_PreDispatch_Frontend_Checkout', 'onCheckoutConfirm'
        );
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
        return parent::enable();
    }

    /**
     * Disables the plugin
     * @return boolean
     */
    public function disable()
    {
        return parent::disable();
    }

    /**
     * Creates the payment method
     * @return void
     */
    protected function createPayments()
    {
        Shopware()->Payments()->createRow(
                array(
                    'name' => 'paymillcc',
                    'description' => 'Kreditkartenzahlung',
                    'action' => 'payment_paymill',
                    'active' => 1,
                    'template' => 'paymillcc.tpl',
                    'pluginID' => $this->getId()
                )
        )->save();
        Shopware()->Payments()->createRow(
                array(
                    'name' => 'paymilldebit',
                    'description' => 'Lastschrift',
                    'action' => 'payment_paymill',
                    'active' => 1,
                    'template' => 'paymilldebit.tpl',
                    'pluginID' => $this->getId()
                )
        )->save();
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

        $form->setElement('text', 'bridgeUrl', array(
            'label' => 'Bridge URL',
            'required' => true,
            'value' => 'https://bridge.paymill.de/'
        ));

        $form->setElement('text', 'apiUrl', array(
            'label' => 'API URL',
            'required' => true,
            'value' => 'https://api.paymill.de/v2/'
        ));

        $form->setElement('checkbox', 'paymillDebugging', array(
            'label' => 'Debugging aktivieren',
            'value' => '0'
        ));

        $form->setElement('checkbox', 'paymillLogging', array(
            'label' => 'Logging aktivieren',
            'value' => '0'
        ));

        $form->setElement('checkbox', 'paymillShowLabel', array(
            'label' => 'Paymill Label anzeigen',
            'value' => '0'
        ));

        $form->save();
    }

    /**
     * Returns the controller path
     * @return string
     */
    public static function onGetControllerPath(Enlight_Event_EventArgs $args)
    {
        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
        return dirname(__FILE__) . '/Controllers/frontend/Paymill.php';
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

        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');

        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() != 'frontend' || !$view->hasTemplate()) {
            return;
        }

        if ($request->get("controller") == "checkout" && self::isPaymillPayment()) {
            $view->sRegisterFinished = "false";
        }

        // if there is a token in the request save it for later use
        if ($request->get("paymillToken")) {
            Shopware()->Session()->paymillTransactionToken = $request->get("paymillToken");
            self::logAction("Token " . $request->get("paymillToken") . " stored to session");
        }
    }

    /**
     * Payment action that is triggered on a payment update
     * @return void
     */
    public static function onpaymentAction(Enlight_Event_EventArgs $args)
    {
        $config = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $args->getSubject()->View()->publicKey = $config->publicKey;
        $args->getSubject()->View()->bridgeUrl = $config->bridgeUrl;
        $args->getSubject()->View()->paymillError = $args->getSubject()->Request()->paymill_error;
        $args->getSubject()->View()->paymillShowLabel = $config->paymillShowLabel;
    }

    /**
     * Extends the confirmationpage with an Errorbox, if there is an error.
     *
     * @param Enlight_Event_EventArgs $arguments
     * @return null
     */
    public function onCheckoutConfirm(Enlight_Event_EventArgs $arguments)
    {
        $params = $arguments->getRequest()->getParams();
        $arguments->getSubject()->View()->debug = $this->Config()->paymillDebugging;
        if ($arguments->getRequest()->getActionName() !== 'confirm' && !isset($params["errorMessage"])) {
            return;
        }
        $pigmbhErrorMessage = Shopware()->Session()->pigmbhErrorMessage;
        unset(Shopware()->Session()->pigmbhErrorMessage);
        $view = $arguments->getSubject()->View();
        $content = '{if $pigmbhErrorMessage}' .
                '<div class="grid_20">' .
                '<div class="error">' .
                '<div class="center">' .
                '<strong>' .
                '{$pigmbhErrorMessage}' .
                '</strong>' .
                '</div>' .
                '</div>' .
                '</div>' .
                '{/if}';
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
     * Logger for events
     * @return void
     */
    public static function logAction($message)
    {
        $logfile = dirname(__FILE__) . '/log.txt';
        if (is_writable($logfile) && Shopware()->Config()->get("paymillLogging")) {
            $handle = fopen($logfile, 'a'); //
            fwrite($handle, "[" . date(DATE_RFC822) . "] " . $message . "\n");
            fclose($handle);
        }
    }
}
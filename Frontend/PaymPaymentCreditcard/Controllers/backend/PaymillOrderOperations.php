<?php
/**
 * Paymill Order Operations
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     PayIntelligent
 */
class Shopware_Controllers_Backend_PaymillOrderOperations extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Action Listener to determine if the Paymill Order Operations Tab will be displayed
     */
    public function displayTabAction()
    {
        $orderId = $this->Request()->getParam("orderId");
        $result = false;
        if (isset($orderId)) {
            //Determine if order can be captured
            $result = true;
        }

        $this->View()->assign(array('success' => $result));
    }

    /**
     * Action Listener to determine if an order is applicable for capture
     */
    public function canCaptureAction()
    {
        $success = false;
        $orderId = $this->Request()->getParam("orderId");
        $model = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneById($orderId);
        if (!function_exists($model->getAttribute()->getPaymillPreAuthorization)) {
            if ($model->getAttribute()->getPaymillPreAuthorization() !== null) {
                $success = true;
            }
        }

        $this->View()->assign(array('success' => $success));
    }

    /**
     * Action Listener to execute the capture for applicable transactions
     *
     * @todo Add translations and exception handling for different cases
     */
    public function captureAction()
    {
        $result = false;
        require_once dirname(__FILE__) . '/../../lib/Services/Paymill/Preauthorizations.php';
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();

        //Gather Data
        $orderId = $this->Request()->getParam("orderId");
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";

        $model = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneById($orderId);
        $preAuthId = $model->getAttribute()->getPaymillPreAuthorization();

        $preAuthObject = new Services_Paymill_Preauthorizations($privateKey, $apiUrl);
        $preAuthObject = $preAuthObject->getOne($preAuthId);

        $description = $preAuthObject['client']['email'] . " " . Shopware()->Config()->get('shopname');
        $amount = $preAuthObject['amount'];
        $currency = $preAuthObject['currency'];

        //Create Transaction
        $parameter = array("amount" => $amount, "currency" => $currency, "description" => $description);

        $paymentProcessor = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_PaymentProcessor($parameter);
        $paymentProcessor->setPreauthId($preAuthId);

        try {
            $result = $paymentProcessor->capture();
            if ($result) {
                $messageText = "Capture has been successful.";
            } else {
                $messageText = "Capture failed.";
            }
        } catch (Exception $exception) {
            $messageText = $exception->getMessage();
        }

        $this->View()->assign(array('success' => $result, 'messageText' => $messageText));
    }
}
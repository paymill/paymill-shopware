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
        $orderId = $this->Request()->getParam("orderId");
        $this->View()->assign(array('success' => true));
    }

    /**
     * Action Listener to execute the capture for applicable transactions
     */
    public function captureAction()
    {
        $orderId = $this->Request()->getParam("orderId");

        $result = true;

        $this->View()->assign(array('success' => $result, 'messageText' => 'test'));
    }
}
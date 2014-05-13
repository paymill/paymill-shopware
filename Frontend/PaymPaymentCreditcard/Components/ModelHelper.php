<?php

/**
 * The ModelHelper class summarizes all access points to the shopware models and secures the access.
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     Paymill
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper
{
    private $_loggingManager = null;

    /**
     * This method is meant to be called during the installation of the plugin to allow use of the Model Helper.
     *
     * @param Shopware_Components_Plugin_Bootstrap $bootstrap
     *
     * @throws Exception
     * @return void
     */
    public static function install($bootstrap)
    {
        try {
            $models = Shopware()->Models();
            Shopware()->Db()->query("DROP TABLE IF EXISTS `paymill_fastCheckout`;");

            //Add Order Properties
            $models->addAttribute('s_order_attributes', 'paymill', 'pre_authorization', 'varchar(255)');
            $models->addAttribute('s_order_attributes', 'paymill', 'transaction', 'varchar(255)');
            $models->addAttribute('s_order_attributes', 'paymill', 'refund', 'varchar(255)');
            $models->addAttribute('s_order_attributes', 'paymill', 'sepa_date', 'int(11)');

            //Add User Properties
            $models->addAttribute('s_user_attributes', 'paymill', 'client_id', 'varchar(255)');
            $models->addAttribute('s_user_attributes', 'paymill', 'payment_id_cc', 'varchar(255)');
            $models->addAttribute('s_user_attributes', 'paymill', 'payment_id_elv', 'varchar(255)');

            //Persist changes
            $bootstrap->Application()->Models()->generateAttributeModels(array('s_order_attributes'));
            $bootstrap->Application()->Models()->generateAttributeModels(array('s_user_attributes'));
        } catch (Exception $exception) {
            Shopware()->Log()->Err("Cannot edit shopware models. " . $exception->getMessage());
            throw new Exception("Cannot edit shopware models. " . $exception->getMessage());
        }
    }

    /**
     * Creates an instance of the modelHelper class
     */
    public function __construct()
    {
        $this->_loggingManager = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManager();
    }

    /**
     * Returns the transaction Id for the given order
     *
     * @param $orderNumber
     *
     * @return mixed
     */
    public function getPaymillTransactionId($orderNumber)
    {
        $orderId = $this->_getOrderIdByOrderNumber($orderNumber);
        $sql = "SELECT paymill_transaction
                FROM s_order_attributes a, s_order o
                WHERE o.id = a.orderID
                AND o.id = ?
                AND a.paymill_transaction IS NOT NULL";
        try {
            $transactionId = Shopware()->Db()->fetchOne($sql, array($orderId));
        } catch (Exception $exception) {
            $transactionId = "";
        }

        return $transactionId ? $transactionId : "";
    }

    /**
     * Returns the cancellation flag of the desired order
     *
     * @param String $orderNumber
     *
     * @return string
     */
    public function getPaymillRefund($orderNumber)
    {
        $orderId = $this->_getOrderIdByOrderNumber($orderNumber);
        $sql = "SELECT paymill_refund
                FROM s_order_attributes a, s_order o
                WHERE o.id = a.orderID
                AND o.id = ?
                AND a.paymill_refund IS NOT NULL";
        try {
            $refundId = Shopware()->Db()->fetchOne($sql, array($orderId));
        } catch (Exception $exception) {
            $refundId = "";
        }

        if($refundId === '0'){
            $refundId = "";
        }

        return $refundId ? $refundId : "";
    }

    /**
     * Returns the client id of the chosen user
     *
     * @param string $userId
     *
     * @return String
     */
    public function getPaymillClientId($userId)
    {
        $sql = "SELECT paymill_client_id
                FROM s_user_attributes a, s_user u
                WHERE u.id = a.userID
                AND u.id = ?
                AND a.paymill_client_id IS NOT NULL";

        try {
            require_once dirname(__FILE__) . '/../lib/Services/Paymill/Clients.php';
            $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
            $clientId = Shopware()->Db()->fetchOne($sql, array($userId));
            $client = new Services_Paymill_Clients(trim($swConfig->get("privateKey")), 'https://api.paymill.com/v2/');
            $clientData = $client->getOne($clientId);
            if (!isset($clientData['id'])) {
                $clientId = "";
                $this->setPaymillClientId($userId, "");
            }
        } catch (Exception $exception) {
            $clientId = "";
        }

        return $clientId ? $clientId : "";
    }

    /**
     * Returns the payment id of the chosen payment for the given user
     *
     * @param string $paymentShortTag Either "cc" or "elv" depending of the desired payment method
     * @param string $userId
     *
     * @return mixed
     */
    public function getPaymillPaymentId($paymentShortTag, $userId)
    {
        $sql = null;
        switch ($paymentShortTag) {
            case "cc":
            case "paymillcc":
                $sql = "SELECT paymill_payment_id_cc
                    FROM s_user_attributes a, s_user u
                    WHERE u.id = a.userID
                    AND u.id = ?
                    AND a.paymill_payment_id_cc IS NOT NULL";
                break;
            case "elv":
            case "paymilldebit":
                $sql = "SELECT paymill_payment_id_elv
                    FROM s_user_attributes a, s_user u
                    WHERE u.id = a.userID
                    AND u.id = ?
                    AND a.paymill_payment_id_elv IS NOT NULL";
                break;
        }

        try {
            require_once dirname(__FILE__) . '/../lib/Services/Paymill/Payments.php';
            $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();

            $paymentId = Shopware()->Db()->fetchOne($sql, array($userId));
            $payment = new Services_Paymill_Payments(trim($swConfig->get("privateKey")), 'https://api.paymill.com/v2/');
            $paymentData = $payment->getOne($paymentId);

            if (!isset($paymentData['id'])) {
                $paymentId = "";
            }

            if (isset($paymentData['client'])) {
                if ($paymentData['client'] !== $this->getPaymillClientId($userId)) {
                    $paymentId = "";
                }
            }
        } catch (Exception $exception) {
            $paymentId = "";
        }

        return $paymentId ? $paymentId : "";
    }

    /**
     * Returns the PreAuthorization Id for the given order
     *
     * @param string $orderNumber
     *
     * @return mixed
     */
    public function getPaymillPreAuthorization($orderNumber)
    {
        $orderId = $this->_getOrderIdByOrderNumber($orderNumber);
        $sql = "SELECT paymill_pre_authorization
                FROM s_order_attributes a, s_order o
                WHERE o.id = a.orderID
                AND o.id = ?
                AND a.paymill_pre_authorization IS NOT NULL";
        try {
            $preAuthorizationId = Shopware()->Db()->fetchOne($sql, array($orderId));
        } catch (Exception $exception) {
            $preAuthorizationId = "";
        }

        return $preAuthorizationId ? $preAuthorizationId : "";
    }

    /**
     * Sets the transaction Id for the given order
     *
     * @param      $orderNumber
     * @param      $transactionId
     *
     * @return bool
     */
    public function setPaymillTransactionId($orderNumber, $transactionId)
    {
        $orderId = $this->_getOrderIdByOrderNumber($orderNumber);
        $sql = "INSERT INTO s_order_attributes(orderID,paymill_transaction)
                VALUES ( ?, ?)
                ON DUPLICATE KEY
                UPDATE orderID = ?, paymill_transaction = ?";

        try {
            Shopware()->Db()->query($sql, array($orderId, $transactionId, $orderId, $transactionId));
            $result = true;
            $message = "Successfully saved Transaction Id for order $orderNumber";
        } catch (Exception $exception) {
            $result = false;
            $message = "Failed saving Transaction Id for order $orderNumber";
        }
        $this->_loggingManager->log($message, var_export($transactionId, true));

        return $result;
    }

    /**
     * Sets the payment cancelled flag for the given order. This flag is used to mark transaction completely refunded
     *
     * @param string $orderNumber
     * @param string $refundId
     *
     * @return bool
     */
    public function setPaymillRefund($orderNumber, $refundId)
    {
        $orderId = $this->_getOrderIdByOrderNumber($orderNumber);
        $sql = "INSERT INTO s_order_attributes(orderID,paymill_refund)
                VALUES ( ?, ?)
                ON DUPLICATE KEY
                UPDATE orderID = ?, paymill_refund = ?";

        try {
            Shopware()->Db()->query($sql, array($orderId, $refundId, $orderId, $refundId));
            $result = true;
            $message = "Successfully saved refund id for order $orderNumber";
        } catch (Exception $exception) {
            $result = false;
            $message = "Failed saving refund id for order $orderNumber";
        }
        $this->_loggingManager->log($message, var_export($refundId, true));

        return $result;
    }

    /**
     * Sets the PreAuthorization Id for the given order.
     *
     * @param      $orderNumber
     * @param      $paymillPreAuthorization
     *
     * @return bool
     */
    public function setPaymillPreAuthorization($orderNumber, $paymillPreAuthorization)
    {
        $orderId = $this->_getOrderIdByOrderNumber($orderNumber);
        $sql = "INSERT INTO s_order_attributes(orderID,paymill_pre_authorization)
                VALUES ( ?, ?)
                ON DUPLICATE KEY
                UPDATE orderID = ?, paymill_pre_authorization = ?";

        try {
            Shopware()->Db()->query($sql, array($orderId, $paymillPreAuthorization, $orderId,
                                                $paymillPreAuthorization));
            $result = true;
            $message = "Successfully saved PreAuthorization Id for order $orderNumber";
        } catch (Exception $exception) {
            $result = false;
            $message = "Failed saving PreAuthorization Id for order $orderNumber";
        }
        $this->_loggingManager->log($message, var_export($paymillPreAuthorization, true));

        return $result;
    }

    /**
     * Sets the payment Id for the given payment and user.
     *
     * @param $paymentShortTag
     * @param $userId
     * @param $paymillPaymentId
     *
     * @return bool
     */
    public function setPaymillPaymentId($paymentShortTag, $userId, $paymillPaymentId)
    {

        $sql = null;

        switch ($paymentShortTag) {
            case "cc":
            case "paymillcc":
                $sql = "INSERT INTO s_user_attributes(userID,paymill_payment_id_cc)
                VALUES ( ?, ?)
                ON DUPLICATE KEY
                UPDATE userID = ?, paymill_payment_id_cc = ?";
                $paymentName = "Credit Card";
                break;
            case "elv":
            case "paymilldebit":
                $sql = "INSERT INTO s_user_attributes(userID,paymill_payment_id_elv)
                VALUES ( ?, ?)
                ON DUPLICATE KEY
                UPDATE userID = ?, paymill_payment_id_elv = ?";
                $paymentName = "ELV";
                break;
            default:
                return false;
        }

        try {
            Shopware()->Db()->query($sql, array($userId, $paymillPaymentId, $userId, $paymillPaymentId));
            $result = true;
            $message = "Successfully saved $paymentName Transaction Id for user $userId";
        } catch (Exception $exception) {
            $result = false;
            $message = "Failed saving $paymentName Transaction Id for user $userId";
        }
        $this->_loggingManager->log($message, var_export($paymillPaymentId, true));

        return $result;
    }

    /**
     * Saves the customers client id into the user model
     *
     * @param $userId
     * @param $paymillClientId
     *
     * @return bool
     */
    public function setPaymillClientId($userId, $paymillClientId)
    {
        $sql = "INSERT INTO s_user_attributes(userID,paymill_client_id)
                VALUES ( ?, ?)
                ON DUPLICATE KEY
                UPDATE userID = ?, paymill_client_id = ?";

        try {
            Shopware()->Db()->query($sql, array($userId, $paymillClientId, $userId, $paymillClientId));
            $result = true;
            $message = "Successfully saved client id for user $userId";
        } catch (Exception $exception) {
            $result = false;
            $message = "Failed saving client id for user $userId";
        }
        $this->_loggingManager->log($message, var_export($paymillClientId, true));

        return $result;
    }

    /**
     * Returns the OrderId associated with the given Number
     *
     * @param $orderNumber
     *
     * @return string
     */
    private function _getOrderIdByOrderNumber($orderNumber)
    {
        return Shopware()->Db()->fetchOne("SELECT id FROM s_order WHERE ordernumber = ?", array($orderNumber));
    }

    /**
     * Returns the OrderNumber associated with the given Id
     *
     * @param $orderId
     *
     * @return string
     */
    public function getOrderNumberById($orderId)
    {
        return Shopware()->Db()->fetchOne("SELECT ordernumber FROM s_order WHERE id = ?", array($orderId));
    }

    /**
     * Returns the state of the cancellation flag removed in patch 1.2.0
     * It is currently used for update compatibility and may get removed in the future
     *
     * @param $orderNumber
     *
     * @return bool
     */
    public function getPaymillCancelled($orderNumber)
    {
        $orderId = $this->_getOrderIdByOrderNumber($orderNumber);
        $preCheckSql = "SHOW COLUMNS FROM `s_order_attributes` LIKE 'paymill_cancelled'";
        $executeQuery = Shopware()->Db()->fetchOne($preCheckSql);
        if (isset($executeQuery)) {
            $sql = "SELECT paymill_cancelled
                FROM s_order_attributes a, s_order o
                WHERE o.id = a.orderID
                      AND o.id = ?
                AND a.paymill_cancelled IS NOT NULL";
            try {
                $hasBeenCancelled = Shopware()->Db()->fetchOne($sql, array($orderId));
            } catch (Exception $exception) {
                $hasBeenCancelled = null;
            }
        } else {
            $hasBeenCancelled = null;
        }

        return $hasBeenCancelled === '1';
    }
}
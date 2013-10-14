<?php

/**
 * The FastCheckoutHelper class implements all methods required for the fast checkout.
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     Paymill
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_FastCheckoutHelper
{
    private $_userId = null;

    private $_paymentName = null;

    public $clientId = null;

    public $paymentId = null;

    /**
     * This method is meant to be called during the installation of the plugin to allow use of the FastCheckout Helper.
     *
     * @throws Exception "Can not create FastCheckout Table"
     */
    public static function install()
    {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `paymill_fastCheckout` (" .
                   "`id` int(11) NOT NULL AUTO_INCREMENT," .
                   "`userId` varchar(250) COLLATE utf8_unicode_ci NOT NULL," .
                   "`clientId` varchar(250) COLLATE utf8_unicode_ci NOT NULL," .
                   "`ccPaymentId` varchar(250) COLLATE utf8_unicode_ci NULL," .
                   "`elvPaymentId` varchar(250) COLLATE utf8_unicode_ci NULL," .
                   "PRIMARY KEY (`id`)," .
                   "UNIQUE KEY `userId` (`userId`)" .
                   ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1";
            Shopware()->Db()->query($sql);
        } catch (Exception $exception) {
            Shopware()->Log()->Err("Can not create FastCheckout Table. " . $exception->getMessage());
            throw new Exception("Can not create FastCheckout Table. " . $exception->getMessage());
        }
    }

    /**Sets the Id used to identify the current user.
     *
     * @param String $arg UserId of the current customer.
     */
    public function setUserId($arg)
    {
        $this->_userId = $arg;
    }

    /**Sets the Payment name used to Identify the current payment. Can be either cc or elv*/
    public function setPaymentName($arg)
    {
        $this->_paymentName = $arg;
    }

    /**
     * Returns a boolean describing if there is FCData for the current Case.
     * Also Prepares the Ids to be read from the public vars.
     *
     * @return boolean Does an entry for the current user and payment exist in the fast checkout table?
     */
    public function entryExists()
    {
        $hasClientId = $this->loadClientId();
        $hasPaymentId = $this->loadPaymentId();

        return $hasClientId && $hasPaymentId;
    }

    /**
     * Returns true id there already is a ClientId for the current user
     *
     * @return Boolean
     */
    public function hasClientId()
    {
        $hasClientId = $this->loadClientId();

        return $hasClientId;
    }

    /**
     * Returns whether there is data for a fast checkout with the given payment or not.
     * If no Payment is given the current payment is being used.
     * @param String|null $paymentNameArg (paymillcc or paymilldebit)
     *
     * @return bool
     */
    public function isFcReady($paymentNameArg = null)
    {
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        $paymentName = $paymentNameArg === null ? $user['additional']['payment']['name']: $paymentNameArg;
        $userId = $user['billingaddress']['userID'];

        if (in_array($paymentName, array("paymillcc", "paymilldebit"))) {
            $payment = $paymentName === 'paymillcc' ? 'ccPaymentId' : 'elvPaymentId';
            $sql = "SELECT count(`$payment`) FROM `paymill_fastCheckout` WHERE `userId` = $userId AND `$payment` IS NOT null";
            try{
                $fcEnabled = Shopware()->Db()->fetchOne($sql);
            } catch (Exception $exception){
                $fcEnabled = 0;
            }

            return $fcEnabled == 1;
        }

        return false;
    }

    public function assignDisplayData($view)
    {
        require_once dirname(__FILE__) . '/../lib/Services/Paymill/Payments.php';
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $privateKey = trim($swConfig->get("privateKey"));
        $apiUrl = "https://api.paymill.com/v2/";

        if ($this->isFcReady("paymillcc")) {
            $this->loadPaymentId();
            $paymentId = $this->paymentId;
            $ccPayment = new Services_Paymill_Payments($privateKey, $apiUrl);
            $paymentObject = $ccPayment->getOne($paymentId);
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


        if ($this->isFcReady("paymilldebit")) {
            $this->setPaymentName('elv');
            $this->loadPaymentId();
            $paymentId = $this->paymentId;
            $elvPayment = new Services_Paymill_Payments($privateKey, $apiUrl);
            $paymentObject = $elvPayment->getOne($paymentId);
            $view->paymillAccountNumber = $paymentObject['account'];
            $view->paymillBankCode = $paymentObject['code'];
        } else {
            $view->paymillAccountNumber = "";
            $view->paymillBankCode = "";
        }
    }

    /**
     * Loads the clientId associated with the current userId from the fc - Table and saves it in the clientId class property.
     * Returns an indicator of success.
     *
     * @return boolean success
     */
    public function loadClientId()
    {
        try {

            $sql = "SELECT count(`clientID`) FROM `paymill_fastCheckout` WHERE `userID`= ? ;";
            $hasId = Shopware()->Db()->fetchOne($sql, array($this->_userId));
            if ($hasId) {
                $sql = "SELECT `clientID` FROM `paymill_fastCheckout` WHERE `userID`= ? ;";
                $this->clientId = Shopware()->Db()->fetchOne($sql, array($this->_userId));
            } else {
                throw new Exception();
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Loads the paymentId associated with the current userId from the fc - Table and saves it in the paymentId class property.
     * Returns an indicator of success.
     *
     * @return boolean success
     */
    public function loadPaymentId()
    {
        try {
            $fieldName = $this->_paymentName == "cc" ? "ccPaymentId" : "elvPaymentId";
            $sql = "SELECT count(`$fieldName`) FROM `paymill_fastCheckout` WHERE `userID`= ? ;";
            $hasId = Shopware()->Db()->fetchOne($sql, array($this->_userId));
            if ($hasId) {
                $sql = "SELECT `$fieldName` FROM `paymill_fastCheckout` WHERE `userID`= ? ;";
                $this->paymentId = Shopware()->Db()->fetchOne($sql, array($this->_userId));
            } else {
                throw new Exception();
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Checks if there is a clientId saved for the current userId.
     * If there is none, the clientId passed as the argument will be saved in the table.
     *
     * @param String $arg
     *
     * @return boolean success
     */
    public function saveClientId($arg)
    {
        if (!$this->hasClientId()) {
            $insertSQL = "INSERT INTO `paymill_fastCheckout`(`userID`,`clientID`) VALUES( ?, ?);";
            try {
                Shopware()->Db()->query($insertSQL, array($this->_userId, $arg));
            } catch (Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if there is a paymentId saved for the current userId.
     * If there is none, the paymentId passed as the argument will be saved in the table.
     *
     * @param String $arg
     *
     * @return boolean success
     */
    public function savePaymentId($arg)
    {
        $paymentIdName = $this->_paymentName == "cc" ? "ccPaymentId" : "elvPaymentId";
        $insertSQL = "UPDATE `paymill_fastCheckout` SET `$paymentIdName` = ? WHERE `userID` = ?;";
        try {
            Shopware()->Db()->query($insertSQL, array($arg, $this->_userId));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Creates an object of the FastCheckoutHelper class.
     *
     * @param string $userId            Make sure this is the Id of the current user. Any operations of this class base on this ID
     * @param string $paymentName       Make sure this the paymentCode of the current payment. Otherwise payment data might get lost due to overwriting.
     */
    public function __construct($userId = null, $paymentName = null)
    {
        $user = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
        $this->_userId = $userId === null ?  $user['additional']['user']['id']: $userId;
        $this->_paymentName = $paymentName === null ? $user['additional']['payment']['name']: $paymentName;
    }
}
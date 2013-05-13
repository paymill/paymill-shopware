<?php
/**
 * FastCheckoutHelper
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     Paymill
 */
require_once dirname(__FILE__) . '/Parents/FastCheckoutHelperAbstract.php';
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_FastCheckoutHelper extends FastCheckoutHelperAbstract
{
    public static function install()
    {
        try {
            $sql = parent::getCreateSQL();
            Shopware()->Db()->query($sql);
        }catch (Exception $exception) {
            Shopware()->Log()->Err("Can not create FastCheckout Table. ". $exception->getMessage());
            throw new Exception("Can not create FastCheckout Table. ". $exception->getMessage());
        }
    }

    public function loadClientId()
    {
        try{
            
            $sql = "SELECT count(`clientID`) FROM `paymill_fastCheckout` WHERE `userID`= ? ;";
            $hasId = Shopware()->Db()->fetchOne($sql, array( $this->userId ));
            if($hasId){
                $sql = "SELECT `clientID` FROM `paymill_fastCheckout` WHERE `userID`= ? ;";
                $this->clientId = Shopware()->Db()->fetchOne($sql, array( $this->userId ));
            }else{
                throw new Exception();
            }
        }catch(Exception $e){
            return false;
        }
        return true;
    }

    public function loadPaymentId()
    {
        try{
            $fieldName = $this->paymentName == "cc" ? "ccPaymentId": "elvPaymentId";
            $sql = "SELECT count(`$fieldName`) FROM `paymill_fastCheckout` WHERE `userID`= ? ;";
            $hasId = Shopware()->Db()->fetchOne($sql, array( $this->userId ));
            if($hasId){
                $sql = "SELECT `$fieldName` FROM `paymill_fastCheckout` WHERE `userID`= ? ;";
                $this->paymentId = Shopware()->Db()->fetchOne($sql, array( $this->userId ));
            }else{
                throw new Exception();
            }
        }catch(Exception $e){
            return false;
        }
        return true;
    }

    public function saveClientId($arg)
    {
        if(!$this->hasClientId()){
            $insertSQL = "INSERT INTO `paymill_fastCheckout`(`userID`,`clientID`) VALUES( ?, ?);";
            try{
                Shopware()->Db()->query($insertSQL, array($this->userId, $arg));
            }catch(Exception $e){
                return false;
            }    
        }
        return true; 
    }

    public function savePaymentId($arg)
    {
        $paymentIdName = $this->paymentName == "cc" ? "ccPaymentId": "elvPaymentId";
        $insertSQL = "UPDATE `paymill_fastCheckout` SET `$paymentIdName` = ? WHERE `userID` = ?;";
            try{
                Shopware()->Db()->query($insertSQL, array($arg, $this->userId));
            }catch(Exception $e){
                return false;
            }    
        return true; 
    }

    public function __construct($userId, $paymentName)
    {
        $this->userId = $userId;
        $this->paymentName = $paymentName;
    }

}
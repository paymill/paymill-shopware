<?php
/**
 * Paymill FastCheckoutHelper
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
abstract class FastCheckoutHelperAbstract{
    //---------------Variables------------------------
    protected $userId      = null;
    protected $paymentName = null;
    public $clientId    = null;
    public $paymentId   = null;
    
    
    //---------------Abstract Methods-----------------
    /**The constructor musst set userId and paymentName 
     */
    abstract function __construct($userId, $paymentName);
    
    /**Creates the paymill_fastCheckout Table. <b>parent::getCreateSQL() Can be used to get the SQL </b>*/
    abstract static function install();
    
    /**Saves the ClientId in association with the UserID
     * @param String $arg ClientId to save into the DB
     * @returns boolean
     */
    abstract function saveClientId($arg);
    
    /**
     * Saves the PaymentId in association with the UserID. Use the payment Name to determine if to save into ccPaymentId or elvPaymentId.
     * @param String $arg PaymentId to save into the DB
     * @returns boolean
     */
    abstract function savePaymentId($arg);
    
    /**
     * Saves the ClientId associated with the current UserID into the class variable of the same name.
     * @return boolean Indicator of success
     */
    abstract function loadClientId();
    
    /**
     * Saves the PaymentId associated with the UserID and the current Payment into the class variable of the same name.
     * @return boolean Indicator of success
     */
    abstract function loadPaymentId();
    

    //---------------Public Methods-------------------
    /**Sets the Id used to identify the current user.
     * @param String $arg UserId of the current customer.
     */
    public function setUserId($arg)
    {
        $this->userId = $arg;
    }
    
    /**Sets the Payment name used to Identify the current payment. Can be either cc or elv*/
    public function setPaymentName($arg)
    {
        $this->paymentName = $arg;
    }
    
    /**
     * Returns a boolean describing if there is FCData for the current Case.
     * Also Prepares the Ids to be read from the public vars.
     * @return boolean Does an entry for the current user and payment exist in the fast checkout table?
     */
    public function entryExists(){
        $hasClientId = $this->loadClientId();
        $hasPaymentId = $this->loadPaymentId();
        return $hasClientId && $hasPaymentId;
    }
    
    /**
     * Returns true id there already is a ClientId for the current user
     * @return Boolean
     */
    public function hasClientId(){
        $hasClientId = $this->loadClientId();
        return $hasClientId;
    }

        //---------------protected Methods----------------
    /**Can be used to get the createTable SQL for the install
     * @return String SQL Create Table string for the paymill_fastCheckout Table
     */
    protected final static function getCreateSQL()
    {
        return "CREATE TABLE IF NOT EXISTS `paymill_fastCheckout` (" .
                "`id` int(11) NOT NULL AUTO_INCREMENT," .
                "`userId` varchar(250) COLLATE utf8_unicode_ci NOT NULL," .
                "`clientId` varchar(250) COLLATE utf8_unicode_ci NOT NULL," .
                "`ccPaymentId` varchar(250) COLLATE utf8_unicode_ci NULL," .
                "`elvPaymentId` varchar(250) COLLATE utf8_unicode_ci NULL," .
                "PRIMARY KEY (`id`)," .
                "UNIQUE KEY `userId` (`userId`)" .
                ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1";
    } 
    
    
}


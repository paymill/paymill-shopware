<?php
/**
 * This class provides methods to alter and save plugin config entries
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ConfigHelper
{
    /**
     * Creates the table all data is saved in
     */
    private function _createConfigTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS paymill_config_data("
               . "id int(1) NOT NULL UNIQUE,"
               . "publicKey varchar(255),"
               . "privateKey varchar(255),"
               . "paymillPreAuth tinyint(1) NOT NULL,"
               . "paymillDebugging tinyint(1) NOT NULL,"
               . "paymillLogging tinyint(1) NOT NULL,"
               . "paymillFastCheckout tinyint(1) NOT NULL,"
               . "paymillSepaActive tinyint(1) NOT NULL"
               . ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
               . "INSERT IGNORE INTO paymill_config_data ("
               . "id, publicKey, privateKey, paymillPreAuth,"
               . "paymillDebugging, paymillLogging, paymillFastCheckout, paymillSepaActive) VALUES("
               . "1, NULL,NULL,0,0,0,0,0);";
        Shopware()->Db()->query($sql);
    }

    /**
     * Saves the config properties into the db
     */
    public function persist()
    {
        $this->_createConfigTable();
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $publicKey = trim($swConfig->get("publicKey"));
        $privateKey = trim($swConfig->get("privateKey"));
        $preAuthFlag = $swConfig->get("paymillPreAuth") == true;
        $debuggingFlag = $swConfig->get("paymillDebugging") == true;
        $loggingFlag = $swConfig->get("paymillLogging") == true;
        $fastCheckoutFlag = $swConfig->get("paymillFastCheckout") == true;
        $sepaFlag = $swConfig->get("paymillSepaActive") == true;

        $sql = "UPDATE paymill_config_data SET"
               . "`publicKey` = ?,"
               . "`privateKey` = ?,"
               . "`paymillPreAuth` = ?,"
               . "`paymillDebugging` = ?,"
               . "`paymillLogging` = ?,"
               . "`paymillFastCheckout` = ?,"
               . "`paymillSepaActive` = ?"
               . "WHERE id = 1";
        Shopware()->Db()->query(
            $sql,
            array(
                 $publicKey,
                 $privateKey,
                 $preAuthFlag?1:0,
                 $debuggingFlag?1:0,
                 $loggingFlag?1:0,
                 $fastCheckoutFlag?1:0,
                 $sepaFlag?1:0
            )
        );
    }

    /**
     * Restores all configurations from a past installation
     * @return mixed
     */
    public function loadData()
    {
        $this->_createConfigTable();
        $sql = "Select * FROM paymill_config_data WHERE id = 1;";
        $result = Shopware()->Db()->fetchAll($sql);
        return $result[0];
    }
}
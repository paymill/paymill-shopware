<?php
/**
 * Paymill Logging
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     Paymill
 */
require_once dirname(__FILE__) . '/Parents/LoggingManagerAbstract.php';
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManagerShopware extends LoggingManagerAbstract
{
    public function getPluginVersion()
    {
        return Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->getVersion();
    }
    
    /**
     * insertOne(string, string, string)<br/>
     * Inserts the following Values from the sources described below into the pigmbh_paymill_log table.<br/>
     * 
     * @param $merchantInfo      <b>String</b> Loggingtext for the merchant<br/>
     * @param $devInfo           <b>String</b> Loggingtext for the developer<br/>
     * @param $devInfoAdditional <b>String</b> Additional Loggingtext which has to be investigated seperately from the devInfo<br/>
     */
    public function insertOne($merchantInfo, $devInfo, $devInfoAdditional = null)
    {
        $sql = "INSERT INTO `pigmbh_paymill_log`(`version`, `merchantInfo`, `devInfo`, `devInfoAdditional`)
            VALUES(?,?,?,?)";
        $version = $this->getPluginVersion();
        
        Shopware()->Db()->query($sql,array($version, $merchantInfo, $devInfo, $devInfoAdditional));
    }
    
    
    /**
     * Executes the sql passed as an argument returning an array of multiple results
     * @param String $query
     * @return array
     */
    public function selectAll($query)
    {
        $result = Shopware()->Db()->fetchAll($query);
        return $result;
    }

    /**
     * Executes the sql passed as an argument returning an array of a single result
     * @param type $query
     * @return type
     */
    public function selectOne($query)
    {
        $result = Shopware()->Db()->fetchOne($query);
        return $result;
    }
    
    
    public static function install()
    {
        
        try{
            $sql = parent::generateCreateTable();
            Shopware()->Db()->query($sql);
        }catch(Exception $exception){
            Shopware()->Log()->Err("There was an Error creating the Log-Table: ".$exception->getMessage());
            throw new Exception("There was an Error creating the Log-Table: ".$exception->getMessage());
        }
        
    }
    
    public function getLoggingMode()
    {
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        return $swConfig->get('paymillLogging');
    }


}

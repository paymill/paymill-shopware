<?php

/**
 * loggingManager.php
 *
 * @category   PayIntelligent
 * @copyright  Copyright (c) 2013 PayIntelligent GmbH (http://payintelligent.de)
 */
abstract class LoggingManagerAbstract
{
    //---------------Abstract Methods---------------
    /**
     * insertOne(string, string, string)<br/>
     * <p align='Center'><b>DO NOT USE THIS METHOD TO LOG INTO THE DB. REFER TO write($merchantInfo, $devInfo, $devInfoAdditional = null) INSTEAD!</b></p><br/><br/>
     * Musst insert the following Values from the sources described below into the pigmbh_paymill_log table.<br/><br/>
     * <b>Values to insert:</b> <br/>
     * (string) plugin version, can be obtained using the getPluginVersion() method<br/>
     * (string) merchant information, can be optained from the argument<br/>
     * (string) developer information, can be optained from the argument<br/>
     * (string) additional developer information, can be optained from the argument<br/>
     * -----------------------------------------------------------------------------<br/>
     *
     * @param $merchantInfo      <b>String</b> Loggingtext for the merchant<br/>
     * @param $devInfo           <b>String</b> Loggingtext for the developer<br/>
     * @param $devInfoAdditional <b>String</b> Additional Loggingtext which has to be investigated seperately from the devInfo<br/>
     */
    abstract function insertOne($merchantInfo, $devInfo, $devInfoAdditional = null);

    /**
     * getPluginVersion(void)<br/>
     * Musst return the version string of the plugin. Will be used to fill the version field of each log entry
     *
     * @return string version
     */
    abstract function getPluginVersion();

    /**
     * getLogginMode(void)<br/>
     * Musst return true if logging has been enabled or false if disabled
     *
     * @return boolean logging
     */
    abstract function getLoggingMode();

    /**
     * <b>Must be overwritten!</b><br/>
     * <br/>
     * The new method needs to execute an SQL Statement returned by this parent.
     * string for the creation of the pigmbh_paymill_log table can be obtained using the parent::generateCreateTable function
     */
    abstract static function install();

    /**
     * selectAll(string)<br/>
     * Musst execute a shop-compliant sql Select returning multiple row results, using the string passed for an argument as the Statement.
     *
     * @param string $query SQL Query String which needs to be passed to the shop-compliant DB interface without changes.
     *
     * @return array() Result of the select
     */
    abstract function selectAll($query);

    /**
     * selectOne(string)<br/>
     * Musst execute a shop-compliant sql Select returning a single result, using the string passed for an argument as the Statement.
     *
     * @param string $query SQL Query String which needs to be passed to the shop-compliant DB interface without changes.
     *
     * @return Result of the select
     */
    abstract function selectOne($query);

    //---------------Public Methods---------------
    /**
     * generateCreateTable(void)<br/>
     * Generates the createTable SQL Statement to be used by the install() method to allow Logging.
     *
     * @return string Create Table SQL String for the pigmbh_paymill_log Table used by this class
     */
    public static function generateCreateTable()
    {
        return "CREATE TABLE IF NOT EXISTS `pigmbh_paymill_log` (" . "`id` int(11) NOT NULL AUTO_INCREMENT," . "`entryDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP," . "`version` varchar(25) NOT NULL COLLATE utf8_unicode_ci," . "`merchantInfo` varchar(250) COLLATE utf8_unicode_ci NOT NULL," . "`devInfo` text COLLATE utf8_unicode_ci DEFAULT NULL," . "`devInfoAdditional` text COLLATE utf8_unicode_ci DEFAULT NULL," . "PRIMARY KEY (`id`)" . ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1";
    }

    /**
     * getTotal(void)<br/>
     * Returns the number of entries in the pigmbh_paymill_log table.<br/>
     *
     * @uses selectOne($query) protected method of this class, make sure to override it to allow access.
     * @return int Number of Entries
     */
    public function getTotal()
    {
        $getTotal = "SELECT count(*) FROM `pigmbh_paymill_log`";
        $count = $this->selectOne($getTotal);

        return $count;
    }

    /**
     * read(int,int)<br/>
     * Reads all entries from the pigmbh_paymill_log table, filters for start and limit numbers and returns them as an array.
     *
     * @param int $start first value to be read.(Recommended range: 0 to n)
     * @param int $limit Number of values to be read. (Recommended range: 1 to n)
     *
     * @uses selectAll($query) protected method of this class, make sure to override it to allow access.
     * @return array result
     */
    public function read($start, $limit)
    {
        //Cast arguments to int to avoid insecure values
        $start = (int)$start;
        $limit = (int)$limit;
        if ($start > $limit) {
            $limit = $start;
        }

        //Build SQL Statement using arguments
        $read = "SELECT * FROM `pigmbh_paymill_log` LIMIT " . $start . ", " . $limit;

        //Process Select and return result
        $result = $this->selectAll($read);

        return $result;
    }

    /**
     * write(string, string, string [can be null])<br />
     * Uses the abstract method insertOne() to insert the arguments into the db log
     *
     * @param String $merchantInfo      Log Message for the Merchant to understand. <b>Keep this one easy.</b>
     * @param String $devInfo           Log information for the dev in here. If you like to log an array, this is the place.
     * @param String $devInfoAdditional Log information for the dev in here. This field is optional but will be displayed near the first info. If you want something to be inspected seperately, log it here.
     */
    public function write($merchantInfo, $devInfo, $devInfoAdditional = null)
    {
        $doLog = $this->getLoggingMode();
        if ($doLog) {
            $this->insertOne($merchantInfo, $devInfo, $devInfoAdditional);
        }
    }
}
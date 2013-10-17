<?php

/**
 * The CsvReader class provides mechanics to load snippets from language files and prepare them for the DB
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     Paymill
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_CsvReader
{
    /** @var string */
    private $_path = null;

    /** @var array */
    private $_knownLanguages = null;

    /** @var array */
    private $_translations;

    /**
     * Creates an instance of the CsvReader Class
     *
     * @param String $path
     *
     */
    public function __construct($path = null)
    {
        $this->_path = $path;
        $this->_knownLanguages = $this->_setLanguages();
        $this->_translations = array();
    }

    /**
     * Searches the s_core_locale table for all locales and saves them into the known languages array
     */
    private function _setLanguages()
    {
        $sql = "SELECT locale,id FROM s_core_locales";
        $result = Shopware()->Db()->fetchAll($sql);
        $languages = array();
        foreach ($result as $entry => $set) {
            $languages[$set['locale']] = $set['id'];
        }

        return $languages;
    }

    /**
     * Load all CSV Files and Translations and returns them as a string for db insert
     *
     * @return bool Indicator of success
     */
    private function _loadCsvFiles()
    {
        try {
            if (($files = scandir($this->getPath()))) {
                foreach ($files as $file) {
                    if (is_file($this->getPath() . $file)) {
                        if ($this->_knownLanguages[basename($file, '.csv')]) {
                            $this->loadCsvFileByName($file);
                        }
                    }
                }
            }
        } catch (Exception $exc) {
            Shopware()->Log()->Error($exc->getTraceAsString());
        }

        return $this->_prepareDbInsert($this->_translations);
    }

    /**
     * Returns the path for the snippet files
     *
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Sets the path for the snippet files
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->_path = $path;
    }

    /**
     * Loads a single csv file by the given name
     *
     * @param string $filename Name of the file
     */
    private function loadCsvFileByName($filename)
    {
        $translations = array();
        if (($file = fopen($this->getPath() . $filename, "r")) !== false) {
            while (($data = fgetcsv($file, 0, ';', '"')) !== false) {
                $translations[$data[0]] = $data[1];
            }
        }
        $this->_translations[$this->_knownLanguages[basename($filename, '.csv')]] = $translations;
    }

    /**
     * Prepares the data taken from files to be merged into 1 insert string
     * @return string
     */
    private function _prepareDbInsert()
    {
        try {
            $result = "";
            $snippetsArray = $this->_translations;
            $shopIdsSql = "SELECT id FROM s_core_shops WHERE locale_id = ?";
            foreach ($snippetsArray as $localeId => $snippets) {
                $shopIds = Shopware()->Db()->fetchAll($shopIdsSql, array($localeId));
                foreach ($shopIds as $list => $shopId) {
                    foreach ($snippets as $name => $value) {
                        $baseString = '("Paymill", "'.$name.'", "'.$value.'", "'.$localeId.'", "'.$shopId['id'].'", Now(), NOW()),';
                        $result .= $baseString;
                    }
                }
            }
            $result = substr_replace($result ,"",-1);
            $result .= ";";
        } catch (Exception $exc) {
            Shopware()->Log()->Error($exc->getTraceAsString());
        }
        return $result;
    }

    /**
     * Returns a string containing the sql insert for the s_core_snippets table
     * @return string
     */
    public function getSqlInsert()
    {
        $sql_snippets = "REPLACE INTO s_core_snippets (`namespace`, `name`, `value`, `localeID`, `shopID`,`created`, `updated`) VALUES ";
        $entries = $this->_loadCsvFiles();
        $sql = $sql_snippets . $entries;
        return $sql;
    }
}
<?php
/**
 * Paymill Logging
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     PayIntelligent
 */
class Shopware_Controllers_Backend_PaymillLogging extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * index action is called if no other action is triggered
     *
     * @return void
     */
    public function indexAction()
    {
        $this->View()->loadTemplate("backend/paymill_logging/app.js");
        $this->View()->assign("title", "Paymill-Logging");
    }

    /**
     * This Action loads the logging data from the database into the backend view
     */
    public function loadStoreAction()
    {

        $start = intval($this->Request()->getParam('start'));
        $limit = intval($this->Request()->getParam('limit'));

       if ($sort = $this->Request()->getParam('sort')) {
            $sort = current($sort);
        }


        if ($filter = $this->Request()->getParam('filter')) {
            foreach ($filter as $value) {
                if (empty($value['property']) || empty($value['value'])) {
                    continue;
                }
                if ($value['property'] == 'searchTerm') {
                    $this->Request()->setParam('searchTerm', $value['value']);
                }
                if ($value['property'] == 'connectedSearch') {
                    $this->Request()->setParam('connectedSearch', $value['value']);
                }
            }
        }

        if($searchTerm = $this->Request()->getParam('searchTerm')){
            $searchTerm = trim($searchTerm);
        }

        $direction = empty($sort['direction']) || $sort['direction'] == 'DESC' ? 'DESC' : 'ASC';
        $property = empty($sort['property']) ? 'id' : $sort['property'];

        $loggingManager = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_LoggingManager();

        //Load Data
        if($connectedSearch = $this->Request()->getParam('connectedSearch')){
            $data = $loggingManager->read($start, ($limit), $property, $direction, $searchTerm, $connectedSearch);
        } else {
            $data = $loggingManager->read($start, ($limit), $property, $direction, $searchTerm);
        }


        $total = $loggingManager->getTotal();
        $this->View()->assign(array("data" => $data, "total" => $total, "success" => true));
    }
}
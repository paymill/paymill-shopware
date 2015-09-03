<?php
require_once dirname(__FILE__) . '/lib/Services/Paymill/Payments.php';

class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_Checkout_Form_PrefillData
{
    /**
     * @var Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper
     */
    private $modelHelper;
    
    /**
     * @var Services_Paymill_Payments 
     */
    private $servicePayments;
    
    /**
     * @var string
     */
    private $apiUrl = "https://api.paymill.com/v2/";

    /**
     * Creates an instance for this class
     */
    public function __construct()
    {
        $swConfig = Shopware()->Plugins()->Frontend()->PaymPaymentCreditcard()->Config();
        $this->modelHelper = new Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_ModelHelper();
        $this->privateKey = trim($swConfig->get("privateKey"));
        $this->servicePayments = new Services_Paymill_Payments(trim($swConfig->get("privateKey")), $this->apiUrl);
    }
    
    public function isDataAvailable($paymentName, $userId){
        return $this->servicePayments->getPaymillPaymentId($paymentName, $userId);
    }

        /**
     * Returns the prefill data for creditcard and directdebit
     * 
     * @param array $userData
     * @return array
     */
    public function prefill($userData){
        $userId = 0;
        $userFullName = '';
        if(array_key_exists('billingaddress', $userData)){
            if(array_key_exists('userID', $userData['billingaddress'])){
                $userId = $userData['billingaddress']['userID'];    
            }
            if(array_key_exists('firstname', $userData['billingaddress']) && array_key_exists('lastname', $userData['billingaddress'])){
                $userFullName = $userData['billingaddress']['firstname'] . " " . $userData['billingaddress']['lastname'];
            }
        }
        
        $creditcardData = $this->prefillCreditcard($userId, $userFullName);
        $directdebitData = $this->prefillDirectdebit($userId);
        return array_merge($creditcardData, $directdebitData);
    }
    
    /**
     * Returns the prefilldata for creditcard
     * 
     * @param integer $userId
     * @param string $userFullName
     * @return array
     */
    private function prefillCreditcard($userId, $userFullName){
        $prefillData = array(
            'paymillCardHolder' => $userFullName,
            'paymillCardNumber' => '',
            'paymillCvc' => '',
            'paymillMonth' => '',
            'paymillYear' => ''
        );
        
        $paymentId = $this->modelHelper->getPaymillPaymentId('cc', $userId);
        if ($paymentId != "") {
            $paymentObject = $this->servicePayments->getOne($paymentId);
            $prefillData['paymillCardNumber'] = "..." . $paymentObject['last4'];
            $prefillData['paymillCvc'] = "***";
            $prefillData['paymillMonth'] = $paymentObject['expire_month'];
            $prefillData['paymillYear'] = $paymentObject['expire_year'];
            $prefillData['paymillCardHolder'] = $paymentObject['card_holder'];
            $prefillData['paymillBrand'] = $paymentObject['card_type'];
        }
        return $prefillData;
    }
    
    /**
     * Returns the prefilldata for directdebit
     * 
     * @param integer $userId
     * @return array
     */
    private function prefillDirectdebit($userId){
        $prefillData = array(
            'paymillAccountNumber' => '',
            'paymillBankCode' => ''
        );
        
        $paymentId = $this->modelHelper->getPaymillPaymentId('elv', $userId);
        if ($paymentId != "") {
            $paymentObject = $this->servicePayments->getOne($paymentId);
            $prefillData['paymillAccountNumber'] = $paymentObject['iban'] != null ? $paymentObject['iban'] : $paymentObject['account'];
            $prefillData['paymillBankCode'] = $paymentObject['bic'] != null ? $paymentObject['bic'] : $paymentObject['code'];
        }
        return $prefillData;
    }
}

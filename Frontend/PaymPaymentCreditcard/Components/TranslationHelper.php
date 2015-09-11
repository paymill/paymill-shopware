<?php

/**
 * The TranslationHelper class contains installation and update routines for the modules translations
 *
 * @category   Shopware
 * @package    Shopware_Plugins
 * @subpackage Paymill
 * @author     Paymill
 */
class Shopware_Plugins_Frontend_PaymPaymentCreditcard_Components_TranslationHelper
{
    private $_configTranslationMapping = array(
        'label_publicKey' => 'publicKey',
        'label_privateKey' => 'privateKey',
        'label_preAuth' => 'paymillPreAuth',
        'label_debugging' => 'paymillDebugging',
        'label_fastCheckout' => 'paymillFastCheckout',
        'label_logging' => 'paymillLogging',
        'label_sepa_active' => 'paymillSepaActive',
        'label_sepa_date' => 'paymillSepaDate',
        'label_paymillPCI' => 'paymillPCI'
    );

    private $_form = null;

    private $snippet = null;

    /**
     * Creates an instance of the translation helper
     *
     * @param $form
     */
    public function __construct($form)
    {
        $this->_form = $form;
        $this->snippet = parse_ini_file(dirname(__FILE__).'/../snippets/backend/paym_payment_creditcard/config.ini', true);
    }

    /**
     * Creates the Translation for the plugin configuration
     *
     * @throws Exception
     * @return void
     */
    public function createPluginConfigTranslation()
    {
        try {
            $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
            foreach ($this->snippet as $locale => $snippets) {
                $localeModel = $shopRepository->findOneBy(array('locale' => $locale));
                if ($localeModel === null) {
                    continue;
                }
                foreach ($snippets as $snippetKey => $translation) {
                    if(!array_key_exists($snippetKey,$this->_configTranslationMapping)){
                        continue;
                    }

                    $elementModel = $this->_form->getElement($this->_configTranslationMapping[$snippetKey]);
                    if ($elementModel === null) {
                        continue;
                    }
                    $description = null;
                    $descriptionKey = str_replace('label_', 'description_', $snippetKey);
                    if(array_key_exists($descriptionKey, $snippets)){
                        $description = $snippets[$descriptionKey];
                    }

                    $this->_addNewConfigTranslation($elementModel, $localeModel, $translation, $description);
                }
            }
        } catch (Exception $exception) {
            throw new Exception("Can not create translation for configuration form." . $exception->getMessage());
        }
    }

    /**
     * Simplifies the usage of the setLabel and addTranslation method calls
     *
     * @param                                 $localeModel
     * @param string                          $translationSnippet
     * @param \Shopware\Models\Config\Element $elementModel
     */
    private function _addNewConfigTranslation($elementModel, $localeModel, $translationSnippet, $description=null)
    {
        $translationModel = new \Shopware\Models\Config\ElementTranslation();
        if(!is_null($description)){
            $translationModel->setDescription($description);
        }
        $translationModel->setLabel($translationSnippet);
        $translationModel->setLocale($localeModel);
        $elementModel->addTranslation($translationModel);
    }

    /**
     * Removes all Snippets created by the plugin installation routine
     */
    public function dropSnippets()
    {
        $sql = "DELETE FROM s_core_snippets WHERE namespace = 'Paymill';";
        Shopware()->DB()->query($sql);
    }
}
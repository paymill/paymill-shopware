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
        'publicKey'           => 'paymill_config_public_key_label',
        'privateKey'          => 'paymill_config_private_key_label',
        'paymillPreAuth'      => 'paymill_config_preauthorize_label',
        'paymillDebugging'    => 'paymill_config_debugging_label',
        'paymillFastCheckout' => 'paymill_config_fast_checkout_label',
        'paymillLogging'      => 'paymill_config_logging_label',
        'paymillSepaActive'   => 'paymill_config_sepa_active_label'
    );

    private $_form = null;

    /**
     * Creates an instance of the translation helper
     * @param $form
     */
    public function __construct($form){
        $this->_form = $form;
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
            $form = $this->_form;
            $translationStore = $this->_getSnippets();

            foreach($translationStore as $locale => $snippets) {
                $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
                $localeModel = $shopRepository->findOneBy(array( 'locale' => $locale ));

                foreach($snippets as $elementName => $snippet) {
                    if($localeModel === null){
                        continue;
                    }
                    $elementModel = $form->getElement($elementName);
                    if($elementModel === null) {
                        continue;
                    }
                    $this->_addNewConfigTranslation($localeModel,$snippet, $elementModel);
                }
            }
        } catch (Exception $exception) {
            Shopware()->Log()->Err("Can not create translation for configuration form." . $exception->getMessage());
            throw new Exception("Can not create translation for configuration form." . $exception->getMessage());
        }
    }

    /**
     * Simplifies the usage of the setLabel and addTranslation method calls
     * @param $localeModel
     * @param string  $translationSnippet
     * @param \Shopware\Models\Config\Element $elementModel
     */
    private function _addNewConfigTranslation($localeModel, $translationSnippet, $elementModel)
    {
        $translationModel = new \Shopware\Models\Config\ElementTranslation();
        $translationModel->setLabel($translationSnippet);
        $translationModel->setLocale($localeModel);
        $elementModel->addTranslation($translationModel);
    }

    /**
     * Returns an array of translation snippets. array[language][elementName][snippet]
     * @return array
     */
    private function _getSnippets(){
        $translationStore = array();
        $sql = "SELECT value FROM s_core_snippets s, s_core_locales l
                WHERE s.localeID = l.id
                AND l.locale = ?
                AND `name` = ?";

        foreach($this->_configTranslationMapping as $elementName => $translationName){
            $translationStore['de_DE'][$elementName] = Shopware()->Db()->fetchOne($sql,array( 'de_DE', $translationName));
            $translationStore['en_GB'][$elementName] = Shopware()->Db()->fetchOne($sql,array( 'en_GB', $translationName));
        }

        return $translationStore;
    }
}
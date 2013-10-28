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
        'paymillShowLabel'    => 'paymill_config_show_label_label'
    );

    private $_form = null;

    /**
     * @param $form
     */
    public function __construct($form){
        $this->_form = $form;
    }

    /**
     * Updates the config form
     * @throws Exception
     */
    public function updateConfigConfigTranslations()
    {
        $form = $this->_form;
        $translationStore = $this->_getSnippets();

        //Map translations to elements
        foreach($this->_configTranslationMapping as $elementName => $translationName){
            $elementModel = $form->getElement($elementName);
            if($elementModel === null) {
                //Continue with the next element if there is no element available
                continue;
            }

            //Alter existing translations
            if($elementModel->hasTranslations()){
                $this->_updateConfigTranslations($elementName, $translationStore, $elementModel);

            } else {
                //Add new translations
                foreach($translationStore as $language => $translationMap){
                    $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
                    $localeModel = $shopRepository->findOneBy(array( 'locale' => $language ));
                    $this->_addNewConfigTranslation($localeModel, $translationMap[$elementName], $elementModel);
                }
            }
        }
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
     * Handles the update of translations
     * @param string $elementName
     * @param array $translationStore
     * @param \Shopware\Models\Config\Element $elementModel
     */
    private function _updateConfigTranslations($elementName, $translationStore, $elementModel){
        $translations = $elementModel->getTranslations();
        $hasTranslations = array();
        do{
            $localeName = $translations->current()->getLocale()->getLocale();
            $hasTranslations[$localeName] = $translations->key();
        }while($translations->next());

        //Add new translations
        foreach($translationStore as $language => $translationMap){
            if(isset($hasTranslations[$language])){
                $elementModel->getTranslations()
                             ->get($hasTranslations[$language])
                             ->setLabel($translationMap[$elementName]);

            } else {
                $shopRepository = Shopware()->Models()->getRepository('\Shopware\Models\Shop\Locale');
                $localeModel = $shopRepository->findOneBy(array( 'locale' => $language ));
                $this->_addNewConfigTranslation($localeModel, $translationMap[$elementName], $elementModel);
            }
        }
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
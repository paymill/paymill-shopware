Paymill-Shopware-4
==================

Payment plugin for Shopware Version 4.


You have to clone with `--recursive` flag in order to get all required submodules. IMPORTANT: Without submodules the source code will not work!

    git clone --recursive https://github.com/Paymill/Paymill-Shopware4.git
    
- Merge the content of the Paymill-Shopware-Module directory with your Shopware installation. 
- In your administration backend configure the PaymillPaymentCreditcard plugin: Insert your private and public key.
- Enable the Paymill plugin.
- If you enable logging in the plugin make sure that log.txt inside the plugin directory is writable.

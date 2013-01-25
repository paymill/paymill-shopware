Paymill-Shopware-4
==================

Payment plugin for Shopware Version 4.


You have to clone with `--recursive` flag in order to get all required submodules. IMPORTANT: Without submodules the source code will not work!

    git clone --recursive https://github.com/Paymill/Paymill-Shopware4.git
    
- Merge the content of the Paymill-Shopware-Module directory with your Shopware installation. 
- In your administration backend install the PaymillPaymentCreditcard plugin and go to the configuration section where you can insert your private and public key (that you can find in your Paymill cockpit [https://app.paymill.de/](https://app.paymill.de/ "Paymill cockpit")).
- Finally activate the plugin and customize it to your needs under Settings > Payment methods.

# Logging

- If you enable logging in the plugin configuration make sure that log.txt inside the plugin directory is writable. Otherwise logging information will not be stored to the logfile.

Paymill-Shopware
==================

Payment plugin for Shopware Community Edition Version 4.3.0 - 5.0.4

## Installation from this git repository

    https://github.com/paymill/paymill-shopware/archive/master.zip

- Download and unzip the Zip file.
- Go to the directory you just unzipped the Plugin into, and copy the "Frontend" Folder into the Community Directory of your Shopware installation ("Shopware Directory"\engine\Shopware\Plugins\Community)
- In your administration backend install the PaymillPaymentCreditcard plugin and go to the configuration section where you can insert your private and public key (that you can find in your Paymill cockpit [https://app.paymill.de/](https://app.paymill.de/ "Paymill cockpit")).
- Finally activate the plugin and customize it to your needs under Settings > Payment methods.

## Update your Installation using this repository
    https://github.com/paymill/paymill-shopware/archive/master.zip

- Download and unzip the Zip file.
- Go to the directory you just unzipped the Plugin into, and copy the "Frontend" Folder into the Community Directory of your Shopware installation ("Shopware Directory"\engine\Shopware\Plugins\Community)
- Click the update button in the plugin manager
- Your plugin is now up to date without any changes to your configuration

## Your Advantages
* PCI DSS compatibility
* Payment means: Credit Card (Visa, Visa Electron, Mastercard, Maestro, Diners, Discover, JCB, AMEX, China Union Pay), Direct Debit (ELV)
* Refunds can be created from an additional tab in the order detail view
* Optional configuration for authorization and manual capture with credit card payments
* Optional fast checkout configuration allowing your customers not to enter their payment detail over and over during checkout
* Improved payment form with visual feedback for your customers
* Supported Languages: German, English
* Backend Log with custom View accessible from your shop backend

## PayFrame
 We’ve introduced a “payment form” option for easier compliance with PCI requirements.
 In addition to having a payment form directly integrated in your checkout page, you can
 use our embedded PayFrame solution to ensure that payment data never touches your
 website.

 PayFrame is enabled by default, but you can choose between both options in the plugin
 settings. Later this year, we’re bringing you the ability to customise the appearance and
 text content of the PayFrame version.

 To learn more about the benefits of PayFrame, please visit our [FAQ](https://www.paymill.com/en/faq/how-does-paymills-payframe-solution-work "FAQ").

## Configuration

Afterwards go to the plugin configuration and configure the Module by inserting your PAYMILL test or live keys


## In case of errors

In case of any errors turn on the debug mode and logging in the PAYMILL Basic Settings. Open the javascript console in your browser and check what's being logged during the checkout process. To access the logged information not printed in the console please refer to the PAYMILL Log in the admin backend.

## Notes about the payment process

The payment is processed when an order is placed in the shop frontend.
An invoice is being generated automatically.

There are several options altering this process:

Fast Checkout: Fast checkout can be enabled by selecting the option in the PAYMILL Basic Settings. If any customer completes a purchase while the option is active this customer will not be asked for data again. Instead a reference to the customer data will be saved allowing comfort during checkout.

Preauthorization and manual capture: If the option is selected, a preauthorization will be generated during checkout. On generation of the invoice, the capture will be triggered automatically, allowing easy capturing without the need to trigger it manually.

## Translation of payment method display names
If you are interested in translating the display name of any payment method, you can do so by following these steps:

* Got to the Shopware Backend
* Click on Configuration/Payment Methods
* Select the desired method (Paymill Methods will show as "Kreditkartenzahlung" and "ELV"
* Click on the globe icon in the right corner of the Description field
* Select the desired language from the list in the left frame
* Enter your translation for the payment name
* After you save your changes, you are good to go.

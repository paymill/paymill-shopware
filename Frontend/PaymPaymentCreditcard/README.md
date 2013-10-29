Paymill-Shopware
==================

Payment plugin for Shopware Community Edition Version 4.0.0 - 4.1.3

## Installation from this git repository

    https://github.com/Paymill/Zahlungsformular/archive/master.zip

- Download and unzip the Zip file.
- Go to the directory you just unzipped the Plugin into, and copy the "Frontend" Folder into the Community Directory of your Shopware installation ("Shopware Directory"\engine\Shopware\Plugins\Community)
- In your administration backend install the PaymillPaymentCreditcard plugin and go to the configuration section where you can insert your private and public key (that you can find in your Paymill cockpit [https://app.paymill.de/](https://app.paymill.de/ "Paymill cockpit")).
- Finally activate the plugin and customize it to your needs under Settings > Payment methods.


## Your Advantages
* PCI DSS compatibility
* Payment means: Credit Card (Visa, Visa Electron, Mastercard, Maestro, Diners, Discover, JCB, AMEX, China Union Pay), Direct Debit (ELV)
* Refunds can be created from an additional tab in the order detail view
* Optional configuration for authorization and manual capture with credit card payments
* Optional fast checkout configuration allowing your customers not to enter their payment detail over and over during checkout
* Improved payment form with visual feedback for your customers
* Supported Languages: German, English
* Backend Log with custom View accessible from your shop backend


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

##Release Notes

###EN
#####1.1.0
- Lib has been updated
- Checkout Form is now always being displayed, showing masked customer information if there is fast checkout data. This allows customers to change their credit card or bank details after the data has been saved.
- Improved the CVC tool-tip in the checkout form
- Customers will now get identified on their first usage of a PAYMILL payment mean and will now always use the same id, even if fast checkout is not enabled.
- Added pre authorization as a plug-in option: If active, credit card transactions will be authorized during checkout and can be captured from a new tab in the order detail view
- Added refund option from the detail tab. Any transaction booked after the update can be completely refunded at will
- Implemented special handling for Maestro cards during the checkout: cards without CVC are now supported
- Improved the translation routine to prepare the support for different languages in the frontend.
- Added status updates for PAYMILL transactions.
- Improved the log to allow search
- Fixed a bug causing the debug option not to work
- Added translations for the payment names.
- Improved update routine: from this version onwards the Shopware update function will be completely functional with this module
- Improved code readability

###DE
####1.1.0
- Lib wurde aktualisiert
- Zahlungsformular zeigt jetzt maskierte Zahlungsdaten sofern fast checkout daten vorhanden sind. Dadurch können Kunden ihre hinterlegten Daten aktualisieren
- CVC Tooltip wurde verbessert
- Kunden werden jetzt ab der ersten Nutzung einer PAYMILL Zahlungsart als Bestandkunden geführt und alle weiteren Transaktionen werden diesem Kunden zugeordnet
- Autorisierungsoption wurde hinzugefügt: Sofern aktiv, werden Kraditkarten Transaktionen im Checkout autorisiert und können anschließend über eine Erweiterung der Bestellungsdetail-Ansicht belastet werden.
- Gutschrift wurde implementiert: Alle nach Installation dieser Version durchgeführten Transaktionen können per Button in der Bestellungsdetail-Ansicht wieder gutgeschrieben werden
- Sonderbehandlung für Maestro Karten im Zahlungsformular: Es werden nun auch Karten ohne CVC unterstützt
- Verbesserte Übersetzungslogik implementiert. Dies ebnet den Weg für das hinzufügen verschiedener Sprachen in künftigen Updates
- Statusaktualisierung für PAYMILL Bestellungen implementiert: Alle nach Installation dieser Version durchgeführten Bestellungen werden auf entsprechende Statuswerte gesetzt.
- Log Ansicht wurde verbessert und bietet jetzt eine Suchfunktion
- Fehler der dazu führen konnte, dass die Debugging Funktion nicht funktionierte wurde behoben
- Übersetzung der Zahlungsarten wurde implementiert
- Lastschrift wurde in ELV umbenannt. Diese Änderung ist vorerst nur für neue Installationen verfügbar.
- Update routine wurde verbessert: Ab dieser Version wird die Shopware Update Routine vom Modul vollständig unterstützt
- Lesbarkeit des Quellcodes wurde verbessert
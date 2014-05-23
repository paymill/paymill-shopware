#Release Notes

##1.4.0
 - united SEPA and regular ELV forms
 - added backend option to show credit card logos in frontend
 - added prenotification in order mail for direct debit

##1.3.1
- Fixed Translation Bug for mainshop

##1.3.0
- Added Support for Conexco Responsive Template
- Added improved iban validation
- Added support for various languages
- Added improved early pan detection

##1.2.0
- Added additional validation to the fast checkout process
- Implemented optional SEPA direct debit form. Only payments from germany are supported

##1.1.4
- Fixed a bug keeping unregistered users from changing the Supplier country in the shopping cart
- Fixed a bug causing crashes on language changes in the shopping cart

##1.1.3
- Fixed a bug causing the first Payment attempt for new customers to crash under some circumstances

##1.1.2
- Fixed a bug causing crashes during checkout

##1.1.1
- Updated Lib
- Updated README.md files
- Added CHANGELOG.md
- Implemented improved error feedback
- Added Snippets to support the improved feedback
- Fixed a bug causing the cvc tooltip to be english in all languages
- Removed logging for successful installations and updates

##1.1.0
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

##1.0.6
- Added dynamic cc-brands
- Added supplier, updated link and support in the plugin description

##1.0.5
- Added translation of payment names as a paragraph to the readme file
- Added Translation of payment method displaynames section to the readme file

##1.0.4
- Fixed a bug causing crashes if no shop with english language is available
- Adjusted description for shopware community store
- Added trim for used keys
- Checking GTC before creating a token.
- Update readme file with supported versions
- Added source parameter
- Improved translation handling

##1.0.3
- Added snippets and translations

##1.0.2
- Rewrote Plugin from scratch

##1.0.1
- Updated Lib to v2
- Added ELV as a payment mean

##1.0.0
- Initial release
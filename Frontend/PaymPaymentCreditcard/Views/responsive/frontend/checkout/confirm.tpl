{extends file="parent:frontend/checkout/confirm.tpl"}
{block name='frontend_checkout_confirm_error_messages' append}
    {if $pigmbhErrorMessage} 
        <div class="grid_20 {$pigmbhErrorClass}">
            <div class="error">
                <div class="center">
                    <strong> {$pigmbhErrorMessage} </strong>
                </div>
            </div>
        </div>
    {/if}
{/block}
{block name="frontend_checkout_confirm_product_table" prepend}
    {if $sPayment.name === 'paymillcc' || $sPayment.name === 'paymilldebit'}
        <script type = "text/javascript" >
            var PAYMILL_PUBLIC_KEY = '{$publicKey}';        
            var paymillcheckout = new Object();
            paymillcheckout.errormessages = new Object();
            paymillcheckout.errormessages.bridge = {
                internal_server_error: '{s namespace=Paymill name=internal_server_error}The communication with the psp failed.{/s}',
                invalid_public_key: '{s namespace=Paymill name=invalid_public_key}The public key is invalid.{/s}',
                invalid_payment_data: '{s namespace=Paymill name=invalid_payment_data}Paymentmethod, card type currency or country not authorized{/s}',
                unknown_error: '{s namespace=Paymill name=unknown_error}Unknown Error{/s}',
                cancelled3DS: '{s namespace=Paymill name=3ds_cancelled}3-D Secure process has been canceled by the user{/s}',
                field_invalid_card_number: '{s namespace=Paymill name=field_invalid_card_number}Invalid Credit Card Number{/s}',
                field_invalid_number: '{s namespace=Paymill name=field_invalid_card_number}Invalid Credit Card Number{/s}',
                field_invalid_card_exp_year: '{s namespace=Paymill name=field_invalid_card_exp_year}Invalid Expiry Year{/s}',
                field_invalid_card_exp_month: '{s namespace=Paymill name=field_invalid_card_exp_month}Invalid Expiry Month{/s}',
                field_invalid_card_exp: '{s namespace=Paymill name=field_invalid_card_exp}Credit Card not valid{/s}',
                field_invalid_exp: '{s namespace=Paymill name=field_invalid_card_exp}Credit Card not valid{/s}',
                field_invalid_card_cvc: '{s namespace=Paymill name=field_invalid_card_cvc}Invalid CVC{/s}',
                field_invalid_cvc: '{s namespace=Paymill name=field_invalid_card_cvc}Invalid CVC{/s}',
                field_invalid_card_holder: '{s namespace=Paymill name=field_invalid_card_holder}Invalid Card Holder{/s}',
                field_invalid_amount_int: '{s namespace=Paymill name=field_invalid_amount_int}Missing amount for 3-D Secure{/s}',
                field_field_invalid_amount: '{s namespace=Paymill name=field_field_invalid_amount}Missing amount for 3-D Secure{/s}',
                field_field_field_invalid_currency: '{s namespace=Paymill name=field_invalid_currency}Invalid currency for 3-D Secure{/s}',
                field_invalid_account_number: '{s namespace=Paymill name=field_invalid_account_number}Invalid Account Number{/s}',
                field_invalid_account_holder: '{s namespace=Paymill name=field_invalid_account_holder}Invalid Account Holder{/s}',
                field_invalid_bank_code: '{s namespace=Paymill name=field_invalid_bank_code}Invalid bank code{/s}',
                field_invalid_iban: '{s namespace=Paymill name=field_invalid_iban}Invalid IBAN{/s}',
                field_invalid_bic: '{s namespace=Paymill name=field_invalid_bic}Invalid BIC{/s}',
                field_invalid_country: '{s namespace=Paymill name=field_invalid_country}Invalid country for sepa transactions{/s}',
                field_invalid_bank_data: '{s namespace=Paymill name=field_invalid_bank_data}Invalid bank data{/s}',
            };
            paymillcheckout.errormessages.validation = new Object();
            paymillcheckout.errormessages.validation.creditcard = {
                cardholder: '{s namespace=Paymill name=feedback_error_creditcard_holder}Please enter the cardholders name.{/s}',
                cardnumber: '{s namespace=Paymill name=feedback_error_creditcard_number}Please enter a valid creditcardnumber.{/s}',
                cvc: '{s namespace=Paymill name=feedback_error_creditcard_cvc}Please enter a valid securecode (see back of creditcard).{/s}',
                expirydate: '{s namespace=Paymill name=feedback_error_creditcard_valid}The expiry date is invalid.{/s}',
            };
            paymillcheckout.errormessages.validation.directdebit = {
                accountholder: '{s namespace=Paymill name=feedback_error_directdebit_holder}Please enter the account name.{/s}',
                iban: '{s namespace=Paymill name=feedback_error_sepa_iban}Please enter a valid iban{/s}',
                bic: '{s namespace=Paymill name=feedback_error_sepa_bic}Please a valid bic.{/s}',
                accountnumber: '{s namespace=Paymill name=feedback_error_directdebit_number}Please enter a valid account number{/s}',
                bankcode: '{s namespace=Paymill name=feedback_error_directdebit_bankcode}Please a valid bankcode.{/s}',
            };
            paymillcheckout.validateCvc = true;
            paymillcheckout.activeBrands = {$CreditcardBrands|@json_encode};
            paymillcheckout.iframe = {
                active: '{$paymillPCI}' === '0',
                options: {
                    lang: '{s namespace=Paymill name=paymill_lang}en{/s}'
                }
            };
            paymillcheckout.debug = {if $debug}true{else}false{/if};
            paymillcheckout.tokenAmount = '{$tokenAmount}';
            paymillcheckout.tokenCurrency = '{config name=currency|upper}';
            paymillcheckout.tokenPayment = '{$sPayment.name}';
            paymillcheckout.fastcheckout = new Object();
            paymillcheckout.fastcheckout.creditcard = {
                cardnumber: '{$paymillCardNumber}',
                month: '{$paymillMonth}',
                year: '{$paymillYear}',
            };
            paymillcheckout.fastcheckout.directdebit = {
                accountnumber: '{$paymillAccountNumber}',
                bankcode: '{$paymillBankCode}',
            };
            paymillcheckout.fastcheckout.changed = false;
        </script>
        <script type="text/javascript" src="https://bridge.paymill.com/dss3"></script>
    <div class="panel has--border">
        <div class="panel--title is--underline">PAYMILL {$sPayment.description}</div>
        <div class="panel--body is--rounded">
            <div class="error" style="display: none">
                {if $sPayment.name == 'paymillcc'}
                    {include file="frontend/_includes/messages.tpl" type="warning" content="{s namespace=Paymill name=feedback_error_creditcard_parent}Please enter your credit card information. For security reason we will not save them on our system.{/s}"}
                    {include file="frontend/_includes/messages.tpl" type="error" content="<ul id='errorsCc'></ul>"}
                {/if}

                {if $sPayment.name == 'paymilldebit'}
                    {include file="frontend/_includes/messages.tpl" type="warning" content="{s namespace=Paymill name=feedback_error_directdebit_parent}Please enter your accountdata. For security reason we will not save them on our system.{/s}"}
                    {include file="frontend/_includes/messages.tpl" type="error" content="<ul id='errorsElv'></ul>"}
                {/if}
            </div >
            {if $sPayment.name === 'paymillcc'}
                {if {config name=paymillBrandIconAmex}}<div class="paymill-card-icon paymill-card-number-amex"></div>{/if}
                {if {config name=paymillBrandIconCartaSi}}<div class="paymill-card-icon paymill-card-number-carta-si"></div>{/if}
                {if {config name=paymillBrandIconCarteBleue}}<div class="paymill-card-icon paymill-card-number-carte-bleue"></div>{/if}
                {if {config name=paymillBrandIconDankort}}<div class="paymill-card-icon paymill-card-number-dankort"></div>{/if}
                {if {config name=paymillBrandIconDinersclub}}<div class="paymill-card-icon paymill-card-number-diners-club"></div>{/if}
                {if {config name=paymillBrandIconDiscover}}<div class="paymill-card-icon paymill-card-number-discover"></div>{/if}
                {if {config name=paymillBrandIconJcb}}<div class="paymill-card-icon paymill-card-number-jcb"></div>{/if}
                {if {config name=paymillBrandIconMaestro}}<div class="paymill-card-icon paymill-card-number-maestro"></div>{/if}
                {if {config name=paymillBrandIconMastercard}}<div class="paymill-card-icon paymill-card-number-mastercard"></div>{/if}
                {if {config name=paymillBrandIconVisa}}<div class="paymill-card-icon paymill-card-number-visa"></div>{/if}
                {if {config name=paymillBrandIconUnionpay}}<div class="paymill-card-icon paymill-card-number-china-unionpay"></div>{/if}
                {if $paymillPCI === '0'}
                    {include file='frontend/paymill/payment/paymill_cc_saq.tpl'}
                {else}
                    {include file='frontend/paymill/payment/paymill_cc_saq_ep.tpl'}
                {/if}
            {elseif $sPayment.name === 'paymilldebit'}
                {include file='frontend/paymill/payment/paymill_debit.tpl'}
            {/if}
        </div>
    </div>
    {/if}
{/block}
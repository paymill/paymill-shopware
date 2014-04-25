<link rel = "stylesheet" type = "text/css" href = "{link file='frontend/_resources/paymill_styles.css'}" />
<script type = "text/javascript" >
    var PAYMILL_PUBLIC_KEY = '{$publicKey}';
    var VALIDATE_CVC = true;
    var API_ERRORS = new Array();
    API_ERRORS["PAYMILL_internal_server_error"] = '{s namespace=Paymill name=PAYMILL_internal_server_error}{/s}';
    API_ERRORS["PAYMILL_invalid_public_key"] = '{s namespace=Paymill name=PAYMILL_invalid_public_key}{/s}';
    API_ERRORS["PAYMILL_invalid_payment_data"] = '{s namespace=Paymill name=PAYMILL_invalid_payment_data}{/s}';
    API_ERRORS["PAYMILL_unknown_error"] = '{s namespace=Paymill name=PAYMILL_unknown_error}{/s}';
    API_ERRORS["PAYMILL_3ds_cancelled"] = '{s namespace=Paymill name=PAYMILL_3ds_cancelled}{/s}';
    API_ERRORS["PAYMILL_field_invalid_card_number"] = '{s namespace=Paymill name=PAYMILL_field_invalid_card_number}{/s}';
    API_ERRORS["PAYMILL_field_invalid_card_exp_year"] = '{s namespace=Paymill name=PAYMILL_field_invalid_card_exp_year}{/s}';
    API_ERRORS["PAYMILL_field_invalid_card_exp_month"] = '{s namespace=Paymill name=PAYMILL_field_invalid_card_exp_month}{/s}';
    API_ERRORS["PAYMILL_field_invalid_card_exp"] = '{s namespace=Paymill name=PAYMILL_field_invalid_card_exp}{/s}';
    API_ERRORS["PAYMILL_field_invalid_card_cvc"] = '{s namespace=Paymill name=PAYMILL_field_invalid_card_cvc}{/s}';
    API_ERRORS["PAYMILL_field_invalid_card_holder"] = '{s namespace=Paymill name=PAYMILL_field_invalid_card_holder}{/s}';
    API_ERRORS["PAYMILL_field_invalid_amount_int"] = '{s namespace=Paymill name=PAYMILL_field_invalid_amount_int}{/s}';
    API_ERRORS["PAYMILL_field_field_invalid_amount"] = '{s namespace=Paymill name=PAYMILL_field_field_invalid_amount}{/s}';
    API_ERRORS["PAYMILL_field_field_field_invalid_currency"] = '{s namespace=Paymill name=PAYMILL_field_field_field_invalid_currency}{/s}';
    API_ERRORS["PAYMILL_field_invalid_account_number"] = '{s namespace=Paymill name=PAYMILL_field_invalid_account_number}{/s}';
    API_ERRORS["PAYMILL_field_invalid_account_holder"] = '{s namespace=Paymill name=PAYMILL_field_invalid_account_holder}{/s}';
    API_ERRORS["PAYMILL_field_invalid_bank_code"] = '{s namespace=Paymill name=PAYMILL_field_invalid_bank_code}{/s}';
    API_ERRORS["PAYMILL_field_invalid_iban"] = '{s namespace=Paymill name=PAYMILL_field_invalid_iban}{/s}';
    API_ERRORS["PAYMILL_field_invalid_bic"] = '{s namespace=Paymill name=PAYMILL_field_invalid_bic}{/s}';
    API_ERRORS["PAYMILL_field_invalid_country"] = '{s namespace=Paymill name=PAYMILL_field_invalid_country}{/s}';
    API_ERRORS["PAYMILL_field_invalid_bank_data"] = '{s namespace=Paymill name=PAYMILL_field_invalid_bank_data}{/s}';
</script >
<script type = "text/javascript" src = "https://bridge.paymill.com/" ></script >
<script type = "text/javascript" src = "{link file='frontend/_resources/javascript/Iban.js'}" ></script >
<script type = "text/javascript" src = "{link file='frontend/_resources/javascript/BrandDetection.js'}" ></script >
<script type = "text/javascript" >
function debug(message)
{
    {if $debug}
    console.log("[" + getPayment() + "] " + message);
    {/if}
}
function isSepaActive()
{
    sepaActive = "{config name=paymillSepaActive}";
    return sepaActive == 1;
}
function getPayment()
{
    return "{$sPayment.name}";
}
function hasDummyData()
{
    if (getPayment() === 'paymillcc') {
        var cardNumber = $('#card-number').val();
        var validMonth = $('#card-expiry-month').val();
        var validYear = $('#card-expiry-year').val();

        debug(cardNumber);
        debug(validMonth);
        debug(validYear);

        if ((cardNumber === "" || validMonth === "" || validYear === "") || ("{$paymillCardNumber}" !== cardNumber) || ("{$paymillMonth}" !== validMonth) || ("{$paymillYear}" !== validYear)) {
            debug("Creditcard information found. New Information will be used. Token should be getting generated.");
            return false;
        }

    }

    if (getPayment() === 'paymilldebit') {
        if (isSepaActive()) {
            var iban = $('#paymill_iban').val();
            var bic = $('#paymill_bic').val();
            if ((iban === "" || bic === "") || ("{$paymillIban}" !== iban) || ("{$paymillBic}" !== bic)) {
                debug("Direct Debit information found. New Information will be used. Token should be getting generated.");
                return false;
            }
        } else {
            var accountNumber = $('#paymill_accountnumber').val();
            var bankCode = $('#paymill_banknumber').val();
            if ((accountNumber === "" || bankCode === "") || ("{$paymillAccountNumber}" !== accountNumber) || ("{$paymillBankCode}" !== bankCode)) {
                debug("Direct Debit information found. New Information will be used. Token should be getting generated.");
                return false;
            }
        }
    }
    debug("Fast Checkout Data found and not altered. Will process with given data. Validation will be skipped.");
    return true;
}
function validate()
{
    debug("Paymill handler triggered");
    var errorsCc = $("#errorsCc");
    errorsCc.parent().hide();
    errorsCc.html("");
    var errorsElv = $("#errorsElv");
    errorsElv.parent().hide();
    errorsElv.html("");
    var result = true;
    if (getPayment() === 'paymillcc') { //If CC
        if (!paymill.validateHolder($('#card-holder').val())) {
            errorsCc.append("<li>{s namespace=Paymill name=feedback_error_creditcard_holder}Please enter the cardholders name.{/s}</li>");
            result = false;
        }
        if (!paymill.validateCardNumber($('#card-number').val())) {
            errorsCc.append("<li>{s namespace=Paymill name=feedback_error_creditcard_number}Please enter a valid creditcardnumber.{/s}</li>");
            result = false;
        }
        if (!paymill.validateCvc($('#card-cvc').val())) {
            if (VALIDATE_CVC) {
                errorsCc.append("<li>{s namespace=Paymill name=feedback_error_creditcard_cvc}Please enter a valid securecode (see back of creditcard).{/s}</li>");
                result = false;
            }
        }
        if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {

            errorsCc.append("<li>{s namespace=Paymill name=feedback_error_creditcard_valid}The expiry date is invalid.{/s}</li>");
            result = false;
        }
        if (!result) {
            errorsCc.parent().show();
        } else {
            debug("Validations successful");
        }
    }
    if (getPayment() === 'paymilldebit') { //If ELV
        if (!paymill.validateHolder($('#paymill_accountholder').val())) {
            errorsElv.append("<li>{s namespace=Paymill name=feedback_error_directdebit_holder}Please enter the account name.{/s}</li>");
            result = false;
        }
        if (isSepaActive()) {
            iban = new Iban();
            if (!iban.validate($('#paymill_iban').val())) {
                errorsElv.append("<li>{s namespace=Paymill name=feedback_error_sepa_iban}Please enter a valid iban{/s}</li>");
                result = false;
            }

            if ($('#paymill_bic').val() === '') {
                errorsElv.append("<li>{s namespace=Paymill name=feedback_error_sepa_bic}Please a valid bic.{/s}</li>");
                result = false;
            }
        } else {
            if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
                errorsElv.append("<li>{s namespace=Paymill name=feedback_error_directdebit_number}Please enter a valid account number{/s}</li>");
                result = false;
            }

            if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
                errorsElv.append("<li>{s namespace=Paymill name=feedback_error_directdebit_bankcode}Please a valid bankcode.{/s}</li>");
                result = false;
            }
        }
        if (!result) {
            errorsElv.parent().show();
        } else {
            debug("Validations successful");
        }
    }
    return result;
}
$(document).ready(function ()
{
    var paymill_form_id = "payment_mean{$payment_mean.id}";

    $('#card-number').keyup(function ()
    {
        $("#card-number")[0].className = $("#card-number")[0].className.replace(/paymill-card-number-.*/g, '');
        var detector = new BrandDetection();
        var brand = detector.detect($('#card-number').val());
        console.log("Brand detected: " + brand);

        if (detector.validate($('#card-number').val())) {
            suffix = '';
        } else {
            suffix = '-temp';
        }

        switch (brand) {
            case 'unknown':
                $("#card-number")[0].className = $("#card-number")[0].className.replace(/paymill-card-number-.*/g, '');
                break;
            case 'carte bleue':
                $("#card-number")[0].className = $("#card-number")[0].className.replace(/paymill-card-number-.*/g, '');
                $('#card-number').addClass("paymill-card-number-" + 'carte-bleue' + suffix);
                break;
            case 'maestro':
                VALIDATE_CVC = false;
            case 'dankort':
            case 'carta-si':
            case 'discover':
            case 'jcb':
            case 'amex':
            case 'china-unionpay':
            case 'diners-club':
            case 'mastercard':
            case 'visa':
                $("#card-number")[0].className = $("#card-number")[0].className.replace(/paymill-card-number-.*/g, '');
                $('#card-number').addClass("paymill-card-number-" + brand + suffix);
                break;

        }
    });

    $("#basketButton").click(function ()
    {
        if ($('#' + paymill_form_id).attr("checked") === "checked") {
            if ($("input[type='checkbox'][name='sAGB']").length) {
                if ($("input[type='checkbox'][name='sAGB']").attr('checked') !== "checked") {
                    $("input[type='checkbox'][name='sAGB']").next('label').addClass('instyle_error');
                    $('html, body').animate({
                        scrollTop: $("input[type='checkbox'][name='sAGB']").offset().top - 100
                    }, 1000);
                    return false;
                }
            }
            if (hasDummyData()) {
                var form = $("#basketButton").closest('form');
                form.get(0).submit();
            } else {
                if (validate()) {
                    try {
                        if (getPayment() === 'paymillcc') { //If CC
                            if (VALIDATE_CVC) {
                                paymill.createToken({
                                    number:     $('#card-number').val(),
                                    cardholder: $('#card-holder').val(),
                                    exp_month:  $('#card-expiry-month').val(),
                                    exp_year:   $('#card-expiry-year').val(),
                                    cvc:        $('#card-cvc').val(),
                                    amount_int: '{$tokenAmount}',
                                    currency:   '{config name=currency|upper}'
                                }, PaymillResponseHandler);
                            } else {
                                cvcInput = $('#card-cvc').val();
                                paymill.createToken({
                                    number:     $('#card-number').val(),
                                    cardholder: $('#card-holder').val(),
                                    exp_month:  $('#card-expiry-month').val(),
                                    exp_year:   $('#card-expiry-year').val(),
                                    cvc:        cvcInput === "" ? "000" : cvcInput,
                                    amount_int: '{$tokenAmount}',
                                    currency:   '{config name=currency|upper}'
                                }, PaymillResponseHandler);
                            }
                        }
                        if (getPayment() === 'paymilldebit') { //If ELV
                            if (isSepaActive()) {
                                paymill.createToken({
                                    iban:          $('#paymill_iban').val(),
                                    bic:           $('#paymill_bic').val(),
                                    accountholder: $('#paymill_accountholder').val()
                                }, PaymillResponseHandler);
                            } else {
                                paymill.createToken({
                                    number:        $('#paymill_accountnumber').val(),
                                    bank:          $('#paymill_banknumber').val(),
                                    accountholder: $('#paymill_accountholder').val()
                                }, PaymillResponseHandler);
                            }
                        }
                    } catch (e) {
                        alert("Ein Fehler ist aufgetreten: " + e);
                    }
                } else {
                    if (getPayment() === 'paymillcc') {
                        $('html, body').animate({
                            scrollTop: $("#errorsCc").offset().top - 100
                        }, 1000);
                    }
                    if (getPayment() === 'paymilldebit') {
                        $('html, body').animate({
                            scrollTop: $("#errorsElv").offset().top - 100
                        }, 1000);
                    }
                }
            }
            return false;
        }
    });
});
function PaymillResponseHandler(error, result)
{
    debug("Started Paymill response handler");
    if (error) {
        errorText = API_ERRORS["PAYMILL_" + error.apierror];
        debug(errorText);
        alert(errorText);
    } else {
        debug("Received token from Paymill API: " + result.token);
        var form = $("#basketButton").closest('form');
        var token = result.token;
        form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
        form.get(0).submit();
    }
}

function detectCreditcardBranding(creditcardNumber)
{
    var brand = 'unknown';
    if (creditcardNumber.length > 5) {
        switch (true) {
            case /^(415006|497|407497|513)/.test(creditcardNumber):
                brand = "carte bleue";
                break;
            case /^(45399[78]|432913|5255)/.test(creditcardNumber):
                brand = "carta si";
                break;
            case /^(4571|5019)/.test(creditcardNumber):
                brand = "dankort";
                break;
            case /^(62|88)/.test(creditcardNumber):
                brand = "china unionpay";
                break;
            case /^6(011|5)/.test(creditcardNumber):
                brand = "discover";
                break;
            case /^3(0[0-5]|[68])/.test(creditcardNumber):
                brand = "diners club";
                break;
            case /^(5018|5020|5038|5893|6304|6759|6761|6762|6763|0604|6390)/.test(creditcardNumber):
                brand = "maestro";
                break;
            case /^(2131|1800|35)/.test(creditcardNumber):
                brand = "jcb";
                break;
            case /^(3[47])/.test(creditcardNumber):
                brand = "amex";
                break;
            case /^(5[1-5])/.test(creditcardNumber):
                brand = "mastercard";
                break;
            case /^(4)/.test(creditcardNumber):
                brand = "visa";
                break;
        }
    }
    return brand;
}
</script >

<div class = "error" style = "display: none" >
    {if $payment_mean.name == 'paymillcc'}
        <li >{s namespace=Paymill name=feedback_error_creditcard_parent}Please enter your credit card information. For security reason we will not save them on our system.{/s}</li >
        <ul id = "errorsCc" ></ul >
    {/if}

    {if $payment_mean.name == 'paymilldebit'}
        <li >{s namespace=Paymill name=feedback_error_directdebit_parent}Please enter your accountdata. For security reason we will not save them on our system.{/s}</li >
        <ul id = "errorsElv" ></ul >
    {/if}

</div >
{if $Controller != "account"}
    <div class = "debit" >
        {if $payment_mean.name == 'paymillcc'}
            {if $showBrandIconLabel}{s namespace=Paymill name=frontend_creditcard_label_icons}The following Creditcard are supported{/s}:<br>{/if}
            {if {config name=paymillBrandIconVisa}}<div class="paymill-card-icon paymill-card-number-visa"></div>{/if}
            {if {config name=paymillBrandIconMastercard}}<div class="paymill-card-icon paymill-card-number-mastercard"></div>{/if}
            {if {config name=paymillBrandIconAmex}}<div class="paymill-card-icon paymill-card-number-amex"></div>{/if}
            {if {config name=paymillBrandIconJcb}}<div class="paymill-card-icon paymill-card-number-jcb"></div>{/if}
            {if {config name=paymillBrandIconMaestro}}<div class="paymill-card-icon paymill-card-number-maestro"></div>{/if}
            {if {config name=paymillBrandIconDinersclub}}<div class="paymill-card-icon paymill-card-number-diners-club"></div>{/if}
            {if {config name=paymillBrandIconDiscover}}<div class="paymill-card-icon paymill-card-number-discover"></div>{/if}
            {if {config name=paymillBrandIconUnionpay}}<div class="paymill-card-icon paymill-card-number-china-unionpay"></div>{/if}
            {if {config name=paymillBrandIconDankort}}<div class="paymill-card-icon paymill-card-number-dankort"></div>{/if}
            {if {config name=paymillBrandIconCartaSi}}<div class="paymill-card-icon paymill-card-number-carta-si"></div>{/if}
            {if {config name=paymillBrandIconCarteBleue}}<div class="paymill-card-icon paymill-card-number-carte-bleue"></div>{/if}


            {if $pigmbhTemplateActive == 1}
                <div class = "form-group" >
                    <label class = "col-lg-4 control-label"
                           for = "card-holder" >{s namespace=Paymill name=frontend_creditcard_label_holder}Credit Card Holder{/s} *</label >

                    <div class = "col-lg-6" >
                        <input id = "card-holder" type = "text" size = "20" class = "form-control"
                               value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
                    </div >
                </div >
                <div class = "form-group" >
                    <label class = "col-lg-4 control-label"
                           for = "card-number" >{s namespace=Paymill name=frontend_creditcard_label_number}Credit Card Number{/s} *</label >

                    <div class = "col-lg-6" >
                        <input id = "card-number" type = "text" size = "20" class = "form-control"
                               value = "{$paymillCardNumber}" />
                    </div >
                </div >
                <div class = "form-group" >
                <span class = "col-lg-4 control-label" >
                <label for = "card-cvc" >{s namespace=Paymill name=frontend_creditcard_label_cvc}CVC {/s}</label >
                <span class = "paymill-tooltip"
                      title = "{s namespace=Paymill name=frontend_creditcard_tooltip_cvc}What is a CVV/CVC number? Prospective credit cards will have a 3 to 4-digit number, usually on the back of the card. It ascertains that the payment is carried out by the credit card holder and the card account is legitimate. On Visa the CVV (Card Verification Value) appears after and to the right of your card number. Same goes for Mastercard's CVC (Card Verfication Code), which also appears after and to the right of  your card number, and has 3-digits. Diners Club, Discover, and JCB credit and debit cards have a three-digit card security code which also appears after and to the right of your card number. The American Express CID (Card Identification Number) is a 4-digit number printed on the front of your card. It appears above and to the right of your card number. On Maestro the CVV appears after and to the right of your number. If you don’t have a CVV for your Maestro card you can use 000.{/s}" >?</span >
                </span >

                    <div class = "col-lg-6" >
                        <input id = "card-cvc" type = "text" size = "20" class = "form-control"
                               value = "{$paymillCvc}" />
                    </div >
                </div >
                <div class = "form-group" >
                    <label class = "col-lg-4 control-label"
                           for = "card-expiry-month" >{s namespace=Paymill name=frontend_creditcard_label_valid}Valid until (MM/YYYY){/s} *</label >

                    <div class = "col-lg-6" >
                        <input id = "card-expiry-month" type = "text" size = "5" class = "form-control"
                               style = "width: 25%; display: inline-block;"
                               value = "{$paymillMonth}" />
                        <input id = "card-expiry-year" type = "text" size = "5" class = "form-control"
                               style = "width: 25%; display: inline-block;"
                               value = "{$paymillYear}" />

                    </div >
                </div >
            {else}
                <p class = "none" >
                    <label >{s namespace=Paymill name=frontend_creditcard_label_holder}Credit Card Holder *{/s}</label >
                    <input id = "card-holder" type = "text" size = "20" class = "text"
                           value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
                </p >
                <p class = "none" >
                    <label >{s namespace=Paymill name=frontend_creditcard_label_number}Credit Card Number *{/s}</label >
                    <input id = "card-number" type = "text" size = "20" class = "text"
                           value = "{$paymillCardNumber}" />
                </p >
                <p class = "none" >
                    <label >{s namespace=Paymill name=frontend_creditcard_label_cvc}CVC *{/s}</label >
                    <input id = "card-cvc" type = "text" size = "4" class = "text" value = "{$paymillCvc}" />
                <span class = "paymill-tooltip"
                      title = "{s namespace=Paymill name=frontend_creditcard_tooltip_cvc} What is a CVV/CVC number? Prospective credit cards will have a 3 to 4-digit number, usually on the back of the card. It ascertains that the payment is carried out by the credit card holder and the card account is legitimate. On Visa the CVV (Card Verification Value) appears after and to the right of your card number. Same goes for Mastercard's CVC (Card Verfication Code), which also appears after and to the right of  your card number, and has 3-digits. Diners Club, Discover, and JCB credit and debit cards have a three-digit card security code which also appears after and to the right of your card number. The American Express CID (Card Identification Number) is a 4-digit number printed on the front of your card. It appears above and to the right of your card number. On Maestro the CVV appears after and to the right of your number. If you don’t have a CVV for your Maestro card you can use 000.{/s}" >?</span >
                </p >
                <p class = "none" >
                    <label >{s namespace=Paymill name=frontend_creditcard_label_valid}Valid until (MM/YYYY) *{/s}</label >
                    <input id = "card-expiry-month" type = "text" style = "width: 30px; display: inline-block;"
                           class = "text"
                           value = "{$paymillMonth}" />
                    <input id = "card-expiry-year" type = "text" style = "width: 60px; display: inline-block;"
                           class = "text"
                           value = "{$paymillYear}" />
                </p >
            {/if}
        {/if}

        {if $payment_mean.name == 'paymilldebit' }
            {if $pigmbhTemplateActive == 1}
                <div class = "form-group" >
                    <label class = "col-lg-4 control-label"
                           for = "paymill_accountholder" >{s namespace=Paymill name=frontend_directdebit_label_holder}Account Holder{/s} *</label >

                    <div class = "col-lg-6" >
                        <input id = "paymill_accountholder" type = "text" size = "20" class = "form-control"
                               value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
                    </div >
                </div >
                {if {config name=paymillSepaActive}}
                    <div class = "form-group" >
                        <label class = "col-lg-4 control-label"
                               for = "paymill_iban" >{s namespace=Paymill name=frontend_directdebit_label_iban}IBAN{/s} *</label >

                        <div class = "col-lg-6" >
                            <input id = "paymill_iban" type = "text" size = "20" class = "form-control"
                                   value = "{$paymillIban}" />
                        </div >
                    </div >
                    <div class = "form-group" >
                        <label class = "col-lg-4 control-label"
                               for = "paymill_bic" >{s namespace=Paymill name=frontend_directdebit_label_bic}BIC{/s} *</label >

                        <div class = "col-lg-6" >
                            <input id = "paymill_bic" type = "text" size = "20" class = "form-control"
                                   value = "{$paymillBic}" />
                        </div >
                    </div >
                {else}
                    <div class = "form-group" >
                        <label class = "col-lg-4 control-label"
                               for = "paymill_accountnumber" >{s namespace=Paymill name=frontend_directdebit_label_number}Account Number{/s} *</label >

                        <div class = "col-lg-6" >
                            <input id = "paymill_accountnumber" type = "text" size = "20" class = "form-control"
                                   value = "{$paymillAccountNumber}" />
                        </div >
                    </div >
                    <div class = "form-group" >
                        <label class = "col-lg-4 control-label"
                               for = "paymill_banknumber" >{s namespace=Paymill name=frontend_directdebit_label_bankcode}Bankcode{/s} *</label >

                        <div class = "col-lg-6" >
                            <input id = "paymill_banknumber" type = "text" size = "20" class = "form-control"
                                   value = "{$paymillBankCode}" />
                        </div >
                    </div >
                {/if}
            {else}
                <p class = "none" >
                    <label for = "paymill_accountholder" >{s namespace=Paymill name=frontend_directdebit_label_holder}Account Holder{/s} *</label >
                    <input id = "paymill_accountholder" type = "text" size = "20" class = "text"
                           value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
                </p >
                {if {config name=paymillSepaActive}}
                    <p class = "none" >
                        <label for = "paymill_iban" >{s namespace=Paymill name=frontend_directdebit_label_iban}IBAN{/s} *</label >
                        <input id = "paymill_iban" type = "text" size = "4" class = "text"
                               value = "{$paymillIban}" />
                    </p >
                    <p class = "none" >
                        <label for = "paymill_bic" >{s namespace=Paymill name=frontend_directdebit_label_bic}BIC{/s} *</label >
                        <input id = "paymill_bic" type = "text" size = "4" class = "text"
                               value = "{$paymillBic}" />
                    </p >
                {else}
                    <p class = "none" >
                        <label for = "paymill_accountnumber" >{s namespace=Paymill name=frontend_directdebit_label_number}Account Number{/s} *</label >
                        <input id = "paymill_accountnumber" type = "text" size = "4" class = "text"
                               value = "{$paymillAccountNumber}" />
                    </p >
                    <p class = "none" >
                        <label for = "paymill_banknumber" >{s namespace=Paymill name=frontend_directdebit_label_bankcode}Bankcode{/s} *</label >
                        <input id = "paymill_banknumber" type = "text" size = "4" class = "text"
                               value = "{$paymillBankCode}" />
                    </p >
                {/if}
            {/if}
        {/if}
        {if ($payment_mean.name == 'paymilldebit') || ($payment_mean.name == 'paymillcc')}
            <p class = "description" >{s namespace=Paymill name=feedback_info_general_required}Fields marked with a * are required.{/s}</p >
        {/if}
    </div >
{/if}

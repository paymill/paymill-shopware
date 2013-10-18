<link rel = "stylesheet" type = "text/css" href = "{link file='frontend/_resources/paymill_styles.css'}" />
<script type = "text/javascript" >
    var PAYMILL_PUBLIC_KEY = '{config name=publicKey|replace:' ':''}';
    var VALIDATE_CVC = true;
</script >
<script type = "text/javascript" src = "https://bridge.paymill.com/" ></script >
<script type = "text/javascript" >
    function debug(message)
    {
        {if $debug}
        console.log("[" + getPayment() + "] " + message);
        {/if}
    }
    function getPayment()
    {
        return "{$sPayment.name}";
    }
    function hasDummyData()
    {
        if(getPayment() === 'paymillcc'){
            var cardNumber = $('#card-number').val();
            var validMonth = $('#card-expiry-month').val();
            var validYear = $('#card-expiry-year').val();

            debug(cardNumber);
            debug(validMonth);
            debug(validYear);

            if((cardNumber === "" || validMonth === "" || validYear === "") ||
            ("{$paymillCardNumber}" !== cardNumber) ||
            ("{$paymillMonth}" !== validMonth) ||
            ("{$paymillYear}" !== validYear)){
                debug("Creditcard information found. New Information will be used. Token should be getting generated.");
                return false;
            }

        }

        if(getPayment() === 'paymilldebit'){
            var accountNumber = $('#paymill_accountnumber').val();
            var bankCode = $('#paymill_banknumber').val();
            if((accountNumber === "" || bankCode === "") ||
            ("{$paymillAccountNumber}" !== accountNumber) ||
            ("{$paymillBankCode}" !== bankCode)){
                debug("Direct Debit information found. New Information will be used. Token should be getting generated.");
                return false;
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
            if (!paymill.validateCardNumber($('#card-number').val())) {
                errorsCc.append("<li>{s namespace=Paymill name=paymill_error_text_invalid_number_cc}Bitte geben Sie eine g&uuml;ltige Kartennummer ein{/s}</li>");
                result = false;
            }
            if (!paymill.validateCvc($('#card-cvc').val())) {
                if(VALIDATE_CVC){
                    errorsCc.append("<li>{s namespace=Paymill name=paymill_error_text_invalid_cvc}Bitte geben sie einen g&uuml;ltigen Sicherheitscode ein (R&uuml;ckseite der Karte).{/s}</li>");
                    result = false;
                }
            }
            if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {

                errorsCc.append("<li>{s namespace=Paymill name=paymill_error_text_invalid_expdate}Das Ablaufdatum der Karte ist ung&uuml;ltig.{/s}</li>");
                result = false;
            }
            if (!result) {
                errorsCc.parent().show();
            } else {
                debug("Validations successful");
            }
        }
        if (getPayment() === 'paymilldebit') { //If ELV
            if (!$('#paymill_accountholder').val()) {
                errorsElv.append("<li>{s namespace=Paymill name=paymill_error_text_invalid_holder_elv}Bitte geben Sie den Kontoinhaber an.{/s}</li>");
                result = false;
            }
            if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
                errorsElv.append("<li>{s namespace=Paymill name=paymill_error_text_invalid_number_elv}Bitte geben Sie eine g&uuml;ltige Kontonummer ein.{/s}</li>");
                result = false;
            }

            if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
                errorsElv.append("<li>{s namespace=Paymill name=paymill_error_text_invalid_bankcode}Bitte geben Sie eine g&uuml;ltige BLZ ein.{/s}</li>");
                result = false;
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
            var brand = paymill.cardType($('#card-number').val());
            brand = brand.toLowerCase();
            $("#card-number")[0].className = $("#card-number")[0].className.replace(/paymill-card-number-.*/g, '');
            $('#card-cvc').val("");
            if (brand !== 'unknown') {
                $('#card-number').addClass("paymill-card-number-" + brand);
            }
            if(brand === 'maestro'){
                VALIDATE_CVC = false;
            }
        });

        $("#basketButton").click(function (event)
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
                    var form = $("#basketButton").parent().parent();
                    form.get(0).submit();
                }
                else {
                    if (validate()) {
                        try {
                            if (getPayment() === 'paymillcc') { //If CC
                                if(VALIDATE_CVC){
                                    paymill.createToken({
                                        number:     $('#card-number').val(),
                                        cardholder: $('#account-holder').val(),
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
                                        cardholder: $('#account-holder').val(),
                                        exp_month:  $('#card-expiry-month').val(),
                                        exp_year:   $('#card-expiry-year').val(),
                                        cvc:        cvcInput === "" ? "000" : cvcInput,
                                        amount_int: '{$tokenAmount}',
                                        currency:   '{config name=currency|upper}'
                                    }, PaymillResponseHandler);
                                }
                            }
                            if (getPayment() === 'paymilldebit') { //If ELV
                                paymill.createToken({
                                    number:        $('#paymill_accountnumber').val(),
                                    bank:          $('#paymill_banknumber').val(),
                                    accountholder: $('#paymill_accountholder').val()
                                }, PaymillResponseHandler);
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
            debug("API returned error:" + error.apierror);
            alert("API returned error:" + error.apierror);
        } else {
            debug("Received token from Paymill API: " + result.token);
            var form = $("#basketButton").parent().parent();
            var token = result.token;
            form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
            form.get(0).submit();
        }
    }
</script >

<div class = "error" style = "display: none" >
    {if $payment_mean.name == 'paymillcc'}
        <li >{s namespace=Paymill name=paymill_error_text_generic_cc}Bitte geben Sie Ihre Kreditkartendaten ein. Aus Sicherheitsgr&uuml;nden speichern wir diese Nicht auf unserem Server.{/s}</li >
        <ul id = "errorsCc" ></ul >
    {/if}

    {if $payment_mean.name == 'paymilldebit'}
        <li >{s namespace=Paymill name=paymill_error_text_generic_elv}Bitte geben Sie Ihre Bankdaten ein. Aus Sicherheitsgr&uuml;nden speichern wir diese Nicht auf unserem Server.{/s}</li >
        <ul id = "errorsElv" ></ul >
    {/if}

</div >
{if $Controller != "account"}
    <div class = "debit" >
        {if $payment_mean.name == 'paymillcc'}
            <p class = "none" >
                <label >{s namespace=Paymill name=paymill_frontend_form_holder_cc}Karteninhaber *{/s}</label >
                <input id = "account-holder" type = "text" size = "20" class = "text"
                       value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
            </p >
            <p class = "none" >
                <label >{s namespace=Paymill name=paymill_frontend_form_number_cc}Kreditkarten-nummer *{/s}</label >
                <input id = "card-number" type = "text" size = "20" class = "text"
                       value = "{$paymillCardNumber}" />
            </p >
            <p class = "none" >
                <label >{s namespace=Paymill name=paymill_frontend_form_cvc}CVC*{/s}</label >
                <input id = "card-cvc" type = "text" size = "4" class = "text"
                       value = "{$paymillCvc}" /><span class="tooltip" title="{s namespace=Paymill name=cvc_tooltip}Hinter dem CVV-Code bzw. CVC verbirgt sich ein Sicherheitsmerkmal von Kreditkarten, &uuml;blicherweise handelt es sich dabei um eine drei- bis vierstelligen Nummer. Der CVV-Code befindet sich auf VISA-Kreditkarten. Der gleiche Code ist auch auf MasterCard-Kreditkarten zu finden, hier allerdings unter dem Namen CVC. Die Abk&uuml;rzung CVC steht dabei für Card Validation Code. Bei VISA wird der Code als Card Verification Value-Code bezeichnet. &Auml;hnlich wie bei Mastercard und VISA gibt es auch bei Diners Club, Discover und JCB eine dreistellige  Nummer, die meist auf der R&uuml;ckseite der Karte zu finden ist. Bei Maestro-Karten gibt es mit und ohne dreistelligen CVV. Wird eine Maestro-Karte ohne CVV verwendet kann einfach 000 eingetragen werden. American Express verwendet die CID (Card Identification Number). Dabei handelt es sich um eine vierstellige Nummer, die meist auf der Vorderseite der Karte, rechts oberhalb der Kartennummer zu finden ist.{/s}">?</span>
            </p >
            <p class = "none" >
                <label >{s namespace=Paymill name=paymill_frontend_form_expdate}G&uuml;ltig bis (MM/YYYY) *{/s}</label >
                <input id = "card-expiry-month" type = "text" style = "width: 30px; display: inline-block;"
                       class = "text"
                       value = "{$paymillMonth}" />
                <input id = "card-expiry-year" type = "text" style = "width: 60px; display: inline-block;"
                       class = "text"
                       value = "{$paymillYear}" />
            </p >
        {/if}

        {if $payment_mean.name == 'paymilldebit' }
            <p class = "none" >
                <label >{s namespace=Paymill name=paymill_frontend_form_holder_elv}Kontoinhaber *{/s}</label >
                <input id = "paymill_accountholder" type = "text" size = "20" class = "text"
                       value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
            </p >
            <p class = "none" >
                <label >{s namespace=Paymill name=paymill_frontend_form_number_elv}Kontonummer *{/s}</label >
                <input id = "paymill_accountnumber" type = "text" size = "4" class = "text"
                       value = "{$paymillAccountNumber}" />
            </p >
            <p class = "none" >
                <label >{s namespace=Paymill name=paymill_frontend_form_bankcode}Bankleitzahl *{/s}</label >
                <input id = "paymill_banknumber" type = "text" size = "4" class = "text"
                       value = "{$paymillBankCode}" />
            </p >
        {/if}

        {if ($payment_mean.name == 'paymilldebit') || ($payment_mean.name == 'paymillcc')}
            <p class = "description" >{s namespace=Paymill name=paymill_frontend_form_info}Die mit einem * markierten Felder sind Pflichtfelder.{/s}</p >
        {/if}
        {if {config name=paymillShowLabel}}
            <p class = "none" >
            <div class = "paymill_powered" >
                <div class = "paymill_credits" >
                    {if $payment_mean.name == 'paymillcc'}
                        {s namespace=Paymill name=paymill_frontend_label_slogan}Sichere Kreditkartenzahlung powered by{/s}
                    {else}
                        {s namespace=Paymill name=paymill_frontend_label_slogan_elv}ELV powered by{/s}
                        <br />
                    {/if}
                    <a href = "http://www.paymill.de" target = "_blank" >Paymill</a >
                </div >
            </div >
            </p >
        {/if}
    </div >
{/if}

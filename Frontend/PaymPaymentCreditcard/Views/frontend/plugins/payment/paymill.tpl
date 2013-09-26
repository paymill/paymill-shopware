<link rel="stylesheet" type="text/css" href="{link file='frontend/_resources/paymill_styles.css'}" />
<script type="text/javascript">
    var PAYMILL_PUBLIC_KEY = '{config name=publicKey|replace:' ':''}';
</script>
<script type="text/javascript" src="https://bridge.paymill.com/"></script>
<script type="text/javascript">
    function debug(message) {
    {if $debug}
        console.log("[" + getPayment() + "] " + message);
    {/if}
    }
    function isCC() {
        return getPayment() === 'paymillcc';
    }
    function isELV() {
        return getPayment() === 'paymilldebit';
    }
    function getPayment() {
        return "{$sPayment.name}";
    }
    function validate() {
        debug("Paymill handler triggered");
        var errorsCc = $("#errorsCc");
        errorsCc.parent().hide();
        errorsCc.html("");
        var errorsElv = $("#errorsElv");
        errorsElv.parent().hide();
        errorsElv.html("");
        var result = true;
        if (isCC()) { //If CC
            if (!paymill.validateCardNumber($('#card-number').val())) {
                errorsCc.append("<li>{s namespace=Paymill name=invalid_cardnumber}Bitte geben Sie eine g&uuml;ltige Kartennummer ein{/s}</li>");
                result = false;
            }
            if (!paymill.validateCvc($('#card-cvc').val())) {
                errorsCc.append("<li>{s namespace=Paymill name=invalid_cvc}Bitte geben sie einen g&uuml;ltigen Sicherheitscode ein (R&uuml;ckseite der Karte).{/s}</li>");
                result = false;
            }
            if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {

                errorsCc.append("<li>{s namespace=Paymill name=invalid_expirydate}Das Ablaufdatum der Karte ist ung&uuml;ltig.{/s}</li>");
                result = false;
            }
            if (!result) {
                errorsCc.parent().show();
            } else {
                debug("Validations successful");
            }
        }
        if (isELV()) { //If ELV
            if (!$('#paymill_accountholder').val()) {
                errorsElv.append("<li>{s namespace=Paymill name=invalid_accountholder}Bitte geben Sie den Kontoinhaber an.{/s}</li>");
                result = false;
            }
            if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
                errorsElv.append("<li>{s namespace=Paymill name=invalid_accountnumber}Bitte geben Sie eine g&uuml;ltige Kontonummer ein.{/s}</li>");
                result = false;
            }

            if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
                errorsElv.append("<li>{s namespace=Paymill name=invalid_bankcode}Bitte geben Sie eine g&uuml;ltige BLZ ein.{/s}</li>");
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
    $(document).ready(function() {
        var paymill_form_id = "payment_mean{$payment_mean.id}";

        $('#card-number').keyup(function() {
            var brand = paymill.cardType($('#card-number').val());
            brand = brand.toLowerCase();
            $("#card-number")[0].className = $("#card-number")[0].className.replace(/paymill-card-number-.*/g, '');
            if (brand !== 'unknown') {
                $('#card-number').addClass("paymill-card-number-" + brand);
            }
        });

        $("#basketButton").click(function(event) {
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
                if (validate()) {
                    try {
                        if (isCC()) { //If CC
                            paymill.createToken({
                                number: $('#card-number').val(),
                                cardholder: $('#account-holder').val(),
                                exp_month: $('#card-expiry-month').val(),
                                exp_year: $('#card-expiry-year').val(),
                                cvc: $('#card-cvc').val(),
                                amount_int: '{$tokenAmount}',
                                currency: '{config name=currency|upper}'
                            }, PaymillResponseHandler);
                        }
                        if (isELV()) { //If ELV
                            paymill.createToken({
                                number: $('#paymill_accountnumber').val(),
                                bank: $('#paymill_banknumber').val(),
                                accountholder: $('#paymill_accountholder').val()
                            }, PaymillResponseHandler);
                        }
                    } catch (e) {
                        alert("Ein Fehler ist aufgetreten: " + e);
                    }
                } else {
                    if (isCC()) {
                        $('html, body').animate({
                            scrollTop: $("#errorsCc").offset().top - 100
                        }, 1000);
                    }
                    if (isELV()) {
                        $('html, body').animate({
                            scrollTop: $("#errorsElv").offset().top - 100
                        }, 1000);
                    }
                }
                return false;
            }
        });
    });
    function PaymillResponseHandler(error, result) {
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
</script>

<div class="error" style="display: none">
    {if $payment_mean.name == 'paymillcc'}
        <li>{s namespace=Paymill name=invalid_error_creditcard}Bitte geben Sie Ihre Kreditkartendaten ein. Aus Sicherheitsgr&uuml;nden speichern wir diese Nicht auf unserem Server.{/s}</li>
        <ul id="errorsCc"> </ul>
    {/if}

    {if $payment_mean.name == 'paymilldebit'}
        <li>{s namespace=Paymill name=invalid_error_debit}Bitte geben Sie Ihre Bankdaten ein. Aus Sicherheitsgr&uuml;nden speichern wir diese Nicht auf unserem Server.{/s}</li>
        <ul id="errorsElv"> </ul>
    {/if}

</div>
{if $Controller != "account"}
    <div class="debit">
        {if $ccHasFcData != 1}
            {if $payment_mean.name == 'paymillcc'}
                <p class="none">
                    <label>{s namespace=Paymill name=form_cardholder}Karteninhaber *{/s}</label>
                    <input id="account-holder" type="text" size="20" class="text" value="{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}"/>
                </p>
                <p class="none">
                    <label>{s namespace=Paymill name=form_cardnumber}Kreditkarten-nummer *{/s}</label>
                    <input id="card-number" type="text" size="20" class="text" />
                </p>
                <p class="none">
                    <label>{s namespace=Paymill name=form_cvc}CVC*{/s}</label>
                    <input id="card-cvc" type="text" size="4" class="text" />
                </p>
                <p class="none">
                    <label>{s namespace=Paymill name=form_expirydate}G&uuml;ltig bis (MM/YYYY) *{/s}</label>
                    <input id="card-expiry-month" type="text" style="width: 30px; display: inline-block;" class="text" />
                    <input id="card-expiry-year" type="text" style="width: 60px; display: inline-block;" class="text" />
                </p>
            {/if}
        {/if}
        {if $elvHasFcData != 1}
            {if $payment_mean.name == 'paymilldebit' }
                <p class="none">
                    <label>{s namespace=Paymill name=form_accountholder}Kontoinhaber *{/s}</label>
                    <input id="paymill_accountholder" type="text" size="20" class="text" value="{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
                </p>
                <p class="none">
                    <label>{s namespace=Paymill name=form_accountnumber}Kontonummer *{/s}</label>
                    <input id="paymill_accountnumber" type="text" size="4" class="text" />
                </p>
                <p class="none">
                    <label>{s namespace=Paymill name=form_bankcode}Bankleitzahl *{/s}</label>
                    <input id="paymill_banknumber" type="text" size="4" class="text" />
                </p>
            {/if}
                {/if}

                    {if ($payment_mean.name == 'paymilldebit' && $elvHasFcData != 1) || ($payment_mean.name == 'paymillcc' && $ccHasFcData != 1)}
         <p class="description">{s namespace=Paymill name=form_info}Die mit einem * markierten Felder sind Pflichtfelder.{/s}</p>
                    {/if}
                    {if {config name=paymillShowLabel}}
            <p>
                <div class="paymill_powered">
                    <div class="paymill_credits">
                        {if $payment_mean.name == 'paymillcc'}
                            {s namespace=Paymill name=form_paymilllabel_cc}Sichere Kreditkartenzahlung powered by{/s}
                        {else}
                            {s namespace=Paymill name=form_paymilllabel_debit}Lastschriftverfahren powered by{/s}
                        {/if}
                        <a href="http://www.paymill.de" target="_blank">Paymill</a>
                    </div>
                </div>
            </p>
                    {/if}
   </div>
                    {/if}

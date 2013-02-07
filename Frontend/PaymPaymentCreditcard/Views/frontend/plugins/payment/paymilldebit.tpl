<link rel="stylesheet" type="text/css" href="{link file='frontend/_resources/paymill_styles.css'}" />
<script type="text/javascript">
  var PAYMILL_PUBLIC_KEY = '{config name=publicKey}';
</script>
<script type="text/javascript" src="{config name=bridgeUrl}"></script>
<script type="text/javascript">
    function validate() {
        debugELV("Paymill handler triggered");
        var errors = $("#errorsdebit");
        errors.parent().hide();
        errors.html("");
        var result = true;
        if (!$('#paymill_accountholder').val()) {
          errors.append("<li>Bitte geben Sie den Kontoinhaber an.</li>");
          result = false;
        }
        if (!paymill.validateAccountNumber($('#paymill_accountnumber').val())) {
          errors.append("<li>Bitte geben Sie eine g&uuml;ltige Kontonummer ein.</li>");
          result = false;
        }
        if (!paymill.validateBankCode($('#paymill_banknumber').val())) {
          errors.append("<li>Bitte geben Sie eine g&uuml;ltige BLZ ein.</li>");
          result = false;
        }
        if (!result) {
            errors.parent().show();
        }else{
            debugELV("Validations successful");
        }
        return result;
    }
    $(document).ready(function() {
        var paymill_form_id = "payment_mean{$payment_mean.id}";
        $("#basketButton").click(function(event) {
            if ($('#' + paymill_form_id).attr("checked") == "checked") {
                if (validate()) {
                    try {
                        paymill.createToken({
                            number: $('#paymill_accountnumber').val(),
                            bank: $('#paymill_banknumber').val(),
                            accountholder: $('#paymill_accountholder').val()
                        }, PaymillResponseHandler);
                    } catch (e) {
                        alert("Ein Fehler ist aufgetreten: " + e);
                    }
                }else{
                    $('html, body').animate({
                        scrollTop: $("#errorsdebit").offset().top - 100
                    }, 1000);
                }
                return false;
            }
        });
    });
    function PaymillResponseHandler(error, result) {
        debugELV("Started Paymill response handler");
        if (error) {
            debugELV("API returned error:" + error.apierror);
            alert("API returned error:" + error.apierror);
        } else {
            debugELV("Received token from Paymill API: " + result.token);
            var form = $("#basketButton").parent().parent();
            var token = result.token;
            form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
            form.get(0).submit();
        }
    }
    function debugELV(message){
        {if $debug}
            console.log("[PaymillELV] " + message);
        {/if}
    }
</script>
<div class="error" style="display: none">
<ul id="errorsdebit">
</ul>
</div>
{if $paymillError == 1}
<div class="error">
    <ul>
        <li>Bitte geben Sie Ihre Bankdaten ein. Aus Sicherheitsgründen speichern wir diese Nicht auf unserem Server.</li>
    </ul>
</div>
{/if}
{if $Controller != "account"}
    <div class="debit">
    <p class="none">
        <label>Kontoinhaber *</label>
        <input id="paymill_accountholder" type="text" size="20" class="text" />
    </p>
    <p class="none">
        <label>Kontonummer *</label>
        <input id="paymill_accountnumber" type="text" size="4" class="text" />
    </p>
    <p class="none">
        <label>Bankleitzahl *</label>
        <input id="paymill_banknumber" type="text" size="4" class="text" />
    </p>
    <p class="description">Die mit einem * markierten Felder sind Pflichtfelder.
    </p>
    {if $paymillShowLabel == 1}
    <p><div class="paymill_powered"><div class="paymill_credits">Sichere Kreditkartenzahlung powered by <a href="http://www.paymill.de" target="_blank">Paymill</a></div></div></p>
    {/if}
</div>
{/if}

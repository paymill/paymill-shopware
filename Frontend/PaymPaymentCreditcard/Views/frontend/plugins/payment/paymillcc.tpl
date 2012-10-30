<link rel="stylesheet" type="text/css" href="{link file='frontend/_resources/paymill_styles.css'}" />
<script type="text/javascript">
  var PAYMILL_PUBLIC_KEY = '{$publicKey}';
</script>
<script type="text/javascript" src="{$bridgeUrl}"></script>
<script type="text/javascript">
    function validate() {
        var errors = $("#errors");
        errors.parent().hide();
        errors.html("");
        var result = true;
        if (!paymill.validateCardNumber($('#card-number').val())) {
          errors.append("<li>Bitte geben Sie eine gültige Kartennummer ein</li>");
          result = false;
        }
        if (!paymill. validateCvc($('#card-cvc').val())) {
          errors.append("<li>Bitte geben sie einen gültigen Sicherheitscode ein (Rückseite der Karte).</li>");
          result = false;
        }
        if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {
          errors.append("<li>Das Ablaufdatum der Karte ist ungültig.</li>");
          result = false;
        }
        if (!result) {
            errors.parent().show();
        }
        return result;
    }
    $(document).ready(function() {
        var paymill_form_id = "payment_mean{$payment_mean.id}";
        $("form.payment").submit(function(event) {
            if ($('#' + paymill_form_id).attr("checked") == "checked") {
                if (validate()) {
                    try {
                        paymill.createToken({
                            number: $('#card-number').val(), 
                            cardholder: "Test",
                            exp_month: $('#card-expiry-month').val(), 
                            exp_year: $('#card-expiry-year').val(), 
                            cvc: $('#card-cvc').val()
                        }, PaymillResponseHandler);
                    } catch (e) {
                        alert("Ein Fehler ist aufgetreten: " + e);
                    }
                } 
                return false;
            }
        });
    });
    function PaymillResponseHandler(error, result) {
        if (error) {
            alert(error.apierror);
        } else {
            var form = $("form.payment");
            var token = result.token;
            form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
            form.get(0).submit();
        }
    }
</script>
<div class="error" style="display: none">
<ul id="errors">
</ul>
</div>
{if $paymillError == 1}
<div class="error">
    <ul>
        <li>Bitte geben Sie Ihre Kreditkartendaten ein. Aus Sicherheitsgründen speichern wir diese Nicht auf unserem Server.</li>
    </ul>
</div>
{/if}
<div class="debit">
    <p>
        <img src="{link file='frontend/_resources/icon_mastercard.png'}" />
        <img src="{link file='frontend/_resources/icon_visa.png'}" />
    </p>
    <p class="none">
        <label>Kreditkarten-nummer *</label>
        <input id="card-number" type="text" size="20" class="text" />
    </p>
    <p class="none">
        <label>CVC</label>
        <input id="card-cvc" type="text" size="4" class="text" />
    </p>
    <p class="none">
        <label>Gültig bis (MM/YYYY) *</label>
        <input id="card-expiry-month" type="text" style="width: 30px; display: inline-block;" class="text" />
        <input id="card-expiry-year" type="text" style="width: 60px; display: inline-block;" class="text" />
    </p>
    <p class="description">Die mit einem * markierten Felder sind Pflichtfelder.
    </p>
    {if $paymillShowLabel == 1}
    <p><div class="paymill_powered"><div class="paymill_credits">Sichere Kreditkartenzahlung powered by <a href="http://www.paymill.de" target="_blank">Paymill</a></div></div></p>
    {/if}
</div>

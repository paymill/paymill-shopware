<script type="text/javascript">
  var PAYMILL_PUBLIC_KEY = '{$publicKey}';
</script>
<script type="text/javascript" src="{$bridgeUrl}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        var paymill_form_id = "payment_mean{$payment_mean.id}";
        $("form.payment").submit(function(event) {
            if ($('#' + paymill_form_id).attr("checked") == "checked") {
                paymill.createToken({
                    number: $('#card-number').val(), 
                    exp_month: $('#card-expiry-month').val(), 
                    exp_year: $('#card-expiry-year').val(), 
                    cvc: $('#card-cvc').val()
                }, PaymillResponseHandler);
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
{if $paymillError == 1}
<div class="error">
    <ul>
        <li>Bitte geben Sie Ihre Kreditkartendaten ein. Aus Sicherheitsgründen speichern wir diese Nicht auf unserem Server.</li>
    </ul>
</div>
{/if}
<div class="debit">
    <p class="none">
        <label>Kreditkarten-nummer</label>
        <input id="card-number" type="text" size="20" class="text" value="4111111111111111"/>
    </p>
    <p class="none">
        <label>CVC</label>
        <input id="card-cvc" type="text" size="4" class="text" value="555"/>
    </p>
    <p class="none">
        <label>Gültig bis (MM/YYYY)</label>
        <input id="card-expiry-month" type="text" style="width: 30px; display: inline-block;" class="text" value="06"/>
        <input id="card-expiry-year" type="text" style="width: 60px; display: inline-block;" class="text" value="2019"/>
    </p>
    <p class="description">Die mit einem * markierten Felder sind Pflichtfelder.
    </p>
</div>

<script type = "text/javascript" >
var paymilliFrameOptions = {
    labels: {
      number:     '{s namespace=Paymill name=frontend_creditcard_label_number}Credit Card Number{/s}',
      cvc:        '{s namespace=Paymill name=frontend_creditcard_label_cvc}CVC {/s}',
      cardholder: '{s namespace=Paymill name=frontend_creditcard_label_holder}Credit Card Holder{/s}',
      exp:        '{s namespace=Paymill name=frontend_creditcard_label_valid}Valid until (MM/YYYY){/s}'
    },
    placeholders: {
      number:     '',
      cvc:        '',
      cardholder: '',
      exp_month:  '',
      exp_year:   ''
    },
    errors: {
      number:     API_ERRORS["PAYMILL_field_invalid_card_number"],
      cvc:        API_ERRORS["PAYMILL_field_invalid_card_cvc"],
      exp:        API_ERRORS["PAYMILL_field_invalid_card_exp"]
    }        
};
{if $paymillStylesheetURL}
    paymilliFrameOptions.stylesheet = '{$paymillStylesheetURL}';
{/if}
    
function PaymillFrameResponseHandler(error, result)
{
    if (error) {
        debug("iFrame load failed with " + error.apierror + error.message);
    } else {
        debug("iFrame successfully loaded");
    }
}

function paymillEmbedFrame()
{
    PAYMILL_FASTCHECKOUT_CC_CHANGED = true;
    paymill.embedFrame('paymillFormContainer', paymilliFrameOptions,  PaymillFrameResponseHandler);
}
    
{if $paymillCardNumber === '' || $paymillMonth === '' || $paymillYear === ''}
    paymillEmbedFrame();
</script >
{else}
    </script >
    <table id="paymillFastCheckoutTable">
        <tr>
            <td>{s namespace=Paymill name=frontend_creditcard_label_number}Credit Card Number{/s}: </td>
            <td id="paymillFcCardNumber" class="paymill-card-number-{$paymillBrand}">{$paymillCardNumber}</td>
        </tr>
        <tr>
            <td>{s namespace=Paymill name=frontend_creditcard_label_cvc}CVC {/s}: </td>
            <td>{$paymillCvc}</td>
        </tr>
        <tr>
            <td>{s namespace=Paymill name=frontend_creditcard_label_holder}Credit Card Holder{/s}: </td>
            <td>{$paymillCardHolder}</td>
        </tr>
        <tr>
            <td>{s namespace=Paymill name=frontend_creditcard_label_valid}Valid until (MM/YYYY){/s}: </td>
            <td>{$paymillMonth}/{$paymillYear}</td>
        </tr>
        <tr>
        <td></td>
        <td>
        <button id="paymillFastCheckoutIframeChange" class="button-middle small" type="button">{s namespace=Paymill name=frontend_paymill_change_button}Change{/s}</button>
        </td>
        </tr>
    </table>
{/if}

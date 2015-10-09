<script type = "text/javascript" >

function PaymillFrameResponseHandler(error, result)
{
    if (error) {
        console.log("iFrame load failed with " + error.apierror + error.message);
    } else {
        console.log("iFrame successfully loaded");
    }
}

function paymillEmbedFrame()
{
    PAYMILL_FASTCHECKOUT_CC_CHANGED = true;
    var paymillOptions = {literal}{lang:'en'}{/literal};
    paymillOptions.lang = '{s namespace=frontend/paym_payment_creditcard/checkout/form name=frontend_iframe_lang}en{/s}';
    paymill.embedFrame('paymillFormContainer', paymillOptions, PaymillFrameResponseHandler);
}
    
{if $paymillCardNumber === '' || $paymillMonth === '' || $paymillYear === ''}
    paymillEmbedFrame();
</script >
{else}
    </script >
    <table id="paymillFastCheckoutTable" style="clear: both">
        <tr>
            <td>{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_cardnumber}Credit Card Number{/s}: </td>
            <td id="paymillFcCardNumber" class="paymill-card-number-{$paymillBrand}">{$paymillCardNumber}</td>
        </tr>
        <tr>
            <td>{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_cvc}CVC {/s}: </td>
            <td>{$paymillCvc}</td>
        </tr>
        <tr>
            <td>{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_holder}Credit Card Holder{/s}: </td>
            <td>{$paymillCardHolder}</td>
        </tr>
        <tr>
            <td>{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_expirydate}Valid until (MM/YYYY){/s}: </td>
            <td>{$paymillMonth}/{$paymillYear}</td>
        </tr>
        <tr>
        <td></td>
        <td>
        <button id="paymillFastCheckoutIframeChange" class="button-middle small" type="button">{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_change}Change{/s}</button>
        </td>
        </tr>
    </table>
{/if}

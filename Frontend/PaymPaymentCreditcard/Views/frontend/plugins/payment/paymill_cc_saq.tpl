<script type = "text/javascript" >
paymilliFrameOptions = {
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
paymill.embedFrame('paymillFormContainer', paymilliFrameOptions,  function(error) {
    if (error) {
        debug("iFrame load failed with " + error.apierror + error.message);
    } else {
        debug("iFrame successfully loaded");
    }
 );
</script >
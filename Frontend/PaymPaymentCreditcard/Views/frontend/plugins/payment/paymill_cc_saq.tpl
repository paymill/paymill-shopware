<script type = "text/javascript" >
paymill.embedFrame('paymillFormContainer', {
  stylesheet: 'https://payintelligent.net/dev/paymill/default/shopware/436_ce/templates/_default/frontend/_resources/styles/framework.css',
  labels: {
    number:     '{s namespace=Paymill name=frontend_creditcard_label_number}Credit Card Number{/s}',
    cvc:        '{s namespace=Paymill name=frontend_creditcard_label_cvc}CVC {/s}',
    cardholder: '{s namespace=Paymill name=frontend_creditcard_label_holder}Credit Card Holder{/s}',
    exp:        '{s namespace=Paymill name=frontend_creditcard_label_valid}Valid until (MM/YYYY){/s}'
  },
  placeholders: {
    number:     '',
    cvc:        '',
    cardholder: '{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}',
    exp_month:  '',
    exp_year:   ''
  }
}, function PaymillResponseHandler(error, result)
    {
        console.log(error);
    } );
</script >
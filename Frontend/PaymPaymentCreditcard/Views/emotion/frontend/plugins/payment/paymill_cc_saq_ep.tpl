{if $pigmbhTemplateActive == 1}
                <div class = "form-group" >
                    <label class = "col-lg-4 control-label"
                           for = "card-holder" >{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_holder}Credit Card Holder{/s} *</label >

                    <div class = "col-lg-6" >
                        <input id = "card-holder" type = "text" size = "20" class = "form-control"
                               value = "{$paymillCardHolder}" />
                    </div >
                </div >
                <div class = "form-group" >
                    <label class = "col-lg-4 control-label"
                           for = "card-number" >{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_cardnumber}Credit Card Number{/s} *</label >

                    <div class = "col-lg-6" >
                        <input id = "card-number" type = "text" size = "20" class = "form-control"
                               value = "{$paymillCardNumber}" />
                    </div >
                </div >
                <div class = "form-group" >
                <span class = "col-lg-4 control-label" >
                <label for = "card-cvc" >{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_cvc}CVC {/s} *</label >
                <span class = "paymill-tooltip"
                      title = "{s namespace=frontend/paym_payment_creditcard/checkout/form name=tooltip_creditcard_cvc}What is a CVV/CVC number? Prospective credit cards will have a 3 to 4-digit number, usually on the back of the card. It ascertains that the payment is carried out by the credit card holder and the card account is legitimate. On Visa the CVV (Card Verification Value) appears after and to the right of your card number. Same goes for Mastercard's CVC (Card Verfication Code), which also appears after and to the right of  your card number, and has 3-digits. Diners Club, Discover, and JCB credit and debit cards have a three-digit card security code which also appears after and to the right of your card number. The American Express CID (Card Identification Number) is a 4-digit number printed on the front of your card. It appears above and to the right of your card number. On Maestro the CVV appears after and to the right of your number. If you don’t have a CVV for your Maestro card you can use 000.{/s}" >?</span >
                </span >

                    <div class = "col-lg-6" >
                        <input id = "card-cvc" type = "text" size = "20" class = "form-control"
                               value = "{$paymillCvc}" />
                    </div >
                </div >
                <div class = "form-group" >
                    <label class = "col-lg-4 control-label"
                           for = "card-expiry-month" >{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_expirydate}Valid until (MM/YYYY){/s} *</label >

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
                    <label >{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_holder}Credit Card Holder{/s} *</label >
                    <input id = "card-holder" type = "text" size = "20" class = "text"
                           value = "{$paymillCardHolder}" />
                </p >
                <p class = "none" >
                    <label >{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_cardnumber}Credit Card Number{/s} *</label >
                    <input id = "card-number" type = "text" size = "20" class = "text"
                           value = "{$paymillCardNumber}" />
                </p >
                <p class = "none" >
                    <label >{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_cvc}CVC{/s} *</label >
                    <input id = "card-cvc" type = "text" size = "4" class = "text" value = "{$paymillCvc}" />
                <span class = "paymill-tooltip"
                      title = "{s namespace=frontend/paym_payment_creditcard/checkout/form name=tooltip_creditcard_cvc} What is a CVV/CVC number? Prospective credit cards will have a 3 to 4-digit number, usually on the back of the card. It ascertains that the payment is carried out by the credit card holder and the card account is legitimate. On Visa the CVV (Card Verification Value) appears after and to the right of your card number. Same goes for Mastercard's CVC (Card Verfication Code), which also appears after and to the right of  your card number, and has 3-digits. Diners Club, Discover, and JCB credit and debit cards have a three-digit card security code which also appears after and to the right of your card number. The American Express CID (Card Identification Number) is a 4-digit number printed on the front of your card. It appears above and to the right of your card number. On Maestro the CVV appears after and to the right of your number. If you don’t have a CVV for your Maestro card you can use 000.{/s}" >?</span >
                </p >
                <p class = "none" >
                    <label >{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_creditcard_expirydate}Valid until (MM/YYYY){/s} *</label >
                    <input id = "card-expiry-month" type = "text" style = "width: 30px; display: inline-block;"
                                                                        class = "text"
                           value = "{$paymillMonth}" />
                    <input id = "card-expiry-year" type = "text" style = "width: 60px; display: inline-block;"
                                                                        class = "text"
                           value = "{$paymillYear}" />
                </p >
{/if}
<p class = "description" >{s namespace=frontend/paym_payment_creditcard/checkout/form name=frontend_paymill_required}Fields marked with a * are required.{/s}</p >
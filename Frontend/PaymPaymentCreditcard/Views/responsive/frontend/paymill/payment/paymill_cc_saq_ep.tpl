<div class="method--bankdata">
    <div class="debit">
        <p class = "none" >
            <input id = "card-holder" type = "text" size = "20" class = "text" value = "{$paymillCardHolder}"  placeholder="{s namespace=frontend/paym_payment_creditcard/form name=label_creditcard_holder}Credit Card Holder{/s} *"/>
        </p >
        <p class = "none" >
            <input id = "card-number" type = "text" size = "20" class = "text" value = "{$paymillCardNumber}"  placeholder="{s namespace=frontend/paym_payment_creditcard/form name=label_creditcard_cardnumber}Credit Card Number{/s} *"/>
        </p >
        <p class = "none" >
            <input id = "card-cvc" type = "text" size = "4" class = "text" value = "{$paymillCvc}" placeholder="{s namespace=frontend/paym_payment_creditcard/form name=label_creditcard_cvc}CVC{/s} *" />
            <span class = "paymill-tooltip" title = "{s namespace=frontend/paym_payment_creditcard/form name=tooltip_creditcard_cvc} What is a CVV/CVC number? Prospective credit cards will have a 3 to 4-digit number, usually on the back of the card. It ascertains that the payment is carried out by the credit card holder and the card account is legitimate. On Visa the CVV (Card Verification Value) appears after and to the right of your card number. Same goes for Mastercard's CVC (Card Verfication Code), which also appears after and to the right of  your card number, and has 3-digits. Diners Club, Discover, and JCB credit and debit cards have a three-digit card security code which also appears after and to the right of your card number. The American Express CID (Card Identification Number) is a 4-digit number printed on the front of your card. It appears above and to the right of your card number. On Maestro the CVV appears after and to the right of your number. If you don’t have a CVV for your Maestro card you can use 000.{/s}" >
                <i class="icon--service"></i>
            </span >
        </p >
        <p class = "none" >
            <input id = "card-expiry-month" type = "text" style = "width: 65px; display: inline-block;" class = "text" value = "{$paymillMonth}" placeholder="MM *"/>
            <input id = "card-expiry-year" type = "text" style = "width: 80px; display: inline-block;" class = "text" value = "{$paymillYear}" placeholder="YYYY *"/>
        </p >
    </div>    
</div>
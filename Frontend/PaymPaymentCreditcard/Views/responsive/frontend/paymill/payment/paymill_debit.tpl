<div class="method--bankdata">
    <div class="debit">
        <p class = "none" >
            <input id = "paymill_accountholder" type = "text" size = "20" class = "text" value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" placeholder="{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_directdebit_accountholder}Account Holder{/s} *" />
        </p >
        <p class = "none" >
            <input id = "paymill_iban" type = "text" size = "4" class = "text" value = "{$paymillAccountNumber}" placeholder="{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_directdebit_accountnumber}Account Number{/s}/{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_directdebit_iban}IBAN{/s} *"/>
        </p >
        <p class = "none" >
            <input id = "paymill_bic" type = "text" size = "4" class = "text" value = "{$paymillBankCode}"  placeholder="{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_directdebit_bankcode}Bankcode{/s}/{s namespace=frontend/paym_payment_creditcard/checkout/form name=label_directdebit_bic}BIC{/s} *"/>
        </p >
    </div>
</div>
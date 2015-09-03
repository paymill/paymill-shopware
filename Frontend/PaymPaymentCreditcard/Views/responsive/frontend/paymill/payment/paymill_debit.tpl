<div class="method--bankdata">
    <div class="debit">
        <p class = "none" >
            <input id = "paymill_accountholder" type = "text" size = "20" class = "text" value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" placeholder="{s namespace=Paymill name=frontend_directdebit_label_holder}Account Holder{/s} *" />
        </p >
        <p class = "none" >
            <input id = "paymill_iban" type = "text" size = "4" class = "text" value = "{$paymillAccountNumber}" placeholder="{s namespace=Paymill name=frontend_directdebit_label_number}Account Number{/s}/{s namespace=Paymill name=frontend_directdebit_label_iban}IBAN{/s} *"/>
        </p >
        <p class = "none" >
            <input id = "paymill_bic" type = "text" size = "4" class = "text" value = "{$paymillBankCode}"  placeholder="{s namespace=Paymill name=frontend_directdebit_label_bankcode}Bankcode{/s}/{s namespace=Paymill name=frontend_directdebit_label_bic}BIC{/s} *"/>
        </p >
    </div>
</div>
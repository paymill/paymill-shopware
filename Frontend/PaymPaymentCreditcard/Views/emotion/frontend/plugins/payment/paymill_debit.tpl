{if $pigmbhTemplateActive == 1}
    <div class = "form-group" >
        <label class = "col-lg-4 control-label"
               for = "paymill_accountholder" >{s namespace=Paymill name=frontend_directdebit_label_holder}Account Holder{/s} *</label >

        <div class = "col-lg-6" >
            <input id = "paymill_accountholder" type = "text" size = "20" class = "form-control"
                   value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
        </div >
    </div >
    <div class = "form-group" >
        <label class = "col-lg-4 control-label"
               for = "paymill_iban" >{s namespace=Paymill name=frontend_directdebit_label_number}Account Number{/s}/{s namespace=Paymill name=frontend_directdebit_label_iban}IBAN{/s} *</label >

        <div class = "col-lg-6" >
            <input id = "paymill_iban" type = "text" size = "20" class = "form-control"
                   value = "{$paymillAccountNumber}" />
        </div >
    </div >
    <div class = "form-group" >
        <label class = "col-lg-4 control-label"
               for = "paymill_bic" >{s namespace=Paymill name=frontend_directdebit_label_bankcode}Bankcode{/s}/{s namespace=Paymill name=frontend_directdebit_label_bic}BIC{/s} *</label >

        <div class = "col-lg-6" >
            <input id = "paymill_bic" type = "text" size = "20" class = "form-control"
                   value = "{$paymillBankCode}" />
        </div >
    </div >
{else}
    <p class = "none" >
        <label for = "paymill_accountholder" >{s namespace=Paymill name=frontend_directdebit_label_holder}Account Holder{/s} *</label >
        <input id = "paymill_accountholder" type = "text" size = "20" class = "text"
               value = "{$sUserData['billingaddress']['firstname']} {$sUserData['billingaddress']['lastname']}" />
    </p >
    <p class = "none" >
        <label for = "paymill_iban" >{s namespace=Paymill name=frontend_directdebit_label_number}Account Number{/s}/{s namespace=Paymill name=frontend_directdebit_label_iban}IBAN{/s} *</label >
        <input id = "paymill_iban" type = "text" size = "4" class = "text"
               value = "{$paymillAccountNumber}" />
    </p >
    <p class = "none" >
        <label for = "paymill_bic" >{s namespace=Paymill name=frontend_directdebit_label_bankcode}Bankcode{/s}/{s namespace=Paymill name=frontend_directdebit_label_bic}BIC{/s} *</label >
        <input id = "paymill_bic" type = "text" size = "4" class = "text"
               value = "{$paymillBankCode}" />
    </p >
{/if}
<p class = "description" >{s namespace=Paymill name=feedback_info_general_required}Fields marked with a * are required.{/s}</p >

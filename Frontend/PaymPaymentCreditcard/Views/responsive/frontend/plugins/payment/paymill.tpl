
<link rel = "stylesheet" type = "text/css" href = "{link file='frontend/_resources/paymill_styles.css'}" />

<div class = "error" style = "display: none" >
    {if $payment_mean.name == 'paymillcc'}
        <li >{s namespace=Paymill name=feedback_error_creditcard_parent}Please enter your credit card information. For security reason we will not save them on our system.{/s}</li >
        <ul id = "errorsCc" ></ul >
    {/if}

    {if $payment_mean.name == 'paymilldebit'}
        <li >{s namespace=Paymill name=feedback_error_directdebit_parent}Please enter your accountdata. For security reason we will not save them on our system.{/s}</li >
        <ul id = "errorsElv" ></ul >
    {/if}
</div >

{if $Controller != "account"}
    <div class = "debit" id='paymillFormContainer'>
    {if $payment_mean.name == 'paymillcc'}
        {if {config name=paymillBrandIconAmex}}<div class="paymill-card-icon paymill-card-number-amex"></div>{/if}
        {if {config name=paymillBrandIconCartaSi}}<div class="paymill-card-icon paymill-card-number-carta-si"></div>{/if}
        {if {config name=paymillBrandIconCarteBleue}}<div class="paymill-card-icon paymill-card-number-carte-bleue"></div>{/if}
        {if {config name=paymillBrandIconDankort}}<div class="paymill-card-icon paymill-card-number-dankort"></div>{/if}
        {if {config name=paymillBrandIconDinersclub}}<div class="paymill-card-icon paymill-card-number-diners-club"></div>{/if}
        {if {config name=paymillBrandIconDiscover}}<div class="paymill-card-icon paymill-card-number-discover"></div>{/if}
        {if {config name=paymillBrandIconJcb}}<div class="paymill-card-icon paymill-card-number-jcb"></div>{/if}
        {if {config name=paymillBrandIconMaestro}}<div class="paymill-card-icon paymill-card-number-maestro"></div>{/if}
        {if {config name=paymillBrandIconMastercard}}<div class="paymill-card-icon paymill-card-number-mastercard"></div>{/if}
        {if {config name=paymillBrandIconVisa}}<div class="paymill-card-icon paymill-card-number-visa"></div>{/if}
        {if {config name=paymillBrandIconUnionpay}}<div class="paymill-card-icon paymill-card-number-china-unionpay"></div>{/if}
        TEST
        
        {*
        {if $paymillPCI == '0'}
            {include file='frontend/plugins/payment/paymill_cc_saq.tpl'}
        {else}
            {include file='frontend/plugins/payment/paymill_cc_saq_ep.tpl'}
        {/if}
        *}
        
    {/if}

    {if $payment_mean.name == 'paymilldebit' }
        {include file='frontend/plugins/payment/paymill_debit.tpl'}
    {/if}
    </div >
{/if}
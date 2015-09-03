{extends file="parent:frontend/checkout/confirm.tpl"}
{block name='frontend_checkout_confirm_error_messages' append}
    {if $pigmbhErrorMessage} 
        <div class="grid_20 {$pigmbhErrorClass}">
            <div class="error">
                <div class="center">
                    <strong> {$pigmbhErrorMessage} </strong>
                </div>
            </div>
        </div>
    {/if}
{/block}
{block name="frontend_checkout_confirm_product_table" prepend}
    {if $sPayment.name === 'paymillcc' || $sPayment.name === 'paymilldebit'}
    <div class="panel has--border">
        <div class="panel--title is--underline">PAYMILL {$sPayment.description}</div>
        <div class="panel--body is--rounded">
            {if $sPayment.name === 'paymillcc'}
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
                {if $paymillPCI == '0'}
                    {include file='frontend/paymill/payment/paymill_cc_saq.tpl'}
                {else}
                    {include file='frontend/paymill/payment/paymill_cc_saq_ep.tpl'}
                {/if}
            {elseif $sPayment.name === 'paymilldebit'}
                {include file='frontend/paymill/payment/paymill_debit.tpl'}
            {/if}
        </div>
    </div>
    {/if}
{/block}
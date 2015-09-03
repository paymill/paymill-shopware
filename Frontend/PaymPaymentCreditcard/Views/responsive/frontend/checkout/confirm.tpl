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
    {if $sPayment.name === 'paymillcc' || $sPayment.name === 'paymillelv'}
    <div class="panel has--border">
        <div class="panel--title is--underline">PAYMILL {$sPayment.description}</div>
        <div class="panel--body is--rounded">
            {* @todo load payment form *}
        </div>
    </div>
    {/if}
{/block}
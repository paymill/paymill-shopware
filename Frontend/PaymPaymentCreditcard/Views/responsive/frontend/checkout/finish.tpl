{extends file="parent:frontend/checkout/finish.tpl"}
{* Transaction number *}
{block name='frontend_checkout_finish_transaction_number' append}
    {if $sepaDate}
    <p>
        <strong>{s namespace=frontend/paym_payment_creditcard/finish name=feedback_info_sepa_date}The direct debit is drawn to the following date{/s}:</strong> {$sepaDate}
    </p>
    {/if}
{/block}
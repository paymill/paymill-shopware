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
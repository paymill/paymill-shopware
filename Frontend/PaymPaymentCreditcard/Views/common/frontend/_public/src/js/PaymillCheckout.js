function debug(message)
{
    if(paymillcheckout.debug){
        console.log("[" + getPayment() + "] " + message);
    }
}

function getPayment()
{
    return paymillcheckout.tokenPayment;
}

function hasDummyData()
{
    if (getPayment() === 'paymillcc') { //If CC
        if (!paymillcheckout.iframe.active) { //if not iframe solutin
            var cardNumber = $('#card-number').val();
            var validMonth = $('#card-expiry-month').val();
            var validYear = $('#card-expiry-year').val();

            debug(cardNumber);
            debug(validMonth);
            debug(validYear);

            if ((cardNumber === "" || validMonth === "" || validYear === "") || (paymillcheckout.fastcheckout.creditcard.cardNumber !== cardNumber) || (paymillcheckout.fastcheckout.creditcard.month !== validMonth) || (paymillcheckout.fastcheckout.creditcard.year !== validYear)) {
                debug("Creditcard information found. New Information will be used. Token should be getting generated.");
                return false;
            }
        } else if (paymillcheckout.fastcheckout.changed) {
            return false;
        }
    }

    if (getPayment() === 'paymilldebit') {
        var iban = $('#paymill_iban').val();
        var bic = $('#paymill_bic').val();
        if ((iban === "" || bic === "") || (paymillcheckout.fastcheckout.directdebit.accountnumber !== iban) || (paymillcheckout.fastcheckout.directdebit.bankcode !== bic)) {
            debug("Direct Debit information found. New Information will be used. Token should be getting generated.");
            return false;
        }
    }
    debug("Fast Checkout Data found and not altered. Will process with given data. Validation will be skipped.");
    return true;
}

function validate()
{
    debug("Paymill handler triggered");
    var errorsCc = $("#errorsCc");
    errorsCc.parents('.error').hide();
    errorsCc.html("");
    var errorsElv = $("#errorsElv");
    errorsElv.parents('.error').hide();
    errorsElv.html("");
    var result = true;
    if (getPayment() === 'paymillcc') { //If CC 
        if (!paymillcheckout.iframe.active) { // if not iframe solution
            if (!paymill.validateHolder($('#card-holder').val())) {
                errorsCc.append("<div>"+paymillcheckout.errormessages.validation.creditcard.cardholder+"</div>");
                result = false;
            }
            if (!paymill.validateCardNumber($('#card-number').val())) {
                errorsCc.append("<div>"+paymillcheckout.errormessages.validation.creditcard.cardnumber+"</div>");
                result = false;
            }
            if (!paymill.validateCvc($('#card-cvc').val())) {
                if (paymillcheckout.validateCvc) {
                    errorsCc.append("<div>"+paymillcheckout.errormessages.validation.creditcard.cvc+"</div>");
                    result = false;
                }
            }
            if (/^\d\d$/.test($('#card-expiry-year').val())) {
                $('#card-expiry-year').val("20" + $('#card-expiry-year').val());
            }
            if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {
                errorsCc.append("<div>"+paymillcheckout.errormessages.validation.creditcard.expirydate+"</div>");
                result = false;
            }
            if (!result) {
                errorsCc.parents('.error').show();
            } else {
                debug("Validations successful");
            }
        } else {
            result = true;
            debug("No validation, because of iFrame Solution.");
        }
    }

    if (getPayment() === 'paymilldebit') { //If ELV
        if (!paymill.validateHolder($('#paymill_accountholder').val())) {
            errorsElv.append("<div>"+paymillcheckout.errormessages.validation.directdebit.accountholder+"</div>");
            result = false;
        }
        if (isSepa()) {
            var iban = new Iban();
            if (!iban.validate($('#paymill_iban').val())) {
                errorsElv.append("<div>"+paymillcheckout.errormessages.validation.directdebit.iban+"</div>");
                result = false;
            }

            if ($('#paymill_bic').val() === '') {
                errorsElv.append("<div>"+paymillcheckout.errormessages.validation.directdebit.bic+"</div>");
                result = false;
            }
        } else {
            if (!paymill.validateAccountNumber($('#paymill_iban').val())) {
                errorsElv.append("<div>"+paymillcheckout.errormessages.validation.directdebit.accountnumber+"</div>");
                result = false;
            }

            if (!paymill.validateBankCode($('#paymill_bic').val())) {
                errorsElv.append("<div>"+paymillcheckout.errormessages.validation.directdebit.bankcode+"</div>");
                result = false;
            }
        }
        if (!result) {
            errorsElv.parents('.error').show();
        } else {
            debug("Validations successful");
        }
    }
    return result;
}

function paymillResponseHandler(error, result)
{
    $('button[type="submit"]').removeAttr("disabled");
    debug("Started Paymill response handler");
    if (error) {
        errorText = paymillcheckout.errormessages.bridge[error.apierror];
        debug(errorText);
        alert(errorText);
    } else {
        debug("Received token from Paymill API: " + result.token);
        paymillSubmitForm(result.token);
    }
}

function paymillSubmitForm(token){
    var form = $('#confirm--form');
    var name;
    if(getPayment() === 'paymillcc'){
        name = $('#card-holder').val();    
    }else{
        name = $('#paymill_accountholder').val();
    }
    form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
    form.append("<input type='hidden' name='paymillName' value='" + name + "'/>");
    form.submit();
}

function isSepa() {
    var reg = new RegExp(/^\D\D/);
    return reg.test($('#paymill_iban').val());
}

$(document).ready(function ()
{
    var paymill_form_id = "payment_mean{$payment_mean.id}";
    $('#card-number').keyup(function ()
    {
        $("#card-number")[0].className = $("#card-number")[0].className.replace(/paymill-card-number-.*/g, '');
        var detector = new BrandDetection();
        var brand = detector.detect($('#card-number').val());

        if (detector.validate($('#card-number').val())) {
            suffix = '';
        } else {
            suffix = '-temp';
        }
        if ($.inArray(brand, paymillcheckout.activeBrands) !== -1) {
            $('#card-number').addClass("paymill-card-number-" + brand + suffix);
        }
    });

    $('button[type="submit"][form="confirm--form"]').click(function ()
    {
        debug('Event triggered');
        /* prevend token generation when token already exsist */
        if ($("input[type='checkbox'][name='paymillToken']").length) {
            return true;
        }
        $(this).attr("disabled","disabled");
        debug('Check for FastCheckout data.');
        if (hasDummyData()) {
            debug('Proceed Fastcheckout');
            paymillSubmitForm('NoTokenRequired');
        } else {
            debug('Validate data');
            if (validate()) {
                try {
                    if (getPayment() === 'paymillcc') { //If CC
                        if (!paymillcheckout.iframe.active) { //if not iFrame Solution
                            if (paymillcheckout.validateCvc) {
                                paymill.createToken({
                                    number: $('#card-number').val(),
                                    cardholder: $('#card-holder').val(),
                                    exp_month: $('#card-expiry-month').val(),
                                    exp_year: $('#card-expiry-year').val(),
                                    cvc: $('#card-cvc').val(),
                                    amount_int: paymillcheckout.tokenAmount,
                                    currency: paymillcheckout.tokenCurrency
                                }, paymillResponseHandler);
                            } else {
                                cvcInput = $('#card-cvc').val();
                                paymill.createToken({
                                    number: $('#card-number').val(),
                                    cardholder: $('#card-holder').val(),
                                    exp_month: $('#card-expiry-month').val(),
                                    exp_year: $('#card-expiry-year').val(),
                                    cvc: cvcInput === "" ? "000" : cvcInput,
                                    amount_int: paymillcheckout.tokenAmount,
                                    currency: paymillcheckout.tokenCurrency
                                }, paymillResponseHandler);
                            }
                        } else {
                            paymill.createTokenViaFrame({
                                amount_int: paymillcheckout.tokenAmount,
                                currency: paymillcheckout.tokenCurrency
                            }, paymillResponseHandler);
                        }
                    }
                    if (getPayment() === 'paymilldebit') { //If ELV
                        if (isSepa()) {
                            paymill.createToken({
                                iban: $('#paymill_iban').val(),
                                bic: $('#paymill_bic').val(),
                                accountholder: $('#paymill_accountholder').val()
                            }, paymillResponseHandler);
                        } else {
                            paymill.createToken({
                                number: $('#paymill_iban').val(),
                                bank: $('#paymill_bic').val(),
                                accountholder: $('#paymill_accountholder').val()
                            }, paymillResponseHandler);
                        }
                    }
                } catch (e) {
                    alert("Ein Fehler ist aufgetreten: " + e);
                    $(this).removeAttr("disabled");
                }
            } else {
                $(this).removeAttr("disabled");
                if (getPayment() === 'paymillcc') {
                    $('html, body').animate({
                        scrollTop: $("#errorsCc").offset().top - 100
                    }, 1000);
                }
                if (getPayment() === 'paymilldebit') {
                    $('html, body').animate({
                        scrollTop: $("#errorsElv").offset().top - 100
                    }, 1000);
                }
            }
        }
        return false;
    });

    $('#paymillFastCheckoutIframeChange').click(function (event) {
        $("#paymillFastCheckoutTable").remove();
        paymillEmbedFrame();
    });
});
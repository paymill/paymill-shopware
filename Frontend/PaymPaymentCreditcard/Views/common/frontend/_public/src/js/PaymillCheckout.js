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
                errorsCc.append("<li>"+paymillcheckout.errormessages.validation.creditcard.cardholder+"</li>");
                result = false;
            }
            if (!paymill.validateCardNumber($('#card-number').val())) {
                errorsCc.append("<li>"+paymillcheckout.errormessages.validation.creditcard.cardnumber+"</li>");
                result = false;
            }
            if (!paymill.validateCvc($('#card-cvc').val())) {
                if (paymillcheckout.validateCvc) {
                    errorsCc.append("<li>"+paymillcheckout.errormessages.validation.creditcard.cvc+"</li>");
                    result = false;
                }
            }
            if (/^\d\d$/.test($('#card-expiry-year').val())) {
                $('#card-expiry-year').val("20" + $('#card-expiry-year').val());
            }
            if (!paymill.validateExpiry($('#card-expiry-month').val(), $('#card-expiry-year').val())) {
                errorsCc.append("<li>"+paymillcheckout.errormessages.validation.creditcard.expirydate+"</li>");
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
            errorsElv.append("<li>"+paymillcheckout.errormessages.validation.directdebit.accountholder+"</li>");
            result = false;
        }
        if (isSepa()) {
            var iban = new Iban();
            if (!iban.validate($('#paymill_iban').val())) {
                errorsElv.append("<li>"+paymillcheckout.errormessages.validation.directdebit.iban+"</li>");
                result = false;
            }

            if ($('#paymill_bic').val() === '') {
                errorsElv.append("<li>"+paymillcheckout.errormessages.validation.directdebit.bic+"</li>");
                result = false;
            }
        } else {
            if (!paymill.validateAccountNumber($('#paymill_iban').val())) {
                errorsElv.append("<li>"+paymillcheckout.errormessages.validation.directdebit.accountnumber+"</li>");
                result = false;
            }

            if (!paymill.validateBankCode($('#paymill_bic').val())) {
                errorsElv.append("<li>"+paymillcheckout.errormessages.validation.directdebit.bankcode+"</li>");
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

function PaymillResponseHandler(error, result)
{
    debug("Started Paymill response handler");
    if (error) {
        errorText = paymillcheckout.errormessages.bridge[error.apierror];
        debug(errorText);
        alert(errorText);
        $('button[type="submit"]').prop("disabled", false);
    } else {
        debug("Received token from Paymill API: " + result.token);
        var form = $('#confirm--form');
        var token = result.token;
        form.append("<input type='hidden' name='paymillToken' value='" + token + "'/>");
        form.submit();
    }
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

    $('button[type="submit"]').click(function ()
    {
        /* prevend token generation when agb hasn't been accepted */
        if ($("input[type='checkbox'][name='sAGB']").length) {
            if ($("input[type='checkbox'][name='sAGB']").attr('checked') !== "checked") {
                return true;
            }
        }
        $(this).prop("disabled", true);
        if (hasDummyData()) {
            var form = $('#confirm--form');
            form.submit();
        } else {
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
                                }, PaymillResponseHandler);
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
                                }, PaymillResponseHandler);
                            }
                        } else {
                            paymill.createTokenViaFrame({
                                amount_int: paymillcheckout.tokenAmount,
                                currency: paymillcheckout.tokenCurrency
                            }, PaymillResponseHandler);
                        }
                    }
                    if (getPayment() === 'paymilldebit') { //If ELV
                        if (isSepa()) {
                            paymill.createToken({
                                iban: $('#paymill_iban').val(),
                                bic: $('#paymill_bic').val(),
                                accountholder: $('#paymill_accountholder').val()
                            }, PaymillResponseHandler);
                        } else {
                            paymill.createToken({
                                number: $('#paymill_iban').val(),
                                bank: $('#paymill_bic').val(),
                                accountholder: $('#paymill_accountholder').val()
                            }, PaymillResponseHandler);
                        }
                    }
                } catch (e) {
                    alert("Ein Fehler ist aufgetreten: " + e);
                    $(this).prop("disabled", false);
                }
            } else {
                $(this).prop("disabled", false);
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
    });

    $('#paymillFastCheckoutIframeChange').click(function (event) {
        $("#paymillFastCheckoutTable").remove();
        paymillEmbedFrame();
    });
});